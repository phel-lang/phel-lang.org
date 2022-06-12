<?php

declare(strict_types=1);

namespace PhelDocBuild\FileGenerator\Domain;

final class ApiMarkdownGenerator
{
    private PhelFnNormalizerInterface $phelFnNormalizer;

    public function __construct(PhelFnNormalizerInterface $phelFnNormalizer)
    {
        $this->phelFnNormalizer = $phelFnNormalizer;
    }

    /**
     * @return list<string>
     */
    public function generate(): array
    {
        $result = $this->zolaHeaders();

        $groupedPhelFns = $this->phelFnNormalizer->getNormalizedGroupedPhelFns();

        foreach ($groupedPhelFns as $values) {
            foreach ($values as ['fnName' => $fnName, 'doc' => $doc]) {
                $result[] = "## `$fnName`";
                $result[] = $doc;
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
