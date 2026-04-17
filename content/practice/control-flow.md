+++
title = "Control Flow"
weight = 4
+++

Decisions and loops without mutable variables. Each tool here fits a different shape of problem - learn when to reach for which.

{% question(difficulty="easy") %}
Define `absolute` returning the absolute value of a number using `if`.
```phel
(absolute -5) ; => 5
(absolute 3)  ; => 3
```
{% end %}
{% solution() %}
```phel
(defn absolute [n]
  (if (< n 0)
    (- n)
    n))
```
`if` takes a condition, a then-branch, and an else-branch - and always returns a value (it's an expression, not a statement).

Learn more: [Control Flow](/documentation/language/control-flow)
{% end %}

{% question(difficulty="easy") %}
Define a predicate `small?` that returns `true` for numbers under 100.
```phel
(small? 99)  ; => true
(small? 100) ; => false
```
{% end %}
{% solution() %}
```phel
(defn small? [n] (< n 100))
```
Predicate functions end with `?` by convention.

Learn more: [Control Flow](/documentation/language/control-flow)
{% end %}

{% question(difficulty="easy") %}
Use `when` to print a warning only if a number is negative. What does `when` return when the condition is false?
```phel
(warn-if-negative -5) ; prints "Warning: negative number!"
(warn-if-negative 5)  ; => nil
```
{% end %}
{% solution() %}
```phel
(defn warn-if-negative [n]
  (when (< n 0)
    (println "Warning: negative number!")))
```
`when` is an `if` without an else branch. It returns `nil` when the condition is falsy. Reach for it when you only care about one side of the decision.

Learn more: [Control Flow](/documentation/language/control-flow)
{% end %}

{% question(difficulty="medium") %}
Use `if-let` to greet a user by name only if the map has one:
```phel
(welcome {:name "Ada"}) ; => "Welcome, Ada!"
(welcome {})            ; => "Welcome, stranger!"
```
{% end %}
{% solution() %}
```phel
(defn welcome [user]
  (if-let [name (:name user)]
    (str "Welcome, " name "!")
    "Welcome, stranger!"))
```
`if-let` binds and tests in one step. If the value is truthy you can use the binding in the then-branch; otherwise you fall through to the else.

Learn more: [Control Flow](/documentation/language/control-flow)
{% end %}

{% question(difficulty="medium") %}
Define `grade` mapping a score to a letter (A >= 90, B >= 80, C >= 70, F otherwise) with `cond`.
```phel
(grade 95) ; => "A"
(grade 82) ; => "B"
(grade 71) ; => "C"
(grade 55) ; => "F"
```
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
`cond` checks each condition in order and returns the value beside the first truthy one. A trailing standalone value acts as the default.

Learn more: [Control Flow](/documentation/language/control-flow)
{% end %}

{% question(difficulty="medium") %}
Define `day-type` that classifies days using `case`:
```phel
(day-type :monday)   ; => "weekday"
(day-type :saturday) ; => "weekend"
(day-type :friday)   ; => "almost there!"
```
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
`case` matches a value against fixed constants. Cleaner than `cond` when you're comparing against specific values.

Learn more: [Control Flow](/documentation/language/control-flow)
{% end %}

{% question(difficulty="medium") %}
Pick the right tool. Write `describe-temp` for ranges:
```phel
(describe-temp 35)  ; => "hot"
(describe-temp 20)  ; => "nice"
(describe-temp 5)   ; => "cold"
(describe-temp -10) ; => "freezing"
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
`cond` wins here because we're testing **ranges**, not exact values. Rule of thumb: `if` for one binary choice, `case` for exact constants, `cond` for multiple range conditions.

Learn more: [Control Flow](/documentation/language/control-flow)
{% end %}

{% question(difficulty="medium") %}
Use `loop` and `recur` to build a vector of numbers from `1` to `10`.
{% end %}
{% solution() %}
```phel
(loop [v [] i 1]
  (if (> i 10)
    v
    (recur (push v i) (inc i))))
; => [1 2 3 4 5 6 7 8 9 10]
```
`loop` defines starting bindings, `recur` jumps back with new values. This is Phel's stack-safe way to iterate without mutable variables.

Learn more: [Control Flow](/documentation/language/control-flow)
{% end %}

{% question(difficulty="medium") %}
Use `loop` and `recur` to compute the sum of `1` to `100`.
{% end %}
{% solution() %}
```phel
(loop [i 1 total 0]
  (if (> i 100)
    total
    (recur (inc i) (+ total i))))
; => 5050
```
The accumulator pattern: carry your running result in a loop variable and return it when done. (Yes, that's the answer young Gauss famously found in seconds.)

Learn more: [Control Flow](/documentation/language/control-flow)
{% end %}
