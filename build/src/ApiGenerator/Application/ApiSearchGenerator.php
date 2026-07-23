<?php

declare(strict_types=1);

namespace PhelWeb\ApiGenerator\Application;

use Phel\Shared\Facade\ApiFacadeInterface;
use PhelWeb\Shared\Text\ZolaAnchor;
use RuntimeException;

/**
 * Builds static/api_search.json, consumed by the client-side search in
 * static/search.js.
 *
 * The index holds two deliberately different kinds of entry. They share only
 * `id` and `type` (the discriminator the frontend switches on) and are NOT
 * interchangeable: an API entry describes one Phel function and carries its
 * signatures, a documentation entry describes a whole markdown page and
 * carries a prose excerpt. They are kept as separate shapes on purpose.
 *
 * @psalm-type TApiSearchItem = array{
 *     id: string,
 *     name: string,
 *     signatures: list<string>,
 *     desc: string,
 *     anchor: string,
 *     namespace: string,
 *     path: string,
 *     type: 'api',
 * }
 * @psalm-type TDocSearchItem = array{
 *     id: string,
 *     title: string,
 *     content: string,
 *     url: string,
 *     type: 'documentation',
 * }
 */
final readonly class ApiSearchGenerator
{
    public function __construct(
        private ApiFacadeInterface $apiFacade,
        private ApiMarkdownGenerator $apiMarkdownGenerator,
    ) {
    }

    /**
     * @return list<TApiSearchItem|TDocSearchItem>
     */
    public function generateSearchIndex(): array
    {
        /**
         * Zola ignores the especial chars, and uses instead a number. This variable keep track
         * of the appearances and uses an autoincrement number to follow the proper link.
         *
         * For example, consider the two functions: `table` and `table?`
         * They belong to the same group `table`, and their anchor will be such as:
         * table  -> table
         * table? -> table-1
         */
        $anchorAppearances = [];
        $result = [];
        $groupedPhelFns = $this->apiFacade->getPhelFunctions();

        foreach ($groupedPhelFns as $fn) {
            $base = ZolaAnchor::fromHeading($fn->nameWithNamespace());
            $anchorAppearances[$base] ??= 0;
            $count = $anchorAppearances[$base]++;
            $anchor = $count === 0 ? $base : $base . '-' . $count;

            $namespaceSlug = $this->apiMarkdownGenerator->namespaceSlug($fn->namespace);
            $path = $namespaceSlug !== ''
                ? sprintf('/documentation/reference/api/%s/#%s', $namespaceSlug, $anchor)
                : '#' . $anchor;

            $result[] = [
                'id' => 'api_' . $fn->name,
                'name' => $fn->nameWithNamespace(),
                'signatures' => $fn->signatures,
                'desc' => $this->formatDescription($fn->description),
                'anchor' => $anchor,
                'namespace' => $fn->namespace,
                'path' => $path,
                'type' => 'api',
            ];
        }

        $documentationItems = $this->generateDocumentationSearchItems();

        return array_merge($result, $documentationItems);
    }

    /**
     * Transforms links `[printf](https://...)` into `<i>printf</i>`.
     */
    private function formatDescription(string $desc): string
    {
        return preg_replace('/\[(.*?)\]\((.*?)\)/', '<i>$1</i>', $desc) ?? $desc;
    }

    /**
     * Only the top-level content/documentation/*.md pages are indexed here;
     * nested sections come from Zola's own search_index.en.js.
     *
     * @return list<TDocSearchItem>
     */
    private function generateDocumentationSearchItems(): array
    {
        $result = [];
        $documentationPath = __DIR__ . '/../../../../content/documentation';

        // A missing/unreadable directory here used to be logged and skipped, which
        // shipped a search index silently missing every documentation entry.
        $files = is_dir($documentationPath) ? scandir($documentationPath) : false;
        if ($files === false) {
            throw new RuntimeException(
                "Cannot read the documentation directory: {$documentationPath}. "
                . 'Refusing to write a search index without the documentation entries.',
            );
        }

        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) !== 'md' || $file === '_index.md') {
                continue;
            }

            $filePath = $documentationPath . '/' . $file;
            $content = (string) file_get_contents($filePath);

            // Extract title from frontmatter
            $title = pathinfo($file, PATHINFO_FILENAME);
            if (preg_match('/title = "([^"]+)"/', $content, $matches)) {
                $title = $matches[1];
            }

            // Remove frontmatter
            $content = preg_replace('/\+\+\+.*?\+\+\+/s', '', $content) ?? $content;

            // Extract code blocks content (preserves important terms like SrcDirs, :pairs, etc.)
            preg_match_all('/```\w*\n?([\s\S]*?)```/', $content, $codeBlocks);
            $codeContent = '';
            if (!empty($codeBlocks[1])) {
                $codeContent = implode(' ', $codeBlocks[1]);
                // Clean code content: remove extra whitespace but preserve all characters
                $codeContent = preg_replace('/\s+/', ' ', trim($codeContent)) ?? trim($codeContent);
            }

            // Remove code blocks from main content
            $content = preg_replace('/```[\s\S]*?```/', ' ', $content) ?? $content;

            // Remove markdown formatting but preserve colons (:) for keywords like :pairs, :keys
            // Remove: # (headers), ` (backticks), * (bold/italic), [] (links), () (links)
            $content = preg_replace('/[#`*\[\]()]/', ' ', $content) ?? $content;

            // Clean up whitespace
            $content = preg_replace('/\s+/', ' ', trim($content)) ?? trim($content);

            // Combine code content with main content (code first for better matching)
            $content = trim($codeContent . ' ' . $content);

            $content = substr($content, 0, 200);

            $result[] = [
                'id' => 'doc_' . pathinfo($file, PATHINFO_FILENAME),
                'title' => $title,
                'content' => $content,
                'url' => '/documentation/' . pathinfo($file, PATHINFO_FILENAME),
                'type' => 'documentation',
            ];
        }

        return $result;
    }
}
