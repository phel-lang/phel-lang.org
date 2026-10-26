+++
title = "Framework Integration"
weight = 4
description = "Add Phel to a Symfony, Laravel, or framework-less PHP project without touching app/ or src/"
aliases = ["/documentation/framework-integration"]
+++

Add Phel to an existing Symfony, Laravel, or plain PHP project without changing anything under `app/` or `src/`. Your Phel code lives in its own directory, `phel export` writes typed PHP wrappers, and your controllers call them like any other class. To serve HTTP with Phel itself, see [Request and Response](/documentation/web/http-request-and-response/).

## Setup

1. Keep Phel sources under `phel/`.
2. Mark the functions PHP calls with `{:export true}`.
3. Create one main namespace (`app.main`) that `:require`s every feature namespace. Loading it registers all exported functions at once.
4. Export PHP wrappers under your framework's `App\` PSR-4 root with `phel export`.
5. Load `app.main` once at boot with the [load guard](/documentation/guides/deployment/#loading-phel-prod-vs-dev): the built file in production, `\Phel::run()` in development.

{% <callout kind="warning"> %}
Namespaces need at least two segments (`shop.pricing`, not `pricing`). A single-segment namespace exports invalid PHP.
{% </callout> %}

Install Phel, and let Composer rebuild the wrappers and the compiled code on every install:

```bash
composer require phel-lang/phel-lang
```

```json
"scripts": {
    "post-install-cmd": ["./vendor/bin/phel export", "./vendor/bin/phel build"],
    "post-update-cmd": ["./vendor/bin/phel export", "./vendor/bin/phel build"]
}
```

For scripts and prototypes, `\Phel::getDefinition('app.main', 'greet')` returns a callable without exporting anything.

## The main namespace

```text
phel/
├── app/main.phel          ; main namespace, lists every feature ns
├── shop/pricing.phel
└── reports/daily.phel
```

<!-- phel-test: skip -->
```phel
(ns app.main
  (:require shop.pricing)
  (:require reports.daily))
```

The build follows `:require`s, so `out/app/main.php` loads every dependency. To add a feature, write the `.phel` file, add one `:require` in `app/main.phel`, and rerun `phel export` and `phel build`.

File paths match namespaces (`phel/shop/pricing.phel` is `shop.pricing`), and hyphens become camelCase in PHP: `my-lib.core` maps to `App\PhelGenerated\MyLib\Core`, and `apply-discount` to `applyDiscount`.

## Laravel

`phel-config.php`:

```php
<?php

use Phel\Config\PhelConfig;

return PhelConfig::forProject()
    ->withSrcDirs(['phel'])
    ->withTestDirs(['tests-phel'])
    ->withExportFromDirectories(['phel'])
    ->withExportNamespacePrefix('App\\PhelGenerated')
    ->withExportTargetDirectory(__DIR__ . '/app/PhelGenerated');
```

A service provider runs the load guard once, in `boot()` (never `register()`), with `base_path()` as the root:

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

final class PhelServiceProvider extends ServiceProvider
{
    private static bool $loaded = false;

    public function boot(): void
    {
        if (self::$loaded) {
            return;
        }

        $built = base_path('out/app/main.php');

        if (is_file($built)) {
            require $built;
        } else {
            \Phel::run(base_path(), 'app.main');
        }

        self::$loaded = true;
    }
}
```

Controllers then call the wrappers directly:

```php
use App\PhelGenerated\Shop\Pricing;

$total = Pricing::applyDiscount((float) $request->input('price'), 10.0);
```

## Symfony

Use the same `phel-config.php` with `->withTestDirs(['tests/phel'])` and `->withExportTargetDirectory(__DIR__ . '/src/PhelGenerated')`; the default `App\ → src/` mapping covers the wrappers. Put the same guard in the kernel's `boot()`, after `parent::boot()`, with `$this->getProjectDir()` as the root.

## Plain PHP

Use `PhelConfig::forProject(mainNamespace: 'app.main')->withSrcDirs(['phel'])`, since `forProject` looks in `src/` by default. Put the guard in your entry script after `require __DIR__ . '/vendor/autoload.php'`, with `__DIR__` as the root.

## Typed PHP from Phel definitions

When a framework expects typed PHP, add metadata to your definitions. Forms without it compile as before.

| Metadata | On | Emits |
|---|---|---|
| `^{:tag T}` | struct field, interface param/return | typed signature; `(a b)` = union `a\|b`, `[a b]` = intersection `a&b` |
| `^{:php/attr [...]}` | struct/interface name, field, method, param, exported `defn` | PHP 8 `#[Attr]` |
| `^:php/override` | struct/enum/interface method | `#[\Override]` (PHP 8.3) |
| `:php/const` block | `definterface` | typed class constants (PHP 8.3) |
| `^{:php/doc "..."}` | struct/interface name, field, method | PHPDoc block (phpstan/psalm) |
| `^{:php/json true}` / `^{:php/stringable true}` | struct name | implements `\JsonSerializable` / `\Stringable` |
| `^:php/readonly` | struct name | `readonly` typed properties |

A struct annotated as a Doctrine entity:

<!-- phel-test: skip -->
```phel
(defstruct ^{:php/attr [:ORM/Entity] :php/json true} product
  [^{:tag int :php/attr [:ORM/Id]} id
   ^{:tag string} name])
```

Every struct implements `\Countable`, `\ArrayAccess`, and `\IteratorAggregate`, so PHP code can call `count($s)` and read `$s['name']`. Writing an offset throws, because structs are immutable.

An exported `defn` can carry a route attribute, such as `^{:php/attr [:Symfony.Component.Routing.Attribute/Route "/products/{id}"]}`, so you need no controller class. For backed enums, framework exception types, and converting maps to typed objects (`hydrate`, `bean`), see [PHP Interop](/documentation/language/php-interop/#native-enums-and-exceptions).

## Next steps

- [Deployment](/documentation/guides/deployment/): worker runtimes, what to ship, and caching
- [PHP Interop](/documentation/language/php-interop/): call PHP and build objects
- For a database, [phel-sql](https://github.com/phel-lang/phel-sql) builds queries from maps and [phel-pdo](https://github.com/phel-lang/phel-pdo) returns rows as maps
