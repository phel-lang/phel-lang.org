<?php

declare(strict_types=1);

use Gacela\Framework\Gacela;
use Phel\Lang\Keyword;
use Phel\Lang\TypeFactory;
use Phel\Run\RunFacade;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;

require __DIR__ . '/../vendor/autoload.php';

Gacela::bootstrap(__DIR__, [
    'config' => [
        'type' => 'php',
        'path' => 'phel-config.php',
        'path_local' => 'phel-config-local.php',
    ],
]);
$rf = new RunFacade();
$rf->getRunCommand()->run(new ArrayInput(['path' => __DIR__ . '/src/doc.phel']), new ConsoleOutput());

echo "+++\n";
echo "title = \"API\"\n";
echo "weight = 110\n";
echo "template = \"page-api.html\"\n";
echo "+++\n\n";

$normalizedData = [];
foreach ($GLOBALS['__phel'] as $ns => $functions) {
    $normalizedNs = str_replace('phel\\', '', $ns);
    $moduleName = $normalizedNs === 'core' ? '' : $normalizedNs . '/';
    foreach ($functions as $fnName => $fn) {
        $fullFnName = $moduleName . $fnName;

        $normalizedData[$fullFnName] = $GLOBALS['__phel_meta'][$ns][$fnName] ?? TypeFactory::getInstance()->emptyPersistentMap();
    }
}

$searchIndex = [];

ksort($normalizedData);
foreach ($normalizedData as $fnName => $meta) {
    $doc = $meta[Keyword::create('doc')] ?? '';
    $isPrivate = $meta[Keyword::create('private')] ?? false;

    if (!$isPrivate) {
        echo "## `$fnName`\n\n";
        echo $doc;
        echo "\n\n";

        $searchIndex[] = [
            'fnName' => $fnName,
            'doc' => $doc,
        ];
    }
}

file_put_contents(__DIR__ . '/../static/api_search.js', "window.searchIndexApi = " . json_encode($searchIndex));