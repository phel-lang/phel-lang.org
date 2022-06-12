<?php

declare(strict_types=1);

namespace PhelDocBuild\FileGenerator\Infrastructure;

use PhelDocBuild\FileGenerator\Domain\ApiMarkdownGenerator;

final class ApiMarkdownFile
{
    private ApiMarkdownGenerator $apiMarkdownGenerator;

    private string $appRootDir;

    public function __construct(
        ApiMarkdownGenerator $apiMarkdownGenerator,
        string $appRootDir,
    ) {
        $this->apiMarkdownGenerator = $apiMarkdownGenerator;
        $this->appRootDir = $appRootDir;
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
