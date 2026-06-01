+++
title = "PHP Interop"
weight = 50
description = "Call PHP functions, build objects, work with PHP arrays, and catch PHP exceptions from Phel."
+++

## Globals and constants

Access PHP superglobals with `php/` prefix and `get`:

```phel
(get php/$_SERVER "key") ; $_SERVER['key']
(get php/$GLOBALS "argv") ; $GLOBALS['argv']
```

PHP [`define`](https://www.php.net/manual/en/function.define.php) constants accessed via `php/CONSTANT_NAME`:

```phel
(php/define "MY_SETTING" "My value") ; Calls PHP define('MY_SETTING', 'My value');
php/MY_SETTING ; => "My value"
```

{% php_note() %}
The `php/` prefix gives you direct access to PHP's global scope:

```php
// PHP
$_SERVER['key']
$GLOBALS['argv']
MY_SETTING

// Phel
(get php/$_SERVER "key")
(get php/$GLOBALS "argv")
php/MY_SETTING
```

**Note:** Use Phel's immutable data structures when possible. Only use PHP arrays when you need to interop with PHP libraries that expect them.
{% end %}

## Calling PHP functions

Add `php/` prefix to any PHP function name:

```phel
(php/strlen "test") ; => 4
(php/date "l")      ; => "Monday" (or whatever the current day is)
```

{% php_note() %}
Any PHP function can be called by adding the `php/` prefix:

```php
// PHP
strlen("test");
date("l");
array_map($fn, $array);

// Phel
(php/strlen "test")
(php/date "l")
(php/array_map fn array)
```

However, Phel provides functional equivalents for many operations. For example, use `(count "test")` instead of `(php/strlen "test")` when working with Phel data structures.
{% end %}

Namespaced PHP functions use full path after `php/`. Three equivalent forms accepted (added in 0.37, last two are backslash-free):

<!-- phel-test: skip -->
```phel
(php/Foo\Bar\baz)      ; classic backslash form
(php/Foo.Bar/baz)      ; dot-separated, slash before fn name
(php/Foo.Bar.baz)      ; fully dot-separated

(php/Amp.trapSignal [(php/:: SIGINT) (php/:: SIGTERM)])
```

Capture into a Phel alias:

<!-- phel-test: skip -->
```phel
(def trap-signal php/\Amp.trapSignal)
(trap-signal [2 15])
```

## Interop shorthands

Terse forms that expand to verbose `php/*`. Use whichever reads better.

| Shorthand                 | Expands to                         |
|---------------------------|------------------------------------|
| `(ClassName. args)`       | `(php/new ClassName args)`         |
| `(new ClassName args)`    | `(php/new ClassName args)`         |
| `(.method obj args)`      | `(php/-> obj (method args))`       |
| `(.-field obj)`           | `(php/-> obj field)`               |
| `(ClassName/method args)` | `(php/:: ClassName (method args))` |
| `ClassName/MEMBER`        | `(php/:: ClassName MEMBER)`        |

```phel
(ns my.module
  (:use DateTimeImmutable DateInterval))

(DateTimeImmutable. "2026-04-20")              ; constructor (preferred)
(.format (DateTimeImmutable.) "Y-m-d")          ; instance method
(.-s (DateInterval. "PT30S"))                  ; property
(DateTimeImmutable/createFromFormat "Y-m-d" "2026-04-20") ; static method
DateTimeImmutable/ATOM                         ; static constant
```

## Class instantiation

Three equivalent forms - prefer `ClassName.` for imported classes:

```phel
(ns my.module
  (:use DateTime DateTimeImmutable))

(DateTime.)              ; => DateTime instance (ClassName. shorthand)
(DateTime. "now")        ; => DateTime instance with arg
(new DateTime)           ; also valid
(php/new DateTime)       ; also valid

(php/new "\\DateTimeImmutable") ; instantiate from string (dynamic)
```

{% php_note() %}
```php
// PHP
new DateTime();
new DateTime("now");
new \DateTimeImmutable();

// Phel - preferred shorthand
(DateTime.)
(DateTime. "now")
(DateTimeImmutable.)
```

Import classes with `:use` to use the short `ClassName.` form without repeating the namespace.
{% end %}

## Method and property call

<!-- phel-test: skip -->
```phel
(php/-> object (methodname expr*))
(php/-> object property)
```

Calls method or accesses property. Both `methodname` and `property` must be symbols, not evaluated values.

Chain multiple in one `php/->`. Each element evaluates on result of previous, enabling fluent chains or nested property access.

<!-- phel-test: skip -->
```phel
(ns my.module
  (:use DateInterval)
  (:use DateTimeImmutable)
  (:use stdClass))

(def di (DateInterval. "PT30S"))

(.format di "%s seconds")          ; => "30 seconds"  (.method shorthand)
(php/-> di (format "%s seconds"))  ; same, verbose form
(.-s di)                           ; => 30  (.-prop shorthand)

;; Chain multiple calls:
;; (new DateTimeImmutable("2024-03-10"))->modify("+1 day")->format("Y-m-d")
(-> (DateTimeImmutable. "2024-03-10")
    (.modify "+1 day")
    (.format "Y-m-d"))

;; php/-> also works and is required for chains mixing methods and properties:
(php/-> user profile (getDisplayName))

;; Nested property access:
(def address (stdClass.))
(def user    (stdClass.))
(php/oset (php/-> address city) "Berlin")
(php/oset (php/-> user address) address)
(php/-> user address city) ; => "Berlin"
```

{% php_note() %}
The `php/->` operator is similar to PHP's `->` but allows chaining in a more functional style:

```php
// PHP
$di->format("%s seconds");
$di->s;
(new DateTimeImmutable("2024-03-10"))->modify("+1 day")->format("Y-m-d");
$user->profile->getDisplayName();

// Phel - shorthand forms
(.format di "%s seconds")
(.-s di)
(-> (DateTimeImmutable. "2024-03-10") (.modify "+1 day") (.format "Y-m-d"))
(php/-> user profile (getDisplayName))   ; mixed chains need php/->
```

Method calls: `(.method obj args)` shorthand or `(php/-> obj (method args))`. Property access: `(.-prop obj)` or `(php/-> obj prop)`. Mixed chains (method + property in one expression) use `php/->` directly.
{% end %}

{% clojure_note() %}
The `php/->` operator is inspired by Clojure's thread-first macro `->`, but specifically designed for PHP object method chaining.
{% end %}

## Static method and property

<!-- phel-test: skip -->
```phel
(php/:: class (methodname expr*))
(php/:: class property)
```

Same as above, but static.

```phel
(ns my.module
  (:use DateTimeImmutable))

DateTimeImmutable/ATOM                                     ; => "Y-m-d\TH:i:sP"  (shorthand)
(php/:: DateTimeImmutable ATOM)                            ; verbose form

(DateTimeImmutable/createFromFormat "Y-m-d" "2020-03-22") ; shorthand
(php/:: DateTimeImmutable (createFromFormat "Y-m-d" "2020-03-22")) ; verbose
```

{% php_note() %}
The `php/::` operator is equivalent to PHP's `::` for static method and property access:

```php
// PHP
DateTimeImmutable::ATOM;
DateTimeImmutable::createFromFormat("Y-m-d", "2020-03-22");

// Phel - shorthand forms
DateTimeImmutable/ATOM
(DateTimeImmutable/createFromFormat "Y-m-d" "2020-03-22")
```
{% end %}

## Set object properties

<!-- phel-test: skip -->
```phel
(php/oset (php/-> object property) value)
(php/oset (php/:: class property) value)
```

Set value on class/object property.

```phel
(def x (stdclass.))
(php/oset (php/-> x name) "foo")
```

{% php_note() %}
`php/oset` is the Phel equivalent of PHP's property assignment:

```php
// PHP
$x = new stdClass();
$x->name = "foo";

// Phel
(def x (stdclass.))
(php/oset (php/-> x name) "foo")
```

**Note:** This mutates the PHP object. When possible, use Phel's immutable data structures instead.
{% end %}

## Get PHP array value

<!-- phel-test: skip -->
```phel
(php/aget arr index)
```

Equivalent: `arr[index] ?? null`.

```phel
(php/aget ["a" "b" "c"] 0) ; Evaluates to "a"
(php/aget (php/array "a" "b" "c") 1) ; Evaluates to "b"
(php/aget (php/array "a" "b" "c") 5) ; Evaluates to nil
```

{% php_note() %}
`php/aget` safely accesses PHP array elements:

```php
// PHP
$arr[0] ?? null;
$arr[1] ?? null;
$arr[5] ?? null;  // Returns null

// Phel
(php/aget arr 0)
(php/aget arr 1)
(php/aget arr 5)  ; Returns nil
```

**Important distinction:**
- Use `php/aget` for **PHP arrays** (mutable)
- Use `get` for **Phel data structures** (immutable vectors, maps)
{% end %}

## Get nested PHP array value

<!-- phel-test: skip -->
```phel
(php/aget-in arr path)
```

Resolves nested values via a sequence of keys/indexes. `path` is a sequential collection (e.g. vector). Missing step returns `nil`.

```phel
(def users
  #php {"users"
        #php {0 #php {"name" "Alice"}
              1 #php {"name" "Bob"}}})

(php/aget-in users ["users" 1 "name"]) ; Evaluates to "Bob"

(php/aget-in
    #php {"meta" #php {"status" "ok"}}
    ["meta" "status"]) ; Evaluates to "ok"

(php/aget-in
    #php {"meta" #php {"status" "ok"}}
    ["meta" "missing"]) ; Evaluates to nil
```

{% php_note() %}
`php/aget-in` provides safe nested array access:

```php
// PHP - manual nested access with null coalescing
$users['users'][1]['name'] ?? null;
$data['meta']['status'] ?? null;
$data['meta']['missing'] ?? null;

// Phel - clean path-based access
(php/aget-in users ["users" 1 "name"])
(php/aget-in data ["meta" "status"])
(php/aget-in data ["meta" "missing"])  ; Returns nil safely
```

This is similar to Phel's `get-in` for immutable data structures, but specifically for PHP arrays.
{% end %}

## Set PHP array value

<!-- phel-test: skip -->
```phel
(php/aset arr index value)
```

Equivalent: `arr[index] = value`.

{% php_note() %}
`php/aset` mutates a PHP array in place:

```php
// PHP
$arr[0] = "value";

// Phel
(php/aset arr 0 "value")
```

**Important:** This mutates the array. For immutable operations, use Phel's `assoc` on Phel data structures instead.
{% end %}

## Set nested PHP array value

<!-- phel-test: skip -->
```phel
(php/aset-in arr path value)
```

Creates or updates nested entries. Missing intermediate arrays are created.

```phel
(def data (php/array))
(php/aset-in data ["user" "profile" "name"] "Charlie")
(php/aget-in data ["user" "profile" "name"]) ; Evaluates to "Charlie"
;; Equivalent to $data['user']['profile']['name'] = 'Charlie';
```

{% php_note() %}
`php/aset-in` creates nested structures automatically:

```php
// PHP - manual nested array creation
$data = [];
$data['user']['profile']['name'] = 'Charlie';

// Phel - automatic path creation
(def data (php/array))
(php/aset-in data ["user" "profile" "name"] "Charlie")
```

This is the mutable counterpart to Phel's `assoc-in` for immutable data structures.
{% end %}

## Append PHP array value

<!-- phel-test: skip -->
```phel
(php/apush arr value)
```

Equivalent: `arr[] = value`.

{% php_note() %}
`php/apush` appends to a PHP array:

```php
// PHP
$arr[] = "new value";

// Phel
(php/apush arr "new value")
```

For immutable operations, use `conj` on Phel vectors instead.
{% end %}

## Unset PHP array value

<!-- phel-test: skip -->
```phel
(php/aunset arr index)
```

Equivalent: `unset(arr[index])`.

{% php_note() %}
`php/aunset` removes an element from a PHP array:

```php
// PHP
unset($arr[0]);

// Phel
(php/aunset arr 0)
```

For immutable operations, use `dissoc` on Phel maps instead.
{% end %}

## Unset nested PHP array value

<!-- phel-test: skip -->
```phel
(php/aunset-in arr path)
```

Removes nested entry. Parent arrays remain untouched even if empty after.

```phel
(def data #php {"user" #php {"profile" #php {"name" "Dora"}}})
(php/aunset-in data ["user" "profile" "name"])
(php/aget-in data ["user" "profile" "name"]) ; Evaluates to nil
;; Equivalent to unset($data['user']['profile']['name']);
```

{% php_note() %}
`php/aunset-in` removes nested array elements:

```php
// PHP
unset($data['user']['profile']['name']);

// Phel
(php/aunset-in data ["user" "profile" "name"])
```

Parent arrays remain intact even if they become empty after the unset.
{% end %}

## `__DIR__`, `__FILE__`, `*file*`

PHP magic constants `__DIR__` and `__FILE__` work but expand at PHP compile, pointing to the generated PHP file under `.phel/cache`.

For the original Phel source path, use `*file*` (absolute path of current Phel file). Combine with `php/dirname` for the source dir.

```phel
(println __DIR__)  ; Directory name of the generated PHP file
(println __FILE__) ; Filename of the generated PHP file

(println (php/dirname *file*)) ; Directory of the original Phel file
(println *file*)               ; Absolute path of the original file
```

{% php_note() %}
**Important distinction:**

```php
// PHP magic constants
__DIR__   // Points to .phel/cache directory (generated PHP)
__FILE__  // Points to cached .php file

// Phel special var
*file*    // Points to your actual .phel source file
```

Use `*file*` when you need to reference the original Phel source location, such as for loading resources relative to your source code.
{% end %}

## Catching PHP exceptions

PHP functions and methods throw native exceptions, and they cross the interop boundary unchanged. Catch them with `try`/`catch`, matching on the PHP class name. Catch `\Throwable` to handle anything.

```phel
(try
  (php/intdiv 1 0)
  (catch \DivisionByZeroError e
    (php/-> e (getMessage))))
; => "Division by zero"
```

The `.method` shorthand and a `finally` clause work too:

<!-- phel-test: skip -->
```phel
(try
  (risky-php-call)
  (catch \Throwable e
    (.getMessage e))
  (finally
    (cleanup)))
```

For Phel's own exceptions, `ex-info`, and re-throwing, see [Error Handling](/documentation/language/error-handling/).

## Calling Phel from PHP

Useful for integrating Phel into existing PHP apps. Load the Phel namespace after `autoload.php`.

Example: [using-exported-phel-function.php](https://github.com/phel-lang/cli-skeleton/blob/main/example/using-exported-phel-function.php)

```php
<?php

use Phel\Phel;
use PhelGenerated\CliSkeleton\Modules\AdderModule;

$projectRootDir = dirname(__DIR__);

require $projectRootDir . '/vendor/autoload.php';

Phel::run($projectRootDir, 'cli-skeleton.modules.adder-module');

$adder = new AdderModule();
$result = $adder->adder(1, 2, 3);

echo 'Result = ' . $result . PHP_EOL;
```

Two ways: manually, or via the `export` command.

### Manually

`PhelCallerTrait` calls any Phel function from a PHP class. Inject the trait, call `callPhel`.

```php
<?php
use Phel\Interop\PhelCallerTrait;

class MyExistingClass {
  use PhelCallerTrait;

  public function myExistingMethod(...$arguments) {
    return $this->callPhel(
        'my.phel.namespace', 
        'phel-function-name', 
        ...$arguments
    );
  }
}
```

### Using the `export` command

`phel export` generates a wrapper class for all Phel functions marked *export*.

Add config to `phel-config.php` first:

```php
<?php
return (new \Phel\Config\PhelConfig())
    ->withExportFromDirectories(['src'])
    ->withExportNamespacePrefix('PhelGenerated')
    ->withExportTargetDirectory('src/PhelGenerated')
;
```

Option details: [Configuration](/documentation/configuration/).

Mark a function exported with metadata:

```phel
(defn my-function
  {:export true}
  [a b]
  (+ a b))
```

`phel export` then generates a wrapper class in the target dir (here `src/PhelGenerated`). Use it from PHP to call Phel functions.

## Next steps

- [Error Handling](/documentation/language/error-handling/): `try`, `catch`, `finally`, `ex-info`.
- [Configuration](/documentation/configuration/): `withExport*` options for `phel export`.
- [PHP API reference](/documentation/reference/api/php): every `php/*` builtin.
