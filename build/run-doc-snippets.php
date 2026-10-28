<?php

declare(strict_types=1);

/**
 * Executes every ```phel snippet in content/ against the real Phel runtime,
 * so documentation cannot silently rot when the language changes. Documented
 * results (`; => value`) are checked too (see ExpectedValueRewriter); a
 * mismatch fails like a broken snippet.
 *
 * Each snippet runs in an isolated `phel run` subprocess under a wall-clock cap
 * enforced here in PHP, so a hanging or memory-heavy snippet cannot take down
 * the suite. The cap is not delegated to GNU `timeout`, which a stock macOS
 * does not ship.
 *
 * Not every snippet is a self-contained program: REPL transcripts, syntax
 * templates and narrative continuations exist by design. Those are either
 * auto-skipped (see SnippetExtractor) or recorded in a checked-in baseline
 * (build/doc-snippets-baseline.json), keyed by snippet content so the entry
 * survives line moves. A mismatch entry adds the expectation's text to that
 * key. The suite fails on:
 *   - a NEW failure not present in the baseline (a regression), or
 *   - a baseline entry that now passes (ratchet: remove it).
 *
 * Usage:
 *   php build/run-doc-snippets.php                 # check against baseline
 *   php build/run-doc-snippets.php --update-baseline
 *   php build/run-doc-snippets.php --verbose [path ...]
 */

require __DIR__ . '/../vendor/autoload.php';

use PhelWeb\DocSnippet\ExpectedValueRewriter;
use PhelWeb\DocSnippet\SnippetExtractor;

$root = dirname(__DIR__);
$baselinePath = $root . '/build/doc-snippets-baseline.json';

// $_SERVER['argv'] rather than $argv: the latter only exists when
// register_argc_argv is on, which PHPStan is right not to assume.
$argvRest = array_slice((array) ($_SERVER['argv'] ?? []), 1);
$verbose = in_array('--verbose', $argvRest, true);
$updateBaseline = in_array('--update-baseline', $argvRest, true);
$paths = array_values(array_filter($argvRest, static fn(string $a): bool => !str_starts_with($a, '--')));
if ($paths === []) {
    $paths = [$root . '/content'];
}

$PHEL = $root . '/vendor/bin/phel';
$TIMEOUT_SECS = 15;
$CONCURRENCY = 8;

$extractor = new SnippetExtractor();
$rewriter = new ExpectedValueRewriter();

/** Stable per-snippet key, independent of its line number. */
$keyOf = static function (string $relFile, string $code): string {
    $normalized = preg_replace('/\s+/', ' ', trim($code));
    return $relFile . '#' . substr(sha1((string) $normalized), 0, 12);
};

$expectationKeyOf = static function (string $snippetKey, string $text): string {
    $normalized = preg_replace('/\s+/', ' ', trim($text));
    return $snippetKey . '=>' . substr(sha1((string) $normalized), 0, 8);
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
$files = array_values(array_filter($files, $extractor->coversFile(...)));
sort($files);

// Extract snippets, write runnable temp files.
$tmpDir = sys_get_temp_dir() . '/phel-doc-snippets-' . getmypid();
@mkdir($tmpDir);

$jobs = [];
$skipped = 0;
$skippedExpectations = [];
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
        // `user`, as in the REPL, so documented values such as `(user.point 1 2)` match.
        $header = $hasNs ? '' : "(ns user)\n";
        $rewrite = $rewriter->rewrite($code, $rel, $snippet['startLine']);
        array_push($skippedExpectations, ...$rewrite['skipped']);
        $key = $keyOf($rel, $code);
        $tmpFile = "{$tmpDir}/s{$index}.phel";
        file_put_contents($tmpFile, $header . $rewrite['code']);
        $originalFile = null;
        if ($rewrite['expectations'] !== []) {
            $originalFile = "{$tmpDir}/s{$index}-original.phel";
            file_put_contents($originalFile, $header . $code);
        }
        $expectations = [];
        foreach ($rewrite['expectations'] as $expectation) {
            $expectations[$expectation['location']] = $expectation + ['key' => $expectationKeyOf($key, $expectation['text'])];
        }
        $jobs[] = [
            'key' => $key,
            'location' => "{$rel}:{$snippet['startLine']}",
            'file' => $tmpFile,
            'originalFile' => $originalFile,
            'expectations' => $expectations,
        ];
    }
}

