+++
title = "Coming from Clojure"
weight = 2
description = "What transfers from Clojure to Phel, what differs, and what Phel adds, with a side-by-side form mapping"
aliases = ["/documentation/coming-from-clojure"]
+++

Phel is a functional Lisp on PHP, inspired by Clojure (and Janet). Persistent data structures, immutability by default, functional-first. This guide: what transfers, what differs, what Phel adds.

Ships with: protocols, transducers, reader conditionals (`#?()`), regex literals, `ex-info`/`ex-data`, hierarchies (`derive`, `isa?`, `parents`, `ancestors`, `descendants`), core.match-style `match`, `schema`, fiber-based `async`, nREPL/LSP toolchain.

## What feels familiar

Clojure intuition carries over.

**Core forms:** `def`, `defn`, `let`, `fn`, `if`, `when`, `cond`, `case`, `do`, `loop`/`recur`.

**Persistent data structures:** vectors, maps, sets. Same algorithms (HAMTs etc.), same core functions:

```phel
(def v [1 2 3])
(conj v 4)              ; => [1 2 3 4]

(def m {:name "Alice" :age 30})
(assoc m :role :admin)  ; => {:name "Alice" :age 30 :role :admin}
(get m :name)           ; => "Alice"
(:name m)               ; => "Alice" (keywords are functions)

(def s #{1 2 3})
(conj s 4)              ; => #{1 2 3 4}
```

**Threading macros:** `->`, `->>`, `as->` work as in Clojure:

```phel
(def users [{:name "Alice" :active true} {:name "Bob" :active false}])

(->> users
     (filter :active)
     (map :name)
     (into #{}))
```

**Destructuring:** sequential and associative work in `let`, `fn`, `defn`, `loop`:

```phel
(let [[a b & rest] [1 2 3 4 5]]
  rest) ; => [3 4 5]

(let [{:name name :age age} {:name "Alice" :age 30}]
  (str name " is " age)) ; => "Alice is 30"
```

**Higher-order functions:** `map`, `filter`, `reduce`, `some`, `every?`, `comp`, `partial`, `apply`, etc.:

```phel
(map inc [1 2 3])          ; => @[2 3 4]
(filter even? [1 2 3 4])   ; => @[2 4]
(reduce + 0 [1 2 3 4 5])   ; => 15
```

**Lazy sequences:** full support. `map`, `filter`, `take`, `drop`, `concat`, `mapcat`, `interleave`, `partition` return lazy seqs. Infinite seqs work:

```phel
(take 5 (iterate inc 0))       ; => @[0 1 2 3 4]
(take 7 (cycle [1 2 3]))       ; => @[1 2 3 1 2 3 1]
(take 5 (repeatedly #(php/rand 1 100)))
(->> (range) (filter even?) (take 5)) ; => @[0 2 4 6 8]
```

`lazy-seq`, `lazy-cat` build custom lazy seqs. `doall`, `dorun`, `realized?` control realization. Lazy file I/O via `line-seq`, `file-seq`, `read-file-lazy`, `csv-seq`.

**Namespaces:** `:require` for Phel modules, `:as` and `:refer` like Clojure.

**REPL:** supports `doc`, inline `require`, multiline. See [REPL](/documentation/tooling/repl).

**Macros:** `defmacro`, quote, syntax-quote, unquote, unquote-splicing. `defn` is a macro. `defn` supports metadata shorthands: `^:memoize` wraps the body in `memoize`; `^:async` wraps in `async` returning `Amp\Future`. See [Macros](/documentation/language/macros).

Reference: [Data Structures](/documentation/language/data-structures), [Functions and Recursion](/documentation/language/functions-and-recursion).

## Key differences

Day-to-day differences.

### No JVM, PHP runtime

Compiles to PHP, runs on PHP. No JVM, classpath, JARs. Dependency manager is Composer (not deps.edn or Leiningen).

### Protocols

Phel supports Clojure-style protocols with `defprotocol` and `extend-type`. Use `definterface` + `defstruct` for simpler cases where you control the type:

