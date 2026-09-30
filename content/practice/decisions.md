+++
title = "Decisions"
weight = 4
description = "Branch on values with if, when, cond, and case, use and/or to pick values, and bind and test in one step with if-let."
aliases = ["/practice/control-flow/"]

[extra]
stage = "Foundations"
goals = [
  "Know which values count as false: only `nil` and `false`",
  "Choose between two values with `if`, and group steps with `do`",
  "Run code for one side only with `when`, `if-not`, and `when-not`",
  "Pick among many cases with `cond` and `case`",
  "Use `and`, `or`, `if-let`, and `when-let` to handle missing values",
]
read_first = [
  ["Control Flow", "/documentation/language/control-flow/"],
  ["Truthiness", "/documentation/language/basic-types/#truthiness"],
]
recap = [
  "You can predict whether any value counts as true or false",
  "You can write `if`, `when`, `cond`, and `case` and pick the right one",
  "You can give a missing value a default with `or`",
  "You can look up a value and branch on it with `if-let` and `when-let`",
]
+++

A program has to make choices: charge shipping or not, greet a user by name or as a stranger. In Phel every decision is an expression that returns a value, so there are no statements and no variables to assign inside branches. This module shows the tools for making choices and when to pick each one.

## Truthiness

A condition does not have to be `true` or `false`. Any value works. Only `nil` and `false` count as false (falsy). Every other value counts as true (truthy).

```phel
(if nil "yes" "no")   ; => "no"
(if :ok "yes" "no")   ; => "yes"
```

{% <question difficulty="easy" kind="predict"> %}
What does each line return?
```phel
(if 0 "yes" "no")
(if "" "yes" "no")
(if [] "yes" "no")
(if false "yes" "no")
```
{% </question> %}
{% <solution> %}
```phel
(if 0 "yes" "no")     ; => "yes"
(if "" "yes" "no")    ; => "yes"
(if [] "yes" "no")    ; => "yes"
(if false "yes" "no") ; => "no"
```
Only `nil` and `false` are falsy. This is different from PHP, where `0`, `""`, and an empty array are all false. To test for zero or empty, ask with a predicate such as `zero?` or `empty?`.

