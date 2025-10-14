<?php

declare(strict_types=1);

namespace PhelWebTests\ReleasesGenerator\Domain;

use PhelWeb\ReleasesGenerator\Application\GitHubIndexReleaseGenerator;
use PHPUnit\Framework\TestCase;

final class GitHubIndexReleaseGeneratorTest extends TestCase
{
    public function test_generate_front_matter_text(): void
    {
        $generator = new GitHubIndexReleaseGenerator();

        $result = $generator->generateFrontMatterText();

        self::assertStringContainsString('title = "Phel Releases"', $result);
        self::assertStringContainsString('sort_by = "date"', $result);
        self::assertStringContainsString('template = "releases.html"', $result);
        self::assertStringContainsString('page_template = "blog-entry.html"', $result);
        self::assertStringContainsString('paginate_by = 15', $result);
    }
}
