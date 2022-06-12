<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Gacela\Framework\Config\GacelaConfigBuilder\ConfigBuilder;
use Gacela\Framework\Gacela;
use Gacela\Framework\Setup\SetupGacela;
use PhelDocBuild\FileGenerator\Facade;

$setupGacela = (new SetupGacela())
    ->setConfig(static function (ConfigBuilder $configBuilder): void {
        $configBuilder->add('phel-config.php', 'phel-config-local.php');
    });

Gacela::bootstrap(__DIR__, $setupGacela);

$fileGeneratorFacade = new Facade();
$fileGeneratorFacade->generateApiSearchFile();
