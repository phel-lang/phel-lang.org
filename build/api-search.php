<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Gacela\Framework\Gacela;
use Phel\Phel;
use PhelWeb\FileGenerator\Facade as FileGeneratorFacade;

Gacela::bootstrap(__DIR__, Phel::configFn());

$facade = new FileGeneratorFacade();
$facade->generateApiSearchFile();
