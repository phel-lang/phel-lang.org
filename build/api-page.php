<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Gacela\Framework\Gacela;
use Phel\Phel;
use PhelWeb\ApiGenerator\Facade as ApiGeneratorFacade;

Gacela::bootstrap(__DIR__, Phel::configFn());

$facade = new ApiGeneratorFacade();
$facade->generateApiMarkdownFile();
