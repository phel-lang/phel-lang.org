+++
title = "Arithmetic"
weight = 3
+++

## Arithmetic Operators

All arithmetic operators are entered in prefix notation.

```phel
# (1 + (2*2) + (10/5) + 3 + 4 + (5 - 6))
(+ 1 (* 2 2) (/ 10 5) 3 4 (- 5 6)) # Evaluates to 13
```

{% php_note() %}
Phel uses prefix notation (operator comes first) instead of PHP's infix notation:

```php
// PHP - infix notation
1 + (2 * 2) + (10 / 5) + 3 + 4 + (5 - 6);

// Phel - prefix notation
(+ 1 (* 2 2) (/ 10 5) 3 4 (- 5 6))
```

This allows operators to accept any number of arguments and eliminates operator precedence concerns.
{% end %}

{% clojure_note() %}
Arithmetic works exactly like Clojure—prefix notation with variadic support for most operators.
{% end %}

Some operators support zero, one or multiple arguments.

```phel
(+) # Evaluates to 0
(+ 1) # Evaluates to 1
(+ 1 2) # Evalutaes to 3
(+ 1 2 3 4 5 6 7 8 9) # Evaluates to 45

(-) # Evaluates to 0
(- 1) # Evaluates to -1
(- 2 1) # Evaluates to 1
(- 3 2 1) # Evaluates to 0

(*) # Evaluates to 1
(* 2) # Evaluates to 2
(* 2 3 4) #Evaluates to 24

(/) # Evaluates to 1
(/ 2) # Evaluates to 0.5 (reciprocal of 2)
(/ 24 4 2) #Evaluates to 3
```

{% php_note() %}
Phel's variadic operators are more flexible than PHP's:

```php
// PHP - requires at least two operands
1 + 2 + 3 + 4 + 5;
// Can't do this: +();  <- syntax error

// Phel - supports 0, 1, or many operands
(+)                     # 0 (identity)
(+ 1)                   # 1 (identity)
(+ 1 2 3 4 5)          # 15 (sum of all)
```

**Useful patterns:**
- `(+)` returns the additive identity (0)
- `(*)` returns the multiplicative identity (1)
- `(- x)` negates a number
- `(/ x)` computes the reciprocal
{% end %}

Further numeric operations are `%` to compute the remainder of two values and `**` to raise a number to the power. All numeric operations can be found in the API documentation.

Some numeric operations can result in an undefined or unrepresentable value. These values are called _Not a Number_ (NaN). Phel represents these values by the constant `NAN`. You can check if a result is NaN by using the `nan?` function.

```phel
(nan? 1) # false
(nan? (php/log -1)) # true
(nan? NAN) # true
```

{% php_note() %}
NaN handling is similar to PHP:

```php
// PHP
is_nan(1);           // false
is_nan(log(-1));     // true
is_nan(NAN);         // true

// Phel
(nan? 1)             # false
(nan? (php/log -1))  # true
(nan? NAN)           # true
```

The `%` operator for remainder and `**` for exponentiation work like PHP's `%` and `**` operators.
{% end %}

## Bitwise Operators

Phel allows the evaluation and manipulation of specific bits within an integer.

```phel
# Bitwise and
(bit-and 0b1100 0b1001) # Evaluates to 8 (0b1000)

# Bitwise or
(bit-or 0b1100 0b1001) # Evaluates to 13 (0b1101)

# Bitwise xor
(bit-xor 0b1100 0b1001) # Evaluates to 5 (0b0101)

# Bitwise complement
(bit-not 0b0111) # Evaluates to -8

# Shifts bit n steps to the left
(bit-shift-left 0b1101 1) # Evaluates to 26 (0b11010)

# Shifts bit n steps to the right
(bit-shift-right 0b1101 1) # Evaluates to 6 (0b0110)

# Set bit at index n
(bit-set 0b1011 2) # Evalutes to (0b1111)

# Clear bit at index n
(bit-clear 0b1011 3) # Evaluates to 3 (0b0011)

# Flip bit at index n
(bit-flip 0b1011 2) # Evaluates to 15 (0b1111)

# Test bit at index n
(bit-test 0b1011 0) # Evaluates to true
(bit-test 0b1011 2) # Evaluates to false
```

{% php_note() %}
Phel provides named functions for bitwise operations instead of PHP's operators:

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

Phel also provides additional bit manipulation functions not available in PHP: `bit-set`, `bit-clear`, `bit-flip`, and `bit-test`.
{% end %}

{% clojure_note() %}
Bitwise operators work exactly like Clojure's—same function names and behavior for bit manipulation.
{% end %}
