+++
title = "Control Flow"
weight = 4
description = "Branch, loop, and build collections with if, cond, case, match, loop/recur, for, and the threading macros"
aliases = ["/documentation/control-flow"]

[extra]
difficulty = "beginner"
+++

After this page you can branch with `if`, `when`, `cond`, `case`, and `match`, repeat work with `for` and `loop`, and write pipelines with the threading macros.

Every form on this page is an expression: it returns a value. There are no statements.

## If

<!-- phel-test: skip -->
```phel
(if test then else?)
```

`if` evaluates `test`. When it is truthy, `if` returns `then`. Otherwise it returns `else`, or `nil` when there is no `else`:

```phel
(defn greet [name]
  (if name
    (str "Hello, " name)
    "Hello, stranger"))

(greet "Alice") ; => "Hello, Alice"
(greet nil)     ; => "Hello, stranger"
(if false 10)   ; => nil
```

Only `false` and `nil` are falsy. `0`, `""`, and `[]` are truthy. See [Truthiness](/documentation/language/basic-types/#truthiness).

## Do {#statements-do}

Each branch of `if` is one form. `do` groups several forms and returns the value of the last one:

```phel
(if true
  (do (println "saving")
      :saved)
  :skipped) ; prints "saving", => :saved
```

## When, if-not, and binding conditionals

`when` is `if` with no `else`. Its body can hold several forms, so you do not need `do`. It returns `nil` when the test is falsy. `when-not` and `if-not` flip the test:

```phel
(when (pos? 5) :positive)              ; => :positive
(when (pos? -5) :positive)             ; => nil
(if-not (empty? []) :has-items :empty) ; => :empty
```

`if-let` and `when-let` bind a value and branch on it in one step. The name exists only in the truthy branch. Use them when a lookup can miss:

```phel
(def users {1 "Alice" 2 "Bob"})

(if-let [name (get users 1)]
  (str "Found " name)
  "No user")        ; => "Found Alice"

(when-let [name (get users 9)]
  (str "Hi " name)) ; => nil
```

`if-some` and `when-some` work the same way but only treat `nil` as missing, so a `false` value still takes the first branch.

## Cond

`cond` takes test and result pairs. It returns the result of the first truthy test, or `nil` when none matches. Use `:else` as the last test for a default:

```phel
(defn ticket-price [age]
  (cond
    (< age 3)  0
    (< age 12) 5
    (< age 65) 10
    :else      7))

(ticket-price 2)  ; => 0
(ticket-price 30) ; => 10
(ticket-price 70) ; => 7
```

{% php_note() %}
`cond` replaces a chain of `if` / `elseif` / `else`. Each branch returns a value, so there is no `$result` variable to assign.
{% end %}

## Case

`case` compares a value against constants. It returns the result for the first match. A last lone form is the default. Without a default, no match returns `nil`:

```phel
(defn status-text [code]
  (case code
    200 "OK"
    404 "Not Found"
    "Unknown"))

(status-text 200) ; => "OK"
(status-text 999) ; => "Unknown"
```

Group constants in a list to share one result: `(case n (1 2 3) :small :big)`.

{% php_note() %}
`case` is like `switch` or `match` in PHP, with no `break` and no fall-through. The test values must be literals, not expressions.
{% end %}

## Condp

`condp` is `cond` with a shared predicate. `(condp pred expr a x b y default)` tests `(pred a expr)`, then `(pred b expr)`, and so on. A last lone form is the default:

```phel
(defn size [n]
  (condp < n
    100 :large
    10  :medium
    :small))

(size 500) ; => :large
(size 5)   ; => :small
```

Without a default, no match throws. Use `case` to compare against constants, and `condp` when the comparison is a function.

## Match

`match` from `phel.match` dispatches on the *shape* of a value. It checks the structure and binds names in one step:

```phel
(ns my-app.main
  (:require phel.match :refer [match]))

(defn describe [x]
  (match [x]
    [0]                   "zero"
    [[a b]]               (str "pair " a " / " b)
    [{:type :err :msg m}] (str "error: " m)
    [(n :guard pos?)]     "positive"
    :else                 "other"))

(describe 0)                        ; => "zero"
(describe [1 2])                    ; => "pair 1 / 2"
(describe {:type :err :msg "boom"}) ; => "error: boom"
(describe 5)                        ; => "positive"
```

The subject is a vector of one or more values. Each pattern is a vector of the same length. `:else` must be the last clause.

| Pattern | Matches |
| --- | --- |
| `42`, `:key`, `"s"` | an equal value |
| `_` | anything, binds nothing |
| `sym` | anything, binds it to `sym` |
| `[a b]` | a vector of exactly 2 elements |
| `[head & tail]` | a vector, binding the rest to `tail` |
| `{:k sym}` | a map with key `:k`, binding its value |
| `(pat :as name)` | `pat`, and binds the whole value to `name` |
| `(pat :guard pred)` | `pat`, when `(pred value)` is truthy |
| `(:or a b)` | any of the alternatives (no bindings inside) |

A `:guard` runs on the raw value, and numeric predicates accept non-numbers: `(pos? [1 2])` is truthy. Put literal and structural patterns before an open numeric guard. Full API: [match reference](/documentation/reference/api/match/).

## Loop

`loop` binds names like `let` and marks a point that `recur` can jump back to with new values:

```phel
(loop [i 0
       acc []]
  (if (< i 3)
    (recur (inc i) (conj acc i))
    acc)) ; => [0 1 2]
```

`recur` must be in tail position, and it must pass one value per binding. It compiles to a PHP `while` loop, so it never grows the call stack. `recur` also works directly in a function body: see [Recursion](/documentation/language/functions-and-recursion/#recursion).

Most loops are shorter with `for`, `map`, `filter`, or `reduce`. Use `loop` when the next step depends on state that those do not carry.

## For

`for` builds a vector from one or more collections. It combines iteration, filtering, and local bindings:

```phel
(for [x :in [1 2 3 4 5 6]
      :when (even? x)]
  (* x x)) ; => [4 16 36]
```

Each binding is `name :verb expr`. The name can destructure, like in `let`:

| Verb | Iterates over | Example | Result |
|------|---------------|---------|--------|
| `:in` | values | `(for [x :in [1 2]] x)` | `[1 2]` |
| `:range` | a `[start end step?]` range | `(for [x :range [0 3]] x)` | `[0 1 2]` |
| `:keys` | keys or indexes | `(for [k :keys {:a 1 :b 2}] k)` | `[:a :b]` |
| `:pairs` | `[key value]` pairs | `(for [[k v] :pairs {:a 1}] [v k])` | `[[1 :a]]` |

Modifiers go after a binding:

| Modifier | Effect |
|----------|--------|
| `:when test` | skip items where `test` is falsy |
| `:while test` | stop at the first item where `test` is falsy |
| `:let [bindings]` | bind more names |
| `:reduce [acc init]` | fold into `acc` instead of building a vector |

Several bindings nest, like nested loops:

```phel
(for [x :in [1 2]
      y :in [:a :b]]
  [x y]) ; => [[1 :a] [1 :b] [2 :a] [2 :b]]

(for [[k v] :pairs {:a 1 :b 2 :c 3}
      :reduce [m {}]]
  (assoc m k (inc v))) ; => {:a 2 :b 3 :c 4}
```

{% clojure_note() %}
Like Clojure's `for`, but it returns a vector, not a lazy sequence, and each binding names its verb (`:in`, `:range`, ...). `:reduce` is a Phel extension.
{% end %}

## Side effects: foreach and dofor {#foreach}

`for` is for building values. For side effects such as printing or writing to a database, use `foreach` or `dofor`. Both return `nil`:

```phel
(foreach [v [1 2 3]]
  (println v)) ; prints 1, 2, 3

(foreach [k v {"a" 1 "b" 2}]
  (println k v)) ; prints a 1, b 2

(dofor [x :in [1 2 3 4] :when (even? x)]
  (println x)) ; prints 2, 4
```

`foreach` iterates any PHP iterable, like PHP's `foreach`. `dofor` takes the same bindings and modifiers as `for`.

## Threading

`->` (thread-first) passes a value as the first argument of each form in turn. `->>` (thread-last) passes it as the last. Read them top to bottom, like a pipeline:

```phel
(-> 5 (+ 3) (* 2)) ; => 16, same as (* (+ 5 3) 2)

(->> [1 2 3 4]
     (map inc)
     (filter even?)) ; => (2 4)
```

Use `->` for maps and objects, where the subject goes first. Use `->>` for sequence functions, where the collection goes last.

`some->` and `some->>` stop at the first `nil`:

```phel
(some-> {:user {:name "Ada"}} :user :name phel.string/upper-case) ; => "ADA"
(some-> {:user nil} :user :name phel.string/upper-case)           ; => nil
```

`cond->` and `cond->>` apply each step only when its test is truthy:

```phel
(defn build-user [name admin?]
  (cond-> {:name name}
    admin?      (assoc :role :admin)
    (= name "") (assoc :error "empty name")))

(build-user "Ada" true)  ; => {:name "Ada" :role :admin}
(build-user "Bob" false) ; => {:name "Bob"}
```

## Errors {#try-catch-and-finally}

`throw` raises any PHP `Throwable`. `try` catches it by type:

```phel
(try
  (throw (Exception. "boom"))
  (catch \Exception e "recovered")) ; => "recovered"
```

`finally`, structured errors with `ex-info`, and when to throw at all: [Error handling](/documentation/language/error-handling/).
