<?php

declare(strict_types=1);

namespace PhelWeb\DocSnippet;

/**
 * Turns the documented results of a snippet (`FORM ; => EXPECTED`, or a
 * `; => EXPECTED` line right below FORM) into checks that print a
 * PHEL-DOC-MISMATCH line when the runtime value differs. The rewrite keeps the
 * line count, so error locations still point at the markdown source.
 *
 * @psalm-type TExpectation = array{line: int, location: string, text: string, form: string, expected: string}
 * @psalm-type TSkippedExpectation = array{line: int, location: string, text: string, reason: string}
 * @psalm-type TRewrite = array{
 *     code: string,
 *     expectations: list<TExpectation>,
 *     skipped: list<TSkippedExpectation>,
 * }
 */
final class ExpectedValueRewriter
{
    public const MISMATCH_MARKER = 'PHEL-DOC-MISMATCH';

    private const DEFINITION_PREFIXES = [
        '(def',
        '(ns',
        '(require',
        '(use',
        '(declare',
        '(comment',
    ];

    private const UNREADABLE_PREFIXES = ['#\'', '#<', '<'];

    /** Words that describe a value instead of printing it; keywords such as `:error` stay checkable. */
    private const NON_VALUE_WORDS = '/(?<![:\w-])(instance|throws|error|varies|random|depends)(?![\w-])/i';

    /** A symbol from `name#` or `gensym`, whose number changes from run to run. */
    private const GENERATED_SYMBOL = '/\b[\w-]*__\w*?\d+\b/';

    private const OPENERS = ['(' => ')', '[' => ']', '{' => '}'];

    /**
     * @return TRewrite
     */
    public function rewrite(string $code, string $file, int $startLine): array
    {
        $lines = explode("\n", $code);
        $codeEnds = [];
        $expectations = [];
        $skipped = [];
        $depth = 0;
        $inString = false;
        $formStart = null;
        $lastForm = null;

        foreach ($lines as $offset => $line) {
            $topLevelAtStart = $depth === 0 && !$inString;
            $commentStart = $this->scanLine($line, $depth, $inString);
            $codeEnds[$offset] = $commentStart ?? strlen($line);
            $hasCode = trim(substr($line, 0, $codeEnds[$offset])) !== '';

            if ($topLevelAtStart && $hasCode) {
                $formStart = $offset;
            }
            $closedForm = null;
            if ($formStart !== null && $depth === 0 && !$inString && $hasCode) {
                $closedForm = ['start' => $formStart, 'end' => $offset];
                $formStart = null;
            }

            $expectedText = $this->expectedText($line, $commentStart);
            if ($expectedText === null) {
                if ($closedForm !== null) {
                    $lastForm = $closedForm;
                } elseif ($hasCode) {
                    $lastForm = null;
                }
                continue;
            }

            $lineNumber = $startLine + $offset;
            $location = "{$file}:{$lineNumber}";

            $span = null;
            $reason = null;
            if ($hasCode) {
                $span = $closedForm;
                $reason = $span === null ? 'inside an enclosing form' : null;
            } elseif (!$topLevelAtStart) {
                $reason = 'inside an enclosing form';
            } elseif ($lastForm !== null && $lastForm['end'] === $offset - 1) {
                $span = $lastForm;
            } else {
                $reason = 'result comment not right below a form';
            }
            $lastForm = null;

            $form = $span === null ? '' : $this->formText($lines, $codeEnds, $span['start'], $span['end']);
            $shownForm = $span === null ? substr($line, 0, $codeEnds[$offset]) : $form;
            $text = trim((string) preg_replace('/\s+/', ' ', $shownForm . ' ' . substr($line, (int) $commentStart)));
            $reason ??= $this->skipReason($form, $expectedText);
            if ($reason !== null || $span === null) {
                $skipped[] = ['line' => $lineNumber, 'location' => $location, 'text' => $text, 'reason' => (string) $reason];
                continue;
            }

            $expected = $this->firstExpectedForm($expectedText);
            $expectations[] = [
                'line' => $lineNumber,
                'location' => $location,
                'text' => $text,
                'form' => $form,
                'expected' => $expected,
            ];
            $this->instrument($lines, $codeEnds, $span['start'], $offset, $hasCode, $expected, $location);
        }

        return [
            'code' => implode("\n", $lines),
            'expectations' => $expectations,
            'skipped' => $skipped,
        ];
    }

    /**
     * The first form of the documented value: a delimited form when it opens
     * with a bracket or a quote, otherwise the text up to the first whitespace.
     */
    public function firstExpectedForm(string $expectedText): string
    {
        $text = ltrim($expectedText);
        $end = $this->delimitedValueEnd($text);
        if ($end !== null) {
            return substr($text, 0, $end);
        }

        return (preg_split('/\s/', $text, 2) ?: [''])[0];
    }

    private function expectedText(string $line, ?int $commentStart): ?string
    {
        if ($commentStart === null) {
            return null;
        }
        if (preg_match('/^;(?!;)\s*=>(.*)$/', substr($line, $commentStart), $m) !== 1) {
            return null;
        }

        return trim($m[1]);
    }

