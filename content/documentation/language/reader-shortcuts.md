+++
title = "Reader Shortcuts"
weight = 8
description = "Catalog of the Phel reader macros and special syntax: literals, quote/quasiquote, tagged literals, anonymous functions, reader conditionals for shared .cljc files, and metadata."
aliases = ["/documentation/language/reader-conditionals/"]

[extra]
difficulty = "intermediate"
+++

The **reader** turns source text into Phel data before the compiler sees it. On the way, it expands a few short `#`-prefixed and single-character forms into ordinary forms.

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

Most rows have a home page: collections in [Data Structures](/documentation/language/data-structures/), quote, quasiquote and `name#` in [Macros](/documentation/language/macros/#hygiene-and-gensym), `@` and atoms in [Global and local bindings](/documentation/language/global-and-local-bindings/#atoms), `#(...)` in [Functions and Recursion](/documentation/language/functions-and-recursion/#anonymous-function-fn), regex and comments in [Basic Types](/documentation/language/basic-types/#regex-literals). The sections below cover the rest.

## Reader conditionals

`#?()` and `#?@()` let one `.cljc` file hold code for Phel and Clojure. Phel finds `.cljc` files next to `.phel` files. Both forms resolve while the file is parsed, so the compiler only sees the selected form.

`#?()` reads key and form pairs and keeps one:

1. `:phel` wins when present, in any position.
2. Otherwise `:default` is used.
3. With neither, the whole form is dropped, as if it were whitespace.

Other keys (`:clj`, `:cljs`) are ignored.

```phel
#?(:default 0 :phel 42) ; => 42
#?(:clj 99 :default 0)  ; => 0
[1 #?(:clj 99) 2]       ; => [1 2]
```

`#?@()` splices the items of the chosen vector or list into the surrounding form. At the top level of a file it is an error, because there is no parent form:

```phel
[1 #?@(:phel [2 3]) 4] ; => [1 2 3 4]
```

They also work inside `ns`, so each platform can require its own libraries:

<!-- phel-test: skip -->
```phel
(ns app.http
  #?(:phel (:require [phel.json :as json])
     :clj  (:require [clojure.data.json :as json])))
```

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

## Metadata `^`

`^` attaches metadata to the next form. A keyword sets that key to `true`. A map adds several keys:

<!-- phel-test: skip -->
```phel
^:private (def x 10)
^{:doc "Example"} (defn foo [] nil)
```

Type hints use the same syntax: `^int`, `^"?int"`, `^{:tag "\\Foo\\Bar"}`. See [Return and parameter types](/documentation/language/functions-and-recursion/#return-and-parameter-types-tag).
