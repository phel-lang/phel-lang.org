+++
title = "Coming from Clojure"
weight = 2
description = "What transfers from Clojure to Phel, what differs, and what Phel adds, with a side-by-side form mapping"
aliases = ["/documentation/coming-from-clojure"]
+++

Most Clojure forms work the same in Phel, so this page lists what is different: the runtime, namespaces, interop, and the few forms Phel changes or leaves out.

## What works the same

- Core forms, destructuring, threading macros, `#(...)` function literals, multi-arity and variadic functions.
- Vectors, maps, sets, keywords as functions, sequence functions, lazy sequences, and transducers.
- `atom`, vars, `binding` (the var must be `^:dynamic`), `with-redefs`, protocols, records, multimethods, and hierarchies.
- `try`/`catch`, `ex-info`, ratios and big numbers, macros with syntax-quote, `#"regex"`, `#_`, and Clojure truthiness: only `nil` and `false` are falsy, unlike PHP.

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
| `deps.edn`, Leiningen | Composer (`composer.json`) + `phel-config.php` | `composer require phel-lang/phel-lang` |
| `lein repl`, `lein test`, uberjar | `phel repl`, `phel test`, `phel build` | `phel build` compiles to PHP |
| `(:require [foo.bar :as b])` | `(:require foo.bar :as b)` | The vector is optional. `clojure.string` is `phel.string`. See [Namespaces](/documentation/language/namespaces/) |
| `(:import (java.time Instant))` | `(:use DateTimeImmutable)` | `:use` imports PHP classes |
| Java interop `(Math/pow 2 10)` | `(php/pow 2 10)` | Any PHP function through `php/`. See [PHP Interop](/documentation/language/php-interop/) |
| `(.method obj)`, `(Class/static)`, `(Class.)` | same | Works on PHP objects and classes |
| `#?(:clj x :default y)` | `#?(:phel x :default y)` | Platform key is `:phel`; `#?@` also works |
| `(memoize f)` on a defn | `(defn ^:memoize f ...)` | `memoize` also exists |
| `core.async`, channels | `async`/`await`, `future-call`, `promise`, `pmap` | Fiber-based, no CSP. See [Async](/documentation/language/async/) |
| agents, refs, STM | none | `atom` is the only mutable reference |
| `clojure.spec` | `phel.schema` | Validation, coercion, generation. See [Schema](/documentation/libraries/schema/) |
| `(* Long/MAX_VALUE 2)` throws | promotes to BigInt | PHP ints promote on overflow |
| inline protocol in `defrecord` | `definterface` inline, `defprotocol` via `extend-type` | See [Protocols and interfaces](#protocols-and-interfaces) |
| custom reader macros | none | Tagged literals only, see [Reader conditionals](#reader-conditionals) |
| ClojureScript | none | PHP is the only target |
| CIDER, Calva | nREPL and LSP servers | See [Editor support](/documentation/tooling/editor-support/) |

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

`#?()` and `#?@()` use `:phel` and `:default` as platform keys, so one `.cljc` file can serve both languages. There are no custom reader macros; `#inst`, `#uuid`, `#regex`, and `#php` are built in, and `register-tag` from `phel.reader` adds more. See [Reader conditionals](/documentation/language/reader-shortcuts/#reader-conditionals).
