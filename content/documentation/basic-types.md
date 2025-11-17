+++
title = "Basic Types"
weight = 2
+++

## Nil, True, False

Nil, true and false are literal constants.

```phel
nil
true
false
```

In Phel, only `false` and `nil` are falsy. Everything else is truthy—including `0`, `""`, and `[]`.

```phel
# Truthiness examples
(if nil "yes" "no")   # => "no"  (nil is falsy)
(if false "yes" "no") # => "no"  (false is falsy)
(if 0 "yes" "no")     # => "yes" (0 is truthy!)
(if "" "yes" "no")    # => "yes" (empty string is truthy!)
(if [] "yes" "no")    # => "yes" (empty vector is truthy!)
```

{% php_note() %}
In PHP, `nil` is the same as `null`, and `true`/`false` are the same. However, truthiness works differently:

**PHP**: `0`, `""`, `[]`, `null`, and `false` are all falsy
**Phel**: Only `false` and `nil` are falsy

This means `if (0)` in PHP is false, but `(if 0 ...)` in Phel is true!
{% end %}

{% clojure_note() %}
Truthiness is the same as Clojure—only `false` and `nil` are falsy.
{% end %}

## Symbol

Symbols are used to name functions and variables in Phel.

```phel
symbol
snake_case_symbol
my-module/my-function
λ
```

## Keywords

A keyword is like a symbol that begins with a colon character. However, it is used as a constant rather than a name for something. Keywords are interned and fast for equality checks.

```phel
:keyword
:range
:0x0x0x
:a-keyword
::
```

Keywords are commonly used as map keys:

```phel
# Map with keyword keys
{:name "Alice" :email "alice@example.com"}

# Accessing map values with keywords
(get {:name "Alice" :age 30} :name)  # => "Alice"
(:name {:name "Alice" :age 30})      # => "Alice" (keywords are functions!)
```

{% php_note() %}
Keywords are like string constants, but more efficient for map keys. Use keywords instead of strings for map keys:

```phel
# Less idiomatic:
{"name" "Alice" "age" 30}

# Idiomatic:
{:name "Alice" :age 30}
```

Keywords are interned (only one instance exists in memory), making equality checks very fast.
{% end %}

{% clojure_note() %}
Keywords work exactly like in Clojure—they're interned, fast for equality checks, and self-evaluate.
{% end %}

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

Strings are surrounded by double quotes. The dollar sign (`$`) does not need to be escaped.

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

"Unicodes can be encoded: \u{1000}"
```

String concatenation and conversion using `str`:

```phel
(str "Hello" " " "World")  # => "Hello World"
(str "The answer is " 42)  # => "The answer is 42"
```

{% php_note() %}
Phel strings are PHP strings internally, so you can use all PHP string functions:

```phel
(php/strlen "hello")                 # => 5
(php/strtoupper "hello")             # => "HELLO"
(php/str_replace "o" "0" "hello")    # => "hell0"
```

Strings work almost the same as PHP double-quoted strings, with one difference: the dollar sign (`$`) doesn't need escaping.
{% end %}

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

A map is a sequence of whitespace-separated key/value pairs surrounded by curly braces. The sequence is defined as key1, value1, key2, value2, etc. There must be an even number of items.

```phel
{} # same as (hash-map)
{:key1 "value1" :key2 "value2"}

# Any type can be a key
{'(1 2 3) '(4 5 6)}  # Lists as keys
{[] []}              # Vectors as keys
{1 2 3 4 5 6}        # Numbers as keys

# Common pattern: keywords as keys
{:name "Alice" :age 30 :email "alice@example.com"}
```

{% php_note() %}
Unlike PHP associative arrays, Phel maps:
- Can have **any type** as keys (not just strings/integers): vectors, lists, or even other maps
- Are **immutable**: operations return new maps without modifying the original
- Are **not** PHP arrays internally—they're their own data structure

```phel
# PHP:
$map = ['name' => 'Alice'];
$map['name'] = 'Bob';  // Mutates in place

# Phel:
(def map {:name "Alice"})
(def new-map (assoc map :name "Bob"))  # Returns new map
# map is still {:name "Alice"}
```
{% end %}

{% clojure_note() %}
Maps work exactly like Clojure maps, including support for any hashable type as keys.
{% end %}

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
