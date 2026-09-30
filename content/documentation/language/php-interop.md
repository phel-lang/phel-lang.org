+++
title = "PHP Interop"
weight = 11
description = "Call PHP functions, create objects, convert PHP arrays, use Composer packages, and call Phel back from PHP."
aliases = ["/documentation/php-interop/"]
+++

Phel compiles to PHP, so every PHP function, class and Composer package is available. After this page you can call PHP code from Phel, move data between PHP arrays and Phel collections, and call Phel from an existing PHP app.

## Quick reference

| Phel                           | PHP                         |
|--------------------------------|-----------------------------|
| `(php/strlen "abc")`           | `strlen("abc")`             |
| `(ClassName. args)`            | `new ClassName(args)`       |
| `(new ClassName args)`         | `new ClassName(args)`       |
| `(.method obj args)`           | `$obj->method(args)`        |
| `(.-field obj)`                | `$obj->field`               |
| `(set! (.-field obj) v)`       | `$obj->field = v`           |
| `(ClassName/method args)`      | `ClassName::method(args)`   |
| `ClassName/MEMBER`             | `ClassName::MEMBER`         |
| `php/PHP_EOL`                  | `PHP_EOL`                   |

These are the only spellings. The old `php/new`, `php/->` and `php/::` forms are rejected since Phel 0.52 (error `PHEL012`).

{% <clojure_note> %}
Same spelling as Clojure: `.method`, `.-field`, `Class/member`, and `->` for chaining.
{% </clojure_note> %}

## Call a PHP function

Put `php/` in front of the function name:

```phel
(php/strlen "test")          ; => 4
(php/str_pad "7" 3 "0" 0)    ; => "007"
```

Phel has its own function for most tasks (`count`, `str`, `map`). Reach for `php/` when Phel has no equivalent or a library expects a PHP call.

For a namespaced function, write the namespace with dots. The backslash form still works but is deprecated:

<!-- phel-test: skip -->
```phel
(php/Amp.trapSignal [php/SIGINT php/SIGTERM])   ; Amp\trapSignal(...)
(php/Amp/trapSignal [php/SIGINT php/SIGTERM])   ; same, slash before the name
```

### PHP functions as values { #php-first-class-callable }

A `php/` function is a value. Bind it, pass it to `map`, or `apply` it:

```phel
(let [upcase php/strtoupper]
  (map upcase ["a" "b"]))   ; => ("A" "B")

(apply php/max [3 7 2])     ; => 7
```

When a PHP library needs a native PHP callable, use `php/callable`. It works like PHP's `strtoupper(...)` and accepts a function, a static method, or an instance method:

```phel
(let [parse (php/callable \DateTimeImmutable createFromFormat)]
  (.format (parse "Y-m-d" "2026-06-06") "Y-m-d")) ; => "2026-06-06"
```

## Create an object { #php-class-instantiation }

Add a dot after the class name. Import the class with `:use` so you write the namespace once:

```phel
(ns my.module
  (:use DateTimeImmutable))

(DateTimeImmutable. "2024-03-10")   ; => DateTimeImmutable instance
(new DateTimeImmutable "2024-03-10") ; same
(new "\\DateTimeImmutable")          ; class name from a string
```

Global PHP classes such as `DateTime` also work without `:use`.

## Call methods and properties { #method-and-property-call }

`.method` calls a method. `.-field` reads a public property. The name is part of the symbol, not an evaluated value:

```phel
(def di (DateInterval. "PT30S"))

(.format di "%s seconds") ; => "30 seconds"
(.-s di)                  ; => 30
```

Chain calls with `->`. Each step receives the result of the previous one:

```phel
;; (new DateTimeImmutable("2024-03-10"))->modify("+1 day")->format("Y-m-d")
(-> (DateTimeImmutable. "2024-03-10")
    (.modify "+1 day")
    (.format "Y-m-d"))   ; => "2024-03-11"
```

### Set a property { #php-set-object-properties }

`set!` assigns a public property. This mutates the PHP object, so keep it at the edge of your code:

```phel
(def user (stdClass.))
(set! (.-name user) "Ada")
(.-name user) ; => "Ada"
```

## Static methods and constants { #php-static-method-and-property-call }

`Class/member` calls a static method or reads a class constant:

```phel
DateTimeImmutable/ATOM ; => "Y-m-d\\TH:i:sP"

(.format (DateTimeImmutable/createFromFormat "Y-m-d" "2020-03-22") "Y-m-d")
; => "2020-03-22"
```

`(set! ClassName/field v)` assigns a static property.

## Global constants and superglobals

Global constants and superglobals use the same `php/` prefix:

