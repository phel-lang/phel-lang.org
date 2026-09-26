+++
title = "Phel 0.52: Honest Output"
aliases = [ "/blog/phel-0-52-honest-output" ]
description = "php/new, php/-> and php/:: are now PHEL012 errors, runtime errors carry a code that phel explain decodes, stack traces read the same everywhere, and phel lint fails on files it cannot parse. What changed in 0.52 and how to upgrade."
date = 2026-09-19
+++

Phel 0.52, *Honest Output*, is about errors that say what went wrong. Every error now carries a code. Every command prints it the same way. Tools that used to report success on broken input now fail. One breaking change comes with it: three old interop forms are gone from source.

## Upgrade

```bash
composer require phel-lang/phel-lang:^0.52
./vendor/bin/phel cache:clear        # or: rm -rf .phel/cache
```

Always clear the cache after upgrading: compiled PHP from an earlier install can reference renamed internals and fail to load otherwise.

## The old interop forms are gone

`php/new`, `php/->` and `php/::` had modern replacements for a while. Now writing them is a compile error. The same goes for `set-var`.

<!-- phel-test: skip -->
```phel
(def released (php/new \DateTimeImmutable "2026-09-19"))
(php/-> released (format "Y-m-d"))
(php/:: \DateTimeImmutable (createFromFormat "!Y-m-d" "2026-09-24"))
```

```text
[PHEL012] "php/new" is no longer valid source for constructing a PHP object. Use "(new \Foo arg)" or "(\Foo. arg)" instead.
```

Each form has a direct replacement. The error names it for you.

```phel
(def released (new \DateTimeImmutable "2026-09-19"))
(.format released "Y-m-d")
; => "2026-09-19"

(.format (\DateTimeImmutable/createFromFormat "!Y-m-d" "2026-09-24") "D, d M Y")
; => "Thu, 24 Sep 2026"

\DateTimeInterface/ATOM
; => "Y-m-d\\TH:i:sP"

(def counter 0)
(alter-var-root (var counter) inc)
counter
; => 1
```

Macros that expand to the old forms keep working. The compiler still emits them, so your generated PHP does not change. Only hand-written source has to move. The rest of `php/*`, like `(php/strlen "abc")`, stays.

## Every error has a code

Runtime errors now carry a code, like compile errors always did. `PHEL400` is a value that is not callable, `PHEL401` a wrong arity, `PHEL402` a type error, `PHEL403` an index out of bounds, `PHEL404` a division by zero.

A code is only useful if you can look it up. `phel explain` does that, with no browser:

```bash
phel explain PHEL404
```

```text
Division by zero [PHEL404]

A division or remainder had zero on the right. `/`, `%`, `rem` and `php/intdiv` all raise it.

Example:
  (/ 1 0)

Fix:
  Guard the divisor before dividing.
```

Run `phel explain` with no code to see the whole list. The [Error Reference](/documentation/reference/errors/) covers every code and its fix.

## One stack trace, everywhere

An uncaught error used to look different in `phel run`, `phel eval` and the REPL. Now it reads the same in all three: the message, the `at` line, your frames, and one marker for the internal ones.

<!-- phel-test: skip -->
```phel
(ns app.stats)

(defn average [xs]
  (/ (reduce + 0 xs) (count xs)))

(println (average []))
```

```text
[PHEL404] Division by zero
  at src/app/stats.phel:4
#1 vendor/phel-lang/phel-lang/src/phel/core/math.phel:302 : (phel\core\/ 0 0)
#2 src/app/stats.phel:4 : (phel\core\/ 0 0)
#3 src/app/stats.phel:7 : (app\stats\average [])
   ... 24 internal frames (--stack-trace to show, full trace in .phel/error.log)
```

The `at` line points at your call, not at the compiled cache. An uncaught `ex-info` prints its data map under the message. When you need the hidden frames, pass `--stack-trace` to `phel run`, `phel eval` or `phel repl`.

## Tools that stop pretending

- `phel lint` on a file that does not parse used to print "No lint issues found." and exit `0`. Now it reports the error and exits `1`.
- The REPL ends on `(exit)` or `(quit)`. `(exit 3)` sets the exit status.
- The OPcache file cache in `.phel/opcache` no longer grows forever. Every run prunes stale entries, `phel cache:clear` empties it, and `phel doctor` reports its size.

If your CI ran `phel lint` on broken files and passed, it will fail now. That is the point.

For the full list, see the [0.52 release notes](/releases/0-52-honest-output/) and the [0.52 upgrade notes](/documentation/upgrading/#0-52). Upgrade. Fix the PHEL012 errors. Let the next error explain itself.
