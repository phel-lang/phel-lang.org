+++
title = "Basic Types"
weight = 2
+++

## Nil, True, False

Nil, true and false are literal constants. In Phel, `nil` is the same as `null` in PHP. Phel's `true` and `false` are the same as PHP's `true` and `false`.

```phel
nil
true
false
```

## Symbol

Symbols are used to name functions and variables in Phel.

```phel
symbol
snake_case_symbol
my-module/my-function
λ
```

## Keywords

A keyword is like a symbol that begins with a colon character. However, it is used as a constant rather than a name for something.

```phel
:keyword
:range
:0x0x0x
:a-keyword
::
```

## Numbers

Phel supports integers and floating-point numbers. Both use the underlying PHP implementation. Integers can be specified in decimal (base 10), hexadecimal (base 16), octal (base 8) and binary (base 2) notations. Binary, octal and hexadecimal formats may contain underscores (`_`) between digits for better readability.

```phel
1337 # integer
+1337 # positive integer
-1337 # negative integer

1.234 # float
+1.234 # positive float
-1.234 # negative float
1.2e3 # float
7E-10 # float

0b10100111001 # binary number
+0b10100111001 # positive binary number
-0b10100111001 # negative binary number
0b101_0011_1001 # binary number with underscores for better readability

0x539 # hexadecimal number
+0x539 # positive hexadecimal number
-0x539 # negative hexadecimal number
-0x5_39 # hexadecimal number with underscores

02471 # octal number
+02471 # positive octal number
-02471 # negative octal number
024_71 # octal number with underscores
```

## Strings

Strings are surrounded by double quotes. They almost work the same as PHP double-quoted strings. One difference is that the dollar sign (`$`) must not be escaped. Internally, Phel strings are represented by PHP strings. Therefore, every PHP string function can be used to operate on the string.

Strings can be written over multiple lines. The line break character is then ignored by the reader.

```phel
"hello world"

"this is\na\nstring"

"this
is
a
string."

"use backslash to escape \" string"

"the dollar must not be escaped: $ or $abc just works"

"Hexadecimal notation is supported: \x41"

"Unicodes can be encoded as in PHP: \u{1000}"
```

## Lists

A list is a sequence of whitespace-separated values surrounded by parentheses.

```phel
(do 1 2 3)
```

A list will be interpreted as a function call, a macro call or a special form by the compiler. A list prefixed with a single quote will be interpreted as data.

```phel
'(1 2 3)
```

## Vectors

A vector is a sequence of whitespace-separated values surrounded by brackets.

```phel
[1 2 3] # same as (vector 1 2 3)
```

A vector in Phel is an indexed data structure. In contrast to PHP arrays, Phel vectors cannot be used as maps, hashtables or dictionaries.

## Maps

A map is a sequence of whitespace-separated key/value pairs surrounded by curly braces, wherein the key and value of each key/value pair are separated by whitespace. There must be an even number of items between curly braces or the parser will signal a parse error. The sequence is defined as key1, value1, key2, value2, etc.

```phel
{} # same as (hash-map)
{:key1 "value1" :key2 "value2"}
{'(1 2 3) '(4 5 6)}
{[] []}
{1 2 3 4 5 6}
```

In contrast to PHP associative arrays, Phel maps can have any types of keys.

## Sets

A set is a sequence of whitespace-separated values prefixed by the function `set` and the whole being surrounded by parentheses.

```phel
#{1 2 3} # same as (set 1 2 3)
```

## Comments

A comment begins with a `#` or `;` character and continues until the end of the line.

```phel
# This is a comment
; This is also a comment
```

Phel also supports multiline comments using the Common Lisp `#|` ... `|#` syntax. The comment spans everything between the opening and closing markers, including line breaks.

```phel
#|
This whole block
is a comment
|#
```

Phel also supports inline s-expression commenting with `#_` which comments out the next form. It can also be stacked to comment out two or more forms after it.

```phel
[:one :two :three]     # results to [:one :two :three]
[#_:one :two :three]   # results to [:two :three]
[#_:one :two #_:three] # results to [:two]
[#_#_:one :two :three] # results to [:three]
```

See also the [comment](/documentation/api/#comment) macro which ignores the forms inside and returns `nil` while still requiring the content to be valid Phel code.
