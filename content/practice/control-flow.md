+++
title = "Control Flow"
weight = 4
+++

Making decisions and repeating actions. Phel gives you several tools for control flow — each suited for different situations.

{% question() %}
Define a function `absolute` that returns the absolute value of a number using `if`.
```phel
(absolute -5)  # => 5
(absolute 3)   # => 3
```
{% end %}
{% solution() %}
```phel
(defn absolute [n]
  (if (< n 0)
    (- n)
    n))
```
`if` takes a condition, a "then" branch, and an "else" branch. It always returns a value.

Learn more: [Control Flow](/documentation/control-flow)
{% end %}

{% question() %}
Define a function `small?` that returns `true` for numbers under 100.
```phel
(small? 99)  # => true
(small? 100) # => false
```
{% end %}
{% solution() %}
```phel
(defn small? [n] (< n 100))
```
By convention, predicate functions (that return true/false) end with `?`.

Learn more: [Control Flow](/documentation/control-flow)
{% end %}

{% question() %}
Use `when` to print a warning only if a number is negative. What does `when` return when the condition is false?
```phel
(defn warn-if-negative [n] ...)
(warn-if-negative -5) # prints "Warning: negative number!"
(warn-if-negative 5)  # => nil
```
{% end %}
{% solution() %}
```phel
(defn warn-if-negative [n]
  (when (< n 0)
    (println "Warning: negative number!")))

(warn-if-negative -5) # prints "Warning: negative number!"
(warn-if-negative 5)  # => nil
```
`when` is like `if` without an else branch. It returns `nil` when the condition is false. Use it when you only care about one case.

Learn more: [Control Flow](/documentation/control-flow)
{% end %}

{% question() %}
Define a function `grade` that converts a score to a letter grade:
```phel
(grade 95) # => "A"
(grade 82) # => "B"
(grade 71) # => "C"
(grade 55) # => "F"
```
Use the `cond` structure (scores: A >= 90, B >= 80, C >= 70, F otherwise).
{% end %}
{% solution() %}
```phel
(defn grade [score]
  (cond
    (>= score 90) "A"
    (>= score 80) "B"
    (>= score 70) "C"
    "F"))
```
`cond` tests conditions in order and returns the value for the first truthy one. The final standalone value acts as a default (like "else").

Learn more: [Control Flow](/documentation/control-flow)
{% end %}

{% question() %}
Define a function `day-type` that classifies days of the week:
```phel
(day-type :monday)   # => "weekday"
(day-type :saturday) # => "weekend"
(day-type :friday)   # => "almost there!"
```
Use the `case` structure.
{% end %}
{% solution() %}
```phel
(defn day-type [day]
  (case day
    :saturday "weekend"
    :sunday   "weekend"
    :friday   "almost there!"
    "weekday"))
```
`case` matches a value against exact cases. The last value without a match acts as the default. It's cleaner than `cond` when you're comparing against specific values.

Learn more: [Control Flow](/documentation/control-flow)
{% end %}

{% question() %}
The exercises in the [Conditionals & Structures](/practice/conditionals-and-structures) section of the old practice used a `message` function with `if`, `cond`, and `case`. Now that you know all three — when would you choose each one? Write a `describe-temp` function that uses the best fit:
```phel
(describe-temp 35)  # => "hot"
(describe-temp 20)  # => "nice"
(describe-temp 5)   # => "cold"
(describe-temp -10) # => "freezing"
```
{% end %}
{% solution() %}
```phel
(defn describe-temp [degrees]
  (cond
    (>= degrees 30) "hot"
    (>= degrees 15) "nice"
    (>= degrees 0)  "cold"
    "freezing"))
```
`cond` is the right choice here because we're testing **ranges**, not exact values. Use `case` for exact matches, `if` for simple true/false, and `cond` for multiple conditions.

Learn more: [Control Flow](/documentation/control-flow)
{% end %}

{% question() %}
Use `loop` and `recur` to build a vector of numbers from `1` to `10`.
{% end %}
{% solution() %}
```phel
(loop [v [] i 1]
  (if (> i 10)
    v
    (recur (push v i) (inc i))))
# => [1 2 3 4 5 6 7 8 9 10]
```
`loop` defines initial bindings and `recur` jumps back to the loop with new values. This is Phel's way of doing iteration without mutable variables.

Learn more: [Control Flow](/documentation/control-flow)
{% end %}

{% question() %}
Use `loop` and `recur` to compute the sum of numbers from 1 to 100.
{% end %}
{% solution() %}
```phel
(loop [i 1 total 0]
  (if (> i 100)
    total
    (recur (inc i) (+ total i))))
# => 5050
```
The accumulator pattern: carry your result in a loop variable and return it when done. (Fun fact: the answer is the same one young Gauss famously computed in seconds!)

Learn more: [Control Flow](/documentation/control-flow)
{% end %}
