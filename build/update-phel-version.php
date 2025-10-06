<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Gacela\Framework\Gacela;
use Phel\Phel;
use PhelWeb\VersionUpdater\Facade as VersionUpdaterFacade;

Gacela::bootstrap(__DIR__, Phel::configFn());

$facade = new VersionUpdaterFacade();
$facade->updateTomlFile();
