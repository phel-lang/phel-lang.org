<?php

declare(strict_types=1);

namespace PhelWebTests\ReleasesGenerator\Domain;

use PHPUnit\Framework\TestCase;
use PhelWeb\ReleasesGenerator\Application\GitHubReleasePagesGenerator;
use PhelWeb\ReleasesGenerator\Domain\Release;

final class GitHubReleasePagesGeneratorTest extends TestCase
{
    private GitHubReleasePagesGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new GitHubReleasePagesGenerator();
    }

    public function test_generate_minimal_release_page(): void
    {
        $release = Release::fromArray([
            'tag_name' => 'v0.23.0',
            'name' => 'Release 0.23.0',
            'body' => "This is a great release with many improvements.\n\n## Changes\n- Feature 1\n- Feature 2",
            'published_at' => '2025-10-05T10:00:00Z',
            'html_url' => 'https://github.com/phel-lang/phel-lang/releases/tag/v0.23.0',
            'assets' => [],
        ]);

        $result = $this->generator->generateReleasePageContent($release);

        self::assertStringContainsString('title = "Release: Release 0.23.0"', $result);
        self::assertStringContainsString('date = 2025-10-05', $result);
        self::assertStringContainsString('[View release on GitHub](https://github.com/phel-lang/phel-lang/releases/tag/v0.23.0)', $result);
        self::assertStringContainsString('description = "This is a great release with many improvements."', $result);
        self::assertStringContainsString('## Changes', $result);
    }

    public function test_generate_release_page_with_assets(): void
    {
        $release = Release::fromArray([
            'tag_name' => 'v0.23.0',
            'name' => 'Release 0.23.0',
            'body' => 'Release notes',
            'published_at' => '2025-10-05T10:00:00Z',
            'html_url' => 'https://github.com/phel-lang/phel-lang/releases/tag/v0.23.0',
            'assets' => [
                [
                    'name' => 'phel.phar',
                    'browser_download_url' => 'https://github.com/phel-lang/phel-lang/releases/download/v0.23.0/phel.phar',
                    'size' => 1048576, // 1 MB
                ],
                [
                    'name' => 'source.zip',
                    'browser_download_url' => 'https://github.com/phel-lang/phel-lang/releases/download/v0.23.0/source.zip',
                    'size' => 2097152, // 2 MB
                ],
            ],
        ]);

        $result = $this->generator->generateReleasePageContent($release);

        self::assertStringContainsString('## Downloads', $result);
        self::assertStringContainsString('[phel.phar](https://github.com/phel-lang/phel-lang/releases/download/v0.23.0/phel.phar) (1 MB)', $result);
        self::assertStringContainsString('[source.zip](https://github.com/phel-lang/phel-lang/releases/download/v0.23.0/source.zip) (2 MB)', $result);
    }

    public function test_escape_special_characters_in_title(): void
    {
        $release = Release::fromArray([
            'tag_name' => 'v0.14.0',
            'name' => 'Release: 0.14.0 - phel\str library',
            'body' => 'Added phel\str library',
            'published_at' => '2024-05-24T10:00:00Z',
            'html_url' => 'https://github.com/phel-lang/phel-lang/releases/tag/v0.14.0',
            'assets' => [],
        ]);

        $result = $this->generator->generateReleasePageContent($release);

        self::assertStringContainsString('title = "Release: Release: 0.14.0 - phel\\\\str library"', $result);
    }

    public function test_format_pr_references_as_links(): void
    {
        $release = Release::fromArray([
            'tag_name' => 'v0.31.0',
            'name' => 'Release 0.31.0',
            'body' => "## Added\n- Feature A (#1153)\n- Feature B (#1128, #1132, #1125)",
            'published_at' => '2026-04-03T10:00:00Z',
            'html_url' => 'https://github.com/phel-lang/phel-lang/releases/tag/v0.31.0',
            'assets' => [],
        ]);

        $result = $this->generator->generateReleasePageContent($release);

        self::assertStringContainsString('[#1153](https://github.com/phel-lang/phel-lang/pull/1153)', $result);
        self::assertStringContainsString('[#1128](https://github.com/phel-lang/phel-lang/pull/1128)', $result);
        self::assertStringContainsString('[#1132](https://github.com/phel-lang/phel-lang/pull/1132)', $result);
        self::assertStringContainsString('[#1125](https://github.com/phel-lang/phel-lang/pull/1125)', $result);
    }

    public function test_extra_block_includes_minor_grouping_fields(): void
    {
        $release = Release::fromArray([
            'tag_name' => 'v0.34.1',
            'name' => 'Release 0.34.1',
            'body' => 'Patch notes',
            'published_at' => '2026-04-21T10:00:00Z',
            'html_url' => 'https://github.com/phel-lang/phel-lang/releases/tag/v0.34.1',
            'assets' => [],
        ]);

        $result = $this->generator->generateReleasePageContent($release);

        self::assertStringContainsString('[extra]', $result);
        self::assertStringContainsString('version = "0.34.1"', $result);
        self::assertStringContainsString('minor = "0.34"', $result);
        self::assertStringContainsString('patch = 1', $result);
        self::assertStringContainsString('is_patch = true', $result);
        self::assertStringContainsString('minor_sort = "00000.00034"', $result);
    }

    public function test_extra_block_for_minor_zero_release(): void
    {
        $release = Release::fromArray([
            'tag_name' => 'v0.34.0',
            'name' => 'Release 0.34.0',
            'body' => 'Notes',
            'published_at' => '2026-04-20T10:00:00Z',
            'html_url' => 'https://github.com/phel-lang/phel-lang/releases/tag/v0.34.0',
            'assets' => [],
        ]);

        $result = $this->generator->generateReleasePageContent($release);

        self::assertStringContainsString('version = "0.34.0"', $result);
        self::assertStringContainsString('minor = "0.34"', $result);
        self::assertStringContainsString('patch = 0', $result);
        self::assertStringContainsString('is_patch = false', $result);
    }

    public function test_extract_description_truncates_long_text(): void
    {
        $longText = str_repeat('This is a very long description. ', 20);
        $release = Release::fromArray([
            'tag_name' => 'v0.23.0',
            'name' => 'Release 0.23.0',
            'body' => $longText,
            'published_at' => '2025-10-05T10:00:00Z',
            'html_url' => 'https://github.com/phel-lang/phel-lang/releases/tag/v0.23.0',
            'assets' => [],
        ]);

        $result = $this->generator->generateReleasePageContent($release);

        self::assertStringContainsString('description =', $result);
        self::assertStringContainsString('...', $result);
    }
}
