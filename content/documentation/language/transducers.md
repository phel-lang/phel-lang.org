+++
title = "Transducers"
weight = 12
description = "Build composable, allocation-free transformation pipelines that decouple the transformation from the consumer, and write your own custom transducers"
+++

Transducers are composable transformation pipelines that decouple *what* you do to a sequence of values (map, filter, take) from *how* you consume the result (build a vector, sum, write to a file). They turn one reducing function into another, fusing every step into a single pass with no intermediate collections.

A normal pipeline allocates at every step; transducers fuse the steps:

```phel
(ns example\transducers)

; Two intermediate lazy sequences
(filter even? (map inc [1 2 3 4 5]))   ; => (2 4 6)

; No intermediate collections
(sequence (comp (map inc) (filter even?)) [1 2 3 4 5])
; => [2 4 6]
```

The [Data Structures](/documentation/language/data-structures/#transducers) page introduces `into` and the basic transducer producers. This page goes deeper: composition order, early termination, stateful transducers, and writing your own.

## Three ways to consume a transducer

```phel
(ns example\consume)

; transduce - apply a transducer, then reduce
(transduce (map inc) + [1 2 3])   ; => 9   (2 + 3 + 4)
(transduce (filter even?) + 0 [1 2 3 4 5 6])   ; => 12  (0 + 2 + 4 + 6, explicit init)

; into - pour transformed elements into a collection
(into [] (map inc) [1 2 3])   ; => [2 3 4]
(into #{} (filter odd?) [1 2 3 4 5])   ; => #{1 3 5}

; sequence - a vector of transformed results; shorthand for (into [] xf coll)
(sequence (filter even?) [1 2 3 4 5 6])   ; => [2 4 6]
```

## Transducer-producing functions

Most sequence functions are dual-purpose: called **with** a collection they return a lazy sequence; called **without** one they return a transducer.

| Function | Transducer form | Description |
|---|---|---|
| `map` | `(map f)` | Apply `f` to each element |
| `filter` | `(filter pred)` | Keep elements where `(pred x)` is truthy |
| `remove` | `(remove pred)` | Keep elements where `(pred x)` is falsy |
| `take` | `(take n)` | Take first `n` elements, then stop |
| `drop` | `(drop n)` | Skip first `n` elements |
| `take-while` | `(take-while pred)` | Take while `(pred x)` is truthy, then stop |
| `drop-while` | `(drop-while pred)` | Skip while `(pred x)` is truthy |
| `take-nth` | `(take-nth n)` | Take every nth element |
| `keep` | `(keep f)` | Keep non-nil results of `(f x)` |
| `keep-indexed` | `(keep-indexed f)` | Keep non-nil results of `(f index x)` |
| `distinct` | `(distinct)` | Remove duplicates |
| `dedupe` | `(dedupe)` | Remove consecutive duplicates |
| `mapcat` | `(mapcat f)` | Map then concatenate (flatten one level) |
| `interpose` | `(interpose sep)` | Insert `sep` between elements |
| `cat` | `cat` | Concatenate nested collections (not dual-purpose; always a transducer) |

## Composing transducers

`comp` builds a pipeline. Transducers compose **left-to-right** (leftmost runs first), the opposite of normal function composition and matching the order of `->>`:

```phel
(ns example\compose)

(def xf (comp
          (filter even?)   ; 1. keep even numbers
          (map #(* % %))   ; 2. square them
          (take 3)))   ; 3. stop after 3 results

(sequence xf (range 1 20))   ; => [4 16 36]

; Equivalent lazy-sequence version (creates intermediates):
(->> (range 1 20)
     (filter even?)
     (map #(* % %))
     (take 3))   ; => (4 16 36)
```

## Early termination

A reducing function signals "stop" by wrapping its return value in `reduced`:

```phel
(ns example\reduced)

; Sum until the accumulator exceeds 10
(reduce
  (fn [acc x] (if (> acc 10) (reduced acc) (+ acc x)))
  0
  [1 2 3 4 5 6 7 8 9 10])   ; => 15
```

- `(reduced x)` wraps `x` to signal early termination
- `(reduced? x)` is true if `x` is a wrapped `Reduced` value
- `(unreduced x)` unwraps a `Reduced` value; returns `x` unchanged if not reduced

`take` and `take-while` use `reduced` internally, so the outer `reduce`/`transduce` stops rather than walking the rest:

```phel
(ns example\early-stop)

(transduce (take 2) conj [1 2 3 4 5])   ; => [1 2]  (does not touch the rest)
```

## Stateful transducers

Some transducers need mutable state across steps (counters, seen-sets). Phel provides volatile references:

- `(volatile! val)` creates a mutable reference initialized to `val`
- `@vol` (deref) reads the current value
- `(vreset! vol new-val)` sets a new value, returns `new-val`
- `(vswap! vol f & args)` applies `f` to current value + args, sets and returns the result

`distinct`, for example, keeps a volatile hash-set of elements it has already emitted; on each step it checks membership before passing the value downstream.

## Custom transducers

A transducer takes a reducing function `rf` and returns a new one handling three arities:

- **0** (init): return `(rf)`, delegate downstream init
- **1** (completion): return `(rf result)`, optionally flush state
- **2** (step): the transformation logic

Dispatch on arity with a variadic `[& args]` plus `case (count args)`, the shape Phel's own core transducers use internally. It works correctly when the returned fn closes over `rf` or other state. (A multi-arity `fn` with `([] ...) ([result] ...) ([result input] ...)` clauses reads cleaner but does not currently compile for transducers, so prefer the variadic form.)

```phel
(ns example\custom)

; A transducer that doubles every element
(defn map-double []
  (fn [rf]
    (fn [& args]
      (case (count args)
        0 (rf)
        1 (rf (first args))
        2 (rf (first args) (* 2 (second args)))))))

(sequence (map-double) [1 2 3])   ; => [2 4 6]
```

### Custom completion logic

Override the 1-arity branch to flush buffered state. This `batch` transducer groups elements into vectors of `n`, emitting any partial final group on completion:

```phel
(ns example\batch)

(defn batch [n]
  (fn [rf]
    (let [buf (volatile! [])]
      (fn [& args]
        (case (count args)
          0 (rf)
          1 (let [result (first args)
                  b @buf]
              ; flush remaining items on completion
              (if (empty? b)
                (rf result)
                (rf (rf result b))))
          2 (let [result (first args)
                  input (second args)]
              (let [b (vswap! buf conj input)]
                (if (= (count b) n)
                  (do (vreset! buf [])
                      (rf result b))
                  result))))))))

(sequence (batch 3) [1 2 3 4 5 6 7])   ; => [[1 2 3] [4 5 6] [7]]
```

### With early termination

Wrap the step result in `reduced` to stop the pipeline. This `take-until` keeps elements until `pred` first returns true, inclusive:

```phel
(ns example\take-until)

(defn take-until [pred]
  (fn [rf]
    (fn [& args]
      (case (count args)
        0 (rf)
        1 (rf (first args))
        2 (let [result (first args)
                input (second args)]
            (if (pred input)
              (reduced (rf result input))
              (rf result input)))))))

(sequence (take-until #(> % 3)) [1 2 3 4 5])   ; => [1 2 3 4]
```

## Transducers vs lazy sequences

Every dual-purpose function has two modes:

```phel
(ns example\two-modes)

; With collection: returns a lazy sequence
(map inc [1 2 3])   ; => (2 3 4)
(filter even? [1 2 3 4])   ; => (2 4)

; Without collection: returns a transducer (a function)
(fn? (map inc))   ; => true
(fn? (filter even?))   ; => true
```

**When to use which:**

- **Lazy sequences** for simple linear pipelines; compose with `->>`.
- **Transducers** to avoid intermediates in multi-step pipelines, to reuse one transformation across multiple sources or destinations, or to reduce into something that isn't a sequence (sums, maps, side effects).

```phel
(ns example\reuse)

; Define once, reuse with different consumers
(def xf (comp (filter even?) (map inc)))

(sequence xf [1 2 3 4 5 6])   ; => [3 5 7]
(transduce xf + [1 2 3 4 5 6])   ; => 15
(into #{} xf [1 2 3 4 5 6])   ; => #{3 5 7}
```

## See also

- [Data structures](/documentation/language/data-structures/#transducers) - `into`, `reduce`, and the basic transducer producers
- [Control flow](/documentation/language/control-flow/) - iterate and build collections with `for` and `loop`
- [Cookbook -- Data processing with transducers](/documentation/guides/cookbook/#data-processing-with-transducers) - a worked real-world pipeline
- [Cheat sheet -- Transducers](/documentation/reference/cheat-sheet/#transducers) - keep it open while coding