Learn more: [Truthiness](/documentation/language/basic-types/#truthiness)
{% </solution> %}

## if and do

`if` takes a test, a then-branch, and an optional else-branch. It returns the value of the branch it runs. Each branch is one form. To run several forms in one branch, group them with `do`, which returns its last value.

```phel
(if true
  (do (println "saving")
      :saved)
  :skipped) ; prints "saving", => :saved
```

{% <question difficulty="easy" kind="write"> %}
Define `absolute` with `if`. It returns the absolute value of a number.
<!-- phel-test: skip -->
```phel
(absolute -5) ; => 5
(absolute 3)  ; => 3
```
{% </question> %}
{% <solution> %}
```phel
(defn absolute [n]
  (if (< n 0)
    (- n)
    n))

(absolute -5) ; => 5
(absolute 3)  ; => 3
```
`if` returns a value, so the whole `if` is the function's result. In PHP this is closer to the ternary `$n < 0 ? -$n : $n` than to an `if` statement.

Learn more: [Control Flow](/documentation/language/control-flow/#if)
{% </solution> %}

{% <question difficulty="easy" kind="predict"> %}
There is no else-branch here. What does the second line return?
```phel
(if (> 3 2) "bigger")
(if (< 3 2) "bigger")
```
{% </question> %}
{% <solution> %}
```phel
(if (> 3 2) "bigger") ; => "bigger"
(if (< 3 2) "bigger") ; => nil
```
When the test is falsy and there is no else-branch, `if` returns `nil`.
{% </solution> %}

{% <question difficulty="medium" kind="fix"> %}
This function should print `"saving"` and return `:saved` for a valid record, and return `:rejected` otherwise. It does not compile. Fix it.
<!-- phel-test: skip -->
```phel
(defn save [record]
  (if (:valid record)
    (println "saving")
    :saved
    :rejected))
; ERROR: 'if requires two or three arguments
```
{% </question> %}
{% <hint> %}
`if` sees four forms after `if`: the test and three more. How do you make two forms count as one?
{% </hint> %}
{% <solution> %}
```phel
(defn save [record]
  (if (:valid record)
    (do (println "saving")
        :saved)
    :rejected))

(save {:valid true})  ; prints "saving", => :saved
(save {:valid false}) ; => :rejected
```
Each branch of `if` is exactly one form. `do` groups the `println` and the return value into one form.

Learn more: [Control Flow](/documentation/language/control-flow/#statements-do)
{% </solution> %}

## when, if-not, and when-not

`when` is an `if` with only a then-branch. Its body can hold several forms without `do`, and it returns `nil` when the test is falsy. `if-not` and `when-not` flip the test.

```phel
(when (pos? 5) :positive)  ; => :positive
(when (pos? -5) :positive) ; => nil
```

{% <question difficulty="easy" kind="write"> %}
Define `check-balance` with `when`. For a negative balance, it prints a warning and returns `:warned`. Otherwise it returns `nil`.
<!-- phel-test: skip -->
```phel
(check-balance -5) ; prints "Warning: negative balance", => :warned
(check-balance 5)  ; => nil
```
{% </question> %}
{% <solution> %}
```phel
(defn check-balance [balance]
  (when (neg? balance)
    (println "Warning: negative balance")
    :warned))

(check-balance -5) ; prints "Warning: negative balance", => :warned
(check-balance 5)  ; => nil
```
Reach for `when` when you only care about one side of the decision. You do not need `do`: `when` runs every form in its body and returns the last one.

Learn more: [Control Flow](/documentation/language/control-flow/#when-if-not-and-binding-conditionals)
{% </solution> %}

{% <question difficulty="easy" kind="fill"> %}
Fill in the blanks. The button says `"Checkout"` when the cart has items, and `"Your cart is empty"` when it does not.
<!-- phel-test: skip -->
```phel
(defn button-label [cart]
  (___ (empty? cart)
    "Checkout"
    ___))

(button-label [:book]) ; => "Checkout"
(button-label [])      ; => "Your cart is empty"
```
{% </question> %}
{% <solution> %}
```phel
(defn button-label [cart]
  (if-not (empty? cart)
    "Checkout"
    "Your cart is empty"))

(button-label [:book]) ; => "Checkout"
(button-label [])      ; => "Your cart is empty"
```
`if-not` runs the first branch when the test is falsy. `(if-not x a b)` is the same as `(if (not x) a b)`.
{% </solution> %}

## cond and case

`cond` takes pairs of test and result, and returns the result of the first truthy test. Use `:else` as the last test for a default. `case` compares one value against constants, like PHP's `match`.

```phel
(defn ticket-price [age]
  (cond
    (< age 3)  0
    (< age 12) 5
    :else      10))

(ticket-price 8) ; => 5

(case :get
  :get  "read"
  :post "write"
  "other") ; => "read"
```

{% <question difficulty="medium" kind="write"> %}
Define `describe-temp` for these ranges of degrees.
<!-- phel-test: skip -->
```phel
(describe-temp 35)  ; => "hot"
(describe-temp 20)  ; => "nice"
(describe-temp 5)   ; => "cold"
(describe-temp -10) ; => "freezing"
```
{% </question> %}
{% <hint> %}
Tests run from top to bottom and the first truthy one wins. Start from the highest range.
{% </hint> %}
{% <solution> %}
```phel
(defn describe-temp [degrees]
  (cond
    (>= degrees 30) "hot"
    (>= degrees 15) "nice"
    (>= degrees 0)  "cold"
    :else           "freezing"))

(describe-temp 35)  ; => "hot"
(describe-temp -10) ; => "freezing"
```
`cond` fits here because you test ranges, not exact values. `:else` is a keyword, and keywords are truthy, so it always matches.

Learn more: [Control Flow](/documentation/language/control-flow/#cond)
{% </solution> %}

{% <question difficulty="medium" kind="write"> %}
Define `day-type` with `case`. Saturday and Sunday share one result.
<!-- phel-test: skip -->
```phel
(day-type :saturday) ; => "weekend"
(day-type :sunday)   ; => "weekend"
(day-type :friday)   ; => "almost there"
(day-type :monday)   ; => "weekday"
```
{% </question> %}
{% <hint> %}
Put several constants in a list to give them one shared result. A last lone form is the default.
{% </hint> %}
{% <solution> %}
```phel
(defn day-type [day]
  (case day
    (:saturday :sunday) "weekend"
    :friday             "almost there"
    "weekday"))

(day-type :sunday) ; => "weekend"
(day-type :monday) ; => "weekday"
```
`case` is cleaner than `cond` when you compare one value against fixed constants. Without the default, an unknown day would return `nil`.

Learn more: [Control Flow](/documentation/language/control-flow/#case)
{% </solution> %}

{% <question difficulty="medium" kind="fix"> %}
A score of 95 should be `"excellent"`, but this function says `"pass"`. Find the bug and fix it.
<!-- phel-test: skip -->
```phel
(defn grade [score]
  (cond
    (>= score 50) "pass"
    (>= score 90) "excellent"
    :else         "fail"))

(grade 95) ; => "pass"
```
{% </question> %}
{% <hint> %}
`cond` stops at the first truthy test. Is `(>= 95 50)` truthy?
{% </hint> %}
{% <solution> %}
```phel
(defn grade [score]
  (cond
    (>= score 90) "excellent"
    (>= score 50) "pass"
    :else         "fail"))

(grade 95) ; => "excellent"
(grade 60) ; => "pass"
(grade 10) ; => "fail"
```
Order matters in `cond`. Put the narrowest test first, or a wider test catches the value before the narrow one gets a chance.
{% </solution> %}

## and and or

`and` returns the first falsy value, or the last value when all are truthy. `or` returns the first truthy value, or the last value when none is. They return values, not only `true` or `false`, and they stop as soon as they know the answer.

```phel
(and true false) ; => false
(or nil "guest") ; => "guest"
```

{% <question difficulty="medium" kind="predict"> %}
What does each line return?
```phel
(and 1 2 3)
(and 1 nil 3)
(or nil "default")
(or nil false)
```
{% </question> %}
{% <hint> %}
Walk through the arguments from left to right. Where does each one stop?
{% </hint> %}
{% <solution> %}
```phel
(and 1 2 3)        ; => 3
(and 1 nil 3)      ; => nil
(or nil "default") ; => "default"
(or nil false)     ; => false
```
`and` stops at `nil` and returns it. `or` returns the first truthy value, or the last value (`false`) when nothing is truthy.

Learn more: [Logical operations](/documentation/language/basic-types/#logical-operations)
{% </solution> %}

{% <question difficulty="medium" kind="refactor"> %}
This function returns the port from a config map, or `8080` when there is none. It looks up `:port` twice. Rewrite it with `or`.
```phel
(defn port [config]
  (if (nil? (get config :port))
    8080
    (get config :port)))

(port {:port 3000}) ; => 3000
(port {})           ; => 8080
```
{% </question> %}
{% <hint> %}
A missing key gives `nil`, and `nil` is falsy.
{% </hint> %}
{% <solution> %}
```phel
(defn port [config]
  (or (:port config) 8080))

(port {:port 3000}) ; => 3000
(port {})           ; => 8080
```
`(or value default)` is the common way to give a missing value a default. It is like PHP's `$config['port'] ?? 8080`.
{% </solution> %}

## if-let and when-let

`if-let` looks up a value, binds it to a name, and branches on it in one step. The name exists only in the then-branch. `when-let` does the same with no else-branch.

```phel
(if-let [name (get {1 "Alice"} 1)]
  (str "Found " name)
  "No user") ; => "Found Alice"
```

{% <question difficulty="medium" kind="write"> %}
Define `welcome` with `if-let`. It greets a user by name when the map has a `:name`.
<!-- phel-test: skip -->
```phel
(welcome {:name "Ada"}) ; => "Welcome, Ada!"
(welcome {})            ; => "Welcome, stranger!"
```
{% </question> %}
{% <hint> %}
The binding vector holds one name and one lookup: `[name ...]`.
{% </hint> %}
{% <solution> %}
```phel
(defn welcome [user]
  (if-let [name (:name user)]
    (str "Welcome, " name "!")
    "Welcome, stranger!"))

(welcome {:name "Ada"}) ; => "Welcome, Ada!"
(welcome {})            ; => "Welcome, stranger!"
```
Without `if-let` you would look up `:name` twice: once to test it, once to use it.

Learn more: [Control Flow](/documentation/language/control-flow/#when-if-not-and-binding-conditionals)
{% </solution> %}

{% <question difficulty="hard" kind="write"> %}
Define `shipping-note` for an order map. Orders of 100 or more ship free. Smaller orders show how much is missing. An order without a `:country` cannot ship.
<!-- phel-test: skip -->
```phel
(shipping-note {:total 120 :country "ES"}) ; => "Free shipping to ES"
(shipping-note {:total 40 :country "ES"})  ; => "Add 60 more for free shipping to ES"
(shipping-note {:total 40})                ; => "Missing country"
```
{% </question> %}
{% <hint> %}
Check the country first with `if-let`. Inside its then-branch, decide on the total.
{% </hint> %}
{% <solution> %}
```phel
(defn shipping-note [order]
  (if-let [country (:country order)]
    (let [total (:total order)]
      (if (>= total 100)
        (str "Free shipping to " country)
        (str "Add " (- 100 total) " more for free shipping to " country)))
    "Missing country"))

(shipping-note {:total 120 :country "ES"}) ; => "Free shipping to ES"
(shipping-note {:total 40 :country "ES"})  ; => "Add 60 more for free shipping to ES"
(shipping-note {:total 40})                ; => "Missing country"
```
Handle the missing value first, then the normal case. Nesting one decision inside another is fine when each level answers one question.
{% </solution> %}
