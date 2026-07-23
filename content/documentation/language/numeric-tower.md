+++
title = "Numeric Tower"
weight = 15
description = "Phel's five numeric shapes (int, BigInt, Ratio, BigDecimal, float), when each appears, and how arithmetic dispatches across them"

[extra]
difficulty = "intermediate"
+++

Phel numbers are not a single type. Arithmetic, comparisons, and predicates dispatch across **five scalar shapes**, picking the most precise representation that fits. This page explains each shape and the rules that govern how they interact.

For literal syntax and the everyday arithmetic operators, see [Basic Types](/documentation/language/basic-types/#numbers). This page goes deeper into precision and promotion.

## The five shapes

| Type | Where it comes from | Notes |
|------|---------------------|-------|
| `int` | native PHP `int` | 64-bit signed on common platforms |
| `Phel\Lang\BigInt` | `bigint`, `+'`, `*'`, ... | arbitrary-precision signed integer |
| `Phel\Lang\Ratio` | `1/2` literals, `/`, `rationalize` | always normalised; collapses to `int`/`BigInt` when integral |
| `Phel\Lang\BigDecimal` | `1.5M` literal, `bigdec` | arbitrary-precision exact decimal |
| `float` | native PHP `float` (IEEE-754 double) | inexact |

`+`, `-`, `*`, `/`, the comparison operators, and the numeric predicates dispatch on these types via `Phel\Lang\NumericOperations`, because PHP's native operators don't dispatch on objects.

## Exact decimal literals: `1.5M`

`M`-suffixed numerals read as `BigDecimal` and print with the `M` suffix. Use them for monetary values and any computation where binary float drift is unacceptable.

```phel
(println 1.5M)               ; 1.5M (a BigDecimal)
(println (+ 0.1M 0.2M))      ; 0.3M (no float drift)
(println (bigdec "3.14159")) ; 3.14159M
(println (bigdec? 1.5M))     ; true
```

Contrast with `float`, where the same sum drifts:

```phel
(println (+ 0.1 0.2)) ; 0.30000000000000004
```

## Promoting integers: `bigint` and `+'`

Promote explicitly with the constructors. A `BigInt` prints as a plain integer (no suffix):

```phel
(println (bigint 42))                            ; 42 (a BigInt)
(println (bigint "1000000000000000000000"))
(println (bigint? (bigint 42)))                  ; true
```

The promoting arithmetic ops `+'`, `-'`, `*'`, `inc'`, and `dec'` auto-promote to `BigInt` on overflow instead of wrapping. Reach for them whenever overflow is possible:

```phel
(println (*' 1000000000 1000000000 1000000000)) ; 1000000000000000000000000000
(println (inc' 9223372036854775807))            ; 9223372036854775808
```

The plain ops (`+`, `*`, `inc`, ...) stay in native `int` and wrap on overflow, matching PHP.

## Large integer literals lex as `float`

PHP's lexer parses an oversize integer literal as a `float`, and so does Phel's:

```phel
(println (float? 12345678901234567890)) ; true (PHP int range overflow)
```

Wrap the value in a string and pass it to `bigint` to keep full precision:

```phel
(println (bigint "12345678901234567890"))
```

## Floats to exact: `rationalize`

`rationalize` converts a `float` to an exact `Ratio` using the **shortest decimal that round-trips** back to the same `float`, so binary-noise digits don't leak in:

```phel
(println (rationalize 0.1)) ; 1/10
```

It returns `1/10`, not `10000000000000001/100000000000000000`. Floats with no short decimal representation (such as `(/ 1.0 3.0)`) keep their round-trip representation as a `Ratio`. Passing `##Inf`, `##-Inf`, or `##NaN` throws an `InvalidArgumentException`.

A `Ratio` is always normalised and collapses back to an integer type when the result is integral:

```phel
(println (numerator 1/2))   ; 1
(println (denominator 1/2)) ; 2
(println (/ 4 2))           ; 2 (collapses to int)
```

## Gotchas

### `bit-shift-right` is arithmetic

`bit-shift-right` performs an arithmetic (sign-preserving) shift, matching PHP's `>>`. There is no `unsigned-bit-shift-right`; for a logical shift use a mask:

```phel
(println (bit-and (bit-shift-right -8 1) 0x7FFFFFFFFFFFFFFF))
```

### `==` arity-1 asserts numeric

In Clojure, `(==)` and `(== x)` return `true` for any single argument. Phel's `==` requires its argument to be numeric and otherwise throws. Single-argument `==` is a numeric assertion, not an identity check:

```phel
(println (== 5)) ; true
```

## Quick reference

| Need | Use |
|------|-----|
| Exact integer beyond PHP `int` | `(bigint "...")` or promoting ops `+'`, `*'` |
| Exact non-integer ratio | `Ratio` literal `1/2`, `(/ a b)`, `(rationalize x)` |
| Exact decimal (money, etc.) | `BigDecimal` literal `1.5M`, `(bigdec "...")` |
| Inexact decimal | native `float` |
| Predicate | `int?`, `bigint?`, `ratio?`, `bigdec?`, `decimal?`, `float?`, `number?` |
| Numerator / denominator | `numerator`, `denominator` |
| Float to exact | `rationalize` |

## See also

- [Basic Types](/documentation/language/basic-types/#numbers) - number literals and arithmetic operators
- [Coming from Clojure](/documentation/guides/coming-from-clojure/) - numeric differences from Clojure
- [Core API](/documentation/reference/api/core/) - full predicate and operator reference
