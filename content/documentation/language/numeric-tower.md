+++
title = "Numeric Tower"
weight = 7
description = "Phel's five numeric shapes (int, BigInt, Ratio, BigDecimal, float), when each appears, and how arithmetic dispatches across them"

[extra]
difficulty = "intermediate"
+++

Phel numbers come in **five shapes**. Arithmetic, comparisons, and predicates work across all of them. For literal syntax and the everyday operators, see [Basic Types](/documentation/language/basic-types/#numbers).

## The five shapes

| Type | Where it comes from | Notes |
|------|---------------------|-------|
| `int` | native PHP `int` | 64-bit signed on common platforms |
| `Phel\Lang\BigInt` | `bigint`, `+'`, `*'`, ... | arbitrary-precision signed integer |
| `Phel\Lang\Ratio` | `1/2` literals, `/`, `rationalize` | always reduced; becomes an `int` or `BigInt` when whole |
| `Phel\Lang\BigDecimal` | `1.5M` literal, `bigdec` | arbitrary-precision exact decimal |
| `float` | native PHP `float` (IEEE-754 double) | inexact |

`+`, `-`, `*`, `/`, the comparison operators, and the numeric predicates handle all five through `Phel\Lang\NumericOperations`, because PHP's native operators do not work on these objects.

## Exact decimal literals: `1.5M`

A number with an `M` suffix reads as a `BigDecimal` and prints with the `M` suffix. Use it for money and any calculation where float rounding errors are not acceptable.

```phel
(println 1.5M)               ; 1.5M (a BigDecimal)
(println (+ 0.1M 0.2M))      ; 0.3M (no float drift)
(println (bigdec "3.14159")) ; 3.14159M
```

Contrast with `float`. The sum prints as `0.3`, but it is not `0.3`:

```phel
(println (= (+ 0.1 0.2) 0.3))       ; false (float drift)
(println (= (+ 0.1M 0.2M) 0.3M))    ; true
```

## Promoting integers: `bigint` and `+'`

Promote explicitly with `bigint`. A `BigInt` prints as a plain integer (no suffix). The promoting operators `+'`, `-'`, `*'`, `inc'`, and `dec'` switch to `BigInt` on overflow instead of wrapping. Use them whenever overflow is possible:

```phel
(println (*' 1000000000 1000000000 1000000000)) ; 1000000000000000000000000000
(println (inc' 9223372036854775807))            ; 9223372036854775808
```

The plain ops (`+`, `*`, `inc`, ...) stay in native `int` and wrap on overflow, matching PHP.

An oversize integer literal such as `12345678901234567890` reads as a `float`, as in PHP. Pass it as a string to keep full precision: `(bigint "12345678901234567890")`.

## Floats to exact: `rationalize`

`rationalize` converts a `float` to an exact `Ratio`, using the shortest decimal that converts back to the same `float`. So `(rationalize 0.1)` returns `1/10`, not `10000000000000001/100000000000000000`. Passing `##Inf` or `##NaN` throws.

A `Ratio` is always reduced, and it becomes an integer when the result is whole:

```phel
(println (numerator 1/2))   ; 1
(println (denominator 1/2)) ; 2
(println (/ 4 2))           ; 2 (collapses to int)
```

Predicates for each shape: `int?`, `bigint?`, `ratio?`, `bigdec?`, `decimal?`, `float?`, `number?`. The full list of numeric functions is in the [core API](/documentation/reference/api/core/).
