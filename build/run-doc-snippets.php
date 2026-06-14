<?php

declare(strict_types=1);

/**
 * Executes every ```phel snippet in content/ against the real Phel runtime,
 * so documentation cannot silently rot when the language changes.
 *
 * Each snippet runs in an isolated `phel run` subprocess with a wall-clock
 * timeout, so a hanging or memory-heavy snippet cannot take down the suite.
 *
 * Not every snippet is a self-contained program: REPL transcripts, syntax
 * templates and narrative continuations exist by design. Those are either
 * auto-skipped (see SnippetExtractor) or recorded in a checked-in baseline
 * (build/doc-snippets-baseline.json), keyed by snippet content so the entry
 * survives line moves. The suite fails on:
 *   - a NEW failure not present in the baseline (a regression), or
 *   - a baseline entry that now passes (ratchet: remove it).
 *
 * Usage:
 *   php build/run-doc-snippets.php                 # check against baseline
 *   php build/run-doc-snippets.php --update-baseline
 *   php build/run-doc-snippets.php --verbose [path ...]
 */

require __DIR__ . '/../vendor/autoload.php';

use PhelWeb\DocSnippet\SnippetExtractor;

$root = dirname(__DIR__);
$baselinePath = $root . '/build/doc-snippets-baseline.json';

$argvRest = array_slice($argv, 1);
$verbose = in_array('--verbose', $argvRest, true);
$updateBaseline = in_array('--update-baseline', $argvRest, true);
$paths = array_values(array_filter($argvRest, static fn(string $a): bool => !str_starts_with($a, '--')));
if ($paths === []) {
    $paths = [$root . '/content'];
}

$PHEL = $root . '/vendor/bin/phel';
$TIMEOUT_BIN = trim((string) shell_exec('command -v timeout gtimeout 2>/dev/null | head -1'));
$TIMEOUT_SECS = 15;
$CONCURRENCY = 8;

$extractor = new SnippetExtractor();

/** Stable per-snippet key, independent of its line number. */
$keyOf = static function (string $relFile, string $code): string {
    $normalized = preg_replace('/\s+/', ' ', trim($code));
    return $relFile . '#' . substr(sha1((string) $normalized), 0, 12);
};

// Collect markdown files.
$files = [];
foreach ($paths as $path) {
    if (is_file($path)) {
        $files[] = $path;
        continue;
    }
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
    foreach ($rii as $f) {
        if ($f->isFile() && $f->getExtension() === 'md') {
            $files[] = $f->getPathname();
        }
    }
}
sort($files);

// Extract snippets, write runnable temp files.
$tmpDir = sys_get_temp_dir() . '/phel-doc-snippets-' . getmypid();
@mkdir($tmpDir);

$jobs = [];
$skipped = 0;
$index = 0;
foreach ($files as $file) {
    $rel = str_replace($root . '/', '', $file);
    foreach ($extractor->extractFromFile($file) as $snippet) {
        if ($snippet['skip']) {
            $skipped++;
            continue;
        }
        $index++;
        $code = $snippet['code'];
        $hasNs = preg_match('/^\s*\(ns\s/', $code) === 1;
        $source = $hasNs ? $code : "(ns doctest\\s{$index})\n{$code}";
        $tmpFile = "{$tmpDir}/s{$index}.phel";
        file_put_contents($tmpFile, $source);
        $jobs[] = [
            'key' => $keyOf($rel, $code),
            'location' => "{$rel}:{$snippet['startLine']}",
            'file' => $tmpFile,
        ];
    }
}

$total = count($jobs);
fwrite(STDERR, "Running {$total} snippets ({$skipped} skipped) ...\n");

$cmd = static function (string $file) use ($TIMEOUT_BIN, $TIMEOUT_SECS, $PHEL): string {
    $run = escapeshellarg($PHEL) . ' run ' . escapeshellarg($file);
    if ($TIMEOUT_BIN !== '') {
        return escapeshellarg($TIMEOUT_BIN) . ' ' . $TIMEOUT_SECS . ' ' . $run;
    }
    return $run;
};

$startProc = static function (array $job) use ($cmd, $tmpDir): array {
    $descriptor = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    // Each snippet gets its own TMPDIR. Phel resolves its compile-cache dir
    // from sys_get_temp_dir() (which honours TMPDIR), and writes a temp
    // compiled PHP file keyed by content hash. Two concurrent snippets that
    // compile to the same hash would otherwise race on that shared file (one
    // unlinks it while the other requires it). An isolated TMPDIR per process
    // removes the collision. cwd is also the isolated dir so filesystem side
    // effects cannot litter the repo.
    $procTmp = $tmpDir . '/' . basename($job['file'], '.phel');
    @mkdir($procTmp);
    $env = getenv();
    $env['TMPDIR'] = $procTmp;
    $proc = proc_open($cmd($job['file']), $descriptor, $pipes, $procTmp, $env);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);
    return ['job' => $job, 'proc' => $proc, 'pipes' => $pipes, 'out' => '', 'err' => ''];
};

