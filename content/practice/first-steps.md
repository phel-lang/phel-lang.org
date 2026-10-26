+++
title = "First Steps"
weight = 1
description = "Your first contact with a Lisp: read a form, do math without precedence rules, build strings, compare values, and read an error message."
aliases = ["/practice/basic/"]

[extra]
stage = "Foundations"
goals = [
  "Read a form: the first item is the operation, the rest are its arguments",
  "Write nested math without precedence rules",
  "Build strings with `str` and print them with `println`",
  "Compare values with `=`, `<`, and predicates like `nil?`",
  "Read an error message and use it to fix your code",
]
read_first = [
  ["Phel in 5 Minutes", "/documentation/phel-in-5-minutes/"],
  ["Basic Types", "/documentation/language/basic-types/"],
]
recap = [
  "You can read any Phel form from the inside out",
  "You can turn a PHP expression like `2 + 3 * 4` into prefix notation",
  "You know the basic values: numbers, strings, keywords, `nil`, `true`, and `false`",
  "You can ask questions about values with `=`, comparisons, predicates, and `type`",
  "You can read an error message, find the line, and fix the cause",
]
+++

This is your first contact with a Lisp. The syntax looks strange for about ten minutes, then it becomes the simplest syntax you have used. Open a [REPL](/documentation/tooling/repl/) (or [the browser REPL](/repl/)) and type every example. Guess first, then run: a wrong guess teaches you more than a right one.

## Reading a form

Phel code is made of **forms**. A form in parentheses is a call: the first item is the operation, and the rest are its arguments. There are no commas and no operator precedence.

```phel
(+ 1 2) ; => 3
```

Read it as "add 1 and 2". In PHP you would write `1 + 2`.

{% <question difficulty="easy" kind="predict"> %}
What does this return?
```phel
(+ 1 2 3 4)
```
{% </question> %}
{% <solution> %}
```phel
(+ 1 2 3 4) ; => 10
```
`+` takes any number of arguments. In PHP you would need three `+` signs; in Phel you write the operation once.
{% </solution> %}

{% <question difficulty="easy" kind="fill"> %}
Replace `___` so the form returns `42`.
<!-- phel-test: skip -->
```phel
(* 6 ___)
```
{% </question> %}
{% <solution> %}
```phel
(* 6 7) ; => 42
```
`*` multiplies all its arguments.
{% </solution> %}

{% <question difficulty="easy" kind="predict"> %}
Forms can contain other forms. Phel evaluates the inner form first, then uses its result. What does this return?
```phel
(* 2 (+ 3 4))
```
{% </question> %}
{% <solution> %}
```phel
(* 2 (+ 3 4)) ; => 14
```
Read from the inside out: `(+ 3 4)` becomes `7`, then `(* 2 7)` becomes `14`.
{% </solution> %}

## Nesting instead of precedence

PHP has rules that say `*` runs before `+`. Phel has no such rules. The parentheses show the order, so you never have to remember it.

```phel
(+ 2 (* 3 4)) ; PHP: 2 + 3 * 4   => 14
(* (+ 2 3) 4) ; PHP: (2 + 3) * 4 => 20
```

{% <question difficulty="easy" kind="write"> %}
Write this PHP expression as a Phel form:
```
(3 + 4.0 / 5) * 6
```
The result should be `22.8`.
{% </question> %}
{% <hint> %}
Start with the innermost part, `4.0 / 5`, and wrap outwards.
{% </hint> %}
{% <solution> %}
```phel
(* (+ 3 (/ 4.0 5)) 6) ; => 22.8
```
Each pair of parentheses is one step. `(/ 4.0 5)` is `0.8`, `(+ 3 0.8)` is `3.8`, and `(* 3.8 6)` is `22.8`.

