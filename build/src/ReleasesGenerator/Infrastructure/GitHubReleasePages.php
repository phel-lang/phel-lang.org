<?php

declare(strict_types=1);

namespace PhelWeb\ReleasesGenerator\Infrastructure;

use RuntimeException;
use PhelWeb\ReleasesGenerator\Application\GitHubReleasePagesGenerator;
use PhelWeb\ReleasesGenerator\Domain\Release;

final readonly class GitHubReleasePages
{
    private const string GITHUB_API_URL = 'https://api.github.com/repos/phel-lang/phel-lang/releases';

    private const int PER_PAGE = 100;

    public function __construct(
        private GitHubReleasePagesGenerator $gitHubReleasePagesGenerator,
        private string $outputDir,
    ) {}

    public function generate(): void
    {
        $releases = $this->fetchAllReleases();

        foreach ($releases as $release) {
            $releaseDto = Release::fromArray($release);
            $this->generateReleasePage($releaseDto);
        }
    }

    private function fetchAllReleases(): array
    {
        $allReleases = [];
        $page = 1;

        do {
            $url = self::GITHUB_API_URL . '?per_page=' . self::PER_PAGE . '&page=' . $page;
            $releases = $this->fetchReleasesPage($url);
            $allReleases = array_merge($allReleases, $releases);
            $page++;
        } while (count($releases) === self::PER_PAGE);

        return $allReleases;
    }

    private function fetchReleasesPage(string $url): array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: Phel-Website-Generator',
                    'Accept: application/vnd.github+json',
                    'X-GitHub-Api-Version: 2022-11-28',
                ],
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            throw new RuntimeException("Failed to fetch releases from GitHub API: {$url}");
        }

        $releases = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($releases)) {
            throw new RuntimeException('Invalid response from GitHub API');
        }

        return $releases;
    }

    private function generateReleasePage(Release $release): void
    {
        $fileName = $this->generateFileName($release);
        $filePath = $this->outputDir . '/' . $fileName;

        $markdown = $this->gitHubReleasePagesGenerator->generateReleasePageContent($release);
        file_put_contents($filePath, $markdown);
    }

    private function generateFileName(Release $release): string
    {
        $slug = strtolower(str_replace(['.', ' '], ['-', '-'], $release->tagName));
        $date = $release->getPublishedDate();

        return "{$date}-release-{$slug}.md";
    }
}
