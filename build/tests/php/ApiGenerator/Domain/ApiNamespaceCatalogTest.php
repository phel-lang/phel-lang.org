<?php

declare(strict_types=1);

namespace PhelWebTests\FileGenerator\Domain;

use PHPUnit\Framework\TestCase;
use PhelWeb\ApiGenerator\Application\ApiNamespaceCatalog;

final class ApiNamespaceCatalogTest extends TestCase
{
    public function test_curated_namespace_has_its_category(): void
    {
        self::assertSame('Web', (new ApiNamespaceCatalog())->categoryOf('http_client'));
    }

    public function test_sub_namespace_inherits_root_category(): void
    {
        self::assertSame('Schema', (new ApiNamespaceCatalog())->categoryOf('schema.brand-new'));
    }

    public function test_unknown_namespace_falls_back_to_other(): void
    {
        self::assertSame(ApiNamespaceCatalog::OTHER, (new ApiNamespaceCatalog())->categoryOf('brand-new'));
    }

    public function test_unknown_namespace_has_no_description(): void
    {
        self::assertNull((new ApiNamespaceCatalog())->descriptionOf('brand-new'));
    }

    public function test_group_orders_categories_and_members(): void
    {
        $grouped = (new ApiNamespaceCatalog())->group(['zeta', 'string', 'alpha', 'core', 'json']);

        self::assertSame(
            [
                'Core language' => ['core', 'string'],
                'Data formats' => ['json'],
                ApiNamespaceCatalog::OTHER => ['alpha', 'zeta'],
            ],
            $grouped,
        );
    }

    public function test_group_puts_uncurated_sub_namespaces_after_curated_ones(): void
    {
        $grouped = (new ApiNamespaceCatalog())->group(['test.gen', 'bench', 'test']);

        self::assertSame(['Testing' => ['test', 'bench', 'test.gen']], $grouped);
    }
}
