<?php

declare(strict_types=1);

namespace PhelDocBuild\FileGenerator\Infrastructure;

use PhelDocBuild\FileGenerator\Domain\ApiSearchGenerator;
use PhelDocBuild\FileGenerator\Domain\MdPageRenderer;
use PhelDocBuild\FileGenerator\Domain\PhelFnNormalizer;
use function json_encode;

final class ApiSearchFile
{
    private PhelFnNormalizer $phelFnNormalizer;
    private ApiSearchGenerator $apiSearchGenerator;
    private string $srcDir;

    public function __construct(
        PhelFnNormalizer $phelFnNormalizer,
        ApiSearchGenerator $apiSearchGenerator,
        string $srcDir
    ) {
        $this->phelFnNormalizer = $phelFnNormalizer;
        $this->apiSearchGenerator = $apiSearchGenerator;
        $this->srcDir = $srcDir;
    }

    public function generate(): void
    {
        $groupedPhelFns = $this->phelFnNormalizer->getNormalizedGroupedPhelFns();

        $searchIndex = $this->apiSearchGenerator->generateSearchIndex($groupedPhelFns);

        file_put_contents(
            $this->srcDir . '/../../static/api_search.js',
            "window.searchIndexApi = " . json_encode($searchIndex)
        );
    }

}
