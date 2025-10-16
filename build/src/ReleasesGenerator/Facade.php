<?php

declare(strict_types=1);

namespace PhelWeb\ReleasesGenerator;

use Gacela\Framework\AbstractFacade;

/**
 * @method Factory getFactory()
 */
final class Facade extends AbstractFacade
{
    public function generateIndividualReleasePages(): void
    {
        $this
            ->getFactory()
            ->createGitHubReleasesGenerator()
            ->generate();
    }
}
