<?php

declare(strict_types=1);

/**
 * Generates the homepage REPL showcase data from REAL runtime output.
 *
 * The animated REPL on the homepage used to hard-code its results in
 * JavaScript, so they could (and did) drift from what Phel actually prints
 * (e.g. it showed `(2 3 4)` where Phel prints `@[2 3 4]`). This script runs
 * the curated forms through a real `phel repl` session and writes the
 * verified prompt/result pairs to static/animated-repl-data.json, which the
 * animation consumes. Re-run via `composer build` so it can never rot.
 */

require __DIR__ . '/../vendor/autoload.php';

$root = dirname(__DIR__);
$phel = $root . '/vendor/bin/phel';
$outFile = $root . '/static/animated-repl-data.json';

// Curated showcase forms. Each must produce exactly one line of REPL output.
// Evaluated in a single session, so later forms can use earlier definitions.
$forms = [
    '(map inc [1 2 3])',
    '(->> (range 1 6) (filter odd?) (reduce +))',
    '(defn greet [name] (str "hello, " name))',
    '(greet "phel")',
    '(->> (range 1 11) (map (fn [x] (* x x))) (reduce +))',
];

// Feed the forms into a real REPL and capture stdout.
$input = implode("\n", $forms) . "\n";
$descriptor = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
$proc = proc_open(escapeshellarg($phel) . ' repl', $descriptor, $pipes, $root);
if (!is_resource($proc)) {
    fwrite(STDERR, "Failed to start phel repl\n");
    exit(1);
}
fwrite($pipes[0], $input);
fclose($pipes[0]);
$stdout = stream_get_contents($pipes[1]);
fclose($pipes[1]);
fclose($pipes[2]);
proc_close($proc);

// Strip ANSI colour codes and isolate the result lines.
$stripped = (string) preg_replace('/\e\[[0-9;]*m/', '', (string) $stdout);
$results = [];
foreach (explode("\n", $stripped) as $line) {
    $line = rtrim($line);
    if ($line === '') {
        continue;
    }
    if (preg_match('/^(Welcome to the Phel Repl|Type "exit"|Bye!)/', $line)) {
        continue;
    }
    $results[] = $line;
}

if (count($results) !== count($forms)) {
    fwrite(STDERR, sprintf(
        "Expected %d result lines, got %d. Showcase forms must each print exactly one line.\nOutput:\n%s\n",
        count($forms),
        count($results),
        $stripped,
    ));
    exit(1);
}

$pairs = [];
foreach ($forms as $i => $form) {
    $pairs[] = ['prompt' => $form, 'result' => $results[$i]];
}

file_put_contents(
    $outFile,
    json_encode($pairs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n",
);

echo 'Wrote ' . count($pairs) . " verified REPL pairs to static/animated-repl-data.json\n";
