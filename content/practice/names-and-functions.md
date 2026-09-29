+++
title = "Names and Functions"
weight = 3
description = "Give values a name with def and let, write your own functions with defn and fn, and call functions with a flexible number of arguments."
aliases = ["/practice/functions-and-bindings/"]

[extra]
stage = "Foundations"
goals = [
  "Name values globally with `def` and locally with `let`",
  "Define functions with `defn`, parameters, and a docstring",
  "Write anonymous functions with `fn` and `#(...)` and pass them to `update`",
  "Give one function several arities, or any number of arguments with `&`",
  "Call a function with the items of a collection using `apply`",
]
read_first = [
  ["Global and Local Bindings", "/documentation/language/global-and-local-bindings/"],
  ["Functions and Recursion", "/documentation/language/functions-and-recursion/"],
]
recap = [
  "You can name a value with `def` and keep helper names local with `let`",
  "You can define, document, and call your own functions",
  "You can write a small anonymous function and hand it to `update`",
  "You can write functions that accept optional or extra arguments",
  "You can spread a collection into a function call with `apply`",
]
+++

So far you have typed values and called built-in functions. Now you give things names and write your own functions. A function is the main unit of reuse in Phel: small functions that each do one thing, combined into bigger ones. Every module after this one builds on what you learn here.

## Naming values with def

`def` binds a global name to a value. After that, you can use the name anywhere the value would go.

```phel
(def pi 3.14159)
(* pi 2) ; => 6.28318
```

{% question(difficulty="easy", kind="predict") %}
What does the last line return?
```phel
(def tax-rate 0.2)
(* 100 tax-rate)
```
{% end %}
{% solution() %}
```phel
(def tax-rate 0.2)
(* 100 tax-rate) ; => 20.0
```
Phel replaces `tax-rate` with its value, `0.2`. An integer times a float gives a float, so the result is `20.0`, not `20`.
{% end %}

{% question(difficulty="easy", kind="write") %}
Bind the name `greeting` to `"Hello, Phel!"`. Then use `greeting` and `str` to build this string:
<!-- phel-test: skip -->
```phel
; => "Hello, Phel! Nice to meet you."
```
{% end %}
{% solution() %}
```phel
(def greeting "Hello, Phel!")
(str greeting " Nice to meet you.") ; => "Hello, Phel! Nice to meet you."
```
`def` creates a global binding: a name that points at a value. In PHP you would write `$greeting = "Hello, Phel!";`. Use `def` for values that the whole program shares.

