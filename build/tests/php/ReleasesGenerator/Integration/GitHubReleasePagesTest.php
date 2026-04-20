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

    public function test_generate_minor_pages_grouping_patches(): void
    {
        $generator = new GitHubReleasePagesGenerator();

        $minor34Headline = Release::fromArray([
            'tag_name' => 'v0.34.0',
            'name' => '0.34.0 - Toolsmith',
            'body' => 'Big toolsmith release.',
            'published_at' => '2026-04-20T10:00:00Z',
            'html_url' => 'https://github.com/phel-lang/phel-lang/releases/tag/v0.34.0',
            'assets' => [
                [
                    'name' => 'phel.phar',
                    'browser_download_url' => 'https://github.com/phel-lang/phel-lang/releases/download/v0.34.0/phel.phar',
                    'size' => 5242880,
                ],
            ],
        ]);
        $minor34Patch = Release::fromArray([
            'tag_name' => 'v0.34.1',
            'name' => '0.34.1',
            'body' => 'Patch release fixing format --dry-run.',
            'published_at' => '2026-04-21T10:00:00Z',
            'html_url' => 'https://github.com/phel-lang/phel-lang/releases/tag/v0.34.1',
            'assets' => [],
        ]);

        $minor33 = Release::fromArray([
            'tag_name' => 'v0.33.0',
            'name' => '0.33.0',
            'body' => 'Another release.',
            'published_at' => '2026-04-17T10:00:00Z',
            'html_url' => 'https://github.com/phel-lang/phel-lang/releases/tag/v0.33.0',
            'assets' => [],
        ]);

        $minor34Page = $generator->generateMinorPageContent([$minor34Headline, $minor34Patch]);
        $minor33Page = $generator->generateMinorPageContent([$minor33]);

        file_put_contents($this->tempDir . '/2026-04-21-0-34-toolsmith.md', $minor34Page);
        file_put_contents($this->tempDir . '/2026-04-17-0-33.md', $minor33Page);

        $files = glob($this->tempDir . '/*.md');
        self::assertCount(2, $files);

        $file34 = file_get_contents($this->tempDir . '/2026-04-21-0-34-toolsmith.md');
        self::assertStringContainsString('title = "Release: 0.34 - Toolsmith"', $file34);
        self::assertStringContainsString('date = 2026-04-21', $file34);
        self::assertStringContainsString('slug = "0-34-toolsmith"', $file34);
        self::assertStringContainsString('## 0.34.1', $file34);
        self::assertStringContainsString('## 0.34.0 - Toolsmith', $file34);
        self::assertStringContainsString('## Downloads', $file34);
        self::assertStringContainsString('**v0.34.0**', $file34);
        self::assertStringContainsString('5 MB', $file34);

        $file33 = file_get_contents($this->tempDir . '/2026-04-17-0-33.md');
        self::assertStringContainsString('title = "Release: 0.33"', $file33);
        self::assertStringContainsString('slug = "0-33"', $file33);
    }
}
