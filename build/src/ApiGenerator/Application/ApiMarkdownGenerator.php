<?php

declare(strict_types=1);

namespace PhelWeb\ApiGenerator\Application;

use Phel\Api\Transfer\PhelFunction;
use Phel\Shared\Facade\ApiFacadeInterface;

final readonly class ApiMarkdownGenerator
{
    public function __construct(
        private ApiFacadeInterface $apiFacade,
    ) {
    }

    /**
     * @return list<string>
     */
    public function generate(): array
    {
        $result = $this->buildZolaHeaders();
        $phelFns = $this->apiFacade->getPhelFunctions();
        $groupedByNamespace = $this->groupFunctionsByNamespace($phelFns);

        $namespaces = [];
        foreach ($groupedByNamespace as $namespace => $functions) {
            $namespaces[] = $this->buildNamespaceSection($namespace, $functions);
        }

        return array_merge($result, ...$namespaces);
    }

    /**
     * @param list<PhelFunction> $phelFns
     * @return array<string, list<PhelFunction>>
     */
    private function groupFunctionsByNamespace(array $phelFns): array
    {
        $grouped = [];
        foreach ($phelFns as $fn) {
            $grouped[$fn->namespace][] = $fn;
        }
        return $grouped;
    }

    /**
     * @param list<PhelFunction> $functions
     * @return list<string>
     */
    private function buildNamespaceSection(string $namespace, array $functions): array
    {
        $elements = [];
        foreach ($functions as $fn) {
            $elements[] = $this->buildFunctionSection($fn);
        }

        return array_merge(['', '---', '', "## `{$namespace}`", ''], ...$elements);
    }

    /**
     * @return list<string>
     */
    private function buildFunctionSection(PhelFunction $fn): array
    {
        $lines = ["### `{$fn->nameWithNamespace()}`"];

        if ($deprecation = $this->buildDeprecationNotice($fn)) {
            $lines[] = $deprecation;
        }

        // Handle exceptional documentation blocks
        $input = preg_replace('/```phel/', '```clojure', $fn->doc);
        if ($fn->name === 'with-mock-wrapper' || $fn->name === 'with-mocks') {
            $input = preg_replace('/^[ \t]+/m', '', $input);
            $input = preg_replace('/(?<!\n)\n(```phel)/', "\n\n$1", $input);
        }

        $lines[] = $input;

        if ($example = $this->buildExampleSection($fn)) {
            $lines = array_merge($lines, $example);
        }

        if ($seeAlso = $this->buildSeeAlsoSection($fn)) {
            $lines = array_merge($lines, $seeAlso);
        }

        if ($sourceLink = $this->buildSourceLink($fn)) {
            $lines[] = $sourceLink;
        }
        $lines[] = '';

        return $lines;
    }

    private function buildDeprecationNotice(PhelFunction $fn): ?string
    {
        if (!isset($fn->meta['deprecated'])) {
            return null;
        }

        $message = sprintf(
            '<small><span style="color: red; font-weight: bold;">Deprecated</span>: %s',
            $fn->meta['deprecated'],
        );

        if (isset($fn->meta['superseded-by'])) {
            $supersededBy = $fn->meta['superseded-by'];
            $anchor = $this->sanitizeAnchor($supersededBy);
            $message .= sprintf(
                ' &mdash; Use [`%s`](#%s) instead',
                $supersededBy,
                $anchor,
            );
        }

        return $message . '</small>';
    }

    /**
     * @return list<string>|null
     */
    private function buildExampleSection(PhelFunction $fn): ?array
    {
        if (!isset($fn->meta['example'])) {
            return null;
        }

        return [
            '',
            '**Example:**',
            '',
            '```clojure',
            $fn->meta['example'],
            '```',
        ];
    }

    /**
     * @return list<string>|null
     */
    private function buildSeeAlsoSection(PhelFunction $fn): ?array
    {
        if (!isset($fn->meta['see-also'])) {
            return null;
        }

        $functionNames = $this->extractFunctionNames($fn->meta['see-also']);
        $links = $this->buildFunctionLinks($functionNames);

        return [
            '',
            '**See also:** ' . implode(', ', $links),
        ];
    }

    /**
     * @return list<string>
     */
    private function extractFunctionNames(mixed $seeAlso): array
    {
        return iterator_to_array($seeAlso);
    }

    /**
     * @param list<string> $functionNames
     * @return list<string>
     */
    private function buildFunctionLinks(array $functionNames): array
    {
        return array_map(
            fn(string $func) => sprintf('[`%s`](#%s)', $func, $this->sanitizeAnchor($func)),
            $functionNames,
        );
    }

    private function buildSourceLink(PhelFunction $fn): ?string
    {
        if ($fn->githubUrl !== '') {
            return sprintf('<small>[[View source](%s)]</small>', $fn->githubUrl);
        }

        if ($fn->docUrl !== '') {
            return sprintf('<small>[[Read more](%s)]</small>', $fn->docUrl);
        }

        return null;
    }

    /**
     * Sanitize function name to match Zola's anchor generation.
     * Removes special characters that Zola doesn't include in anchors.
     *
     * Examples:
     *   "empty?" becomes "empty"
     *   "set!" becomes "set"
     *   "php-array-to-map" stays "php-array-to-map"
     */
    private function sanitizeAnchor(string $funcName): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '', $funcName);
    }

    /**
     * @return list<string>
     */
    private function buildZolaHeaders(): array
    {
        return [
            '+++',
            'title = "API"',
            'weight = 110',
            'template = "page-api.html"',
            'aliases = [ "/api" ]',
            '+++',
        ];
    }
}
