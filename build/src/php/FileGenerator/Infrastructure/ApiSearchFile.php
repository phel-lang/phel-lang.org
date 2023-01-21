<?php

declare(strict_types=1);

namespace PhelDocBuild\FileGenerator\Infrastructure;

use Phel\Api\ApiFacadeInterface;
use PhelDocBuild\FileGenerator\Domain\ApiSearchGenerator;
use function json_encode;

final class ApiSearchFile
{
    public function __construct(
        private ApiFacadeInterface $phelInternalFacade,
        private ApiSearchGenerator $apiSearchGenerator,
        private string $appRootDir
    ) {
    }

    public function generate(): void
    {
        $groupedPhelFns = $this->phelInternalFacade->getNormalizedGroupedFunctions();
        $searchIndex = $this->apiSearchGenerator->generateSearchIndex($groupedPhelFns);

        file_put_contents(
            $this->appRootDir . '/../static/api_search.js',
            "window.searchIndexApi = " . json_encode($searchIndex)
        );
    }
}
