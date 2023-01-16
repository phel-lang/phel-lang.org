<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Gacela\Framework\Bootstrap\GacelaConfig;
use Gacela\Framework\Gacela;
use PhelDocBuild\FileGenerator\Facade;

Gacela::bootstrap(__DIR__, static function (GacelaConfig $config): void {
    $config->addAppConfig('phel-config.php', 'phel-config-local.php');
});

$fileGeneratorFacade = new Facade();
$fileGeneratorFacade->generateApiMarkdownFile();
