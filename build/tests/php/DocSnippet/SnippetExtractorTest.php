<?php

declare(strict_types=1);

namespace PhelWebTests\DocSnippet;

use PHPUnit\Framework\TestCase;
use PhelWeb\DocSnippet\SnippetExtractor;

final class SnippetExtractorTest extends TestCase
{
    private SnippetExtractor $extractor;

    protected function setUp(): void
    {
        $this->extractor = new SnippetExtractor();
    }

    public function test_extracts_phel_block_with_line_number(): void
    {
        $md = [
            '# Title',
            '',
            'Some text.',
            '',
            '```phel',
            '(+ 1 2)',
            '```',
        ];

        $snippets = $this->extractor->extract($md, 'doc.md');

        self::assertCount(1, $snippets);
        self::assertSame('doc.md', $snippets[0]['file']);
        self::assertSame(6, $snippets[0]['startLine']);
        self::assertSame('(+ 1 2)', $snippets[0]['code']);
        self::assertFalse($snippets[0]['skip']);
    }

    public function test_ignores_non_phel_fences(): void
    {
        $md = [
            '```php',
            'echo 1;',
            '```',
            '```bash',
            'ls',
            '```',
        ];

        self::assertCount(0, $this->extractor->extract($md, 'doc.md'));
    }

    public function test_extracts_multiple_blocks(): void
    {
        $md = [
            '```phel',
            '(def a 1)',
            '```',
            'between',
            '```phel',
            '(def b 2)',
            '(def c 3)',
            '```',
        ];

        $snippets = $this->extractor->extract($md, 'doc.md');

        self::assertCount(2, $snippets);
        self::assertSame("(def b 2)\n(def c 3)", $snippets[1]['code']);
    }

    public function test_repl_transcript_is_skipped(): void
    {
        $md = [
            '```phel',
            'user:1> (+ 1 2)',
            '3',
            '```',
        ];

        $snippets = $this->extractor->extract($md, 'doc.md');

        self::assertTrue($snippets[0]['skip']);
        self::assertSame('repl-transcript', $snippets[0]['skipReason']);
    }

    public function test_skip_via_fence_info_string(): void
    {
        $md = [
            '```phel skip',
            '(this-needs-context)',
            '```',
        ];

        $snippets = $this->extractor->extract($md, 'doc.md');

        self::assertTrue($snippets[0]['skip']);
        self::assertSame('marked', $snippets[0]['skipReason']);
    }

    public function test_skip_via_marker_comment(): void
    {
        $md = [
            '<!-- phel-test: skip -->',
            '```phel',
            '(this-needs-context)',
            '```',
        ];

        $snippets = $this->extractor->extract($md, 'doc.md');

        self::assertTrue($snippets[0]['skip']);
        self::assertSame('marked', $snippets[0]['skipReason']);
    }

    public function test_marker_only_applies_to_immediately_following_block(): void
    {
        $md = [
            '<!-- phel-test: skip -->',
            '```phel',
            '(skipped)',
            '```',
            'text resets the marker',
            '```phel',
            '(+ 1 2)',
            '```',
        ];

        $snippets = $this->extractor->extract($md, 'doc.md');

        self::assertTrue($snippets[0]['skip']);
        self::assertFalse($snippets[1]['skip']);
    }

    public function test_empty_block_is_skipped(): void
    {
        $md = [
            '```phel',
            '',
            '```',
        ];

        $snippets = $this->extractor->extract($md, 'doc.md');

        self::assertTrue($snippets[0]['skip']);
        self::assertSame('empty', $snippets[0]['skipReason']);
    }
}
