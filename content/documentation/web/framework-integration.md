+++
title = "Framework Integration"
weight = 4
description = "Add Phel to a Symfony, Laravel, or framework-less PHP project without touching app/ or src/, and keep data as maps instead of ORM entities"
aliases = ["/documentation/framework-integration"]
+++

After this page you can add Phel to an existing Symfony, Laravel, or plain PHP project without changing anything under `app/` or `src/`. Your Phel code lives in its own directory, you export typed PHP wrappers, and your controllers call them like any other class.

To serve HTTP with Phel itself instead, see [Request and Response](/documentation/web/http-request-and-response/) and [Routing](/documentation/web/routing/).

{% php_note() %}
Phel installs as a Composer package and compiles to plain PHP. Your framework never knows it is calling Lisp: it sees ordinary classes and methods.
{% end %}

## Core idea

1. Keep Phel sources under `phel/`.
2. Mark public functions with `{:export true}`.
3. Create one main namespace (`app.main`) that `:require`s every feature namespace. Loading it registers all exported functions at once.
4. Export PHP wrappers under your framework's `App\` PSR-4 root via `phel export`.
5. In production, run `phel build` at deploy and `require 'build/app/main.php'` at boot; in development, `\Phel::run($root, 'app.main')` compiles on first call. One [load guard](/documentation/guides/deployment/#loading-phel-prod-vs-dev) picks the right path.

{% callout(kind="warning") %}
Namespaces need at least two segments (`shop.pricing`, not `pricing`). A single-segment namespace exports invalid PHP.
{% end %}

There are two ways to call Phel from PHP:

| Flavor | How | When |
|--------|-----|------|
| Exported wrappers | `{:export true}` + `vendor/bin/phel export` to a typed PHP class | Production, IDE autocomplete |
| Dynamic lookup | `\Phel::getDefinition($ns, $name)(...)` | Scripts, prototyping |

And two load modes, behind the same provider/kernel hook:

| Mode | What | Per-request cost |
|------|------|------------------|
| Prod (AOT) | `require 'build/app/main.php'`, precompiled | Zero compile, one `require` |
| Dev (JIT) | `\Phel::run($root, 'app.main')` | Gacela bootstrap + compile on first call |

Install Phel, then let Composer build the wrappers and the compiled code on every install:

```bash
composer require phel-lang/phel-lang
```


```json
"scripts": {
    "post-install-cmd": ["./vendor/bin/phel export", "./vendor/bin/phel build"],
    "post-update-cmd": ["./vendor/bin/phel export", "./vendor/bin/phel build"]
}
```

## The main namespace pattern

Layout:

```text
phel/
├── app/main.phel          ; main namespace, lists every feature ns
├── shop/pricing.phel
├── reports/daily.phel
└── auth/tokens.phel
```

`phel/app/main.phel` requires every feature namespace:

<!-- phel-test: skip -->
```phel
(ns app.main
  (:require shop.pricing)
  (:require reports.daily)
  (:require auth.tokens))
```

Loading `app.main` (via `require` or `\Phel::run()`) registers every exported function across all three namespaces. The build walks `:require`s transitively, so `build/app/main.php` `require_once`s every dependency. Any controller can then call any wrapper:

```php
App\PhelGenerated\Shop\Pricing::applyDiscount(...)
App\PhelGenerated\Reports\Daily::summary(...)
App\PhelGenerated\Auth\Tokens::makeToken(...)
```

To add a feature: write the `.phel` file, add one `:require` in `app/main.phel`, and rerun `phel export` + `phel build`.

## Laravel

`phel-config.php`:

```php
<?php

use Phel\Config\PhelConfig;

return PhelConfig::forProject()
    ->withSrcDirs(['phel'])
    ->withTestDirs(['tests-phel'])
    ->withBuildDestDir('build')
    ->withExportFromDirectories(['phel'])
    ->withExportNamespacePrefix('App\\PhelGenerated')
    ->withExportTargetDirectory(__DIR__ . '/app/PhelGenerated');
```

A service provider runs the [load guard](/documentation/guides/deployment/#loading-phel-prod-vs-dev) once, with Laravel's `base_path()` as the root, so all wrappers are ready:

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

        $built = base_path('build/app/main.php');

        if (is_file($built)) {
            require $built;
        } else {
            \Phel::run(base_path(), 'app.main');
        }

        self::$loaded = true;
    }
}
```

A controller calls the wrappers directly:

```php
use App\PhelGenerated\Shop\Pricing;
use App\PhelGenerated\Reports\Daily;

final class CheckoutController
{
    public function __invoke(Request $request): JsonResponse
    {
        $total = Pricing::applyDiscount(
            (float) $request->input('price'),
            (float) $request->input('percent'),
        );

        return response()->json([
            'total' => $total,
            'report' => Daily::summary((int) $request->input('day')),
        ]);
    }
}
```

## Symfony

`phel-config.php` (only the test and export directories differ from Laravel):

```php
<?php

use Phel\Config\PhelConfig;

return PhelConfig::forProject()
    ->withSrcDirs(['phel'])
    ->withTestDirs(['tests/phel'])
    ->withBuildDestDir('build')
    ->withExportFromDirectories(['phel'])
    ->withExportNamespacePrefix('App\\PhelGenerated')
    ->withExportTargetDirectory(__DIR__ . '/src/PhelGenerated');
```

The default `App\ → src/` PSR-4 mapping covers `App\PhelGenerated\`.

Hook the same [load guard](/documentation/guides/deployment/#loading-phel-prod-vs-dev) into the kernel `boot()`, with `getProjectDir()` as the root:

```php
private static bool $phelLoaded = false;

