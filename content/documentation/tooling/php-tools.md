+++
title = "PHP Debugging Tools"
weight = 7
aliases = ["/documentation/tooling/php-tools"]
+++

Phel compiles to PHP, so PHP debugging functions work. Pairs with Phel's built-in helpers.

## Native var_dump()

Any PHP function via `php/` prefix:

```phel
;; Dumping a definition by its name
(def v (+ 2 2))
(php/var_dump v)
;; OUTPUT:
int(4)
```

```phel 
;; Directly dumping the result of a function
(php/var_dump (+ 3 3))
;; OUTPUT:
int(6)
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
    "symfony/var-dumper": "^6.4|^7.0"
},
```

`dump()` a definition or result:

```phel
(php/dump (+ 4 4))
;; OUTPUT:
8
```

`dd()` dumps and halts:

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

Keep generated temp PHP files for debugging. Useful when an error references `/private/var/folders/.../T/__phelV2KvGD` that no longer exists. See [docs](/documentation/configuration/).

```php
<?php # phel-config-local.php

return (require __DIR__ . '/phel-config.php')
    ->withKeepGeneratedTempFiles(true)
;
```

> TIP: Add to `.gitignore` to control dev config without touching the global one.

### Inspecting compiled PHP

After `withKeepGeneratedTempFiles(true)`:

1. **Find the files** in `/tmp/` or system temp dir.
2. **Read the PHP** to see how Phel compiles.
3. **Understand errors** by matching line numbers.
4. **Learn the compiler** by seeing optimization patterns.

**Example:**

```phel
;; Your Phel code
(defn greet [name]
  (str "Hello, " name "!"))
```

Generated PHP:

```php
<?php
// Generated PHP (simplified)
function greet($name) {
    return "Hello, " . $name . "!";
}
```

Useful for:
- Debugging compiler issues
- Understanding performance
- Learning Phel internals
- Reporting bugs with concrete examples

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
