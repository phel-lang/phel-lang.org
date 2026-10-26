+++
title = "Basic Types"
weight = 1
description = "Phel's primitive values: nil, booleans, numbers, strings, keywords, plus truthiness, equality, and reader literals"
aliases = ["/documentation/basic-types", "/documentation/arithmetic", "/documentation/truth-and-boolean-operations"]

[extra]
difficulty = "beginner"
+++

## Nil, true, false

```phel
nil
true
false
```

`nil` means "no value", like PHP's `null`. Only `false` and `nil` are falsy. `0`, `""`, and `[]` are truthy, unlike in PHP. See [Truthiness](#truthiness).

## Numbers

Integers and floats are PHP's native `int` and `float`. Integers can be written in decimal, hex, binary, or octal. Hex, binary, and octal allow `_` separators:

```phel
1337            ; => 1337
-1337           ; => -1337
0x539           ; => 1337 (hex)
0b101_0011_1001 ; => 1337 (binary)
02471           ; => 1337 (octal)

1.234           ; => 1.234
1.2e3           ; => 1200.0
```

Phel also has exact types: ratios (`1/2`), BigDecimal (`1.5M`), and BigInt. See [Numeric tower](/documentation/language/numeric-tower/).

## Arithmetic operators

Phel uses prefix notation: the operator comes first, then the arguments. There is no operator precedence to remember:

```phel
;; 1 + (2 * 2) + (10 / 5) + 3 + 4 + (5 - 6)
(+ 1 (* 2 2) (/ 10 5) 3 4 (- 5 6)) ; => 13
```

Operators take any number of arguments:

```phel
(+)           ; => 0
(+ 1 2 3 4)   ; => 10
(- 5)         ; => -5 (negate)
(- 10 3 2)    ; => 5
(*)           ; => 1
(* 2 3 4)     ; => 24
(/ 24 4 2)    ; => 3
(/ 2)         ; => 1/2 (reciprocal)
```

Dividing two integers gives a `Ratio` when the result is not whole. Use a float operand, or `float`, when you want a float:

```phel
(/ 10 3)          ; => 10/3
(/ 10.0 3)        ; => 3.3333333333333
(float (/ 10 3))  ; => 3.3333333333333
```

`rem` keeps the sign of the dividend and `mod` the sign of the divisor: `(rem -7 2)` is `-1`, `(mod -7 2)` is `1`. `quot`, `**`, `inc`, `dec`, `min`, `max`, `floor`, `round`, and the bitwise functions (`bit-and`, `bit-or`, `bit-shift-left`, ...) are in the [core API](/documentation/reference/api/core/).

## Strings

Strings use double quotes. They can span lines. `$` needs no escaping:

```phel
"hello world"
"line one\nline two"
"escape a quote: \" and a hex char: \x41"
"unicode: \u{1000}"
"the dollar just works: $abc"
```

Build strings with `str`. It converts every argument to a string and skips `nil`:

```phel
(str "Hello" " " "World") ; => "Hello World"
(str "Total: " 42)        ; => "Total: 42"
(str "a" nil "b")         ; => "ab"
```

Strings are sequences of characters, with full UTF-8 support, so sequence functions work on them:

```phel
(count "hello")             ; => 5
(seq "abc")                 ; => ["a" "b" "c"]
(frequencies "abracadabra") ; => {"a" 5, "b" 2, "r" 2, "c" 1, "d" 1}
```

For upper case, split, join, trim, and the rest, use the `phel.string` module. See the [string API](/documentation/reference/api/string/). Every PHP string function is also available with the `php/` prefix, for example `(php/strlen "abc")`.

## Keywords

A keyword starts with `:`. It names a constant. Keywords are interned, so comparing two keywords is fast. Their main use is as map keys:

```phel
{:name "Alice" :age 30}

(get {:name "Alice" :age 30} :name) ; => "Alice"
(:name {:name "Alice" :age 30})     ; => "Alice"
```

A keyword is also a function: called with a map, it looks itself up.

