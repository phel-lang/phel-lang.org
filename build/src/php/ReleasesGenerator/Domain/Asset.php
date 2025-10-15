<?php

declare(strict_types=1);

namespace PhelWeb\ReleasesGenerator\Domain;

final readonly class Asset
{
    public function __construct(
        public string $name,
        public string $downloadUrl,
        public int $size,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            downloadUrl: $data['browser_download_url'],
            size: $data['size'],
        );
    }
}
