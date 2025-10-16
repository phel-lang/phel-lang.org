<?php

declare(strict_types=1);

namespace PhelWeb\VersionUpdater;

use Gacela\Framework\AbstractProvider;
use Gacela\Framework\Container\Container;
use Phel\Console\ConsoleFacade;

/**
 * @method Factory getFactory()
 */
final class DependencyProvider extends AbstractProvider
{
    public const string FACADE_PHEL_CONSOLE = 'FACADE_PHEL_CONSOLE';

    public function provideModuleDependencies(Container $container): void
    {
        $container->set(self::FACADE_PHEL_CONSOLE, function (Container $container) {
            return $container->getLocator()->get(ConsoleFacade::class);
        });
    }
}