$total = count($jobs);
$startedAt = microtime(true);
$isTty = stream_isatty(STDERR);
fwrite(STDERR, "Running {$total} snippets ({$skipped} skipped) ...\n");

/**
 * Live counter, so a multi-minute run is not silent. On a terminal it redraws
 * one line in place; piped to a file or a CI log it appends a plain line every
 * 15s instead, so the log stays short but never goes quiet for long.
 */
$progress = static function (int $done, int $passed, int $failed, bool $force = false) use ($total, $startedAt, $isTty): void {
    static $lastRenderAt = 0.0;

    $now = microtime(true);
    $interval = $isTty ? 0.1 : 15.0;
    if (!$force && $now - $lastRenderAt < $interval) {
        return;
    }
    $lastRenderAt = $now;

    $line = sprintf(
        '  %d/%d  passed %d  failed %d  %ds',
        $done,
        $total,
        $passed,
        $failed,
        (int) round($now - $startedAt),
    );
    fwrite(STDERR, $isTty ? "\r\e[K{$line}" : "{$line}\n");
};

$cmd = static function (string $file) use ($PHEL): string {
    return escapeshellarg($PHEL) . ' run ' . escapeshellarg($file);
};

$startProc = static function (array $job, string $prefix = '') use ($cmd, $tmpDir, $TIMEOUT_SECS): array {
    $descriptor = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    // Each snippet gets its own TMPDIR. Phel resolves its compile-cache dir
    // from sys_get_temp_dir() (which honours TMPDIR), and writes a temp
    // compiled PHP file keyed by content hash. Two concurrent snippets that
    // compile to the same hash would otherwise race on that shared file (one
    // unlinks it while the other requires it). An isolated TMPDIR per process
    // removes the collision. cwd is also the isolated dir so filesystem side
    // effects cannot litter the repo.
    $procTmp = $tmpDir . '/' . $prefix . basename($job['file'], '.phel');
    @mkdir($procTmp);
    $env = getenv();
    $env['TMPDIR'] = $procTmp;
    $proc = proc_open($cmd($job['file']), $descriptor, $pipes, $procTmp, $env);
    if (!is_resource($proc)) {
        throw new RuntimeException("Failed to start snippet process for {$job['file']}");
    }
    // Both pipes are non-blocking and drained on every poll. A blocking read of
    // one pipe deadlocks as soon as the snippet fills the other (~64KB), which a
    // single Phel error report can do on its own.
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    return [
        'job' => $job,
        'proc' => $proc,
        'pipes' => $pipes,
        'out' => '',
        'err' => '',
        'deadline' => microtime(true) + $TIMEOUT_SECS,
        'timedOut' => false,
    ];
};

/**
 * Poll one running process until it exits or outruns its deadline, draining
 * both pipes as it goes. The wall-clock cap is enforced here rather than by
 * wrapping the command in GNU `timeout`, which is absent on a stock macOS.
 *
 * @return array{0: int, 1: string, 2: string, 3: bool} exit code, stdout, stderr, timed out
 */
