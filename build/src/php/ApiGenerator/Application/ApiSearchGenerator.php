<?php

declare(strict_types=1);

namespace PhelWeb\ApiGenerator\Application;

use Phel\Shared\Facade\ApiFacadeInterface;

final readonly class ApiSearchGenerator
{
    private const array SPECIAL_ENDING_CHARS = ['=', '*', '?', '+', '>', '<', '!'];

    public function __construct(
        private ApiFacadeInterface $apiFacade
    ) {
    }

    /**
     * @return array<string, array{
     *     fnName: string,
     *     fnSignature: string,
     *     desc: string,
     *     anchor: string,
     * }>
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
        $groupFnNameAppearances = [];
        $result = [];
        $groupedPhelFns = $this->apiFacade->getPhelFunctions();

        foreach ($groupedPhelFns as $fn) {
            $groupKey = $fn->groupKey;
            $groupFnNameAppearances[$groupKey] ??= 0;

            if ($groupFnNameAppearances[$groupKey] === 0) {
                $anchor = $groupKey;
                $groupFnNameAppearances[$groupKey]++;
            } else {
                $sanitizedFnName = str_replace(['/', ...self::SPECIAL_ENDING_CHARS], ['-', ''], $fn->name);
                $anchor = rtrim($sanitizedFnName, '-') . '-' . $groupFnNameAppearances[$groupKey]++;
            }

            $result[] = [
                'id' => 'api_' . $fn->name,
                'name' => $fn->nameWithNamespace(),
                'signature' => $fn->signature,
                'desc' => $this->formatDescription($fn->description),
                'anchor' => $anchor,
                'type' => 'api',
            ];
        }

        // Add documentation files to search index
        $documentationItems = $this->generateDocumentationSearchItems();
        $result = array_merge($result, $documentationItems);

        return $result;
    }

    /**
     * Transforms links `[printf](https://...)` into `<i>printf</i>`.
     */
    private function formatDescription(string $desc): string
    {
        return preg_replace('/\[(.*?)\]\((.*?)\)/', '<i>$1</i>', $desc);
    }

    /**
     * Generate search index items for documentation files
     *
     * @return array<array{id: string, title: string, content: string, url: string, type: string}>
     */
    private function generateDocumentationSearchItems(): array
    {
        $result = [];
        $documentationPath = __DIR__ . '/../../../../../content/documentation';
        
        if (!is_dir($documentationPath)) {
            error_log("Documentation path not found: " . $documentationPath);
            return [];
        }

        $files = scandir($documentationPath);
        if ($files === false) {
            error_log("Could not scan documentation directory: " . $documentationPath);
            return [];
        }
        
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) !== 'md' || $file === '_index.md') {
                continue;
            }
            
            $filePath = $documentationPath . '/' . $file;
            $content = file_get_contents($filePath);
            
            // Extract title from frontmatter
            $title = pathinfo($file, PATHINFO_FILENAME);
            if (preg_match('/title = "([^"]+)"/', $content, $matches)) {
                $title = $matches[1];
            }
            
            // Remove frontmatter
            $content = preg_replace('/\+\+\+.*?\+\+\+/s', '', $content);
            
            // Remove markdown formatting and clean content
            $content = preg_replace('/[#`*\[\]()]/', ' ', $content);
            $content = preg_replace('/\s+/', ' ', trim($content));
            
            // Limit content length for search index
            $content = substr($content, 0, 500);
            
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
