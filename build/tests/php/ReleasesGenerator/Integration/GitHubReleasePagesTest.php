<?php

declare(strict_types=1);

namespace PhelWebTests\ReleasesGenerator\Integration;

use Gacela\Framework\Bootstrap\GacelaConfig;
use Gacela\Framework\Gacela;
use PHPUnit\Framework\TestCase;
use PhelWeb\ReleasesGenerator\Application\GitHubReleasePagesGenerator;
use PhelWeb\ReleasesGenerator\Domain\Release;

final class GitHubReleasePagesTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        Gacela::bootstrap(__DIR__ . '/../../../..', static function (GacelaConfig $config): void {
            $config->addAppConfig('phel-config.php', 'phel-config-local.php');
        });

        $this->tempDir = sys_get_temp_dir() . '/phel-releases-test-' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->tempDir);
        }
    }

    public function test_generate_release_pages_from_mock_data(): void
    {
        $generator = new GitHubReleasePagesGenerator();

        $this->createMockReleasePages($generator);

        $files = glob($this->tempDir . '/*.md');
        self::assertCount(2, $files);

        $file1Content = file_get_contents($this->tempDir . '/2025-10-05-release-v0-23-0.md');
        self::assertStringContainsString('title = "Release: Release 0.23.0"', $file1Content);
        self::assertStringContainsString('date = 2025-10-05', $file1Content);
        self::assertStringContainsString('This is a test release', $file1Content);
        self::assertStringContainsString('## Downloads', $file1Content);
        self::assertStringContainsString('phel.phar', $file1Content);
        self::assertStringContainsString('5 MB', $file1Content);

        $file2Content = file_get_contents($this->tempDir . '/2025-09-01-release-v0-22-0.md');
        self::assertStringContainsString('title = "Release: Release 0.22.0"', $file2Content);
        self::assertStringContainsString('date = 2025-09-01', $file2Content);
    }

    private function createMockReleasePages(GitHubReleasePagesGenerator $generator): void
    {
        $mockReleases = [
            [
                'tag_name' => 'v0.23.0',
                'name' => 'Release 0.23.0',
                'body' => 'This is a test release',
                'published_at' => '2025-10-05T10:00:00Z',
                'html_url' => 'https://github.com/phel-lang/phel-lang/releases/tag/v0.23.0',
                'assets' => [
                    [
                        'name' => 'phel.phar',
                        'browser_download_url' => 'https://github.com/phel-lang/phel-lang/releases/download/v0.23.0/phel.phar',
                        'size' => 5242880, // 5 MB
                    ],
                ],
            ],
            [
                'tag_name' => 'v0.22.0',
                'name' => 'Release 0.22.0',
                'body' => 'This is another test release',
                'published_at' => '2025-09-01T10:00:00Z',
                'html_url' => 'https://github.com/phel-lang/phel-lang/releases/tag/v0.22.0',
                'assets' => [],
            ],
        ];

        foreach ($mockReleases as $releaseData) {
            $release = Release::fromArray($releaseData);
            $content = $generator->generateReleasePageContent($release);
            $fileName = $this->generateFileName($release);
            file_put_contents($this->tempDir . '/' . $fileName, $content);
        }
    }

    private function generateFileName(Release $release): string
    {
        $slug = strtolower(str_replace(['.', ' '], ['-', '-'], $release->tagName));
        $date = $release->getPublishedDate();

        return "{$date}-release-{$slug}.md";
    }
}
