<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Phel\Phel;
use PhelWeb\VersionUpdater\Facade as VersionUpdaterFacade;

Phel::bootstrap(__DIR__);

$facade = new VersionUpdaterFacade();
$facade->updateTomlFile();
