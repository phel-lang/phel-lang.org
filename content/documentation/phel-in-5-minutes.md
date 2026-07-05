+++
title = "Phel in 5 Minutes"
weight = 1
description = "Never seen a Lisp? Read Phel code in five minutes. One rule, four literals, one escape hatch to all of PHP. No install required."
+++

Phel is a [Lisp](https://en.wikipedia.org/wiki/Lisp_(programming_language)) that compiles to PHP. If you have never touched a Lisp, the parentheses look alien. They are not. This page teaches you to *read* Phel in five minutes: no install, no theory, no functional-programming lecture.

Want the pitch for *why* you would use it? See [Why Phel?](/documentation/why-phel/). Ready to run code? See [Getting Started](/documentation/getting-started/). This page is only about reading the syntax.

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

That is the whole rule. **The first item inside the parentheses is the function; everything after it is an argument.** No operator precedence, no special cases.

Even math works this way, because `+` is just a function:

```phel
(+ 1 2)        ; => 3
(+ 1 2 3 4)    ; => 10
(* 3 (+ 1 2))  ; => 9
```

Read the last one inside-out, exactly like nested PHP calls: `(+ 1 2)` is `3`, then `(* 3 3)` is `9`. The same PHP would be `3 * (1 + 2)`.

## Reading nested calls

Nesting is where Lisp starts to feel readable instead of scary. This PHP:

```php
strtoupper(trim("  hello  "));
```

is this Phel:

```phel
(php/strtoupper (php/trim "  hello  "))  ; => "HELLO"
```

Same shape, inside-out evaluation. The innermost parentheses run first. (`php/` is explained below: it means "reach into PHP".)

## The four data literals

You already know these from PHP and JSON. Phel writes them slightly differently:

| Phel | What it is | PHP equivalent |
| --- | --- | --- |
| `"text"` `42` `true` `nil` | string, number, boolean, null | `"text"` `42` `true` `null` |
| `:name` | keyword: a lightweight constant, often a map key | `"name"` as an array key |
| `[1 2 3]` | vector: an ordered list | `[1, 2, 3]` |
| `{:a 1 :b 2}` | map: key/value pairs | `["a" => 1, "b" => 2]` |

Note there are **no commas** inside `[...]` or `{...}`; whitespace separates items. A map is just alternating keys and values:

```phel
(get {:name "Ada" :age 36} :name)  ; => "Ada"
```

`get` is a function, `{:name ...}` is its first argument, `:name` is its second. One rule, still holding.

## Naming things

Three forms cover almost everything. Map them to PHP you already write:

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

Anything prefixed with `php/` reaches straight into PHP. Every function, class, constant, and Composer package is one prefix away, so you are never stuck:

```phel
(php/strlen "hello")                 ; => 5, calls PHP strlen()
(php/date "Y-m-d" 0)                 ; calls PHP date()
(def now (php/new DateTime "2024-01-15"))  ; new DateTime(...)
(php/-> now (format "Y-m-d"))        ; $now->format("Y-m-d")
```

`php/new` builds objects, `php/->` calls methods, `php/::` reaches statics and constants. You do not need them to start, but it is why "a Lisp on PHP" is more than a slogan: the entire PHP ecosystem is still there. Full details in [PHP Interop](/documentation/php-interop/).

## Putting it together

Here is a complete, runnable program using only what is above. Read it top to bottom:

```phel
(defn greet [name]
  (str "Hello, " name "!"))

(def people ["Ada" "Alan" "Grace"])

(println (map greet people))
; prints: @["Hello, Ada!" "Hello, Alan!" "Hello, Grace!"]
```

`str` joins values into a string. `map` applies `greet` to every item in the vector (this is Phel's `map`, the data-transforming one, not PHP's `map` naming). `println` prints the result. If you can follow this, you can read Phel.

## What next?

- [Getting Started](/documentation/getting-started/): install and open a live REPL in under a minute.
- [Why Phel?](/documentation/why-phel/): honest answers on where Phel fits and where it does not.
- [Rosetta Stone: PHP to Phel](/documentation/guides/rosetta-stone/): the same tasks side by side in both languages.
- [Cheat Sheet](/documentation/reference/cheat-sheet/): every core form on one page, to keep open while you code.
