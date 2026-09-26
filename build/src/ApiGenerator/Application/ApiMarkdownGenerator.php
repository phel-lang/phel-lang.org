<?php

declare(strict_types=1);

namespace PhelWeb\ApiGenerator\Application;

use Phel\Shared\Api\PhelFunction;
use Phel\Shared\Facade\ApiFacadeInterface;
use PhelWeb\Shared\Text\EmDash;
use PhelWeb\Shared\Text\ZolaAnchor;

final readonly class ApiMarkdownGenerator
{
    public const string INDEX_KEY = '_index';

    public function __construct(
        private ApiFacadeInterface $apiFacade,
        private ApiNamespaceCatalog $catalog = new ApiNamespaceCatalog(),
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

        foreach ($files as $key => $lines) {
            $files[$key] = array_map(EmDash::strip(...), $lines);
        }

        return $files;
    }

    /**
     * URL path segment for a namespace page. Distinct concept from a heading
     * anchor (see ZolaAnchor), even though the two currently normalise the
     * same way.
     */
    public function namespaceSlug(string $namespace): string
    {
        $slug = strtolower($namespace);
        $slug = str_replace(['\\', '/', '_'], '-', $slug);
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug) ?? $slug;
        $slug = preg_replace('/-+/', '-', $slug) ?? $slug;
        return trim($slug, '-');
    }

    /**
     * The index is one raw HTML block (no blank lines) so markdown leaves the
     * nested cards alone.
     *
     * @param array<string, list<PhelFunction>> $groupedByNamespace
     * @return list<string>
     */
    private function buildIndexFile(array $groupedByNamespace): array
    {
        $counts = array_map(count(...), $groupedByNamespace);

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
            sprintf(
                '%d namespaces and %s functions, grouped by area.',
                count($counts),
                number_format(array_sum($counts)),
            ),
            '',
        ];

        $groups = $this->catalog->group(array_keys($counts));
        if ($groups === []) {
            return $lines;
        }

        $lines[] = '<nav class="api-index-toc" aria-label="API categories">';
        $lines[] = '<ul>';
        foreach (array_keys($groups) as $category) {
            $lines[] = sprintf(
                '<li><a href="#%s">%s</a></li>',
                ZolaAnchor::fromHeading($category),
                htmlspecialchars($category),
            );
        }
        $lines[] = '</ul>';
        $lines[] = '</nav>';

        foreach ($groups as $category => $namespaces) {
            $anchor = ZolaAnchor::fromHeading($category);
            $lines[] = sprintf('<section class="api-index-group" aria-labelledby="%s">', $anchor);
            $lines[] = sprintf('<h2 id="%s">%s</h2>', $anchor, htmlspecialchars($category));
            $lines[] = '<ul class="api-namespace-grid">';
            foreach ($this->nestSubNamespaces($namespaces) as $namespace => $subNamespaces) {
                $lines = array_merge($lines, $this->buildNamespaceCard($namespace, $subNamespaces, $counts));
            }
            $lines[] = '</ul>';
            $lines[] = '</section>';
        }

        $lines[] = '<p class="api-index-json">The full API is also available as JSON at <a href="/api.json"><code>/api.json</code></a>.</p>';
        $lines[] = '';

        return $lines;
    }

    /**
     * Folds `a.b` under `a` when `a` is in the same list, so the index shows
     * one card per family. Orphans keep their own card.
     *
     * @param list<string> $namespaces
     * @return array<string, list<string>>
     */
    private function nestSubNamespaces(array $namespaces): array
    {
        $nested = [];
        foreach ($namespaces as $namespace) {
            $root = explode('.', $namespace, 2)[0];
            if ($root !== $namespace && in_array($root, $namespaces, true)) {
                continue;
            }
            $nested[$namespace] = [];
        }

        foreach ($namespaces as $namespace) {
            $root = explode('.', $namespace, 2)[0];
            if ($root !== $namespace && isset($nested[$root])) {
                $nested[$root][] = $namespace;
            }
        }

        return array_map(static function (array $subs): array {
            sort($subs);
            return $subs;
        }, $nested);
    }

    /**
     * @param list<string> $subNamespaces
     * @param array<string, int> $counts
     * @return list<string>
     */
    private function buildNamespaceCard(string $namespace, array $subNamespaces, array $counts): array
    {
        $description = $this->catalog->descriptionOf($namespace);

        $lines = [
            '<li class="api-ns-card">',
            sprintf(
                '<a class="api-ns-card__link" href="/documentation/reference/api/%s/"><span class="api-ns-card__head"><span class="api-namespace-grid__name">%s</span><span class="api-namespace-grid__count">%d</span></span>%s</a>',
                $this->namespaceSlug($namespace),
                htmlspecialchars($namespace),
                $counts[$namespace],
                $description === null
                    ? ''
                    : sprintf('<span class="api-ns-card__desc">%s</span>', htmlspecialchars($description)),
            ),
        ];

        if ($subNamespaces !== []) {
            $lines[] = sprintf('<ul class="api-ns-card__subs" aria-label="%s sub-namespaces">', htmlspecialchars($namespace));
            foreach ($subNamespaces as $sub) {
                $lines[] = sprintf(
                    '<li><a href="/documentation/reference/api/%s/"><code>%s</code><span class="api-ns-card__sub-count">%d</span></a></li>',
                    $this->namespaceSlug($sub),
                    htmlspecialchars($sub),
                    $counts[$sub],
                );
            }
            $lines[] = '</ul>';
        }

        $lines[] = '</li>';

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
        $description = $this->catalog->descriptionOf($namespace);
        $lines = [
            '+++',
            sprintf('title = "%s"', addslashes($namespace)),
            ...($description === null ? [] : [sprintf('description = "%s"', addslashes($description))]),
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

        // with-mock-wrapper and with-mocks ship indented doc blocks that need
        // de-indenting first.
        $input = $fn->doc;
        if ($fn->name === 'with-mock-wrapper' || $fn->name === 'with-mocks') {
            $input = preg_replace('/^[ \t]+/m', '', $input) ?? $input;
            $input = preg_replace('/(?<!\n)\n(```phel)/', "\n\n$1", $input) ?? $input;
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
            '<small><span class="api-deprecated">Deprecated</span>: %s',
            (string) $fn->meta['deprecated'],
        );

        if (isset($fn->meta['superseded-by'])) {
            $supersededBy = (string) $fn->meta['superseded-by'];
            $href = $this->buildFunctionHref($namespace, $supersededBy, $functionMap);
            $message .= $href === null
                ? sprintf(' &mdash; Use `%s` instead', $supersededBy)
                : sprintf(' &mdash; Use [`%s`](%s) instead', $supersededBy, $href);
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
            '```phel',
            (string) $fn->meta['example'],
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
     * `:see-also` is authored as a Phel vector, so at runtime it arrives as a
     * PersistentVector of function-name strings. Tests feed the equivalent
     * shape with Symbol elements, hence the Stringable half of the union.
     *
     * @param iterable<string|\Stringable> $seeAlso
     *
     * @return list<string>
     */
    private function extractFunctionNames(iterable $seeAlso): array
    {
        $names = [];
        foreach ($seeAlso as $name) {
            $names[] = (string) $name;
        }

        return $names;
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
                return $href === null
                    ? sprintf('<code>%s</code>', htmlspecialchars($func))
                    : sprintf('<a href="%s"><code>%s</code></a>', $href, htmlspecialchars($func));
            },
            $functionNames,
        );
    }

    /**
     * Null when the name matches no documented function: a link to an anchor
     * Zola never emitted is worse than no link.
     *
     * @param array<string, PhelFunction> $functionMap
     */
    private function buildFunctionHref(string $currentNamespace, string $name, array $functionMap): ?string
    {
        $target = $this->resolveFunction($currentNamespace, $name, $functionMap);

        if ($target === null) {
            return null;
        }

        $anchor = ZolaAnchor::fromHeading($target->nameWithNamespace());
        if ($target->namespace === $currentNamespace) {
            return '#' . $anchor;
        }

        return '/documentation/reference/api/' . $this->namespaceSlug($target->namespace) . '/#' . $anchor;
    }

    /**
     * A bare name prefers the referring namespace, then core, then any
     * namespace. A qualified name may carry the full Phel namespace
     * (`phel.schema/validate`, or the older `phel\schema/validate`), while the
     * API keys it the short way (`schema/validate`, core without a prefix).
     *
     * @param array<string, PhelFunction> $functionMap
     */
    private function resolveFunction(string $currentNamespace, string $name, array $functionMap): ?PhelFunction
    {
        $slash = strpos($name, '/');
        if ($slash === false || $slash === 0) {
            return $functionMap[$currentNamespace . '/' . $name] ?? $functionMap[$name] ?? null;
        }

        $namespace = str_replace('\\', '.', substr($name, 0, $slash));
        $namespace = str_starts_with($namespace, 'phel.') ? substr($namespace, 5) : $namespace;
        $bareName = substr($name, $slash + 1);

        return $namespace === 'core'
            ? $functionMap[$bareName] ?? null
            : $functionMap[$namespace . '/' . $bareName] ?? null;
    }
}
