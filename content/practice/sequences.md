+++
title = "Sequences"
weight = 5
description = "Transform collections with map, filter, and reduce, then chain the steps into clear pipelines with ->> and ->."
aliases = ["/practice/working-with-collections/"]

[extra]
stage = "Functional core"
goals = [
  "Build number ranges with `range`",
  "Transform, select, and combine values with `map`, `filter`, `remove`, and `reduce`",
  "Sort, count, and group data with `sort-by`, `frequencies`, and `group-by`",
  "Turn nested calls into readable pipelines with `->>` and `->`",
]
read_first = [
  ["Data Structures", "/documentation/language/data-structures/"],
  ["Lazy Sequences", "/documentation/language/lazy-sequences/"],
  ["Functions and Recursion", "/documentation/language/functions-and-recursion/"],
]
recap = [
  "You can transform a whole collection without writing a loop",
  "You can fold a collection into one value with `reduce`, with or without a start value",
  "You can sort, count, and group data in one line",
  "You can rewrite nested calls as a `->>` pipeline that reads top to bottom",
]
+++

Most programs take a collection, change each item, keep some of them, and sum up the rest. In Phel you do this with a small set of functions instead of `foreach` loops. Each function does one job and returns a new value, so you can chain them. By the end of this module you will write data pipelines that read like a list of steps.

## Ranges and map

`range` builds a sequence of numbers: the end is not included. `map` calls a function on every item and returns the results. Both return a sequence, which prints with round brackets.

```phel
(range 1 4)           ; => (1 2 3)
(map inc [1 2 3])     ; => (2 3 4)
```

{% <question difficulty="easy" kind="predict"> %}
What does each call return?
<!-- phel-test: skip -->
```phel
(range 5)
(range 1 6)
(range 0 10 3)
```
{% </question> %}
{% <solution> %}
```phel
(range 5)      ; => (0 1 2 3 4)
(range 1 6)    ; => (1 2 3 4 5)
(range 0 10 3) ; => (0 3 6 9)
```
With one argument, `range` starts at 0. The end is never included. The third argument is the step.
{% </solution> %}

{% <question difficulty="easy" kind="fill"> %}
Fill in the blanks so each line returns the result in the comment.
<!-- phel-test: skip -->
```phel
(map ___ [4 7 9 10])            ; => (5 8 10 11)
(map ___ ["ada" "grace" "alan"]) ; => (3 5 4)
```
{% </question> %}
{% <hint> %}
You already know a function that adds one, and a function that tells you the length of a string.
{% </hint> %}
{% <solution> %}
```phel
(map inc [4 7 9 10])             ; => (5 8 10 11)
(map count ["ada" "grace" "alan"]) ; => (3 5 4)
```
When a function already does what you need, pass it by name. You do not need to wrap it in `#(inc %)`.
{% </solution> %}

## Filter, remove, some, and every?

`filter` keeps the items where a predicate returns a truthy value. `remove` does the opposite. `some` returns the first truthy answer, and `every?` checks that all items pass.

```phel
(filter pos? [-1 2 -3 4]) ; => (2 4)
```

{% <question difficulty="easy" kind="predict"> %}
What does each call return?
<!-- phel-test: skip -->
```phel
(filter even? [1 2 3 4 5 6])
(remove even? [1 2 3 4 5 6])
(some even? [1 3 5])
(every? pos? [1 2 3])
```
{% </question> %}
{% <solution> %}
```phel
(filter even? [1 2 3 4 5 6]) ; => (2 4 6)
(remove even? [1 2 3 4 5 6]) ; => (1 3 5)
(some even? [1 3 5])         ; => nil
(every? pos? [1 2 3])        ; => true
```
`some` returns `nil` when no item passes, not `false`. That is fine in an `if`, because `nil` is falsy.
{% </solution> %}

{% <question difficulty="medium" kind="write"> %}
From the numbers 1 to 10, keep the even ones and double each. Use `range`, `filter`, and `map`.
<!-- phel-test: skip -->
```phel
; => (4 8 12 16 20)
```
{% </question> %}
{% <hint> %}
Filter first, then map. The inner call runs first.
{% </hint> %}
{% <solution> %}
```phel
(map #(* % 2) (filter even? (range 1 11)))
; => (4 8 12 16 20)
```
Read nested calls from the inside out: `range`, then `filter`, then `map`. Later in this module you will rewrite this so it reads top to bottom.
{% </solution> %}

## Reduce

`reduce` folds a collection into one value. It calls a function with the result so far and the next item. You can give a start value; without one, `reduce` starts with the first item.

```phel
(reduce + 0 [1 2 3]) ; => 6, computed as (+ (+ (+ 0 1) 2) 3)
```

