<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Phel\Phel;
use PhelWeb\ReleasesGenerator\Facade as ReleasesGeneratorFacade;

Phel::bootstrap(__DIR__);

$facade = new ReleasesGeneratorFacade();
$facade->generateIndividualReleasePages();
