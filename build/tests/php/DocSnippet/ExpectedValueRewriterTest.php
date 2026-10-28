<?php

declare(strict_types=1);

namespace PhelWebTests\DocSnippet;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PhelWeb\DocSnippet\ExpectedValueRewriter;

final class ExpectedValueRewriterTest extends TestCase
{
    private ExpectedValueRewriter $rewriter;

    protected function setUp(): void
    {
        $this->rewriter = new ExpectedValueRewriter();
    }

    public function test_rewrites_a_single_line_expectation(): void
    {
        $result = $this->rewriter->rewrite('(+ 1 2) ; => 3', 'doc.md', 10);

        self::assertSame(
            '(let [__expected (quote 3) __actual (+ 1 2)] (when-not (or (= __expected __actual) (= (pr-str __expected) (pr-str __actual))) '
            . '(println (str "\nPHEL-DOC-MISMATCH doc.md:10 expected " (pr-str __expected) " got " (pr-str __actual)))))',
            $result['code'],
        );
        self::assertCount(1, $result['expectations']);
        self::assertSame('doc.md:10', $result['expectations'][0]['location']);
        self::assertSame('(+ 1 2)', $result['expectations'][0]['form']);
        self::assertSame('3', $result['expectations'][0]['expected']);
        self::assertSame([], $result['skipped']);
    }

    public function test_keeps_the_line_count(): void
    {
        $code = "(def a 1)\n(+ a 1) ; => 2\n(let [b 2]\n  (* a b)) ; => 2\n(inc a)\n; => 2";

        $result = $this->rewriter->rewrite($code, 'doc.md', 1);

        self::assertCount(3, $result['expectations']);
        self::assertSame(6, substr_count($result['code'], "\n") + 1);
    }

    public function test_runner_detects_mismatches_after_printed_output(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'phel-doc-expectations-');
        self::assertIsString($file);
        $root = dirname(__DIR__, 4);