```phel
(definterface Greetable
  (greet [this]))

(defstruct person [name]
  Greetable
  (greet [this] (str "Hello, " name)))

(greet (person "Alice")) ; => "Hello, Alice"
```

See [Interfaces](/documentation/language/interfaces) for the full reference.

### Multimethods

Phel supports Clojure-style `defmulti` / `defmethod` with hierarchy-aware dispatch through the `derive` / `isa?` system:

```phel
(defmulti area :shape)
(defmethod area :circle [{:radius r}] (* 3.14159 r r))
(defmethod area :rectangle [{:width w :height h}] (* w h))
```

### Type tags and inference

`:tag` metadata emits PHP type declarations. Phel also infers return types from primitive operations:

<!-- phel-test: skip -->
```phel
;; Explicit tags
(defn ^int add [^int a ^int b]
  (+ a b))
;; Compiles to: function add(int $a, int $b): int { ... }

;; Nullable type
(defn ^"?string" find-name [^int id]
  ...)

;; ^:memoize wraps the body in memoize automatically
(defn ^:memoize expensive [x]
  (compute x))

;; ^:async wraps the body in async, returning Amp\Future
(defn ^:async fetch [url]
  ...)
```

Inferred tags from tail primitive ops propagate to the PHP signature - you often don't need to annotate at all.

### Numeric tower

Phel ships `BigInt`, `BigDecimal`, and `Ratio` as first-class types:

```phel
1N          ; BigInt literal
1.5M        ; BigDecimal literal
1/2         ; Ratio literal (not a float, exact ratio)

(/ 1 2)     ; => 1/2  (Ratio, exact)
(/ 1.0 2)   ; => 0.5  (float)
(+ 1N 2N)   ; => 3    (BigInt)

;; PHP ints auto-promote to BigInt on overflow
(* 9999999999999999999N 2N) ; stays exact

;; Constructors and predicates
(bigint 42)    ; => 42
(bigdec "1.5") ; => 1.5M
(ratio? 1/2)   ; => true
```

### Atoms only, no agents/refs/STM

`atom` is the only mutable primitive. Works like Clojure's:

```phel
(def counter (atom 0))
(swap! counter inc)   ; counter is now 1
(deref counter)       ; => 1
@counter              ; => 1 (shorthand)
(reset! counter 42)   ; direct reset
```

No agents, refs, STM. See [Global and Local Bindings](/documentation/language/global-and-local-bindings).

### No spec

No `clojure.spec`. Phel ships `phel.schema` for validation, coercion, and generation. See the schema recipe in the [Cookbook](/documentation/guides/cookbook/).

### Truthiness

