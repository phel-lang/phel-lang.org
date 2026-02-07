+++
title = "Working with Collections"
weight = 5
+++

This is where Phel really shines. Transforming data with `map`, `filter`, `reduce`, and the threading macro is at the heart of functional programming.

{% question() %}
Increment all numbers in `[4 7 9 10]` by one. Use the `map` function.
{% end %}
{% solution() %}
```phel
# Using a named function directly
(map inc [4 7 9 10])
# => [5 8 10 11]

# Using an anonymous function
(map |(+ $ 1) [4 7 9 10])

# Using fn
(map (fn [x] (+ x 1)) [4 7 9 10])
```
`map` applies a function to every element of a collection and returns a new collection. When a named function already does what you need (like `inc`), pass it directly.

Learn more: [Functions and Recursion](/documentation/functions-and-recursion)
{% end %}

{% question() %}
Given a list of names, return them all in uppercase:
```phel
["ada" "grace" "alan"]
# => ["ADA" "GRACE" "ALAN"]
```
Hint: `php/strtoupper` converts a string to uppercase.
{% end %}
{% solution() %}
```phel
(map php/strtoupper ["ada" "grace" "alan"])
# => ["ADA" "GRACE" "ALAN"]
```
You can use PHP functions directly in Phel with the `php/` prefix. This gives you access to the entire PHP standard library.

Learn more: [PHP Interop](/documentation/php-interop)
{% end %}

{% question() %}
Filter the even numbers from `[1 2 3 4 5 6 7 8 9 10]`.
{% end %}
{% solution() %}
```phel
(filter even? [1 2 3 4 5 6 7 8 9 10])
# => (2 4 6 8 10)
```
`filter` keeps only the elements for which the predicate returns `true`.
{% end %}

{% question() %}
From the vector `[1 2 3 4 5 6 7 8 9 10]`, get only the even numbers and then double each one.
{% end %}
{% solution() %}
```phel
(map |(* $ 2) (filter even? [1 2 3 4 5 6 7 8 9 10]))
# => (4 8 12 16 20)
```
Composing `filter` and `map` is a common pattern. Read it inside-out: first filter, then map.
{% end %}

{% question() %}
Rewrite the previous exercise using the threading macro `->>` so it reads top-to-bottom instead of inside-out.
{% end %}
{% solution() %}
```phel
(->> [1 2 3 4 5 6 7 8 9 10]
     (filter even?)
     (map |(* $ 2)))
# => (4 8 12 16 20)
```
`->>` (thread-last) passes each result as the **last** argument to the next function. It turns nested calls into a readable pipeline.

Learn more: [Functions and Recursion](/documentation/functions-and-recursion)
{% end %}

{% question() %}
Use `reduce` to compute the sum of `[1 2 3 4 5]`.
{% end %}
{% solution() %}
```phel
(reduce + 0 [1 2 3 4 5])
# => 15
```
`reduce` combines all elements of a collection into a single value. It takes: a function, an initial value, and a collection. Here, it computes `(+ (+ (+ (+ (+ 0 1) 2) 3) 4) 5)`.
{% end %}

{% question() %}
Use `reduce` to find the longest string in `["cat" "elephant" "dog" "hippopotamus"]`.
{% end %}
{% solution() %}
```phel
(reduce
  (fn [longest s]
    (if (> (php/strlen s) (php/strlen longest)) s longest))
  ""
  ["cat" "elephant" "dog" "hippopotamus"])
# => "hippopotamus"
```
`reduce` is very flexible — any time you need to "collapse" a collection into a single value, it's your tool.
{% end %}

{% question() %}
Use the `for` structure to extract all `:value` entries from this vector of maps:
```phel
(def data [{:id 1 :value 10.3} {:id 2 :value 20.06} {:id 7 :value 30.1}])
```
Expected result: `(10.3 20.06 30.1)`
{% end %}
{% solution() %}
```phel
(def data [{:id 1 :value 10.3} {:id 2 :value 20.06} {:id 7 :value 30.1}])
(for [m :in data] (:value m))
# => (10.3 20.06 30.1)
```
`for` is a list comprehension — it generates a new sequence by transforming each element.

Learn more: [Control Flow](/documentation/control-flow)
{% end %}

{% question() %}
Sort a list of people by age:
```phel
(def people [{:name "Charlie" :age 30}
             {:name "Ada" :age 36}
             {:name "Bob" :age 25}])
```
{% end %}
{% solution() %}
```phel
(sort-by :age people)
# => [{:name "Bob" :age 25} {:name "Charlie" :age 30} {:name "Ada" :age 36}]
```
`sort-by` takes a key function and a collection. Since keywords are functions, `:age` extracts the value to sort on.
{% end %}

{% question() %}
Use `update-in` to change the balance from 3 to 4 in this nested structure:
```phel
(def data {:shops [:shop-1]
           :customers [{:id "Bob"
                        :account {:balance 3}}]})
```
{% end %}
{% solution() %}
```phel
(update-in data [:customers 0 :account :balance] inc)
# => {:shops [:shop-1] :customers [{:id "Bob" :account {:balance 4}}]}
```
`update-in` navigates into a nested structure and applies a function to the value it finds. Here, `inc` transforms `3` into `4` — all while keeping the original data unchanged.

Learn more: [Data Structures](/documentation/data-structures)
{% end %}

{% question() %}
Use `frequencies` to count how many times each fruit appears:
```phel
["apple" "banana" "apple" "cherry" "banana" "apple"]
```
{% end %}
{% solution() %}
```phel
(frequencies ["apple" "banana" "apple" "cherry" "banana" "apple"])
# => {"apple" 3 "banana" 2 "cherry" 1}
```
`frequencies` returns a map from each distinct element to the number of times it appears. Very useful for counting and analysis.
{% end %}

{% question() %}
Use `group-by` to split numbers into even and odd groups:
```phel
[1 2 3 4 5 6 7 8]
```
{% end %}
{% solution() %}
```phel
(group-by even? [1 2 3 4 5 6 7 8])
# => {false [1 3 5 7] true [2 4 6 8]}
```
`group-by` applies a function to each element and groups them by the result.
{% end %}

{% question() %}
Create a function `combine` that merges a vector of maps into one:
```phel
(combine [{:a 1 :b 2} {:c 3} {:d 4 :e 5}])
# => {:a 1 :b 2 :c 3 :d 4 :e 5}
```
{% end %}
{% solution() %}
```phel
(defn combine [maps]
  (apply merge maps))
```
`apply` "spreads" the vector as individual arguments to `merge`. So `(apply merge [{:a 1} {:b 2}])` becomes `(merge {:a 1} {:b 2})`.

Learn more: [Functions and Recursion](/documentation/functions-and-recursion)
{% end %}