$reap = static function (array $r): array {
    while (true) {
        $r['out'] .= (string) stream_get_contents($r['pipes'][1]);
        $r['err'] .= (string) stream_get_contents($r['pipes'][2]);

        $status = proc_get_status($r['proc']);
        if (!$status['running']) {
            break;
        }
        if (!$r['timedOut'] && microtime(true) > $r['deadline']) {
            $r['timedOut'] = true;
            proc_terminate($r['proc'], 9);
        }
        usleep(20_000);
    }

    $r['out'] .= (string) stream_get_contents($r['pipes'][1]);
    $r['err'] .= (string) stream_get_contents($r['pipes'][2]);
    fclose($r['pipes'][1]);
    fclose($r['pipes'][2]);
    proc_close($r['proc']);

    return [(int) $status['exitcode'], $r['out'], $r['err'], $r['timedOut']];
};

/** First line of output, stripped of ANSI colour, as the failure note. */
$noteOf = static function (int $exit, string $out, string $err, bool $timedOut) use ($TIMEOUT_SECS): string {
    if ($timedOut) {
        return "TIMEOUT (>{$TIMEOUT_SECS}s)";
    }
    $raw = trim((string) strtok($err . "\n" . $out, "\n"));

    return (string) preg_replace('/\e\[[0-9;]*m/', '', $raw);
};

// Run with bounded concurrency.
$running = [];
$failed = [];   // key => ['location' => ..., 'note' => ...]
$passedKeys = [];
$passed = 0;
$queue = $jobs;

// Expectations of a snippet whose instrumented run passed: a PHEL-DOC-MISMATCH
// line in stdout fails that expectation, its absence passes it.
$mismatches = [];   // key => ['location' => ..., 'note' => ...]
$checked = 0;
$unverifiable = [];
$recordExpectations = static function (array $job, string $out) use (&$mismatches, &$checked, &$passedKeys): void {
    $marker = preg_quote(ExpectedValueRewriter::MISMATCH_MARKER, '/');
    preg_match_all('/^' . $marker . ' (\S+) (expected .*)$/m', $out, $found, PREG_SET_ORDER);
    $notes = [];
    foreach ($found as $m) {
        $notes[$m[1]] = $m[2];
    }
    foreach ($job['expectations'] as $location => $expectation) {
        ++$checked;
        if (isset($notes[$location])) {
            $mismatches[$expectation['key']] = ['location' => $location, 'note' => $notes[$location]];
        } else {
            $passedKeys[$expectation['key']] = true;
        }
    }
};

while ($queue !== [] || $running !== []) {
    while (count($running) < $CONCURRENCY && $queue !== []) {
        $running[] = $startProc(array_shift($queue));
    }

    foreach ($running as $rk => &$r) {
        $r['out'] .= (string) stream_get_contents($r['pipes'][1]);
        $r['err'] .= (string) stream_get_contents($r['pipes'][2]);
        $status = proc_get_status($r['proc']);
        if ($status['running']) {
            if (!$r['timedOut'] && microtime(true) > $r['deadline']) {
                $r['timedOut'] = true;
                proc_terminate($r['proc'], 9);
            }
            continue;
        }
        $r['out'] .= (string) stream_get_contents($r['pipes'][1]);
        $r['err'] .= (string) stream_get_contents($r['pipes'][2]);
        fclose($r['pipes'][1]);
        fclose($r['pipes'][2]);
        proc_close($r['proc']);

        $job = $r['job'];
        $exit = (int) $status['exitcode'];
        if ($exit === 0 && !$r['timedOut']) {
            $passed++;
            $passedKeys[$job['key']] = true;
            $recordExpectations($job, $r['out']);
        } else {
            $failed[$job['key']] = [
                'location' => $job['location'],
                'note' => $noteOf($exit, $r['out'], $r['err'], $r['timedOut']),
                'job' => $job,
            ];
        }
        unset($running[$rk]);
        $progress($passed + count($failed), $passed, count($failed));
    }
    unset($r);

    if ($running !== []) {
        usleep(20_000);
    }
}

$progress($passed + count($failed), $passed, count($failed), true);
if ($isTty) {
    fwrite(STDERR, "\n");
}