Learn more: [Arithmetic operators](/documentation/language/basic-types/#arithmetic-operators)
{% </solution> %}

## Numbers

Phel has integers (`42`), floats (`4.2`), and **rationals** (`1/2`). Dividing two integers does not lose precision: when the result is not a whole number, you get an exact fraction.

```phel
(/ 1 3)     ; => 1/3
(+ 1/2 1/4) ; => 3/4
```

{% <question difficulty="easy" kind="predict"> %}
Predict each result, then run them one by one.
```phel
(/ 10 5)
(/ 10 4)
(/ 10.0 4)
```
{% </question> %}
{% <solution> %}
```phel
(/ 10 5)   ; => 2
(/ 10 4)   ; => 5/2
(/ 10.0 4) ; => 2.5
```
`10 / 4` is not a whole number, so Phel keeps it as the exact fraction `5/2`. PHP would give you the float `2.5`. Put a float in (`10.0`) and you get a float out.

Learn more: [Numbers](/documentation/language/basic-types/#numbers)
{% </solution> %}

## Strings and printing

Strings use double quotes. `str` joins any number of values into one string. `println` writes a line to the screen.

```phel
(str "Phel " "is " "fun") ; => "Phel is fun"
(println "Hi")            ; prints Hi, returns nil
```

{% <question difficulty="easy" kind="fill"> %}
Replace `___` so the form returns `"Hello, Phel"`.
<!-- phel-test: skip -->
```phel
(str "Hello" ___ "Phel")
```
{% </question> %}
{% <solution> %}
```phel
(str "Hello" ", " "Phel") ; => "Hello, Phel"
```
`str` glues its arguments together in order. In PHP you would write `"Hello" . ", " . "Phel"`.
{% </solution> %}

{% <question difficulty="easy" kind="predict"> %}
`str` also accepts values that are not strings. What does this print, and what does the whole form return?
```phel
(println (str "Total: " (+ 1 2)))
```
{% </question> %}
{% <hint> %}
There are two answers: what shows on the screen, and the value of the `println` form itself.
{% </hint> %}
{% <solution> %}
```phel
(println (str "Total: " (+ 1 2)))
; prints: Total: 3
; => nil
```
`str` turns the number `3` into text. `println` prints the line, then returns `nil`, which means "no value". Printing is a side effect; the return value is something else.
{% </solution> %}

## Keywords, nil, and booleans

A **keyword** starts with a colon, like `:admin`. It is a name that stands for itself, often used as a label. `nil` means "nothing". `true` and `false` are booleans. `type` tells you what kind of value you have, and it answers with a keyword.

```phel
(type "hi") ; => :string
```

{% <question difficulty="easy" kind="predict"> %}
Predict the result of each form.
```phel
(type 42)
(type 4.2)
(type 1/2)
(type :admin)
(type nil)
(type false)
```
{% </question> %}
{% <solution> %}
```phel
(type 42)     ; => :int
(type 4.2)    ; => :float
(type 1/2)    ; => :ratio
(type :admin) ; => :keyword
(type nil)    ; => :nil
(type false)  ; => :boolean
```
When a value behaves in a way you do not expect, `type` is the first thing to check.

Learn more: [Keywords](/documentation/language/basic-types/#keywords)
{% </solution> %}

## Comparing values

`=` checks if values are equal. `<`, `>`, `<=`, and `>=` compare numbers. Like `+`, they accept more than two arguments. `not=` is the opposite of `=`.

```phel
(= 5 (+ 2 3)) ; => true
(< 1 2)       ; => true
```

{% <question difficulty="medium" kind="predict"> %}
Predict each result. Two of them may surprise a PHP developer.
```phel
(= "abc" "abc")
(= 1 1.0)
(= "1" 1)
(< 1 2 3)
(< 1 3 2)
(not= :a :b)
```
{% </question> %}
{% <hint> %}
`(< a b c)` asks "is each value smaller than the next one?". And `=` never converts types.
{% </hint> %}
{% <solution> %}
```phel
(= "abc" "abc") ; => true
(= 1 1.0)       ; => false
(= "1" 1)       ; => false
(< 1 2 3)       ; => true
(< 1 3 2)       ; => false
(not= :a :b)    ; => true
```
`=` is strict, like PHP's `===`: an int and a float are different values, and so are a string and a number. `(< 1 2 3)` checks the whole chain, so `(< 1 3 2)` is false because `3` is not smaller than `2`.

Learn more: [Equality and comparison](/documentation/language/basic-types/#equality-and-comparison)
{% </solution> %}

## Asking questions with predicates

A **predicate** is a function that answers yes or no. By convention its name ends with `?`.

```phel
(string? "hi") ; => true
(int? "42")    ; => false
```

{% <question difficulty="medium" kind="predict"> %}
Predict each result.
```phel
(nil? nil)
(nil? 0)
(nil? false)
(int? 4.0)
(float? 4.0)
(keyword? :a)
```
{% </question> %}
{% <hint> %}
`nil?` is true for one value only.
{% </hint> %}
{% <solution> %}
```phel
(nil? nil)    ; => true
(nil? 0)      ; => false
(nil? false)  ; => false
(int? 4.0)    ; => false
(float? 4.0)  ; => true
(keyword? :a) ; => true
```
`0` and `false` are values, not "nothing", so they are not `nil`. And `4.0` is a float even though it looks whole.

Learn more: [Nil, true, false](/documentation/language/basic-types/#nil-true-false)
{% </solution> %}

{% <question difficulty="medium" kind="write"> %}
Write one form that returns `true` when the result of `(/ 10 4)` is a rational number. Use `type`, `=`, and a keyword.
{% </question> %}
{% <hint> %}
You already know what `(type 1/2)` returns. Compare against that keyword.
{% </hint> %}
{% <solution> %}
```phel
(= (type (/ 10 4)) :ratio) ; => true
```
Inside out: `(/ 10 4)` is `5/2`, `type` gives `:ratio`, and `=` compares two keywords. Three small forms make one question.
{% </solution> %}

## Reading errors

Errors are part of the work. A Phel error tells you three things: **what** went wrong, **where** (file and line), and often a **hint** for the fix. Read the first line first; it is usually enough.

```
[PHEL100] Unterminated list starting at line 2. Did you forget a closing ')'?
```

The next three exercises break on purpose. Run each one, read the message, then fix the code.

{% <question difficulty="medium" kind="fix"> %}
This form has a typo. Run it and read the error. What does Phel suggest? Fix the form.
<!-- phel-test: skip -->
```phel
(prinltn "Hello, Phel")
```
{% </question> %}
{% <hint> %}
Look for the words "Did you mean".
{% </hint> %}
{% <solution> %}
Phel says:
```
[PHEL001] Cannot resolve symbol 'prinltn'. Did you mean 'print', 'printf', or 'println'?
```
```phel
(println "Hello, Phel")
```
"Cannot resolve symbol" means Phel does not know that name. It points at the exact spot with `^^^` and lists names that look close.
{% </solution> %}

{% <question difficulty="medium" kind="fix"> %}
A PHP developer wrote this. Run it and read the error. Why is `1` in the message? Fix the form.
<!-- phel-test: skip -->
```phel
(1 + 2)
```
{% </question> %}
{% <hint> %}
Which item in a form does Phel treat as the operation?
{% </hint> %}
{% <solution> %}
Phel says:
```
[PHEL011] Value 1 of type int is not callable.
```
```phel
(+ 1 2) ; => 3
```
Phel always calls the first item of a form. Here the first item is `1`, and a number cannot be called. Move the operation to the front.
{% </solution> %}

{% <question difficulty="hard" kind="fix"> %}
This should return `"Total: 5"`, but it fails. Run it, read the error, and fix it.
<!-- phel-test: skip -->
```phel
(+ "Total: " 5)
```
{% </question> %}
{% <hint> %}
The message says what `+` expected. Which function from this module joins text?
{% </hint> %}
{% <solution> %}
Phel says:
```
Expected a number, got string
```
```phel
(str "Total: " 5) ; => "Total: 5"
```
`+` is only for numbers, and Phel does not convert types for you. To build text, use `str`, the way you would use `.` in PHP.

Learn more: [Strings](/documentation/language/basic-types/#strings)
{% </solution> %}
