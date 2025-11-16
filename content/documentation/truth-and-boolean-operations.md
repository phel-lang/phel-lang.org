+++
title = "Truth and Boolean operations"
weight = 4
+++

## Truthiness

In Phel, only `false` and `nil` represent falsity. Everything else evaluates to true—including `0`, `""`, and `[]`.

The function `truthy?` can be used to check if a value is truthy. To check for the values `true` and `false` specifically, the functions `true?` and `false?` can be used.

```phel
(truthy? false) # Evaluates to false
(truthy? nil) # Evaluates to false
(truthy? true) # Evaluates to true
(truthy? 0) # Evaluates to true
(truthy? -1) # Evaluates to true

(true? true) # Evaluates to true
(true? false) # Evaluates to false
(true? 0) # Evaluates to false
(true? -1) # Evaluates to false

(false? true) # Evaluates to false
(false? false) # Evaluates to true
(false? 0) # Evaluates to false
(false? -1) # Evaluates to false
```

{% php_note() %}
This is **different from PHP** where `0`, `""`, `[]`, and `null` are all falsy.

```php
// PHP
if (0) { }        // false - won't execute
if ("") { }       // false - won't execute
if ([]) { }       // false - won't execute

// Phel
(if 0 "yes" "no")   # => "yes" - 0 is truthy!
(if "" "yes" "no")  # => "yes" - "" is truthy!
(if [] "yes" "no")  # => "yes" - [] is truthy!
```
{% end %}

{% clojure_note() %}
Truthiness works exactly like Clojure—only `false` and `nil` are falsy.
{% end %}

## Identity vs Equality

The function `id` returns `true` if two values are identical. Identity is stricter than equality. It first checks if both types are identical and then compares their values. Phel keywords and symbols with the same names are always identical. Lists, vectors, maps and sets are only identical if they point to the same references.

```phel
(id true true) # Evaluates to true
(id true false) # Evaluates to false
(id 5 "5") # Evaluates to false
(id :test :test) # Evaluates to true
(id 'sym 'sym) # Evaluates to true
(id '() '()) # Evaluates to false
(id [] []) # Evaluates to false
(id {} {}) # Evaluates to false
```

To check if two values are equal, the equal function (`=`) can be used. Two values are equal if they have the same type and value. Lists, vectors, maps and sets are equal if they have same values, but they must not point to the same references.

```phel
(= true true) # Evaluates to true
(= true false) # Evaluates to false
(= 5 "5") # Evaluates to false
(= 5 5) # Evaluates to true
(= 5 5.0) # Evaluates to false
(= :test :test) # Evaluates to true
(= 'sym 'sym) # Evaluates to true
(= '() '()) # Evaluates to true
(= [] []) # Evaluates to true
(= {} {}) # Evaluates to true
```

To check if two values are unequal, the `not=` function can be used.

{% php_note() %}
**Comparison with PHP operators:**

- `id` is like PHP's `===` (identity/strict equality) with support for Phel types
- `=` is **not** like PHP's `==` (loose equality)
- `=` compares Phel values structurally with type checking

If you need PHP's loose equality, use `php/==`:

```phel
(php/== 5 "5")  # => true (PHP loose equality)
(= 5 "5")       # => false (Phel structural equality)
(id 5 5)        # => true (Phel identity)
```
{% end %}

{% clojure_note() %}
`id` is like Clojure's `identical?`, and `=` is like Clojure's `=`.
{% end %}

## Comparison Operations

Further comparison function are:

- `<=`: Checks if each argument is less than or equal to the following argument. Returns a boolean.
- `<`: Checks if each argument is strictly less than the following argument. Returns a boolean.
- `>=`: Checks if each argument is greater than or equal to the following argument. Returns a boolean.
- `>`: Checks if each argument is strictly greater than the following argument. Returns a boolean.

## Logical Operations

The `and` function evaluates each expression one at a time, from left to right. If a form returns logical false, `and` returns that value and doesn't evaluate any of the other expressions, otherwise it returns the value of the last expression. Calling the `and` function without arguments returns true.

```phel
(and) # Evaluates to true
(and 1) # Evaluates to 1
(and false) # Evaluates to false
(and 0) # Evaluates to 0
(and true 5) # Evaluates to 5
```

The `or` function evaluates each expression one at a time, from left to right. If a form returns a logical true value, `or` returns that value and doesn't evaluate any of the other expressions, otherwise it returns the value of the last expression. Calling `or` without arguments, returns nil.

```phel
(or) # Evaluates to nil
(or 1) # Evaluates to 1
(or false 5) # Evaluates to 5
```

The `not` function returns `true` if the given value is logical false and `false` otherwise.

```phel
(not 1) # Evaluates to false
(not 0) # Evaluates to false
(not true) # Evaluates to false
(not false) # Evaluates to true
(not nil) # Evaluates to true
```
