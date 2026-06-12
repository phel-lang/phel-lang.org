+++
title = "Control flow"
weight = 4
description = "Branch, loop, and build collections with if, cond, case, loop/recur, for, and thread macros like cond->"
aliases = ["/documentation/control-flow"]
+++

Everything that decides what runs next: conditionals (`if`, `cond`, `case`), iteration (`loop`/`recur`, `foreach`, `for`), and conditional threading.

## If

<!-- phel-test: skip -->
```phel
(if test then else?)
```

Evaluates _test_. If truthy, returns _then_; if falsy, returns _else_ (or `nil`).

Only `false` and `nil` are falsy. Everything else truthy. PHP equivalent: `test !== null && test !== false`.

```phel
;; Basic if examples
(if true 10) ; Evaluates to 10
(if false 10) ; Evaluates to nil
(if true (print 1) (print 2)) ; Prints 1 but not 2

;; Important: Only false and nil are falsy!
(if 0 (print 1) (print 2)) ; Prints 1 (0 is truthy!)
(if nil (print 1) (print 2)) ; Prints 2 (nil is falsy)
(if [] (print 1) (print 2)) ; Prints 1 (empty vector is truthy!)

;; Practical examples
(defn greet [name]
  (if name
    (str "Hello, " name)
    "Hello, stranger"))

(greet "Alice")  ; => "Hello, Alice"
(greet nil)      ; => "Hello, stranger"

;; Using if for validation
(defn divide [a b]
  (if (= b 0)
    nil
    (/ a b)))

(divide 10 2)  ; => 5
(divide 10 0)  ; => nil
```

## Case

<!-- phel-test: skip -->
```phel
(case test & pairs)
```

Evaluates _test_, matches against first item of each pair. Returns the matching second item, or `nil` if no match.

```phel
;; Basic case examples
(case (+ 7 5)
  3 :small
  12 :big) ; Evaluates to :big

(case (+ 7 5)
  3 :small
  15 :big) ; Evaluates to nil (no match)

(case (+ 7 5)) ; Evaluates to nil (no pairs)

;; Practical examples
(defn http-status-message [code]
  (case code
    200 "OK"
    201 "Created"
    400 "Bad Request"
    404 "Not Found"
    500 "Internal Server Error"))

(http-status-message 200)  ; => "OK"
(http-status-message 404)  ; => "Not Found"
(http-status-message 999)  ; => nil

;; Using case with keywords
(defn animal-sound [animal]
  (case animal
    :dog "Woof!"
    :cat "Meow!"
    :cow "Moo!"
    :duck "Quack!"))

(animal-sound :dog)   ; => "Woof!"
(animal-sound :fish)  ; => nil
```

{% php_note() %}
Like PHP `switch`, more concise:

```php
// PHP
switch ($value) {
    case 3:
        $result = 'small';
        break;
    case 12:
        $result = 'big';
        break;
    default:
        $result = null;
}

// Phel
(case value
  3 :small
  12 :big)
```

No `break`, no fall-through.
{% end %}

## Cond

<!-- phel-test: skip -->
```phel
(cond & pairs)
```

Walks pairs. First pair whose test is truthy: returns its second expression. No match returns `nil`.

```phel
;; Basic cond examples
(cond
  (neg? 5) :negative
  (pos? 5) :positive)  ; Evaluates to :positive

(cond
  (neg? 5) :negative
  (neg? 3) :negative) ; Evaluates to nil (no match)

(cond) ; Evaluates to nil (no pairs)

;; Practical examples
(defn classify-number [n]
  (cond
    (< n 0) "negative"
    (= n 0) "zero"
    (> n 0) "positive"))

(classify-number -5)  ; => "negative"
(classify-number 0)   ; => "zero"
(classify-number 10)  ; => "positive"

;; Using cond for complex conditions
(defn ticket-price [age]
  (cond
    (< age 3) 0          ; Free for toddlers
    (< age 12) 5         ; Child price
    (< age 65) 10        ; Adult price
    :else 7))            ; Senior discount

(ticket-price 2)   ; => 0
(ticket-price 10)  ; => 5
(ticket-price 30)  ; => 10
(ticket-price 70)  ; => 7

;; Combining multiple conditions
(defn water-state [temp]
  (cond
    (<= temp 0) :ice
    (and (> temp 0) (< temp 100)) :liquid
    (>= temp 100) :steam))

(water-state -5)   ; => :ice
(water-state 25)   ; => :liquid
(water-state 105)  ; => :steam
```

