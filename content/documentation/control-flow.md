+++
title = "Control flow"
weight = 5
+++

## If

```phel
(if test then else?)
```

A control flow structure. First evaluates _test_. If _test_ evaluates to `true`, only the _then_ form is evaluated and the result is returned. If _test_ evaluates to `false` only the _else_ form is evaluated and the result is returned. If no _else_ form is given, `nil` will be returned.

The _test_ evaluates to `false` if its value is `false` or equal to `nil`. Every other value evaluates to `true`. In sense of PHP this means (`test != null && test !== false`).

```phel
# Basic if examples
(if true 10) # Evaluates to 10
(if false 10) # Evaluates to nil
(if true (print 1) (print 2)) # Prints 1 but not 2

# Important: Only false and nil are falsy!
(if 0 (print 1) (print 2)) # Prints 1 (0 is truthy!)
(if nil (print 1) (print 2)) # Prints 2 (nil is falsy)
(if [] (print 1) (print 2)) # Prints 1 (empty vector is truthy!)

# Practical examples
(defn greet [name]
  (if name
    (str "Hello, " name)
    "Hello, stranger"))

(greet "Alice")  # => "Hello, Alice"
(greet nil)      # => "Hello, stranger"

# Using if for validation
(defn divide [a b]
  (if (= b 0)
    nil
    (/ a b)))

(divide 10 2)  # => 5
(divide 10 0)  # => nil
```

## Case

```phel
(case test & pairs)
```

Evaluates the _test_ expression. Then iterates over each pair. If the result of the test expression matches the first value of the pair, the second expression of the pair is evaluated and returned. If no match is found, returns nil.

```phel
# Basic case examples
(case (+ 7 5)
  3 :small
  12 :big) # Evaluates to :big

(case (+ 7 5)
  3 :small
  15 :big) # Evaluates to nil (no match)

(case (+ 7 5)) # Evaluates to nil (no pairs)

# Practical examples
(defn http-status-message [code]
  (case code
    200 "OK"
    201 "Created"
    400 "Bad Request"
    404 "Not Found"
    500 "Internal Server Error"))

(http-status-message 200)  # => "OK"
(http-status-message 404)  # => "Not Found"
(http-status-message 999)  # => nil

# Using case with keywords
(defn animal-sound [animal]
  (case animal
    :dog "Woof!"
    :cat "Meow!"
    :cow "Moo!"
    :duck "Quack!"))

(animal-sound :dog)   # => "Woof!"
(animal-sound :fish)  # => nil
```

{% php_note() %}
`case` is similar to PHP's `switch` but more concise:

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

No `break` needed—Phel's `case` doesn't fall through.
{% end %}

{% clojure_note() %}
`case` works exactly like Clojure's `case`—evaluates to the matching value without fall-through.
{% end %}

## Cond

```phel
(cond & pairs)
```

Iterates over each pair. If the first expression of the pair evaluates to logical true, the second expression of the pair is evaluated and returned. If no match is found, returns nil.

```phel
# Basic cond examples
(cond
  (neg? 5) :negative
  (pos? 5) :positive)  # Evaluates to :positive

(cond
  (neg? 5) :negative
  (neg? 3) :negative) # Evaluates to nil (no match)

(cond) # Evaluates to nil (no pairs)

# Practical examples
(defn classify-number [n]
  (cond
    (< n 0) "negative"
    (= n 0) "zero"
    (> n 0) "positive"))

(classify-number -5)  # => "negative"
(classify-number 0)   # => "zero"
(classify-number 10)  # => "positive"

# Using cond for complex conditions
(defn ticket-price [age]
  (cond
    (< age 3) 0          # Free for toddlers
    (< age 12) 5         # Child price
    (< age 65) 10        # Adult price
    :else 7))            # Senior discount

(ticket-price 2)   # => 0
(ticket-price 10)  # => 5
(ticket-price 30)  # => 10
(ticket-price 70)  # => 7

# Combining multiple conditions
(defn water-state [temp]
  (cond
    (<= temp 0) :ice
    (and (> temp 0) (< temp 100)) :liquid
    (>= temp 100) :steam))

(water-state -5)   # => :ice
(water-state 25)   # => :liquid
(water-state 105)  # => :steam
```

