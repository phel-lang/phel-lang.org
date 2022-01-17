<?php

declare(strict_types=1);

namespace PhelDocBuild;

use Phel\Lang\Collections\Map\PersistentMapInterface;
use Phel\Lang\Keyword;
use Phel\Lang\TypeFactory;
use Phel\Run\RunFacade;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;

final class DocFileGenerator
{
    /**
     * @var array<string,array{fnName:string,doc:string}>
     */
    private array $groupNormalizedData;

    public function __construct()
    {
        $this->loadAllPhelNs();
        $normalizedData = $this->normalizeDataFromPhel();
        $this->groupNormalizedData = $this->groupNormalizedData($normalizedData);
    }

    public function renderMdPage(): void
    {
        echo "+++\n";
        echo "title = \"API\"\n";
        echo "weight = 110\n";
        echo "template = \"page-api.html\"\n";
        echo "+++\n\n";

        foreach ($this->groupNormalizedData as $values) {
            foreach ($values as ['fnName' => $fnName, 'doc' => $doc]) {
                echo "## `$fnName`\n\n";
                echo $doc;
                echo "\n\n";
            }
        }
    }

    public function generateApiSearch(): void
    {
        $searchIndex = $this->generateSearchIndex();

        file_put_contents(
            __DIR__ . '/../../static/api_search.js',
            "window.searchIndexApi = " . \json_encode($searchIndex)
        );
    }

    private function loadAllPhelNs(): void
    {
        (new RunFacade())
            ->getRunCommand()
            ->run(
                new ArrayInput(['path' => __DIR__ . '/doc.phel']),
                new ConsoleOutput()
            );
    }

    /**
     * @return array<string,PersistentMapInterface>
     */
    private function normalizeDataFromPhel(): array
    {
        $normalizedData = [];
        foreach ($GLOBALS['__phel'] as $ns => $functions) {
            $normalizedNs = str_replace('phel\\', '', $ns);
            $moduleName = $normalizedNs === 'core' ? '' : $normalizedNs . '/';
            foreach ($functions as $fnName => $fn) {
                $fullFnName = $moduleName . $fnName;

                $normalizedData[$fullFnName] = $GLOBALS['__phel_meta'][$ns][$fnName]
                    ?? TypeFactory::getInstance()->emptyPersistentMap();
            }
        }
        ksort($normalizedData);

        return $normalizedData;
    }

    /**
     * @param array<string,PersistentMapInterface> $normalizedData
     *
     * @return array<string,array{fnName:string,doc:string}>
     */
    private function groupNormalizedData(array $normalizedData): array
    {
        $result = [];
        foreach ($normalizedData as $fnName => $meta) {
            $isPrivate = $meta[Keyword::create('private')] ?? false;
            if ($isPrivate) {
                continue;
            }

            $groupKey = preg_replace(
                '/[^a-zA-Z0-9\-]+/',
                '',
                str_replace('/', '-', $fnName)
            );

            $result[$groupKey][] = [
                'fnName' => $fnName,
                'doc' => $meta[Keyword::create('doc')] ?? '',
            ];
        }

        foreach ($result as $values) {
            usort($values, static fn(array $a, array $b) => $a['fnName'] <=> $b['fnName']);
        }

        return $result;
    }

    /**
     * @return array<string,array{fnName:string,doc:string,anchor:string}>
     */
    private function generateSearchIndex(): array
    {
        $searchIndex = [];
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

        foreach ($this->groupNormalizedData as $groupKey => $values) {
            $groupFnNameAppearances[$groupKey] = 0;

            foreach ($values as ['fnName' => $fnName, 'doc' => $doc]) {
                $specialEndingChars = ['/', '=', '*', '?'];

                if ($groupFnNameAppearances[$groupKey] === 0) {
                    $anchor = $groupKey;
                    $groupFnNameAppearances[$groupKey]++;
                } else {
                    $fnName2 = str_replace($specialEndingChars, '', $fnName);
                    $anchor = $fnName2 . '-' . $groupFnNameAppearances[$groupKey]++;
                }

                $searchIndex[] = [
                    'fnName' => $fnName,
                    'doc' => $doc,
                    'anchor' => $anchor,
                ];
            }
        }

        return $searchIndex;
    }
}