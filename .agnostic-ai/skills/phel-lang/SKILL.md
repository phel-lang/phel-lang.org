---
name: phel-lang
description: Write or verify Phel code (Lisp on PHP). Triggers on .phel files, phel-config.php, phel CLI commands, or Phel snippets in markdown docs. Verify any non-trivial snippet against the runtime before claiming it works.
model: sonnet
---

# Phel

Lisp dialect compiling to PHP. Persistent data structures. PHP interop via `php/`.

## Verify before documenting

Any Phel snippet added to docs/blog/cookbook MUST be runtime-checked. Examples that "look right" silently rot. Two ways:

```bash
# One expression
./vendor/bin/phel eval '(map inc [1 2 3])'

# Several forms, from stdin (quoted heredoc: no shell escaping)
./vendor/bin/phel eval - <<'PHEL'
(ns scratch.check)
(println (+ 40 2))
PHEL

# A file, when the sample defines a namespace or needs output
./vendor/bin/phel run path/to/scratch.phel

# Exploration
./vendor/bin/phel repl
```

In docs, `php build/run-doc-snippets.php <file.md>` runs every ```phel block of a page the way CI does.

Write the snippet, run it, paste real output. If output differs from what you assumed, fix the doc - not the runtime.

## Gotchas (project-specific)

- `defprotocol` cannot be implemented inline in `defstruct`. Use `definterface` for inline; `defprotocol` + `extend-type` per struct.
- `extends?` works only on primitive type keywords (`:string`, `:int`). Returns `false` for struct types. Use `satisfies?` on instances instead.
- CLI args: `*argv*` (vector of user args, excludes program name); `*program*` for the script path. Not `argv` (removed in 0.45) or `php/$argv`.
- Side effects: `doseq` / `foreach`. Build sequences: `for`. Mixing causes wrong return shape.
- Namespaces use dots: `phel.string`, `app.core`. The backslash form (`phel\string`) is deprecated and warns with `--warn-deprecations`. Give app code at least two segments (`app.core`, not `app`).
- REPL output: vectors print as `[1 2]`, lazy seqs (`map`, `filter`) as `(1 2)`. Prompt is `user:N>`. Match the runtime output in docs.

- Map destructuring is binding-first: `{n :name}`, or `{:keys [name]}`. Key-first pairs (`{:name n}`) are deprecated since 0.51.
- `php/new`, `php/->` and `php/::` are rejected (PHEL012). Use `(new Foo)`, `(.method obj)`, `Foo/static`.
- `/` on two integers returns a ratio: `(/ 1 2)` is `1/2`. Use `4.0` or `(float x)` when a float is meant.
- `catch \Exception` does not catch PHP `\Error` (for example `DivisionByZeroError`); catch `\Throwable` for both.

## Core syntax

```phel
(defn greet [name] (str "Hello, " name))      ; function
(defrecord Todo [id text done])                ; record (positional + map ctor)
(definterface Showable (show [this]))          ; interface - inline-implementable
(defprotocol Renderable (render [this]))       ; protocol - extend-type only
(extend-type Todo Renderable (render [t] ...)) ; protocol impl per type

(defmulti area :shape)                          ; multimethod
(defmethod area :circle [{r :radius}] (* 3.14 r r))

(into [] (comp (filter odd?) (map inc)) [1 2 3 4 5]) ; transducer
(re-find #"\d+" "abc123")                       ; regex literal
```

## PHP interop

```phel
(new DateTimeImmutable "2026-01-01")       ; constructor (also `(DateTimeImmutable. ...)`)
(.format obj "Y-m-d")                       ; method shorthand
(.-prop obj)                                ; property shorthand
DateTimeImmutable/ATOM                      ; static constant
(DateTimeImmutable/createFromFormat ...)    ; static method
(php/strlen "x")                            ; any PHP function
```

## This repo

- Docs: `content/documentation/`, `content/blog/`
- Phel scratch: `local/main.phel`
- Build config for the snippet runner: `build/phel-config.php`. Compile cache: `.phel/`
- CLI: `./vendor/bin/phel <run|eval|repl|test|build>`

For full language reference: `content/documentation/language/` and `content/documentation/reference/`.
