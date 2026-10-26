+++
title = "Recursion and Loops"
weight = 6
description = "Repeat work without mutable variables: recursion, loop and recur, for comprehensions, side-effect loops, and lazy sequences."

[extra]
stage = "Functional core"
goals = [
  "Write recursive functions with a clear base case",
  "Use `loop` and `recur` with an accumulator, and know why `recur` exists",
  "Build sequences with `for` and its `:in`, `:range`, `:when`, and `:let` clauses",
  "Run side effects with `foreach` and `dotimes`",
  "Work with infinite lazy sequences from `iterate`, `repeat`, and `cycle`",
]
read_first = [
  ["Control Flow", "/documentation/language/control-flow/"],
  ["Functions and Recursion", "/documentation/language/functions-and-recursion/"],
  ["Lazy Sequences", "/documentation/language/lazy-sequences/"],
]
recap = [
  "You can solve a problem by calling a function on a smaller piece of it",
  "You can write a loop with `loop` and `recur` that runs a million times in constant memory",
  "You can build a new collection with `for` and filter it with `:when`",
  "You can take what you need from an infinite sequence",
]
+++

`map`, `filter`, and `reduce` cover most loops. Sometimes you need more control: stop at a condition, carry several values, or walk a structure step by step. This module shows the tools Phel gives you for that. None of them change a variable in place, and all of them return a value.

## Recursion

A recursive function calls itself on a smaller problem. It needs a base case: an input small enough to answer directly. Without one, it never stops.

```phel
(defn countdown [n]
  (if (= n 0)
    [:liftoff]
    (conj (countdown (dec n)) n)))

(countdown 3) ; => [:liftoff 1 2 3]
```

{% <question difficulty="easy" kind="predict"> %}
What does `(fact 5)` return?
<!-- phel-test: skip -->
```phel
(defn fact [n]
  (if (<= n 1)
    1
    (* n (fact (dec n)))))

(fact 5)
```
{% </question> %}
{% <solution> %}
```phel
(defn fact [n]
  (if (<= n 1)
    1
    (* n (fact (dec n)))))

(fact 5) ; => 120
```
`(fact 5)` is `(* 5 (fact 4))`, which is `(* 5 (* 4 (fact 3)))`, and so on down to the base case `(fact 1)`, which returns 1.
{% </solution> %}

{% <question difficulty="medium" kind="write"> %}
Write `my-count` without using `count`. Use recursion with `empty?`, `rest`, and `inc`.
<!-- phel-test: skip -->
```phel
(my-count [:a :b :c]) ; => 3
(my-count [])         ; => 0
```
{% </question> %}
{% <hint> %}
The count of an empty collection is 0. The count of any other collection is one more than the count of its `rest`.
{% </hint> %}
{% <solution> %}
```phel
(defn my-count [xs]
  (if (empty? xs)
    0
    (inc (my-count (rest xs)))))

(my-count [:a :b :c]) ; => 3
(my-count [])         ; => 0
```
Every recursive function has the same shape: check the base case first, then call yourself on a smaller input.
{% </solution> %}

{% <question difficulty="medium" kind="fix"> %}
This function should add all numbers in a vector, but it never finishes. Fix it.
<!-- phel-test: skip -->
```phel
(defn sum-all [xs]
  (+ (first xs) (sum-all (rest xs))))

(sum-all [1 2 3]) ; expected => 6
```
{% </question> %}
{% <hint> %}
What should happen when `xs` is empty?
{% </hint> %}
{% <solution> %}
```phel
(defn sum-all [xs]
  (if (empty? xs)
    0
    (+ (first xs) (sum-all (rest xs)))))

(sum-all [1 2 3]) ; => 6
```
There was no base case. `(rest [])` is `[]` again, so the function kept calling itself with an empty vector until the program ran out of memory.
{% </solution> %}

## loop and recur

Each normal function call waits for the next one to finish, and every waiting call takes memory. PHP has no tail call optimization, so deep recursion grows until memory runs out. `recur` fixes this: it jumps back to the top of the function or `loop` with new values, and the old call does not wait.

Compare two ways to add the numbers from 1 to n:

```phel
(defn sum-to-slow [n]
  (if (= n 0)
    0
    (+ n (sum-to-slow (dec n)))))

(defn sum-to [n]
  (loop [i n
         total 0]
    (if (= i 0)
      total
      (recur (dec i) (+ total i)))))

(sum-to-slow 1000) ; => 500500
(sum-to 1000)      ; => 500500
```

Both give the same answer. With `n` set to one million, `sum-to-slow` keeps a million calls waiting: in our test the process needed about 430 MB of memory, more than the 128 MB limit many PHP servers use. `sum-to` stayed at about 33 MB, the same as a program that does nothing. `loop` sets the starting values, and `recur` must be the last thing the code does (the tail position), so there is nothing left to wait for.

