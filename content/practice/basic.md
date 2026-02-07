+++
title = "First Steps"
weight = 1
+++

Your first encounter with Phel! These exercises will get you comfortable with prefix notation, basic expressions, and calling functions. Fire up the [REPL](/documentation/repl) and follow along.

{% question() %}
Compute `1 + 1`
{% end %}
{% solution() %}
```phel
(+ 1 1)
```
In Phel (like all Lisps), the operator comes first. This is called **prefix notation**.

Learn more: [Arithmetic](/documentation/arithmetic)
{% end %}

{% question() %}
Compute `(3 + 4 / 5) * 6`
{% end %}
{% solution() %}
```phel
(* (+ 3 (/ 4 5)) 6)
```
Nested expressions are evaluated from the inside out. No operator precedence rules to memorize!

Learn more: [Arithmetic](/documentation/arithmetic)
{% end %}

{% question() %}
Use the `str` function to join the strings `"hello"` and `" world"` together.
{% end %}
{% solution() %}
```phel
(str "hello" " world")
# => "hello world"
```
`str` concatenates any number of strings together.

Learn more: [Basic Types](/documentation/basic-types)
{% end %}

{% question() %}
Call the function `get` with arguments `"hello"` and `1`. What does it return?
{% end %}
{% solution() %}
```phel
(get "hello" 1)
# => "e"
```
Strings are indexable! `get` retrieves the character at position 1 (zero-based).

Learn more: [Basic Types](/documentation/basic-types)
{% end %}

{% question() %}
Check if two values are equal: is `(+ 2 3)` the same as `5`?
{% end %}
{% solution() %}
```phel
(= (+ 2 3) 5)
# => true
```
The `=` function compares values for equality. It works with any types.

Learn more: [Truth and Boolean Operations](/documentation/truth-and-boolean-operations)
{% end %}

{% question() %}
Try these predicates and guess the result before running them:
```phel
(string? "hello")
(int? 42)
(nil? nil)
(nil? 0)
```
{% end %}
{% solution() %}
```phel
(string? "hello") # => true
(int? 42)         # => true
(nil? nil)        # => true
(nil? 0)          # => false  (0 is not nil!)
```
Predicates are functions that return `true` or `false`. By convention, their names end with `?`.

Learn more: [Basic Types](/documentation/basic-types)
{% end %}

{% question() %}
Use `not`, `and`, and `or` to evaluate these expressions. Predict the result first!
```phel
(not true)
(and true false)
(or false true)
(and (> 5 3) (< 10 20))
```
{% end %}
{% solution() %}
```phel
(not true)              # => false
(and true false)        # => false
(or false true)         # => true
(and (> 5 3) (< 10 20)) # => true
```
Boolean operators work as you'd expect. `and` returns the last truthy value or the first falsy one; `or` returns the first truthy value.

Learn more: [Truth and Boolean Operations](/documentation/truth-and-boolean-operations)
{% end %}

{% question() %}
What happens if you evaluate `(+ 1 "hello")`? Try it! What about `(type 42)` and `(type "hi")`?
{% end %}
{% solution() %}
```phel
(+ 1 "hello") # => Error! You can't add a number and a string.
(type 42)      # => "int"
(type "hi")    # => "string"
```
Phel will tell you when types don't match. The `type` function helps you inspect any value.
{% end %}
