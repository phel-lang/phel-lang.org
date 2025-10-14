<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Gacela\Framework\Gacela;
use Phel\Phel;
use PhelWeb\ReleasesGenerator\Facade as ReleasesGeneratorFacade;

Gacela::bootstrap(__DIR__, Phel::configFn());

$facade = new ReleasesGeneratorFacade();
$facade->generateIndexReleasePage();
$facade->generateIndividualReleasePages();
