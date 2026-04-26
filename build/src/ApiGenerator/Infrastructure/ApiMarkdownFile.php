<?php

declare(strict_types=1);

namespace PhelWeb\ApiGenerator\Infrastructure;

use FilesystemIterator;
use PhelWeb\ApiGenerator\Application\ApiMarkdownGenerator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final readonly class ApiMarkdownFile
{
    public function __construct(
        private ApiMarkdownGenerator $apiMarkdownGenerator,
        private string $appRootDir,
    ) {
    }

    public function generate(): void
    {
        $files = $this->apiMarkdownGenerator->generate();

        $apiDir = $this->appRootDir . '/../content/documentation/reference/api';
        $legacyFile = $this->appRootDir . '/../content/documentation/reference/api.md';

        if (file_exists($legacyFile)) {
            unlink($legacyFile);
        }

        $this->resetDirectory($apiDir);

        foreach ($files as $key => $lines) {
            $filename = $key === ApiMarkdownGenerator::INDEX_KEY
                ? '_index.md'
                : $this->apiMarkdownGenerator->namespaceSlug($key) . '.md';

            file_put_contents(
                $apiDir . '/' . $filename,
                implode(PHP_EOL, $lines),
            );
        }
    }

    private function resetDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new \RuntimeException(sprintf('Failed to create directory: %s', $dir));
            }
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $entry) {
            if ($entry->isDir()) {
                rmdir($entry->getPathname());
            } else {
                unlink($entry->getPathname());
            }
        }
    }
}
