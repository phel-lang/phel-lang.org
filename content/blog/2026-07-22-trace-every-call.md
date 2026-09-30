+++
title = "Trace Every Call with `phel.trace`"
aliases = [ "/blog/phel-0-49-arity-lane" ]
description = "See every call a function makes, with its arguments and its result, indented by depth. `phel.trace` brings `clojure.tools.trace` to Phel, and multi-arity calls got 1.5-2x faster."
date = 2026-07-22
+++

An order total comes out wrong. The total calls a line function, which calls a parser, which calls something else. Somewhere in that chain one value goes bad.

`dbg` shows you one value. You need the whole path: which function ran, with which arguments, and what it returned.

That is what `phel.trace` does. It follows `clojure.tools.trace`: wrap a function, call it, and every call and result prints to stderr, indented by depth.

## `deftrace` shows the whole call tree

`deftrace` works like `defn`. The difference: every call to the function prints its arguments and its result. Recursive calls are traced too.

```phel
(ns app.main
  (:require phel.trace :refer [deftrace]))

(deftrace fact [n]
  (if (< n 2) 1 (* n (fact (dec n)))))

(fact 3)
; TRACE t1: (fact 3)
; TRACE t2: | (fact 2)
; TRACE t3: | | (fact 1)
; TRACE t3: | | => 1
; TRACE t2: | => 2
; TRACE t1: => 6
```

Read it top to bottom. Each call gets an id (`t1`, `t2`, `t3`). The `|` bars show how deep you are. The line with `=>` and the same id is the result of that call, so you can match every call with its return.

It is recursion, drawn for you.

## `dotrace` traces code you don't want to edit

`deftrace` means changing the definition. Often you don't want that. The functions already exist, and you only want to watch them for one run.

`dotrace` takes a vector of function names and a body. While the body runs, those functions are traced. After it, they go back to normal.

Here is the order total from the start of this post:

```phel
(ns app.main
  (:require phel.trace :refer [dotrace]))

(defn parse-qty [s] (php/intval s))
(defn line-total [line] (* (parse-qty (:qty line)) (:price line)))
(defn order-total [lines] (reduce + (map line-total lines)))

(dotrace [parse-qty line-total]
  (order-total [{:qty "2" :price 5} {:qty "x" :price 3}]))
; TRACE t1: (line-total {:qty 2, :price 5})
; TRACE t2: | (parse-qty 2)
; TRACE t2: | => 2
; TRACE t1: => 10
; TRACE t3: (line-total {:qty x, :price 3})
; TRACE t4: | (parse-qty x)
; TRACE t4: | => 0
; TRACE t3: => 0
; => 10
```

There is the bug. `parse-qty` got `"x"` and returned `0`, with no error. The second line counted for nothing. No `println` added, no function changed.

Traces print values the way `print` does, so strings show without quotes: `x` in the output is the string `"x"`.

> `dbg` shows a value. `dotrace` shows a conversation.

`dotrace` swaps the functions for the whole process while the body runs, like `with-redefs`. Use it for debugging and tests, not in production code that runs in parallel.

## `trace` and `trace-fn` for the small cases

Two smaller helpers cover the rest.

`trace` prints a value with an optional tag and returns it. It takes the value last, so it fits in a `->>` pipeline:

```phel
(ns app.main
  (:require phel.trace :refer [trace]))

(->> [3 1 2]
     sort
     (trace :sorted)
     (map inc))
; stderr: TRACE :sorted: [1 2 3]
; => (2 3 4)
```

`trace-fn` wraps a function value. Use it where you pass a function along, such as to `map` or `mapv`:

```phel
(ns app.main
  (:require phel.trace :refer [trace-fn]))

(mapv (trace-fn "inc" inc) [1 2])
; TRACE t1: (inc 1)
; TRACE t1: => 2
; TRACE t2: (inc 2)
; TRACE t2: => 3
; => [2 3]
```

The call ids keep counting across the whole process. Call `(reset-trace-state!)` to start again from `t1`, which helps when you compare two runs or check the output in a test.

The [debugging guide](/documentation/guides/debugging/#trace-function-calls) puts `phel.trace` next to `dbg`, `tap>` and `(break)`, with a table of which tool answers which question.

## Multi-arity calls take a faster path

Tracing shows you calls. This release also made many of them cheaper.

A multi-arity function used to send every call through one generic entry point. It packed the arguments into an array, then picked the arity at runtime. Now each arity compiles to its own fixed method.

```phel
(defn greet
  ([] (greet "world"))
  ([name] (str "Hello, " name)))

(greet "Phel") ; => "Hello, Phel"
```

In a build, `(greet "Phel")` knows its argument count at compile time. It jumps straight to the one-argument method. Roughly 1.5-2x faster per call, with no change to your code.

For smaller builds there is a new opt-in. `withStripSymbolMeta()` drops symbol metadata from compiled output. On the Phel repo itself: 28% smaller artifacts and a 40% faster cold `require`. The cost: `phel doc` and `(meta ...)` return `nil` for built definitions.

```php
<?php

use Phel\Config\PhelConfig;

return (new PhelConfig())
    ->withStripSymbolMeta();
```

## Also in Phel 0.49

- **Behaviour change:** sorted collections treat `NAN` as equal to itself and sort it after every number, like Clojure's `compare`. `(count (sorted-set NAN NAN))` is now `1`, it was `2`.
- `partition` and `partition-all` accept Clojure's `step` and `pad` arities: `(partition 2 1 [1 2 3 4])` returns `([1 2] [2 3] [3 4])`. The `[n coll]` form is unchanged.
- The `pr`, `prn`, `pr-str` and `prn-str` family prints data you can read back, with strings quoted.
- The atom API is complete: `swap-vals!` and `reset-vals!` return `[old new]`, and `compare-and-set!` writes only on a match.
- Sets of maps work as small relations, like `clojure.set`: `select`, `project`, `rename`, `index`, plus `subseq` and `rsubseq` on sorted collections.
- More Clojure core: `mapv`, `filterv`, `every-pred`, `while`, `distinct?`, `bounded-count`, `map-invert` and `random-sample`.
- In the REPL, `(tap> x)` prints out of the box. Detach it with `(remove-tap print-tap)`.
- The REPL reports a stray closing bracket instead of waiting for input forever.

Wrap the function. Run it once. Read the tree.

Shipped in [Phel 0.49](/releases/0-49-arity-lane/). Upgrade notes: [0.49](/documentation/reference/upgrading/#0-49).
