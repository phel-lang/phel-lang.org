<?php

declare(strict_types=1);

namespace PhelWeb\DocSnippet;

/**
 * Extracts ```phel fenced code blocks from markdown so they can be executed
 * against the real Phel runtime. Detects blocks that are not standalone
 * runnable (REPL transcripts, author-marked skips) and flags them.
 *
 * @psalm-type TSnippet = array{
 *     file: string,
 *     startLine: int,
 *     code: string,
 *     skip: bool,
 *     skipReason: string|null,
 * }
 */
final class SnippetExtractor
{
    /** Opt-out marker an author can place on the line above a fence. */
    private const SKIP_MARKER = '/<!--\s*phel-test:\s*skip\s*-->/i';

    /**
     * @return list<TSnippet>
     */
    public function extractFromFile(string $path): array
    {
        $relative = $path;
        $lines = explode("\n", (string) file_get_contents($path));

        return $this->extract($lines, $relative);
    }

    /**
     * @param list<string> $lines
     *
     * @return list<TSnippet>
     */
    public function extract(array $lines, string $file): array
    {
        $snippets = [];
        $inBlock = false;
        $buffer = [];
        $startLine = 0;
        $markedSkip = false;
        $prevNonEmpty = '';

        foreach ($lines as $index => $line) {
            $trimmed = ltrim($line);

            if (!$inBlock && preg_match('/^```phel\b(.*)$/', $trimmed, $m)) {
                $inBlock = true;
                $buffer = [];
                $startLine = $index + 2; // 1-indexed first content line
                // Skip via fence info string (```phel skip) or a marker comment above.
                $markedSkip = str_contains(strtolower(trim($m[1])), 'skip')
                    || preg_match(self::SKIP_MARKER, $prevNonEmpty) === 1;
                continue;
            }

            if ($inBlock && preg_match('/^```\s*$/', $trimmed)) {
                $code = implode("\n", $buffer);
                $reason = $this->skipReason($code, $markedSkip);
                $snippets[] = [
                    'file' => $file,
                    'startLine' => $startLine,
                    'code' => $code,
                    'skip' => $reason !== null,
                    'skipReason' => $reason,
                ];
                $inBlock = false;
                continue;
            }

            if ($inBlock) {
                $buffer[] = $line;
                continue;
            }

            if ($trimmed !== '') {
                $prevNonEmpty = $trimmed;
            }
        }

        return $snippets;
    }

    private function skipReason(string $code, bool $markedSkip): ?string
    {
        if ($markedSkip) {
            return 'marked';
        }

        if (trim($code) === '') {
            return 'empty';
        }

        // REPL session transcripts: prompts like "user:1>", "phel:1>", "phel:>" or ">>>".
        if (preg_match('/^\s*[\w.\\\\-]+:\d*>/m', $code) || preg_match('/^\s*>>>/m', $code)) {
            return 'repl-transcript';
        }

        return null;
    }
}
