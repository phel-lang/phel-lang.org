+++
title = "Debug Phel with `(break)`, `dbg` and `inspect`"
aliases = [ "/blog/phel-0-48-step-into" ]
description = "Stop a running function and query its locals with `(break)`. Print any value in place with `dbg` and `inspect`, without reshaping your code."
date = 2026-07-15
+++

A function returns the wrong number. You want to see one value in the middle of it. So you wrap the expression in a `let`, add a `println`, run it again, and later undo all of that.

Most of the time you only need to look. Not rewrite.

Phel gives you three tools for that. `dbg` prints a value where it stands. `inspect` does the same inside a pipeline. `(break)` stops the function and lets you ask questions about its locals. None of them changes what your code returns.

## `dbg` prints a value and hands it back

Wrap any expression in `dbg`. It prints the file, the line, the form and its value to stderr. Then it returns the value, so the code around it keeps working.

```phel
(defn area [w h]
  (* (dbg w) h))

(area 3 4)
; stderr: [/app/src/shapes.phel:2] w => 3
; => 12
```

It works on any form, not only on a name. The printed line shows the source form, so you know which `dbg` spoke when you have more than one:

```phel
(defn price [qty unit]
  (let [subtotal (* qty unit)
        tax      (* subtotal 0.2)]
    (dbg (+ subtotal tax))))

(price 2 10)
; stderr: [/app/src/shop.phel:4] (+ subtotal tax) => 24.0
; => 24.0
```

Output goes to stderr on purpose. Your program's real output on stdout stays clean, so you can pipe it and still see the debug lines in the terminal.

## `inspect` fits into a pipeline

`dbg` is good for one value. For data, you want to read the shape. `inspect`, from `phel.pprint`, pretty-prints the value to stdout (in color on a terminal) and returns it unchanged.

Because it takes the value as its only argument, it drops into a threading macro as one more step:

```phel
(ns app.main
  (:require phel.pprint :refer [inspect]))

(-> {:user "ada" :roles [:admin]}
    inspect
    (get :roles))
; stdout: {:user "ada", :roles [:admin]}
; => [:admin]
```

Add the line, read the data, delete the line. The pipeline never changes shape.

> Print it in place. Then delete one line.

## `(break)` stops and lets you ask questions

Printing works when you know what to print. Sometimes you don't. You want to stop at a line and look around.

Put `(break)` inside a function. When execution reaches it, Phel pauses and opens a small REPL with the local bindings in scope:

```phel
(defn total [xs]
  (let [sum (reduce + xs)]
    (break)
    sum))

(total [1 2 3])
```

Run it from a terminal and you get this session. The lines after `break>` are what I typed:

```text
--- breakpoint ---
  xs = [1 2 3]
  sum = 6
type an expression to eval it with locals in scope; (continue) to resume
break> sum
=> 6
break> (count xs)
=> 3
break> :locals
  xs = [1 2 3]
  sum = 6
break> (continue)
6
```

Phel lists the locals first, so you often see the problem before you type anything. Then any expression you type runs against those bindings: `(count xs)`, `(filter odd? xs)`, a call to another function in your namespace. `:locals` prints the list again. `(continue)` resumes, and the function returns as usual. The last `6` in the session is the program's own output.

This is the loop you want for a bad value deep in a call chain. Stop there. Ask. Resume.

## A forgotten `(break)` never hangs a build

A breakpoint that waits for input is a risk. Leave one in, and a CI job waits forever.

Phel checks for a terminal first. Without one (CI, a pipe, a parallel test worker), `(break)` prints one line and moves on:

```text
--- breakpoint skipped (no interactive terminal) ---
6
```

Closing stdin also resumes, so `Ctrl-D` at the `break>` prompt works like `(continue)`. You still want to delete it before you commit. But a slip costs you one line in the log, not a stuck pipeline.

All three tools are covered in more depth in the [debugging guide](/documentation/guides/debugging/), next to `tap>`, stack traces and Xdebug.

## Also in Phel 0.48

- No breaking changes. The compiled-code cache format changed, so the first run after the upgrade recompiles everything once.
- New core functions from Clojure: `trampoline`, `reductions`, `subvec`, `reduce-kv`, `gcd`, `lcm`, `arity` and `variadic?`.
- `with-open` closes every bound resource in reverse order, even when the body throws.
- `phel test --coverage=html` writes a self-contained HTML report with each `.phel` line colored by hit or miss.
- Phel runs on read-only systems such as the NixOS build sandbox. Caches degrade quietly, and commands that must write a file fail with a clear error.
- `phel export` stubs carry native PHP types and docblocks from `:tag` metadata.
- Smaller, faster compiled PHP: `reduce` over a typed vector becomes a native `foreach`, and repeated collection literals share one constant.

Stop guessing what a value is. Look at it.

Shipped in [Phel 0.48](/releases/0-48-step-into/). Upgrade notes: [0.48](/documentation/reference/upgrading/#0-48).
