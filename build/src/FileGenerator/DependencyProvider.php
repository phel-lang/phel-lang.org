<?php

declare(strict_types=1);

namespace PhelDocBuild\FileGenerator;

use Gacela\Framework\AbstractDependencyProvider;
use Gacela\Framework\Container\Container;
use Phel\Run\RunFacade;

/**
 * @method Factory getFactory()
 */
final class DependencyProvider extends AbstractDependencyProvider
{
    public const FACADE_PHEL_RUN = 'FACADE_PHEL_RUN';

    public function provideModuleDependencies(Container $container): void
    {
        $container->set(self::FACADE_PHEL_RUN, fn() => new RunFacade());
    }
}