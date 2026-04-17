+++
title = "Functional Toolbox"
weight = 5
+++

This is where Phel sings. `map`, `filter`, `reduce`, threading macros, list comprehensions, and destructuring turn data wrangling into a readable pipeline.

{% question(difficulty="medium") %}
Increment every number in `[4 7 9 10]`. Use `map`.
{% end %}
{% solution() %}
```phel
;; pass a named function directly
(map inc [4 7 9 10])
;; => [5 8 10 11]

;; or with the short anonymous form
(map |(+ $ 1) [4 7 9 10])

;; or with fn
(map (fn [x] (+ x 1)) [4 7 9 10])
```
`map` applies a function to every element and returns a new collection. When a built-in already does what you need (like `inc`), pass it by name.

Learn more: [Functions and Recursion](/documentation/language/functions-and-recursion)
{% end %}

{% question(difficulty="medium") %}
Uppercase every name:
```phel
["ada" "grace" "alan"]
;; => ["ADA" "GRACE" "ALAN"]
```
Hint: `php/strtoupper` does the lifting.
{% end %}
{% solution() %}
```phel
(map php/strtoupper ["ada" "grace" "alan"])
;; => ["ADA" "GRACE" "ALAN"]
```
PHP functions are reachable via the `php/` prefix - the entire PHP stdlib is at your fingertips.

Learn more: [PHP Interop](/documentation/php-interop)
{% end %}

{% question(difficulty="medium") %}
Keep only the even numbers in `[1 2 3 4 5 6 7 8 9 10]`.
{% end %}
{% solution() %}
```phel
(filter even? [1 2 3 4 5 6 7 8 9 10])
;; => (2 4 6 8 10)
```
`filter` keeps the elements for which the predicate returns truthy.
{% end %}

{% question(difficulty="medium") %}
From `[1 2 3 4 5 6 7 8 9 10]`, keep the evens and double each.
{% end %}
{% solution() %}
```phel
(map |(* $ 2) (filter even? [1 2 3 4 5 6 7 8 9 10]))
;; => (4 8 12 16 20)
```
Composing `filter` and `map` is a daily pattern. Read it inside-out: filter first, then map.
{% end %}

{% question(difficulty="medium") %}
Rewrite the previous solution with the threading macro `->>` so it reads top-to-bottom.
{% end %}
{% solution() %}
```phel
(->> [1 2 3 4 5 6 7 8 9 10]
     (filter even?)
     (map |(* $ 2)))
;; => (4 8 12 16 20)
```
`->>` (thread-last) feeds each result as the **last** argument to the next call. Nested calls become a clear pipeline.

Learn more: [Functions and Recursion](/documentation/language/functions-and-recursion)
{% end %}

{% question(difficulty="medium") %}
Use the other threading macro, `->`, to build a user step by step:
```phel
(-> {}
    (put :name "Ada")
    (put :age 36)
    (put :role :admin))
;; => {:name "Ada" :age 36 :role :admin}
```
{% end %}
{% solution() %}
```phel
(-> {}
    (put :name "Ada")
    (put :age 36)
    (put :role :admin))
;; => {:name "Ada" :age 36 :role :admin}
```
`->` (thread-first) inserts each result as the **first** argument of the next call. Use `->` for "build up a value" pipelines (often with maps), and `->>` for "transform a sequence" pipelines.

Learn more: [Functions and Recursion](/documentation/language/functions-and-recursion)
{% end %}

{% question(difficulty="medium") %}
Use `reduce` to sum `[1 2 3 4 5]`.
{% end %}
{% solution() %}
```phel
(reduce + 0 [1 2 3 4 5])
;; => 15
```
`reduce` collapses a collection to a single value: it takes a function, an initial value, and a collection. Here it computes `(+ (+ (+ (+ (+ 0 1) 2) 3) 4) 5)`.
{% end %}

{% question(difficulty="medium") %}
Use `reduce` to find the longest string in `["cat" "elephant" "dog" "hippopotamus"]`.
{% end %}
{% solution() %}
```phel
(reduce
  (fn [longest s]
    (if (> (php/strlen s) (php/strlen longest)) s longest))
  ""
  ["cat" "elephant" "dog" "hippopotamus"])
;; => "hippopotamus"
```
Any time you need to fold a collection down to a single value, `reduce` is the tool.
{% end %}

