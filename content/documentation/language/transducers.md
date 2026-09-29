+++
title = "Transducers"
weight = 15
description = "Build reusable transformation pipelines that run in one pass, consume them with into, transduce and sequence, and write your own transducers."

[extra]
difficulty = "advanced"
+++

A transducer is a transformation step (map, filter, take) that does not know where its input comes from or where its output goes. After this page you can compose transducers into one-pass pipelines, reuse one pipeline with different consumers, and write your own.

## Why use them

A lazy pipeline builds an intermediate sequence at every step. A transducer pipeline fuses the steps into one pass:

```phel
(filter even? (map inc [1 2 3 4 5]))               ; => (2 4 6), two intermediate sequences
(sequence (comp (map inc) (filter even?)) [1 2 3 4 5]) ; => [2 4 6], one pass
```

Define the pipeline once and feed it to any consumer:

```phel
(def xf (comp (filter even?) (map inc)))

(into [] xf [1 2 3 4 5 6])     ; => [3 5 7]
(into #{} xf [1 2 3 4 5 6])    ; => #{3 5 7}
(transduce xf + [1 2 3 4 5 6]) ; => 15
```

Use lazy sequences and `->>` for a simple one-off chain. Use transducers for multi-step pipelines, for a transformation you reuse across sources or destinations, or when the result is not a sequence (a sum, a map, a side effect). For `into` and the basics, see [Data structures](/documentation/language/data-structures/#transducers).

## Consume a transducer

| Function | Does | Example | Result |
|---|---|---|---|
| `into` | pours the results into a collection | `(into #{} (filter odd?) [1 2 3 4 5])` | `#{1 3 5}` |
| `transduce` | transforms, then reduces | `(transduce (map inc) + [1 2 3])` | `9` |
| `sequence` | returns the results; same as `(into [] xf coll)` | `(sequence (filter even?) [1 2 3 4])` | `[2 4]` |

`transduce` takes an optional start value before the collection: `(transduce (filter even?) + 100 [1 2 3 4])` returns `106`.

A reducing function has three arities: 0 for the start value, 1 to finish, 2 for each step. `completing` turns a plain 2-arity function into a full one, with `identity` as the finish step:

```phel
(transduce (map inc) (completing conj) [1 2 3]) ; => [2 3 4]
```

## Transducer-producing functions {#transducer-producing-functions}

Most sequence functions have two forms. With a collection they return a lazy sequence. Without one they return a transducer: `(map f)`, `(filter pred)`.

The functions that work this way: `map`, `filter`, `remove`, `keep`, `keep-indexed`, `mapcat`, `take`, `drop`, `take-while`, `drop-while`, `take-nth`, `distinct`, `dedupe` and `interpose`. `cat` is always a transducer: it flattens nested collections one level. Each is described in the [API reference](/documentation/reference/api/).

## Compose with `comp`

`comp` builds a pipeline. The leftmost transducer runs first, in the same order as `->>`:

```phel
(def xf (comp
          (filter even?)  ; 1. keep even numbers
          (map #(* % %))  ; 2. square them
          (take 3)))      ; 3. stop after 3 results

(sequence xf (range 1 20)) ; => [4 16 36]
```

This is the opposite of `comp` on plain functions, where the rightmost runs first.

## Stop early

A reducing function stops the reduction by wrapping its result in `reduced`:

```phel
;; Sum until the total goes over 10
(reduce
  (fn [acc x] (if (> acc 10) (reduced acc) (+ acc x)))
  0
  [1 2 3 4 5 6 7 8 9 10]) ; => 15
```

`reduced?` checks for a wrapped value, and `unreduced` unwraps it (a plain value comes back unchanged). `take` and `take-while` use `reduced`, so `(transduce (take 2) conj [1 2 3 4 5])` returns `[1 2]` without reading the rest.

## Write your own

A transducer takes a reducing function `rf` and returns a new one with three arities:

- **0** (init): return `(rf)`.
- **1** (completion): return `(rf result)`, after flushing any buffered state.
- **2** (step): the transformation.

```phel
(defn map-double []
  (fn [rf]
    (fn
      ([] (rf))
      ([result] (rf result))
      ([result input] (rf result (* 2 input))))))

(sequence (map-double) [1 2 3]) ; => [2 4 6]
```

### Keep state and flush it

A stateful transducer keeps state in a volatile, created inside `(fn [rf] ...)` so each use starts fresh. `volatile!` creates it, `@` reads it, `vreset!` sets it and `vswap!` updates it with a function. This `batch` groups items into vectors of `n` and flushes the last partial group on completion:

```phel
(defn batch [n]
  (fn [rf]
    (let [buf (volatile! [])]
      (fn
        ([] (rf))
        ([result]
         (let [b @buf]
           (if (empty? b)
             (rf result)
             (rf (rf result b)))))
        ([result input]
         (let [b (vswap! buf conj input)]
           (if (= (count b) n)
             (do (vreset! buf [])
                 (rf result b))
             result)))))))

(sequence (batch 3) [1 2 3 4 5 6 7]) ; => [[1 2 3] [4 5 6] [7]]
```

### Stop from inside

Wrap the step result in `reduced` to end the pipeline. This `take-until` keeps items up to and including the first one that matches `pred`:

```phel
(defn take-until [pred]
  (fn [rf]
    (fn
      ([] (rf))
      ([result] (rf result))
      ([result input]
       (if (pred input)
         (reduced (rf result input))
         (rf result input))))))

(sequence (take-until #(> % 3)) [1 2 3 4 5]) ; => [1 2 3 4]
```
