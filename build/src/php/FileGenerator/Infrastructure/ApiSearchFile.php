<?php

declare(strict_types=1);

namespace PhelDocBuild\FileGenerator\Infrastructure;

use PhelDocBuild\FileGenerator\Domain\ApiSearchGenerator;
use PhelDocBuild\FileGenerator\Domain\PhelFnNormalizerInterface;
use function json_encode;

final class ApiSearchFile
{
    private PhelFnNormalizerInterface $phelFnNormalizer;

    private ApiSearchGenerator $apiSearchGenerator;

    private string $appRootDir;

    public function __construct(
        PhelFnNormalizerInterface $phelFnNormalizer,
        ApiSearchGenerator $apiSearchGenerator,
        string $appRootDir
    ) {
        $this->phelFnNormalizer = $phelFnNormalizer;
        $this->apiSearchGenerator = $apiSearchGenerator;
        $this->appRootDir = $appRootDir;
    }

    public function generate(): void
    {
        $groupedPhelFns = $this->phelFnNormalizer->getNormalizedGroupedPhelFns();

        $searchIndex = $this->apiSearchGenerator->generateSearchIndex($groupedPhelFns);

        file_put_contents(
            $this->appRootDir . '/../static/api_search.js',
            "window.searchIndexApi = " . json_encode($searchIndex)
        );
    }

}
