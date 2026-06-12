+++
title = "Lazy Sequences"
weight = 11
description = "Defer computation with lazy-seq and lazy-cat, build infinite sequences, and avoid the common laziness pitfalls."
aliases = ["/documentation/lazy-sequences"]
+++

Lazy sequences defer computation until values are actually needed. This lets you describe infinite or expensive collections, then realize only the part you consume.

Phel has two constructs for building them by hand: `lazy-seq` wraps an expression in a thunk, and `lazy-cat` concatenates collections lazily. Most of the time you will reach for the built-in lazy functions (`range`, `map`, `filter`, ...) listed at the end of this page.

For a quick overview of the lazy helpers see the [cheat sheet](/documentation/reference/cheat-sheet/#lazy-sequences); this page explains how laziness actually works and how to write your own lazy sequences.

## lazy-seq

`lazy-seq` takes a body that returns a sequence or `nil`. It wraps the body in a zero-arg thunk and returns a lazy sequence that, on first access (`first`, `rest`, `take`, ...), evaluates the body once and caches the result.

```phel
(def my-lazy-seq
  (lazy-seq
    (println "Computing...")
    [1 2 3 4 5]))

(first my-lazy-seq)  ; prints "Computing..." then returns 1
(first my-lazy-seq)  ; returns 1 (cached, no printing)
```

### Infinite sequences

Combine `lazy-seq` with recursion, using `cons` to defer the recursive call so the sequence builds one element at a time:

```phel
(defn ints-from [n]
  (lazy-seq
    (cons n (ints-from (inc n)))))

(println (take 5 (ints-from 0)))   ; [0 1 2 3 4]
(println (take 3 (ints-from 10)))  ; [10 11 12]
```

`take` realizes only the elements it returns, so the infinite recursion never runs away.

## lazy-cat

`lazy-cat` concatenates collections. It expands to `concat` and evaluates its arguments eagerly, which is fine for finite or already-realized sequences:

```phel
(println (lazy-cat [1 2] [3 4] [5 6]))                 ; (1 2 3 4 5 6)
(println (lazy-cat (range 3) (range 3 6)))             ; (0 1 2 3 4 5)
(println (lazy-cat (take 3 (range 100))
                   (take 3 (range 10 20))))            ; (0 1 2 10 11 12)
```

Because it evaluates its arguments first, `lazy-cat` must **not** be used to build a recursive infinite sequence. Use `cons` for that instead:

```phel skip
;; ✅ cons defers the recursive call
(defn ints [n]
  (lazy-seq (cons n (ints (inc n)))))

(take 5 (ints 0))  ; => [0 1 2 3 4]

;; ❌ lazy-cat evaluates all args first -> the recursive call never returns
(defn ints [n]
  (lazy-seq (lazy-cat [n] (ints (inc n)))))  ; stack overflow
```

## Common patterns

A Fibonacci sequence and a prime sieve, both infinite and lazily realized:

```phel
;; Fibonacci
(defn fib-seq
  ([] (fib-seq 0 1))
  ([a b] (lazy-seq (cons a (fib-seq b (+ a b))))))

(println (take 10 (fib-seq)))
; [0 1 1 2 3 5 8 13 21 34]

;; Sieve of primes: filtering an infinite sequence
(defn ints-from [n]
  (lazy-seq (cons n (ints-from (inc n)))))

(defn primes []
  (let [sieve (fn sieve [s]
                (lazy-seq
                  (cons (first s)
                        (sieve (filter (fn [x] (not= 0 (mod x (first s))))
                                       (rest s))))))]
    (sieve (ints-from 2))))

(println (take 10 (primes)))
; [2 3 5 7 11 13 17 19 23 29]
```

Because the pipeline is lazy, you can compose transformations over a data source and only touch the records you actually consume:

```phel skip
(:require phel.string :as str)

(defn process-records [records]
  (->> records
       (filter (fn [x] (not (empty? x))))
       (map (fn [x] (str/trim x)))
       (map parse-record)))

(take 100 (process-records lazy-data-source))
```

## Built-in lazy functions

These return lazy sequences, so you rarely need to write `lazy-seq` yourself:

- `range` — lazy sequence of numbers
- `iterate` — infinite sequence by repeatedly applying a function
- `repeat` — infinite sequence of a repeated value
- `cycle` — infinite sequence by cycling through a collection
- `map` — lazy transformation
- `filter` — lazy filtering
- `take` — first `n` elements (realizes them)
- `drop` — skips first `n` elements (stays lazy)

```phel
(println (take 10 (iterate (fn [x] (* 2 x)) 1)))
; [1 2 4 8 16 32 64 128 256 512]

(println (take 7 (cycle [:a :b :c])))
; [:a :b :c :a :b :c :a]

(println (->> (range 100)
              (filter even?)
              (map (fn [x] (* x x)))
              (take 5)))
; [0 4 16 36 64]
```

## Performance

**Use lazy sequences for:** large or infinite collections, partial consumption, composing transformations, and memory efficiency.

**Avoid them when:** every element is accessed immediately, you iterate the same sequence multiple times, or holding the head would leak memory.

### Chunking

A lazy sequence may realize more elements than you consume, so side effects can run for elements you never read:

```phel skip
(take 5 (map (fn [x] (do (println x) x)) (range 100)))
;; may print more than 5 numbers due to chunking
```

### Realizing

Force a lazy sequence when you need all of it:

```phel
(def nums (map inc (range 5)))

(println (doall nums))  ; realizes the whole sequence and returns it
(dorun nums)            ; realizes for side effects only, returns nil
```

## Gotchas

**1. Holding the head** keeps the whole sequence in memory. Don't bind a large lazy sequence to a name you reuse:

```phel skip
;; ❌ binds `nums`, so first + last hold the entire sequence in memory
(let [nums (range 1000000)]
  (println (first nums))
  (println (last nums)))

;; ✅ don't bind the head
(println (first (range 1000000)))
(println (last (range 1000000)))
```

**2. Lazy sequences in tests** — realize before asserting, otherwise you compare against an unrealized thunk:

```phel skip
(is (= expected (doall lazy-result)))  ; force realization
```

**3. Side effects run on realization, not creation.** Building a lazy sequence does nothing until you consume it:

```phel
(def log-and-inc
  (map (fn [x] (do (println "Processing" x) (inc x)))
       (range 5)))
; nothing printed yet

(println (first log-and-inc))  ; now prints "Processing 0" then 1
```

## Debugging

```phel
(def my-lazy-seq (lazy-seq [1 2 3]))

(println (realized? my-lazy-seq))  ; false until consumed

;; Inspect a potentially-infinite sequence without fully realizing it
(println (take 10 (iterate inc 0)))
(println (take-while (fn [x] (< x 100)) (iterate inc 0)))
```

## Further reading

- [Cheat sheet — lazy sequences](/documentation/reference/cheat-sheet/#lazy-sequences) for a one-screen reference, including lazy file I/O (`line-seq`, `file-seq`, `csv-seq`).
- [Data structures](/documentation/language/data-structures/) for the sequence functions that consume and transform these collections.
- [Cookbook](/documentation/guides/cookbook/) for lazy pipelines applied to real tasks.
- [Clojure: lazy sequences](https://clojure.org/reference/sequences) — the model Phel follows.
