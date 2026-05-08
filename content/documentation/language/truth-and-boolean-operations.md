+++
title = "Truth and Boolean operations"
weight = 3
aliases = ["/documentation/truth-and-boolean-operations"]
+++

## Truthiness

Only `false` and `nil` are falsy. `0`, `""`, `[]` all truthy.

`truthy?` checks truthiness. `true?` and `false?` check for the values themselves.

```phel
(truthy? false) ; Evaluates to false
(truthy? nil) ; Evaluates to false
(truthy? true) ; Evaluates to true
(truthy? 0) ; Evaluates to true
(truthy? -1) ; Evaluates to true

(true? true) ; Evaluates to true
(true? false) ; Evaluates to false
(true? 0) ; Evaluates to false
(true? -1) ; Evaluates to false

(false? true) ; Evaluates to false
(false? false) ; Evaluates to true
(false? 0) ; Evaluates to false
(false? -1) ; Evaluates to false
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
(identical? true true)   ; Evaluates to true
(identical? true false)  ; Evaluates to false
(identical? 5 "5")       ; Evaluates to false
(identical? :test :test) ; Evaluates to true
(identical? 'sym 'sym)   ; Evaluates to true
(identical? '() '())     ; Evaluates to false
(identical? [] [])       ; Evaluates to false
(identical? {} {})       ; Evaluates to false
```

> **Note:** `id` is a deprecated alias for `identical?`.

`=` checks equality: same type and value. Collections equal if values match (no reference check).

```phel
(= true true) ; Evaluates to true
(= true false) ; Evaluates to false
(= 5 "5") ; Evaluates to false
(= 5 5) ; Evaluates to true
(= 5 5.0) ; Evaluates to false
(= :test :test) ; Evaluates to true
(= 'sym 'sym) ; Evaluates to true
(= '() '()) ; Evaluates to true
(= [] []) ; Evaluates to true
(= {} {}) ; Evaluates to true
```

Use `not=` for inequality.

{% php_note() %}
**vs PHP operators:**

- `identical?` like `===` (strict, with Phel types)
- `=` is **not** like `==` (loose)
- `=` compares structurally with type checking

PHP loose equality: use `php/==`:

```phel
(php/== 5 "5")      ; => true (PHP loose equality)
(= 5 "5")           ; => false (Phel structural equality)
(identical? 5 5)    ; => true (Phel identity)
```
{% end %}

## Comparisons

All comparison operators accept multiple arguments and return a bool:

- `<=`: each arg ≤ next
- `<`: each arg strictly < next
- `>=`: each arg ≥ next
- `>`: each arg strictly > next

```phel
(< 1 2)     ; Evaluates to true
(< 1 2 3)   ; Evaluates to true  (1 < 2 and 2 < 3)
(< 1 3 2)   ; Evaluates to false (3 is not < 2)
(>= 5 5)    ; Evaluates to true
(> 3 2 1)   ; Evaluates to true  (3 > 2 and 2 > 1)
```

## Logical operations

`and` evaluates left-to-right. Returns first falsy or the last value. No args returns true.

```phel
(and) ; Evaluates to true
(and 1) ; Evaluates to 1
(and false) ; Evaluates to false
(and 0) ; Evaluates to 0
(and true 5) ; Evaluates to 5
```

`or` evaluates left-to-right. Returns first truthy or the last value. No args returns nil.

```phel
(or) ; Evaluates to nil
(or 1) ; Evaluates to 1
(or false 5) ; Evaluates to 5
```

`not` returns `true` for falsy values, `false` otherwise.

```phel
(not 1) ; Evaluates to false
(not 0) ; Evaluates to false
(not true) ; Evaluates to false
(not false) ; Evaluates to true
(not nil) ; Evaluates to true
```
