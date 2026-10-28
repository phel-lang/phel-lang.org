+++
title = "PHP Interop"
weight = 11
description = "Call PHP functions, create objects, convert PHP arrays, use Composer packages, and call Phel back from PHP."
aliases = ["/documentation/php-interop/"]

[extra]
difficulty = "intermediate"
practice = ["php-interop"]
+++

Phel compiles to PHP, so every PHP function, class and Composer package is available.

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

For a namespaced function, write the namespace with dots: `(php/Amp.trapSignal [php/SIGINT])` calls `Amp\trapSignal(...)`.

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

Global constants and superglobals use the same `php/` prefix: `php/PHP_INT_MAX`, `(get php/$_SERVER "REQUEST_METHOD")`. For command-line arguments use `*argv*`, not `$argv`.

<a id="by-reference-arguments"></a>

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

To make your own Phel function take an argument by reference, like PHP's `&$arr`, mark the parameter `^:reference`: `(defn add-to-array [^:reference arr] (php/apush arr 10))`. It works on plain parameters only, not with destructuring. Prefer returning a new value.

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

## Use Composer packages

Install a package with `composer require` as usual. Phel loads `vendor/autoload.php`, so its classes are ready to use. Import them with `:use`, writing the namespace with dots:

```phel
(ns my.module
  (:use Symfony.Component.String.UnicodeString))

(-> (UnicodeString. "hello world")
    (.camel)
    (.toString))   ; => "helloWorld"
```

For framework setups (Symfony, Laravel), see [Framework integration](/documentation/web/framework-integration/).

## Types, objects and enums

`php/instanceof` checks an object against a class or interface. For Phel values use the core predicates (`int?`, `string?`, `map?`, ...):

```phel
(php/instanceof (DateTime.) \DateTimeInterface) ; => true
```

### Map to typed object and back

`hydrate` builds a typed PHP object from a Phel map without calling the constructor: `(hydrate "App\\Point" {:x 1 :y 2})`. `bean` reads an object's public properties back into a map with keyword keys.

### Native enums and exceptions

`defenum` compiles to a native PHP backed enum, plus a `Status?` predicate. Doctrine and Symfony can use it as a column type:

```phel
(defenum Status :active "active" :inactive "inactive")
;; emits: enum Status: string { case active = "active"; case inactive = "inactive"; }
```

`defexception` defines a PHP exception class with the parent you choose: see [Error Handling](/documentation/language/error-handling/#custom-exception-types-with-defexception). `phel.reflect` reads PHP 8 attributes and converts enum cases to keywords and back: see the [reflect API](/documentation/reference/api/reflect).

A `defstruct` is a real PHP class. A `:php` block adds magic methods such as `__toString`: see [Structs](/documentation/language/data-structures/#structs).

## Catch PHP exceptions

PHP exceptions reach Phel unchanged. Catch them by class name, or `\Throwable` for anything. See [Error Handling](/documentation/language/error-handling/).

## `__DIR__` and `__FILE__`

`__DIR__` and `__FILE__` (and `*file*`) point to your `.phel` source file, not to the generated PHP. The compiler fixes these paths at compile time. A `phel build` on a CI machine keeps the CI machine's paths. If the build output moves to another machine, resolve files from a root directory you pass in at runtime.

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

Run `vendor/bin/phel export` (export options are in [Configuration](/documentation/reference/configuration/#full-reference)). Each namespace becomes a PHP class with one static method per exported function, so PHP calls `Adder::adder(1, 2, 3)` after `Phel::run(...)` loads the namespace. See the [CLI skeleton example](https://github.com/phel-lang/cli-skeleton/blob/main/example/using-exported-phel-function.php) and [Framework integration](/documentation/web/framework-integration/), which also covers typed PHP output.

Without export, a PHP class can `use Phel\Interop\PhelCallerTrait` and call `$this->callPhel('my.phel.namespace', 'phel-function-name', ...$arguments)`.

Every `php/*` builtin is listed in the [PHP API reference](/documentation/reference/api/php).
