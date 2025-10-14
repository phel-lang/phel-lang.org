<?php

declare(strict_types=1);

namespace PhelWeb\ReleasesGenerator\Infrastructure;

use PhelWeb\ReleasesGenerator\Application\GitHubIndexReleaseGenerator;

final readonly class GitHubIndexRelease
{
    public function __construct(
        private GitHubIndexReleaseGenerator $gitHubIndexReleaseGenerator,
        private string $outputDir,
    ) {}

    public function generate(): void
    {
        $frontMatter = $this->gitHubIndexReleaseGenerator->generateFrontMatterText();

        file_put_contents($this->outputDir, $frontMatter);
    }
}
