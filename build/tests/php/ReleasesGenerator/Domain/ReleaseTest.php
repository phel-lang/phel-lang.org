<?php

declare(strict_types=1);

namespace PhelWebTests\ReleasesGenerator\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PhelWeb\ReleasesGenerator\Domain\Release;

final class ReleaseTest extends TestCase
{
    public function test_final_release_is_stable(): void
    {
        $release = $this->makeRelease('v0.54.0', prerelease: false);

        self::assertTrue($release->isStable());
        self::assertSame('0.54.0', $release->getVersion());
    }

    public function test_release_without_prerelease_flag_is_stable(): void
    {
        $release = Release::fromArray([
            'tag_name' => 'v0.54.0',
            'name' => '0.54.0',
            'body' => 'Notes.',
            'published_at' => '2026-10-04T10:00:00Z',
            'html_url' => 'https://github.com/phel-lang/phel-lang/releases/tag/v0.54.0',
            'assets' => [],
        ]);

        self::assertTrue($release->isStable());
    }

    public function test_release_marked_as_prerelease_is_not_stable(): void
    {
        $release = $this->makeRelease('v1.0.0-rc2', prerelease: true);

        self::assertFalse($release->isStable());
    }

    #[DataProvider('preReleaseTags')]
    public function test_pre_release_tag_is_not_stable_even_without_the_flag(string $tagName): void
    {
        $release = $this->makeRelease($tagName, prerelease: false);

        self::assertFalse($release->isStable());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function preReleaseTags(): iterable
    {
        yield 'release candidate' => ['v1.0.0-rc1'];
        yield 'beta' => ['v1.0.0-beta'];
        yield 'not a version' => ['nightly'];
    }

    private function makeRelease(string $tagName, bool $prerelease): Release
    {
        return Release::fromArray([
            'tag_name' => $tagName,
            'name' => ltrim($tagName, 'v'),
            'body' => 'Notes.',
            'published_at' => '2026-10-04T10:00:00Z',
            'html_url' => 'https://github.com/phel-lang/phel-lang/releases/tag/' . $tagName,
            'assets' => [],
            'prerelease' => $prerelease,
        ]);
    }
}