    private function skipReason(string $form, string $expectedText): ?string
    {
        if (!$this->isSingleForm($form)) {
            return 'not one form';
        }
        foreach (self::DEFINITION_PREFIXES as $prefix) {
            if (str_starts_with($form, $prefix)) {
                return 'definition form';
            }
        }
        if ($expectedText === '') {
            return 'no expected value';
        }
        if (str_contains($expectedText, '...')) {
            return 'elided value';
        }
        foreach (self::UNREADABLE_PREFIXES as $prefix) {
            if (str_starts_with($expectedText, $prefix)) {
                return 'unreadable value';
            }
        }
        if ($this->opensDelimitedValue($expectedText) && $this->delimitedValueEnd($expectedText) === null) {
            return 'value does not close on this line';
        }
        if (preg_match(self::GENERATED_SYMBOL, $expectedText) === 1) {
            return 'generated symbol';
        }
        $outsideStrings = (string) preg_replace('/"(?:[^"\\\\]|\\\\.)*"/s', '""', $expectedText);
        if (preg_match(self::NON_VALUE_WORDS, $outsideStrings, $m) === 1) {
            return "describes the value ('" . strtolower($m[1]) . "')";
        }

        return null;
    }

    /**
     * @param array<int, string> $lines
     * @param array<int, int> $codeEnds
     */
    private function formText(array $lines, array $codeEnds, int $start, int $end): string
    {
        $parts = [];
        for ($i = $start; $i <= $end; $i++) {
            $parts[] = substr($lines[$i], 0, $codeEnds[$i]);
        }

        return trim(implode("\n", $parts));
    }

    /**
     * @param array<int, string> $lines
     * @param array<int, int> $codeEnds
     */
    private function instrument(array &$lines, array $codeEnds, int $start, int $commentLine, bool $sameLine, string $expected, string $location): void
    {
        $head = '(let [__expected (quote ' . $expected . ') __actual ';
        $tail = '] (when-not (or (= __expected __actual) (= (pr-str __expected) (pr-str __actual))) '
            . '(println (str "\n' . self::MISMATCH_MARKER . ' ' . addcslashes($location, '"\\')
            . ' expected " (pr-str __expected) " got " (pr-str __actual)))))';

        if ($sameLine && $start === $commentLine) {
            $lines[$start] = $head . trim(substr($lines[$start], 0, $codeEnds[$start])) . $tail;
            return;
        }

        $lines[$start] = $head . $lines[$start];
        $lines[$commentLine] = $sameLine ? rtrim(substr($lines[$commentLine], 0, $codeEnds[$commentLine])) . $tail : $tail;
    }

    /**
     * Advances the bracket depth and string state across one line and returns
     * the offset of the comment that ends it, if any.
     */
    private function scanLine(string $line, int &$depth, bool &$inString): ?int
    {
        $length = strlen($line);
        for ($i = 0; $i < $length; $i++) {
            $char = $line[$i];
            if ($inString) {
                if ($char === '\\') {
                    $i++;
                } elseif ($char === '"') {
                    $inString = false;
                }
                continue;
            }
            if ($char === '"') {
                $inString = true;
            } elseif ($char === ';') {
                return $i;
            } elseif (isset(self::OPENERS[$char])) {
                $depth++;
            } elseif (in_array($char, self::OPENERS, true)) {
                $depth--;
            }
        }

        return null;
    }

    private function isSingleForm(string $form): bool
    {
        $end = $this->formEnd($form, 0);

        return $end !== null && trim(substr($form, $end)) === '';
    }

    /** Reads through prefixes such as quote, deref, `|` and `#?` to the end of one form. */
    private function formEnd(string $text, int $pos): ?int
    {
        $length = strlen($text);
        while ($pos < $length && ctype_space($text[$pos])) {
            $pos++;
        }
        if ($pos >= $length) {
            return null;
        }

        $char = $text[$pos];
        if (str_contains("'`,@|^", $char)) {
            return $this->formEnd($text, $pos + 1);
        }
        if ($char === '#' && ($text[$pos + 1] ?? '') === '?') {
            return $this->formEnd($text, $pos + 2);
        }
        if ($char === '#' && ($text[$pos + 1] ?? '') === '{') {
            return $this->delimitedEnd($text, $pos + 1);
        }
        if ($char === '"' || isset(self::OPENERS[$char])) {
            return $this->delimitedEnd($text, $pos);
        }
        if (in_array($char, self::OPENERS, true)) {
            return null;
        }

        while ($pos < $length && !ctype_space($text[$pos]) && !str_contains('()[]{}";', $text[$pos])) {
            $pos++;
        }

        return $pos;
    }

    private function opensDelimitedValue(string $text): bool
    {
        $first = $text[str_starts_with($text, '#{') ? 1 : 0] ?? '';

        return $first === '"' || isset(self::OPENERS[$first]);
    }

    private function delimitedValueEnd(string $text): ?int
    {
        if (!$this->opensDelimitedValue($text)) {
            return null;
        }

        return $this->delimitedEnd($text, str_starts_with($text, '#{') ? 1 : 0);
    }

    /** End offset of the string or bracketed form that opens at $pos; null when it never closes or closes with the wrong bracket. */
    private function delimitedEnd(string $text, int $pos): ?int
    {
        $length = strlen($text);
        $stack = [];
        $inString = false;
        for ($i = $pos; $i < $length; $i++) {
            $char = $text[$i];
            if ($inString) {
                if ($char === '\\') {
                    $i++;
                } elseif ($char === '"') {
                    $inString = false;
                    if ($stack === []) {
                        return $i + 1;
                    }
                }
                continue;
            }
            if ($char === '"') {
                $inString = true;
            } elseif (isset(self::OPENERS[$char])) {
                $stack[] = self::OPENERS[$char];
            } elseif (in_array($char, self::OPENERS, true)) {
                if (array_pop($stack) !== $char) {
                    return null;
                }
                if ($stack === []) {
                    return $i + 1;
                }
            }
        }

        return null;
    }
}
