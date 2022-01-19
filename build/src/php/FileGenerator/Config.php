<?php

declare(strict_types=1);

namespace PhelDocBuild\FileGenerator;

use Gacela\Framework\AbstractConfig;

final class Config extends AbstractConfig
{
    public function getSrcDir(): string
    {
        return __DIR__ . '/../..';
    }
}
