+++
title = "Basic Types"
weight = 1
description = "Phel's primitive values: nil, booleans, numbers, strings, keywords, plus truthiness, equality, and reader literals"
aliases = ["/documentation/basic-types", "/documentation/arithmetic", "/documentation/truth-and-boolean-operations"]
+++

The building blocks of every Phel program: literals, numbers, strings, keywords, and the truthiness rules that differ from PHP.

## Nil, true, false

Literal constants:

```phel
nil
true
false
```

Only `false` and `nil` are falsy: `0`, `""`, and `[]` are all truthy (unlike PHP, where they are falsy). See [Truthiness](#truthiness) for the predicates and the full PHP comparison.

## Symbol

Names functions and variables:

<!-- phel-test: skip -->
```phel
symbol
snake_case_symbol
my-module/my-function
λ
```

## Keywords

Like a symbol but starts with `:`. Used as a constant. Interned, fast equality.

```phel
:keyword
:range
:0x0x0x
:a-keyword
::
```

Common as map keys:

```phel
;; Map with keyword keys
{:name "Alice" :email "alice@example.com"}

; Accessing map values with keywords
(get {:name "Alice" :age 30} :name)  ; => "Alice"
(:name {:name "Alice" :age 30})      ; => "Alice" (keywords are functions!)
```

{% php_note() %}
Like string constants, more efficient as map keys. Prefer over strings:

```phel
; Less idiomatic:
{"name" "Alice" "age" 30}

; Idiomatic:
{:name "Alice" :age 30}
```

Interned: one instance in memory, fast equality.
{% end %}

## Numbers

Integers, floats, ratios, big integers, big decimals. Integers and floats wrap PHP's natives. Integers in decimal, hex, octal, binary. Binary/octal/hex may use `_` separators.

```phel
1337 ; integer
+1337 ; positive integer
-1337 ; negative integer

1.234 ; float
+1.234 ; positive float
-1.234 ; negative float
1.2e3 ; float
7E-10 ; float

0b10100111001 ; binary number
+0b10100111001 ; positive binary number
-0b10100111001 ; negative binary number
0b101_0011_1001 ; binary number with underscores for better readability

0x539 ; hexadecimal number
+0x539 ; positive hexadecimal number
-0x539 ; negative hexadecimal number
-0x5_39 ; hexadecimal number with underscores

02471 ; octal number
+02471 ; positive octal number
-02471 ; negative octal number
024_71 ; octal number with underscores
```

### Ratios, BigInt, BigDecimal

```phel
1/2          ; Ratio
-3/4         ; Ratio
(/ 10 3)     ; => 10/3 (int / int with non-integer result returns Ratio)
(numerator 1/2)    ; => 1
(denominator 1/2)  ; => 2

(bigint "100000000000000000000")  ; BigInt from string
(bigint? 1N)                       ; predicate

1.5M         ; BigDecimal literal (M suffix)
1.5e3M       ; BigDecimal exponent
(bigdec "0.1")
(bigdec? 1.5M)  ; => true
```

Auto-promoting variants `+'`, `-'`, `*'`, `inc'`, `dec'` widen to BigInt on overflow instead of wrapping.

## Arithmetic operators

Prefix notation:

```phel
;; 1 + (2*2) + (10/5) + 3 + 4 + (5 - 6)
(+ 1 (* 2 2) (/ 10 5) 3 4 (- 5 6)) ; => 13
```

{% php_note() %}
Prefix notation (operator first) instead of PHP's infix:

```php
// PHP - infix notation
1 + (2 * 2) + (10 / 5) + 3 + 4 + (5 - 6);

// Phel - prefix notation
(+ 1 (* 2 2) (/ 10 5) 3 4 (- 5 6))
```

Operators take any number of args, no precedence concerns.
{% end %}

Operators take zero, one, or many args:

```phel
(+) ; => 0
(+ 1) ; => 1
(+ 1 2) ; => 3
(+ 1 2 3 4 5 6 7 8 9) ; => 45

(-) ; => 0
(- 1) ; => -1
(- 2 1) ; => 1
(- 3 2 1) ; => 0

(*) ; => 1
(* 2) ; => 2
(* 2 3 4) ; => 24

(/) ; => 1
(/ 2) ; => 1/2 (reciprocal as Ratio)
(/ 24 4 2) ; => 3
(/ 10 3)   ; => 10/3 (Ratio, exact)
```

`(/ int int)` with a non-integer result returns a `Ratio`, not a float. Coerce with `float` or `(/ 10.0 3)` if you need a float.

{% php_note() %}
Variadic operators are more flexible than PHP's:

```php
// PHP - requires at least two operands
1 + 2 + 3 + 4 + 5;
// Can't do this: +();  <- syntax error

// Phel - supports 0, 1, or many operands
(+)                     ; 0 (identity)
(+ 1)                   ; 1 (identity)
(+ 1 2 3 4 5)          ; 15 (sum of all)
```

**Patterns:**
- `(+)` additive identity (0)
- `(*)` multiplicative identity (1)
- `(- x)` negate
- `(/ x)` reciprocal
{% end %}

Other numerics:

- `quot`, `rem`, `mod`: integer quotient, remainder, modulo. `%` aliases `rem`.
- `floor`, `ceil`, `round`, `sqrt`: math primitives.
- `**`: power.
- `+'`, `-'`, `*'`, `inc'`, `dec'`: auto-promote to `BigInt` on overflow.
- `numerator`, `denominator`, `rationalize`, `ratio?`.
- `bigint`, `biginteger`, `bigint?`; `bigdec`, `bigdec?` / `decimal?`.

Full list in the [API docs](/documentation/reference/api/core/).

Some operations yield NaN. Phel uses `NAN` constant. Check with `nan?`:

```phel
(nan? 1) ; false
(nan? (php/log -1)) ; true
(nan? NAN) ; true
```

{% php_note() %}
NaN handling matches PHP:

```php
// PHP
is_nan(1);           // false
is_nan(log(-1));     // true
is_nan(NAN);         // true

// Phel
(nan? 1)             ; false
(nan? (php/log -1))  ; true
(nan? NAN)           ; true
```

`%` remainder and `**` exponent match PHP's.
{% end %}

## Bitwise operators

Manipulate bits in integers.

```phel
;; Bitwise and
(bit-and 0b1100 0b1001) ; => 8 (0b1000)

;; Bitwise or
(bit-or 0b1100 0b1001) ; => 13 (0b1101)

;; Bitwise xor
(bit-xor 0b1100 0b1001) ; => 5 (0b0101)

;; Bitwise complement
(bit-not 0b0111) ; => -8

;; Shifts bit n steps to the left
(bit-shift-left 0b1101 1) ; => 26 (0b11010)

;; Shifts bit n steps to the right
(bit-shift-right 0b1101 1) ; => 6 (0b0110)

;; Set bit at index n
(bit-set 0b1011 2) ; => 15 (0b1111)

;; Clear bit at index n
(bit-clear 0b1011 3) ; => 3 (0b0011)

;; Flip bit at index n
(bit-flip 0b1011 2) ; => 15 (0b1111)

;; Test bit at index n
(bit-test 0b1011 0) ; => true
(bit-test 0b1011 2) ; => false
```

{% php_note() %}
Named functions instead of PHP operators:

```php
// PHP bitwise operators
0b1100 & 0b1001;      // AND
0b1100 | 0b1001;      // OR
0b1100 ^ 0b1001;      // XOR
~0b0111;              // NOT
0b1101 << 1;          // Left shift
0b1101 >> 1;          // Right shift

// Phel named functions
(bit-and 0b1100 0b1001)
(bit-or 0b1100 0b1001)
(bit-xor 0b1100 0b1001)
(bit-not 0b0111)
(bit-shift-left 0b1101 1)
(bit-shift-right 0b1101 1)
```

Adds extra functions not in PHP: `bit-set`, `bit-clear`, `bit-flip`, `bit-test`.
{% end %}

## Strings

Double-quoted. `$` doesn't need escaping.

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

Concat and convert with `str`:

```phel
(str "Hello" " " "World")  ; => "Hello World"
(str "The answer is " 42)  ; => "The answer is 42"
```

Strings are iterable: work with `map`, `filter`, `count`, `frequencies`, `foreach`. Full UTF-8 / multibyte support:

```phel
(count "hello")             ; => 5
(frequencies "abracadabra") ; => {a 5, b 2, r 2, c 1, d 1}
(seq "abc")                 ; => [a b c]
```

{% php_note() %}
PHP strings internally. Use `phel.string` for idiomatic string operations:

```phel
(ns example
  (:require phel.string :as str))

(count "hello")                      ; => 5
(str/upper-case "hello")             ; => "HELLO"
(str/replace "hello" "o" "0")        ; => "hell0"
```

All PHP string functions also available via `php/` prefix. Same as PHP double-quoted strings, except `$` doesn't need escaping.
{% end %}

## Lists

Whitespace-separated values in parentheses:

```phel
(do 1 2 3)
```

Lists are function/macro/special-form calls. Quoted lists are data:

```phel
'(1 2 3)
```

## Vectors

Whitespace-separated values in brackets:

```phel
[1 2 3] ; same as (vector 1 2 3)
```

Indexed data structure. Unlike PHP arrays, vectors are not maps/hashtables.

## Maps

Whitespace-separated key/value pairs in braces. Even count: key1, value1, key2, value2.

```phel
{} ; same as (hash-map)
{:key1 "value1" :key2 "value2"}

; Any type can be a key
{'(1 2 3) '(4 5 6)}  ; Lists as keys
{[] []}              ; Vectors as keys
{1 2 3 4 5 6}        ; Numbers as keys

; Common pattern: keywords as keys
{:name "Alice" :age 30 :email "alice@example.com"}
```

{% php_note() %}
Unlike PHP associative arrays, Phel map keys can be **any type** (vectors, lists, other maps), maps are **immutable** (operations return new maps), and they are **not** PHP arrays internally. Worked comparison in [Data structures → Immutability](/documentation/language/data-structures/#immutability-vs-php-mutability).
{% end %}

## Sets

Whitespace-separated values in `#{}`, or built with `hash-set`:

```phel
#{1 2 3}         ; set literal
(hash-set 1 2 3) ; same result
(set [1 2 3])    ; coerce a collection to a set
```

## Queues

Persistent FIFO queues with amortised O(1) `push`, `peek`, `pop`:

```phel
(def q (queue 1 2 3))
(queue? q)        ; => true
(peek q)          ; => 1
(push q 4)        ; => <-(1 2 3 4)-<
(pop q)           ; => <-(2 3)-<
```

## Map entries

`map-entry` produces an entry that compares equal to a 2-element vector. `seq` over a map yields map entries:

```phel
(def e (map-entry :a 1))
(map-entry? e)    ; => true
(key e)           ; => :a
(val e)           ; => 1
(= e [:a 1])      ; => true
```

## Tagged literals

Reader tags for common values:

```phel
#inst "2026-04-20T12:00:00Z"      ; => \DateTimeImmutable
#regex "\\d+"                      ; => PCRE pattern string (delimited)
#uuid "550e8400-e29b-41d4-a716-446655440000"
```

### Custom tags

Register with `register-tag`:

```phel
(ns my-app.readers
  (:require phel.reader :refer [register-tag]))

(register-tag "money" (fn [[amount currency]]
                        {:amount amount :currency currency}))
```

In any source file:

<!-- phel-test: skip -->
```phel
#money [100 "EUR"]   ; => {:amount 100 :currency "EUR"}
```

A `data-readers.phel` at any source root auto-loads. Ship tag definitions with your library.

## PHP reader literals

Native PHP arrays inline without `php/array`:

```phel
#php [1 2 3]          ; expands to (php-indexed-array 1 2 3)
#php {"a" 1 "b" 2}    ; expands to (php-associative-array "a" 1 "b" 2)
```

Non-recursive expansion. Nested Phel forms stay Phel data.

## Regex literals

`#"..."` is reader sugar for PCRE patterns:

```phel
#"\d+"           ; Matches one or more digits
#"[a-zA-Z]+"     ; Matches one or more letters
#"hello\s+world" ; Matches "hello" followed by whitespace and "world"
```

Use with `re-find` and `re-matches`:

```phel
(re-find #"\d+" "abc123def")     ; => "123"
(re-find #"\d+" "no digits")     ; => nil

(re-matches #"\d+" "123")        ; => "123" (full string must match)
(re-matches #"\d+" "abc123")     ; => nil (not a full match)

; Capture groups return vectors
(re-find #"(\d+)-(\d+)" "date: 2026-04-03")
; => ["2026-04" "2026" "04"]
```

{% clojure_note() %}
Same `#"..."` syntax as Clojure. Engine is PHP PCRE, not Java regex, so some details differ.
{% end %}

## Anonymous function shorthand

`#(...)` defines an inline anonymous function, using `%`/`%1`/`%2`/`%&` for positional arguments: `#(* % 2)` is the same as `(fn [x] (* x 2))`. Full rules and the deprecated `|(...)` form live in [Functions and Recursion](/documentation/language/functions-and-recursion/#anonymous-function-fn).

## Deref shorthand

`@x` is shorthand for `(deref x)`, reading the current value of an atom or other reference type. Atom mechanics (`swap!`, `reset!`, the `!` convention) live in [Global and local bindings](/documentation/language/global-and-local-bindings/#atoms).

## Comments

`;` runs to end of line. `;;` for standalone, `;` for inline:

```phel
;; This is a standalone comment
(+ 1 2) ; This is an inline comment
```

> **Deprecation:** `#` line and `#| ... |#` multiline comments are deprecated. Use `;` and `;;`. `#` prefix is reserved for reader macros (`#()`, `#""`, `#?()`).

`#_` comments out the next form. Stack to comment multiple forms:

```phel
[:one :two :three]     ; => [:one :two :three]
[#_:one :two :three]   ; => [:two :three]
[#_:one :two #_:three] ; => [:two]
[#_#_:one :two :three] ; => [:three]
```

See [comment](/documentation/reference/api/core/#comment) macro: ignores forms, returns `nil`, still requires valid Phel code.

## Truthiness

Only `false` and `nil` are falsy. `truthy?` checks truthiness. `true?` and `false?` check for the exact values.

```phel
(truthy? false) ; => false
(truthy? nil)   ; => false
(truthy? true)  ; => true
(truthy? 0)     ; => true
(truthy? -1)    ; => true

(true? true)    ; => true
(true? false)   ; => false
(true? 0)       ; => false

(false? false)  ; => true
(false? true)   ; => false
(false? 0)      ; => false
```

{% php_note() %}
This is **different from PHP** where `0`, `""`, `[]`, and `null` are all falsy.

```php
// PHP
if (0) { }        // false - won't execute
if ("") { }       // false - won't execute
if ([]) { }       // false - won't execute

// Phel
(if 0 "yes" "no")   ; => "yes" - 0 is truthy!
(if "" "yes" "no")  ; => "yes" - "" is truthy!
(if [] "yes" "no")  ; => "yes" - [] is truthy!
```
{% end %}

## Identity vs equality

`identical?` returns `true` if two values are identical. Stricter than equality: types match, then values. Keywords/symbols with same name always identical. Lists, vectors, maps, sets identical only if same reference.

```phel
(identical? true true)   ; => true
(identical? true false)  ; => false
(identical? 5 "5")       ; => false
(identical? :test :test) ; => true
(identical? 'sym 'sym)   ; => true
(identical? '() '())     ; => false
(identical? [] [])       ; => false
(identical? {} {})       ; => false
```

`=` checks equality: same type and value. Collections equal if values match (no reference check).

```phel
(= true true)  ; => true
(= 5 "5")      ; => false
(= 5 5)        ; => true
(= 5 5.0)      ; => false
(= :test :test) ; => true
(= [] [])      ; => true
(= {} {})      ; => true
```

Use `not=` for inequality.

{% php_note() %}
- `identical?` like `===` (strict, with Phel types)
- `=` is **not** like `==` (loose equality)
- `=` compares structurally with type checking

PHP loose equality: use `php/==`:

```phel
(php/== 5 "5")   ; => true (PHP loose equality)
(= 5 "5")        ; => false (Phel structural equality)
(identical? 5 5) ; => true (Phel identity)
```
{% end %}

## Comparisons

All comparison operators accept multiple arguments:

```phel
(< 1 2)     ; => true
(< 1 2 3)   ; => true  (1 < 2 and 2 < 3)
(< 1 3 2)   ; => false (3 is not < 2)
(>= 5 5)    ; => true
(> 3 2 1)   ; => true  (3 > 2 and 2 > 1)
```

## Logical operations

`and` evaluates left-to-right. Returns first falsy value or the last value. No args returns `true`.

```phel
(and)        ; => true
(and 1)      ; => 1
(and false)  ; => false
(and true 5) ; => 5
```

`or` evaluates left-to-right. Returns first truthy value or the last value. No args returns `nil`.

```phel
(or)          ; => nil
(or 1)        ; => 1
(or false 5)  ; => 5
```

`not` returns `true` for falsy values, `false` otherwise.

```phel
(not 1)     ; => false
(not false) ; => true
(not nil)   ; => true
```

## Next steps

- [Data structures](/documentation/language/data-structures/) - lists, vectors, maps, and sets in depth
- [Control flow](/documentation/language/control-flow/) - put truthiness to work with `if`, `cond`, and `case`
- [Cheat sheet](/documentation/reference/cheat-sheet/) - keep it open while coding
