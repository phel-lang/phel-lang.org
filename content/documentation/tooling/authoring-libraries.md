+++
title = "Authoring libraries"
weight = 4
aliases = ["/documentation/authoring-libraries"]
+++

Share Phel code across projects via Composer and Packagist.

Reference repo: [chemaclass/phel-cli-gui](https://github.com/Chemaclass/phel-cli-gui) ([Packagist](https://packagist.org/packages/chemaclass/phel-cli-gui)).

## From scratch

### 1. Create the project

```bash
mkdir my-lib && cd my-lib
git init
composer init   # answer prompts, name = your-vendor/my-lib
composer require phel-lang/phel-lang
```

### 2. Add `composer.json` scripts and autoload

Open the generated `composer.json` and merge in:

```json
{
    "require": {
        "phel-lang/phel-lang": "^0.36"
    },
    "autoload": {
        "psr-4": { "YourVendor\\MyLib\\": "src/php/" }
    },
    "scripts": {
        "test": "vendor/bin/phel test",
        "build": "vendor/bin/phel build --no-cache",
        "format": "vendor/bin/phel format"
    }
}
```

`psr-4` only needed if shipping PHP interop. See [Composer constraints](https://getcomposer.org/doc/articles/versions.md) for `^` vs `~`.

### 3. Add `phel-config.php`

```php
<?php
declare(strict_types=1);

use Phel\Config\PhelBuildConfig;
use Phel\Config\PhelConfig;

return (new PhelConfig())
    ->useNestedLayout()
    ->setBuildConfig((new PhelBuildConfig())
        ->setMainPhelNamespace('your-vendor.my-lib')
        ->setMainPhpPath('out/main.php'));
```

`useNestedLayout()` sets `src/phel`, `tests/phel`, format dirs. Override with `setSrcDirs`, `setTestDirs`, `setFormatDirs`. Full options: [Configuration](/documentation/configuration/).

### 4. Create the source layout

```
my-lib/
  composer.json
  phel-config.php
  src/phel/        ; Phel sources
  src/php/         ; optional PHP interop
  tests/phel/      ; Phel tests
  tests/php/       ; optional PHPUnit tests
```

First Phel file at `src/phel/core.phel`:

```phel
(ns your-vendor.my-lib.core)

(defn greet [name]
  (str "Hello, " name "!"))
```

Namespace path mirrors directory: `your-vendor.my-lib.core` lives at `src/phel/your-vendor/my-lib/core.phel`. See [Namespaces](/documentation/language/namespaces/).

### 5. Add a test

`tests/phel/core-test.phel`:

```phel
(ns your-vendor.my-lib.core-test
  (:require phel.test :refer [deftest is])
  (:require your-vendor.my-lib.core :refer [greet]))

(deftest greet-test
  (is (= "Hello, world!" (greet "world"))))
```

Run:

```bash
composer test
```

See [Testing](/documentation/testing/).

### 6. Push to GitHub

```bash
git add . && git commit -m "init"
git remote add origin git@github.com:your-vendor/my-lib.git
git push -u origin main
```

### 7. Publish on Packagist

1. Log in to [Packagist](https://packagist.org/).
2. Click **Submit**, paste the GitHub URL.
3. On the package page, click **Settings** and add the GitHub webhook (Packagist shows the URL and token). Future tags then auto-publish.

### 8. Tag a release

```bash
git tag 0.1.0
git push --tags
```

Packagist picks up the tag within seconds. Install anywhere:

```bash
composer require your-vendor/my-lib
```

## Conventions

- **Namespace:** `{vendor}.{library-name}`, sub-namespaces map to subdirectories.
- **Private defs:** [`def-`](/documentation/reference/api/core/#def-), [`defn-`](/documentation/reference/api/core/#defn-), [`defmacro-`](/documentation/reference/api/core/#defmacro-) keep symbols out of the public API.
- **PHP interop:** if PHP consumers will call your code, set `setMainPhpPath` then `composer build`. See [PHP interop](/documentation/php-interop/#calling-phel-from-php).

## Cross-platform code

Phel reads `.cljc` files with reader conditionals, so the same source can target Phel and Clojure.

```clojure
(def platform
  #?(:phel "PHP"
     :clj "JVM"
     :default "Unknown"))
```

Splice variant for sequences:

```clojure
(def features
  [:core
   #?@(:phel [:php-interop :composer]
       :clj [:java-interop :maven])])
```

Keys: `:phel`, `:default`. Phel picks `:phel`, falls back to `:default`.
