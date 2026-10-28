+++
title = "Lazy Sequences"
weight = 14
description = "Work with lazy sequences: built-in lazy functions, infinite sequences with lazy-seq, forcing with doall, and the common laziness pitfalls."
aliases = ["/documentation/lazy-sequences"]

[extra]
difficulty = "advanced"
+++

A lazy sequence computes its values only when you read them. Use one for large or infinite sources, or when you read only part of the data.

## Built-in lazy functions { #built-in-lazy-functions }

You rarely write a lazy sequence by hand: `range`, `iterate`, `repeat`, `cycle`, `map`, `filter`, `take`, and `drop` already return one. Chain them freely. Only the values you read are computed, so infinite sources are safe as long as something limits them:

```phel
(->> (range)            ; 0, 1, 2, ... forever
     (map inc)
     (filter odd?)
     (take 5))          ; => (1 3 5 7 9)

(take 5 (iterate #(* 2 %) 1))           ; => (1 2 4 8 16)
(take 7 (cycle [:a :b :c]))             ; => (:a :b :c :a :b :c :a)
(take-while #(< % 10) (iterate #(* 2 %) 1)) ; => (1 2 4 8)
```

Lazy file readers (`line-seq`, `file-seq`, `csv-seq`) are on the [cheat sheet](/documentation/reference/cheat-sheet/#lazy-sequences).

## Force a sequence

A lazy sequence runs nothing until something reads it. Force it with `doall` when you need every value now, or `dorun` when you only want the side effects:

```phel
(def nums (map inc (range 5)))

(doall nums) ; => [1 2 3 4 5], fully computed
(dorun nums) ; => nil
```

For side effects over a collection, prefer `foreach` or `doseq`. They are eager and make the intent clear.

## Build your own with `lazy-seq`

`lazy-seq` wraps a body that returns a sequence or `nil`. The body runs once, on first read, and the result is cached. Put a recursive call inside `cons` to build an infinite sequence one element at a time:

```phel
(defn fib-seq
  ([] (fib-seq 0 1))
  ([a b] (lazy-seq (cons a (fib-seq b (+ a b))))))

(take 10 (fib-seq)) ; => (0 1 1 2 3 5 8 13 21 34)
```

`realized?` tells whether a lazy sequence has run its body yet. Use `cons`, not `lazy-cat`, for the recursive step: `lazy-cat` evaluates its arguments right away, so the recursive call never returns.

## Pitfalls

### Side effects run in chunks

A lazy sequence can compute more values than you read. Side effects inside `map` run for those extra values too, and at a time you do not control:

```phel
(def xs (take 5 (map (fn [x] (println x) x) (range 100))))
; prints 0 through 5 when defined: six side effects for five results
```

Keep side effects out of lazy pipelines. Use `foreach` or `doseq`.

### Holding the head

When a name holds the start of a large sequence, every realized value stays in memory until the name goes away:

<!-- phel-test: skip -->
```phel
;; Holds all million values: nums is still needed after first
(let [nums (range 1000000)]
  (println (first nums))
  (println (last nums)))

;; Each sequence can be released as it is consumed
(println (first (range 1000000)))
(println (last (range 1000000)))
```

To run `map`, `filter` and `take` in one pass with no intermediate sequences, use [Transducers](/documentation/language/transducers/).