public function boot(): void
{
    parent::boot();

    if (self::$phelLoaded) {
        return;
    }

    $built = $this->getProjectDir() . '/build/app/main.php';

    if (is_file($built)) {
        require $built;
    } else {
        \Phel::run($this->getProjectDir(), 'app.main');
    }

    self::$phelLoaded = true;
}
```

Controllers then use any wrapper (`App\PhelGenerated\Reports\Daily`, and so on), all registered by the main load.

## Plain PHP

`phel-config.php`:

```php
<?php

use Phel\Config\PhelConfig;

return PhelConfig::forProject(mainNamespace: 'app.main')
    ->withSrcDirs(['phel'])
    ->withTestDirs(['tests/phel'])
    ->withBuildDestDir('build');
```

Entry script, applying the [load guard](/documentation/guides/deployment/#loading-phel-prod-vs-dev) with `__DIR__` as the root:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

$built = __DIR__ . '/build/app/main.php';

if (is_file($built)) {
    require $built;
} else {
    \Phel::run(__DIR__, 'app.main');
}

// Call anything
$greet = \Phel::getDefinition('app.main', 'greet');
echo $greet('World') . "\n";
```

## Persistence: maps, not entities

An ORM entity (Doctrine, Eloquent) is a mutable object that the framework loads and tracks for changes. Phel data is immutable, so making a Phel struct be an entity works against the language. Keep rows as plain maps and do the database write at the edge. Two small libraries cover it:

- [phel-sql](https://github.com/phel-lang/phel-sql): HoneySQL-style. Map in, `[sql params]` out. No driver.
- [phel-pdo](https://github.com/phel-lang/phel-pdo): runs `[sql params]`, returns rows as maps.

<!-- phel-test: skip -->
```phel
(ns shop.catalog
  (:require phel.sql :as sql)
  (:require phel.pdo :as pdo))

(defn find-product [conn id]
  (let [[query params] (sql/format {:select [:id :name :price]
                                    :from   [:products]
                                    :where  [:= :id id]})]
    (-> (pdo/prepare conn query)
        (pdo/execute params)
        (pdo/fetch))))                       ; => {:id 1 :name "Keyboard" :price 49.9}
```

The business logic stays pure: a map in, a map out, no database:

```phel
(defn apply-discount [product pct]
  (update product :price (fn [p] (* p (- 1 pct)))))

(apply-discount {:id 1 :name "Keyboard" :price 50.0} 0.1)
; => {:id 1, :name "Keyboard", :price 45.0}
```

### Reuse the framework connection

Do not open a second connection. phel-sql does not depend on a driver, so its `[sql params]` goes into the connection your framework already has, for example Doctrine DBAL in Symfony:

```php
// Symfony service, $conn injected (Doctrine\DBAL\Connection)
[$sql, $params] = Catalog::buildProductQuery($id);   // exported Phel fn calling sql/format
$rows = $conn->executeQuery($sql, $params)->fetchAllAssociative();
```

phel-pdo can also wrap an existing PDO handle so its map-returning helpers run on the host's pooled connection.

### When you need the object {#when-you-really-need-the-object}

Some PHP APIs require a typed instance, such as a DTO or a value object. Convert at the boundary: `hydrate` builds an instance from a map without running its constructor, and `bean` reads its public properties back into a map with keyword keys. Keep maps everywhere else.

<!-- phel-test: skip -->
```phel
(def dto (hydrate "App\\Dto\\Product" {:id 1 :name "Keyboard" :price 49.9}))
(bean dto)  ; => {:id 1 :name "Keyboard" :price 49.9}
```

See [Map to typed object and back](/documentation/language/php-interop/#map-to-typed-object-and-back) in the PHP interop reference for the full semantics.

### Typed PHP from Phel definitions

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

Two more forms help with frameworks:

- `(defenum Status :active "active" :inactive "inactive")` emits a native PHP backed enum (for Doctrine or Symfony columns) and a `Status?` predicate.
- `(defexception NotFound \RuntimeException)` emits an exception class with the parent you choose, so framework `catch` blocks match by type.

See [Native enums and exceptions](/documentation/language/php-interop/#native-enums-and-exceptions) for details.

### Controllers, transactions, migrations

- **Routes and commands:** an exported `defn` can carry `^{:php/attr [:Symfony.Component.Routing.Attribute/Route "/products/{id}"]}`. `phel export` then puts the `#[Route]` on the generated wrapper, so you need no controller class.
- **Transactions:** wrap writes with phel-pdo's transaction helpers. Keep pure work outside the transaction.
- **Migrations:** use `doctrine/migrations` or your framework's tool.

## Notes

- Namespace path matches directory: `phel/shop/pricing.phel` maps to `(ns shop.pricing)`.
- Hyphens become camelCase: `(ns my-lib.core)` maps to `App\PhelGenerated\MyLib\Core`; `apply-discount` to `applyDiscount`.
- Production and development loading, the run-once rule, and what to commit: see [Loading Phel: prod vs dev](/documentation/guides/deployment/#loading-phel-prod-vs-dev).
- Load Phel in Laravel's `boot()`, never `register()` or any per-request hot path.
- `withBuildDestDir()` is relative to the project root.
- Add `vendor/bin/phel test` to CI alongside `phpunit`.

## Next steps

- [PHP Interop](/documentation/language/php-interop/): call PHP, build objects, typed emission, enums, and exceptions
- [Debugging](/documentation/guides/debugging/): inspect compiled PHP and dump values with Symfony VarDumper
- [phel-sql](https://github.com/phel-lang/phel-sql) and [phel-pdo](https://github.com/phel-lang/phel-pdo): data-driven SQL and a PDO wrapper
