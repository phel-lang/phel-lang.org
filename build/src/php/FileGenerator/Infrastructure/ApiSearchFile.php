<?php

declare(strict_types=1);

namespace PhelWeb\FileGenerator\Infrastructure;

use PhelWeb\FileGenerator\Application\ApiSearchGenerator;

use function json_encode;

final readonly class ApiSearchFile
{
    public function __construct(
        private ApiSearchGenerator $apiSearchGenerator,
        private string $appRootDir
    ) {
    }

    public function generate(): void
    {
        $searchIndex = $this->apiSearchGenerator->generateSearchIndex();

        file_put_contents(
            $this->appRootDir . '/../static/api_search.js',
            "window.searchIndexApi = " . json_encode($searchIndex, JSON_THROW_ON_ERROR)
        );
    }
}
