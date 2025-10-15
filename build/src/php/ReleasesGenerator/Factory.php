<?php

declare(strict_types=1);

namespace PhelWeb\ReleasesGenerator;

use Gacela\Framework\AbstractFactory;
use Gacela\Framework\Config\Config;
use PhelWeb\ReleasesGenerator\Application\GitHubReleasePagesGenerator;
use PhelWeb\ReleasesGenerator\Infrastructure\GitHubReleasePages;

/**
 * @method Config getConfig()
 */
final class Factory extends AbstractFactory
{
    public function createGitHubReleasesGenerator(): GitHubReleasePages
    {
        return new GitHubReleasePages(
            new GitHubReleasePagesGenerator(),
            $this->getReleasesDirectoryLocation(),
        );
    }

    private function getReleasesDirectoryLocation(): string
    {
        return $this->getConfig()->getAppRootDir() . '/../content/releases';
    }
}