{% <question difficulty="easy" kind="fill"> %}
Fill in the blanks so the loop returns the numbers 1 to 10 in a vector.
<!-- phel-test: skip -->
```phel
(loop [v []
       i 1]
  (if (> i 10)
    ___
    (recur (conj v i) ___)))
; => [1 2 3 4 5 6 7 8 9 10]
```
{% </question> %}
{% <solution> %}
```phel
(loop [v []
       i 1]
  (if (> i 10)
    v
    (recur (conj v i) (inc i))))
; => [1 2 3 4 5 6 7 8 9 10]
```
`recur` takes one new value for each `loop` binding, in the same order. When the loop ends, you return the accumulated vector.
{% </solution> %}

{% <question difficulty="medium" kind="write"> %}
Use `loop` and `recur` to add the numbers from 1 to 100.
<!-- phel-test: skip -->
```phel
; => 5050
```
{% </question> %}
{% <hint> %}
Carry two values: the current number and the running total.
{% </hint> %}
{% <solution> %}
```phel
(loop [i 1
       total 0]
  (if (> i 100)
    total
    (recur (inc i) (+ total i))))
; => 5050
```
This is the accumulator pattern: carry the result so far in a loop binding and return it at the end. `(reduce + (range 1 101))` gives the same answer; use `loop` when the steps do not fit a single `reduce`.
{% </solution> %}

{% <question difficulty="medium" kind="refactor"> %}
This recursive `my-count` works on small inputs but needs more and more memory on large ones. Rewrite it with `loop` and `recur` so it counts a million items safely.
<!-- phel-test: skip -->
```phel
(defn my-count [xs]
  (if (empty? xs)
    0
    (inc (my-count (rest xs)))))

(my-count (range 1000000)) ; => 1000000
```
{% </question> %}
{% <hint> %}
The call is not in tail position because `inc` runs after it returns. Move the counting into an accumulator.
{% </hint> %}
{% <solution> %}
```phel
(defn my-count [xs]
  (loop [xs xs
         n 0]
    (if (empty? xs)
      n
      (recur (rest xs) (inc n)))))

(my-count (range 1000000)) ; => 1000000
```
The accumulator `n` holds the count so far, so there is nothing left to do after `recur`. Moving the pending work into an accumulator is how you turn most recursions into loops.
{% </solution> %}

{% <question difficulty="medium" kind="fix"> %}
This does not compile: Phel says `Can't call 'recur here`. Fix it so `(fact 5)` returns 120.
<!-- phel-test: skip -->
```phel
(defn fact [n]
  (if (<= n 1)
    1
    (* n (recur (dec n)))))
```
{% </question> %}
{% <hint> %}
`recur` must be the last call. Here `*` still needs its result. Add an accumulator with `loop`.
{% </hint> %}
{% <solution> %}
```phel
(defn fact [n]
  (loop [n n
         acc 1]
    (if (<= n 1)
      acc
      (recur (dec n) (* acc n)))))

(fact 5) ; => 120
```
Phel checks the tail position when it compiles. Multiply into `acc` first, then `recur`: now nothing waits for the result.
{% </solution> %}

{% <question difficulty="hard" kind="write"> %}
The Collatz rule: if `n` is even, divide it by 2; if odd, compute `3n + 1`. Repeat until you reach 1. Write `collatz-steps` that returns how many steps that takes.
<!-- phel-test: skip -->
```phel
(collatz-steps 1)  ; => 0
(collatz-steps 6)  ; => 8
(collatz-steps 27) ; => 111
```
{% </question> %}
{% <hint> %}
Carry `n` and a step counter in a `loop`. `cond` has three cases: done, even, odd.
{% </hint> %}
{% <solution> %}
```phel
(defn collatz-steps [n]
  (loop [n n
         steps 0]
    (cond
      (= n 1)   steps
      (even? n) (recur (/ n 2) (inc steps))
      :else     (recur (+ (* 3 n) 1) (inc steps)))))

(collatz-steps 1)  ; => 0
(collatz-steps 6)  ; => 8
(collatz-steps 27) ; => 111
```
You cannot know in advance how many steps you need, so `map` or `range` do not fit. `recur` works in any branch of `cond`, as long as it is the last call in that branch.
{% </solution> %}

## for comprehensions

`for` builds a vector from one or more bindings. `:in` walks a collection and `:range` walks numbers. `:when` skips items and `:let` names an intermediate value.

```phel
(for [x :in [1 2 3]] (* x 10)) ; => [10 20 30]
```

{% <question difficulty="easy" kind="predict"> %}
What does each `for` return?
<!-- phel-test: skip -->
```phel
(for [x :in [1 2 3 4 5 6] :when (even? x)] (* x x))
(for [n :range [1 20] :when (= 0 (rem n 5))] n)
```
{% </question> %}
{% <solution> %}
```phel
(for [x :in [1 2 3 4 5 6] :when (even? x)] (* x x)) ; => [4 16 36]
(for [n :range [1 20] :when (= 0 (rem n 5))] n)     ; => [5 10 15]
```
`:when` drops the items that fail the test. `:range [1 20]` works like `(range 1 20)`, so 20 is not included.
{% </solution> %}

