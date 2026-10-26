+++
title = "Agentic Coding"
weight = 7
description = "Single-page Phel reference for AI coding agents (Claude Code, Codex, Cursor, Copilot, Aider, Gemini). Self-contained syntax, idioms, interop, gotchas."
aliases = ["/documentation/llms", "/documentation/ai-agents", "/documentation/tooling/ai-agents/", "/documentation/agent-setup", "/documentation/ai-setup", "/documentation/reference/agent-setup/"]
+++

A one-page Phel reference for AI coding agents (Claude Code, Codex, Cursor, Copilot, Aider, Gemini). If an agent can load only one doc, load this one. To build AI features in Phel instead (LLM chat, embeddings, tool use), see the [AI module API](/documentation/reference/api/ai/).

<div class="agent-doc-cta">
  <a href="/agentic-coding.md" class="btn btn-primary btn-lg" download>
    <span aria-hidden="true">⤓</span> Download raw markdown
  </a>
  <span class="agent-doc-cta__hint">For agents and scripts: <code>curl https://phel-lang.org/agentic-coding.md</code>. Same body, no HTML chrome.</span>
</div>

## TL;DR for agents

Rules first, reasons second. Check with `phel doc` before you deviate.

| Use | Avoid | Why |
|---|---|---|
| `phel doc <fn>`, grep `vendor/phel-lang/phel-lang/src/phel/` | inventing names | An unknown symbol is a `PHEL001` compile error |
| `phel.string` (alias `str`) | `phel.str`, `clojure.string` | Neither exists. `phel.string` fns return Phel values |
| `(ns app.main)`: two or more segments, file path mirrors the ns under `src/` | `(ns main)` | A single segment puts your code in a top-level PHP namespace |
| `*argv*` (vector of strings) | `argv`, `php/$argv` | `argv` was removed. `$argv` is undefined under `phel run` |
| `for` to build data, `doseq`/`foreach` for effects | `for` with side effects | `for` returns a vector. `doseq` and `foreach` return `nil` |
| `recur` in tail position of `loop`/`fn` | `recur` anywhere else | Non-tail `recur` is a compile error (`PHEL010`) |
| `vec`/`php->phel` (PHP to Phel), `to-array`/`phel->php` (Phel to PHP) | passing PHP arrays as Phel collections | Different types. `count`, `map` and friends expect Phel values |
| `#php {"k" "v"}` for a literal PHP assoc array | `{:k "v"}` where PHP wants an array | Phel maps are objects, not PHP arrays |
| `(:x p)` or `(get p :x)` for record fields | `(.-x p)` | Record fields are protected PHP properties |
| Binding-first destructuring `{name :name}` | key-first `{:name name}` | Key-first is deprecated since 0.51 |
| Only `false` and `nil` are falsy | treating `0`, `""`, `[]`, `{}` as falsy | All four are truthy |
| `(when-not *build-mode* ...)` around top-level effects | unguarded top-level effects | `phel build` evaluates the top level, so effects fire at build time |
| `(new Foo arg)`, `(.m obj)`, `(Foo/m)` | `php/new`, `php/->`, `php/::` | Those are a `PHEL012` error since 0.52 |
| Check Clojure-looking forms first ([Phel is not Clojure](#phel-is-not-clojure)) | porting Clojure blindly | PHP target: different stdlib and concurrency |

Phel is a functional Lisp that compiles to plain PHP (8.5+), with immutable data and macros. Source files end in `.phel`; config lives in `phel-config.php` (see [Configuration](/documentation/reference/configuration/)).

## CLI

```bash
vendor/bin/phel doc <fn>          # signature + docstring (search, not exact match)
vendor/bin/phel eval '<expr>'     # evaluate and print; `eval -` reads stdin
vendor/bin/phel run <file> [args] # run a script; args land in *argv*
vendor/bin/phel test [path]       # run tests
vendor/bin/phel repl              # interactive REPL
vendor/bin/phel build             # compile the project to PHP
vendor/bin/phel format <file>     # format in place
vendor/bin/phel lint [path]       # static analysis, exit 1 on errors
vendor/bin/phel balance <file> --fix  # append dropped closing brackets
vendor/bin/phel explain PHEL001   # what an error code means
vendor/bin/phel doctor            # check PHP, extensions, config
```

For multi-line code, pipe a quoted heredoc (`<<'PHEL' ... PHEL`) into `phel eval -`, so nothing needs escaping. After you edit a `.phel` file, run `phel balance <file> --fix`. It appends missing closers and reports anything it cannot fix safely.

## Installed agent skills

One command installs a skill file for each agent plus a shared `.agents/` docs tree:

```bash
composer require phel-lang/phel-lang
vendor/bin/phel agent-install --auto   # agents detected in this project
vendor/bin/phel agent-install claude   # one platform
vendor/bin/phel agent-install --all    # every platform
```

`--auto` looks for `.claude/`, `.cursor/`, `AGENTS.md`, `.github/copilot-instructions.md`, and similar files. An existing skill file is backed up to `*.pre-phel.bak` before it is replaced. Run the command again after you update Phel: it writes only new or changed docs and keeps files you edited.

| Platform | Argument | File installed |
|----------|----------|----------------|
| Claude Code | `claude` | `.claude/skills/phel-lang/SKILL.md` |
| Cursor | `cursor` | `.cursor/rules/phel.mdc` |
| Codex | `codex` | `AGENTS.md` |
| GitHub Copilot | `copilot` | `.github/copilot-instructions.md` |
| Gemini | `gemini` | `GEMINI.md` |
| Aider | `aider` | `CONVENTIONS.md` |

| Flag | Effect |
|------|--------|
| `--auto` | install only for agents found in the project |
| `--all` | install for every platform |
| `--with-examples` | also copy `.agents/examples/` (small runnable projects) |
| `--no-docs` | install only the skill file, not `.agents/` |
| `--dry-run` | print what would be written, change nothing |
| `--force` | replace the skill file without a backup, and replace docs you edited (after backing them up) |
| `--uninstall` | remove the skill file and restore its backup |
| `--check` | compare the installed docs version with the bundled one; exit 1 when they differ |

The skill file tells the agent to read `.agents/RULES.md` (hard rules and CLI map), then `quick-syntax.md`, then `index.md` (task to recipe map), then `tasks/*.md` (HTTP apps, CLI tools, tests, macros, errors, schema validation, and more). Before install, the same files are in `vendor/phel-lang/phel-lang/resources/agents/`. Every core function's source is in `vendor/phel-lang/phel-lang/src/phel/core/`.

Agents that cannot run `phel` can fetch `https://phel-lang.org/agentic-coding.md` (this page) or `https://phel-lang.org/llms.txt` (a link index of all docs).

## Syntax in 60 seconds

```phel
;; Two semicolons for a standalone comment, one after code.

;; Literals: nil true false
;; Numbers: 42 -3 1.5 3.14e2 0xFF 0b1010 017 (octal) 1/3 1.5M
;; Strings: "hello" "line\nbreak"
;; Keywords: :status :user/email
;; Symbols: my-var my-ns/fn
;; Regex: #"^\d+$"

;; A call: (operator arg1 arg2 ...)
(+ 1 2 3)                          ; => 6

;; Data structures, all immutable:
[1 2 3]                            ; vector
{:a 1 :b 2}                        ; map
#{1 2 3}                           ; set
'(1 2 3)                           ; list (quoted, so not a call)

;; Literal PHP assoc array, for interop:
#php {"k" "v"}
```

## Core forms

<!-- phel-test: skip -->
```phel
(def x 42)                         ; global
(def- secret 7)                    ; private global
(defn greet [name]                 ; public function
  (str "Hello, " name))
(defn- helper [x] (* x 2))         ; private function
(let [x 1, y 2] (+ x y))           ; locals (commas are whitespace)
(cond test-1 expr-1
      :else  fallback)

(loop [acc 0 n 10]
  (if (zero? n) acc (recur (+ acc n) (dec n))))

(for [x :in xs :when (odd? x)] (* x x))  ; returns a vector
(doseq [x xs] (println x))               ; effects, returns nil

#(* % 2)                           ; one arg
#(+ %1 %2)                         ; several args
#(apply + %&)                      ; variadic

(->> xs (filter odd?) (map inc))   ; thread last
(some-> m :a :b)                   ; stops at nil

(try (risky)
  (catch \RuntimeException e (.getMessage e))
  (finally (cleanup)))
```

## Namespaces

File `src/my-app/users.phel`:

```phel
(ns my-app.users
  (:require phel.string :as str)
  (:use DateTimeImmutable))

(defn full-name [{first-name :first last-name :last}]
  (str/join " " [first-name last-name]))
```

- Use two or more segments (`my-app.main`, not `main`), separated by `.`. The `\` separator is deprecated.
- The file path mirrors the namespace under the source dir. Dashes become underscores in PHP: `my-app.users` compiles to the PHP namespace `my_app\users`.

## PHP interop

<!-- phel-test: skip -->
```phel
(php/strlen "hi")                          ; PHP function
(new DateTimeImmutable "2024-01-15")       ; constructor
(DateTimeImmutable. "2024-01-15")          ; same, shorthand
(.format date "Y-m-d")                     ; instance method
(.-prop obj)                               ; instance property
(DateTimeImmutable/createFromFormat f s)   ; static method
DateTimeImmutable/ATOM                     ; class constant

(to-array ["a" "b" "c"])                   ; Phel vector -> PHP list
(phel->php {:a 1 :b [1 2]})                ; nested Phel data -> PHP arrays
(vec (php/explode "," "a,b,c"))            ; PHP list -> ["a" "b" "c"]
(php->phel (php/json_decode s true))       ; nested PHP arrays -> Phel data
```

Use `phel->php` for a map, not `to-array`. For string splitting, `(str/split "a,b,c" #",")` returns a Phel vector directly.

## Records, protocols, multimethods

```phel
(defrecord Point [x y])
(def p (->Point 1 2))
(:x p)                             ; => 1
(get p :x)                         ; => 1

(defprotocol Drawable
  (draw [this]))

(extend-type :string Drawable
  (draw [s] (str "drawn " s)))

(defmulti area :shape)
(defmethod area :circle [{r :radius}] (* 3 r r))
(defmethod area :rect   [{w :w h :h}] (* w h))
(area {:shape :rect :w 2 :h 3})    ; => 6
```

`defprotocol` cannot be implemented inline in `defstruct`. Use `extend-type`, or `definterface` for inline methods.

## Equality and comments

- `=` is value equality for all types. `identical?` is reference equality.
- `;` after code, `;;` on its own line, `#_` skips the next form, `(comment ...)` ignores its body. `#` line comments were removed and do not lex.

```phel
(= [1 2] [1 2])                    ; => true
#_(this-form-is-skipped)
```

## Tests

<!-- phel-test: skip -->
```phel
(ns my-app.users-test
  (:require phel.test :refer [deftest is])
  (:require my-app.users :as users))

(deftest full-name-joins
  (is (= "Ada Lovelace"
         (users/full-name {:first "Ada" :last "Lovelace"}))))
```

Run with `vendor/bin/phel test`. Filter with `--filter=name`, find deprecated forms with `--warn-deprecations`.

## Other gotchas

- **`transduce` with `max` or `min`:** they have no zero-arity. Pass an init: `(transduce xf (fn [a b] (max a b)) 0 coll)`.
- **No `to-vec` or `to-list`.** Use `vec` or `to-array`.
- **`recur` must match the `loop` bindings.** A wrong argument count is a compile error.
- **Cache with `^:memoize`:** `(defn ^:memoize f [x] ...)`, or `^{:memoize-lru 32}` for a bounded cache.

## Phel is not Clojure

Agents trained on Clojure invent Clojure-only forms. Check anything that "sounds Clojure" with `phel doc <name>` first.

- **Strings:** `phel.string`, not `clojure.string`. Some names match, some do not.
- **Interop is PHP, not Java:** `(new Class arg)`, `(.method obj)`, `(Class/method)`, `Class/CONST`.
- **Records:** read fields by keyword `(:x p)`. No `.-field` on records.
- **Numbers:** PHP `int` and `float`, plus Phel ratios (`(/ 1 3)` is `1/3`), big integers (auto-promoted on overflow), and big decimals (`1.5M`).
- **Reader conditionals** use `:phel` and `:default`: `#?(:phel "phel" :default "other")`.
- **Concurrency is fiber-based.** `atom`, `future`, `promise`, `pmap`, `async`, `await`, `await-all`, `await-any` exist. `ref`, `agent` and STM do not.
- **No `clojure.*` namespaces.** Look under `phel.*` instead: `phel.string`, `phel.walk`, `phel.match`, `phel.test`, `phel.html`, `phel.json`. Set operations (`union`, `difference`, `select`, ...) are in core.
- **Type tags emit PHP declarations:** `^int`, `^string`, `^"?int"` on `defn` params and return.

If `phel doc <name>` prints `No function matches`, the function does not exist. Do not call it. The command exits 0 either way, so read the output.

More on this site: [Cheat Sheet](/documentation/reference/cheat-sheet/), [PHP Interop](/documentation/language/php-interop/), [Cookbook](/documentation/guides/cookbook/), [CLI Commands](/documentation/reference/cli-commands/), [Error Reference](/documentation/reference/errors/).
