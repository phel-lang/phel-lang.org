+++
title = "Coming from Clojure"
weight = 2
description = "What transfers from Clojure to Phel, what differs, and what Phel adds, with a side-by-side form mapping"
aliases = ["/documentation/coming-from-clojure"]
+++

This page maps your Clojure knowledge to Phel. Most forms work the same, so it lists only what is different: the runtime, namespaces, interop, and the few forms Phel changes or leaves out.

Phel is a functional Lisp that compiles to PHP. It is inspired by Clojure (and Janet): persistent data structures, immutability by default, and a functional core.

## What works the same

You can write these the way you write them in Clojure:

| Area | Forms |
|---|---|
| Core forms | `def`, `defn`, `let`, `fn`, `if`, `when`, `cond`, `case`, `do`, `loop`/`recur` |
| Data | vectors, maps, sets, keywords (also as functions), `conj`, `assoc`, `get`, `get-in`, `update` |
| Sequences | `map`, `filter`, `reduce`, `some`, `every?`, `comp`, `partial`, `apply`, `lazy-seq`, `lazy-cat`, `doall`, `realized?`, transducers |
| Threading | `->`, `->>`, `as->` |
| Destructuring | sequential and associative, in `let`, `fn`, `defn`, `loop` |
| Functions | `#(* % 2)`, `%1`, `%&`, multi-arity, variadic `&` |
| State | `atom`, `swap!`, `reset!`, `deref`/`@` |
| Vars | `#'sym`, `alter-var-root`, `with-redefs`, `binding` (var must be `^:dynamic`) |
| Polymorphism | `defprotocol`, `extend-type`, `defrecord`, `defmulti`/`defmethod`, `derive`, `isa?`, `parents`, `ancestors`, `descendants` |
| Errors | `try`/`catch`/`finally`, `ex-info`, `ex-data` |
| Numbers | `1N`, `1.5M`, `1/2`, `(/ 1 2)` returns a ratio |
| Macros | `defmacro`, quote, syntax-quote, unquote, unquote-splicing |
| Reader | `#"regex"`, `#_` discard, `(comment ...)`, `;` and `;;` comments |
| Printing | `println`, `print`, `prn`, `pr-str` |
| Truthiness | only `nil` and `false` are falsy; `0`, `""`, `[]` are truthy (unlike PHP) |

```phel
(def users [{:name "Alice" :active true} {:name "Bob" :active false}])

(->> users
     (filter :active)
     (map :name)
     (into #{}))
; => #{"Alice"}
```

## What is different

| Clojure | Phel | Notes |
|---|---|---|
| JVM, JARs, classpath | PHP 8.5+ | Compiles to PHP and runs with your PHP binary |
| `deps.edn`, Leiningen | Composer (`composer.json`) | `composer require phel-lang/phel-lang` |
| `(:require [foo.bar :as b])` | `(:require foo.bar :as b)` | No vector around each clause |
| `(:import (java.time Instant))` | `(:use DateTimeImmutable)` | `:use` imports PHP classes |
| Java interop `(Math/pow 2 10)` | `(php/pow 2 10)` | Any PHP function through `php/` |
| `(.method obj)`, `(Class/static)`, `(Class.)` | same | Works on PHP objects and classes |
| `#?(:clj x :default y)` | `#?(:phel x :default y)` | Platform key is `:phel`; `#?@` also works |
| `(memoize f)` on a defn | `(defn ^:memoize f ...)` | `memoize` also exists |
| `core.async`, channels | `async`/`await`, `future-call`, `promise`, `pmap` | Fiber-based, no CSP. See [Async](/documentation/language/async/) |
| agents, refs, STM | none | `atom` is the only mutable reference |
| `clojure.spec` | `phel.schema` | Validation, coercion, generation. See [Schema](/documentation/libraries/schema/) |
| `(* Long/MAX_VALUE 2)` throws | promotes to BigInt | PHP ints promote on overflow |
| inline protocol in `defrecord` | `definterface` inline, `defprotocol` via `extend-type` | See [Protocols and interfaces](#protocols-and-interfaces) |
| custom reader macros | none | Tagged literals only, see below |
| ClojureScript | none | PHP is the only target |
| CIDER, Calva | nREPL and LSP servers | See [Editor support](/documentation/tooling/editor-support/) |

## Namespaces

Namespaces use `.` as in Clojure. Require Phel namespaces with `:require` and PHP classes with `:use`:

```clojure
;; Clojure
(ns myapp.users
  (:require [myapp.db :as db]
            [clojure.string :as str]))
```

<!-- phel-test: skip -->
```phel
;; Phel
(ns myapp.users
  (:require myapp.db :as db)
  (:require phel.string :as str)
  (:use DateTimeImmutable))
```

`:refer` works the same: `(:require myapp.db :refer [query])`. The Clojure string namespace is `phel.string` in Phel. The old backslash form `(ns myapp\db)` still parses, and warns under `PHEL_WARN_DEPRECATIONS=1`. See [Namespaces](/documentation/language/namespaces/).

## PHP interop

Java interop becomes PHP interop:

```clojure
;; Clojure
(System/currentTimeMillis)
(.toUpperCase "hello")
```

```phel
;; Phel
(php/time)
(php/strtoupper "hello") ; => "HELLO"
(.format (DateTimeImmutable. "2024-01-15") "Y-m-d") ; => "2024-01-15"
```

`:tag` metadata emits PHP type declarations: `(defn ^int add [^int a ^int b] ...)` compiles to `function add(int $a, int $b): int`, and `^"?string"` marks a nullable type. See [PHP Interop](/documentation/language/php-interop/).

## Protocols and interfaces

`defprotocol` works with `extend-type`, but you cannot implement a protocol inline in `defstruct` or `defrecord`. For an inline implementation, use `definterface`:

```phel
(definterface Greetable
  (greet [this]))

(defstruct person [name]
  Greetable
  (greet [this] (str "Hello, " name)))

(greet (person "Alice")) ; => "Hello, Alice"
```

See [Interfaces](/documentation/language/interfaces/).

## Reader conditionals

`#?()` and `#?@()` use `:phel` and `:default` as platform keys, so one `.cljc` file can serve both languages:

```phel
(def host
  #?(:phel "PHP"
     :default "Unknown"))
; => "PHP"
```

There are no custom reader macros. Four tagged literals are built in:

| Tag | Reads as |
|---|---|
| `#inst` | `DateTimeImmutable` |
| `#uuid` | `Phel\Lang\UUID` |
| `#regex` | PCRE pattern string |
| `#php` | PHP array |

Any other tag is a read error unless you register a handler with `register-tag` from `phel.reader`. Tags inside a branch that is not selected (such as `:clj`) are skipped. See [Reader Conditionals](/documentation/language/reader-conditionals/).

## What you gain

- **Hosting.** PHP runs on almost any web host, including cheap shared hosting. You can bring a Lisp to teams that already run PHP.
- **Deployment.** No JVM startup, heap tuning, or GC settings. Deploy like any PHP app.
- **Startup time.** PHP starts in milliseconds, so CLI tools and short scripts are practical.
- **Libraries.** Every Composer package (Laravel, Symfony, Guzzle, Doctrine) is callable through [PHP interop](/documentation/language/php-interop/).

## Next steps

- [Rosetta Stone: PHP to Phel](/documentation/guides/rosetta-stone/): the PHP side of the same forms
- [Cookbook](/documentation/guides/cookbook/): recipes for common tasks
- [REPL](/documentation/tooling/repl/): the interactive workflow
