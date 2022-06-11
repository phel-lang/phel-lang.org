<?php

declare(strict_types=1);

namespace PhelDocBuild\FileGenerator\Infrastructure;

use PhelDocBuild\FileGenerator\Domain\PhelFnNormalizer;

final class ApiMarkdownFile
{
    private string $appRootDir;

    private PhelFnNormalizer $phelFnNormalizer;

    public function __construct(
        string $appRootDir,
        PhelFnNormalizer $phelFnNormalizer
    ) {
        $this->appRootDir = $appRootDir;
        $this->phelFnNormalizer = $phelFnNormalizer;
    }

    public function generate(): void
    {
        $contentLines = $this->generateMarkdownContentLines();

        $this->createDocumentationApiFile($contentLines);
    }

    /**
     * @return list<string>
     */
    private function generateMarkdownContentLines(): array
    {
        $result = [];
        $result[] = "+++";
        $result[] = "title = \"API\"";
        $result[] = "weight = 110";
        $result[] = "template = \"page-api.html\"";
        $result[] = "+++\n";

        $groupedPhelFns = $this->phelFnNormalizer->getNormalizedGroupedPhelFns();

        foreach ($groupedPhelFns as $values) {
            foreach ($values as ['fnName' => $fnName, 'doc' => $doc]) {
                $result[] = "## `$fnName`";
                $result[] = $doc;
            }
        }

        return $result;
    }

    /**
     * @param list<string> $contentLines
     */
    private function createDocumentationApiFile(array $contentLines): void
    {
        file_put_contents(
            $this->appRootDir . '/../content/documentation/api.md',
            implode(PHP_EOL, $contentLines)
        );
    }
}
