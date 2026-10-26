+++
title = "Phel in 5 Minutes"
weight = 1
description = "Never seen a Lisp? Read Phel code in five minutes. One rule, four literals, one escape hatch to all of PHP. No install required."
+++

Phel is a [Lisp](https://en.wikipedia.org/wiki/Lisp_(programming_language)) that compiles to PHP. After this page you can read Phel code, even if you have never used a Lisp. No install needed.

This page covers syntax only. For why you would use Phel, see the [common questions](/#common-questions). To run code, see [Getting Started](/documentation/getting-started/).

## The one rule

In PHP you call a function like this:

```php
add(1, 2);
```

In Phel, the parenthesis moves to the front and the commas disappear:

<!-- phel-test: skip -->
```phel
(add 1 2)
```

That is the whole rule. **The first item inside the parentheses is the function; everything after it is an argument.** There is no operator precedence and no special case.

Math works the same way, because `+` is a function too:

```phel
(+ 1 2)        ; => 3
(+ 1 2 3 4)    ; => 10
(* 3 (+ 1 2))  ; => 9
```

Read the last one inside-out, like nested PHP calls: `(+ 1 2)` is `3`, then `(* 3 3)` is `9`. The same PHP would be `3 * (1 + 2)`.

## Reading nested calls

Nested calls keep the same shape as in PHP. This PHP:

```php
strtoupper(trim("  hello  "));
```

is this Phel:

```phel
(php/strtoupper (php/trim "  hello  "))  ; => "HELLO"
```

The innermost parentheses run first. The `php/` prefix calls a PHP function; see [the escape hatch](#the-escape-hatch-php) below.

## The four data literals

You know these from PHP and JSON. Phel writes them a little differently:

| Phel | What it is | PHP equivalent |
| --- | --- | --- |
| `"text"` `42` `true` `nil` | string, number, boolean, null | `"text"` `42` `true` `null` |
| `:name` | keyword: a lightweight constant, often a map key | `"name"` as an array key |
| `[1 2 3]` | vector: an ordered list | `[1, 2, 3]` |
| `{:a 1 :b 2}` | map: key/value pairs | `["a" => 1, "b" => 2]` |

There are **no commas** inside `[...]` or `{...}`; whitespace separates items. A map is a list of alternating keys and values:

```phel
(get {:name "Ada" :age 36} :name)  ; => "Ada"
```

`get` is a function, `{:name ...}` is its first argument, `:name` is its second. One rule, still holding.

## Naming things

Three forms cover most code. Each maps to PHP you already write:

```phel
(def pi 3.14)               ; a constant, like a PHP constant

(defn square [x]            ; a named function: (defn name [params] body)
  (* x x))

(let [r 2                   ; local variables, scoped to the block
      area (* pi (square r))]
  area)                     ; => 12.56
```

- `def` binds a name at the top level.
- `defn` defines a function. The `[x]` is the parameter list, the rest is the body. The last expression is the return value: no `return` keyword.
- `let` introduces locals in `[name value name value ...]` pairs, usable only inside its parentheses.

## The escape hatch: `php/`

Anything prefixed with `php/` calls into PHP. Every PHP function is one prefix away, and classes from PHP or Composer work with `new`:

```phel
(php/strlen "hello")                  ; => 5, PHP strlen("hello")
(php/str_repeat "ab" 3)               ; => "ababab"
(def now (new DateTime "2024-01-15")) ; $now = new DateTime("2024-01-15")
(.format now "Y-m-d")                 ; => "2024-01-15", $now->format("Y-m-d")
```

`new` builds objects, `.method` calls methods, and `Class/member` reaches statics and constants. You do not need them to start, but they mean the whole PHP ecosystem stays available. Full details in [PHP Interop](/documentation/language/php-interop/).

## Putting it together

This complete program uses only what is above. Read it top to bottom:

```phel
(defn greet [name]
  (str "Hello, " name "!"))

(def people ["Ada" "Alan" "Grace"])

(println (map greet people))
; prints: (Hello, Ada! Hello, Alan! Hello, Grace!)
```

`str` joins values into a string. `map` applies `greet` to every item in the vector, like PHP's `array_map`. It returns a lazy sequence, which prints in parentheses. `println` prints the result. If you can follow this, you can read Phel.

## Next steps

- [Getting Started](/documentation/getting-started/): install Phel and open a live REPL.
- [Rosetta Stone: PHP to Phel](/documentation/guides/rosetta-stone/): the same tasks side by side in both languages.
- [Cheat Sheet](/documentation/reference/cheat-sheet/): every core form on one page, to keep open while you code.
