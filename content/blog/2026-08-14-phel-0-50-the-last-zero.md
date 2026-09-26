+++
title = "Phel 0.50: The Last Zero"
aliases = [ "/blog/phel-0-50-the-last-zero" ]
description = "The clean-up before 1.0: legacy reader syntax and core aliases are gone, core functions check their arity, Clojure-style PHP interop is the way to reach PHP, and phel balance and phel bench arrive. What changed in 0.50 and how to upgrade."
date = 2026-08-14
+++

Phel 0.50, *The Last Zero*, is the clean-up before 1.0. Everything that printed a deprecation notice is gone: old reader shortcuts, old core aliases, old CLI flags. What stays is one spelling per idea, and that spelling matches Clojure. This post shows what breaks, how to fix it, and what you get back.

## Upgrade

```bash
composer require phel-lang/phel-lang:^0.50
./vendor/bin/phel cache:clear        # or: rm -rf .phel/cache
```

Always clear the cache after upgrading: compiled PHP from an earlier install can reference renamed internals and fail to load otherwise.

Do one step first. Stay on 0.49 and run your suite with deprecation warnings on:

```bash
PHEL_WARN_DEPRECATIONS=1 ./vendor/bin/phel test
```

Each warning names a file and a line. **A clean run means the upgrade is a version bump.**

## The old syntax is gone

Here is code that ran on 0.49:

<!-- phel-test: skip -->
```phel
# a line comment
(map |(* $ 2) [1 2 3])

(defmacro inc-tmp [x]
  `(let [tmp$ ,x] (+ tmp$ 1)))

(push [1 2] 3)
```

And the same code on 0.50:

```phel
;; a line comment
(map #(* % 2) [1 2 3])
; => (2 4 6)

(defmacro inc-tmp [x]
  `(let [tmp# ~x] (+ tmp# 1)))

(inc-tmp 41)
; => 42

(conj [1 2] 3)
; => [1 2 3]
```

The mapping is mechanical:

- `#` comments and `#| |#` blocks become `;` and `;;`.
- `|(...)` short functions become `#(...)`, with `%` and `%1` instead of `$` and `$1`.
- `,` and `,@` become `~` and `~@`.
- `foo$` gensyms become `foo#`.
- The old core aliases go to their Clojure names: `push` to `conj`, `put` to `assoc`, `unset` to `dissoc`, `values` to `vals`, `function?` to `fn?`, and a few more.

Most of these fail loudly. A bare `#` stops the lexer with `[PHEL310] Cannot lex '#': no token starts with it.` A short function reports `Cannot resolve symbol '|'`. A removed alias reports `Cannot resolve symbol 'push'. Did you mean 'pos?', 'peek', or 'pmap'?`

The compiler finds those for you.

## Watch the comma

Two changes fail silently. They are the reason to run the warnings on 0.49 first.

`,` is now whitespace, as in Clojure. So an old macro still compiles, and quotes its argument instead of inserting it:

<!-- phel-test: skip -->
```phel
(defmacro twice [x]
  `(do ,x ,x))

(twice (println "hi"))
```

That expands to `(do x x)`. The call fails at the use site with `[PHEL001] Cannot resolve symbol 'x'`. In a bigger macro, the wrong symbol can resolve to something and never fail at all.

`foo$` is now an ordinary symbol. The macro still runs, but `tmp$` no longer gets a unique name, so it can capture a user's variable of the same name.

Sweep for commas glued to the next token:

```bash
grep -rnE ',[^[:space:]]' --include='*.phel' src/ tests/
```

A comma followed by a space is fine: `{:a 1, :b 2}` is idiomatic. Sweep anything that *generates* Phel too, like templates or PHP heredocs.

> The loud breaks cost you a minute. The quiet ones cost you a bug report.

## A stricter core

Core functions now declare real arities instead of taking a rest argument. A wrong argument count is an error, not something ignored:

```text
[PHEL002] Wrong number of arguments to function "phel.core\get". Got: 1. Expected: 2 or 3
```

`(max)` and `(min)` with no arguments now fail at compile time. An unresolved `(:require ...)` fails when the namespace loads, not when the missing name is first called.

You will see one change in every REPL session. Lazy sequences print like lists now. On 0.49:

```text
(map inc [1 2 3])
; => @[2 3 4]
```

On 0.50:

```phel
(map inc [1 2 3])
; => (2 3 4)

(pr-str (take 3 (range)))
; => "(0 1 2)"
```

The old form did not read back. `@` is the deref reader macro, so `@[2 3 4]` meant something else. Now `@` means deref and nothing more, and the output matches Clojure. If a test compares printed output against `"@[...]"`, update the expected string.

On the CLI, `phel index --out` is now `--output` and `phel config --json` is now `--format=json`. If you embed Phel, it now needs Gacela 2 and Symfony Console 7.3 or later. The [Upgrading notes](/documentation/upgrading/#0-50) list the rest.

## Clojure-style PHP interop

Every PHP member now has a Clojure spelling: methods, properties, static calls, constants, enum cases and assignment.

```phel
(def d (new \DateTimeImmutable "2026-08-14"))

(.format d "Y-m-d")
; => "2026-08-14"

(-> d (.modify "+1 day") (.format "D, d M"))
; => "Sat, 15 Aug"

(.-name \RoundingMode/HalfEven)
; => "HalfEven"

(def box (new \stdClass))
(set! (.-count box) 1)
(.-count box)
; => 1
```

The editor completes and hovers these, static properties and PHP superglobals included. 0.50 deprecated `php/new`, `php/->` and `php/::`. Since 0.52 they are a compile error, so move now. The design is in [ADR 0007](https://github.com/phel-lang/phel-lang/blob/v0.50.0/docs/adr/0007-clojure-style-interop-is-the-source-spelling.md).

## Two new commands

`phel balance` finds unbalanced `()`, `[]` and `{}`. It reads the lexer's tokens, so a paren inside a string or a comment never counts. `--fix` appends the missing closers:

```bash
./vendor/bin/phel balance src/
./vendor/bin/phel balance --fix src/
```

```text
Unbalanced (1):
  src/app/greet.phel:3:0: unclosed '(', needs ')'
Run again with --fix to append the missing delimiters.
```

It refuses anything with more than one plausible fix, like a surplus closer or an unterminated string. It won't guess.

`phel bench` runs benchmarks written in Phel:

```phel
(ns app.sum-bench
  (:require phel.bench :refer [defbench]))

(defbench sum-a-range {:revs 1000}
  (reduce + 0 (range 100)))
```

Store a baseline, then fail a run that regresses by more than a set percentage:

```bash
./vendor/bin/phel bench --store=.phel/bench-baseline.json
./vendor/bin/phel bench --ref=.phel/bench-baseline.json --tolerance=10
```

## Faster, by a lot

Real arities are also a speed change. A call no longer packs its arguments into a rest list and unpacks them again. `partial` is 30x faster, `fnil` 50x, `sort` on strings 22.7x, `sum` over PHP ints 27.5x. The compiler infers collection types from literals, so `get-in`, `nth` and `reduce` take native paths on ordinary code.

One behaviour change rides along: `sort-by` now calls its key function once per element, not twice per comparison. The result is the same. An impure key function runs far fewer times.

For the full list, see the [0.50 release notes](/releases/0-50-the-last-zero/). Run the warnings. Fix what they name. Then bump.