```phel
php/PHP_INT_MAX                    ; => 9223372036854775807
(php/define "MY_SETTING" "on")
php/MY_SETTING                     ; => "on"

(get php/$_SERVER "REQUEST_METHOD") ; $_SERVER['REQUEST_METHOD']
```

For command-line arguments use `*argv*`, not `$argv`.

## Named and by-reference arguments

PHP 8 named arguments go after a `:&` marker, as `:name value` pairs. They work for functions, constructors, instance methods and static methods:

```phel
(let [dt (\DateTime/createFromFormat :& :format "Y-m-d" :datetime "2026-06-06")]
  (.format dt "Y-m-d")) ; => "2026-06-06"
```

Some PHP functions write to a `&$ref` parameter (`preg_match`, `sort`). Wrap a `let`-bound local in `php/ref`. A top-level `def` is not a PHP variable, so it fails with `php/ref expects a local variable`:

```phel
(let [matches (php/array)]
  (php/preg_match "/(\d+)/" "order-42" (php/ref matches))
  (php/aget matches 1)) ; => "42"
```

## PHP arrays and Phel collections

Scalars (int, float, string, bool, `nil`) cross the boundary unchanged. Collections do not: Phel vectors and maps are immutable, PHP arrays are mutable. Convert when a PHP function needs an array, or when PHP gives you one:

| Function | Direction | Example | Result |
|---|---|---|---|
| `to-array` | Phel vector/map to PHP array (one level) | `(to-array [1 2 3])` | `<PHP-Array [1, 2, 3]>` |
| `phel->php` | Phel to PHP, nested | `(phel->php {:a 1 :b [1 2]})` | `<PHP-Array ["a":1, "b":<PHP-Array [1, 2]>]>` |
| `php->phel` | PHP to Phel, nested | `(php->phel #php {"a" 1 "b" #php [1 2]})` | `{"a" 1, "b" [1 2]}` |
| `php-array-to-map` | PHP array to Phel map (one level) | `(php-array-to-map #php {"a" 1})` | `{"a" 1}` |

Keyword keys become strings on the way to PHP. String keys stay strings on the way back. A typical round trip through a PHP function:

```phel
(php/json_encode (phel->php {:name "Ada" :tags ["x" "y"]}))
; => "{\"name\":\"Ada\",\"tags\":[\"x\",\"y\"]}"

(php->phel (php/json_decode "{\"a\":1,\"b\":[1,2]}" true))
; => {"a" 1, "b" [1 2]}
```

Write a PHP array literal with `#php [...]` or `#php {...}`. Core sequence functions such as `map`, `filter` and `count` also read PHP arrays directly.

### Read and write a PHP array in place

When you must mutate a PHP array, use these seven forms. Use them only on PHP arrays. The last column shows the matching function for Phel data:

| Form                       | PHP equivalent          | On Phel data |
|----------------------------|-------------------------|--------------|
| `(php/aget arr k)`         | `$arr[k] ?? null`       | `get`        |
| `(php/aget-in arr path)`   | `$arr[a][b] ?? null`    | `get-in`     |
| `(php/aset arr k v)`       | `$arr[k] = v`           | `assoc`      |
| `(php/aset-in arr path v)` | `$arr[a][b] = v`        | `assoc-in`   |
| `(php/apush arr v)`        | `$arr[] = v`            | `conj`       |
| `(php/aunset arr k)`       | `unset($arr[k])`        | `dissoc`     |
| `(php/aunset-in arr path)` | `unset($arr[a][b])`     | `dissoc-in`  |

`path` is a vector of keys and indexes. A missing key reads as `nil` at any depth. `php/aset-in` creates missing arrays along the path. `php/aunset-in` removes only the last key and keeps the parents, even when they become empty.

```phel
(def data (php/array))
(php/aset data "id" 42)
(php/aset-in data ["user" "profile" "name"] "Charlie")
(php/apush data "extra")

(php/aget-in data ["user" "profile" "name"]) ; => "Charlie"
(php/aget-in data ["user" "missing" "name"]) ; => nil
data ; => <PHP-Array ["id":42, "user":<PHP-Array ["profile":<PHP-Array ["name":"Charlie"]>]>, 0:"extra"]>
```

## Use Composer packages

Install a package with Composer as usual. Phel loads `vendor/autoload.php`, so its classes are ready to use. Import them with `:use`, writing the namespace with dots:

```bash
composer require symfony/string
```

```phel
(ns my.module
  (:use Symfony.Component.String.UnicodeString))

(-> (UnicodeString. "hello world")
    (.camel)
    (.toString))   ; => "helloWorld"
```

A backslash namespace in `:use` (`Symfony\Component\...`) still compiles but prints a deprecation warning. For framework setups (Symfony, Laravel), see [Framework integration](/documentation/web/framework-integration/).

