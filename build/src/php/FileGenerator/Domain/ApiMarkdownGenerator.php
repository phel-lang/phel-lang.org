<?php

declare(strict_types=1);

namespace PhelDocBuild\FileGenerator\Domain;

final class ApiMarkdownGenerator
{
    public function __construct(
        private PhelFunctionRepositoryInterface $repository
    ) {
    }

    /**
     * @return list<string>
     */
    public function generate(): array
    {
        $result = $this->zolaHeaders();
        $groupedPhelFns = $this->repository->getAllGroupedFunctions();

        foreach ($groupedPhelFns as $phelFunctions) {
            foreach ($phelFunctions as $fn) {
                $result[] = "## `{$fn->fnName()}`";
                $result[] = $fn->doc();
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
        $result[] = 'aliases = [ "/api" ]';
        $result[] = '+++';
        $result[] = '';

        return $result;
    }
}
