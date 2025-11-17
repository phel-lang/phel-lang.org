+++
title = "Functions and Recursion"
weight = 7
+++

## Anonymous Function (fn)

```phel
(fn [params*] expr*)

(fn
  ([params1*] expr1*)
  ([params2*] expr2*)
  ...)
```

Defines a function. A function consists of a list of parameters and a list of expression. The value of the last expression is returned as the result of the function. All other expression are only evaluated for side effects. If no expression is given, the function returns `nil`.

Functions can define multiple arities. When calling such a function, the clause matching the number of provided arguments is chosen. Variadic clauses are supported for at most one arity and must be the one with the most parameters. If no arity matches, a readable compile-time or runtime error is thrown.

Function also introduces a new lexical scope that is not accessible outside the function.

```phel
(fn []) # Function with no arguments that returns nil
(fn [x] x) # The identity function
(fn [] 1 2 3) # A function that returns 3
(fn [a b] (+ a b)) # A function that returns the sum of a and b
```

Function can also be defined as variadic function with an infinite amount of arguments using the `&` separator.

```phel
(fn [& args] (count args)) # A variadic function that counts the arguments

(fn [a b c &]) # A variadic function with extra arguments ignored

(fn # A multi-arity function
  ([] "hi") 
  ([name] (str "hi " name))
  ([greeting name & rest] (str greeting " " name rest)))
```

There is a shorter form to define an anonymous function. This omits the parameter list and names parameters based on their position.

* `$` is used for a single parameter
* `$1`, `$2`, `$3`, etc are used for multiple parameters
* `$&` is used for the remaining variadic parameters

```phel
|(+ 6 $) # Same as (fn [x] (+ 6 x))
|(+ $1 $2) # Same as (fn [a b] (+ a b))
|(sum $&) # Same as (fn [& xs] (sum xs))
```

{% php_note() %}
The short-form anonymous function syntax `|` is similar to PHP's arrow functions:

```php
// PHP
$add = fn($x) => $x + 6;
array_map(fn($x) => $x * 2, $array);

// Phel
(def add |(+ $ 6))
(map |(* $ 2) array)
```
{% end %}

{% clojure_note() %}
The short-form `|` syntax is inspired by Clojure's `#()` reader macro, but uses different parameter names (`$` instead of `%`).
{% end %}


## Global functions

```phel
(defn name docstring? attributes? [params*] expr*)

(defn name docstring? attributes?
  ([params1*] expr1*)
  ([params2*] expr2*)
  ...)
```

Global functions can be defined using `defn`. Like anonymous functions, they may provide multiple arities. The most specific clause based on the number of arguments is chosen at call time. A single variadic clause is allowed and must declare the maximum number of arguments.

```phel
(defn my-add-function [a b]
  (+ a b))

(defn greet
  ([] "hi")
  ([name] (str "hi " name))
  ([greeting name] (str greeting " " name)))
```

Each global function can take an optional doc comment and attribute map.

```phel
(defn my-add-function
  "adds value a and b"
  [a b]
  (+ a b))
```

### Private functions

Private functions are not exported from the namespace and cannot be accessed from other namespaces. You can create private functions in two ways:

1. Using the `{:private true}` attribute map
2. Using the `defn-` shorthand

```phel
(defn my-private-add-function
  {:private true}
  [a b]
  (+ a b))
  
(defn- my-private-add-function 
  [a b]
  (+ a b))
```

Both approaches are equivalent, but `defn-` provides a more concise syntax for defining private functions.

## Recursion

Similar to `loop`, functions can be made recursive using `recur`. The `recur` special form enables tail-call optimization, preventing stack overflow errors.

```phel
# Recursive factorial (regular recursion - can stack overflow)
(defn factorial [n]
  (if (<= n 1)
    1
    (* n (factorial (dec n)))))

(factorial 5)  # => 120

# Tail-recursive factorial using recur with loop
(defn factorial-recur [n]
  (loop [acc 1
         n n]
    (if (<= n 1)
      acc
      (recur (* acc n) (dec n)))))

(factorial-recur 5)  # => 120

# Recursive sum (can stack overflow on large collections)
(defn sum-recursive [coll]
  (if (empty? coll)
    0
    (+ (first coll) (sum-recursive (rest coll)))))

(sum-recursive [1 2 3 4 5])  # => 15

# Tail-recursive sum using recur (safe for large collections)
(defn sum-recur [coll]
  (loop [acc 0
         remaining coll]
    (if (empty? remaining)
      acc
      (recur (+ acc (first remaining)) (rest remaining)))))

(sum-recur [1 2 3 4 5])  # => 15

# Using recur directly in function (also tail-call optimized)
(defn countdown [n]
  (if (<= n 0)
    "Done!"
    (do
      (println n)
      (recur (dec n)))))

# (countdown 5)  # Prints: 5, 4, 3, 2, 1, then returns "Done!"
```

{% php_note() %}
`recur` is compiled to a PHP `while` loop, preventing "Maximum function nesting level" errors that would occur with regular recursive calls in PHP.

```php
// PHP - This will cause stack overflow for large n
function factorial($n) {
    if ($n <= 1) return 1;
    return $n * factorial($n - 1);  // Stack overflow for large n!
}

// Phel with recur - This works for any size n
(defn factorial-recur [n]
  (loop [acc 1
         n n]
    (if (<= n 1)
      acc
      (recur (* acc n) (dec n)))))
```

**Key difference:** Regular recursion builds up a call stack, while `recur` reuses the same stack frame (tail-call optimization).
{% end %}

{% clojure_note() %}
`recur` works exactly like Clojure's `recur`—it provides tail-call optimization by compiling to a loop.
{% end %}

## Apply functions

```phel
(apply f expr*)
```
Calls the function with the given arguments. The last argument must be a list of values, which are passed as separate arguments, rather than a single list. Apply returns the result of the calling function.

```phel
(apply + [1 2 3]) # Evaluates to 6
(apply + 1 2 [3]) # Evaluates to 6
(apply + 1 2 3) # BAD! Last element must be a list
```

## Passing by reference

Sometimes it is required that a variable should pass to a function by reference. This can be done by applying the `:reference` metadata to the symbol.

```phel
(fn [^:reference my-arr]
  (php/apush my-arr 10))
```

Support for references is very limited in Phel. Currently, it only works for function arguments (except destructuring).

{% php_note() %}
This is equivalent to PHP's `&` reference operator:

```php
// PHP
function addToArray(&$arr) {
    $arr[] = 10;
}

// Phel
(defn add-to-array [^:reference arr]
  (php/apush arr 10))
```

**Note:** Use references sparingly. Phel's immutable data structures are usually a better choice than mutating PHP arrays.
{% end %}
