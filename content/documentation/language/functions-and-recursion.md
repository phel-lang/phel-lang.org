+++
title = "Functions and Recursion"
weight = 5
description = "Define functions with defn and fn, use multi-arity and variadics, recurse safely with recur, and dispatch with multimethods"
aliases = ["/documentation/functions-and-recursion"]

[extra]
difficulty = "intermediate"
+++

After this page you can define named and anonymous functions, give them several arities, recurse without growing the stack, and dispatch on data with multimethods.

## Global functions

<!-- phel-test: skip -->
```phel
(defn name docstring? attributes? [params*] expr*)
```

`defn` defines a global function. It returns the value of its last expression:

```phel
(defn add
  "Adds a and b."
  [a b]
  (+ a b))

(add 1 2) ; => 3
```

The docstring is optional. The REPL shows it with `(doc add)`.

### Multiple arities and variadics

A function can have one clause per argument count. The call picks the clause that matches:

```phel
(defn greet
  ([] "hi")
  ([name] (str "hi " name))
  ([greeting name] (str greeting " " name)))

(greet)              ; => "hi"
(greet "Ada")        ; => "hi Ada"
(greet "hello" "Ada") ; => "hello Ada"
```

`&` collects the remaining arguments into a sequence:

```phel
(defn sum-all [x & more]
  (apply + x more))

(sum-all 1 2 3) ; => 6
```

Only one clause can be variadic, and it must have the most parameters. Calling a `defn` with an argument count it does not accept fails at compile time with `PHEL002`.

Parameters can take vectors and maps apart by shape, as in `(defn area [{:keys [width height]}] ...)`. See [Destructuring](/documentation/language/destructuring/#in-function-parameters).

### Private functions

`defn-` defines a function that other namespaces cannot use. It is the same as adding `{:private true}` as the attribute map:

<!-- phel-test: skip -->
```phel
(defn- helper [x] (* x 2))

(defn helper
  {:private true}
  [x]
  (* x 2))
```

## Anonymous function (fn)

`fn` creates a function without a global name. Pass it to another function, or return it:

```phel
(map (fn [x] (* x 2)) [1 2 3]) ; => (2 4 6)

(defn make-adder [n]
  (fn [x] (+ x n)))

((make-adder 10) 5) ; => 15
```

`fn` supports the same arities and `&` as `defn`. The function closes over the locals around it, like `n` above.

`#(...)` is a shorter form for small functions. `%` (or `%1`) is the first argument, `%2` the second, and `%&` the rest:

```phel
(map #(* % 2) [1 2 3])     ; => (2 4 6)
(#(+ %1 %2) 1 2)           ; => 3
(#(apply + %&) 1 2 3)      ; => 6
```

Use `fn` when the body is more than one short expression or when a name for the argument helps the reader.

{% <php_note> %}
`#(* % 2)` is like PHP's arrow function `fn($x) => $x * 2`. Unlike PHP closures, a Phel `fn` captures every local it uses without a `use (...)` clause.
{% </php_note> %}

{% <callout kind="warning"> %}
**Removed in 0.50:** `|(...)` with `$`, `$1`, `$&`. Use `#(...)` with `%`. **Removed:** the `function?` predicate. Use `fn?`.
{% </callout> %}

## Apply and compose

`apply` calls a function with the elements of a collection as its arguments. The last argument must be a collection, a string, or `nil`:

```phel
(apply + [1 2 3])   ; => 6
(apply + 1 2 [3])   ; => 6
```

`partial` fixes the first arguments. `comp` chains functions from right to left:

```phel
((partial + 10) 5)          ; => 15
((comp inc #(* % 2)) 5)     ; => 11, same as (inc (* 5 2))
```

The [core API](/documentation/reference/api/core/) lists more helpers such as `juxt`, `complement`, and `constantly`.

## Recursion

A function can call itself. Each call adds a PHP stack frame, so deep recursion can hit PHP's nesting limit:

```phel
(defn sum-list [coll]
  (if (empty? coll)
    0
    (+ (first coll) (sum-list (rest coll)))))

(sum-list [1 2 3 4 5]) ; => 15
```

`recur` jumps back to the start of the function, or of the closest `loop`, with new argument values. It compiles to a PHP `while` loop, so it runs in constant stack space. `recur` must be the last thing the function does:

```phel
(defn factorial [n]
  (loop [acc 1
         n n]
    (if (<= n 1)
      acc
      (recur (* acc n) (dec n)))))

(factorial 5) ; => 120

(defn countdown [n]
  (if (<= n 0)
    :done
    (recur (dec n))))

(countdown 100000) ; => :done
```

The `loop` form itself is covered in [Control flow](/documentation/language/control-flow/#loop). For most collection work, `reduce`, `map`, and `filter` are shorter than explicit recursion.

## Multimethods

A multimethod picks an implementation from the result of a dispatch function. `defmulti` sets the dispatch function. `defmethod` adds one implementation per dispatch value, and `:default` catches the rest:

```phel
(defmulti area :shape)

(defmethod area :rectangle [{w :width h :height}]
  (* w h))

(defmethod area :circle [{r :radius}]
  (* 3 r r))

(defmethod area :default [shape]
  (throw (InvalidArgumentException. "Unknown shape")))

(area {:shape :rectangle :width 4 :height 3}) ; => 12
(area {:shape :circle :radius 2})             ; => 12
```

The dispatch function can be any function, such as `#(get % :language)` or `(fn [x] (type x))`. Other namespaces can add methods later without changing the original code. Dispatch on type hierarchies: [Interfaces](/documentation/language/interfaces/#hierarchies).

## Defn metadata shortcuts

Metadata on a `defn` can wrap the body:

<!-- phel-test: skip -->
```phel
(defn ^:memoize fib [n]
  (if (< n 2) n (+ (fib (dec n)) (fib (- n 2)))))

(defn ^{:memoize-lru 128} lookup [k]
  (slow-lookup k))

(defn ^:async fetch [url]
  (http/get url))
```

`^:memoize` caches every result forever. `^{:memoize-lru N}` keeps the `N` most recent results. They desugar to [`memoize`](/documentation/reference/api/core/#memoize) and [`memoize-lru`](/documentation/reference/api/core/#memoize-lru), and recursive self-calls also use the cache. `^:async` wraps the body in `async` and returns an `Amp\Future`: see [Async](/documentation/language/async/).

## Return and parameter types (`:tag`)

Type hints become PHP type declarations, and the compiler checks them:

```phel
(defn ^int add-ints [^int a ^int b] (+ a b))

(defn greet-name ^{:tag "?string"} [^string name]
  (when (seq name) (str "hi " name)))
```

Write a hint as `^int`, `^"?int"`, `^"\\Foo\\Bar"`, or `^{:tag "..."}`. The compiler also infers return types from the last expression and parameter types from how the body uses them. It adds inferred types to the PHP signature of single-arity functions and reports mismatches at compile time.

## Passing by reference

`^:reference` metadata passes a PHP variable by reference, like PHP's `&$arr`:

```phel
(defn add-to-array [^:reference arr]
  (php/apush arr 10))
```

It works on plain function parameters only, not with destructuring. Prefer returning a new value over changing a PHP array in place.

Next: [Destructuring](/documentation/language/destructuring/) shows every way to take arguments apart by shape.
