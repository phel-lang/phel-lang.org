+++
title = "Phel 0.48: Step Into"
aliases = [ "/blog/phel-0-48-step-into" ]
description = "A (break) debugger with the locals in scope, dbg and inspect for printing values in place, trampoline, reductions, subvec and with-open from Clojure, HTML test coverage, and a CLI that runs on read-only systems. What changed in 0.48 and how to upgrade."
date = 2026-07-15
+++

Phel 0.48, *Step Into*, is about looking inside running code. You can print a value without restructuring the expression, pause at a line and query the locals, and see which lines your tests never reach. No breaking changes: upgrade and keep going.

## Upgrade

```bash
composer require phel-lang/phel-lang:^0.48
./vendor/bin/phel cache:clear        # or: rm -rf .phel/cache
```

Always clear the cache after upgrading: compiled PHP from an earlier install can reference renamed internals and fail to load otherwise. This release also bumps the compiled-code cache format, so old entries are dropped once on the first run. Expect one cold compile. The [0.48 upgrade notes](/documentation/upgrading/#0-48) list every behaviour change.

## Print a value in place

`dbg` wraps any expression. It prints the file, the line, the form and its value to stderr, then returns the value. The code around it keeps working.

```phel
(defn area [w h]
  (* (dbg w) h))

(area 3 4)
; stderr: [/app/src/shapes.phel:2] w => 3
; => 12
```

`inspect`, from `phel.pprint`, does the same job for data. It pretty-prints the value to stdout (in color on a terminal) and hands it back, so it drops into a threading pipeline:

```phel
(ns app.main
  (:require phel.pprint :refer [inspect]))

(-> {:user "ada" :roles [:admin]}
    inspect
    (get :roles))
; stdout: {:user "ada", :roles [:admin]}
; => [:admin]
```

No more `let` blocks added only to print something.

## Stop and look around

`(break)` pauses at the call site and opens a small REPL with the local bindings in scope. Evaluate any expression against them. Type `:locals` to list them again, and `(continue)` to resume.

```phel
(defn total [xs]
  (let [sum (reduce + xs)]
    (break)
    sum))

(total [1 2 3])
```

At a terminal you get a `break>` prompt with `xs` and `sum` bound. Without one (CI, a pipe, a parallel test worker), the breakpoint prints a one-line notice and moves on.

A forgotten `(break)` never hangs a build.

## Clojure functions you kept reaching for

0.48 fills gaps that Clojure developers hit early:

```phel
(declare odd-steps?)
(defn even-steps? [n] (if (zero? n) true #(odd-steps? (dec n))))
(defn odd-steps? [n] (if (zero? n) false #(even-steps? (dec n))))

(trampoline even-steps? 100000) ; => true, without growing the stack
(reductions + [1 2 3 4])        ; => (1 3 6 10)
(subvec [:a :b :c :d :e] 1 3)   ; => [:b :c]

(with-open [in (php/fopen "php://memory" "w+")]
  (php/fwrite in "hello")
  (php/rewind in)
  (php/fgets in))               ; => "hello"
```

`with-open` closes every bound resource in reverse order, even when the body throws. Objects with a `close` method get it called, and PHP streams go through `fclose`. Also new: `reduce-kv`, `gcd` and `lcm`.

## Coverage you can read

Text coverage tells you a percentage. The HTML report shows you the lines:

```bash
phel test --coverage=html
```

It writes a self-contained report to `var/coverage/`. Open `index.html` and each `.phel` file shows up with its lines colored by hit or miss. Pass `--coverage=html:<dir>` to write it somewhere else. Coverage needs pcov or xdebug loaded.

## Runs where it can't write

The nixpkgs package of 0.47 failed its version check. The NixOS build sandbox has no writable project root, and Phel crashed trying to write its caches there. Now caches degrade quietly on a read-only system, and a pre-warmed cache is still read. `--help`, `doc`, `eval`, `run`, `compile` and the REPL all work.

Commands that must write a file fail loudly instead. `phel format`, `phel test --coverage` and `phel profile --output` report a clear error when the target is not writable.

## Leaner compiled PHP

`reduce` over a typed vector now compiles to a native `foreach`, and `(** x 2)` on a typed number becomes a plain multiplication. Repeated collection literals share one constant, and definition metadata takes less space. Your code does not change. The PHP it compiles to gets smaller and faster.

For the full list, see the [0.48 release notes](/releases/0-48-step-into/). Upgrade, clear the cache, and step into your code.
