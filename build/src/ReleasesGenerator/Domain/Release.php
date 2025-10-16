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
    ) {}

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
        return date('Y-m-d', strtotime($this->publishedAt));
    }
}
