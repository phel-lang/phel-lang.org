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
        $result = array_merge($result, $this->buildJsonEndpointNotice());
        $phelFns = $this->apiFacade->getPhelFunctions();
        $groupedByNamespace = $this->groupFunctionsByNamespace($phelFns);
        $anchorMap = $this->buildAnchorMap($groupedByNamespace);

        $namespaces = [];
        foreach ($groupedByNamespace as $namespace => $functions) {
            $namespaces[] = $this->buildNamespaceSection($namespace, $functions, $anchorMap);
        }

        return array_merge($result, ...$namespaces);
    }

    /**
     * @return list<string>
     */
    private function buildJsonEndpointNotice(): array
    {
        return [
            '',
            '> **Tip:** This documentation is also available in JSON format at [`/api.json`](/api.json).',
            '',
        ];
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
     * Build a map of function names to their Zola-generated anchor IDs.
     *
     * Processes headings in the same order they appear in the markdown to
     * replicate Zola's collision handling (appending -1, -2, etc.).
     * Keys by both short name and qualified name for flexible lookup.
     *
     * @param array<string, list<PhelFunction>> $groupedByNamespace
     * @return array<string, string>
     */
    private function buildAnchorMap(array $groupedByNamespace): array
    {
        $usedSlugs = [];
        $anchorMap = [];

        foreach ($groupedByNamespace as $functions) {
            foreach ($functions as $fn) {
                $slug = $this->zolaSlugify($fn->nameWithNamespace());

                if (isset($usedSlugs[$slug])) {
                    $anchor = $slug . '-' . $usedSlugs[$slug];
                    $usedSlugs[$slug]++;
                } else {
                    $anchor = $slug;
                    $usedSlugs[$slug] = 1;
                }

                $anchorMap[$fn->name] = $anchor;
                $anchorMap[$fn->nameWithNamespace()] = $anchor;
            }
        }

        return $anchorMap;
    }

    /**
     * Replicate Zola's heading slug generation.
     *
     * Zola lowercases, replaces non-alphanumeric (except hyphens) with hyphens,
     * collapses consecutive hyphens, and trims leading/trailing hyphens.
     */
    private function zolaSlugify(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9-]/', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        return trim($text, '-');
    }

    /**
     * @param list<PhelFunction> $functions
     * @param array<string, string> $anchorMap
     * @return list<string>
     */
    private function buildNamespaceSection(string $namespace, array $functions, array $anchorMap): array
    {
        $elements = [];
        foreach ($functions as $fn) {
            $elements[] = $this->buildFunctionSection($fn, $anchorMap);
        }

        return array_merge(['', '---', '', "## `{$namespace}`", ''], ...$elements);
    }

    /**
     * @param array<string, string> $anchorMap
     * @return list<string>
     */
    private function buildFunctionSection(PhelFunction $fn, array $anchorMap): array
    {
        $lines = ["### `{$fn->nameWithNamespace()}`"];

        if ($deprecation = $this->buildDeprecationNotice($fn, $anchorMap)) {
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

        if ($seeAlso = $this->buildSeeAlsoSection($fn, $anchorMap)) {
            $lines = array_merge($lines, $seeAlso);
        }

        if ($sourceLink = $this->buildSourceLink($fn)) {
            $lines[] = $sourceLink;
        }
        $lines[] = '';

        return $lines;
    }

    /**
     * @param array<string, string> $anchorMap
     */
    private function buildDeprecationNotice(PhelFunction $fn, array $anchorMap): ?string
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
            $anchor = $anchorMap[$supersededBy] ?? $this->zolaSlugify($supersededBy);
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
     * @param array<string, string> $anchorMap
     * @return list<string>|null
     */
    private function buildSeeAlsoSection(PhelFunction $fn, array $anchorMap): ?array
    {
        if (!isset($fn->meta['see-also'])) {
            return null;
        }

        $functionNames = $this->extractFunctionNames($fn->meta['see-also']);
        $links = $this->buildFunctionLinks($functionNames, $anchorMap);

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
     * @param array<string, string> $anchorMap
     * @return list<string>
     */
    private function buildFunctionLinks(array $functionNames, array $anchorMap): array
    {
        return array_map(
            function (string $func) use ($anchorMap) {
                $anchor = $anchorMap[$func] ?? $this->zolaSlugify($func);
                return sprintf('[`%s`](#%s)', $func, $anchor);
            },
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
     * @return list<string>
     */
    private function buildZolaHeaders(): array
    {
        return [
            '+++',
            'title = "API"',
            'weight = 110',
            'template = "page-api.html"',
            'aliases = ["/api", "/documentation/api"]',
            '+++',
        ];
    }
}
