+++
title = "First Steps"
weight = 1
+++

Welcome to Phel! These exercises ease you into prefix notation, basic types, and the REPL feel. Fire up the [Phel REPL](/documentation/tooling/repl) and try each one before peeking at the solution.

{% question(difficulty="easy") %}
Compute `1 + 1`.
{% end %}
{% solution() %}
```phel
(+ 1 1)
```
In Phel (like every Lisp), the operator comes first. This is called **prefix notation**.

Learn more: [Arithmetic](/documentation/language/arithmetic)
{% end %}

{% question(difficulty="easy") %}
Compute `(3 + 4 / 5) * 6`.
{% end %}
{% solution() %}
```phel
(* (+ 3 (/ 4 5)) 6)
```
Nested expressions evaluate inside-out. No precedence rules to memorize - the parens make the order obvious.

Learn more: [Arithmetic](/documentation/language/arithmetic)
{% end %}

{% question(difficulty="easy") %}
Use `str` to glue `"hello"` and `" world"` together.
{% end %}
{% solution() %}
```phel
(str "hello" " world")
; => "hello world"
```
`str` concatenates any number of values into a string.

Learn more: [Basic Types](/documentation/language/basic-types)
{% end %}

{% question(difficulty="easy") %}
Print `"Hello, Phel!"` to the screen.
{% end %}
{% solution() %}
```phel
(println "Hello, Phel!")
; Hello, Phel!
; => nil
```
`println` writes its arguments to stdout followed by a newline. The expression itself returns `nil`.

Learn more: [Basic Types](/documentation/language/basic-types)
{% end %}

{% question(difficulty="easy") %}
Call `get` with `"hello"` and `1`. What comes back?
{% end %}
{% solution() %}
```phel
(get "hello" 1)
; => "e"
```
Strings are indexable. `get` returns the character at index `1` (zero-based).

Learn more: [Basic Types](/documentation/language/basic-types)
{% end %}

{% question(difficulty="easy") %}
Check whether `(+ 2 3)` equals `5`.
{% end %}
{% solution() %}
```phel
(= (+ 2 3) 5)
; => true
```
`=` compares values for equality and works across any types.

Learn more: [Truth and Boolean Operations](/documentation/language/truth-and-boolean-operations)
{% end %}

{% question(difficulty="easy") %}
Predict each result, then run them:
```phel
(string? "hello")
(int? 42)
(nil? nil)
(nil? 0)
```
{% end %}
{% solution() %}
```phel
(string? "hello") ; => true
(int? 42)         ; => true
(nil? nil)        ; => true
(nil? 0)          ; => false  (0 is not nil!)
```
Predicates return `true` or `false`. By convention, their names end with `?`.

Learn more: [Basic Types](/documentation/language/basic-types)
{% end %}

{% question(difficulty="easy") %}
Predict each result, then run them:
```phel
(not true)
(and true false)
(or false true)
(and (> 5 3) (< 10 20))
```
{% end %}
{% solution() %}
```phel
(not true)              ; => false
(and true false)        ; => false
(or false true)         ; => true
(and (> 5 3) (< 10 20)) ; => true
```
`and` returns the last truthy value or the first falsy one. `or` returns the first truthy value. They short-circuit, just like in PHP.

Learn more: [Truth and Boolean Operations](/documentation/language/truth-and-boolean-operations)
{% end %}

{% question(difficulty="easy") %}
What happens if you evaluate `(+ 1 "hello")`? Try it. Then inspect `(type 42)` and `(type "hi")`.
{% end %}
{% solution() %}
```phel
(+ 1 "hello") ; => Error! Numbers and strings don't add.
(type 42)     ; => :int
(type "hi")   ; => :string
```
Phel surfaces type errors loudly. `type` returns a keyword describing any value - useful when something behaves unexpectedly.
{% end %}
