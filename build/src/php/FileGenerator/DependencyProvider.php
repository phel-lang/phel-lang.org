<?php

declare(strict_types=1);

namespace PhelDocBuild\FileGenerator;

use Gacela\Framework\AbstractDependencyProvider;
use Gacela\Framework\Container\Container;
use PhelNormalizedInternal\PhelNormalizedInternalFacade;

/**
 * @method Factory getFactory()
 */
final class DependencyProvider extends AbstractDependencyProvider
{
    public const FACADE_PHEL_NORMALIZED_INTERNAL = 'FACADE_PHEL_NORMALIZED_INTERNAL';

    public function provideModuleDependencies(Container $container): void
    {
        $container->set(self::FACADE_PHEL_NORMALIZED_INTERNAL, function (Container $container) {
            return $container->getLocator()->get(PhelNormalizedInternalFacade::class);
        });
    }
}
