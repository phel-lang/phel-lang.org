<?php

declare(strict_types=1);

namespace PhelWeb\ApiGenerator\Infrastructure;

use PhelWeb\ApiGenerator\Application\ApiMarkdownGenerator;

final readonly class ApiMarkdownFile
{
    public function __construct(
        private ApiMarkdownGenerator $apiMarkdownGenerator,
        private string $appRootDir,
    ) {
    }

    public function generate(): void
    {
        $contentLines = $this->apiMarkdownGenerator->generate();

        file_put_contents(
            $this->appRootDir . '/../content/documentation/api.md',
            implode(PHP_EOL, $contentLines)
        );
    }
}