{% php_note() %}
`cond` is like a chain of `if`/`elseif` in PHP:

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

More elegant for multiple conditions than nested `if` expressions. Use `:else` as the last condition for a default case.
{% end %}

{% clojure_note() %}
`cond` works exactly like Clojure's `cond`—evaluates predicates in order and returns first match.
{% end %}

## Loop

```phel
(loop [bindings*] expr*)
```
Creates a new lexical context with variables defined in bindings and defines a recursion point at the top of the loop.

```phel
(recur expr*)
```
Evaluates the expressions in order and rebinds them to the recursion point. A recursion point can be either a `fn` or a `loop`. The recur expressions must match the arity of the recursion point exactly.

Internally `recur` is implemented as a PHP while loop and therefore prevents the _Maximum function nesting level_ errors.

```phel
# Basic loop example - sum numbers from 1 to 10
(loop [sum 0
       cnt 10]
  (if (= cnt 0)
    sum
    (recur (+ cnt sum) (dec cnt))))  # => 55

# Recursion in a function
(defn factorial [n]
  (loop [acc 1
         n n]
    (if (<= n 1)
      acc
      (recur (* acc n) (dec n)))))

(factorial 5)  # => 120

# Finding an element in a vector
(defn find-index [pred coll]
  (loop [idx 0
         items coll]
    (cond
      (empty? items) nil
      (pred (first items)) idx
      :else (recur (inc idx) (rest items)))))

(find-index even? [1 3 5 8 9])  # => 3
(find-index neg? [1 2 3])       # => nil

# Building a result with loop
(defn reverse-vec [v]
  (loop [result []
         remaining v]
    (if (empty? remaining)
      result
      (recur (conj result (last remaining))
             (pop remaining)))))

(reverse-vec [1 2 3 4])  # => [4 3 2 1]
```

{% php_note() %}
`loop`/`recur` provides tail-call optimization, which PHP doesn't support natively:

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
    (recur (dec n))))  # No stack overflow!
```

This is critical for functional programming patterns in PHP.
{% end %}

{% clojure_note() %}
`loop`/`recur` works exactly like Clojure—provides tail-call optimization by compiling to iterative loops.
{% end %}

## Foreach

```phel
(foreach [value valueExpr] expr*)
(foreach [key value valueExpr] expr*)
```
The `foreach` special form can be used to iterate over all kind of PHP datastructures for side-effects. The return value of `foreach` is always `nil`. The `loop` special form should be preferred of the `foreach` special form whenever possible.

```phel
(foreach [v [1 2 3]]
  (print v)) # Prints 1, 2 and 3

(foreach [k v {"a" 1 "b" 2}]
  (print k)
  (print v)) # Prints "a", 1, "b" and 2
```

{% php_note() %}
`foreach` mirrors PHP's foreach loop syntax:

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

**Note:** Prefer `for` or `loop` when you need to return values. `foreach` is only for side-effects.
{% end %}

## For

A more powerful loop functionality is provided by the `for` loop. The `for` loop is an elegant way to define and create arrays based on existing collections. It combines the functionality of `foreach`, `let`, `if` and `reduce` in one call.

```phel
(for head body+)
```

The `head` of the loop is a vector that contains a
sequence of bindings and modifiers. A binding is a sequence of three
values `binding :verb expr`. Where `binding` is a binding as
in `let` and `:verb` is one of the following keywords:

* `:range` loop over a range, by using the range function.
* `:in` loops over all values of a collection.
* `:keys` loops over all keys/indexes of a collection.
* `:pairs` loops over all key value pairs of a collection.

After each loop binding additional modifiers can be applied. Modifiers
have the form `:modifier argument`. The following modifiers are supported:

* `:while` breaks the loop if the expression is falsy.
* `:let` defines additional bindings.
* `:when` only evaluates the loop body if the condition is true.
* `:reduce [accumulator initial-value]` Instead of returning a list, it reduces the values into `accumulator`. Initially `accumulator` is bound to `initial-value`. Normally with `when` macro inside `reduce` function the accumulator becomes `nil` when the condition is not met. However with `for`, `:when` can be used for conditional logic with `:reduce` without this issue.

```phel
(for [x :range [0 3]] x) # Evaluates to [0 1 2]
(for [x :range [3 0 -1]] x) # Evaluates to [3 2 1]

