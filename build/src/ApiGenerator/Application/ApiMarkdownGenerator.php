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
        $functionMap = $this->buildFunctionMap($phelFns);

        foreach ($groupedByNamespace as $namespace => $functions) {
            $result = array_merge($result, $this->buildNamespaceSection($namespace, $functions, $functionMap));
        }

        return $result;
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
     * @param list<PhelFunction> $phelFns
     * @return array<string, PhelFunction>
     */
    private function buildFunctionMap(array $phelFns): array
    {
        $map = [];
        foreach ($phelFns as $fn) {
            $map[$fn->nameWithNamespace()] = $fn;
        }
        return $map;
    }

    /**
     * @param list<PhelFunction> $functions
     * @param array<string, PhelFunction> $functionMap
     * @return list<string>
     */
    private function buildNamespaceSection(string $namespace, array $functions, array $functionMap): array
    {
        $lines = ['', '---', '', "## `{$namespace}`", ''];

        foreach ($functions as $fn) {
            $lines = array_merge($lines, $this->buildFunctionSection($fn, $functionMap));
        }

        return $lines;
    }

    /**
     * @param array<string, PhelFunction> $functionMap
     * @return list<string>
     */
    private function buildFunctionSection(PhelFunction $fn, array $functionMap): array
    {
        $lines = ["### `{$fn->nameWithNamespace()}`"];

        if ($deprecation = $this->buildDeprecationNotice($fn, $functionMap)) {
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

        if ($footer = $this->buildFooterSection($fn, $functionMap)) {
            $lines = array_merge($lines, $footer);
        }
        $lines[] = '';

        return $lines;
    }

    /**
     * @param array<string, PhelFunction> $functionMap
     */
    private function buildDeprecationNotice(PhelFunction $fn, array $functionMap): ?string
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
     * @param array<string, PhelFunction> $functionMap
     * @return list<string>|null
     */
    private function buildFooterSection(PhelFunction $fn, array $functionMap): ?array
    {
        $hasSeeAlso = isset($fn->meta['see-also']);
        $hasSource = $fn->githubUrl !== '' || $fn->docUrl !== '';

        if (!$hasSeeAlso && !$hasSource) {
            return null;
        }

        $lines = ['', '<div class="api-footer">'];

        if ($hasSeeAlso) {
            $functionNames = $this->extractFunctionNames($fn->meta['see-also']);
            $links = $this->buildFunctionLinks($functionNames);
            $lines[] = '<div><strong>See also:</strong> ' . implode(', ', $links) . '</div>';
        }

        if ($hasSource) {
            if ($fn->githubUrl !== '') {
                $lines[] = '<div><a href="' . $fn->githubUrl . '">View source</a></div>';
            } elseif ($fn->docUrl !== '') {
                $lines[] = '<div><a href="' . $fn->docUrl . '">Read more</a></div>';
            }
        }

        $lines[] = '</div>';
        $lines[] = '';

        return $lines;
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
            function (string $func) {
                return sprintf('[`%s`](#%s)', $func, $this->sanitizeAnchor($func));
            },
            $functionNames,
        );
    }

    private function sanitizeAnchor(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9-]/', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        return trim($text, '-');
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
