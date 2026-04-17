+++
title = "Functions & Bindings"
weight = 3
+++

Functions and names are the bread and butter of any Phel program. You'll learn to bind values, define functions, scope locals, destructure inputs, and build closures that remember.

{% question(difficulty="easy") %}
Use `def` to bind the name `greeting` to `"Hello, Phel!"`. Then evaluate `greeting`.
{% end %}
{% solution() %}
```phel
(def greeting "Hello, Phel!")
greeting
; => "Hello, Phel!"
```
`def` creates a global binding - a name pointing at a value.

Learn more: [Global and Local Bindings](/documentation/language/global-and-local-bindings)
{% end %}

{% question(difficulty="easy") %}
Use `defn` to define a function `hello` that takes no arguments and returns `"hello!"`.
```phel
(hello) ; => "hello!"
```
{% end %}
{% solution() %}
```phel
(defn hello [] "hello!")
```
`defn` is short for "define function". The empty `[]` is the parameter list; the last expression in the body is the return value.

Learn more: [Functions and Recursion](/documentation/language/functions-and-recursion)
{% end %}

{% question(difficulty="easy") %}
Define `double` so that `(double 5)` returns `10`.
{% end %}
{% solution() %}
```phel
(defn double [n] (* n 2))
```

Learn more: [Functions and Recursion](/documentation/language/functions-and-recursion)
{% end %}

{% question(difficulty="easy") %}
Add a docstring to `double`. Then look it up with `(doc double)`.
{% end %}
{% solution() %}
```phel
(defn double
  "Multiplies the given number by 2."
  [n]
  (* n 2))

(doc double)
```
Docstrings sit between the name and the parameter list. `doc` reads them back at the REPL - your future self will thank you.

Learn more: [Functions and Recursion](/documentation/language/functions-and-recursion)
{% end %}

{% question(difficulty="easy") %}
Use `let` to bind `name` to `"world"`, then return `"Hello, world!"` via `str`.
{% end %}
{% solution() %}
```phel
(let [name "world"]
  (str "Hello, " name "!"))
; => "Hello, world!"
```
`let` creates locals that exist only inside its body. Use it to keep your scope tight and your global namespace clean.

Learn more: [Global and Local Bindings](/documentation/language/global-and-local-bindings)
{% end %}

{% question(difficulty="medium") %}
Use `let` with multiple bindings to compute the area of a rectangle (width 5, height 3).
{% end %}
{% solution() %}
```phel
(let [width 5
      height 3]
  (* width height))
; => 15
```
You can stack as many bindings as you need. Later bindings can refer to earlier ones.

Learn more: [Global and Local Bindings](/documentation/language/global-and-local-bindings)
{% end %}

{% question(difficulty="medium") %}
Pull `x` and `y` out of the vector `[10 20]` in a single `let`, then return their sum.
{% end %}
{% solution() %}
```phel
(let [[x y] [10 20]]
  (+ x y))
; => 30
```
This is **destructuring**: the binding form mirrors the shape of the value. It also works with maps:
```phel
(let [{:keys [name age]} {:name "Ada" :age 36}]
  (str name " is " age))
; => "Ada is 36"
```

Learn more: [Destructuring](/documentation/language/destructuring)
{% end %}

{% question(difficulty="medium") %}
Create an anonymous function that adds 10 to a number. Call it with `5`. Try both the `fn` form and the short `|` form.
{% end %}
{% solution() %}
```phel
((fn [x] (+ x 10)) 5)
; => 15

(|(+ $ 10) 5)
; => 15
```
Anonymous functions shine when you need a one-off (think `map`, `filter`). The `|` shortcut uses `$` for the first argument, `$1` for the second, and so on.

Learn more: [Functions and Recursion](/documentation/language/functions-and-recursion)
{% end %}

{% question(difficulty="medium") %}
Define `greet` with an optional second argument that defaults to `"Hello"`:
```phel
(greet "Ada")           ; => "Hello, Ada!"
(greet "Ada" "Welcome") ; => "Welcome, Ada!"
```
{% end %}
{% solution() %}
```phel
(defn greet
  [name & [greeting]]
  (let [g (or greeting "Hello")]
    (str g ", " name "!")))
```
The `& [greeting]` destructures any extra arguments. `or` substitutes a default when `greeting` is `nil`.

Learn more: [Functions and Recursion](/documentation/language/functions-and-recursion), [Destructuring](/documentation/language/destructuring)
{% end %}

{% question(difficulty="medium") %}
Build `make-adder` so that `(make-adder n)` returns a function that adds `n` to its argument:
```phel
(def add5 (make-adder 5))
(add5 10) ; => 15
(add5 20) ; => 25
```
{% end %}
{% solution() %}
```phel
(defn make-adder [n]
  (fn [x] (+ x n)))

(def add5 (make-adder 5))
(add5 10) ; => 15
```
`make-adder` returns a fresh function that **closes over** `n` - this is a closure. The captured `n` lives on inside the returned function for as long as you keep it.

Learn more: [Functions and Recursion](/documentation/language/functions-and-recursion)
{% end %}

{% question(difficulty="medium") %}
Implement `factorial` using recursion.
```phel
(factorial 5) ; => 120
```
{% end %}
{% solution() %}
```phel
(defn factorial [n]
  (if (<= n 1)
    1
    (* n (factorial (dec n)))))
```
Classic recursion: shrink the problem on every call until you hit the base case (`n <= 1`). For huge `n` you'd reach for `loop`/`recur` (next section) to avoid blowing the stack.

Learn more: [Functions and Recursion](/documentation/language/functions-and-recursion)
{% end %}
