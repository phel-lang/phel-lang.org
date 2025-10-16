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
