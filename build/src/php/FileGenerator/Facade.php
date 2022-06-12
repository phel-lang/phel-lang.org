<?php

declare(strict_types=1);

namespace PhelDocBuild\FileGenerator;

use Gacela\Framework\AbstractFacade;

/**
 * @method Factory getFactory()
 */
final class Facade extends AbstractFacade
{
    public function generateApiMarkdownFile(): void
    {
        $this->getFactory()
            ->createApiMarkdownFile()
            ->generate();
    }

    public function generateApiSearchFile(): void
    {
        $this->getFactory()
            ->createApiSearchFile()
            ->generate();
    }
}
