<?php

declare(strict_types=1);

namespace PhelDocBuild\FileGenerator\Infrastructure;

use PhelDocBuild\FileGenerator\Domain\ApiSearchGenerator;
use PhelDocBuild\FileGenerator\Domain\MdPageRenderer;
use PhelDocBuild\FileGenerator\Domain\PhelFnNormalizer;

final class DocFileGenerator
{
    private MdPageRenderer $mdPageRenderer;
    private PhelFnNormalizer $phelFnNormalizer;
    private ApiSearchGenerator $apiSearchGenerator;

    public function __construct(
        MdPageRenderer $mdPageRenderer,
        PhelFnNormalizer $phelFnNormalizer,
        ApiSearchGenerator $apiSearchGenerator
    ) {
        $this->mdPageRenderer = $mdPageRenderer;
        $this->phelFnNormalizer = $phelFnNormalizer;
        $this->apiSearchGenerator = $apiSearchGenerator;
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
            __DIR__ . '/../../../../static/api_search.js',
            "window.searchIndexApi = " . \json_encode($searchIndex)
        );
    }

}
