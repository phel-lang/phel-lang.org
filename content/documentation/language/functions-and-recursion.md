+++
title = "Functions and Recursion"
weight = 6
aliases = ["/documentation/functions-and-recursion"]
+++

## Anonymous function (fn)

```phel
(fn [params*] expr*)

(fn
  ([params1*] expr1*)
  ([params2*] expr2*)
  ...)
```

Defines a function: parameter list, expression list. Returns last expression's value. Earlier expressions evaluate for side-effects. No expressions returns `nil`.

Functions can have multiple arities. Call dispatches on argument count. At most one variadic clause, which must have the most params. No matching arity raises a clear compile/runtime error.

Functions introduce their own lexical scope.

```phel
(fn []) ; Function with no arguments that returns nil
(fn [x] x) ; The identity function
(fn [] 1 2 3) ; A function that returns 3
(fn [a b] (+ a b)) ; A function that returns the sum of a and b
```

Variadic functions use `&`:

```phel
(fn [& args] (count args)) ; A variadic function that counts the arguments

(fn [a b c &]) ; A variadic function with extra arguments ignored

(fn ; A multi-arity function
  ([] "hi") 
  ([name] (str "hi " name))
  ([greeting name & rest] (str greeting " " name rest)))
```

Shorter form omits the parameter list, naming params by position:

* `%` or `%1` refers to the first argument
* `%2`, `%3`, etc. refer to subsequent arguments
* `%&` captures remaining variadic arguments

```phel
#(+ 6 %)       ; Same as (fn [x] (+ 6 x))
#(+ %1 %2)     ; Same as (fn [a b] (+ a b))
#(apply + %&)  ; Same as (fn [& xs] (apply + xs))

; Using with higher-order functions
(map #(* % 2) [1 2 3])        ; => [2 4 6]
(filter #(> % 3) [1 5 2 8])   ; => [5 8]
```

> **Legacy:** `|(...)` with `$` / `$1` / `$&` still reads. Prefer `#(...)` with `%` (matches Clojure).

{% php_note() %}
`#()` short-form is like PHP arrow functions:

```php
// PHP
$add = fn($x) => $x + 6;
array_map(fn($x) => $x * 2, $array);

// Phel
(def add #(+ % 6))
(map #(* % 2) array)
```
{% end %}


## Global functions

```phel
(defn name docstring? attributes? [params*] expr*)

(defn name docstring? attributes?
  ([params1*] expr1*)
  ([params2*] expr2*)
  ...)
```

`defn` defines a global function. Multiple arities allowed; single variadic clause must declare the max arg count.

```phel
(defn my-add-function [a b]
  (+ a b))

(defn greet
  ([] "hi")
  ([name] (str "hi " name))
  ([greeting name] (str greeting " " name)))
```

Optional doc string and attribute map:

```phel
(defn my-add-function
  "adds value a and b"
  [a b]
  (+ a b))
```

### Private functions

Private functions don't export from the namespace. Two forms:

1. `{:private true}` attribute
2. `defn-` shorthand

```phel
(defn my-private-add-function
  {:private true}
  [a b]
  (+ a b))
  
(defn- my-private-add-function 
  [a b]
  (+ a b))
```

Equivalent, but `defn-` is more concise.

## Recursion

Like `loop`, functions can recurse with `recur`. TCO prevents stack overflow.

```phel
;; Recursive factorial (regular recursion - can stack overflow)
(defn factorial [n]
  (if (<= n 1)
    1
    (* n (factorial (dec n)))))

(factorial 5)  ; => 120

;; Tail-recursive factorial using recur with loop
(defn factorial-recur [n]
  (loop [acc 1
         n n]
    (if (<= n 1)
      acc
      (recur (* acc n) (dec n)))))

(factorial-recur 5)  ; => 120

;; Recursive sum (can stack overflow on large collections)
(defn sum-recursive [coll]
  (if (empty? coll)
    0
    (+ (first coll) (sum-recursive (rest coll)))))

(sum-recursive [1 2 3 4 5])  ; => 15

;; Tail-recursive sum using recur (safe for large collections)
(defn sum-recur [coll]
  (loop [acc 0
         remaining coll]
    (if (empty? remaining)
      acc
      (recur (+ acc (first remaining)) (rest remaining)))))

(sum-recur [1 2 3 4 5])  ; => 15

;; Using recur directly in function (also tail-call optimized)
(defn countdown [n]
  (if (<= n 0)
    "Done!"
    (do
      (println n)
      (recur (dec n)))))

;; (countdown 5)  ; Prints: 5, 4, 3, 2, 1, then returns "Done!"
```

{% php_note() %}
`recur` compiles to a PHP `while`, avoiding "Maximum function nesting level" errors:

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

**Difference:** Recursion builds the call stack; `recur` reuses one stack frame (TCO).
{% end %}

## Multimethods

Runtime polymorphism via dispatch functions. Decouples dispatch from implementations, enabling open extension.

### Defining

`defmulti` declares the dispatch function. `defmethod` adds implementations per dispatch value:

```phel
;; Define a multimethod that dispatches on the :shape key
(defmulti area :shape)

;; Implement for each shape type
(defmethod area :circle [{:radius r}]
  (* 3.14159 r r))

(defmethod area :rectangle [{:width w :height h}]
  (* w h))

(defmethod area :triangle [{:base b :height h}]
  (/ (* b h) 2))

(area {:shape :circle :radius 5})       ; => 78.53975
(area {:shape :rectangle :width 4 :height 3}) ; => 12
(area {:shape :triangle :base 6 :height 4})   ; => 12
```

### Custom dispatch

Dispatch function can be anything, not just a keyword:

```phel
(defmulti greeting #(get % :language))

(defmethod greeting "en" [_] "Hello!")
(defmethod greeting "es" [_] "Hola!")
(defmethod greeting "de" [_] "Hallo!")

(greeting {:language "es"})  ; => "Hola!"
```

## Apply functions

```phel
(apply f expr*)
```

Calls `f` with the args. Last arg must be a list, spread as separate arguments. Returns the result.

```phel
(apply + [1 2 3]) ; Evaluates to 6
(apply + 1 2 [3]) ; Evaluates to 6
```

`(apply + 1 2 3)` is invalid: last arg must be a list.

## Passing by reference

Pass a variable by reference with `:reference` metadata:

```phel
(fn [^:reference my-arr]
  (php/apush my-arr 10))
```

Limited support: works for function arguments only (no destructuring).

{% php_note() %}
Equivalent to PHP `&`:

```php
// PHP
function addToArray(&$arr) {
    $arr[] = 10;
}

// Phel
(defn add-to-array [^:reference arr]
  (php/apush arr 10))
```

**Note:** Prefer immutable data structures over mutating PHP arrays.
{% end %}
