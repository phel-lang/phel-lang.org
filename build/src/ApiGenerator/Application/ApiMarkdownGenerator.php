<?php

declare(strict_types=1);

namespace PhelWeb\ApiGenerator\Application;

use Phel\Api\Transfer\PhelFunction;
use Phel\Shared\Facade\ApiFacadeInterface;

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

        /** @var list<PhelFunction> $phelFns */
        $phelFns = $this->apiFacade->getPhelFunctions();

        $groupedByNamespace = [];
        foreach ($phelFns as $fn) {
            $groupedByNamespace[$fn->namespace][] = $fn;
        }

        foreach ($groupedByNamespace as $namespace => $fns) {

            $result[] = "";
            $result[] = "---";
            $result[] = "";
            $result[] = "## `{$namespace}`";

            /** @var PhelFunction $fn */
            foreach ($fns as $fn) {
                $result[] = "### `{$fn->nameWithNamespace()}`";
                if (isset($fn->meta['deprecated'])) {
                    $deprecatedMessage = sprintf(
                        '<small><span style="color: red; font-weight: bold;">Deprecated</span>: %s',
                        $fn->meta['deprecated']
                    );
                    if (isset($fn->meta['superseded-by'])) {
                        $supersededBy = $fn->meta['superseded-by'];
                        $deprecatedMessage .= sprintf(
                            ' &mdash; Use [`%s`](#%s) instead',
                            $supersededBy,
                            $supersededBy
                        );
                    }
                    $deprecatedMessage .= '</small>';
                    $result[] = $deprecatedMessage;
                }
                $result[] = $fn->doc;
                if ($fn->githubUrl !== '') {
                    $result[] = sprintf('<small>[[View source](%s)]</small>', $fn->githubUrl);
                } elseif ($fn->docUrl !== '') {
                    $result[] = sprintf('<small>[[Read more](%s)]</small>', $fn->docUrl);
                }
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
