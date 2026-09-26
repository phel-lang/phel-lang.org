+++
title = "PHP Interop"
weight = 50
description = "Call PHP functions, build objects, work with PHP arrays, and catch PHP exceptions from Phel."
+++

Phel runs on PHP. Every PHP function, class and Composer package is one form away.

This page covers the forms: calling functions, building objects, reading and writing PHP arrays, catching PHP exceptions, and calling Phel back from PHP.

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

Namespaced PHP functions use full path after `php/`. Three equivalent forms accepted (last two are backslash-free):

<!-- phel-test: skip -->
```phel
(php/Foo\Bar\baz)      ; classic backslash form
(php/Foo.Bar/baz)      ; dot-separated, slash before fn name
(php/Foo.Bar.baz)      ; fully dot-separated

(php/Amp.trapSignal [php/SIGINT php/SIGTERM])
```

To bind one to a name, see [PHP functions as values](#php-first-class-callable).

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

## Interop forms

Clojure-style forms cover every class member. They are the only spelling: the
old `php/new`, `php/->` and `php/::` are rejected as source since Phel 0.52
(error `PHEL012`), though the compiler still emits them internally.

| Form                      | PHP equivalent            |
|---------------------------|---------------------------|
| `(ClassName. args)`       | `new ClassName(args)`     |
| `(new ClassName args)`    | `new ClassName(args)`     |
| `(.method obj args)`      | `$obj->method(args)`      |
| `(.-field obj)`           | `$obj->field`             |
| `(set! (.-field obj) v)`  | `$obj->field = v`         |
| `(ClassName/method args)` | `ClassName::method(args)` |
| `ClassName/MEMBER`        | `ClassName::MEMBER`       |

The sections below show each row in use.

## Class instantiation {#php-class-instantiation}

Two equivalent forms. Prefer `ClassName.` and import the class with `:use`, so you never repeat its namespace:

```phel
(ns my.module
  (:use DateTime DateTimeImmutable))

(DateTime.)              ; => DateTime instance (ClassName. shorthand)
(DateTime. "now")        ; => DateTime instance with arg
(new DateTime)           ; also valid

(new "\\DateTimeImmutable") ; instantiate from string (dynamic)
```

## Method and property call

<!-- phel-test: skip -->
```phel
(.methodname object expr*)
(.-property object)
```

Calls method or accesses property. Both `methodname` and `property` are part of the head symbol, not evaluated values.

Thread with `->` to chain: each element evaluates on the result of the previous one, methods and properties alike.

<!-- phel-test: skip -->
```phel
(ns my.module
  (:use DateInterval)
  (:use DateTimeImmutable)
  (:use stdClass))

(def di (DateInterval. "PT30S"))

(.format di "%s seconds")          ; => "30 seconds"
(.-s di)                           ; => 30

;; Chain multiple calls:
;; (new DateTimeImmutable("2024-03-10"))->modify("+1 day")->format("Y-m-d")
(-> (DateTimeImmutable. "2024-03-10")
    (.modify "+1 day")
    (.format "Y-m-d"))

;; Chains mixing methods and properties thread the same way:
(-> user (.-profile) (.getDisplayName))

;; Nested property access:
(def address (stdClass.))
(def user    (stdClass.))
(set! (.-city address) "Berlin")
(set! (.-address user) address)
(.-city (.-address user)) ; => "Berlin"
```

{% clojure_note() %}
Same spelling as Clojure: `.method`, `.-field`, `Class/member`, and `->` for chaining.
{% end %}

## Static method and property {#php-static-method-and-property-call}

<!-- phel-test: skip -->
```phel
(class/methodname expr*)
class/PROPERTY
```

Same as above, but static.

```phel
(ns my.module
  (:use DateTimeImmutable))

DateTimeImmutable/ATOM                                    ; => "Y-m-d\TH:i:sP"

(DateTimeImmutable/createFromFormat "Y-m-d" "2020-03-22")
```

## Named arguments

PHP 8 named arguments are passed after a `:&` marker as `:key value` pairs. Works in constructors, instance methods, and static calls. Keyword keys map to the PHP parameter names; order is then irrelevant.

```phel
(let [dt (\DateTime/createFromFormat :& :format "Y-m-d" :datetime "2026-06-06")]
  (.format dt "Y-m-d")) ; => "2026-06-06"
```

{% php_note() %}
```php
// PHP
\DateTime::createFromFormat(format: "Y-m-d", datetime: "2026-06-06");
new \App\Mailer(host: "smtp", port: 587);
```

<!-- phel-test: skip -->
```phel
;; Phel
(\DateTime/createFromFormat :& :format "Y-m-d" :datetime "2026-06-06")
(new \App\Mailer :& :host "smtp" :port 587)
```
{% end %}

## By-reference arguments

Some PHP functions write through a `&$ref` parameter (`preg_match`, `sort`, ...). Wrap a **local** binding in `php/ref` to pass it by reference; the local must be `let`-bound (a top-level `def` is not a PHP variable).

```phel
(let [subject "order-42"
      matches (php/array)]
  (php/preg_match "/(\d+)/" subject (php/ref matches))
  (php/aget matches 1)) ; => "42"
```

`php/ref` also works inside method and static calls.

## Set object properties {#php-set-object-properties}

<!-- phel-test: skip -->
```phel
(set! (.-property object) value)
(set! class/property value)
```

Set value on class/object property.

```phel
(def x (stdclass.))
(set! (.-name x) "foo")
```

{% php_note() %}
`set!` is the Phel equivalent of PHP's property assignment:

```php
// PHP
$x = new stdClass();
$x->name = "foo";

// Phel
(def x (stdclass.))
(set! (.-name x) "foo")
```

**Note:** This mutates the PHP object. When possible, use Phel's immutable data structures instead.
{% end %}

## Type conversions

Phel values and PHP values cross the boundary automatically for scalars (int, float, string, bool, nil). Collections differ: Phel uses immutable vectors/maps, PHP uses arrays. Convert explicitly when a library needs one or the other.

| Function | Direction | Example | Result |
|---|---|---|---|
| `to-array` | Phel vector/map to PHP array | `(to-array [1 2 3])` | `<PHP-Array [1, 2, 3]>` |
| `phel->php` | deep Phel to PHP (nested) | `(phel->php {:a 1 :b 2})` | `<PHP-Array ["a":1, "b":2]>` |
| `php->phel` | deep PHP to Phel (nested) | `(php->phel (php/array 1 2 3))` | `[1 2 3]` |
| `php-array-to-map` | PHP array to Phel map | `(php-array-to-map #php {"a" 1 "b" 2})` | `{"a" 1, "b" 2}` |

```phel
(to-array [1 2 3])                 ; => <PHP-Array [1, 2, 3]>
(php->phel (php/array 1 2 3))       ; => [1 2 3]
(php-array-to-map #php {"a" 1})     ; => {"a" 1}
(phel->php {:a 1})                  ; => <PHP-Array ["a":1]>
```

Use `#php [...]` and `#php {...}` reader macros to write PHP array literals directly.

## Checking types

`php/instanceof` tests an object against a PHP class or interface:

```phel
(php/instanceof (new \DateTime) \DateTimeInterface) ; => true
```

For Phel's own values use the core predicates (`int?`, `string?`, `map?`, `vector?`, ...).

## PHP functions as values {#php-first-class-callable}

A `php/`-prefixed function is a first-class value. Bind it, pass it, or spread arguments into it with `apply`:

```phel
(let [upcase php/strtoupper]
  (map upcase ["a" "b"]))        ; => ("A" "B")

(apply php/max [3 7 2])          ; => 7
```

Capture a namespaced PHP function into a Phel alias the same way:

<!-- phel-test: skip -->
```phel
(def trap-signal php/\Amp.trapSignal)
(trap-signal [2 15])
```

`php/callable` builds a native PHP first-class callable, like PHP's `strtoupper(...)`, without an `fn` wrapper. It takes a function, a static method, or an instance method:

```phel
(map (php/callable \strtoupper) ["a" "b"]) ; => ("A" "B")

(let [parse (php/callable \DateTimeImmutable createFromFormat)]
  (.format (parse "Y-m-d" "2026-06-06") "Y-m-d")) ; => "2026-06-06"
```

## PHP arrays

Seven `php/` forms read and write a PHP array in place. Use them only on PHP arrays. Phel vectors and maps are immutable and have their own functions, in the last column.

| Form                       | PHP equivalent          | On Phel data |
|----------------------------|-------------------------|--------------|
| `(php/aget arr k)`         | `$arr[k] ?? null`       | `get`        |
| `(php/aget-in arr path)`   | `$arr[a][b] ?? null`    | `get-in`     |
| `(php/aset arr k v)`       | `$arr[k] = v`           | `assoc`      |
| `(php/aset-in arr path v)` | `$arr[a][b] = v`        | `assoc-in`   |
| `(php/apush arr v)`        | `$arr[] = v`            | `conj`       |
| `(php/aunset arr k)`       | `unset($arr[k])`        | `dissoc`     |
| `(php/aunset-in arr path)` | `unset($arr[a][b])`     | `dissoc-in`  |

`path` is a vector of keys and indexes.

### Get PHP array value

A missing key returns `nil`, at any depth.

```phel
(def users
  #php {"users" #php {0 #php {"name" "Alice"}
                      1 #php {"name" "Bob"}}})

(php/aget (php/array "a" "b" "c") 1)   ; => "b"
(php/aget (php/array "a" "b" "c") 5)   ; => nil
(php/aget-in users ["users" 1 "name"]) ; => "Bob"
(php/aget-in users ["users" 7 "name"]) ; => nil
```

### Set PHP array value

`php/aset-in` creates the missing arrays along the path.

```phel
(def data (php/array))
(php/aset data "id" 42)
(php/aset-in data ["user" "profile" "name"] "Charlie")
(php/aget-in data ["user" "profile" "name"]) ; => "Charlie"
```

### Append PHP array value

```phel
(def xs (php/array))
(php/apush xs "first")
(php/apush xs "second")
xs ; => <PHP-Array ["first", "second"]>
```

### Unset PHP array value

`php/aunset-in` removes only the last key. The parent arrays stay, even when empty.

```phel
(def data #php {"id" 42 "user" #php {"profile" #php {"name" "Dora"}}})
(php/aunset data "id")
(php/aunset-in data ["user" "profile" "name"])
data ; => <PHP-Array ["user":<PHP-Array ["profile":<PHP-Array []>]>]>
```

## `__DIR__`, `__FILE__`, `*file*`

The compiler replaces PHP's `__DIR__` and `__FILE__` with the location of your `.phel` source file. `*file*` holds the same absolute path.

```phel
(println __DIR__)              ; directory of this .phel file
(println __FILE__)             ; absolute path of this .phel file
(println (php/dirname *file*)) ; same as __DIR__
(println *file*)               ; same as __FILE__
```

All four are baked in at compile time. A `phel build` on a CI machine keeps the CI machine's paths in the output. When the build moves to another machine, resolve files from a root you pass in at runtime.

{% php_note() %}
In PHP, `__DIR__` is the directory of the running `.php` file. In Phel it is the directory of the `.phel` source, not of the generated PHP:

```php
// PHP
__DIR__   // directory of this .php file

// Phel
__DIR__   // directory of the .phel source, fixed at compile time
*file*    // absolute path of the .phel source
```
{% end %}

## Map to typed object and back

`hydrate` and `bean` bridge a Phel map and a typed PHP object both ways: `hydrate` rebuilds an instance from a map (skipping the constructor, like an ORM rehydrating an entity), and `bean` reads an object's public properties back into a map with keyword keys.

<!-- phel-test: skip -->
```phel
;; class App\Point { public int $x; public int $y; }
(def p (hydrate "App\\Point" {:x 1 :y 2})) ; => App\Point instance
(bean p)                                    ; => {:x 1 :y 2}
```

To read PHP 8 attributes and bridge native enums, see `phel.reflect`
(`class-attributes`, `enum->keyword`, ...) in the
[API reference](/documentation/reference/api/reflect).

## Magic methods on structs

A `defstruct` is a real PHP class, so it can expose magic methods (`__invoke`, `__toString`, `__get`, ...) through an inline `:php` block. See [Structs](/documentation/language/data-structures/#structs) for the full form.

```phel
(defstruct money [cents]
  :php
  (__toString [this] (str "$" (/ (get this :cents) 100))))

(php/strval (money 500)) ; => "$5"
```

## Native enums and exceptions

`defenum` compiles to a native PHP backed enum (e.g. for Doctrine/Symfony columns), plus a `Name?` predicate. The enum is a real PHP type: consume it from PHP, reference it by full name (`\my\ns\Status`), or bridge cases to keywords with `phel.reflect` (see [Reflection](#reflection-attributes-and-enums)).

```phel
(defenum Status :active "active" :inactive "inactive")
;; emits: enum Status: string { case active = "active"; case inactive = "inactive"; }
```

`defexception` defines an exception extending a chosen parent, so framework `catch` blocks match it by type:

```phel
(defexception NotFound \RuntimeException)

(try
  (throw (NotFound "missing"))
  (catch \RuntimeException e (.getMessage e))) ; => "missing"
```

## Reflection: attributes and enums

`phel.reflect` reads PHP 8 attributes and bridges native enums (including `defenum` output) to keywords and back. Pass classes/enums by full name.

```phel
(ns my-app
  (:require phel.reflect :as reflect))
```

Attributes come back as `{:name :args}` maps:

| Function | Reads |
|---|---|
| `class-attributes` | attributes on a class |
| `method-attributes` | attributes on a method |
| `property-attributes` | attributes on a property |

<!-- phel-test: skip -->
```phel
;; #[Tag('x')] class Thing {}
(reflect/class-attributes \Demo\Thing)
; => [{:name "Demo\\Tag" :args {0 "x"}}]
```

Enum bridge:

| Function | Does |
|---|---|
| `enum-values` | all cases as keywords |
| `enum->keyword` | one case to its keyword |
| `keyword->enum` | keyword back to the case |

<!-- phel-test: skip -->
```phel
;; enum Suit: string { case Hearts = 'H'; case Spades = 'S'; }
(reflect/enum-values \Demo\Suit)               ; => [:Hearts :Spades]
(reflect/enum->keyword \Demo\Suit/Hearts) ; => :Hearts
(reflect/keyword->enum \Demo\Suit :Spades)     ; => Suit::Spades
```

## Catching PHP exceptions

PHP functions and methods throw native exceptions, and they cross the interop boundary unchanged. Catch them with `try`/`catch`, matching on the PHP class name. Catch `\Throwable` to handle anything.

```phel
(try
  (php/intdiv 1 0)
  (catch \DivisionByZeroError e
    (.getMessage e)))
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

Use this to bring Phel into an existing PHP app. Two ways: call a Phel function by name with `PhelCallerTrait`, or generate PHP wrapper classes with `phel export`.

This is the export route, taken from the CLI skeleton's [using-exported-phel-function.php](https://github.com/phel-lang/cli-skeleton/blob/main/example/using-exported-phel-function.php). Run `vendor/bin/phel export` first to generate the wrapper classes:

```php
<?php declare(strict_types=1);

use Phel\Phel;
use PhelGenerated\CliSkeleton\Core\Adder;

$projectRootDir = dirname(__DIR__);

require $projectRootDir . '/vendor/autoload.php';

Phel::run($projectRootDir, 'cli-skeleton.core.adder');

$result = Adder::adder(1, 2, 3);

echo 'Result = ' . $result . PHP_EOL; // Result = 6
```

`Phel::run()` loads the namespace. The exported functions are static methods on the generated class.

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

Set the `withExportFromDirectories`, `withExportNamespacePrefix`, and `withExportTargetDirectory` options in `phel-config.php` first: see [Configuration](/documentation/configuration/#full-reference).

Mark a function exported with metadata:

```phel
(defn my-function
  {:export true}
  [a b]
  (+ a b))
```

`phel export` then generates a wrapper class in the target dir (here `src/PhelGenerated`). Use it from PHP to call Phel functions.

### Typed and annotated output

When the generated PHP must satisfy a framework's type expectations, opt-in metadata (`^{:tag T}`, `^{:php/attr [...]}`, `^{:php/doc "..."}`, `^:php/readonly`, and more) enriches it; untagged forms are unchanged. For the full metadata table and a Doctrine-entity `defstruct` example, see [Typed PHP from Phel definitions](/documentation/web/framework-integration/#typed-php-from-phel-definitions).

## Next steps

- [Error Handling](/documentation/language/error-handling/): `try`, `catch`, `finally`, `ex-info`.
- [Configuration](/documentation/configuration/): `withExport*` options for `phel export`.
- [PHP API reference](/documentation/reference/api/php): every `php/*` builtin.
- [Rosetta Stone](/documentation/guides/rosetta-stone/): PHP and Phel side by side, interop included.
