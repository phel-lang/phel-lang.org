<?php

declare(strict_types=1);

namespace PhelWeb\ReleasesGenerator\Application;

use PhelWeb\ReleasesGenerator\Domain\Release;
use RuntimeException;

final readonly class GitHubReleasePagesGenerator
{
    /**
     * @param list<Release> $releases All releases belonging to one minor version.
     */
    public function generateMinorPageContent(array $releases): string
    {
        if (count($releases) === 0) {
            throw new RuntimeException('Cannot generate a minor page from an empty release list');
        }

        $sorted = $this->sortByPatchDesc($releases);

        $headline = $this->findHeadline($sorted);
        $latest = $sorted[0];
        $patches = array_values(array_filter($sorted, static fn(Release $r): bool => $r->getPatch() > 0));

        return $this->generateFrontMatter($headline, $latest, $sorted)
            . $this->generateBody($headline, $patches)
            . $this->generateDownloadsSection($sorted)
            . $this->generateFooter($headline);
    }

    /**
     * @param list<Release> $releases
     */
    public function computeSlug(array $releases): string
    {
        $sorted = $this->sortByPatchDesc($releases);
        $headline = $this->findHeadline($sorted);
        $base = $headline->getMinorDashKey();
        $suffix = $this->extractNameSuffix($headline->name, $headline->getVersion());

        if ($suffix === '') {
            return $base;
        }

        return $base . '-' . $this->slugify($suffix);
    }

    /**
     * @param list<Release> $releases
     * @return list<Release>
     */
    private function sortByPatchDesc(array $releases): array
    {
        $sorted = $releases;
        usort($sorted, static fn(Release $a, Release $b): int => $b->getPatch() <=> $a->getPatch());
        return $sorted;
    }

    /**
     * @param list<Release> $sorted
     */
    private function findHeadline(array $sorted): Release
    {
        foreach ($sorted as $release) {
            if ($release->getPatch() === 0) {
                return $release;
            }
        }
        // Fallback: use the oldest patch as the headline.
        return end($sorted) ?: $sorted[0];
    }

    /**
     * @param list<Release> $sorted
     */
    private function generateFrontMatter(Release $headline, Release $latest, array $sorted): string
    {
        $description = $this->extractDescription($headline->body);
        $title = $this->buildMinorTitle($headline);
        $slug = $this->computeSlug($sorted);

        $versions = array_map(static fn(Release $r): string => $r->getVersion(), $sorted);
        $aliases = $this->computeAliases($sorted, $headline, $slug);

        $markdown = "+++\n";
        $markdown .= "title = \"" . $this->escapeTomlString($title) . "\"\n";
        if (!empty($description)) {
            $markdown .= "description = \"" . $this->escapeTomlString($description) . "\"\n";
        }
        $markdown .= "date = " . $latest->getPublishedDate() . "\n";
        $markdown .= "slug = \"" . $slug . "\"\n";

        if ($aliases !== []) {
            $markdown .= "aliases = [" . implode(', ', array_map(
                fn(string $p): string => '"' . $p . '"',
                $aliases,
            )) . "]\n";
        }

        $markdown .= "\n[extra]\n";
        $markdown .= "minor = \"" . $headline->getMinorKey() . "\"\n";
        $markdown .= "minor_sort = " . sprintf('"%05d.%05d"', $headline->getMajor(), $headline->getMinor()) . "\n";
        $markdown .= "headline_version = \"" . $headline->getVersion() . "\"\n";
        $markdown .= "latest_version = \"" . $latest->getVersion() . "\"\n";
        $markdown .= "versions = [" . implode(', ', array_map(
            fn(string $v): string => '"' . $v . '"',
            $versions,
        )) . "]\n";

        $markdown .= "+++\n\n";

        return $markdown;
    }

    /**
     * @param list<Release> $sorted
     * @return list<string>
     */
    private function computeAliases(array $sorted, Release $headline, string $slug): array
    {
        $aliases = [];
        foreach ($sorted as $release) {
            $aliases[] = "/releases/release-v{$release->getMajor()}-{$release->getMinor()}-{$release->getPatch()}/";
        }

        $aliases[] = "/releases/v{$headline->getMinorDashKey()}/";

        $canonical = "/releases/{$slug}/";
        $aliases = array_filter(
            array_unique($aliases),
            static fn(string $path): bool => $path !== $canonical,
        );

        return array_values($aliases);
    }

    private function buildMinorTitle(Release $headline): string
    {
        $minor = $headline->getMinorKey();
        $suffix = $this->extractNameSuffix($headline->name, $headline->getVersion());

        return $suffix !== ''
            ? "Release: {$minor} - {$suffix}"
            : "Release: {$minor}";
    }

    private function extractNameSuffix(string $rawName, string $version): string
    {
        $name = trim($rawName);
        $name = preg_replace('/^Release\s*[:\-]?\s*/i', '', $name) ?? $name;
        $name = preg_replace('/\bv?' . preg_quote($version, '/') . '\b/', '', $name) ?? $name;
        $name = preg_replace('/^v?\d+\.\d+(?:\.\d+)?\b/', '', $name) ?? $name;

        return trim($name, " \t\n\r\0\x0B-–—:·");
    }

    /**
     * @param list<Release> $patches
     */
    private function generateBody(Release $headline, array $patches): string
    {
        $body = '';

        foreach ($patches as $patch) {
            $body .= $this->renderSection($patch);
            $body .= "---\n\n";
        }

        $body .= $this->renderSection($headline);

        return $body;
    }

    private function renderSection(Release $release): string
    {
        $anchor = '<a id="v' . str_replace('.', '-', $release->getVersion()) . '"></a>';

        $name = trim($release->name) !== '' ? trim($release->name) : $release->getVersion();
        $heading = "## " . $this->stripLeadingReleasePrefix($name);

        $meta = '*Released ' . $release->getPublishedDate() . '*';
        $meta .= ' · [GitHub release](' . $release->htmlUrl . ')';

        $body = $this->formatChangelogLinks($release->body);

        return "{$anchor}\n\n{$heading}\n\n{$meta}\n\n{$body}\n\n";
    }

    private function stripLeadingReleasePrefix(string $name): string
    {
        return preg_replace('/^Release\s*[:\-]?\s*/i', '', $name) ?? $name;
    }

    /**
     * @param list<Release> $releases
     */
    private function generateDownloadsSection(array $releases): string
    {
        $withAssets = array_values(array_filter($releases, static fn(Release $r): bool => $r->hasAssets()));
        if (count($withAssets) === 0) {
            return '';
        }

        $out = "## Downloads\n\n";
        foreach ($withAssets as $release) {
            $out .= "**v{$release->getVersion()}**\n\n";
            foreach ($release->assets as $asset) {
                $out .= sprintf(
                    "- [%s](%s) (%s)\n",
                    $asset->name,
                    $asset->downloadUrl,
                    $this->formatBytes($asset->size),
                );
            }
            $out .= "\n";
        }

        return $out;
    }

    private function generateFooter(Release $headline): string
    {
        return "---\n\n[View release on GitHub]({$headline->htmlUrl})\n";
    }

    private function formatChangelogLinks(string $body): string
    {
        $body = $this->formatPrReferences($body);

        return preg_replace_callback(
            '#https://github\.com/phel-lang/phel-lang/compare/(v[\d.]+)\.\.\.(v[\d.]+)#',
            fn(array $matches): string => "[{$matches[1]}...{$matches[2]}]({$matches[0]})",
            $body,
        );
    }

    private function formatPrReferences(string $body): string
    {
        return preg_replace_callback(
            '/\((#\d+(?:,\s*#\d+)*)\)/',
            static function (array $matches): string {
                $refs = preg_replace_callback(
                    '/#(\d+)/',
                    static fn(array $m): string => "[#{$m[1]}](https://github.com/phel-lang/phel-lang/pull/{$m[1]})",
                    $matches[1],
                );
                return "({$refs})";
            },
            $body,
        );
    }

    private function extractDescription(string $body): string
    {
        if (empty($body)) {
            return '';
        }

        $cleanedBody = $this->removeBlockquotes($body);
        $description = $this->buildDescriptionFromLines($cleanedBody);

        return $this->truncateDescription($description);
    }

    private function removeBlockquotes(string $body): string
    {
        return preg_replace('/^>\s*/m', '', $body);
    }

    private function buildDescriptionFromLines(string $body): string
    {
        $lines = explode("\n", trim($body));
        $description = '';

        foreach ($lines as $line) {
            $line = trim($line);

            if ($this->shouldSkipLine($line)) {
                if (!empty($description)) {
                    break;
                }
                continue;
            }

            $description .= $line . ' ';

            if (strlen($description) > 150) {
                break;
            }
        }

        return trim($description);
    }

    private function shouldSkipLine(string $line): bool
    {
        return empty($line)
            || str_starts_with($line, '#')
            || str_starts_with($line, '-')
            || str_starts_with($line, '*')
            || str_starts_with($line, '@');
    }

    private function truncateDescription(string $description): string
    {
        if (strlen($description) <= 200) {
            return $description;
        }

        return substr($description, 0, 197) . '...';
    }

    private function escapeTomlString(string $value): string
    {
        return str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
    }

    private function slugify(string $value): string
    {
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        return trim($value, '-');
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);

        return round($bytes / (1024 ** $power), 2) . ' ' . $units[$power];
    }
}
