+++
title = "Coming from Clojure"
weight = 4
+++

If you already know Clojure, you will feel right at home in Phel. Phel is a functional Lisp that compiles to PHP, directly inspired by Clojure (and Janet). It brings persistent data structures, immutability by default, and a functional-first philosophy to the PHP ecosystem. This guide highlights what transfers directly, what differs, and where Phel adds capabilities unique to its PHP target.

## What Feels Familiar

Most of your Clojure intuition carries over unchanged.

**Core forms** -- `def`, `defn`, `let`, `fn`, `if`, `when`, `cond`, `case`, `do`, `loop`/`recur` all work the way you expect.

**Persistent data structures** -- Vectors, maps, and sets use the same algorithms (HAMTs and similar structures) and the same core functions:

```phel
(def v [1 2 3])
(conj v 4)              # => [1 2 3 4]

(def m {:name "Alice" :age 30})
(assoc m :role :admin)  # => {:name "Alice" :age 30 :role :admin}
(get m :name)           # => "Alice"
(:name m)               # => "Alice" (keywords are functions)

(def s #{1 2 3})
(conj s 4)              # => #{1 2 3 4}
```

**Threading macros** -- `->`, `->>`, and `as->` work exactly as in Clojure:

```phel
(->> users
     (filter :active)
     (map :name)
     (into #{}))
```

**Destructuring** -- Both sequential and associative destructuring work in `let`, `fn`, `defn`, and `loop`:

```phel
(let [[a b & rest] [1 2 3 4 5]]
  rest) # => (3 4 5)

(let [{:name name :age age} {:name "Alice" :age 30}]
  (str name " is " age)) # => "Alice is 30"
```

**Higher-order functions** -- `map`, `filter`, `reduce`, `some`, `every?`, `comp`, `partial`, `apply`, and friends are all present:

```phel
(map inc [1 2 3])          # => (2 3 4)
(filter even? [1 2 3 4])   # => (2 4)
(reduce + 0 [1 2 3 4 5])   # => 15
```

**Lazy sequences** -- Since v0.25.0, Phel has full lazy sequence support. Core functions like `map`, `filter`, `take`, `drop`, `concat`, `mapcat`, `interleave`, and `partition` all return lazy sequences. Infinite sequences work too:

```phel
(take 5 (iterate inc 0))       # => (0 1 2 3 4)
(take 7 (cycle [1 2 3]))       # => (1 2 3 1 2 3 1)
(take 5 (repeatedly |(php/rand 1 100)))
(->> (range) (filter even?) (take 5)) # => (0 2 4 6 8)
```

Phel also provides `lazy-seq` and `lazy-cat` macros for building custom lazy sequences, plus `doall`, `dorun`, and `realized?` for controlling realization. Lazy file I/O is available through `line-seq`, `file-seq`, `read-file-lazy`, and `csv-seq`.

**Namespaces with `:require`** -- The module system uses `:require` for Phel modules and supports `:as` and `:refer`, just like Clojure.

**REPL-driven development** -- Phel ships with a REPL that supports `doc`, inline `require`, and multiline expressions. See the [REPL](/documentation/repl) page.

**Macros** -- `defmacro`, quote, syntax-quote, unquote, and unquote-splicing are all available. `defn` is itself a macro, just like in Clojure. See [Macros](/documentation/macros).

For full reference on data structures, see [Data Structures](/documentation/data-structures). For function definitions and recursion, see [Functions and Recursion](/documentation/functions-and-recursion).

## Key Differences

These are the conceptual differences that matter most day-to-day.

### No JVM -- PHP is the runtime

Phel compiles to PHP and runs on the PHP interpreter. There is no JVM, no classpath, no JAR files. Your dependency manager is Composer, not deps.edn or Leiningen.

### No protocols -- use interfaces instead

Phel does not have Clojure-style protocols or `defrecord`. Instead it provides `definterface` and `defstruct`. Structs can implement one or more interfaces:

```phel
(definterface Greetable
  (greet [this]))

(defstruct person [name]
  Greetable
  (greet [this] (str "Hello, " name)))

(greet (person "Alice")) # => "Hello, Alice"
```

See [Interfaces](/documentation/interfaces) for the full reference.

### No multimethods

There is no `defmulti` / `defmethod`. Use `cond`, `case`, or interfaces to achieve dispatch.

### No atoms/agents/refs -- use `var`

