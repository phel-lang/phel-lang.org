<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Gacela\Framework\Gacela;
use PhelDocBuild\FileGenerator\Facade;

Gacela::bootstrap(__DIR__, [
    'config' => [
        'type' => 'php',
        'path' => 'phel-config.php',
        'path_local' => 'phel-config-local.php',
    ],
]);

$fileGeneratorFacade = new Facade();
$fileGeneratorFacade->generateMdPage();
$fileGeneratorFacade->generateApiSearch();
