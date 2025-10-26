<?php

declare(strict_types=1);

namespace PhelWeb\ReleasesGenerator\Application;

use PhelWeb\ReleasesGenerator\Domain\Release;

final readonly class GitHubReleasePagesGenerator
{
    public function generateReleasePageContent(Release $release): string
    {
        return $this->generateFrontMatter($release)
            . $this->generateBody($release)
            . $this->generateDownloadsSection($release)
            . $this->generateFooter($release);
    }

    private function generateFrontMatter(Release $release): string
    {
        $description = $this->extractDescription($release->body);

        $markdown = "+++\n";
        $markdown .= "title = \"Release: " . $this->escapeTomlString($release->name) . "\"\n";
        if (!empty($description)) {
            $markdown .= "description = \"" . $this->escapeTomlString($description) . "\"\n";
        }

        $markdown .= "date = " . date('Y-m-d', strtotime($release->publishedAt)) . "\n";

        $markdown .= "+++\n\n";

        return $markdown;
    }

    private function generateBody(Release $release): string
    {
        return $this->formatChangelogLinks($release->body) . "\n\n";
    }

    private function generateDownloadsSection(Release $release): string
    {
        if (false === $release->hasAssets()) {
            return '';
        }

        $downloads = "## Downloads\n\n";

        foreach ($release->assets as $asset) {
            $downloads .= sprintf(
                "- [%s](%s) (%s)\n",
                $asset->name,
                $asset->downloadUrl,
                $this->formatBytes($asset->size),
            );
        }

        return $downloads . "\n";
    }

    private function generateFooter(Release $release): string
    {
        return "---\n\n[View release on GitHub]({$release->htmlUrl})\n";
    }

    private function formatChangelogLinks(string $body): string
    {
        return preg_replace_callback(
            '#https://github\.com/phel-lang/phel-lang/compare/(v[\d.]+)\.\.\.(v[\d.]+)#',
            fn(array $matches): string => "[{$matches[1]}...{$matches[2]}]({$matches[0]})",
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
