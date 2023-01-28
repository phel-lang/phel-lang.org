<?php

declare(strict_types=1);

namespace PhelDocBuild\FileGenerator\Infrastructure;

use PhelDocBuild\FileGenerator\Domain\ApiSearchGenerator;

use function json_encode;

final class ApiSearchFile
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
            "window.searchIndexApi = " . json_encode($searchIndex)
        );
    }
}
