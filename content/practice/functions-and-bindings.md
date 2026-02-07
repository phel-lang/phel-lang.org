+++
title = "Functions & Bindings"
weight = 3
+++

Functions are the building blocks of any Phel program. Here you'll learn how to define them, name things, and scope your variables.

{% question() %}
Use `def` to create a binding called `greeting` with the value `"Hello, Phel!"`. Then evaluate `greeting`.
{% end %}
{% solution() %}
```phel
(def greeting "Hello, Phel!")
greeting
# => "Hello, Phel!"
```
`def` creates a global binding — a name associated with a value.

Learn more: [Global and Local Bindings](/documentation/global-and-local-bindings)
{% end %}

{% question() %}
Use `defn` to define a function `hello` that takes no arguments and returns `"hello!"`.
```phel
(hello) # => "hello!"
```
{% end %}
{% solution() %}
```phel
(defn hello [] "hello!")
```
`defn` is short for "define function". The `[]` is the parameter list (empty here), and the last expression is the return value.

Learn more: [Functions and Recursion](/documentation/functions-and-recursion)
{% end %}

{% question() %}
Define a function `double` that takes a number and returns it multiplied by 2.
```phel
(double 5) # => 10
```
{% end %}
{% solution() %}
```phel
(defn double [n] (* n 2))
```

Learn more: [Functions and Recursion](/documentation/functions-and-recursion)
{% end %}

{% question() %}
Add a docstring to `double`. Then use `(doc double)` to see it.
{% end %}
{% solution() %}
```phel
(defn double
  "Multiplies the given number by 2."
  [n]
  (* n 2))

(doc double)
```
Docstrings are placed between the function name and the parameter list. They help other developers (and your future self!) understand what a function does.

Learn more: [Functions and Recursion](/documentation/functions-and-recursion)
{% end %}

{% question() %}
Use `let` to create a local binding `name` with value `"world"`, then return the string `"Hello, world!"` using `str`.
{% end %}
{% solution() %}
```phel
(let [name "world"]
  (str "Hello, " name "!"))
# => "Hello, world!"
```
`let` creates bindings that only exist within its body. This keeps your code clean and avoids polluting the global scope.

Learn more: [Global and Local Bindings](/documentation/global-and-local-bindings)
{% end %}

{% question() %}
Use `let` to bind multiple values and compute a result. Calculate the area of a rectangle with `width` 5 and `height` 3.
{% end %}
{% solution() %}
```phel
(let [width 5
      height 3]
  (* width height))
# => 15
```
You can create multiple bindings in a single `let`. Later bindings can reference earlier ones.

Learn more: [Global and Local Bindings](/documentation/global-and-local-bindings)
{% end %}

{% question() %}
Create an anonymous function that adds 10 to a number. Test it by calling it with `5`. Try both the `fn` form and the short `|` form.
{% end %}
{% solution() %}
```phel
# Using fn
((fn [x] (+ x 10)) 5)
# => 15

# Using the short form
(|(+ $ 10) 5)
# => 15
```
Anonymous functions are useful when you need a quick one-off function (especially with `map`, `filter`, etc.). The `|` form uses `$` for the first argument, `$1` for the second, and so on.

Learn more: [Functions and Recursion](/documentation/functions-and-recursion)
{% end %}

{% question() %}
Define a function `greet` that takes a `name` and an optional `greeting` (defaulting to `"Hello"`):
```phel
(greet "Ada")           # => "Hello, Ada!"
(greet "Ada" "Welcome") # => "Welcome, Ada!"
```
Hint: use a [rest parameter](/documentation/functions-and-recursion) or multiple arities.
{% end %}
{% solution() %}
```phel
(defn greet
  [name & [greeting]]
  (let [g (or greeting "Hello")]
    (str g ", " name "!")))
```
The `& [greeting]` captures extra arguments via destructuring. `or` provides the default value when `greeting` is `nil`.

Learn more: [Functions and Recursion](/documentation/functions-and-recursion), [Destructuring](/documentation/destructuring)
{% end %}

{% question() %}
Implement a `factorial` function using recursion.
```phel
(factorial 5) # => 120
```
{% end %}
{% solution() %}
```phel
(defn factorial [n]
  (if (<= n 1)
    1
    (* n (factorial (dec n)))))
```
This is classic recursion: the function calls itself with a smaller input until it reaches the base case (`n <= 1`).

Learn more: [Functions and Recursion](/documentation/functions-and-recursion)
{% end %}