Phel provides a single mutable state primitive: `var`. It works similarly to a Clojure atom:

```phel
(def counter (var 0))
(swap! counter inc)   # counter is now 1
(deref counter)       # => 1
(set! counter 42)     # direct reset
```

There are no agents, refs, or STM. See [Global and Local Bindings](/documentation/global-and-local-bindings) for details.

### No spec

There is no built-in spec or schema system. Validate data with predicates and `cond` or reach for a PHP validation library through interop.

### Truthiness

This is the same as Clojure -- only `false` and `nil` are falsy. `0`, `""`, and `[]` are all truthy. If you have been writing Clojure this is exactly what you expect, but it differs from PHP's truthiness rules. See [Truth and Boolean Operations](/documentation/truth-and-boolean-operations).

### No reader macros

Phel does not support custom reader macros. The short anonymous function syntax uses `|` instead of `#()`, and tagged literals are not available. The `#_` form for commenting out expressions is supported.

## Syntax Differences

This section shows Clojure and Phel side by side for the constructs that differ syntactically.

### Namespace declaration

Namespaces use `\` as the separator (following PHP conventions) instead of `.`:

```clojure
;; Clojure
(ns myapp.users
  (:require [myapp.db :as db]
            [clojure.string :as str]))
```

```phel
# Phel
(ns myapp\users
  (:require myapp\db :as db))
```

Key differences:
- `\` instead of `.` as separator
- No vector wrapping around each require clause
- `:use` imports PHP classes (separate from `:require` for Phel modules)
- `:refer` works the same way: `(:require myapp\db :refer [query])`

See [Namespaces](/documentation/namespaces) for the full reference.

### Keywords

Keywords look the same:

```clojure
;; Clojure
:name
:my-key
::namespaced-key
```

```phel
# Phel
:name
:my-key
::namespaced-key
```

Keywords work as functions on maps in both languages: `(:name user)`.

### String concatenation and formatting

Phel uses `str` for concatenation (same as Clojure) and `format` for sprintf-style formatting:

```clojure
;; Clojure
(str "Hello, " name "!")
(format "Hello, %s! You are %d." name age)
```

```phel
# Phel
(str "Hello, " name "!")
(format "Hello, %s! You are %d." name age)
```

### Anonymous functions

The full `fn` form works identically. The short form uses `|` with `$` parameters instead of `#()` with `%` parameters:

```clojure
;; Clojure
(fn [x] (* x 2))
#(* % 2)
#(+ %1 %2)
```

```phel
# Phel
(fn [x] (* x 2))
|(* $ 2)
|(+ $1 $2)
```

See [Functions and Recursion](/documentation/functions-and-recursion) for multi-arity functions, variadic parameters, and `recur`.

### Maps

Maps use `{}` in both languages. Keyword keys are idiomatic:

```clojure
;; Clojure
{:name "Alice" :age 30}
(get user :name)
(:name user)
(assoc user :role :admin)
```

```phel
# Phel
{:name "Alice" :age 30}
(get user :name)
(:name user)
(assoc user :role :admin)
```

The syntax and functions are the same. Phel maps also support any hashable type as keys, including vectors and other maps.

### PHP interop (replaces Java interop)

Where Clojure has Java interop, Phel has PHP interop using the `php/` prefix:

```clojure
;; Clojure (Java interop)
(System/currentTimeMillis)
(.toUpperCase "hello")
(Math/pow 2 10)
```

```phel
# Phel (PHP interop)
(php/time)
(php/strtoupper "hello")
(php/pow 2 10)
```

Any PHP function is callable by adding the `php/` prefix. See [PHP Interop](/documentation/php-interop) for the full reference.

### Printing

Use `println` for output with a newline, `print` without:

```clojure
;; Clojure
(println "Hello, world!")
(pr-str {:a 1})
```

```phel
# Phel
(println "Hello, world!")
(str {:a 1})
```

### Comments

Phel uses `#` or `;` for line comments (both work). Block comments use `#| ... |#`:

```clojure
;; Clojure
;; line comment
(comment (+ 1 2))
```

```phel
# Phel
# line comment
; also a line comment
#| block
   comment |#
(comment (+ 1 2))
```

## PHP Interop (Your New Superpower)

PHP interop is Phel's equivalent of Clojure's Java interop. The `php/` prefix gives you access to the entire PHP ecosystem.

### Calling PHP functions

