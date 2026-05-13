+++
title = "Agentic Coding"
weight = 50
description = "Single-page Phel reference for AI coding agents (Claude Code, Codex, Cursor, Copilot, Aider, Gemini). Self-contained syntax, idioms, interop, gotchas."
aliases = ["/documentation/llms", "/documentation/ai-agents"]
+++

Single-page reference for AI agents (Claude Code, Codex, Cursor, Copilot, Aider, Gemini) to learn Phel without crawling the docs. Humans pairing with an agent benefit too.

Load this one if you can only load one doc into an agent's context.

## What Phel is

Functional Lisp that compiles to PHP. Runs on any PHP 8.4+, ships via Composer, full PHP interop.

- Immutable persistent data structures.
- Macros, homoiconicity, REPL-driven dev.
- Compiles to plain PHP. No separate runtime, no JVM.
- Source: `.phel`. Config: `phel-config.php`.

## Verify before you generate

Verify against the install before suggesting code:

```bash
vendor/bin/phel doc <fn>          # function signature + docstring
vendor/bin/phel eval '<expr>'      # one-shot eval
vendor/bin/phel repl               # full REPL
vendor/bin/phel test [path]        # run tests
vendor/bin/phel run <file>         # run a script
vendor/bin/phel build              # compile to PHP
vendor/bin/phel format <file>      # rewrite formatting
vendor/bin/phel doctor             # env + extension check
```

Uncertain function name? Run `phel doc` or grep `vendor/phel-lang/phel-lang/src/php/Lang/` and `vendor/phel-lang/phel-lang/src/phel/core/`. Don't invent function names.

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

(for   [x :in xs :when (odd? x)] (* x x))   ; lazy comprehension
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

;; Catch PHP exceptions:
(try (risky)
  (catch RuntimeException e (handle e)))
```

## Records, Protocols, Multimethods

```phel
(defrecord Point [x y])
(def p (->Point 1 2))
(get p :x)                         ; => 1     (use get, not .-x)
(map->Point {:x 1 :y 2})           ; => (point 1 2)

(defprotocol Drawable
  (draw [this]))

(extend-type :string Drawable
  (draw [s] (println s)))

(defmulti area :shape)
(defmethod area :circle [{:radius r}] (* 3.14 r r))
(defmethod area :rect   [{:w w :h h}] (* w h))
```

## Truthiness, equality, comments

- Only `false` and `nil` are falsy. `0`, `""`, `[]`, `{}` are all truthy.
- `=` is value equality across all types. `identical?` is reference equality.
- Comments: `;` inline, `;;` standalone, `#_` discards the next form, `(comment ...)` ignores its body.

```phel
(if 0 "truthy" "falsy")            ; => "truthy"
(= [1 2] [1 2])                    ; => true
#_(this-form-is-skipped)
```

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

## Common gotchas

Most failure modes agents hit:

1. **CLI args:** use `*argv*` (vector of strings, post-script-path). `php/$argv` is `null` under `phel run`.
2. **`for` vs `foreach`:** `for` builds a lazy sequence. `foreach` (or `doseq`) for side-effects (logging, IO).
3. **`transduce` with `max`/`min`:** no zero-arity. Wrap and pass init: `(transduce xf (fn [a b] (max a b)) 0 coll)`.
4. **Top-level side-effects break `phel build`:** guard with `(when-not *build-mode* ...)`.
5. **Record access by keyword:** `(get p :x)`, not `(.-x p)`.
6. **PHP arrays aren't Phel collections:** convert with `vec` or `to-php-array`. No `to-vec`/`to-list`.
7. **Namespaces need ≥2 segments:** `(ns app.main)`, not `(ns main)`.
8. **String module:** `phel.string` (renamed from `phel.str`).
9. **PHP assoc literal:** `#php {"k" "v"}`, not `{:k "v"}`. Phel maps aren't PHP arrays.
10. **`recur` only in tail position** of `loop` or `fn`.

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

## Authoring guidelines for agents

When generating Phel code:

1. **Verify, don't invent.** Run `phel doc <fn>` or grep `src/phel/core/` before using an uncertain name.
2. **Prefer pure functions.** Push side-effects to the edge. Use `atom` only for shared mutable state.
3. **Thread, don't nest.** `(->> xs (filter f) (map g) (reduce h 0))` beats deep nesting.
4. **Right comprehension:** `for` returns data, `foreach` runs effects, `dotimes` repeats, `loop`/`recur` accumulates.
5. **Stay immutable.** `(conj v x)` returns a new vector. Rebind, don't expect mutation.
6. **Comment style:** `;` inline, `;;` standalone. `#` line comments deprecated.
7. **No em-dashes** in docstrings or generated site docs. Prefer commas, colons, periods, parentheses.
8. **Conventional commits.** `feat:`, `fix:`, `refactor:`, `chore:`, `docs:`, `test:`. No AI/LLM authorship references.

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