        try {
            file_put_contents($file, <<<'MARKDOWN'
```phel
(do (print "cleanup") "recovered") ; => "wrong"
```

```phel
"recovered" ; => "wrong"
```

```phel
(do (print "cleanup") "recovered") ; => "recovered"
```
MARKDOWN);
            $process = proc_open(
                [PHP_BINARY, $root . '/build/run-doc-snippets.php', $file],
                [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['redirect', 1]],
                $pipes,
                $root,
            );
            self::assertIsResource($process);
            fclose($pipes[0]);
            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            $exit = proc_close($process);

            self::assertSame(1, $exit, $output);
            self::assertStringContainsString('Expectations: checked 3, mismatched 2, unverifiable 0', $output);
            self::assertStringContainsString($file . ':2  expected "wrong" got "recovered"', $output);
            self::assertStringContainsString($file . ':6  expected "wrong" got "recovered"', $output);
        } finally {
            unlink($file);
        }
    }

    public function test_multi_line_form_is_wrapped_from_its_first_line(): void
    {
        $result = $this->rewriter->rewrite("(let [b 2]\n  (* 3 b)) ; => 6", 'doc.md', 5);
        $lines = explode("\n", $result['code']);

        self::assertSame('(let [__expected (quote 6) __actual (let [b 2]', $lines[0]);
        self::assertStringStartsWith('  (* 3 b))] (when-not', $lines[1]);
        self::assertStringContainsString('doc.md:6 expected', $lines[1]);
        self::assertSame("(let [b 2]\n  (* 3 b))", $result['expectations'][0]['form']);
    }

    public function test_result_comment_right_below_a_form_checks_that_form(): void
    {
        $result = $this->rewriter->rewrite("(str \"a\" \"b\")\n; => \"ab\"", 'doc.md', 1);
        $lines = explode("\n", $result['code']);

        self::assertSame('(let [__expected (quote "ab") __actual (str "a" "b")', $lines[0]);
        self::assertStringStartsWith('] (when-not', $lines[1]);
        self::assertSame('(str "a" "b") ; => "ab"', $result['expectations'][0]['text']);
    }

    public function test_arrow_inside_a_string_is_not_an_expectation(): void
    {
        $code = '(println "a ; => b")';

        $result = $this->rewriter->rewrite($code, 'doc.md', 1);

        self::assertSame($code, $result['code']);
        self::assertSame([], $result['expectations']);
        self::assertSame([], $result['skipped']);
    }

    public function test_arrow_after_a_string_holding_a_semicolon_is_an_expectation(): void
    {
        $result = $this->rewriter->rewrite('(str "a;b") ; => "a;b"', 'doc.md', 1);

        self::assertSame('"a;b"', $result['expectations'][0]['expected']);
    }

    public function test_double_semicolon_is_not_an_expectation(): void
    {
        $code = '(+ 1 2) ;; => 3';

        $result = $this->rewriter->rewrite($code, 'doc.md', 1);

        self::assertSame($code, $result['code']);
        self::assertSame([], $result['expectations']);
    }

    public function test_plain_comments_are_left_alone(): void
    {
        $code = "; a comment\n(+ 1 2) ; prints nothing";

        $result = $this->rewriter->rewrite($code, 'doc.md', 1);

        self::assertSame($code, $result['code']);
        self::assertSame([], $result['skipped']);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function skippedLines(): iterable
    {
        yield 'definition' => ['(def x (+ 1 2)) ; => 3', 'definition form'];
        yield 'function definition' => ['(defn f [] 1) ; => 1', 'definition form'];
        yield 'namespace' => ['(ns app) ; => nil', 'definition form'];
        yield 'two forms' => ['(inc 1) (inc 2) ; => 2 3', 'not one form'];
        yield 'nested' => ["(do\n  (inc 1) ; => 2\n  3)", 'inside an enclosing form'];
        yield 'own line after a blank' => ["(inc 1)\n\n; => 2", 'result comment not right below a form'];
        yield 'own line at the start' => ['; => 2', 'result comment not right below a form'];
        yield 'elided' => ['(range 100) ; => (0 1 2 ...)', 'elided value'];
        yield 'var' => ['(var x) ; => #\'user/x', 'unreadable value'];
        yield 'object' => ['(fn [] 1) ; => <function>', 'unreadable value'];
        yield 'description' => ['(DateTime.) ; => DateTime instance', "describes the value ('instance')"];
        yield 'random' => ['(rand) ; => 0.42 (random)', "describes the value ('random')"];
        yield 'throws' => ['(/ 1 0) ; => throws DivisionByZeroError', "describes the value ('throws')"];
        yield 'unclosed value' => ['(range 3) ; => (0 1', 'value does not close on this line'];
        yield 'generated symbol' => ['(gensym) ; => __phel_1', 'generated symbol'];
        yield 'empty' => ['(inc 1) ; =>', 'no expected value'];
    }

    #[DataProvider('skippedLines')]
    public function test_skips_lines_it_cannot_check(string $code, string $reason): void
    {
        $result = $this->rewriter->rewrite($code, 'doc.md', 1);

        self::assertSame([], $result['expectations']);
        self::assertSame($code, $result['code']);
        self::assertCount(1, $result['skipped']);
        self::assertSame($reason, $result['skipped'][0]['reason']);
    }

    public function test_keyword_or_string_holding_a_skip_word_is_still_checked(): void
    {
        $result = $this->rewriter->rewrite("(f) ; => {:error nil}\n(g) ; => \"error: boom\"", 'doc.md', 1);

        self::assertCount(2, $result['expectations']);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function expectedTexts(): iterable
    {
        yield 'number with a note' => ['1337 (hex)', '1337'];
        yield 'string' => ['"a b" (string)', '"a b"'];
        yield 'string with escaped quote' => ['"say \"hi\"" ok', '"say \"hi\""'];
        yield 'list' => ['(1 2 3) lazily', '(1 2 3)'];
        yield 'nested vector' => ['[[1 :a] [2 :b]], in order', '[[1 :a] [2 :b]]'];
        yield 'map with string holding a bracket' => ['{:a "]"} done', '{:a "]"}'];
        yield 'set' => ['#{1 2} unordered', '#{1 2}'];
        yield 'keyword' => [':timeout (the future keeps running)', ':timeout'];
        yield 'nil' => ['nil', 'nil'];
    }

    #[DataProvider('expectedTexts')]
    public function test_takes_the_first_form_of_the_expected_text(string $text, string $first): void
    {
        self::assertSame($first, $this->rewriter->firstExpectedForm($text));
    }

    public function test_reader_shortcuts_count_as_one_form(): void
    {
        $result = $this->rewriter->rewrite("@a ; => 1\n'(1 2) ; => (1 2)\n#?(:phel 1 :default 0) ; => 1", 'doc.md', 1);

        self::assertCount(3, $result['expectations']);
    }
}
