<?php

declare(strict_types=1);

namespace PhelDocBuild\FileGenerator;

use Gacela\Framework\AbstractFacade;

/**
 * @method Factory getFactory()
 */
final class Facade extends AbstractFacade
{
    public function generateMdPage(): void
    {
        $this->getFactory()
            ->createDocFileGenerator()
            ->renderMdPage();
    }

    public function generateApiSearch(): void
    {
        $this->getFactory()
            ->createDocFileGenerator()
            ->generateApiSearch();
    }
}