Same as Clojure: only `false` and `nil` falsy. `0`, `""`, `[]` truthy. Differs from PHP. See [Truthiness](/documentation/language/basic-types/#truthiness).

### Reader conditionals

`#?()` and splicing `#?@()`, using `:phel` and `:default` as platform keys. Enables cross-platform `.cljc` files:

```phel
(def host
  #?(:phel "PHP"
     :default "Unknown"))
```

No custom reader macros. Generic tagged literals (`#uuid`, `#inst`, `#cpp`, etc.) read as tagged-literal nodes. Clojure-style `#(...)` with `%`/`%1`/`%&` works. Phel-only `|(...)` with `$` accepted but deprecated. `#_` to skip a form.

## Syntax differences

Clojure and Phel side by side for syntactically different constructs.

### Namespace declaration

Same `.` separator as Clojure. PHP class FQNs in `:use` use `.`:

```clojure
;; Clojure
(ns myapp.users
  (:require [myapp.db :as db]
            [clojure.string :as str]))
```

```phel
;; Phel
(ns myapp.users
  (:require myapp.db :as db))
```

Differences:
- No vector wrap per require clause
- `:use` for PHP classes; `:require` for Phel modules
- `:refer` same: `(:require myapp.db :refer [query])`
- Backslash form `(ns myapp.db)` still parses for legacy code, warns under `PHEL_WARN_DEPRECATIONS=1`

See [Namespaces](/documentation/language/namespaces).

### Keywords

Same:

```clojure
;; Clojure
:name
:my-key
::namespaced-key
```

```phel
;; Phel
:name
:my-key
::namespaced-key
```

Keywords act as functions on maps in both: `(:name user)`.

### String concatenation and formatting

`str` for concat, `format` for sprintf-style:

```clojure
;; Clojure
(str "Hello, " name "!")
(format "Hello, %s! You are %d." name age)
```

```phel
;; Phel
(def name "Alice")
(def age 30)
(str "Hello, " name "!")
(format "Hello, %s! You are %d." name age)
```

### Anonymous functions

`fn` and `#(...)` with `%` work the same:

```clojure
;; Clojure
(fn [x] (* x 2))
#(* % 2)
#(+ %1 %2)
```

```phel
;; Phel
(fn [x] (* x 2))
#(* % 2)
#(+ %1 %2)
```

Phel accepts legacy `|(...)` with `$`, but `#(...)` is preferred.

See [Functions and Recursion](/documentation/language/functions-and-recursion) for multi-arity, variadic, `recur`.

### Maps

Both use `{}`. Keyword keys idiomatic:

```clojure
;; Clojure
{:name "Alice" :age 30}
(get user :name)
(:name user)
(assoc user :role :admin)
```

```phel
;; Phel
(def user {:name "Alice" :age 30})
(get user :name)
(:name user)
(assoc user :role :admin)
```

Same syntax and functions. Phel maps also accept any hashable type as keys.

### PHP interop (replaces Java interop)

Use `php/` prefix:

```clojure
;; Clojure (Java interop)
(System/currentTimeMillis)
(.toUpperCase "hello")
(Math/pow 2 10)
```

```phel
;; Phel (PHP interop)
(php/time)
(php/strtoupper "hello")
(php/pow 2 10)
```

Any PHP function via `php/` prefix. See [PHP Interop](/documentation/php-interop).

### Printing

`println` adds newline, `print` doesn't:

```clojure
;; Clojure
(println "Hello, world!")
(pr-str {:a 1})
```

```phel
;; Phel
(println "Hello, world!")
(str {:a 1})
```

### Comments

Use `;` and `;;`. Legacy `#` line and `#| ... |#` block comments still read but deprecated. `#_` skips a form:

```clojure
;; Clojure
;; line comment
(comment (+ 1 2))
```

```phel
;; Phel
; line comment
;; standalone comment
#_(comment (+ 1 2))  ; skip the next form
(comment (+ 1 2))
```

## PHP interop

Phel's equivalent of Clojure's Java interop: the `php/` prefix unlocks the whole PHP ecosystem, and the Clojure-style shorthands carry over.

| Clojure                   | Phel                                                            |
|---------------------------|-----------------------------------------------------------------|
| `(Math/pow 2 10)`         | `(php/pow 2 10)` (any PHP function via `php/`)                   |
| `(Classname. args)`       | `(php/new Classname args)`                                      |
| `(.method obj args)`      | `(php/-> obj (method args))` or `(.method obj args)`            |
| `(Classname/method args)` | `(php/:: Classname (method args))` or `(Classname/method args)` |
| array element             | `(php/aget arr key)` (PHP arrays, not Phel data structures)     |

See [PHP Interop](/documentation/php-interop/) for the full reference: functions, objects, method and static calls, constants, and PHP-array access.

## What you'll miss (and workarounds)

### CIDER / Calva / nREPL

Editor tooling covers [VS Code, PhpStorm, Emacs, Vim](/documentation/tooling/editor-support/). Phel ships [nREPL](/documentation/tooling/cli-commands/#nrepl) and [LSP](/documentation/tooling/cli-commands/#lsp) servers, structured stack frames in `EvalError`, stdout capture in `EvalResult`.

### ClojureScript

PHP only. No browser/JavaScript target.

### deps.edn / Leiningen

Use Composer. `composer.json` replaces `deps.edn`:

```json
{
  "require": {
    "phel-lang/phel-lang": "^0.47",
    "php": ">=8.4"
  }
}
```

### core.async / concurrency primitives

PHP is request-based, not long-running. No `core.async`, channels, CSP. Use PHP queues or process managers via interop.

## What you'll gain

### Cheap, ubiquitous hosting

PHP runs on virtually any web host, including shared hosting at a few dollars/month. No JVM-capable server.

### Simpler deployment

No JVM startup, heap tuning, GC config. Deploy like any PHP app: upload files or `composer install`.

### Fast startup

PHP processes start in milliseconds. No JVM warmup. CLI tools and short-lived scripts are practical.

### PHP ecosystem

Decades of battle-tested libraries via `composer require`: WordPress, Laravel, Symfony, Guzzle, PHPUnit, Doctrine, thousands more. All callable via `php/`.

### Shared hosting

Many orgs already run PHP. Bring FP/Lisp into environments where the JVM isn't an option.

## Quick reference: Clojure to Phel

| Clojure                      | Phel                                                      | Notes                                  |
|------------------------------|-----------------------------------------------------------|----------------------------------------|
| `(ns foo.bar)`               | `(ns foo.bar)`                                            | Same separator. PHP FQNs use `.`       |
| `(:require [foo.bar :as b])` | `(:require foo.bar :as b)`                                | No vector wrapping required            |
| `#(* % 2)`                   | `#(* % 2)`                                                | Same. `\|(* $ 2)` legacy, deprecated   |
| `(atom 0)`                   | `(atom 0)`                                                | Same                                   |
| `@my-atom`                   | `@my-atom`                                                | Same                                   |
| `(reset! a v)`               | `(reset! a v)`                                            | Same (`set!` alias removed in 0.36)    |
| `(swap! a f)`                | `(swap! a f)`                                             | Same                                   |
| `#'sym` / `(var sym)`        | `#'sym` / `(var sym)`                                     | First-class `Var` handle               |
| `(alter-var-root #'v f)`     | `(alter-var-root #'v f)`                                  | Same                                   |
| `(with-redefs [v x] ...)`    | `(with-redefs [v x] ...)`                                 | Same. Works for non-dynamic vars       |
| `(binding [*x* v] ...)`      | `(binding [*x* v] ...)`                                   | Var must be `^:dynamic`                |
| `(.method obj)`              | `(.method obj)` or `(php/-> obj (method))`                | Both forms work                        |
| `(Class/staticMethod)`       | `(Class/staticMethod)` or `(php/:: Class (staticMethod))` | Both forms work                        |
| `(new Class)`                | `(Class.)` or `(php/new Class)`                           | `ClassName.` shorthand                 |
| `^int` tag                   | `^int` tag                                                | Emits PHP type declaration             |
| `(memoize f)`                | `^:memoize` on `defn`                                     | Metadata shorthand                     |
| `(defprotocol P)`            | `(defprotocol P)`                                         | Same                                   |
| `(defrecord R)`              | `(defrecord R)` or `(defstruct R)`                        | Both available                         |
| `(lazy-seq ...)`             | `(lazy-seq ...)`                                          | Same                                   |
| `1N` / `1.5M` / `1/2`        | `1N` / `1.5M` / `1/2`                                     | BigInt / BigDecimal / Ratio            |
| `(/ 1 2)` → `1/2` (Ratio)    | `(/ 1 2)` → `1/2` (Ratio)                                 | Same; use `(/ 1.0 2)` for float        |
| `#"regex"`                   | `#"regex"`                                                | Regex literal; `re-find`, `re-matches` |
| `#?(:clj x :default y)`      | `#?(:phel x :default y)`                                  | Reader conditionals                    |
| `(ex-info msg data)`         | `(ex-info msg data)`                                      | Same                                   |
| `(transduce xf f coll)`      | `(transduce xf f coll)`                                   | Same                                   |
| `;; comment`                 | `;; comment`                                              | `;` and `;;` standard                  |

Welcome to the PHP side of Lisp. The parentheses are the same; the runtime just happens to be PHP.

## Next steps

- [Rosetta Stone: PHP to Phel](/documentation/guides/rosetta-stone/) - the PHP angle on the same forms
- [Cookbook](/documentation/guides/cookbook/) - copy-paste recipes to get productive fast
- [PHP interop](/documentation/php-interop/) - the full interop reference
