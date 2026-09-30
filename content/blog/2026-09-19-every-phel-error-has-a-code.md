+++
title = "Every Phel Error Has a Code"
aliases = [ "/blog/phel-0-52-honest-output" ]
description = "Every Phel error now starts with a code like PHEL404, and phel explain turns that code into a cause, an example and a fix. Uncaught errors print one short stack trace that points at your line."
date = 2026-09-19
+++

A job fails in CI. The log says "Division by zero", then thirty frames of compiled PHP from a cache folder you never opened. Which line of yours was it? You scroll. You guess. You add a `println` and push again.

The message was right. It was not useful.

Phel errors now start with a code. The code has a name, and `phel explain` tells you what it means and how to fix it, from the terminal. The stack trace under it points at your file and hides the rest.

> An error message tells you what broke. A code tells you where to read next.

## Every error starts with a code

Compile errors always had a code. Runtime errors now have one too. Take a function that averages an empty list:

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
```

Five codes cover the common runtime failures:

- `PHEL400`: a value in call position is not a function.
- `PHEL401`: a function got the wrong number of arguments.
- `PHEL402`: an argument has the wrong type.
- `PHEL403`: an index is out of bounds.
- `PHEL404`: a division by zero.

They show up where you would expect them:

<!-- phel-test: skip -->
```phel
(nth [1 2 3] 5)
; [PHEL403] Vector index 5 out of bounds

(def limit 5)
(limit 1)
; [PHEL400] Value of type int is not callable
```

A code is short enough to search for, paste in a chat, or grep in a log. It stays the same when the wording of the message changes.

## `phel explain` decodes it in the terminal

A code is only useful if you can look it up. No browser needed:

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

Every entry has the same three parts: what it means, the smallest program that raises it, and the fix. Some entries teach you something the message cannot. `PHEL403` reminds you that `get` returns `nil` where `nth` throws:

```bash
phel explain PHEL403
```

```text
Index out of bounds [PHEL403]

An indexed read asked for a position the collection does not have. `nth` throws here; `get` returns `nil` instead.

Example:
  (nth [1 2] 9)

Fix:
  Check the index against `(count coll)`, or use `get` with a default.
```

Run `phel explain` with no code to list them all, from `PHEL001 Undefined symbol` to `PHEL404 Division by zero`. The [Error Reference](/documentation/reference/errors/) holds the same text on the web.

## The stack trace points at your line

An uncaught error used to look different in `phel run`, `phel eval` and the REPL. Now it reads the same in all three: the code and message, an `at` line, your frames, and one line for everything internal.

```text
[PHEL404] Division by zero
  at src/app/stats.phel:4
#1 vendor/phel-lang/phel-lang/src/phel/core/math.phel:302 : (phel\core\/ 0 0)
#2 src/app/stats.phel:4 : (phel\core\/ 0 0)
#3 src/app/stats.phel:6 : (app\stats\average [])
   ... 24 internal frames (--stack-trace to show, full trace in .phel/error.log)
```

The `at` line is the answer to "which line of mine?". It points at your call, even when the error was raised deep inside the core library. Frame `#3` shows the call that started it, with the empty vector that caused it.

When you need the hidden frames, the last line tells you how: pass `--stack-trace` to `phel run`, `phel eval` or `phel repl`, or open `.phel/error.log`. The log is plain text now. Each entry starts with a timestamp and the command, and the file rotates at 1 MiB.

## Your `ex-info` data prints too

Most of your own errors are `ex-info`: a message plus a map of the facts. When one goes uncaught, the map now prints under the `at` line:

<!-- phel-test: skip -->
```phel
(ns app.billing)

(defn charge [order]
  (when-not (pos? (:amount order))
    (throw (ex-info "Invalid amount" {:order-id (:id order) :amount (:amount order)}))))

(charge {:id 42 :amount -5})
```

```text
Invalid amount
  at src/app/billing.phel:5
  data: {:order-id 42, :amount -5}
```

The order id and the bad amount are right there. No need to reproduce the failure to learn which order it was.

## Compile errors show the line and a caret

Compile errors now all print the same way: code, file and line, the source around it, a caret under the problem. Forget a closing paren and the error names the line where the list opened, not the end of the file:

<!-- phel-test: skip -->
```phel
(ns app.cart)

(defn total [xs]
  (reduce + 0 xs)
```

```text
[PHEL100] Unterminated list starting at line 3. Did you forget a closing ')'?
in src/app/cart.phel:3

3| (defn total [xs]
   ^
4|   (reduce + 0 xs)
```

`phel explain PHEL100` adds the tip: run `phel balance` to find the missing one.

`phel lint` agrees now. On a file that does not parse, it used to print "No lint issues found." and exit `0`. Now it reports the same `PHEL100` and exits `1`. If your CI linted broken files and passed, it fails now. That is the point.

## Also in Phel 0.52

- **Breaking:** `php/new`, `php/->`, `php/::` and `set-var` are no longer valid source. Each one is a `PHEL012` error that names its replacement: `(new DateTimeImmutable "2026-09-19")`, `(.format d "Y-m-d")`, `DateTimeImmutable/createFromFormat`, `(alter-var-root (var v) f)`. Macros that expand to them keep working, and the rest of `php/*` stays.
- A special form with too few arguments names the form and the shape it wants, instead of an internal `PHEL403`.
- Analyzer messages use Phel names: `nil`, not `null`; `vector`, not the PHP class.
- The REPL ends on `(exit)` or `(quit)`, and `(exit 3)` sets the exit status.
- The OPcache file cache in `.phel/opcache` stops growing: every run prunes stale entries, `phel cache:clear` empties it, and `phel doctor` reports its size.
- `phel test` shows the source form on the `Form:` line of a failed assertion, not the evaluated value.

Next time CI fails, read the code first. Then ask `phel explain`.

Shipped in [Phel 0.52](/releases/0-52-honest-output/). Upgrade notes: [0.52](/documentation/reference/upgrading/#0-52).