// Retry pass: a failed snippet is re-run once, sequentially and in isolation,
// before being recorded as a real failure. With an empty baseline the suite
// has zero tolerance, so a transient hiccup (load spike, slow timeout) must not
// turn the build red on its own. A genuinely broken snippet fails both times.
if ($failed !== []) {
    $retryTotal = count($failed);
    $retryDone = 0;
    fwrite(STDERR, "Retrying {$retryTotal} failed snippet(s) sequentially ...\n");
    foreach ($failed as $key => $info) {
        $job = $info['job'];
        ++$retryDone;
        fwrite(STDERR, $isTty ? "\r\e[K  retry {$retryDone}/{$retryTotal}  {$job['location']}" : "  retry {$retryDone}/{$retryTotal}  {$job['location']}\n");
        [$exit, $out, $err, $timedOut] = $reap($startProc($job, 'retry-'));
        if ($exit === 0 && !$timedOut) {
            unset($failed[$key]);
            $passed++;
            $passedKeys[$key] = true;
            $recordExpectations($job, $out);
            continue;
        }
        $failed[$key]['note'] = $noteOf($exit, $out, $err, $timedOut);
        if ($job['originalFile'] === null) {
            continue;
        }
        // The checks must never break a snippet that runs on its own: if the
        // original passes, its expectations are reported as unverifiable instead.
        [$exit, $out, $err, $timedOut] = $reap($startProc(['file' => $job['originalFile']] + $job, 'original-'));
        if ($exit === 0 && !$timedOut) {
            unset($failed[$key]);
            $passed++;
            $passedKeys[$key] = true;
            foreach ($job['expectations'] as $expectation) {
                $unverifiable[] = $expectation;
            }
        } else {
            $failed[$key]['note'] = $noteOf($exit, $out, $err, $timedOut);
        }
    }
    if ($isTty) {
        fwrite(STDERR, "\r\e[K");
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
$failures = $failed + $mismatches;
$expectationSummary = "Expectations: checked {$checked}, mismatched " . count($mismatches) . ', unverifiable ' . count($unverifiable);

if ($updateBaseline) {
    $entries = [];
    foreach ($failures as $key => $info) {
        $entries[$key] = $info['location'] . '  ::  ' . $info['note'];
    }
    ksort($entries);
    file_put_contents(
        $baselinePath,
        json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n",
    );
    echo "\nWrote baseline with " . count($entries) . " known failures to build/doc-snippets-baseline.json\n";
    echo "Snippets: {$total}   Passed: {$passed}   Failed: " . count($failed) . "   Skipped: {$skipped}\n";
    echo "{$expectationSummary}\n";
    exit(0);
}

$baseline = [];
if (is_file($baselinePath)) {
    $baseline = (array) json_decode((string) file_get_contents($baselinePath), true);
}

$regressions = array_diff_key($failures, $baseline);   // failing now, not in baseline
$fixed = array_intersect_key($passedKeys, $baseline); // in baseline but now passing

echo "\n";
echo "Snippets: {$total}   Passed: {$passed}   Failed: " . count($failed)
    . "   Skipped: {$skipped}   Baseline: " . count($baseline) . "\n";
echo "{$expectationSummary}\n";

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

if ($verbose && $failures !== []) {
    echo "\nAll current failures:\n";
    foreach ($failures as $info) {
        echo "  {$info['location']}  {$info['note']}\n";
    }
}

if ($verbose && $unverifiable !== []) {
    echo "\nUnverifiable expectations (the instrumented snippet fails, the original passes):\n";
    foreach ($unverifiable as $expectation) {
        echo "  {$expectation['location']}  {$expectation['text']}\n";
    }
}

if ($verbose && $skippedExpectations !== []) {
    echo "\nSkipped expectations:\n";
    foreach ($skippedExpectations as $expectation) {
        echo "  {$expectation['location']}  [{$expectation['reason']}]  {$expectation['text']}\n";
    }
}

if ($exit === 0) {
    echo "\nNo regressions. " . count($baseline) . " known failures tracked in baseline.\n";
}

exit($exit);