(for [x :in [1 2 3]] (inc x)) # Evaluates to [2 3 4]
(for [x :in {:a 1 :b 2 :c 3}] x) # Evaluates to [1 2 3]

(for [x :keys [1 2 3]] x) # Evaluates to [0 1 2]
(for [x :keys {:a 1 :b 2 :c 3}] x) # Evaluates to [:a :b :c]

(for [[k v] :pairs {:a 1 :b 2 :c 3}] [v k]) # Evaluates to [[1 :a] [2 :b] [3 :c]]
(for [[k v] :pairs [1 2 3]] [k v]) # Evaluates to [[0 1] [1 2] [2 3]]
(for [[k v] :pairs {:a 1 :b 2 :c 3} :reduce [m {}]]
  (assoc m k (inc v))) # Evaluates to {:a 2 :b 3 :c 4}
(for [[k v] :pairs {:a 1 :b 2 :c 3} :reduce [m {}] :let [x (inc v)]]
  (assoc m k x)) # Evaluates to {:a 2 :b 3 :c 4}
(for [[k v] :pairs {:a 1 :b 2 :c 3} :when (contains-value? [:a :c] k) :reduce [acc {}]]
    (assoc acc k v)) # Evaluates to {:a 1 :c 3}

(for [x :in [2 2 2 3 3 4 5 6 6] :while (even? x)] x) # Evaluates to [2 2 2]
(for [x :in [2 2 2 3 3 4 5 6 6] :when (even? x)] x) # Evaluates to [2 2 2 4 6 6]

(for [x :in [1 2 3] :let [y (inc x)]] [x y]) # Evaluates to [[1 2] [2 3] [3 4]]

(for [x :range [0 4] y :range [0 x]] [x y]) # Evaluates to [[1 0] [2 0] [2 1] [3 0] [3 1] [3 2]]
```

{% php_note() %}
Phel's `for` is a powerful list comprehension, not like PHP's `for` loop:

```php
// PHP - manual array building
$result = [];
foreach (range(1, 3) as $x) {
    $result[] = $x + 1;
}

// Phel - declarative comprehension
(for [x :in [1 2 3]] (inc x))  # [2 3 4]
```

Phel's `for` combines iteration, filtering (`:when`), early termination (`:while`), reduction (`:reduce`), and nested loops in one elegant expression—much more powerful than PHP's `for`/`foreach`.
{% end %}

{% clojure_note() %}
`for` works similarly to Clojure's `for`—list comprehensions with `:let`, `:when`, and nested bindings. The `:reduce` modifier is a Phel extension.
{% end %}

# Do

```phel
(do expr*)
```

Evaluates the expressions in order and returns the value of the last expression. If no expression is given, `nil` is returned.

```phel
(do 1 2 3 4) # Evaluates to 4
(do (print 1) (print 2) (print 3)) # Print 1, 2, and 3
```

# Dofor

```phel
(dofor [x :in [1 2 3]] (print x)) # Prints 1, 2, 3 and returns nil
(dofor [x :in [2 3 4 5] :when (even? x)] (print x)) # Prints 1, 2 and returns nil
```

Iterating over collections for side-effects is also possible with `dofor` which has similar behavior to `for` otherwise but returns `nil` as `foreach` does.

# Exceptions

```phel
(throw expr)
```

The _expr_ is evaluated and thrown, therefore _expr_ must return a value that implements PHP's `Throwable` interface.

## Try, Catch and Finally

```phel
(try expr* catch-clause* finally-clause?)
```

All expressions are evaluated and if no exception is thrown the value of the last expression is returned. If an exception occurs and a matching _catch-clause_ is provided, its expression is evaluated and the value is returned. If no matching _catch-clause_ can be found the exception is propagated out of the function. Before returning normally or abnormally the optionally _finally-clause_ is evaluated.

```phel
(try) # Evaluates to nil

(try
  (throw (php/new \Exception))
  (catch \Exception e "error")) # Evaluates to "error"

(try
  (+ 1 1)
  (finally (print "test"))) # Evaluates to 2 and prints "test"

(try
  (throw (php/new \Exception))
  (catch \Exception e "error")
  (finally (print "test"))) # Evaluates to "error" and prints "test"
```
