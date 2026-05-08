+++
title = "Arithmetic"
weight = 2
aliases = ["/documentation/arithmetic"]
+++

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
(/ 2) ; => 1/2 (reciprocal as Rational)
(/ 24 4 2) ; => 3
(/ 10 3)   ; => 10/3 (Rational, exact)
```

`(/ int int)` with a non-integer result returns a `Rational`, not a float. Coerce with `float` or `(/ 10.0 3)` if you need a float.

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
- `+'`, `-'`, `*'`, `inc'`, `dec'`: auto-promote to `BigInteger` on overflow.
- `numerator`, `denominator`, `rationalize`, `ratio?`.
- `bigint`, `biginteger`, `bigint?`; `bigdec`, `bigdec?` / `decimal?`.

Full list in the API docs.

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
