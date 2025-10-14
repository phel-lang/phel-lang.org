<?php

declare(strict_types=1);

namespace PhelWeb\ReleasesGenerator;

use Gacela\Framework\AbstractFacade;

/**
 * @method Factory getFactory()
 */
final class Facade extends AbstractFacade
{
    public function generateIndexReleasePage(): void
    {
        $this
            ->getFactory()
            ->createIndexReleasePage()
            ->generate();
    }

    public function generateIndividualReleasePages(): void
    {
        $this
            ->getFactory()
            ->createGitHubReleasesGenerator()
            ->generate();
    }
}