Learn more: [Global and Local Bindings](/documentation/language/global-and-local-bindings/#definition-def)
{% end %}

{% question(difficulty="easy", kind="predict") %}
Now that you can name a value, check that "changing" a collection leaves the original alone. What do the last two lines return?
```phel
(def langs ["php" "phel"])
(conj langs "clojure")
langs
```
{% end %}
{% solution() %}
```phel
(def langs ["php" "phel"])
(conj langs "clojure") ; => ["php" "phel" "clojure"]
langs                  ; => ["php" "phel"]
```
`conj` returns a new vector and leaves `langs` as it was. To keep the new vector, give it a name: `(def more-langs (conj langs "clojure"))`. In PHP, `$langs[] = "clojure";` changes the array in place. In Phel nothing changes behind your back.

Learn more: [Data Structures](/documentation/language/data-structures/)
{% end %}

## Local names with let

`let` takes a vector of name and value pairs, then a body. The names exist only inside the body. A later name can use an earlier one.

```phel
(let [price 50
      qty 3]
  (* price qty)) ; => 150
```

{% question(difficulty="easy", kind="fill") %}
Fill in the blanks so the expression returns the area of a rectangle that is 5 wide and 3 high.
<!-- phel-test: skip -->
```phel
(let [width 5
      height ___]
  (___ width height))
; => 15
```
{% end %}
{% solution() %}
```phel
(let [width 5
      height 3]
  (* width height))
; => 15
```
Stack as many pairs as you need in the `let` vector. Local names keep the global namespace clean.

Learn more: [Global and Local Bindings](/documentation/language/global-and-local-bindings/#local-bindings-let)
{% end %}

{% question(difficulty="medium", kind="predict") %}
What does the `let` return, and what does `x` return on the last line?
```phel
(def x 1)

(let [x 10
      y (* x 2)]
  (+ x y))

x
```
{% end %}
{% hint() %}
Inside the `let`, the local `x` hides the global one. Which `x` does `y` see?
{% end %}
{% solution() %}
```phel
(def x 1)

(let [x 10
      y (* x 2)]
  (+ x y)) ; => 30

x ; => 1
```
Inside the `let`, `x` is `10`, so `y` is `20` and the sum is `30`. The local `x` only hides the global one inside the body. Outside, `x` is still `1`.
{% end %}

## Defining functions with defn

`defn` defines a named function: a name, a vector of parameters, and a body. The function returns the value of its last expression. There is no `return` keyword. A docstring after the name describes what the function does.

```phel
(defn square
  "Multiplies n by itself."
  [n]
  (* n n))

(square 7) ; => 49
```

{% question(difficulty="easy", kind="write") %}
Define a function `twice` that multiplies a number by 2.
<!-- phel-test: skip -->
```phel
(twice 5)  ; => 10
(twice -3) ; => -6
```
{% end %}
{% solution() %}
```phel
(defn twice [n]
  (* n 2))

(twice 5)  ; => 10
(twice -3) ; => -6
```
`[n]` is the parameter list. The body `(* n 2)` is the last expression, so its value is what the function returns.

Learn more: [Functions and Recursion](/documentation/language/functions-and-recursion/#global-functions)
{% end %}

{% question(difficulty="easy", kind="write") %}
Define `full-name` with two parameters and a docstring. It joins a first and a last name with a space.
<!-- phel-test: skip -->
```phel
(full-name "Ada" "Lovelace") ; => "Ada Lovelace"
```
{% end %}
{% solution() %}
```phel
(defn full-name
  "Joins a first and a last name with a space."
  [first-name last-name]
  (str first-name " " last-name))

(full-name "Ada" "Lovelace") ; => "Ada Lovelace"
```
The docstring goes between the name and the parameter list. In the REPL, `(doc full-name)` prints it back.

Learn more: [Functions and Recursion](/documentation/language/functions-and-recursion/#global-functions)
{% end %}

{% question(difficulty="medium", kind="fix") %}
This function does not compile. Read the error, then fix the function so that `(area 5 3)` returns `15`.
<!-- phel-test: skip -->
```phel
(defn area [width]
  (* width height))

(area 5 3)
; ERROR: Cannot resolve symbol 'height'
```
{% end %}
{% hint() %}
A function only sees its own parameters, its local names, and global names. Where should `height` come from?
{% end %}
{% solution() %}
```phel
(defn area [width height]
  (* width height))

(area 5 3) ; => 15
```
`height` was never a parameter, so Phel cannot find it. Every value the function needs from the caller goes in the parameter vector, in the order the caller passes them.
{% end %}

{% question(difficulty="medium", kind="write") %}
Define `shipping-cost`. A parcel costs a base fee of 5, plus 2 for each kilogram. Use `let` inside the function to name the base fee and the price per kilogram.
<!-- phel-test: skip -->
```phel
(shipping-cost 0) ; => 5
(shipping-cost 3) ; => 11
```
{% end %}
{% hint() %}
The body of `defn` can be a `let`. The value of the `let` becomes the return value of the function.
{% end %}
{% solution() %}
```phel
(defn shipping-cost [kg]
  (let [base-fee 5
        per-kg 2]
    (+ base-fee (* per-kg kg))))

(shipping-cost 0) ; => 5
(shipping-cost 3) ; => 11
```
Naming `base-fee` and `per-kg` tells the reader what the numbers mean. Without the names, `(+ 5 (* 2 kg))` works but hides the rules.
{% end %}

## Anonymous functions

`fn` creates a function without a name. `#(...)` is a shorter form, where `%` is the first argument (`%1`, `%2` when there are several). Use them when you need a small function once, for example as the last argument of `update`.

```phel
(update {:name "Ada" :visits 2} :visits (fn [n] (+ n 10)))
; => {:name "Ada" :visits 12}
```

{% question(difficulty="easy", kind="predict") %}
What do these two expressions return?
```phel
((fn [x] (+ x 10)) 5)
(#(* %1 %2) 3 4)
```
{% end %}
{% solution() %}
```phel
((fn [x] (+ x 10)) 5) ; => 15
(#(* %1 %2) 3 4)      ; => 12
```
The first position of a list is the function to call. Here that function is written in place. `#(* %1 %2)` is the same as `(fn [a b] (* a b))`.

Learn more: [Functions and Recursion](/documentation/language/functions-and-recursion/#anonymous-function-fn)
{% end %}

{% question(difficulty="medium", kind="refactor") %}
This code reads the total, changes it, and writes it back. Rewrite it with `update` and a `#(...)` function, so `total` is named only once.
```phel
(def cart {:items 3 :total 50})

(assoc cart :total (* (get cart :total) 0.9))
; => {:items 3 :total 45.0}
```
{% end %}
{% hint() %}
`update` passes the old value to the function you give it and stores what the function returns.
{% end %}
{% solution() %}
```phel
(def cart {:items 3 :total 50})

(update cart :total #(* % 0.9))
; => {:items 3 :total 45.0}
```
`update` does the read and the write for you. You only describe the change: "multiply by 0.9". The original `cart` does not change.
{% end %}

## Flexible arguments

A function can have one body per number of arguments. This is called multi-arity. Wrap each parameter vector and its body in parentheses. A short arity often calls a longer one with a default.

```phel
(defn price
  ([amount] (price amount 0))
  ([amount discount] (- amount discount)))

(price 100)    ; => 100
(price 100 15) ; => 85
```

{% question(difficulty="medium", kind="write") %}
Define `greet`. With one argument it says `"Hello"`. With two, the first argument is the greeting to use.
<!-- phel-test: skip -->
```phel
(greet "Ada")           ; => "Hello, Ada!"
(greet "Welcome" "Ada") ; => "Welcome, Ada!"
```
{% end %}
{% hint() %}
Write the two-argument version first. Then let the one-argument version call it with `"Hello"`.
{% end %}
{% solution() %}
```phel
(defn greet
  ([name] (greet "Hello" name))
  ([greeting name] (str greeting ", " name "!")))

(greet "Ada")           ; => "Hello, Ada!"
(greet "Welcome" "Ada") ; => "Welcome, Ada!"
```
The one-argument arity fills in the default and hands over to the two-argument arity. The formatting rule lives in one place. In PHP you would write a default parameter value, `function greet($name, $greeting = "Hello")`.

Learn more: [Functions and Recursion](/documentation/language/functions-and-recursion/#multiple-arities-and-variadics)
{% end %}

{% question(difficulty="medium", kind="predict") %}
`apply` calls a function with the items of a collection as its arguments. What do these return?
```phel
(apply + [1 2 3])
(apply max 4 [1 9])
(apply str ["a" "b" "c"])
```
{% end %}
{% hint() %}
Rewrite each call without `apply`: take the items out of the vector and put them in the argument list.
{% end %}
{% solution() %}
```phel
(apply + [1 2 3])         ; => 6
(apply max 4 [1 9])       ; => 9
(apply str ["a" "b" "c"]) ; => "abc"
```
`(apply + [1 2 3])` is the same as `(+ 1 2 3)`. Arguments before the collection come first, so `(apply max 4 [1 9])` is `(max 4 1 9)`.

Learn more: [Functions and Recursion](/documentation/language/functions-and-recursion/#apply-and-compose)
{% end %}

{% question(difficulty="hard", kind="write") %}
Define `log-line`. It takes a level, then any number of text parts, and joins them after the level in brackets.
<!-- phel-test: skip -->
```phel
(log-line "INFO" "user " "Ada " "logged in") ; => "[INFO] user Ada logged in"
(log-line "WARN" "disk almost full")         ; => "[WARN] disk almost full"
(log-line "DEBUG")                           ; => "[DEBUG] "
```
{% end %}
{% hint() %}
In a parameter vector, `& parts` collects every remaining argument into one collection. Then you need a way to pass that collection to `str`.
{% end %}
{% solution() %}
```phel
(defn log-line [level & parts]
  (str "[" level "] " (apply str parts)))

(log-line "INFO" "user " "Ada " "logged in") ; => "[INFO] user Ada logged in"
(log-line "WARN" "disk almost full")         ; => "[WARN] disk almost full"
(log-line "DEBUG")                           ; => "[DEBUG] "
```
`level` takes the first argument and `parts` collects the rest, which can be none. A common mistake is `(str "[" level "] " parts)`: that puts the printed collection in the string, not its items.

Learn more: [Functions and Recursion](/documentation/language/functions-and-recursion/#multiple-arities-and-variadics)
{% end %}
