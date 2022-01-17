<?php

declare(strict_types=1);

namespace PhelDocBuild\FileGenerator\Infrastructure;

use PhelDocBuild\FileGenerator\Domain\ApiSearchGenerator;
use PhelDocBuild\FileGenerator\Domain\MdPageRenderer;
use PhelDocBuild\FileGenerator\Domain\PhelFnNormalizer;

use function json_encode;

final class DocFileGenerator
{
    private MdPageRenderer $mdPageRenderer;
    private PhelFnNormalizer $phelFnNormalizer;
    private ApiSearchGenerator $apiSearchGenerator;
    private string $srcDir;

    public function __construct(
        MdPageRenderer $mdPageRenderer,
        PhelFnNormalizer $phelFnNormalizer,
        ApiSearchGenerator $apiSearchGenerator,
        string $srcDir
    ) {
        $this->mdPageRenderer = $mdPageRenderer;
        $this->phelFnNormalizer = $phelFnNormalizer;
        $this->apiSearchGenerator = $apiSearchGenerator;
        $this->srcDir = $srcDir;
    }

    public function renderMdPage(): void
    {
        $groupedPhelFns = $this->phelFnNormalizer->getNormalizedGroupedPhelFns();

        $this->mdPageRenderer->renderMdPage($groupedPhelFns);
    }

    public function generateApiSearch(): void
    {
        $groupedPhelFns = $this->phelFnNormalizer->getNormalizedGroupedPhelFns();

        $searchIndex = $this->apiSearchGenerator->generateSearchIndex($groupedPhelFns);

        file_put_contents(
            $this->srcDir . '/../../static/api_search.js',
            "window.searchIndexApi = " . json_encode($searchIndex)
        );
    }

}