{% <question difficulty="easy" kind="predict"> %}
What does each call return?
<!-- phel-test: skip -->
```phel
(reduce + [1 2 3 4 5])
(reduce + 10 [1 2 3])
(reduce * [1 2 3 4])
(reduce + [])
```
{% </question> %}
{% <solution> %}
```phel
(reduce + [1 2 3 4 5]) ; => 15
(reduce + 10 [1 2 3])  ; => 16
(reduce * [1 2 3 4])   ; => 24
(reduce + [])          ; => 0
```
With an empty collection and no start value, `reduce` calls the function with no arguments. `(+)` returns 0, so the answer is 0.
{% </solution> %}

{% <question difficulty="medium" kind="write"> %}
Write `longest` that returns the longest string in a vector. Use `reduce`.
<!-- phel-test: skip -->
```phel
(longest ["cat" "elephant" "dog" "hippopotamus"]) ; => "hippopotamus"
(longest [])                                      ; => ""
```
{% </question> %}
{% <hint> %}
Start with `""`. At each step, keep whichever of the two strings has the larger `count`.
{% </hint> %}
{% <solution> %}
```phel
(defn longest [words]
  (reduce
    (fn [best word]
      (if (> (count word) (count best)) word best))
    ""
    words))

(longest ["cat" "elephant" "dog" "hippopotamus"]) ; => "hippopotamus"
(longest [])                                      ; => ""
```
The start value `""` also gives a safe answer for an empty vector. Whenever you need one value out of many, think of `reduce`.
{% </solution> %}

{% <question difficulty="medium" kind="fix"> %}
This should return the total number of characters in all words. It fails with `Expected a number, got string`. Fix it.
<!-- phel-test: skip -->
```phel
(reduce (fn [total word] (+ total (count word)))
        ["hi" "there" "phel"])
; expected => 11
```
{% </question> %}
{% <hint> %}
Without a start value, what is `total` on the first call?
{% </hint> %}
{% <solution> %}
```phel
(reduce (fn [total word] (+ total (count word)))
        0
        ["hi" "there" "phel"])
; => 11
```
Without a start value, the first item `"hi"` becomes `total`, so the first step is `(+ "hi" 5)`. When the result has a different type than the items, always give a start value.
{% </solution> %}

## Sort, take, drop, and distinct

`sort` orders a collection. Pass a comparison like `>` to reverse the order. `sort-by` sorts by the result of a function, often a keyword. `take` and `drop` keep or skip the first items, and `distinct` removes repeats.

```phel
(sort-by count ["ccc" "a" "bb"]) ; => ["a" "bb" "ccc"]
```

{% <question difficulty="medium" kind="predict"> %}
What does each call return?
<!-- phel-test: skip -->
```phel
(sort [5 1 4 2 3])
(take 3 (sort > [5 1 4 2 3]))
(drop 2 [:a :b :c :d])
(distinct [1 2 1 3 2])
```
{% </question> %}
{% <hint> %}
`(sort > ...)` puts the biggest number first.
{% </hint> %}
{% <solution> %}
```phel
(sort [5 1 4 2 3])            ; => [1 2 3 4 5]
(take 3 (sort > [5 1 4 2 3])) ; => (5 4 3)
(drop 2 [:a :b :c :d])        ; => (:c :d)
(distinct [1 2 1 3 2])        ; => (1 2 3)
```
`sort` returns a vector. `take`, `drop`, and `distinct` return sequences. `distinct` keeps the first time it sees each value.
{% </solution> %}

{% <question difficulty="medium" kind="write"> %}
Return the name of the youngest person.
<!-- phel-test: skip -->
```phel
(def people [{:name "Charlie" :age 30}
             {:name "Ada" :age 36}
             {:name "Bob" :age 25}])
; => "Bob"
```
{% </question> %}
{% <hint> %}
Keywords are functions, so `:age` can be the sort key.
{% </hint> %}
{% <solution> %}
```phel
(def people [{:name "Charlie" :age 30}
             {:name "Ada" :age 36}
             {:name "Bob" :age 25}])

(:name (first (sort-by :age people)))
; => "Bob"
```
`sort-by :age` puts the youngest first. Then `first` takes that map and `:name` reads the name.
{% </solution> %}

## Counting and grouping

`frequencies` counts how often each value appears. `group-by` calls a function on each item and puts items with the same result together.

```phel
(frequencies [:a :b :a]) ; => {:a 2, :b 1}
```

