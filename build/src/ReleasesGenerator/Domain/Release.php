<?php

declare(strict_types=1);

namespace PhelWeb\ReleasesGenerator\Domain;

final readonly class Release
{
    /**
     * @param list<Asset> $assets
     */
    public function __construct(
        public string $tagName,
        public string $name,
        public string $body,
        public string $publishedAt,
        public string $htmlUrl,
        public array $assets,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $assets = array_map(
            fn(array $assetData): Asset => Asset::fromArray($assetData),
            $data['assets'],
        );

        return new self(
            tagName: $data['tag_name'],
            name: $data['name'],
            body: $data['body'],
            publishedAt: $data['published_at'],
            htmlUrl: $data['html_url'],
            assets: $assets,
        );
    }

    public function hasAssets(): bool
    {
        return count($this->assets) > 0;
    }

    public function getPublishedDate(): string
    {
        return (new \DateTimeImmutable($this->publishedAt))
            ->setTimezone(new \DateTimeZone('Europe/Berlin'))
            ->format('Y-m-d');
    }

    public function getMajor(): int
    {
        return $this->parseVersion()[0];
    }

    public function getMinor(): int
    {
        return $this->parseVersion()[1];
    }

    public function getPatch(): int
    {
        return $this->parseVersion()[2];
    }

    public function getMinorKey(): string
    {
        [$major, $minor] = $this->parseVersion();
        return "{$major}.{$minor}";
    }

    public function getMinorDashKey(): string
    {
        [$major, $minor] = $this->parseVersion();
        return "{$major}-{$minor}";
    }

    public function getVersion(): string
    {
        [$major, $minor, $patch] = $this->parseVersion();
        return "{$major}.{$minor}.{$patch}";
    }

    public function hasValidVersion(): bool
    {
        $parsed = $this->tryParseVersion();
        return $parsed !== null;
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function parseVersion(): array
    {
        $parsed = $this->tryParseVersion();
        if ($parsed === null) {
            throw new \RuntimeException("Invalid semver tag: {$this->tagName}");
        }
        return $parsed;
    }

    /**
     * @return array{0: int, 1: int, 2: int}|null
     */
    private function tryParseVersion(): ?array
    {
        $stripped = ltrim($this->tagName, 'vV');
        if (!preg_match('/^(\d+)\.(\d+)(?:\.(\d+))?/', $stripped, $m)) {
            return null;
        }
        return [(int) $m[1], (int) $m[2], isset($m[3]) ? (int) $m[3] : 0];
    }
}