{% php_note() %}
Like a chain of `if`/`elseif`:

```php
// PHP
if ($value < 0) {
    $result = 'negative';
} elseif ($value > 0) {
    $result = 'positive';
} else {
    $result = null;
}

// Phel
(cond
  (neg? value) :negative
  (pos? value) :positive)
```

Cleaner than nested `if`. Use `:else` as a default.
{% end %}

For destructuring-by-shape (matching the structure of vectors and maps, not just running predicates), see [Match](#match) below.

## Match

`match` lives in `phel.match` and dispatches by _shape_: it destructures the subject and binds names in one step. It expands to nested `cond` + `let` at compile time, so there is no runtime overhead beyond the checks you write.

```phel
(ns my-app.main (:require phel.match :refer [match]))

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

The subject is a vector of one or more targets; every pattern is a vector whose length must equal the target count.

### Pattern kinds

| Pattern | Matches |
| --- | --- |
| `42`, `:key`, `"s"` | literal equality |
| `_` | wildcard (matches anything, binds nothing) |
| `sym` | binds the target to `sym` |
| `[a b c]` | a vector of exactly 3 elements, recursively matched |
| `[head & tail]` | a vector, binding the remaining slice to `tail` |
| `{:k sym}` | a map with key `:k`, binding its value to `sym` |
| `(pat :as name)` | matches `pat`, also binds the whole subject to `name` |
| `(pat :guard pred)` | matches `pat`, then requires `(pred subject)` truthy |
| `(:or alt1 alt2 ...)` | any alternative matches (literal/structural only, no bindings) |

### Guards

A `:guard` adds a runtime predicate on top of a structural pattern:

```phel
(ns my-app.main (:require phel.match :refer [match]))

(defn sign [n]
  (match [n]
    [(x :guard neg?)] "negative"
    [(x :guard pos?)] "positive"
    :else             "zero"))

(sign -3) ; => "negative"
(sign 7)  ; => "positive"
(sign 0)  ; => "zero"
```

### Rest binding

End a vector pattern with `& rest` to capture the remaining slice:

```phel
(ns my-app.main (:require phel.match :refer [match]))

(match [[10 20 30]]
  [[head & tail]] (str head ":" (count tail))) ; => "10:2"
```

### Pitfalls

* Each pattern vector's length must equal the target count.
* `:else` must be the final clause.
* `:or` alternatives may not introduce bindings; they are literal or structural only.
* Nested patterns bind left-to-right; a later binding shadows an earlier one with the same name.
* A `:guard` predicate runs against the raw value. Numeric predicates coerce non-numbers, so `(pos? [1 2])` is truthy. Put literal and structural patterns _before_ an open numeric guard.

See also [`phel.schema`](/documentation/reference/api/schema/) for shapes reusable across validation and matching, and `case`/`cond`/`condp` above for simpler dispatch without destructuring. Full API: [match reference](/documentation/reference/api/match/).

## Loop

<!-- phel-test: skip -->
```phel
(loop [bindings*] expr*)
```

Creates a lexical context with bindings and a recursion point at the top.

<!-- phel-test: skip -->
```phel
(recur expr*)
```

Evaluates expressions and rebinds at the recursion point. Recursion point is a `fn` or `loop`. Arities must match exactly.

`recur` compiles to a PHP `while` loop, avoiding _Maximum function nesting level_ errors.

```phel
;; Basic loop example - sum numbers from 1 to 10
(loop [sum 0
       cnt 10]
  (if (= cnt 0)
    sum
    (recur (+ cnt sum) (dec cnt))))  ; => 55

;; Recursion in a function
(defn factorial [n]
  (loop [acc 1
         n n]
    (if (<= n 1)
      acc
      (recur (* acc n) (dec n)))))

(factorial 5)  ; => 120

;; Finding an element in a vector
(defn find-index [pred coll]
  (loop [idx 0
         items coll]
    (cond
      (empty? items) nil
      (pred (first items)) idx
      :else (recur (inc idx) (rest items)))))

(find-index even? [1 3 5 8 9])  ; => 3
(find-index neg? [1 2 3])       ; => nil

;; Building a result with loop
(defn reverse-vec [v]
  (loop [result []
         remaining v]
    (if (empty? remaining)
      result
      (recur (conj result (last remaining))
             (pop remaining)))))

(reverse-vec [1 2 3 4])  ; => [4 3 2 1]
```

{% php_note() %}
TCO not in PHP natively:

```php
// PHP - recursive functions can cause stack overflow
function countdown($n) {
    if ($n === 0) return 0;
    return countdown($n - 1);  // Stack overflow for large n!
}

// Phel - recur compiles to a while loop (safe for any n)
(loop [n 1000000]
  (if (= n 0)
    0
    (recur (dec n))))  ; No stack overflow!
```

Critical for FP patterns in PHP.
{% end %}

## Foreach

<!-- phel-test: skip -->
```phel
(foreach [value valueExpr] expr*)
(foreach [key value valueExpr] expr*)
```

Iterate any PHP data structure for side-effects. Always returns `nil`. Prefer `loop` when possible.

```phel
(foreach [v [1 2 3]]
  (print v)) ; Prints 1, 2 and 3

(foreach [k v {"a" 1 "b" 2}]
  (print k)
  (print v)) ; Prints "a", 1, "b" and 2
```

{% php_note() %}
Mirrors PHP `foreach`:

```php
// PHP
foreach ([1, 2, 3] as $v) {
    print($v);
}

foreach (["a" => 1, "b" => 2] as $k => $v) {
    print($k);
    print($v);
}

// Phel
(foreach [v [1 2 3]]
  (print v))

(foreach [k v {"a" 1 "b" 2}]
  (print k)
  (print v))
```

**Note:** Use `for` or `loop` to return values. `foreach` is side-effects only.
{% end %}

## For

`for` builds collections from existing ones. Combines `foreach`, `let`, `if`, `reduce`.

<!-- phel-test: skip -->
```phel
(for head body+)
```

`head` is a vector of bindings and modifiers. A binding is `binding :verb expr` where `binding` works as in `let` and `:verb` is one of:

* `:range` loop over a range
* `:in` values of a collection
* `:keys` keys/indexes of a collection
* `:pairs` key-value pairs

Modifiers (form `:modifier argument`):

* `:while` break when expression is falsy
* `:let` additional bindings
* `:when` evaluate body only when condition is true
* `:reduce [acc init]` reduce instead of returning a list. `acc` starts at `init`. Unlike `when` inside `reduce`, `:when` works cleanly with `:reduce`

```phel
(for [x :range [0 3]] x) ; Evaluates to [0 1 2]
(for [x :range [3 0 -1]] x) ; Evaluates to [3 2 1]

(for [x :in [1 2 3]] (inc x)) ; Evaluates to [2 3 4]
(for [x :in {:a 1 :b 2 :c 3}] x) ; Evaluates to [1 2 3]

(for [x :keys [1 2 3]] x) ; Evaluates to [0 1 2]
(for [x :keys {:a 1 :b 2 :c 3}] x) ; Evaluates to [:a :b :c]

(for [[k v] :pairs {:a 1 :b 2 :c 3}] [v k]) ; Evaluates to [[1 :a] [2 :b] [3 :c]]
(for [[k v] :pairs [1 2 3]] [k v]) ; Evaluates to [[0 1] [1 2] [2 3]]
(for [[k v] :pairs {:a 1 :b 2 :c 3} :reduce [m {}]]
  (assoc m k (inc v))) ; Evaluates to {:a 2, :b 3, :c 4}
(for [[k v] :pairs {:a 1 :b 2 :c 3} :reduce [m {}] :let [x (inc v)]]
  (assoc m k x)) ; Evaluates to {:a 2, :b 3, :c 4}
(for [[k v] :pairs {:a 1 :b 2 :c 3} :when (contains-value? [:a :c] k) :reduce [acc {}]]
    (assoc acc k v)) ; Evaluates to {:a 1, :c 3}

(for [x :in [2 2 2 3 3 4 5 6 6] :while (even? x)] x) ; Evaluates to [2 2 2]
(for [x :in [2 2 2 3 3 4 5 6 6] :when (even? x)] x) ; Evaluates to [2 2 2 4 6 6]

(for [x :in [1 2 3] :let [y (inc x)]] [x y]) ; Evaluates to [[1 2] [2 3] [3 4]]

(for [x :range [0 4] y :range [0 x]] [x y]) ; Evaluates to [[1 0] [2 0] [2 1] [3 0] [3 1] [3 2]]
```

{% php_note() %}
List comprehension, not PHP's `for`:

```php
// PHP - manual array building
$result = [];
foreach (range(1, 3) as $x) {
    $result[] = $x + 1;
}

// Phel - declarative comprehension
(for [x :in [1 2 3]] (inc x))  ; [2 3 4]
```

Combines iteration, filtering (`:when`), early termination (`:while`), reduction (`:reduce`), nested loops.
{% end %}

{% clojure_note() %}
Like Clojure `for` (`:let`, `:when`, nesting). `:reduce` is a Phel extension.
{% end %}

## Do

<!-- phel-test: skip -->
```phel
(do expr*)
```

Evaluates expressions in order. Returns the last value, or `nil` if empty.

```phel
(do 1 2 3 4) ; Evaluates to 4
(do (print 1) (print 2) (print 3)) ; Print 1, 2, and 3
```

## Dofor

Like `for` but for side-effects. Returns `nil` like `foreach`.

```phel
(dofor [x :in [1 2 3]] (print x)) ; Prints 1, 2, 3, returns nil
(dofor [x :in [2 3 4 5] :when (even? x)] (print x)) ; Prints 2, 4, returns nil
```

## Conditional threading

### cond->

<!-- phel-test: skip -->
```phel
(cond-> expr & clauses)
```

Threads expression through each form whose test is truthy (thread-first). Skips forms with falsy tests.

```phel
(cond-> 1
  true inc
  false (* 42)
  true (* 3))  ; => 6

;; Only applies inc (true) and (* 3) (true), skips (* 42) (false)
;; 1 -> (inc 1) -> 2 -> (* 2 3) -> 6

(defn maybe-transform [data opts]
  (cond-> data
    (:uppercase opts) (phel.string/upper-case)
    (:trim opts)      (phel.string/trim)
    (:prefix opts)    (#(str (:prefix opts) %))))
```

### cond->>

<!-- phel-test: skip -->
```phel
(cond->> expr & clauses)
```

Like `cond->` but threads as last arg (thread-last).

```phel
(cond->> [1 2 3 4 5]
  true (map inc)
  false (filter odd?)
  true (take 3))  ; => @[2 3 4]

;; Only applies (map inc) and (take 3), skips (filter odd?)
```

## Exceptions

<!-- phel-test: skip -->
```phel
(throw expr)
```

Evaluates _expr_ and throws it. Must implement PHP `Throwable`.

## Try, catch, and finally

<!-- phel-test: skip -->
```phel
(try expr* catch-clause* finally-clause?)
```

Evaluates expressions. No exception: returns last value. Matching _catch-clause_: returns its value. No match: exception propagates. _finally-clause_ runs before return.

```phel
(try) ; Evaluates to nil

(try
  (throw (Exception.))
  (catch Exception e "error")) ; Evaluates to "error"

(try
  (+ 1 1)
  (finally (print "test"))) ; Evaluates to 2 and prints "test"

(try
  (throw (Exception.))
  (catch Exception e "error")
  (finally (print "test"))) ; Evaluates to "error" and prints "test"
```

For catching PHP exceptions, structured errors with `ex-info`/`ex-data`, exception chaining, and guidance on when to throw, see [Error handling](/documentation/language/error-handling/).

## Next steps

- [Match reference](/documentation/reference/api/match/) - all `match` pattern kinds and the full API
- [Error handling](/documentation/language/error-handling/) - throw, catch, and structured errors in depth
- [Functions and recursion](/documentation/language/functions-and-recursion/) - `loop`/`recur` and tail calls
- [Cheat sheet](/documentation/reference/cheat-sheet/) - keep it open while coding
