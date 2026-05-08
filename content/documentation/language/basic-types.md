+++
title = "Basic Types"
weight = 1
aliases = ["/documentation/basic-types"]
+++

## Nil, true, false

Literal constants:

```phel
nil
true
false
```

Only `false` and `nil` are falsy. `0`, `""`, `[]` truthy.

```phel
;; Truthiness examples
(if nil "yes" "no")   ; => "no"  (nil is falsy)
(if false "yes" "no") ; => "no"  (false is falsy)
(if 0 "yes" "no")     ; => "yes" (0 is truthy!)
(if "" "yes" "no")    ; => "yes" (empty string is truthy!)
(if [] "yes" "no")    ; => "yes" (empty vector is truthy!)
```

{% php_note() %}
`nil` = PHP `null`. `true`/`false` same. But truthiness differs:

**PHP**: `0`, `""`, `[]`, `null`, `false` falsy.
**Phel**: only `false`, `nil` falsy.

`if (0)` in PHP is false, but `(if 0 ...)` in Phel is true.
{% end %}

## Symbol

Names functions and variables:

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

### Ratios, BigInteger, BigDecimal

```phel
1/2          ; Rational
-3/4         ; Rational
(/ 10 3)     ; => 10/3 (int / int with non-integer result returns Rational)
(numerator 1/2)    ; => 1
(denominator 1/2)  ; => 2

(bigint "100000000000000000000")  ; BigInteger from string
(bigint? 1N)                       ; predicate

1.5M         ; BigDecimal literal (M suffix)
1.5e3M       ; BigDecimal exponent
(bigdec "0.1")
(bigdec? 1.5M)  ; => true
```

Auto-promoting variants `+'`, `-'`, `*'`, `inc'`, `dec'` widen to BigInteger on overflow instead of wrapping. See [Arithmetic](/documentation/language/arithmetic/).

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
(frequencies "abracadabra") ; => {"a" 5 "b" 2 "r" 2 "c" 1 "d" 1}
(seq "abc")                 ; => ("a" "b" "c")
```

{% php_note() %}
PHP strings internally. All PHP string functions work:

```phel
(php/strlen "hello")                 ; => 5
(php/strtoupper "hello")             ; => "HELLO"
(php/str_replace "o" "0" "hello")    ; => "hell0"
```

Same as PHP double-quoted strings, except `$` doesn't need escaping.
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
Unlike PHP associative arrays, Phel maps:
- **Any type** as keys: vectors, lists, other maps
- **Immutable**: operations return new maps
- **Not** PHP arrays internally

```phel
; PHP:
$map = ['name' => 'Alice'];
$map['name'] = 'Bob';  // Mutates in place

; Phel:
(def map {:name "Alice"})
(def new-map (assoc map :name "Bob"))  ; Returns new map
; map is still {:name "Alice"}
```
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
(push q 4)        ; => queue 1 2 3 4
(pop q)           ; => queue 2 3
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

`#(...)` defines anonymous functions inline. `%` placeholders:

- `%` or `%1` refers to the first argument
- `%2`, `%3`, etc. refer to subsequent arguments
- `%&` captures remaining variadic arguments

```phel
#(+ 6 %)       ; Same as (fn [x] (+ 6 x))
#(+ %1 %2)     ; Same as (fn [a b] (+ a b))
#(apply + %&)  ; Same as (fn [& xs] (apply + xs))

; Using with higher-order functions
(map #(* % 2) [1 2 3])        ; => [2 4 6]
(filter #(> % 3) [1 5 2 8])   ; => [5 8]
(sort-by #(get % :age) users)  ; Sort users by age
```

> **Note:** Older `|(...)` form with `$` placeholders is deprecated. See [Functions and Recursion](/documentation/language/functions-and-recursion/).

## Deref shorthand

`@` is shorthand for `(deref ...)`. Dereferences atoms and other reference types:

```phel
(def counter (atom 0))

@counter              ; Same as (deref counter) => 0
(swap! counter inc)
@counter              ; => 1
```

## Comments

`;` runs to end of line. `;;` for standalone, `;` for inline:

```phel
;; This is a standalone comment
(+ 1 2) ; This is an inline comment
```

> **Deprecation:** `#` line and `#| ... |#` multiline comments are deprecated. Use `;` and `;;`. `#` prefix is reserved for reader macros (`#()`, `#""`, `#?()`).

`#_` comments out the next form. Stack to comment multiple forms:

```phel
[:one :two :three]     ; results to [:one :two :three]
[#_:one :two :three]   ; results to [:two :three]
[#_:one :two #_:three] ; results to [:two]
[#_#_:one :two :three] ; results to [:three]
```

See [comment](/documentation/reference/api/core/#comment) macro: ignores forms, returns `nil`, still requires valid Phel code.
