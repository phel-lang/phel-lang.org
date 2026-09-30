+++
title = "Macros"
weight = 10
description = "See Phel code as plain data, build code with syntax-quote, write your own macros, and learn when a function is the better choice."

[extra]
stage = "Real world"
goals = [
  "Treat quoted code as a list you can read and change with `first`, `rest`, and `cons`",
  "Build code with syntax-quote, `~` and `~@`, and run it with `eval`",
  "Write a macro with `defmacro` and check its output with `macroexpand-1`",
  "Avoid name clashes with auto-gensym (`name#`) and evaluate each argument once",
  "Decide when a function is enough and a macro is not needed",
]
read_first = [
  ["Macros", "/documentation/language/macros/"],
  ["Reader Shortcuts", "/documentation/language/reader-shortcuts/"],
]
recap = [
  "You can read a quoted form as a list of symbols and values",
  "You can build code from a template with syntax-quote, unquote, and splice",
  "You can write a macro that controls when its arguments run",
  "You can expand a macro to see the code it generates",
  "You can spot a macro that should have been a function",
]
+++

In Phel, code is data. A form such as `(+ 1 2)` is a list, and you can take it apart and build new ones with the same functions you use on any list. A macro is a function that runs at compile time: it receives code as data and returns new code. This module starts with code as data and only writes a macro once that idea is clear.

## Code is data

A quote (`'`) stops evaluation. `'(+ 1 2)` is not the number 3: it is a list with three items, the symbol `+` and the numbers 1 and 2.

```phel
(+ 1 2)  ; => 3
'(+ 1 2) ; => (+ 1 2)
```

{% <question difficulty="easy" kind="predict"> %}
What does each line return?
```phel
(first '(+ 1 2))
(rest '(+ 1 2))
(count '(* 2 (+ 1 3)))
```
{% </question> %}
{% <solution> %}
```phel
(first '(+ 1 2))       ; => +
(rest '(+ 1 2))        ; => (1 2)
(count '(* 2 (+ 1 3))) ; => 3
```
A quoted form is an ordinary list. The nested `(+ 1 3)` counts as one item: it is a list inside the list.
{% </solution> %}

{% <question difficulty="easy" kind="predict"> %}
What is printed, and what is returned?
```phel
'(println "hi")
```
{% </question> %}
{% <solution> %}
```phel
'(println "hi") ; => (println "hi")
```
Nothing is printed. The quote keeps the form as data, so `println` never runs. You get back a list of a symbol and a string.
{% </solution> %}

{% <question difficulty="easy" kind="predict"> %}
What does each `type` call return?
```phel
(type '(+ 1 2))
(type (first '(+ 1 2)))
(type (last '(+ 1 2)))
```
{% </question> %}
{% <solution> %}
```phel
(type '(+ 1 2))         ; => :list
(type (first '(+ 1 2))) ; => :symbol
(type (last '(+ 1 2)))  ; => :int
```
Code is built from the same values you already know: lists, symbols, numbers, strings, keywords. That is why a macro can work on code with normal Phel functions.
{% </solution> %}

## Running code you built: `eval`

`eval` takes a form and runs it. Build a list, then evaluate it:

```phel
(eval '(+ 1 2))         ; => 3
(eval (list '- 10 4))   ; => 6
```

{% <question difficulty="easy" kind="fill"> %}
Fill in the blank so the built form multiplies its numbers:
<!-- phel-test: skip -->
```phel
(eval (list ___ 2 3)) ; => 6
```
{% </question> %}
{% <hint> %}
You need the symbol for multiplication, not the function itself.
{% </hint> %}
{% <solution> %}
```phel
(eval (list '* 2 3)) ; => 6
```
`'*` is the symbol. `(list '* 2 3)` builds the form `(* 2 3)`, and `eval` runs it.
{% </solution> %}

{% <question difficulty="medium" kind="write"> %}
Write `swap-op`. It takes a form and a new operator symbol and returns the same form with the operator replaced.
<!-- phel-test: skip -->
```phel
(swap-op '(+ 2 3) '*)        ; => (* 2 3)
(eval (swap-op '(+ 2 3) '*)) ; => 6
```
{% </question> %}
{% <hint> %}
The operator is the `first` item. Keep the `rest` and put the new operator in front with `cons`.
{% </hint> %}
{% <solution> %}
```phel
(defn swap-op [form op]
  (cons op (rest form)))

(swap-op '(+ 2 3) '*)        ; => (* 2 3)
(eval (swap-op '(+ 2 3) '*)) ; => 6
```
This is a code transformation, written as a normal function. A macro does the same work, but the compiler calls it for you.

