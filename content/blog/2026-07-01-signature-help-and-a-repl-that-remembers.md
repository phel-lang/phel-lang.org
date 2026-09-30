+++
title = "Signature Help and a REPL That Remembers"
aliases = [ "/blog/phel-0-47-clear-signals" ]
description = "Your editor now shows the arities and docstring of a Phel function while you type the call, and the REPL keeps your last three results in *1, *2 and *3."
date = 2026-07-01
+++

You type `(reduce ` and stop. Does the initial value come before the collection, or after? You leave the editor, search the docs, come back, and the thought you had is gone.

Then you evaluate something slow in the REPL. You look at the result and want to use it in the next form. It is gone too. You run the slow thing again.

Two small breaks in flow. Both happen dozens of times a day.

Now your editor shows the shape of the call while you type it, and the REPL keeps your last three results.

## Your editor shows the call while you type it

The Phel language server (`phel lsp`) answers the editor's _signature help_ request. That is the popup that appears when you open a call. It used to cover only `php/...` interop. Now it covers plain Phel functions too.

Type `(reduce + ` and the editor asks the server what `reduce` looks like. This is the real answer from `phel lsp`, trimmed to the useful fields:

```json
{
  "signatures": [
    {
      "label": "([f coll] [f init coll])",
      "documentation": "```phel\n(reduce f coll)\n(reduce f init coll)\n```\nReduces collection to a single value by repeatedly applying function to accumulator and elements. Respects early termination via `(reduced val)`."
    }
  ],
  "activeSignature": 0,
  "activeParameter": 0
}
```

Your editor turns that into a popup: both arities, the docstring, and the argument you are on. The question from the opening has its answer right there. `init` goes between the function and the collection:

```phel
(reduce + [1 2 3])
; => 6

(reduce + 10 [1 2 3])
; => 16
```

That matters most for functions where the order is not obvious. Here `init` is an empty map:

```phel
(reduce (fn [lengths word] (assoc lengths word (count word)))
        {}
        ["tea" "coffee"])
; => {"tea" 3, "coffee" 6}
```

Any editor that speaks LSP gets this. There is nothing to configure beyond pointing it at `phel lsp`.

## `(doc)` shows an example, not only a signature

Signature help tells you the order. Sometimes you want to see a call. `(doc sym)` in the REPL now prints the function's `:example` under an `Example:` heading:

<!-- phel-test: skip -->
```phel
(doc map)
```

```text
(map f)
(map f coll)
(map f coll & more)

Returns a lazy sequence of the result of applying `f` to all of the first items in each coll,
...
Example:
(map inc [1 2 3]) ; => (2 3 4)
```

**A signature tells you what to pass. An example tells you what comes back.**

## The REPL remembers your last three results

In `phel repl`, `*1` holds the last result, `*2` the one before, and `*3` the one before that. You can use them in the next form:

<!-- phel-test: skip -->
```phel
(+ 1 2)
; => 3

(* *1 10)
; => 30

[*1 *2]
; => [30 3]
```

Look at the last line. `[*1 *2]` is itself an evaluation, so it reads the history before its own result goes in. After it, `*1` is `[30 3]`.

This is the habit Clojure developers bring with them. Compute something slow once, look at it, then build on it without running it again.

## Your editor sees the same history over nREPL

Most people do not type into a terminal REPL. They evaluate forms from the editor over nREPL, the protocol that Calva, Conjure and CIDER speak.

`phel nrepl` now sends the history back with every eval response. These are three real responses from one session, trimmed to the value and history fields:

```json
{"value": "3",       "*1": "3",       "*2": "nil",  "*3": "nil"}
{"value": "\"ab\"",  "*1": "\"ab\"",  "*2": "3",    "*3": "nil"}
{"value": "(0 1 2)", "*1": "(0 1 2)", "*2": "\"ab\"", "*3": "3"}
```

The forms were `(+ 1 2)`, `(str "a" "b")` and `(range 3)`. Each value moves one slot down as a new one comes in. The history is per session.

Calva and Conjure read those fields and show the last three results. You see what you evaluated a minute ago without scrolling back through output.

## Also in Phel 0.47

- No breaking changes.
- `phel test` prints a `+`, `-`, `~` structural diff for any two collections of the same shape that differ, not only large ones.
- `phel compile` tells you on stderr when a form folds to a value and emits no PHP, for example `(+ 1 2)` to `3`.
- `phel init` scaffolds new configs at `->withOptimizationLevel(2)`. The runtime default stays `0`.
- Stdlib runtime errors like `(/ 1 0)` in `phel run`, `test` and `build` show the `.phel` location instead of a compiled temp path.
- "Did you mean" suggestions rank by relevance, so `prn` suggests `print`, `printf`, `println`.
- Requiring a missing namespace fails at require time, with a clear error.
- Startup is about 30% faster through a persistent OPcache file cache. Opt out with `PHEL_NO_OPCACHE_REEXEC=1`.
- `phel test --parallel` no longer fails at random from the PHAR, and it is no longer slower than a serial run.

Ask the editor, not the docs. Reuse the result, do not recompute it.

Shipped in [Phel 0.47](/releases/0-47-clear-signals/). Upgrade notes: [0.47](/documentation/reference/upgrading/#0-47).
