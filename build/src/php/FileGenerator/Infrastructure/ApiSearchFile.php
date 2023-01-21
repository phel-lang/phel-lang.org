<?php

declare(strict_types=1);

namespace PhelDocBuild\FileGenerator\Infrastructure;

use Phel\Api\ApiFacadeInterface;
use PhelDocBuild\FileGenerator\Domain\ApiSearchGenerator;

use function json_encode;

final class ApiSearchFile
{
    public function __construct(
        private ApiFacadeInterface $phelApiFacade,
        private ApiSearchGenerator $apiSearchGenerator,
        private string $appRootDir,
        private array $allNamespaces = []
    ) {
    }

    public function generate(): void
    {
        $groupedPhelFns = $this->phelApiFacade->getNormalizedGroupedFunctions($this->allNamespaces);
        $searchIndex = $this->apiSearchGenerator->generateSearchIndex($groupedPhelFns);

        file_put_contents(
            $this->appRootDir . '/../static/api_search.js',
            "window.searchIndexApi = " . json_encode($searchIndex)
        );
    }
}
