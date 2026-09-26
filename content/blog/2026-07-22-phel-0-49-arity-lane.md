+++
title = "Phel 0.49: Arity Lane"
aliases = [ "/blog/phel-0-49-arity-lane" ]
description = "Multi-arity calls 1.5-2x faster, NaN that behaves in sorted collections, new core fns including the pr/prn family, swap-vals! and clojure.set-style relations, and a phel.trace namespace. What changed in 0.49 and how to upgrade."
date = 2026-07-22
+++

Phel 0.49, *Arity Lane*, gives multi-arity functions their own fast path and fills in a long list of Clojure core functions. No breaking changes, but two behaviour changes are worth a look before you upgrade.

## Upgrade

```bash
composer require phel-lang/phel-lang:^0.49
./vendor/bin/phel cache:clear        # or: rm -rf .phel/cache
```

Always clear the cache after upgrading: compiled PHP from an earlier install can reference renamed internals and fail to load otherwise. The [0.49 upgrade notes](/documentation/upgrading/#0-49) list every behaviour change.

## Two behaviour changes

PHP says `NAN <=> NAN` is `1`. So a sorted set never found the `NAN` it already held, and every insert added a new one. The default comparator now matches Clojure's `compare`: `NAN` equals itself and sorts after every number.

```phel
(count (sorted-set NAN NAN)) ; => 1, was 2
```

If a sorted collection of yours counted duplicate `NAN` values, the count drops.

`partition` and `partition-all` also accept Clojure's `step` arities. The `[n coll]` form is unchanged:

```phel
(partition 2 1 [1 2 3 4])          ; => ([1 2] [2 3] [3 4])
(partition 3 3 [:pad] [1 2 3 4])   ; => ([1 2 3] [4 :pad])
(partition-all 3 2 [1 2 3 4 5])    ; => ([1 2 3] [3 4 5] [5])
```

## The arity lane

A multi-arity function used to route every call through one variadic `__invoke`. It packed the arguments into an array, then picked the arity at runtime. Now each arity compiles to its own fixed method.

```phel
(defn greet
  ([] (greet "world"))
  ([name] (str "Hello, " name)))

(greet "Phel") ; => "Hello, Phel"
```

Outside the REPL, a call like `(greet "Phel")` knows its argument count at compile time. It jumps straight to the one-argument method. Roughly 1.5-2x faster per call, with no change to your code.

For smaller builds, there is a new opt-in. `withStripSymbolMeta()` drops symbol metadata from compiled output: 28% smaller artifacts and a 40% faster cold `require` on the Phel repo itself. The cost: `phel doc` and `(meta ...)` return `nil` for built definitions.

```php
<?php

use Phel\Config\PhelConfig;

return (new PhelConfig())
    ->withStripSymbolMeta();
```

## More of Clojure core

The pr family prints data you can read back, with strings quoted:

```phel
(prn "hi" \A :k)            ; prints: "hi" "A" :k
(pr-str {:name "Ada"})      ; => "{:name \"Ada\"}"
```

The atom API is complete. `swap-vals!` and `reset-vals!` return both the old and the new value, and `compare-and-set!` only writes when the current value matches:

```phel
(def counter (atom 0))
(swap-vals! counter inc)        ; => [0 1]
(reset-vals! counter 10)        ; => [1 10]
(compare-and-set! counter 5 11) ; => false, counter is still 10
```

Sets of maps now work as small relations, the way `clojure.set` treats them:

```phel
(def people #{{:name "Ada" :lang "php"}
              {:name "Rich" :lang "clj"}})

(select #(= "php" (:lang %)) people) ; => #{{:name "Ada", :lang "php"}}
(project people [:name])             ; => #{{:name "Ada"} {:name "Rich"}}
(subseq (sorted-set 1 5 10 15) > 5)  ; => (10 15)
```

Also new: `mapv`, `filterv`, `every-pred`, `while`, `distinct?`, `bounded-count`, `map-invert`, `random-sample`, `rename`, `index` and `rsubseq`.

## Trace calls, not only values

The new `phel.trace` namespace follows `clojure.tools.trace`. `deftrace` defines a function that logs every call and its result to stderr, indented by depth:

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

In the REPL, `(tap> x)` now prints out of the box. Detach it with `(remove-tap print-tap)`.

For the full list, see the [0.49 release notes](/releases/0-49-arity-lane/). Upgrade, clear the cache, and let your multi-arity functions take the fast lane.
