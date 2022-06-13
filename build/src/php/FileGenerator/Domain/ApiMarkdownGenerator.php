<?php

declare(strict_types=1);

namespace PhelDocBuild\FileGenerator\Domain;

use PhelNormalizedInternal\PhelNormalizedInternalFacadeInterface;

final class ApiMarkdownGenerator
{
    private PhelNormalizedInternalFacadeInterface $phelInternalFacade;

    public function __construct(PhelNormalizedInternalFacadeInterface $phelInternalFacade)
    {
        $this->phelInternalFacade = $phelInternalFacade;
    }

    /**
     * @return list<string>
     */
    public function generate(): array
    {
        $result = $this->zolaHeaders();

        $groupedPhelFns = $this->phelInternalFacade->getNormalizedGroupedFunctions();

        foreach ($groupedPhelFns as $values) {
            foreach ($values as $value) {

                $result[] = "## `{$value->fnName()}`";
                $result[] = $value->doc();
            }
        }

        return $result;
    }

    /**
     * @return list<string>
     */
    private function zolaHeaders(): array
    {
        $result = [];
        $result[] = '+++';
        $result[] = 'title = "API"';
        $result[] = 'weight = 110';
        $result[] = 'template = "page-api.html"';
        $result[] = '+++';
        $result[] = '';

        return $result;
    }
}
