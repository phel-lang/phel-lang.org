+++
title = "Prove a Speedup with `phel bench --ab`"
aliases = [ "/blog/phel-0-53-floor-raised" ]
description = "phel bench --ab runs your benchmarks against a git ref and your working tree in turns, in the same sitting, so a warm laptop cannot fake a speedup. Short type tags like ^map make a fast lookup one word away."
date = 2026-09-24
+++

You change a function and you think it got faster. So you benchmark it. Before: `1.069ms`. You run the same benchmark again, same code, nothing changed: `772μs`.

That is a 28% "speedup" from pressing Enter twice.

A laptop warms up. Caches fill, the CPU clock moves, other apps wake and sleep. Numbers taken ten minutes apart are not a fair fight. Any before-and-after you did that way was part signal, part weather.

`phel bench --ab` fixes the method. It runs the old code and the new code in turns, in the same sitting, and only calls it a win when every pair agrees.

## Write the benchmark with `defbench`

A benchmark is a Phel function with a body to time. Here is one that builds a map of orders keyed by id:

```phel
(ns shop.orders-bench
  (:require phel.bench :refer [defbench]))

(defn index-by-id [orders]
  (reduce (fn [acc order] (assoc acc (:id order) order))
          {}
          orders))

(def orders
  (vec (for [i :range [0 500]] {:id i :total (* i 3)})))

(defbench bench-index-by-id
  {:revs 50}
  (index-by-id orders))

(get (index-by-id orders) 7)
; => {:id 7, :total 21}
```

`defbench` defines a normal zero-argument function, so you can still call it by hand. `:revs` is how many calls make one measured iteration. Pick it so one iteration takes a few milliseconds.

Put the file under your test directory and run it:

```bash
phel bench
```

```text
benchmark                           revs its    mean rstdev vs-baseline
shop.orders-bench/bench-index-by-id   50   5 1.069ms ±1.06%         new
```

`rstdev` is how much the iterations disagree. Above a few percent, do not trust a comparison.

## A stored baseline goes stale

The classic flow is to store numbers on `main`, switch branches and compare:

```bash
phel bench --store=.phel/bench-baseline.json
# change index-by-id to use a transient
phel bench --ref=.phel/bench-baseline.json --tolerance=10
```

```text
benchmark                           revs its      mean rstdev vs-baseline
shop.orders-bench/bench-index-by-id   50   5 725.946μs ±0.69%      -5.99%
```

A 6% gain. Is it real? The two runs happened minutes apart, on a machine that was warming up the whole time. The baseline in this case was `772μs`. The very first run of the same code was `1.069ms`. Against that one, the "gain" would be 32%.

> A benchmark you cannot repeat is a guess with decimals.

`--ref` is still the right tool when the other side cannot be rebuilt, or when CI stores the baseline itself. For "is my change faster than `main`?", there is a better question to ask the machine.

## `--ab` runs both sides in the same sitting

Give `phel bench` a git ref instead of a file:

```bash
phel bench --ab=main --pairs=5 --filter=index
```

Side A is the ref, `main` here. Side B is your working tree, uncommitted changes included. For each pair, Phel runs A, then B, each as its own `phel bench` process with the same flags. Five pairs means ten runs, interleaved. If the machine warms up, it warms up for both sides.

Side A runs from a temporary `git worktree` in the system temp dir. Phel removes it when the run ends, fails or is interrupted. Your working tree is never checked out or stashed. Run the command from the repository root.

The output has one row per benchmark. The [benchmarking guide](https://github.com/phel-lang/phel-lang/blob/main/docs/benchmarking.md) shows the shape:

```text
benchmark                          a-mean   b-mean  delta signs
my-app.bench/bench-step           4.912μs  4.103μs -16.47%   5/5
my-app.bench/bench-render         1.207ms  1.215ms  +0.62%   3/5 noise
```

`delta` is the mean of the per-pair changes, `(B - A) / A`. `signs` counts the pairs that moved in that direction. `5/5` means every pair saw B faster. `3/5` means the pairs disagree, so the row says `noise`. A consistent sign is the signal. A mean that one lucky pair set is not.

## Turn it into a gate

Add `--tolerance` and the command gets an exit code:

```bash
phel bench --ab=main --pairs=5 --tolerance=10
```

It fails only when a benchmark is slower by more than 10% in every pair. One noisy pair cannot break the build, and a real regression cannot hide behind one fast pair.

Two details make this work on real projects. A benchmark file that does not exist on `main`, like the one you added with your change, runs from the working tree on both sides. And side A needs its own `vendor/`: when `composer.lock` is the same at both refs, Phel links the installed packages instead of installing them again.

## Short type tags make the fast path one word

Once you can measure, you want easy wins. Tagging a parameter as a Phel map used to mean the full PHP interface name. Now it is one word: `^map`, `^vector`, `^set`, `^list`, `^keyword`, `^symbol` or `^atom`.

```phel
(defn order-total [^map order]
  (* (:qty order) (:price order)))

(order-total {:qty 3 :price 10})
; => 30
```

The tag does two jobs. `(:qty order)` compiles to a direct map lookup instead of a generic call. And a wrong argument fails at the call, with a code:

<!-- phel-test: skip -->
```phel
(order-total [3 10])
; [PHEL402] ... Argument #1 ($order) must be of type ...PersistentMapInterface, ...PersistentVector given
```

Add `?` to accept `nil` as well:

```phel
(defn first-sku [^?vector skus]
  (first skus))

(first-sku nil)
; => nil
```

Tag the hot function. Then prove it with `--ab`.

## Also in Phel 0.53

- **Breaking:** PHP 8.5 is the minimum. On 8.4, Composer refuses the install. Your Phel code does not change.
- **Breaking:** an octal escape above `\377` is a compile error. `"\400"` used to compile to a NUL byte without a word.
- Dropping the result of a collection update now warns. `(assoc user :role :admin)` followed by `user` prints a PHP warning that the receiver is unchanged, when the call compiles to a direct method, as on a tagged local.
- Literal `assoc` calls with several pairs are 3.4x to 3.7x faster, `get-in` 2.9x to 4.5x, `assoc-in` and `update-in` 1.9x to 2.8x.
- `into` with a transducer and `reduce` build fewer closures: `(reduce + 0 v)` over 32 ints went from 9.6μs to 6.8μs.
- `phel test --parallel` workers run with the OPcache JIT off, which stops SIGBUS crashes on arm64 macOS runners.

Stop trusting the second run. Measure in pairs.

Shipped in [Phel 0.53](/releases/0-53-floor-raised/). Upgrade notes: [0.53](/documentation/reference/upgrading/#0-53).
