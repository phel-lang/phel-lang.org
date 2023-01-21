<?php

declare(strict_types=1);

namespace PhelDocBuild\FileGenerator;

use Gacela\Framework\AbstractConfig;

final class Config extends AbstractConfig
{
    /**
     * @return list<string>
     */
    public function allNamespaces(): array
    {
        return [
            'phel\\http',
            'phel\\html',
            'phel\\test',
            'phel\\json',
        ];
    }
}
