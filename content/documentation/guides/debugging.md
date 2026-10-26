+++
title = "Debugging"
weight = 11
description = "Debug Phel code step by step: dbg, tap>, trace, stack traces, REPL breakpoints, Xdebug, PHP dump tools, compiled PHP, and profiling."
aliases = ["/documentation/debugging/", "/documentation/tooling/php-tools/"]
+++

This page takes you from the quickest print to a full step debugger. Start at the top and move down only when the simpler tool does not answer your question.

| Question | Tool |
|---|---|
| What does this expression return? | [`dbg`](#print-a-value-with-dbg) |
| What values flow through the program? | [`tap>`](#watch-values-with-tap) |
| Which calls happen, with which arguments? | [`phel.trace`](#trace-function-calls) |
| Why did it crash? | [The stack trace](#read-a-stack-trace) |
| What are the locals at this line? | [`(break)`](#pause-with-break) |
| I need IDE breakpoints and stepping | [Xdebug](#step-through-with-xdebug) |
| What PHP does my Phel become? | [`phel compile`](#inspect-the-compiled-php) |
| It works, but it is slow | [`phel profile`](#find-the-slow-part) |

## Print a value with dbg

`dbg` prints `[file:line] form => value` to stderr and returns the value unchanged. Wrap any subexpression without changing your code's shape:

```phel
(defn area [w h]
  (* (dbg w) h))

(area 3 4)
;; stderr: [src/main.phel:2] w => 3
;; => 12
```

Because the value passes through, `dbg` works inside threading macros:

```phel
(->> (range 10)
     (map inc)
     (dbg)          ; the mapped sequence
     (filter even?)
     (dbg))         ; the filtered result
```

With no argument, `dbg` prints only `[file:line]` and returns nil. Use it as a "reached here" marker:

<!-- phel-test: skip -->
```phel
(when (some-condition? x)
  (dbg)
  (handle x))
```

Output goes to stderr, so it never mixes with stdout. Hide it with `2>/dev/null`. In tests, capture it by redefining `dbg-write` with `with-redefs`.

For large nested data, use `phel.pprint`:

```phel
(ns my-app
  (:require phel.pprint :refer [pprint]))

(pprint {:users [{:name "Alice" :roles [:admin :editor]}
                 {:name "Bob" :roles [:viewer]}]
         :count 2})
```

### PHP dump functions

Every PHP function works through the `php/` prefix, so `var_dump` is always available:

```phel
(php/var_dump (+ 3 3))
;; stdout: int(6)
```

For richer output (colors, collapsible HTML in the browser, circular references), add [Symfony VarDumper](https://symfony.com/doc/current/components/var_dumper.html) as a dev dependency:

```json
"require-dev": {
    "symfony/var-dumper": "^7.4"
}
```

Then `dump` prints a value and continues, and `dd` prints it and stops the script:

<!-- phel-test: skip -->
```phel
(php/dump (+ 4 4))   ; prints 8, keeps running
(php/dd (+ 5 5))     ; prints 10, then exits
```

These PHP tools print Phel collections as PHP objects, with all their internal fields. Use them for PHP objects from interop code, and `dbg` or `pprint` for Phel data.

## Watch values with tap>

`tap>` sends a value to every handler registered with `add-tap`. The code that calls `tap>` does not know who listens, so you can leave the calls in place and attach a handler only when you need it:

```phel
(add-tap println)                    ; attach a handler
(tap> {:event :login :user "alice"}) ; anywhere in your code
(remove-tap println)                 ; detach it
```

The REPL registers a printing tap on start, so `(tap> x)` shows up there with no setup. Remove it with `(remove-tap phel.repl/print-tap)`. The [REPL guide](/documentation/tooling/repl/#debug-helpers) shows how to collect tapped values in an atom.

## Trace function calls

`phel.trace` shows how a function is called: arguments, results, and recursion depth. `deftrace` defines a function that prints every call to stderr:

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

To trace existing functions without editing them, use `dotrace`. It traces the named functions inside the body and restores them afterwards:

<!-- phel-test: skip -->
```phel
(dotrace [parse-row normalize]
  (import-csv "data.csv"))
```

Two smaller helpers are public too: `(trace :tag value)` prints a tagged value and returns it, and `(trace-fn "name" f)` returns a traced wrapper for any function.

## Read a stack trace

Errors point at your `.phel` files and lines, not at the generated PHP. This script divides by zero:

<!-- phel-test: skip -->
```phel
(ns my-app.main)

(defn div-all [nums d]
  (map (fn [n] (/ n d)) nums))

(println (first (div-all [1 2] 0)))
```

Output (paths shortened):

```text
[PHEL404] Division by zero
  at src/main.phel:4
#1 vendor/phel-lang/phel-lang/src/phel/core/math.phel:302 : (phel\core\/ 1 0)
#2 src/main.phel:4 : (phel\core\/ 1 0)
#3 vendor/phel-lang/phel-lang/src/php/Lang/Generators/TransformGenerator.php:53 : (my-app\main\div-all 1)
#10 src/main.phel:6 : (phel\core\first ())
   ... 30 internal frames (--stack-trace to show, full trace in .phel/error.log)
```

- The first line has the error code. `vendor/bin/phel explain PHEL404` explains it and shows the smallest program that raises it. All codes are listed in [Error codes](/documentation/reference/errors/).
- Each `#N file.phel:line : (fn args...)` frame is your code or a core function it called, with the real arguments.
- Runtime internals collapse into `... N internal frames`. Pass `--stack-trace` to see them. The full trace is always in `.phel/error.log`.
- Many errors end with a `hint:` line, for example a missing `(:require ...)` for an undefined symbol.

## Pause with break

Put `(break)` in a function and execution stops there. You get a sub-REPL with every local in scope:

<!-- phel-test: skip -->
```phel
(defn checkout [cart user]
  (let [total (cart-total cart)]
    (break)
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

At the `break>` prompt, evaluate any expression with those locals. Commands:

| Command | Effect |
|---|---|
| `(continue)`, `continue`, `c`, or `Ctrl-D` | Resume |
| `:locals` or `l` | Print the locals again |

Without a terminal (CI, pipes, parallel test workers, cron), `(break)` prints `--- breakpoint skipped ---` and continues, so a forgotten one never hangs a job.

In the plain [REPL](/documentation/tooling/repl/), `*1`, `*2`, `*3` hold recent results and `*e` holds the last exception. Use `macroexpand-1` and `macroexpand` to see what a macro produces:

```phel
(macroexpand-1 '(when x y))
; => (if x (do y))
```

## Step through with Xdebug

For IDE breakpoints, watches, and stepping, use [Xdebug](https://xdebug.org/). With the VS Code Phel extension you set breakpoints in `.phel` files and see Phel values. Other editors (PhpStorm, Emacs, Neovim) debug the compiled PHP. [Xdebug Setup](/documentation/tooling/xdebug-setup/) covers install and editor config.

To stop the connected debugger at a line from code (a no-op without Xdebug):

<!-- phel-test: skip -->
```phel
(when (php/function_exists "xdebug_break")
  (php/xdebug_break))
```

## Inspect the compiled PHP

`phel compile` prints the PHP for a snippet or a file without running it, so it is safe on code with side effects:

```bash
vendor/bin/phel compile '(defn greet [name] (str "Hello, " name "!"))'
```

Output, trimmed:

```php
\Phel::addDefinition(
  "user",
  "greet",
  new class() extends \Phel\Lang\AbstractFn {
    public const BOUND_TO = "user\\greet";

    public function __invoke($name): string {
      return (\Phel\Lang\Registry::readRoot("phel.core", "str"))->__invoke("Hello, ", $name, "!");
    }
  },
  // ... location and metadata
);
```

Every `defn` becomes a class that extends `AbstractFn`, registered under its namespace. Core functions are looked up through the registry. Use this to debug interop, report a compiler bug, or see why something is slow.

### Keep the generated files

`phel run` and the REPL compile to temp files such as `$TMPDIR/phel/tmp/__phel_<hash>.php` and delete them afterwards. An error that names one of those files then points at a file that is gone. Keep them in a local config file:

```php
<?php # phel-config-local.php

return (require __DIR__ . '/phel-config.php')
    ->withKeepGeneratedTempFiles(true)
;
```

Add `phel-config-local.php` to `.gitignore` so your dev settings stay out of the shared config. See [Configuration](/documentation/reference/configuration/) for other dev settings.

### Show all PHP errors

To see every PHP warning, notice, and deprecation during development, turn on error reporting in the same file:

```php
<?php # phel-config-local.php

error_reporting(E_ALL);
ini_set('display_errors', '1');

return (require __DIR__ . '/phel-config.php')
    ->withKeepGeneratedTempFiles(true)
;
```

## Find the slow part

When the code is correct but slow, measure before you change anything:

```bash
vendor/bin/phel profile src/main.phel
```

It reports call counts and self and total time per function, plus compile phase costs. Sort with `--sort=total|self|calls|avg` and export with `--format=json`. [Performance](/documentation/guides/performance/) explains what to do with the results.

## Keep the loop short

- `vendor/bin/phel watch` reloads namespaces when files change, so you skip startup cost between tries.
- `vendor/bin/phel test --filter <name>` reruns one test. Once you find the bug, pin it with a [test](/documentation/guides/testing/).
