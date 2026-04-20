<?php

declare(strict_types=1);

namespace PhelWeb\ReleasesGenerator\Infrastructure;

use PhelWeb\ReleasesGenerator\Application\GitHubReleasePagesGenerator;
use PhelWeb\ReleasesGenerator\Domain\Release;
use RuntimeException;

final readonly class GitHubReleasePages
{
    private const string GITHUB_API_URL = 'https://api.github.com/repos/phel-lang/phel-lang/releases';

    private const int PER_PAGE = 100;

    public function __construct(
        private GitHubReleasePagesGenerator $gitHubReleasePagesGenerator,
        private string $outputDir,
    ) {
    }

    public function generate(): void
    {
        $rawReleases = $this->fetchAllReleases();
        $releases = $this->mapToReleases($rawReleases);

        $this->cleanStaleReleasePages();

        $groups = $this->groupByMinor($releases);

        foreach ($groups as $group) {
            $this->generateMinorPage($group);
        }
    }

    /**
     * @param list<array<string, mixed>> $rawReleases
     * @return list<Release>
     */
    private function mapToReleases(array $rawReleases): array
    {
        $releases = [];
        foreach ($rawReleases as $raw) {
            $release = Release::fromArray($raw);
            if ($release->hasValidVersion()) {
                $releases[] = $release;
            }
        }
        return $releases;
    }

    /**
     * @param list<Release> $releases
     * @return list<list<Release>>
     */
    private function groupByMinor(array $releases): array
    {
        $groups = [];
        foreach ($releases as $release) {
            $key = $release->getMinorKey();
            $groups[$key] ??= [];
            $groups[$key][] = $release;
        }
        return array_values($groups);
    }

    private function cleanStaleReleasePages(): void
    {
        foreach (glob($this->outputDir . '/*.md') ?: [] as $file) {
            if (basename($file) === '_index.md') {
                continue;
            }
            @unlink($file);
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

    /**
     * @param list<Release> $group
     */
    private function generateMinorPage(array $group): void
    {
        $markdown = $this->gitHubReleasePagesGenerator->generateMinorPageContent($group);
        $fileName = $this->generateFileName($group);
        file_put_contents($this->outputDir . '/' . $fileName, $markdown);
    }

    /**
     * @param list<Release> $group
     */
    private function generateFileName(array $group): string
    {
        $latest = $this->latestRelease($group);
        $slug = $this->gitHubReleasePagesGenerator->computeSlug($group);
        return "{$latest->getPublishedDate()}-{$slug}.md";
    }

    /**
     * @param list<Release> $group
     */
    private function latestRelease(array $group): Release
    {
        $sorted = $group;
        usort($sorted, static fn(Release $a, Release $b): int => $b->getPatch() <=> $a->getPatch());
        return $sorted[0];
    }
}
