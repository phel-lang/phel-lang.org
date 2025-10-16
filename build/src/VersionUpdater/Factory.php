<?php

declare(strict_types=1);

namespace PhelWeb\VersionUpdater;

use Gacela\Framework\AbstractFactory;
use Gacela\Framework\Config\Config;
use Phel\Shared\Facade\ConsoleFacadeInterface;
use PhelWeb\VersionUpdater\Infrastructure\PhelVersionUpdater;

/**
 * @method Config getConfig()
 */
final class Factory extends AbstractFactory
{
    public function createPhelVersionUpdater(): PhelVersionUpdater
    {
        return new PhelVersionUpdater(
            $this->getPhelConsoleFacade(),
            $this->getConfigFileLocation(),
        );
    }

    private function getPhelConsoleFacade(): ConsoleFacadeInterface
    {
        return $this->getProvidedDependency(DependencyProvider::FACADE_PHEL_CONSOLE);
    }

    private function getConfigFileLocation(): string
    {
        return $this->getConfig()->getAppRootDir() . '/../config.toml';
    }
}
