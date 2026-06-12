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
        $headers = [
            'User-Agent: Phel-Website-Generator',
            'Accept: application/vnd.github+json',
            'X-GitHub-Api-Version: 2022-11-28',
        ];

        // Authenticate when a token is available (GitHub Actions sets GITHUB_TOKEN).
        // Lifts the rate limit from 60 to 5000 req/hr, which is why unauthenticated
        // CI runs hit HTTP 403 on the shared runner IPs.
        $token = getenv('GITHUB_TOKEN') ?: getenv('GH_TOKEN');
        if (is_string($token) && $token !== '') {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => $headers,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            throw new RuntimeException("Failed to fetch releases from GitHub API: {$url}");
        }

        $status = $this->statusCodeFromHeaders($http_response_header ?? []);
        if ($status !== 0 && $status >= 400) {
            throw new RuntimeException(
                "GitHub API returned HTTP {$status} for {$url}. Response: " . substr($response, 0, 500),
            );
        }

        $releases = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($releases)) {
            throw new RuntimeException('Invalid response from GitHub API');
        }

        return $releases;
    }

    /**
     * @param list<string> $responseHeaders
     */
    private function statusCodeFromHeaders(array $responseHeaders): int
    {
        // The status line (e.g. "HTTP/1.1 403 Forbidden") is the first header;
        // on redirects the last status line wins.
        $status = 0;
        foreach ($responseHeaders as $header) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $matches) === 1) {
                $status = (int) $matches[1];
            }
        }

        return $status;
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