{% question(difficulty="medium") %}
Use `for` to extract every `:value` from this vector of maps:
```phel
(def data [{:id 1 :value 10.3} {:id 2 :value 20.06} {:id 7 :value 30.1}])
```
Expected result: `(10.3 20.06 30.1)`.
{% end %}
{% solution() %}
```phel
(def data [{:id 1 :value 10.3} {:id 2 :value 20.06} {:id 7 :value 30.1}])
(for [m :in data] (:value m))
;; => (10.3 20.06 30.1)
```
`for` is a list comprehension - it generates a new sequence by transforming each element.

Learn more: [Control Flow](/documentation/language/control-flow)
{% end %}

{% question(difficulty="medium") %}
Use `for` with two bindings to generate every combination of suit and rank:
```phel
(def suits [:hearts :diamonds :clubs :spades])
(def ranks [:ace :king :queen])
;; => build all 12 [suit rank] pairs
```
{% end %}
{% solution() %}
```phel
(def suits [:hearts :diamonds :clubs :spades])
(def ranks [:ace :king :queen])

(for [s :in suits
      r :in ranks]
  [s r])
;; => ([:hearts :ace] [:hearts :king] [:hearts :queen]
;;     [:diamonds :ace] [:diamonds :king] [:diamonds :queen]
;;     [:clubs :ace] [:clubs :king] [:clubs :queen]
;;     [:spades :ace] [:spades :king] [:spades :queen])
```
Multiple bindings act like nested loops: the right-most binding varies fastest. Add `:when` clauses to filter, `:let` clauses to bind locals.

Learn more: [Control Flow](/documentation/language/control-flow)
{% end %}

{% question(difficulty="medium") %}
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
;; => [{:name "Bob" :age 25} {:name "Charlie" :age 30} {:name "Ada" :age 36}]
```
`sort-by` takes a key function and a collection. Since keywords are functions, `:age` extracts the value to compare on.
{% end %}

{% question(difficulty="medium") %}
Use `update-in` to bump the balance from `3` to `4`:
```phel
(def data {:shops [:shop-1]
           :customers [{:id "Bob"
                        :account {:balance 3}}]})
```
{% end %}
{% solution() %}
```phel
(update-in data [:customers 0 :account :balance] inc)
;; => {:shops [:shop-1] :customers [{:id "Bob" :account {:balance 4}}]}
```
`update-in` walks into a nested structure and applies a function at the path. Pure - the original data stays put.

Learn more: [Data Structures](/documentation/language/data-structures)
{% end %}

{% question(difficulty="medium") %}
Use `frequencies` to count fruit appearances:
```phel
["apple" "banana" "apple" "cherry" "banana" "apple"]
```
{% end %}
{% solution() %}
```phel
(frequencies ["apple" "banana" "apple" "cherry" "banana" "apple"])
;; => {"apple" 3 "banana" 2 "cherry" 1}
```
`frequencies` returns a map from each distinct element to its count. Perfect for tallies.
{% end %}

{% question(difficulty="medium") %}
Use `group-by` to split numbers into evens and odds:
```phel
[1 2 3 4 5 6 7 8]
```
{% end %}
{% solution() %}
```phel
(group-by even? [1 2 3 4 5 6 7 8])
;; => {false [1 3 5 7] true [2 4 6 8]}
```
`group-by` runs the function on each element and bundles values that share a result.
{% end %}

{% question(difficulty="hard") %}
Define `area` so it accepts a map `{:width w :height h}` and returns `w * h`. Use destructuring in the parameter list:
```phel
(area {:width 5 :height 3}) ;; => 15
```
{% end %}
{% solution() %}
```phel
(defn area [{:keys [width height]}]
  (* width height))
```
`{:keys [width height]}` destructures the map directly in the parameter list - no `let` required. This pattern is everywhere in idiomatic Phel: tidy callers, self-documenting signatures.

Learn more: [Destructuring](/documentation/language/destructuring)
{% end %}

{% question(difficulty="hard") %}
Define `combine` that merges a vector of maps into one:
```phel
(combine [{:a 1 :b 2} {:c 3} {:d 4 :e 5}])
;; => {:a 1 :b 2 :c 3 :d 4 :e 5}
```
{% end %}
{% solution() %}
```phel
(defn combine [maps]
  (apply merge maps))
```
`apply` "spreads" the vector as individual arguments. So `(apply merge [{:a 1} {:b 2}])` is the same as `(merge {:a 1} {:b 2})`. This trick works with any variadic function.

Learn more: [Functions and Recursion](/documentation/language/functions-and-recursion)
{% end %}
