<?php

declare(strict_types=1);

namespace PhelDocBuild\FileGenerator\Application;

use Phel\Api\ApiFacadeInterface;
use Phel\Api\Transfer\PhelFunction;

final readonly class ApiMarkdownGenerator
{
    public function __construct(
        private ApiFacadeInterface $apiFacade
    ) {
    }

    /**
     * @return list<string>
     */
    public function generate(): array
    {
        $result = $this->zolaHeaders();

        /** @var list<PhelFunction> $groupedPhelFns */
        $groupedPhelFns = $this->apiFacade->getPhelFunctions();

        foreach ($groupedPhelFns as $fn) {
            $result[] = "## `{$fn->name()}`";
            $result[] = "<small><strong>Namespace</strong> `{$fn->namespace()}`</small>";
            $result[] = $fn->doc();
            if ($fn->githubUrl() !== '') {
                $result[] = sprintf('<small>[[View source](%s)]</small>', $fn->githubUrl());
            }elseif ($fn->docUrl() !== '') {
                $result[] = sprintf('<small>[[Read more](%s)]</small>', $fn->docUrl());
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
