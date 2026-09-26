+++
title = "PHP Debugging Tools"
weight = 7
description = "Debug Phel with PHP tools: var_dump, Symfony VarDumper dump/dd, inspecting compiled PHP, and error reporting"
aliases = ["/documentation/tooling/php-tools"]
+++

Phel compiles to PHP, so every PHP debugging function is available through the `php/` prefix. Reach for these alongside Phel's built-in [REPL helpers](/documentation/tooling/repl/#debug-helpers).

## Native var_dump()

Any PHP function via `php/` prefix:

```phel
;; Dumping a definition by its name
(def v (+ 2 2))
(php/var_dump v)
;; OUTPUT:
;; int(4)
```

```phel
;; Directly dumping the result of a function
(php/var_dump (+ 3 3))
;; OUTPUT:
;; int(6)
```

`(php/die)` halts execution so you can inspect at leisure.

### When to use var_dump()

- **Quick debugging:** no setup
- **Inspect PHP objects:** internal structure
- **Type checks:** verify type conversions
- **Legacy code:** existing PHP codebases

## Symfony VarDumper: dump() & dd()

Use [Symfony VarDumper](https://symfony.com/doc/current/components/var_dumper.html). Install via composer under `require-dev`:

```json
"require-dev": {
    "symfony/var-dumper": "^7.4"
},
```

`dump()` a definition or result:

```phel
(php/dump (+ 4 4))
;; OUTPUT:
8
```

`dd()` dumps and halts:

<!-- phel-test: skip -->
```phel
(php/dd (+ 5 5))
;; OUTPUT:
10
```

### Why Symfony VarDumper?

**Beautiful output:** syntax highlighting, collapsible nested structures, better formatting than var_dump().

**Rich info:** object properties/methods, resource types, circular references.

**Web-friendly:** HTML output, dark mode, copy-to-clipboard.

### Best practices

<!-- phel-test: skip -->
```phel
;; Use dump() during development
(defn process-user [user]
  (php/dump user)  ; Inspect without stopping
  (-> user
      (validate)
      (save)))

;; Use dd() to stop and inspect
(defn debug-pipeline [data]
  (-> data
      (transform)
      (php/dd)  ; Stop here and inspect
      (save)))  ; Never reached
```

## Check the evaluated PHP

The fastest way to see what Phel emits is `phel compile`. It prints the PHP for a snippet or a file without running it:

```bash
vendor/bin/phel compile '(defn greet [name] (str "Hello, " name "!"))'
```

Output, trimmed after the function body:

```php
\Phel::addDefinition(
  "user",
  "greet",
  new class() extends \Phel\Lang\AbstractFn {
    public const BOUND_TO = "user\\greet";

    public function __invoke($name): string {
      return (\Phel\Lang\Registry::readRoot("phel.core", "str"))->__invoke("Hello, ", $name, "!");
    }
  },
  // ... location and metadata
);
```

Every `defn` becomes a class that extends `AbstractFn`, registered under its namespace. Core functions are looked up through the registry. Reach for this when you debug interop, report a compiler bug, or want to see why something is slow.

### Keep the temp files

`phel run` and the REPL compile to temp files such as `$TMPDIR/phel/tmp/__phel_<hash>.php` and delete them afterwards. An error that points at one of those paths then points at a file that no longer exists. Keep them with `withKeepGeneratedTempFiles`:

```php
<?php # phel-config-local.php

return (require __DIR__ . '/phel-config.php')
    ->withKeepGeneratedTempFiles(true)
;
```

> TIP: Add `phel-config-local.php` to `.gitignore` to change your dev config without touching the shared one.

Then open the file from the error message and match its line numbers. See [Configuration](/documentation/configuration/) for the other dev settings.

## PHP error reporting

Detailed errors in development:

```php
<?php # phel-config-local.php

error_reporting(E_ALL);
ini_set('display_errors', '1');

return (require __DIR__ . '/phel-config.php')
    ->withKeepGeneratedTempFiles(true)
;
```

Catches:
- Type errors
- Undefined variables
- Deprecated calls
- Warnings and notices

## Next steps

- [Phel debug helpers](/documentation/tooling/repl/#debug-helpers) for native debugging
- [XDebug](/documentation/tooling/xdebug-setup/) for step-through debugging
- [Config docs](/documentation/configuration/) for more dev settings