{% <question difficulty="medium" kind="write"> %}
Group these words by their length.
<!-- phel-test: skip -->
```phel
["a" "bb" "cc" "d" "eee"]
; => {1 ["a" "d"], 2 ["bb" "cc"], 3 ["eee"]}
```
Then count how often each fruit appears:
<!-- phel-test: skip -->
```phel
["apple" "banana" "apple" "cherry" "banana" "apple"]
; => {"apple" 3, "banana" 2, "cherry" 1}
```
{% </question> %}
{% <hint> %}
One function groups by the result of another function. The other one counts.
{% </hint> %}
{% <solution> %}
```phel
(group-by count ["a" "bb" "cc" "d" "eee"])
; => {1 ["a" "d"], 2 ["bb" "cc"], 3 ["eee"]}

(frequencies ["apple" "banana" "apple" "cherry" "banana" "apple"])
; => {"apple" 3, "banana" 2, "cherry" 1}
```
`group-by` keeps the items in each group. `frequencies` keeps only the count. In PHP you would reach for `array_count_values` for the second one.
{% </solution> %}

## Pipelines with ->> and ->

Nested calls read from the inside out. The thread-last macro `->>` lets you write the same steps top to bottom: it puts each result as the last argument of the next call. The thread-first macro `->` puts it as the first argument instead.

```phel
(->> [1 2 3]
     (map inc)
     (reduce +))
; => 9, same as (reduce + (map inc [1 2 3]))
```

Use `->>` for sequence functions (`map`, `filter`, `reduce` take the collection last). Use `->` for map functions (`assoc`, `update`, `get` take the map first).

{% <question difficulty="medium" kind="refactor"> %}
Rewrite this with `->>` so it reads top to bottom.
<!-- phel-test: skip -->
```phel
(map #(* % 2) (filter even? (range 1 11)))
; => (4 8 12 16 20)
```
{% </question> %}
{% <hint> %}
Start the pipeline with the innermost value, `(range 1 11)`.
{% </hint> %}
{% <solution> %}
```phel
(->> (range 1 11)
     (filter even?)
     (map #(* % 2)))
; => (4 8 12 16 20)
```
Each line is one step. To add a step, you add a line, instead of wrapping the whole thing in another call.
{% </solution> %}

{% <question difficulty="medium" kind="refactor"> %}
Rewrite this with `->` so each change is on its own line.
<!-- phel-test: skip -->
```phel
(update (assoc (assoc {} :name "Ada") :age 36) :age inc)
; => {:name "Ada", :age 37}
```
{% </question> %}
{% <hint> %}
`assoc` and `update` take the map as their first argument. Which macro puts the value first?
{% </hint> %}
{% <solution> %}
```phel
(-> {}
    (assoc :name "Ada")
    (assoc :age 36)
    (update :age inc))
; => {:name "Ada", :age 37}
```
`->` passes the map as the first argument of each call. Using `->>` here would pass the map as the last argument of `assoc`, which is the wrong place.
{% </solution> %}

{% <question difficulty="hard" kind="refactor"> %}
This returns the three largest word lengths, without repeats. Rewrite it as a `->>` pipeline.
<!-- phel-test: skip -->
```phel
(def words ["apple" "fig" "banana" "kiwi" "plum" "cherry" "pear"])

(take 3 (sort > (distinct (map count words))))
; => (6 5 4)
```
{% </question> %}
{% <hint> %}
List the steps from the inside out: count each word, drop repeats, sort, take three.
{% </hint> %}
{% <solution> %}
```phel
(def words ["apple" "fig" "banana" "kiwi" "plum" "cherry" "pear"])

(->> words
     (map count)
     (distinct)
     (sort >)
     (take 3))
; => (6 5 4)
```
`(sort >)` works in a pipeline because `sort` takes the collection last. You can also write a step with no extra arguments without brackets, like `distinct`, but brackets keep every line the same shape.
{% </solution> %}

{% <question difficulty="hard" kind="write"> %}
Write `top-words` that returns the `n` most common words, most common first. Use a `->>` pipeline.
<!-- phel-test: skip -->
```phel
(top-words 2 ["a" "b" "a" "c" "a" "b"]) ; => ("a" "b")
(top-words 1 ["x" "y" "y"])             ; => ("y")
```
{% </question> %}
{% <hint> %}
`frequencies` gives you a map. Sorting a map gives you `[word count]` pairs, and `second` reads the count from a pair.
{% </hint> %}
{% <solution> %}
```phel
(defn top-words [n words]
  (->> words
       (frequencies)
       (sort-by second >)
       (take n)
       (map first)))

(top-words 2 ["a" "b" "a" "c" "a" "b"]) ; => ("a" "b")
(top-words 1 ["x" "y" "y"])             ; => ("y")
```
`sort-by` also takes a comparison, so `(sort-by second >)` sorts by count, largest first. The last step keeps only the word from each pair.
{% </solution> %}
