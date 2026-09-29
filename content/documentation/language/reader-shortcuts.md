+++
title = "Reader Shortcuts"
weight = 8
description = "Catalog of the Phel reader macros and special syntax: literals, quote/quasiquote, tagged literals, anonymous functions, reader conditionals, and metadata."

[extra]
difficulty = "intermediate"
+++

After this page you can read any Phel shortcut syntax and know the plain form it stands for.

The **reader** turns source text into Phel data before the compiler sees it. On the way, it expands a few short `#`-prefixed and single-character forms into ordinary forms. Topics with their own page are linked, not repeated.

## Quick reference

| Syntax      | Name               | Stands for                                     | Example                          |
|-------------|--------------------|------------------------------------------------|----------------------------------|
| `[]`        | Vector             | `(vector ...)`                                 | `[1 2 3]`                        |
| `{}`        | Hash map           | `(hash-map ...)`                               | `{:a 1 :b 2}`                    |
| `#{}`       | Set                | `(hash-set ...)`                               | `#{1 2 3}`                       |
| `'()`       | List               | quoted `(list ...)`                            | `'(1 2 3)`                       |
| `'`         | Quote              | `(quote x)`                                    | `'x`                             |
| `` ` ``     | Quasiquote         | quote with selective evaluation                | `` `(1 ~x) ``                    |
| `~`         | Unquote            | evaluate inside a quasiquote                   | `~x`                             |
| `~@`        | Unquote-splice     | splice a sequence inside a quasiquote          | `~@xs`                           |
| `name#`     | Auto-gensym        | fresh unique symbol inside a quasiquote        | `` `(let [g# 1] g#) ``           |
| `#?()`      | Reader conditional | form for the current platform                  | `#?(:phel 1 :default 0)`         |
| `#?@()`     | Conditional splice | splice by platform                             | `#?@(:phel [1 2])`               |
| `@`         | Deref              | `(deref x)`                                    | `@my-atom`                       |
| `#'`        | Var-quote          | `(var my-fn)`                                  | `#'my-fn`                        |
| `#(...)`    | Anonymous function | `(fn [...] ...)` with `%` args                 | `#(+ %1 %2)`                     |
| `#"..."`    | Regex literal      | `(re-pattern "...")`                           | `#"\d+"`                         |
| `#<tag>`    | Tagged literal     | the tag's reader function                      | `#inst "2026-01-01T00:00:00Z"`   |
| `#php`      | PHP array          | `(php-indexed-array ...)` or `(php-associative-array ...)` | `#php [1 2 3]`       |
| `;` `;;`    | Line comment       | comment to end of line                         | `;; note`                        |
| `#_`        | Form comment       | skip the next form                             | `#_ expr`                        |
| `^`         | Metadata           | attach metadata to the next form               | `^:private`                      |

## Collection literals

Each collection literal is a shorter way to call its constructor:

```phel
[1 2 3]       ; => [1 2 3]      same as (vector 1 2 3)
{:a 1 :b 2}   ; => {:a 1 :b 2}  same as (hash-map :a 1 :b 2)
#{1 2 3}      ; => #{1 2 3}     same as (hash-set 1 2 3)
'(1 2 3)      ; => (1 2 3)      same as (list 1 2 3)
```

Operations on each collection: [Data Structures](/documentation/language/data-structures/).

## Quote `'`

Quote returns the next form as data instead of evaluating it:

```phel
'x            ; => x          the symbol x, same as (quote x)
'(+ 1 2)      ; => (+ 1 2)    the list, not 3
```

## Quasiquote `` ` ``

Quasiquote is like quote, but lets you evaluate parts of the form. `~` inserts the value of one form. `~@` splices in the elements of a sequence. Macros are built on it:

```phel
(let [x 5]   `(1 ~x 3))        ; => (1 5 3)
(let [xs [2 3 4]] `(1 ~@xs 5)) ; => (1 2 3 4 5)
```

{% callout(kind="warning") %}
**Removed in 0.50:** `,` (unquote) and `,@` (unquote-splicing). Use `~` and `~@`. `,` is now whitespace, so `` `(f ,x) `` still parses but *quotes* `x` instead of unquoting it. There is no error, only a wrong expansion.
{% end %}

### Auto-gensym `name#`

Inside a quasiquote, a symbol that ends in `#` becomes a fresh unique name. The same `name#` maps to the same generated name everywhere in that template. This keeps macro locals from clashing with user code. See [Macros](/documentation/language/macros/#hygiene-and-gensym).

{% callout(kind="warning") %}
**Removed in 0.50:** the `name$` suffix. Use `name#`.
{% end %}

## Reader conditionals `#?()` and `#?@()`

`#?()` picks one form by platform key: Phel uses `:phel`, then falls back to `:default`. `#?@()` splices a platform-specific collection into the surrounding one. With them, one `.cljc` file can compile under both Phel and Clojure. See [Reader Conditionals](/documentation/language/reader-conditionals/).

## Deref `@`

`@x` is `(deref x)`. It reads the current value of an atom:

```phel
(def counter (atom 41))
(swap! counter inc)
@counter ; => 42
```

Atoms: [Global and local bindings](/documentation/language/global-and-local-bindings/#atoms).

## Var-quote `#'`

`#'my-fn` returns the Var for a global definition, the same as `(var my-fn)`. A call through the Var always uses the current definition, which is what makes redefining functions in the REPL work:

```phel
(def greeting "hello")
(deref #'greeting) ; => "hello"
```

## Anonymous functions `#(...)`

`#(...)` defines a short function. `%` (or `%1`) is the first argument, `%2` the second, and `%&` the rest:

```phel
(map #(* % 2) [1 2 3])          ; => (2 4 6)
(reduce #(+ %1 %2) 0 [1 2 3 4]) ; => 10
```

When to use a named `fn` instead: [Functions and Recursion](/documentation/language/functions-and-recursion/#anonymous-function-fn).

{% callout(kind="warning") %}
**Removed in 0.50:** the `|(...)` form with `$`, `$1`, `$&`. Use `#(...)` with `%`.
{% end %}

## Regex literals `#"..."`

`#"..."` is a PCRE pattern, the same as `(re-pattern "...")`. Backslashes do not need doubling:

```phel
(re-find #"\d+" "abc123") ; => "123"
```

Matching functions: [Basic Types](/documentation/language/basic-types/#regex-literals).

## Tagged literals `#<tag> form`

A tagged literal passes the next form to the tag's reader function while the code is read. Three tags are built in:

<!-- phel-test: skip -->
```phel
#inst "2026-01-01T00:00:00Z"                   ; a \DateTimeImmutable
#uuid "550e8400-e29b-41d4-a716-446655440000"   ; a Phel\Lang\UUID
#regex "\\d+"                                  ; a PCRE pattern string
```

Register your own tag with `register-tag` from `phel.reader`. For tags used across a project or shipped with a library, put the registrations in a `data-readers.phel` file at any source root. It loads automatically:

<!-- phel-test: skip -->
```phel
;; src/phel/data-readers.phel
(ns my-app.data-readers
  (:require phel.reader :refer [register-tag]))

(register-tag "money" (fn [[amount currency]]
                        {:amount amount :currency currency}))
```

After that, any source file can use the tag:

<!-- phel-test: skip -->
```phel
#money [100 "EUR"] ; => {:amount 100 :currency "EUR"}
```

`phel.reader` also has `tag-registered?`, `unregister-tag`, and `registered-tags`.

## PHP array literals `#php`

`#php` builds a native PHP array without calling `php-indexed-array` or `php-associative-array`:

```phel
#php [1 2 3]        ; same as (php-indexed-array 1 2 3)
#php {"a" 1 "b" 2}  ; same as (php-associative-array "a" 1 "b" 2)
```

Only the outer form becomes a PHP array. Nested forms stay Phel data. More on PHP arrays: [PHP interop](/documentation/language/php-interop/).

## Comments

`;` comments to the end of the line. `#_` skips the next form:

```phel
[1 #_(+ 2 3) 4] ; => [1 4]
```

Comment styles and the `comment` macro: [Basic Types](/documentation/language/basic-types/#comments).

## Metadata `^`

`^` attaches metadata to the next form. A keyword sets that key to `true`. A map adds several keys:

<!-- phel-test: skip -->
```phel
^:private (def x 10)
^{:doc "Example"} (defn foo [] nil)
```

Type hints use the same syntax: `^int`, `^"?int"`, `^{:tag "\\Foo\\Bar"}`. See [Return and parameter types](/documentation/language/functions-and-recursion/#return-and-parameter-types-tag).
