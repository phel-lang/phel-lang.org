<?php

declare(strict_types=1);

namespace PhelWeb\VersionUpdater;

use Gacela\Framework\AbstractFacade;

/**
 * @method Factory getFactory()
 */
final class Facade extends AbstractFacade
{
    public function updateTomlFile(): void
    {
        $this->getFactory()
            ->createPhelVersionUpdater()
            ->update();
    }
}
