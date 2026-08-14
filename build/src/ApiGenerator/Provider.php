<?php

declare(strict_types=1);

namespace PhelWeb\ApiGenerator;

use Gacela\Framework\AbstractProvider;
use Gacela\Framework\Container\Container;
use Phel\Api\ApiFacade;

final class Provider extends AbstractProvider
{
    public const string FACADE_PHEL_API = 'FACADE_PHEL_API';

    public function provideModuleDependencies(Container $container): void
    {
        $container->set(self::FACADE_PHEL_API, function (Container $container) {
            return $container->getLocator()->get(ApiFacade::class);
        });
    }
}
