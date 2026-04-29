+++
title = "Agentic Coding"
weight = 50
description = "Single-page Phel reference for AI coding agents (Claude Code, Codex, Cursor, Copilot, Aider, Gemini). Self-contained syntax, idioms, interop, gotchas."
aliases = ["/documentation/llms", "/documentation/ai-agents"]
+++

A single page designed for AI coding agents (Claude Code, Codex, Cursor, Copilot, Aider, Gemini) to learn Phel without crawling the rest of the docs. Humans pairing with an agent will also find it useful.

If you only have time to load one doc into an agent's context, load this one.

## What Phel Is

Phel is a functional Lisp that compiles to PHP. It runs on any PHP 8.4+ runtime, ships through Composer, and interops fully with PHP libraries.

- Immutable persistent data structures by default.
- Macros, homoiconicity, REPL-driven development.
- Compiles to plain PHP. No separate runtime, no JVM.
- Source files: `.phel`. Project config: `phel-config.php`.

## Verify Before You Generate

Before suggesting code, verify against the running install:

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

If a function name is uncertain, run `phel doc` or grep `vendor/phel-lang/phel-lang/src/php/Lang/` and `vendor/phel-lang/phel-lang/src/phel/core/`. Do not invent function names.

## Installed Agent Skills

The Phel package ships skill adapters in `vendor/phel-lang/phel-lang/.agents/`. Install for the active agent:

```bash
vendor/bin/phel agent-install claude    # or codex, cursor, copilot, aider, gemini
vendor/bin/phel agent-install --all     # every adapter
```

The `.agents/` tree contains: `RULES.md`, `index.md` (intent map), `tasks/*.md` (recipes for HTTP apps, CLI tools, tests, REPL flow, validation, etc.), and `examples/`. Prefer it over guessing.

## Syntax in 60 Seconds

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
(doseq [x :in xs] (println x))              ; side effects, returns nil
(dotimes [i 5] (println i))

(fn [x] (* x 2))                   ; anonymous fn
#(* % 2)                           ; reader shorthand (single arg)
#(+ %1 %2)                         ; multi-arg shorthand
#(apply + %&)                      ; variadic shorthand

(-> x (f a) (g b))                 ; thread first
(->> x (f a) (g b))                ; thread last
(some-> x .a .b)                   ; nil-safe thread first
(cond-> x pred (f y))              ; conditional thread

(try expr (catch php\Exception e (handle e)) (finally cleanup))
```

## Namespaces

```phel
;; src/my-app/users.phel
(ns my-app\users
  (:require phel\string :as str)
  (:require phel\html :as h)
  (:use \DateTimeImmutable))

(defn full-name [{:first f :last l}]
  (str/join " " [f l]))
```

Rules:

- Two or more segments required (`my-app\main`, not `main`).
- File path mirrors namespace under `src/`. Source uses dashes, compiled PHP uses studly case (`my-app\users` ↔ `MyApp\Users`).

## PHP Interop

```phel
(php/strlen "hi")                          ; call PHP function
(php/new \DateTimeImmutable "2024-01-15")  ; construct
(php/-> obj (method arg))                  ; instance method
(php/:: \DateTimeImmutable ATOM)           ; static / constant

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
  (catch \RuntimeException e (handle e)))
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

## Truthiness, Equality, Comments

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
(ns my-app\users-test
  (:require phel\test :refer [deftest is])
  (:require my-app\users :as users))

(deftest full-name-joins
  (is (= "Ada Lovelace"
         (users/full-name {:first "Ada" :last "Lovelace"}))))
```

Run with `vendor/bin/phel test`.

## Common Gotchas

Read these once. They cover most failure modes agents hit.

1. **CLI args**: use `*argv*` (vector of strings, post-script-path), not `php/$argv` (which is `null` under `phel run`).
2. **`for` vs `doseq`**: `for` builds a lazy sequence. Use `doseq` for side effects (logging, printing, IO).
3. **`transduce` with `max`/`min`**: they have no zero-arity, so wrap and pass init: `(transduce xf (fn [a b] (max a b)) 0 coll)`.
4. **Top-level side effects break `phel build`**: guard with `(when-not *build-mode* ...)`.
5. **Records access by keyword**: `(get p :x)`, not `(.-x p)`.
6. **PHP arrays are not Phel collections**: convert with `vec` or `to-php-array`. There is no `to-vec` or `to-list`.
7. **Namespace must have at least two segments**: `(ns app\main)`, not `(ns main)`.
8. **String module is `phel\string`** (renamed from `phel\str`).
9. **PHP assoc literal**: `#php {"k" "v"}`, not `{:k "v"}`. Phel maps are not PHP arrays.
10. **`recur` only in tail position** of `loop` or `fn`.

## Project Layout

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
return \Phel\Config\PhelConfig::forProject('my-app\main');
```

Full options in [Configuration](/documentation/configuration).

## Authoring Guidelines for Agents

When generating Phel code:

1. **Verify, don't invent.** Run `phel doc <fn>` or grep `src/phel/core/` before using a function name you are unsure about.
2. **Prefer pure functions.** Push side effects to the edge. Use `atom` only when you genuinely need shared mutable state.
3. **Thread, don't nest.** `(->> xs (filter f) (map g) (reduce h 0))` beats nested calls four levels deep.
4. **Reach for the right comprehension.** `for` returns data, `doseq` runs effects, `dotimes` repeats, `loop`/`recur` accumulates.
5. **Stay immutable.** `(conj v x)` returns a new vector; rebind it. Do not expect in-place mutation.
6. **Match comment style.** `;` inline, `;;` standalone. The `#` line comment form is deprecated.
7. **Avoid em-dashes** in any Phel docstrings or generated docs for this site. Prefer commas, colons, periods, or parentheses.
8. **Conventional commits.** `feat:`, `fix:`, `refactor:`, `chore:`, `docs:`, `test:`. No mention of AI / LLM authorship in commit messages.

## Where to Look Next

Inside the Phel install:

- `vendor/phel-lang/phel-lang/.agents/index.md`: intent → recipe map.
- `vendor/phel-lang/phel-lang/.agents/RULES.md`: canonical rule set + CLI map.
- `vendor/phel-lang/phel-lang/.agents/tasks/`: recipes for HTTP, CLI, tests, debugging, validation, pattern matching.
- `vendor/phel-lang/phel-lang/src/phel/core/`: source of every core function.

On this site:

- [Cheat Sheet](/documentation/reference/cheat-sheet): full filterable list of core forms and functions.
- [Language section](/documentation/language/): one page per concept (types, functions, control flow, macros, interfaces, namespaces, destructuring, recursion).
- [PHP Interop](/documentation/php-interop): every interop form in detail.
- [Cookbook](/documentation/guides/cookbook): copy-paste recipes for real tasks.
- [Rosetta Stone](/documentation/guides/rosetta-stone): PHP → Phel patterns side by side.
- [REPL guide](/documentation/tooling/repl): development loop.
- [CLI Commands](/documentation/tooling/cli-commands): every subcommand.