// Run with bounded concurrency.
$running = [];
$failed = [];   // key => ['location' => ..., 'note' => ...]
$passedKeys = [];
$passed = 0;
$queue = $jobs;

while ($queue !== [] || $running !== []) {
    while (count($running) < $CONCURRENCY && $queue !== []) {
        $running[] = $startProc(array_shift($queue));
    }

    foreach ($running as $rk => &$r) {
        $r['out'] .= (string) stream_get_contents($r['pipes'][1]);
        $r['err'] .= (string) stream_get_contents($r['pipes'][2]);
        $status = proc_get_status($r['proc']);
        if ($status['running']) {
            continue;
        }
        $r['out'] .= (string) stream_get_contents($r['pipes'][1]);
        $r['err'] .= (string) stream_get_contents($r['pipes'][2]);
        fclose($r['pipes'][1]);
        fclose($r['pipes'][2]);
        proc_close($r['proc']);

        $job = $r['job'];
        $exit = $status['exitcode'];
        if ($exit === 0) {
            $passed++;
            $passedKeys[$job['key']] = true;
        } else {
            $raw = trim((string) strtok($r['err'] . "\n" . $r['out'], "\n"));
            $raw = (string) preg_replace('/\e\[[0-9;]*m/', '', $raw);
            $note = $exit === 124 ? 'TIMEOUT' : $raw;
            $failed[$job['key']] = ['location' => $job['location'], 'note' => $note, 'job' => $job];
        }
        unset($running[$rk]);
    }
    unset($r);

    if ($running !== []) {
        usleep(20_000);
    }
}

// Retry pass: a failed snippet is re-run once, sequentially and in isolation,
// before being recorded as a real failure. With an empty baseline the suite
// has zero tolerance, so a transient hiccup (load spike, slow timeout) must not
// turn the build red on its own. A genuinely broken snippet fails both times.
if ($failed !== []) {
    foreach ($failed as $key => $info) {
        $job = $info['job'];
        $retryTmp = $tmpDir . '/retry-' . basename($job['file'], '.phel');
        @mkdir($retryTmp);
        $env = getenv();
        $env['TMPDIR'] = $retryTmp;
        $descriptor = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $proc = proc_open($cmd($job['file']), $descriptor, $pipes, $retryTmp, $env);
        if (!is_resource($proc)) {
            continue;
        }
        $out = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($proc);
        if ($exit === 0) {
            unset($failed[$key]);
            $passed++;
            $passedKeys[$key] = true;
        } else {
            $raw = trim((string) strtok($err . "\n" . $out, "\n"));
            $failed[$key]['note'] = $exit === 124 ? 'TIMEOUT' : (string) preg_replace('/\e\[[0-9;]*m/', '', $raw);
        }
    }
}

// Recursively remove the temp dir (snippets may have written files into it).
$rmTree = static function (string $dir) use (&$rmTree): void {
    foreach (glob($dir . '/*') ?: [] as $path) {
        is_dir($path) ? $rmTree($path) : @unlink($path);
    }
    @rmdir($dir);
};
$rmTree($tmpDir);

// --- Baseline handling ---
if ($updateBaseline) {
    $entries = [];
    foreach ($failed as $key => $info) {
        $entries[$key] = $info['location'] . '  ::  ' . $info['note'];
    }
    ksort($entries);
    file_put_contents(
        $baselinePath,
        json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n",
    );
    echo "\nWrote baseline with " . count($entries) . " known-failing snippets to build/doc-snippets-baseline.json\n";
    echo "Snippets: {$total}   Passed: {$passed}   Failed: " . count($failed) . "   Skipped: {$skipped}\n";
    exit(0);
}

$baseline = [];
if (is_file($baselinePath)) {
    $baseline = (array) json_decode((string) file_get_contents($baselinePath), true);
}

$regressions = array_diff_key($failed, $baseline);   // failing now, not in baseline
$fixed = array_intersect_key($passedKeys, $baseline); // in baseline but now passing

echo "\n";
echo "Snippets: {$total}   Passed: {$passed}   Failed: " . count($failed)
    . "   Skipped: {$skipped}   Baseline: " . count($baseline) . "\n";

$exit = 0;

if ($regressions !== []) {
    $exit = 1;
    echo "\nNEW FAILURES (not in baseline) - fix the snippet or run --update-baseline:\n";
    foreach ($regressions as $info) {
        echo "  {$info['location']}  {$info['note']}\n";
    }
}

if ($fixed !== []) {
    $exit = 1;
    echo "\nSNIPPETS NOW PASSING - remove from baseline (ratchet) via --update-baseline:\n";
    foreach (array_keys($fixed) as $key) {
        echo "  {$baseline[$key]}\n";
    }
}

if ($verbose && $failed !== []) {
    echo "\nAll current failures:\n";
    foreach ($failed as $info) {
        echo "  {$info['location']}  {$info['note']}\n";
    }
}

if ($exit === 0) {
    echo "\nNo regressions. " . count($baseline) . " known-unrunnable snippets tracked in baseline.\n";
}

exit($exit);
