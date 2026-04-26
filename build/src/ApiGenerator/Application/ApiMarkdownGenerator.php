<?php

declare(strict_types=1);

namespace PhelWeb\ApiGenerator\Application;

use Phel\Api\Transfer\PhelFunction;
use Phel\Shared\Facade\ApiFacadeInterface;

final readonly class ApiMarkdownGenerator
{
    public const string INDEX_KEY = '_index';

    public function __construct(
        private ApiFacadeInterface $apiFacade,
    ) {
    }

    /**
     * Returns one entry per output markdown file.
     *
     * Keys:
     *   "_index"     -> lines for content/documentation/reference/api/_index.md
     *   "<namespace>" -> lines for content/documentation/reference/api/<slug>.md
     *
     * @return array<string, list<string>>
     */
    public function generate(): array
    {
        $phelFns = $this->apiFacade->getPhelFunctions();
        $groupedByNamespace = $this->groupFunctionsByNamespace($phelFns);
        $functionMap = $this->buildFunctionMap($phelFns);

        $files = [];
        $files[self::INDEX_KEY] = $this->buildIndexFile($groupedByNamespace);

        foreach ($groupedByNamespace as $namespace => $functions) {
            $files[$namespace] = $this->buildNamespaceFile($namespace, $functions, $functionMap);
        }

        return $files;
    }

    public function namespaceSlug(string $namespace): string
    {
        $slug = strtolower($namespace);
        $slug = str_replace(['\\', '/', '_'], '-', $slug);
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug) ?? '';
        $slug = preg_replace('/-+/', '-', $slug) ?? '';
        return trim($slug, '-');
    }

    /**
     * @param array<string, list<PhelFunction>> $groupedByNamespace
     * @return list<string>
     */
    private function buildIndexFile(array $groupedByNamespace): array
    {
        $lines = [
            '+++',
            'title = "API"',
            'description = "Browse all built-in Phel namespaces and functions."',
            'weight = 110',
            'template = "page-api-index.html"',
            'sort_by = "title"',
            'insert_anchor_links = "right"',
            'aliases = ["/api", "/documentation/api"]',
            '+++',
            '',
            '> **Tip:** This documentation is also available in JSON format at [`/api.json`](/api.json).',
            '',
            'Browse the API by namespace:',
            '',
        ];

        ksort($groupedByNamespace);
        $lines[] = '<ul class="api-namespace-grid">';
        foreach ($groupedByNamespace as $namespace => $functions) {
            $slug = $this->namespaceSlug($namespace);
            $count = count($functions);
            $lines[] = sprintf(
                '<li><a href="/documentation/reference/api/%s/"><span class="api-namespace-grid__name">%s</span><span class="api-namespace-grid__count">%d</span></a></li>',
                $slug,
                htmlspecialchars($namespace),
                $count,
            );
        }
        $lines[] = '</ul>';
        $lines[] = '';

        return $lines;
    }

    /**
     * @param list<PhelFunction> $functions
     * @param array<string, PhelFunction> $functionMap
     * @return list<string>
     */
    private function buildNamespaceFile(string $namespace, array $functions, array $functionMap): array
    {
        $count = count($functions);
        $lines = [
            '+++',
            sprintf('title = "%s"', addslashes($namespace)),
            'template = "page-api-namespace.html"',
            '',
            '[extra]',
            sprintf('fn_count = %d', $count),
            sprintf('namespace = "%s"', addslashes($namespace)),
            '+++',
            '',
        ];

        foreach ($functions as $fn) {
            $lines = array_merge($lines, $this->buildFunctionSection($namespace, $fn, $functionMap));
        }

        return $lines;
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
            // Qualified key takes precedence; bare-name key is a best-effort fallback.
            $map[$fn->nameWithNamespace()] = $fn;
            if (!isset($map[$fn->name])) {
                $map[$fn->name] = $fn;
            }
        }
        return $map;
    }

    /**
     * @param array<string, PhelFunction> $functionMap
     * @return list<string>
     */
    private function buildFunctionSection(string $namespace, PhelFunction $fn, array $functionMap): array
    {
        $lines = ["### `{$fn->nameWithNamespace()}`"];

        if ($deprecation = $this->buildDeprecationNotice($namespace, $fn, $functionMap)) {
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

        if ($footer = $this->buildFooterSection($namespace, $fn, $functionMap)) {
            $lines = array_merge($lines, $footer);
        }
        $lines[] = '';

        return $lines;
    }

    /**
     * @param array<string, PhelFunction> $functionMap
     */
    private function buildDeprecationNotice(string $namespace, PhelFunction $fn, array $functionMap): ?string
    {
        if (!isset($fn->meta['deprecated'])) {
            return null;
        }

        $message = sprintf(
            '<small><span style="color: red; font-weight: bold;">Deprecated</span>: %s',
            $fn->meta['deprecated'],
        );

        if (isset($fn->meta['superseded-by'])) {
            $supersededBy = (string) $fn->meta['superseded-by'];
            $href = $this->buildFunctionHref($namespace, $supersededBy, $functionMap);
            $message .= sprintf(
                ' &mdash; Use [`%s`](%s) instead',
                $supersededBy,
                $href,
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
    private function buildFooterSection(string $namespace, PhelFunction $fn, array $functionMap): ?array
    {
        $hasSeeAlso = isset($fn->meta['see-also']);
        $hasSource = $fn->githubUrl !== '' || $fn->docUrl !== '';

        if (!$hasSeeAlso && !$hasSource) {
            return null;
        }

        $lines = ['', '<div class="api-footer">'];

        if ($hasSeeAlso) {
            $functionNames = $this->extractFunctionNames($fn->meta['see-also']);
            $links = $this->buildFunctionLinks($namespace, $functionNames, $functionMap);
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
     * @param array<string, PhelFunction> $functionMap
     * @return list<string>
     */
    private function buildFunctionLinks(string $currentNamespace, array $functionNames, array $functionMap): array
    {
        return array_map(
            function (string $func) use ($currentNamespace, $functionMap) {
                $href = $this->buildFunctionHref($currentNamespace, $func, $functionMap);
                return sprintf('<a href="%s"><code>%s</code></a>', $href, htmlspecialchars($func));
            },
            $functionNames,
        );
    }

    /**
     * @param array<string, PhelFunction> $functionMap
     */
    private function buildFunctionHref(string $currentNamespace, string $name, array $functionMap): string
    {
        $target = $functionMap[$name] ?? null;

        if ($target === null) {
            return '#' . $this->sanitizeAnchor($name);
        }

        $anchor = $this->sanitizeAnchor($target->nameWithNamespace());
        if ($target->namespace === $currentNamespace) {
            return '#' . $anchor;
        }

        return '/documentation/reference/api/' . $this->namespaceSlug($target->namespace) . '/#' . $anchor;
    }

    private function sanitizeAnchor(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9-]/', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        return trim($text, '-');
    }
}