```phel
(php/strlen "hello")             # => 5
(php/array_reverse [3 1 2])     # PHP array_reverse
(php/date "Y-m-d")              # => "2024-01-15"
(php/json_encode (to-php-array {:a 1}))
```

### Creating objects

```phel
(ns my\app
  (:use \DateTimeImmutable)
  (:use \PDO))

(def now (php/new DateTimeImmutable))
(def db (php/new PDO "sqlite::memory:"))
```

### Method calls

```phel
# Instance methods
(php/-> now (format "Y-m-d"))

# Chaining (like Clojure's doto but for methods)
(php/-> (php/new DateTimeImmutable "2024-01-15")
        (modify "+1 month")
        (format "Y-m-d"))
```

### Static methods and constants

```phel
(php/:: DateTimeImmutable ATOM)
(php/:: DateTimeImmutable (createFromFormat "Y-m-d" "2024-03-22"))
```

### PHP array access

When working with PHP arrays (not Phel data structures), use `php/aget` and `php/aset`:

```phel
(def config (php/json_decode (php/file_get_contents "config.json") true))
(php/aget config "database")
(php/aget-in config ["database" "host"])
```

For the complete interop reference, see [PHP Interop](/documentation/php-interop).

## What You Will Miss (And Workarounds)

### Protocols

Use `definterface` + `defstruct` instead of `defprotocol` + `defrecord`. The pattern is similar but interfaces must be implemented on structs, not extended to existing types after the fact. See [Interfaces](/documentation/interfaces).

### CIDER / Calva / nREPL

Editor tooling is simpler than the Clojure ecosystem. There are extensions for [VS Code, PhpStorm, Emacs, and Vim](/documentation/editor-support), with syntax highlighting and basic REPL integration. There is no nREPL protocol.

### ClojureScript

Phel targets PHP only. There is no browser/JavaScript target.

### deps.edn / Leiningen

Use Composer for dependency management. Your `composer.json` replaces `deps.edn`:

```json
{
  "require": {
    "phel-lang/phel-lang": "^0.29"
  }
}
```

### core.async / concurrency primitives

PHP's execution model is request-based, not long-running. There is no `core.async`, no channels, no CSP. For concurrent work, use PHP's queue systems or process managers through interop.

## What You Will Gain

### Cheap, ubiquitous hosting

PHP runs on virtually every web host, including shared hosting plans that cost a few dollars per month. No need for a JVM-capable server.

### Simpler deployment

No JVM startup, no heap tuning, no GC configuration. Deploy Phel the same way you deploy any PHP application -- upload files or `composer install` on the server.

### Fast startup time

PHP processes start in milliseconds, not seconds. No JVM warmup. This makes CLI tools and short-lived scripts practical.

### The PHP ecosystem

Decades of battle-tested libraries are one `composer require` away: WordPress, Laravel, Symfony components, Guzzle for HTTP, PHPUnit, Doctrine for database access, and thousands more. All of these are callable from Phel through `php/` interop.

### Easy shared hosting

Many organizations already run PHP infrastructure. Phel lets you bring functional programming and Lisp into environments where deploying a JVM is not an option.

## Quick Reference: Clojure to Phel

| Clojure | Phel | Notes |
|---------|------|-------|
| `(ns foo.bar)` | `(ns foo\bar)` | `\` separator instead of `.` |
| `(:require [foo.bar :as b])` | `(:require foo\bar :as b)` | No vector wrapping |
| `#(* % 2)` | `\|(* $ 2)` | `\|` short fn, `$` instead of `%` |
| `(atom 0)` | `(var 0)` | Single mutable state primitive |
| `@my-atom` | `(deref my-var)` | Dereference |
| `(reset! a v)` | `(set! a v)` | Direct set |
| `(swap! a f)` | `(swap! a f)` | Same |
| `(.method obj)` | `(php/-> obj (method))` | Instance method call |
| `(Class/static)` | `(php/:: Class (static))` | Static method call |
| `(new Class)` | `(php/new Class)` | Instantiation |
| `(defprotocol P)` | `(definterface P)` | Interface instead of protocol |
| `(defrecord R)` | `(defstruct R)` | Struct instead of record |
| `(lazy-seq ...)` | `(lazy-seq ...)` | Same -- available since v0.25 |
| `;; comment` | `# comment` | `#` or `;` both work |

Welcome to the PHP side of Lisp. The parentheses are the same -- the runtime just happens to be PHP.