{% <php_note> %}
Use keywords where PHP code uses string array keys or class constants. `{:name "Alice"}` is the idiomatic form of `['name' => 'Alice']`.
{% </php_note> %}

## Symbols

A symbol such as `valid?` or `my-module/my-function` names a function or a value. A `/` separates a namespace from a name. When Phel evaluates a symbol, it looks up the value bound to that name. Quote it (`'my-function`) to keep the symbol itself as data.

## Truthiness

Only `false` and `nil` are falsy. Every other value is truthy, including `0`, `""`, and empty collections:

```phel
(if 0 "yes" "no")   ; => "yes"
(if "" "yes" "no")  ; => "yes"
(if [] "yes" "no")  ; => "yes"
(if nil "yes" "no") ; => "no"
```

`truthy?` tests truthiness. `true?` and `false?` test for the exact values:

```phel
(truthy? 0)  ; => true
(true? 0)    ; => false
(false? nil) ; => false
```

{% <php_note> %}
In PHP, `0`, `""`, `"0"`, and `[]` are falsy. In Phel they are truthy. To test for an empty collection, use `empty?`. To test for zero, use `zero?`.
{% </php_note> %}

## Equality and comparison

`=` compares by value. Types must match, and collections are equal when their contents are equal:

```phel
(= 5 5)          ; => true
(= 5 "5")        ; => false
(= 5 5.0)        ; => false
(= [1 2] [1 2])  ; => true
(= {} {})        ; => true
(not= 1 2)       ; => true
```

Use `==` to compare numbers across types: `(== 5 5.0)` returns `true`.

`identical?` is stricter: it checks that two values are the same instance. Keywords and symbols with the same name are identical. Two collections are identical only when they are the same reference:

```phel
(identical? :a :a)   ; => true
(identical? [] [])   ; => false
```

Comparison operators take any number of arguments and check that the order holds across all of them:

```phel
(< 1 2 3)  ; => true
(< 1 3 2)  ; => false
(>= 5 5)   ; => true
```

{% <php_note> %}
`=` is not PHP's `==`. It never converts types. `identical?` is close to `===`. When you need PHP's loose comparison, call it directly: `(php/== 5 "5")` returns `true`.
{% </php_note> %}

## Logical operations

`and` returns the first falsy value, or the last value. `or` returns the first truthy value, or the last value. Both stop evaluating as soon as they know the answer:

```phel
(and true 5)   ; => 5
(and 1 nil 3)  ; => nil
(or nil 5)     ; => 5
(or false nil) ; => nil
(not 0)        ; => false
```

`(or x default)` is the usual way to give a fallback for a missing value.

## Comments

`;` starts a comment that runs to the end of the line. Use `;;` for a comment on its own line and `;` after code:

```phel
;; A standalone comment
(+ 1 2) ; an inline comment
```

`#_` skips the next form. Stack it to skip several:

```phel
[1 #_2 3]              ; => [1 3]
[#_#_:one :two :three] ; => [:three]
```

The [`comment`](/documentation/reference/api/core/#comment) macro ignores its body and returns `nil`. The body must still be valid Phel.

## Regex literals

`#"..."` is a PCRE pattern. You do not need to double the backslashes:

```phel
(re-find #"\d+" "abc123def")    ; => "123"
(re-find #"\d+" "no digits")    ; => nil
(re-matches #"\d+" "abc123")    ; => nil (the whole string must match)
(re-seq #"\d+" "a1b22c333")     ; => ["1" "22" "333"]

(re-find #"(\d+)-(\d+)" "date: 2026-04-03")
; => ["2026-04" "2026" "04"]
```

With capture groups, the result is a vector: the full match, then each group.

{% <clojure_note> %}
Same `#"..."` syntax as Clojure. The engine is PHP PCRE, not Java regex, so some details differ.
{% </clojure_note> %}

## Tagged literals

`#inst`, `#uuid`, `#php`, and your own tags are covered in [Reader shortcuts](/documentation/language/reader-shortcuts/#tagged-literals-tag-form).