## Types, objects and enums

`php/instanceof` checks an object against a class or interface. For Phel values use the core predicates (`int?`, `string?`, `map?`, ...):

```phel
(php/instanceof (DateTime.) \DateTimeInterface) ; => true
```

### Map to typed object and back

`hydrate` builds a typed PHP object from a Phel map without calling the constructor, like an ORM rehydrating an entity. `bean` reads an object's public properties back into a map with keyword keys:

<!-- phel-test: skip -->
```phel
;; class App\Point { public int $x; public int $y; }
(def p (hydrate "App\\Point" {:x 1 :y 2})) ; => App\Point instance
(bean p)                                    ; => {:x 1 :y 2}
```

### Native enums and exceptions

`defenum` compiles to a native PHP backed enum, plus a `Status?` predicate. Doctrine and Symfony can use it as a column type:

```phel
(defenum Status :active "active" :inactive "inactive")
;; emits: enum Status: string { case active = "active"; case inactive = "inactive"; }
```

`defexception` defines an exception class with the parent you choose, so framework `catch` blocks match it by type:

```phel
(defexception NotFound \RuntimeException)

(try
  (throw (NotFound "missing"))
  (catch \RuntimeException e (.getMessage e))) ; => "missing"
```

`phel.reflect` reads PHP 8 attributes (`class-attributes`, `method-attributes`, `property-attributes`) and converts enum cases to keywords and back (`enum-values`, `enum->keyword`, `keyword->enum`). See the [reflect API](/documentation/reference/api/reflect).

### Magic methods on structs

A `defstruct` is a real PHP class. An inline `:php` block adds magic methods such as `__toString` or `__invoke`. See [Structs](/documentation/language/data-structures/#structs):

```phel
(defstruct money [cents]
  :php
  (__toString [this] (str "$" (/ (get this :cents) 100))))

(php/strval (money 500)) ; => "$5"
```

## Catch PHP exceptions

PHP exceptions reach Phel unchanged. Catch them by class name, or catch `\Throwable` for anything:

```phel
(try
  (php/intdiv 1 0)
  (catch \DivisionByZeroError e
    (.getMessage e)))
; => "Division by zero"
```

`finally`, `ex-info` and re-throwing are on [Error Handling](/documentation/language/error-handling/).

## `__DIR__` and `__FILE__`

`__DIR__` and `__FILE__` point to your `.phel` source file, not to the generated PHP. `*file*` holds the same absolute path as `__FILE__`:

<!-- phel-test: skip -->
```phel
(php/file_get_contents (str __DIR__ "/data.json"))
```

The compiler fixes these paths at compile time. A `phel build` on a CI machine keeps the CI machine's paths. If the build output moves to another machine, resolve files from a root directory you pass in at runtime.

## Calling Phel from PHP

To use Phel inside an existing PHP app, generate PHP wrapper classes with `phel export`, or call a function by name with `PhelCallerTrait`.

### Using the `export` command

Mark each function to export with `:export` metadata:

```phel
(defn adder
  {:export true}
  [a b c]
  (+ a b c))
```

Set `withExportFromDirectories`, `withExportNamespacePrefix` and `withExportTargetDirectory` in `phel-config.php` (see [Configuration](/documentation/reference/configuration/#full-reference)), then run `vendor/bin/phel export`. Each namespace becomes a PHP class with one static method per exported function. From the [CLI skeleton example](https://github.com/phel-lang/cli-skeleton/blob/main/example/using-exported-phel-function.php):

```php
<?php declare(strict_types=1);

use Phel\Phel;
use PhelGenerated\CliSkeleton\Core\Adder;

$projectRootDir = dirname(__DIR__);
require $projectRootDir . '/vendor/autoload.php';

Phel::run($projectRootDir, 'cli-skeleton.core.adder'); // loads the namespace

echo Adder::adder(1, 2, 3); // 6
```

### Manually

`PhelCallerTrait` calls any Phel function from a PHP class:

```php
<?php
use Phel\Interop\PhelCallerTrait;

class MyExistingClass {
  use PhelCallerTrait;

  public function myExistingMethod(...$arguments) {
    return $this->callPhel('my.phel.namespace', 'phel-function-name', ...$arguments);
  }
}
```

### Typed and annotated output

When a framework needs typed PHP, add metadata such as `^{:tag T}`, `^{:php/attr [...]}`, `^{:php/doc "..."}` or `^:php/readonly`. Forms without it compile as before. The full table and a Doctrine entity example are in [Typed PHP from Phel definitions](/documentation/web/framework-integration/#typed-php-from-phel-definitions).

Every `php/*` builtin is listed in the [PHP API reference](/documentation/reference/api/php).
