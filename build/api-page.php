<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use PhelDocBuild\DocFileGenerator;
use Gacela\Framework\Gacela;

Gacela::bootstrap(__DIR__, [
    'config' => [
        'type' => 'php',
        'path' => 'phel-config.php',
        'path_local' => 'phel-config-local.php',
    ],
]);

$docFileGenerator = new DocFileGenerator();
$docFileGenerator->renderMdPage();
$docFileGenerator->generateApiSearch();
