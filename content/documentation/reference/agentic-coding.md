+++
title = "Agentic Coding"
weight = 50
description = "Single-page Phel reference for AI coding agents (Claude Code, Codex, Cursor, Copilot, Aider, Gemini). Self-contained syntax, idioms, interop, gotchas."
aliases = ["/documentation/llms", "/documentation/ai-agents"]
+++

Single-page reference for AI agents (Claude Code, Codex, Cursor, Copilot, Aider, Gemini) to learn Phel without crawling the docs. Humans pairing with an agent benefit too.

Load this one if you can only load one doc into an agent's context.

<div class="agent-doc-cta">
  <a href="/agentic-coding.md" class="btn btn-primary btn-lg" download>
    <span aria-hidden="true">⤓</span> Download raw markdown
  </a>
  <span class="agent-doc-cta__hint">For agents and scripts: <code>curl https://phel-lang.org/agentic-coding.md</code>. Same body, no HTML chrome.</span>
</div>

## TL;DR for agents

Truncation-safe rules. Code form first, reason second. Verify with `phel doc` before deviating.

| Use | Avoid | Why |
|-----|-------|-----|
| `phel doc <fn>`, grep `vendor/phel-lang/phel-lang/src/phel/core/` | inventing names | Hallucinated symbols compile then fail at runtime. |
| `phel.string` (alias `str`) | `phel.str`, `clojure.string`, `php/strtoupper`, `php/explode` | `phel.str` removed. Phel string fns return Phel values. |
| `(ns app.main)` (≥2 segments, file mirrors path under `src/`) | `(ns main)` | Single-segment ns exports invalid PHP under `phel build`. |
| `argv` (vector of strings) | `*argv*` (pre-0.39), `php/$argv` | Symbol renamed in 0.39. `php/$argv` is `nil` under `phel run`. |
| `for` for data, `foreach`/`doseq` for effects | `for` with side effects | `for` returns a vector. `foreach` returns `nil`. |
| `recur` in tail of `loop`/`fn` | `recur` anywhere else | Non-tail `recur` errors at compile time. |
| `vec` (PHP→Phel), `to-php-array` (Phel→PHP) | treating PHP arrays as Phel collections | Different types. Mixing breaks `count`, `map`, etc. |
| `#php {"k" "v"}` for PHP assoc | `{:k "v"}` as a PHP array | Phel maps are not PHP arrays. |
| `(:x p)` or `(get p :x)` for records | `(.-x p)` | Record fields are protected PHP properties. |
| `false`, `nil` only as falsy | assuming `0`, `""`, `[]`, `{}` falsy | All four are truthy. |
| `(when-not *build-mode* ...)` around top-level effects | unguarded top-level effects | `phel build` evaluates top level; effects fire at build time. |
| Verify Clojure-looking forms first ([Phel is not Clojure](#phel-is-not-clojure)) | porting Clojure code blindly | PHP target, not JVM. Different stdlib, different concurrency. |

## What Phel is

Functional Lisp that compiles to PHP. Runs on any PHP 8.4+, ships via Composer, full PHP interop.

- Immutable persistent data structures.
- Macros, homoiconicity, REPL-driven dev.
- Compiles to plain PHP. No separate runtime, no JVM.
- Source: `.phel`. Config: `phel-config.php`.

## CLI cheat sheet

```bash
vendor/bin/phel doc <fn>           # function signature + docstring
vendor/bin/phel eval '<expr>'      # one-shot eval
vendor/bin/phel repl               # full REPL
vendor/bin/phel test [path]        # run tests
vendor/bin/phel run <file>         # run a script
vendor/bin/phel build              # compile to PHP
vendor/bin/phel format <file>      # rewrite formatting
vendor/bin/phel doctor             # env + extension check
```

## Installed agent skills

Phel ships skill adapters in `vendor/phel-lang/phel-lang/.agents/`. Install for the active agent:

```bash
vendor/bin/phel agent-install claude    # or codex, cursor, copilot, aider, gemini
vendor/bin/phel agent-install --all     # every adapter
```

`.agents/` contains: `RULES.md`, `index.md` (intent map), `tasks/*.md` (HTTP apps, CLI tools, tests, REPL flow, validation), `examples/`. Prefer it over guessing.

## Syntax in 60 seconds

```phel
;; Inline comment uses one semicolon.
;; Standalone comment uses two.

;; Atoms: nil true false
;; Numbers: 42 -3 1.5 3.14e2 0xFF 0b1010 0o17
;; Strings: "hello" "line\nbreak"
;; Keywords: :status :user/email
;; Symbols: my-var my-ns/fn
;; Regex literal: #"^\d+$"

;; Calls: (function arg1 arg2 ...). First element is the operator.
(+ 1 2 3)                          ; => 6
(str "Hello, " name)

;; Data structures (all immutable):
[1 2 3]                            ; vector
{:a 1 :b 2}                        ; map
#{1 2 3}                           ; set
'(1 2 3)                           ; list (data, not a call)

;; PHP assoc array literal (when interop needs one):
#php {"k" "v"}
```

## Core Forms

```phel
(def x 42)                         ; global binding
(def- secret 7)                    ; private binding

(defn greet [name]                 ; public function
  (str "Hello, " name))

(defn- helper [x] (* x 2))         ; private function

(let [x 1, y 2] (+ x y))           ; local bindings (commas optional)

(if cond then else)
(when cond expr ...)
(cond  pred-1 expr-1
       pred-2 expr-2
       :else  fallback)
(case x 1 "one" 2 "two" "default")
(condp = x 1 "one" 2 "two" "other")

(do expr1 expr2 ... last)          ; sequence; returns last

(loop [acc 0 n 10]
  (if (zero? n) acc (recur (+ acc n) (dec n))))

(for   [x :in xs :when (odd? x)] (* x x))   ; comprehension, returns vector
(foreach [x xs] (println x))               ; side effects, returns nil
(dotimes [i 5] (println i))

(fn [x] (* x 2))                   ; anonymous fn
#(* % 2)                           ; reader shorthand (single arg)
#(+ %1 %2)                         ; multi-arg shorthand
#(apply + %&)                      ; variadic shorthand

(-> x (f a) (g b))                 ; thread first
(->> x (f a) (g b))                ; thread last
(some-> x .a .b)                   ; nil-safe thread first
(cond-> x pred (f y))              ; conditional thread

(try expr (catch Exception e (handle e)) (finally cleanup))
```

## Namespaces

```phel
;; src/my-app/users.phel
(ns my-app.users
  (:require phel.string :as str)
  (:require phel.html :as h)
  (:use DateTimeImmutable))

(defn full-name [{:first f :last l}]
  (str/join " " [f l]))
```

Rules:

- Two or more segments required (`my-app.main`, not `main`).
- File path mirrors namespace under `src/`. Source uses dashes, compiled PHP uses studly case (`my-app.users` ↔ `MyApp\Users`).

## PHP Interop

```phel
(php/strlen "hi")                          ; call PHP function
(php/new DateTimeImmutable "2024-01-15")   ; construct
(php/-> obj (method arg))                  ; instance method
(php/:: DateTimeImmutable ATOM)            ; static / constant

;; Shorthands also accepted:
(.method obj arg)
(.-prop obj)
(Class/method args)
Class/CONST

;; Convert Phel collection to PHP array (when handing off to PHP):
(to-php-array ["a" "b" "c"])

;; Convert PHP array back to Phel collection:
(vec (php/explode "," "a,b,c"))            ; => ["a" "b" "c"]
;; Or with phel.string (returns Phel vector directly):
;; (phel.string/split "a,b,c" #",")

;; Catch PHP exceptions:
(try (risky)
  (catch RuntimeException e (handle e)))
```

## Records, Protocols, Multimethods

```phel
(defrecord Point [x y])
(def p (->Point 1 2))
(:x p)                             ; => 1     (keyword-as-fn: preferred)
(get p :x)                         ; => 1     (also valid)
(map->Point {:x 1 :y 2})           ; => (point 1 2)

(defprotocol Drawable
  (draw [this]))

(extend-type :string Drawable
  (draw [s] (println s)))

(defmulti area :shape)
(defmethod area :circle [{:radius r}] (* 3.14 r r))
(defmethod area :rect   [{:w w :h h}] (* w h))
```

## Equality and comments

- `=` is value equality across all types. `identical?` is reference equality.
- Comments: `;` inline, `;;` standalone, `#_` discards the next form, `(comment ...)` ignores its body.

```phel
(= [1 2] [1 2])                    ; => true
#_(this-form-is-skipped)
```

Truthiness is in the [TL;DR](#tl-dr-for-agents).

## Tests

```phel
(ns my-app.users-test
  (:require phel.test :refer [deftest is])
  (:require my-app.users :as users))

(deftest full-name-joins
  (is (= "Ada Lovelace"
         (users/full-name {:first "Ada" :last "Lovelace"}))))
```

Run with `vendor/bin/phel test`.

## Other gotchas

Beyond the TL;DR:

- **`transduce` with `max`/`min`:** no zero-arity. Pass init: `(transduce xf (fn [a b] (max a b)) 0 coll)`.
- **No `to-vec` / `to-list` functions.** Use `vec` (PHP array to Phel vector) or `to-php-array` (Phel to PHP).
- **`recur` arity must match `loop` bindings.** Mismatched arg count errors at compile time.
- **`#` line comments are deprecated.** Use `;` or `;;`.

## Phel is not Clojure

Agents trained on Clojure data hallucinate Clojure-only forms in Phel code. Phel is Lisp-on-PHP, not Lisp-on-JVM. Verify with `phel doc <name>` before using anything that "sounds Clojure".

Known differences:

- **Strings module:** `phel.string`, not `clojure.string`. Some function names match, some don't. Check each.
- **Interop is PHP, not Java.** `(php/new Class arg)`, `(.method obj)`, `(Class/method)`, `Class/CONST`. No `Class/.method`, no `Class.`, no JVM.
- **Records:** field access by keyword `(:x p)`. No `.-field` on records.
- **Numbers:** PHP `int`/`float`, plus Phel `:ratio` (`(/ 1 3)` => `1/3`) and `:bigint` (auto-promoted on overflow). No `BigDecimal`.
- **Reader conditionals use `:phel`/`:default`,** not `:clj`/`:cljs`. Example: `#?(:phel "phel" :default "other")`.
- **Concurrency primitives are fiber-based.** `atom`, `future`, `promise`, `pmap`, `async`/`await`, `await-all`, `await-any` all exist (see `phel/core/async.phel`). `ref`, `agent`, STM do not. Verify each with `phel doc`.
- **No `clojure.*` namespaces.** `clojure.set`, `clojure.walk`, `clojure.spec`, `clojure.test.check`, `core.match`: none. Phel modules live under `phel.*` (`phel.string`, `phel.html`, `phel.test`, etc).
- **`phel.test`, not `clojure.test`.** Uses `deftest` + `is`.
- **Type tags emit PHP declarations**, not Java. `^int`, `^string`, `^"?int"` on `defn` params/return.

When in doubt: run `phel doc <name>`. If it errors, it does not exist; do not generate code that calls it.

## Project layout

```
my-app/
  composer.json         # PHP deps + composer scripts (repl, dev, test, build)
  phel-config.php       # Phel config; usually one line via forProject()
  src/
    main.phel           # entry namespace
    modules/...
  tests/
    modules/...
```

Minimal `phel-config.php`:

```php
<?php
return \Phel\Config\PhelConfig::forProject('my-app.main');
```

Full options: [Configuration](/documentation/configuration).

## Idiomatic style for agents

TL;DR covers what must not break. These shape what good Phel looks like:

1. **Prefer pure functions.** Push side-effects to the edge. Use `atom` only for shared mutable state.
2. **Thread, don't nest.** `(->> xs (filter f) (map g) (reduce h 0))` beats deep nesting.
3. **Stay immutable.** `(conj v x)` returns a new vector. Rebind, don't expect mutation.
4. **Interop shorthands.** `(.method obj)`, `(.-prop obj)`, `(Class/method)`, `(ClassName.)`. Shorter, idiomatic.
5. **`^:memoize` for caching.** `(defn ^:memoize f [x] ...)` beats a manual `static $cache` pattern.
6. **Type tags emit PHP declarations.** `^int`, `^string`, `^"?int"` on `defn` params/return = free PHP type hints.
7. **No em-dashes** in docstrings or generated site docs. Use commas, colons, periods, parentheses.
8. **Conventional commits.** `feat:`, `fix:`, `ref:`, `chore:`, `docs:`, `test:`. No AI/LLM authorship references.

## Where to look next

In the Phel install:

- `vendor/phel-lang/phel-lang/.agents/index.md`: intent → recipe map.
- `vendor/phel-lang/phel-lang/.agents/RULES.md`: canonical rules + CLI map.
- `vendor/phel-lang/phel-lang/.agents/tasks/`: HTTP, CLI, tests, debugging, validation, pattern matching.
- `vendor/phel-lang/phel-lang/src/phel/core/`: every core function source.

On this site:

- [Cheat Sheet](/documentation/reference/cheat-sheet): filterable forms and functions.
- [Language section](/documentation/language/): types, functions, control flow, macros, interfaces, namespaces, destructuring, recursion.
- [PHP Interop](/documentation/php-interop): every interop form.
- [Cookbook](/documentation/guides/cookbook): copy-paste recipes.
- [Rosetta Stone](/documentation/guides/rosetta-stone): PHP to Phel side-by-side.
- [REPL guide](/documentation/tooling/repl): dev loop.
- [CLI Commands](/documentation/tooling/cli-commands): every subcommand.
