<?php

declare(strict_types=1);

namespace PhelWeb\FileGenerator;

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
