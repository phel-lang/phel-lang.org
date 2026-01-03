+++
title = "PHP Debugging Tools"
weight = 3
+++

Since Phel compiles to PHP, you can use familiar PHP debugging functions and tools. These are perfect for quick inspections and work alongside Phel's built-in helpers.

## Native var_dump()

You can use any PHP function simply using the `php/` prefix:

```phel
# Dumping a definition by its name
(def v (+ 2 2))
(php/var_dump v)
# OUTPUT:
int(4)
```

```phel 
# Directly dumping the result of a function
(php/var_dump (+ 3 3))
# OUTPUT:
int(6)
```

Additionally, you can call `(php/die)` to force the execution of the process so that you can debug a particular value at your own rhythm.

### When to use var_dump()

- **Quick and dirty debugging**: No setup required
- **Inspecting PHP objects**: See internal PHP structure
- **Checking types**: Verify PHP type conversions
- **Legacy code**: When working with existing PHP codebases

## Symfony VarDumper: dump() & dd()

Symfony has an awesome [VarDumper Component](https://symfony.com/doc/current/components/var_dumper.html) which you can use in your Phel projects as well. You can install it by using composer, under your `require-dev` dependencies.

```json
"require-dev": {
    "symfony/var-dumper": "^5.4"
},
```

And then, the same drill, you can `dump()` a definition by its name or the function result:

```phel
(php/dump (+ 4 4))
# OUTPUT:
8
```

Additionally, you can also use `dd()` to dump and die the execution of the program as soon as it reaches that point:

```phel 
(php/dd (+ 5 5))
# OUTPUT:
10
```

### Why Symfony VarDumper?

**Beautiful output:**
- Syntax highlighting
- Collapsible nested structures
- Better formatting than var_dump()

**Rich information:**
- Object properties and methods
- Resource types
- Circular references detection

**Web-friendly:**
- Nice HTML output in browsers
- Dark mode support
- Copy-to-clipboard functionality

### Best Practices

```phel
# Use dump() during development
(defn process-user [user]
  (php/dump user)  # Inspect without stopping
  (-> user
      (validate)
      (save)))

# Use dd() to stop and inspect
(defn debug-pipeline [data]
  (-> data
      (transform)
      (php/dd)  # Stop here and inspect
      (save)))  # Never reached
```

## Check the Evaluated PHP

You can keep the generated temporal PHP files for debugging purposes. Useful when you see an error occurring on `/private/var/folders/qq/dvftwj.../T/__phelV2KvGD` but the file does not exist. Read the [docs](/documentation/configuration/#keepgeneratedtempfiles).

```php
<?php # phel-config-local.php

return (require __DIR__ . '/phel-config.php')
    ->setKeepGeneratedTempFiles(true)
;
```

> TIP: Add this file to the `.gitignore` of the project, so you can have control over the configuration while on development without changing the global config.

### Inspecting Compiled PHP

Once you enable `setKeepGeneratedTempFiles(true)`, you can:

1. **Find the generated files**: Look for temp files in `/tmp/` or your system's temp directory
2. **Read the PHP code**: See exactly how Phel compiles to PHP
3. **Understand errors**: Match error line numbers to your Phel code
4. **Learn the compiler**: See optimization patterns

**Example workflow:**

```phel
# Your Phel code
(defn greet [name]
  (str "Hello, " name "!"))
```

With `setKeepGeneratedTempFiles(true)`, you can find and read the generated PHP:

```php
<?php
// Generated PHP (simplified)
function greet($name) {
    return "Hello, " . $name . "!";
}
```

This is invaluable for:
- **Debugging compiler issues**
- **Understanding performance**
- **Learning Phel internals**
- **Reporting bugs** with concrete examples

## PHP Error Reporting

Enable detailed error reporting during development:

```php
<?php # phel-config-local.php

error_reporting(E_ALL);
ini_set('display_errors', '1');

return (require __DIR__ . '/phel-config.php')
    ->setKeepGeneratedTempFiles(true)
;
```

This helps catch:
- Type errors
- Undefined variables
- Deprecated function calls
- PHP warnings and notices

## Next Steps

- Use [Phel's debug helpers](/documentation/debug/phel-helpers/) for Phel-native debugging
- Set up [XDebug](/documentation/debug/xdebug-setup/) for professional step-through debugging
- Check the [configuration docs](/documentation/configuration/) for more development settings
