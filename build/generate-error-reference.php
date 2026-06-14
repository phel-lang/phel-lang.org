<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use PhelWeb\ErrorReference\ErrorReferenceGenerator;

$outFile = dirname(__DIR__) . '/content/documentation/reference/errors.md';

$generator = new ErrorReferenceGenerator($outFile);
$generator->generate();

echo "Wrote error reference to content/documentation/reference/errors.md\n";
