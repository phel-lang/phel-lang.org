+++
title = "Debugging"
weight = 72
description = "A practical debugging workflow for Phel: the dbg macro, tap>, source-mapped stack traces, the REPL, inspecting compiled PHP, Xdebug, and profiling."
+++

Phel ships a full debugging toolbox. This page is the map: pick the tool that matches your symptom, then follow the links for details.

| Symptom | Tool |
|---|---|
| "This expression returns the wrong value" | [`dbg`](#dbg-print-any-expression-in-place) |
| "I want to watch values flow through the program" | [`tap>`](#tap-decoupled-inspection-streams) |
| "Which calls happen, with which arguments?" | [`phel.trace`](#phel-trace-log-every-call-of-a-function) |
| "My program crashes and I don't understand the error" | [Read the stack trace](#reading-a-stack-trace) |
| "I want to poke at my code interactively" | [The REPL](#debug-in-the-repl) |
| "I want to pause right here and inspect locals" | [`(break)`](#break-a-repl-breakpoint-in-your-code) |
| "I need IDE breakpoints and step debugging" | [Xdebug](#step-debugging-with-xdebug) |
| "What PHP does my Phel become?" | [`phel compile`](#inspect-the-compiled-php) |
| "It works, but it's slow" | [`phel profile`](#find-the-slow-part) |

## dbg: print any expression in place

The `dbg` macro prints `[file:line] form => value` to **stderr** and returns the value unchanged, so you can wrap any subexpression without restructuring your code:

```phel
(defn area [w h]
  (* (dbg w) h))

(area 3 4)
;; stderr: [src/main.phel:2] w => 3
;; => 12
```

Because the value passes through, `dbg` drops into the middle of threading macros and nested calls:

```phel
(->> (range 10)
     (map inc)
     (dbg)          ; see the intermediate sequence
     (filter even?)
     (dbg))         ; ...and the filtered result
```

With no argument, `dbg` prints just `[file:line]` as a "reached here" marker and returns nil:

<!-- phel-test: skip -->
```phel
(when (some-condition? x)
  (dbg)             ; did we get into this branch?
  (handle x))
```

Output goes to stderr, so it never mixes with your program's stdout — pipe-friendly scripts stay clean, and you can silence debugging with `2>/dev/null`. In tests, redefine the underlying `dbg-write` function with `with-redefs` to capture the output.

For big nested structures, reach for `phel.pprint`:

```phel
(ns my-app
  (:require phel.pprint :refer [pprint]))

(pprint {:users [{:name "Alice" :roles [:admin :editor]}
                 {:name "Bob" :roles [:viewer]}]
         :count 2})
```

## tap>: decoupled inspection streams

`tap>` sends a value to every handler registered with `add-tap`. Producers don't know who is listening, so you can leave `tap>` calls in place and attach or detach inspection at will:

```phel
(add-tap println)                    ; attach an inspector
(tap> {:event :login :user "alice"}) ; anywhere in your code
(remove-tap println)                 ; detach when done
```

Since 0.49 the REPL registers a printing tap on startup, so `(tap> x)` is visible there out of the box (detach it with `(remove-tap phel.repl/print-tap)`). See [the REPL guide](/documentation/tooling/repl/#debug-helpers) for patterns like collecting tapped values into an atom during tests.

## phel.trace: log every call of a function

*Available since 0.49.*

When you need to see *how* a function is being called — argument flow, recursion shape, call order — instrument it with `phel.trace` (inspired by `clojure.tools.trace`). `deftrace` defines a function whose every call, including recursive ones, prints its arguments and result to stderr:

<!-- phel-test: skip -->
```phel
(ns my-app
  (:require phel.trace :refer [deftrace dotrace]))

(deftrace fact [n]
  (if (<= n 1) 1 (* n (fact (dec n)))))

(fact 3)
;; TRACE t1: (fact 3)
;; TRACE t2: | (fact 2)
;; TRACE t3: | | (fact 1)
;; TRACE t3: | | => 1
;; TRACE t2: | => 2
;; TRACE t1: => 6
```

To trace an *existing* function without touching its definition, wrap a body in `dotrace` — the named globals are traced inside the body and restored afterwards:

<!-- phel-test: skip -->
```phel
(dotrace [parse-row normalize]
  (import-csv "data.csv"))
```

The building blocks are also public: `(trace :tag value)` prints a tagged value and returns it, and `(trace-fn "name" f)` returns a traced wrapper for any function.

## Reading a stack trace

Phel compiles to PHP, but you never debug raw PHP line numbers: error output is **source-mapped back to your `.phel` files**. A failing script:

<!-- phel-test: skip -->
```phel
(ns my-app.main)

(defn div-all [nums d]
  (map (fn [n] (/ n d)) nums))

(println (first (div-all [1 2] 0)))
```

produces:

```text
Division by zero
   ... 1 internal frame
#1 src/main.phel:4 : (phel\core\/ 1 0)
   ... 5 internal frames
#8 src/main.phel:6 : (phel\core\first @[])
   ... 21 internal frames
```

How to read it:

- Each `#N file.phel:line : (fn args...)` frame is **your code** (or a core fn your code called), with real Phel file and line numbers plus the actual arguments.
- Runs of PHP-native frames (runtime internals, vendor code) are collapsed into `... N internal frames`. The full unfiltered PHP trace is always written to the error log if you need it.
- Many common failures come with an actionable `hint:` line after the trace — for example, calling something that isn't callable, wrong argument counts, or an undefined symbol suggesting a missing `(:require ...)`.

## Debug in the REPL

`phel repl` is the fastest feedback loop. The history variables `*1`, `*2`, `*3` hold recent results and `*e` holds the last exception, so you can grab a failing value and dissect it interactively. Combine with `doc`, `dir`, `apropos`, and `symbol-info` to explore unfamiliar code, and `require` with `:reload` to pull in fresh definitions as you edit.

The full tour lives in the [REPL guide](/documentation/tooling/repl/); for the editor-integrated variant see [`phel nrepl`](/documentation/tooling/editor-support/).

## break: a REPL breakpoint in your code

Drop `(break)` anywhere in a function and execution **pauses right there**, opening an interactive sub-REPL with every lexical local in scope:

<!-- phel-test: skip -->
```phel
(defn checkout [cart user]
  (let [total (cart-total cart)]
    (break)                       ; pause here
    (charge user total)))
```

```text
--- breakpoint ---
  cart = {:items [...]}
  user = {:id 42, :name "alice"}
  total = 99.5
type an expression to eval it with locals in scope; (continue) to resume
break>
```

At the `break>` prompt you can evaluate any expression against the captured locals — call functions on them, check invariants, reproduce the bug in place. Commands:

- `(continue)`, `continue`, or `c` — resume execution
- `:locals` or `l` — reprint the captured locals
- `Ctrl-D` (EOF) — resume

`(break)` is safe to leave in code that runs non-interactively: when there is no terminal attached (CI, pipes, parallel test workers, cron) it prints `--- breakpoint skipped ---` and resumes immediately instead of hanging.

## Step debugging with Xdebug

For IDE breakpoints, watches, and call-stack inspection, Phel supports [Xdebug](https://xdebug.org/). With the VS Code Phel extension you set breakpoints **directly in `.phel` files** — they're mapped to the compiled PHP automatically, traces show Phel locations, and Phel data structures render natively. Other editors (PhpStorm, Emacs, Neovim) attach to the compiled PHP output.

You can also trigger a hard breakpoint from code — the Xdebug counterpart of `(break)` — which halts the connected debugger exactly at that line, and is a no-op when Xdebug isn't loaded:

<!-- phel-test: skip -->
```phel
(when (php/function_exists "xdebug_break")
  (php/xdebug_break))
```

Setup, editor configs, and troubleshooting: [Xdebug Setup](/documentation/tooling/xdebug-setup/).

## Inspect the compiled PHP

When you want to understand what your Phel actually becomes — macro expansion questions, interop surprises, performance curiosity — ask the compiler directly:

```bash
phel compile '(defn double [x] (* x 2))'
```

```php
\Phel::addDefinition(
  "user",
  "double",
  new class() extends \Phel\Lang\AbstractFn {
    ...
  },
  ...
);
```

It compiles without evaluating, so it's safe to probe side-effecting code. For a whole project, `withKeepGeneratedTempFiles(true)` in `phel-config.php` preserves the generated PHP files for inspection (see [Configuration](/documentation/configuration/)).

To debug *macros* specifically, expand them step by step in the REPL with `macroexpand-1` and `macroexpand`.

## Find the slow part

When the bug is "it's correct but slow", don't guess:

```bash
phel profile src/main.phel
```

reports per-function call counts and self/total timings, plus compile-time phase costs. Sort with `--sort=total|self|calls|avg`, export JSON with `--format=json`. See [Performance](/documentation/performance/) for what to do with the results.

## Keep the loop tight

- `phel watch` reloads namespaces when files change, so print-debugging iterations don't pay startup cost.
- `phel test --filter <name>` reruns just the failing test while you bisect.
- PHP-side tools work too: `(php/var_dump x)`, Symfony VarDumper's `(php/dump x)` / `(php/dd x)` — see [PHP Debugging Tools](/documentation/tooling/php-tools/).

## Next steps

- [REPL](/documentation/tooling/repl/) - history vars, introspection helpers, tap patterns
- [Xdebug Setup](/documentation/tooling/xdebug-setup/) - breakpoints in `.phel` files
- [PHP Debugging Tools](/documentation/tooling/php-tools/) - `var_dump`, `dump`, `dd`
- [Testing](/documentation/testing/) - pin the bug down with a test once you've found it