In real programs you rarely need `eval`. It is here to show that built code can run.
{% </solution> %}

## Syntax-quote: code templates

Building code with `list` gets hard to read. Syntax-quote (`` ` ``) writes a template instead. Inside it, `~` inserts a value and `~@` inserts the items of a collection one by one:

```phel
(let [x 5]
  `(+ 1 ~x))       ; => (phel.core/+ 1 5)

`(+ ~@[1 2 3])     ; => (phel.core/+ 1 2 3)
```

Syntax-quote also writes the full name of core symbols, such as `phel.core/+`. The code still means `+`, even if the caller has a local with the same name.

{% <question difficulty="medium" kind="predict"> %}
What does each line return? Look closely at the difference between `~` and `~@`.
```phel
(let [nums [1 2 3]]
  `(+ ~nums))

(let [nums [1 2 3]]
  `(+ ~@nums))

(let [nums [1 2 3]]
  (eval `(+ ~@nums)))
```
{% </question> %}
{% <hint> %}
`~` inserts one value. `~@` opens the collection and inserts what is inside.
{% </hint> %}
{% <solution> %}
```phel
(let [nums [1 2 3]]
  `(+ ~nums))           ; => (phel.core/+ [1 2 3])

(let [nums [1 2 3]]
  `(+ ~@nums))          ; => (phel.core/+ 1 2 3)

(let [nums [1 2 3]]
  (eval `(+ ~@nums)))   ; => 6
```
With `~` the vector becomes one argument, and `(+ [1 2 3])` would fail. With `~@` each number becomes its own argument.
{% </solution> %}

## Writing a macro

`defmacro` looks like `defn`. The difference: its arguments arrive as code, not as values, and it returns code. The compiler replaces the call with that code before the program runs. Here `twice` runs a form two times:

```phel
(defmacro twice [form]
  `(do ~form ~form))

(twice (println "hi")) ; prints "hi" two times
```

`macroexpand-1` shows the code a macro produces, without running it. Quote the call so it stays data:

```phel
(defmacro twice [form]
  `(do ~form ~form))

(macroexpand-1 '(twice (println "hi")))
; => (do (println "hi") (println "hi"))
```

{% <question difficulty="medium" kind="write"> %}
Write `unless`, the opposite of `if`: when the test is falsy it returns `then`, otherwise `else`. Only the chosen branch may run.
<!-- phel-test: skip -->
```phel
(unless false "yes" "no")        ; => "yes"
(unless (= 1 1) "yes" "no")      ; => "no"
(unless true (println "boom") 1) ; => 1, and nothing is printed
```
{% </question> %}
{% <hint> %}
Return an `if` form with the test wrapped in `not`. Use syntax-quote and `~` for the three parts.
{% </hint> %}
{% <solution> %}
```phel
(defmacro unless [test then else]
  `(if (not ~test) ~then ~else))

(unless false "yes" "no")        ; => "yes"
(unless (= 1 1) "yes" "no")      ; => "no"
(unless true (println "boom") 1) ; => 1
```
The call becomes `(if (not true) (println "boom") 1)`. `if` runs only one branch, so `"boom"` is never printed.
{% </solution> %}

{% <question difficulty="medium" kind="predict"> %}
Here is `unless` as a function instead of a macro. What is printed, and what is returned?
```phel
(defn unless-fn [test then else]
  (if (not test) then else))

(unless-fn true (println "boom") 1)
```
{% </question> %}
{% <hint> %}
When do function arguments run: before the call or inside it?
{% </hint> %}
{% <solution> %}
```phel
(defn unless-fn [test then else]
  (if (not test) then else))

(unless-fn true (println "boom") 1) ; prints "boom", => 1
```
A function evaluates all its arguments before the body runs, so `"boom"` is printed even though that branch is not chosen. Control over evaluation is the main reason to write a macro.
{% </solution> %}

{% <question difficulty="medium" kind="predict"> %}
What does `macroexpand-1` return here?
```phel
(defmacro unless [test then else]
  `(if (not ~test) ~then ~else))

(macroexpand-1 '(unless (empty? xs) "has items" "empty"))
```
{% </question> %}
{% <hint> %}
`not` is written inside the syntax-quote, so it gets its full name. The arguments are inserted as they were written.
{% </hint> %}
{% <solution> %}
```phel
(defmacro unless [test then else]
  `(if (not ~test) ~then ~else))

(macroexpand-1 '(unless (empty? xs) "has items" "empty"))
; => (if (phel.core/not (empty? xs)) "has items" "empty")
```
`xs` does not need to exist: expanding only rewrites code, it does not run it. When a macro misbehaves, expand it and read the result.
{% </solution> %}

## Hygiene: safe names and single evaluation

A macro that binds a local can hide a name the caller uses. End the name with `#` inside a syntax-quote, and each expansion gets a fresh, unique symbol:

```phel
(defmacro my-or [a b]
  `(let [tmp# ~a]
     (if tmp# tmp# ~b)))

(macroexpand-1 '(my-or nil 42))
; => (let [tmp__1 nil] (if tmp__1 tmp__1 42))
```

The number after `tmp__` changes on each expansion.

{% <question difficulty="medium" kind="fix"> %}
`add-ten` works with a literal, but gives a wrong answer when the caller has a local named `n`. Find the problem and fix it.
<!-- phel-test: skip -->
```phel
(defmacro add-ten [x]
  `(let [n 10]
     (+ n ~x)))

(add-ten 1)             ; => 11
(let [n 1] (add-ten n)) ; => 20, expected 11
```
{% </question> %}
{% <hint> %}
Expand `(add-ten n)` and read the `let`. Which `n` does the inserted `n` refer to?
{% </hint> %}
{% <solution> %}
```phel
(defmacro add-ten [x]
  `(let [n# 10]
     (+ n# ~x)))

(add-ten 1)             ; => 11
(let [n 1] (add-ten n)) ; => 11
```
The broken version expands to `(let [n 10] (+ n n))`: the macro's `n` hides the caller's `n`. With `n#`, the macro's local gets a unique name that cannot clash.
{% </solution> %}

{% <question difficulty="hard" kind="fix"> %}
`square` looks right, but `(square (next-id))` would call `next-id` twice. Show the problem with `println`, then fix the macro so its argument runs once.
<!-- phel-test: skip -->
```phel
(defmacro square [x]
  `(* ~x ~x))

(square (do (println "run") 3)) ; prints "run" two times, => 9
```
{% </question> %}
{% <hint> %}
Expand it. The argument is code, and `~x` pastes that code in two places. Bind it to a local first.
{% </hint> %}
{% <solution> %}
```phel
(defmacro square [x]
  `(let [v# ~x]
     (* v# v#)))

(square (do (println "run") 3)) ; prints "run" once, => 9
```
A macro copies code, not values. When an argument appears more than once in the template, bind it once with a `name#` local.
{% </solution> %}

{% <question difficulty="hard" kind="write"> %}
Write `with-timing`. It takes a label and any number of body forms, runs the body, prints the label and the time in milliseconds, and returns the body's result. Use `(php/microtime true)` for the current time in seconds.
<!-- phel-test: skip -->
```phel
(with-timing "sum" (reduce + (range 1000)))
; prints something like: sum 0.1 ms
; => 499500
```
{% </question> %}
{% <hint> %}
Take the body with `& body` and splice it into a `do` with `~@`. You need two locals, one for the start time and one for the result: both need `#`.
{% </hint> %}
{% <solution> %}
```phel
(defmacro with-timing [label & body]
  `(let [start#  (php/microtime true)
         result# (do ~@body)]
     (println ~label (* 1000 (- (php/microtime true) start#)) "ms")
     result#))

(with-timing "sum" (reduce + (range 1000))) ; => 499500
```
A function could not do this: it would receive the result, already computed, and have nothing left to time. The macro wraps the code itself.
{% </solution> %}

## When not to write a macro

A macro is not a value. You cannot pass it to `map`, store it in a map, or `apply` it. Write a macro only for new syntax or for control over evaluation. Everything else is a function.

{% <question difficulty="medium" kind="refactor"> %}
`(map add-tax [10 20])` fails with a `Too few arguments` error. Rewrite `add-tax` so that it works with `map`.
<!-- phel-test: skip -->
```phel
(defmacro add-tax [price]
  `(* ~price 1.21))

(add-tax 10)            ; => 12.1
(map add-tax [10 20])   ; fails
```
{% </question> %}
{% <hint> %}
Does `add-tax` need to control when its argument runs?
{% </hint> %}
{% <solution> %}
```phel
(defn add-tax [price]
  (* price 1.21))

(add-tax 10)          ; => 12.1
(map add-tax [10 20]) ; => (12.1 24.2)
```
`add-tax` only computes a value from a value, so it should be a function. Reach for a function first, and write a macro only when a function cannot do the job.

Learn more: [When to write a macro](/documentation/language/macros/#when-to-write-a-macro)
{% </solution> %}
