<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Phel\Phel;
use PhelWeb\ApiGenerator\Facade as ApiGeneratorFacade;

Phel::bootstrap(__DIR__);

$facade = new ApiGeneratorFacade();
$facade->generateApiMarkdownFile();