{% <question difficulty="medium" kind="write"> %}
Build every `[suit rank]` pair for this small deck with `for`.
<!-- phel-test: skip -->
```phel
(def suits [:hearts :diamonds :clubs :spades])
(def ranks [:ace :king :queen])
; => [[:hearts :ace] [:hearts :king] [:hearts :queen] [:diamonds :ace] ... ]
; 12 pairs in total
```
{% </question> %}
{% <hint> %}
Give `for` two bindings, one after the other.
{% </hint> %}
{% <solution> %}
```phel
(def suits [:hearts :diamonds :clubs :spades])
(def ranks [:ace :king :queen])

(def deck
  (for [s :in suits
        r :in ranks]
    [s r]))

(take 4 deck) ; => ([:hearts :ace] [:hearts :king] [:hearts :queen] [:diamonds :ace])
(count deck)  ; => 12
```
Two bindings act like nested loops. The last binding changes fastest. In PHP you would write two nested `foreach` loops and push into an array.
{% </solution> %}

{% <question difficulty="medium" kind="fill"> %}
Fill in the blanks: square the numbers 1 to 5 and keep only the squares above 5.
<!-- phel-test: skip -->
```phel
(for [x :range [1 6]
      ___ [sq (* x x)]
      ___ (> sq 5)]
  sq)
; => [9 16 25]
```
{% </question> %}
{% <hint> %}
One clause names a value, the other one filters.
{% </hint> %}
{% <solution> %}
```phel
(for [x :range [1 6]
      :let [sq (* x x)]
      :when (> sq 5)]
  sq)
; => [9 16 25]
```
`:let` computes `sq` once, so both the test and the result can use it.
{% </solution> %}

## Side effects: foreach and dotimes

`for` builds a value. When you only want to do something, like print, use `foreach` over a collection or `dotimes` for a number of times. Both return `nil`.

```phel
(foreach [name ["Ada" "Bob"]]
  (println "Hi" name))
```

{% <question difficulty="easy" kind="predict"> %}
What does this print, and what does the whole expression return?
<!-- phel-test: skip -->
```phel
(dotimes [i 3]
  (println "Line" (inc i)))
```
{% </question> %}
{% <solution> %}
```phel
(dotimes [i 3]
  (println "Line" (inc i)))
; prints:
; Line 1
; Line 2
; Line 3
; => nil
```
`dotimes` counts `i` from 0 up to, but not including, 3. It returns `nil` because it exists for the printing, not for a result. If you need the lines as data, use `for` or `map`.
{% </solution> %}

## Lazy sequences

`iterate`, `repeat` with one argument, `cycle`, and `(range)` with no arguments return infinite sequences. They are lazy: Phel computes items only when you ask for them. So you must limit them with `take` or `take-while`.

```phel
(take 4 (iterate inc 10)) ; => (10 11 12 13)
```

Laziness matters because you can describe "all the numbers" and let the consumer decide where to stop. `map` and `filter` are lazy too, so a pipeline over an infinite sequence does only the work needed for the answer.

{% <question difficulty="medium" kind="predict"> %}
What does each call return?
<!-- phel-test: skip -->
```phel
(take 5 (iterate #(* 2 %) 1))
(take 5 (cycle [:red :green]))
(repeat 3 "ab")
(take-while #(< % 100) (iterate #(* 3 %) 1))
```
{% </question> %}
{% <hint> %}
`iterate` calls the function on its last result, again and again.
{% </hint> %}
{% <solution> %}
```phel
(take 5 (iterate #(* 2 %) 1))                ; => (1 2 4 8 16)
(take 5 (cycle [:red :green]))               ; => (:red :green :red :green :red)
(repeat 3 "ab")                              ; => ["ab" "ab" "ab"]
(take-while #(< % 100) (iterate #(* 3 %) 1)) ; => (1 3 9 27 81)
```
`repeat` with a count is not infinite, so it returns a plain vector. `take-while` stops at the first item that fails the test, so it works well on an infinite sequence.
{% </solution> %}

{% <question difficulty="hard" kind="write"> %}
Find the first square number greater than 1000. Do not guess an upper limit: start from `(range)`, which counts up forever.
<!-- phel-test: skip -->
```phel
; => 1024
```
{% </question> %}
{% <hint> %}
Build the squares with `map`, keep the big ones with `filter`, and take the `first`. A `->>` pipeline reads well here.
{% </hint> %}
{% <solution> %}
```phel
(->> (range)
     (map #(* % %))
     (filter #(> % 1000))
     (first))
; => 1024
```
This works because every step is lazy: `first` asks for one item, so Phel squares only a few dozen numbers and stops. A `loop` would work too, but the pipeline says what you want instead of how to count.
{% </solution> %}
