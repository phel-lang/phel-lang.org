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
# One-shot eval
./vendor/bin/phel run -e '(println (map inc [1 2 3]))'

# REPL session for exploration
./vendor/bin/phel repl
```

Write the snippet, run it, paste real output. If output differs from what you assumed, fix the doc - not the runtime.

## Gotchas (project-specific)

- `defprotocol` cannot be implemented inline in `defstruct`. Use `definterface` for inline; `defprotocol` + `extend-type` per struct.
- `extends?` works only on primitive type keywords (`:string`, `:int`). Returns `false` for struct types. Use `satisfies?` on instances instead.
- CLI args: `*argv*`, not `php/$argv`.
- Side effects: `doseq` / `foreach`. Build sequences: `for`. Mixing causes wrong return shape.
- String module: `phel\string` (not `phel\str`).
- Phel vectors print as `@[...]`. Clojure prints `[...]`. Match the runtime output in docs.

## Core syntax

```phel
(defn greet [name] (str "Hello, " name))      ; function
(defrecord Todo [id text done])                ; record (positional + map ctor)
(definterface Showable (show [this]))          ; interface - inline-implementable
(defprotocol Renderable (render [this]))       ; protocol - extend-type only
(extend-type Todo Renderable (render [t] ...)) ; protocol impl per type

(defmulti area :shape)                          ; multimethod
(defmethod area :circle [{:radius r}] (* 3.14 r r))

(into [] (comp (filter odd?) (map inc)) [1 2 3 4 5]) ; transducer
(re-find #"\d+" "abc123")                       ; regex literal
```

## PHP interop

```phel
(DateTimeImmutable. "2026-01-01")          ; constructor shorthand
(.format obj "Y-m-d")                       ; method shorthand
(.-prop obj)                                ; property shorthand
DateTimeImmutable/ATOM                      ; static constant
(DateTimeImmutable/createFromFormat ...)    ; static method
(php/strlen "x")                            ; any PHP function
```

## This repo

- Docs: `content/documentation/`, `content/blog/`
- Phel scratch: `local/main.phel`
- Build artifacts: `build/phel-config.php`, `.phel/`
- CLI: `./vendor/bin/phel <run|repl|test|build>`

For full language reference: `content/documentation/language/` and `content/documentation/reference/`.
