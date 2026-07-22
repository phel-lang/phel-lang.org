<?php

declare(strict_types=1);

namespace PhelWeb\ReleasesGenerator\Domain;

/**
 * One downloadable file attached to a GitHub release.
 *
 * @psalm-type TGitHubAssetPayload = array{
 *     name: string,
 *     browser_download_url: string,
 *     size: int,
 *     ...
 * }
 */
final readonly class Asset
{
    public function __construct(
        public string $name,
        public string $downloadUrl,
        public int $size,
    ) {
    }

    /**
     * @param TGitHubAssetPayload $data Raw asset entry from GET /repos/{owner}/{repo}/releases
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            downloadUrl: $data['browser_download_url'],
            size: $data['size'],
        );
    }
}
