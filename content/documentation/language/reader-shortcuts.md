+++
title = "Reader Shortcuts"
weight = 13
description = "Compact catalog of the reader macros and special syntax the Phel reader expands at read time: literals, quote/quasiquote, tagged literals, anonymous functions, reader conditionals, and metadata."
+++

The **reader** turns source text into Phel data before the compiler sees it. Along the way it expands a handful of single-character and `#`-prefixed shortcuts into ordinary forms. Knowing what each one desugars to makes unfamiliar code readable: every shortcut on this page is just a shorter spelling of a regular call or form.

This page is the consolidated catalog. Several shortcuts have their own deep-dive sections elsewhere; this page links to them rather than repeating the detail.

## Collection literals

The four data-structure literals each desugar to a constructor call:

```phel
[1 2 3]       ; => [1 2 3]      same as (vector 1 2 3)
{:a 1 :b 2}   ; => {:a 1 :b 2}  same as (hash-map :a 1 :b 2)
#{1 2 3}      ; => {1 2 3}       hash-set, same as (hash-set 1 2 3)
'(1 2 3)      ; => (1 2 3)      quoted list, same as (list 1 2 3)
```

Empty forms read the same way: `[]`, `{}`, `#{}`, `'()`. See [Data Structures](/documentation/language/data-structures) for the operations each collection supports.

## Quote `'`

Quote returns the following form as data instead of evaluating it:

```phel
'x            ; => x          the symbol x, same as (quote x)
'(+ 1 2)      ; => (+ 1 2)    the list, not 3
'[1 2 3]      ; => [1 2 3]    the literal vector
```

## Quasiquote `` ` ``

Quasiquote is like quote but allows selective evaluation inside it: unquote (`~`) splices in one evaluated form, unquote-splicing (`~@`) splices in a sequence. This is the backbone of macros.

```phel
(let [x 5]   `(1 ~x 3))        ; => (1 5 3)     ~ evaluates one form
(let [xs [2 3 4]] `(1 ~@xs 5)) ; => (1 2 3 4 5) ~@ splices a sequence
```

> **Deprecated:** the `,` (unquote) and `,@` (unquote-splicing) reader macros. Use `~` and `~@`. Inside a quasiquote `,` is plain whitespace.

### Auto-gensym `name#`

Inside a quasiquote, a symbol ending in `#` expands to a fresh, unique name. Every occurrence of the same `name#` within one quasiquote shares that generated name, which gives hygienic macros without an explicit `gensym` call:

```phel skip
(defmacro time [expr]
  `(let [start# (php/microtime true)
         ret#   ~expr]
     (println "Elapsed:" (- (php/microtime true) start#) "secs")
     ret#))
```

Each expansion produces a fresh pair such as `start__123__auto__` / `ret__124__auto__`, so generated bindings cannot collide with the caller's locals. See [Macros](/documentation/language/macros) for the full story on hygiene.

> **Deprecated:** `name$` as an auto-gensym suffix. Use `name#`.

## Reader conditionals `#?()` and `#?@()`

Resolved while reading. `#?()` picks one form by platform key; `#?@()` splices a collection and is only valid inside another collection. Phel selects the `:phel` branch and falls back to `:default`:

```phel
#?(:phel (php/microtime) :clj 99)  ; => the (php/microtime) value in Phel
#?(:clj 99 :default 0)             ; => 0
[1 #?@(:phel [2 3]) 4]             ; => [1 2 3 4]
```

These let one `.cljc` file compile under both Phel and Clojure. The `:clj` branch is never read by Phel, so it does not need to be valid Phel.

## Deref `@`

```phel
(def my-atom (atom 41))
@my-atom              ; => 41   same as (deref my-atom)
(swap! my-atom inc)
@my-atom              ; => 42
```

## Var-quote `#'`

`#'my-fn` returns the `PhelVar` handle for a global definition, the same as `(var my-fn)`. The handle stays bound to the var, so calls through it pick up later redefinitions, which is what makes interactive redefinition work.

```phel
(def greeting "hello")
(var greeting)        ; => the PhelVar handle (printed as a var)
```

## Tagged literals `#<tag> form`

A tagged literal runs the tag's reader function on the following form at read time. Three tags are built in:

```phel skip
#inst "2026-01-01T00:00:00Z"                   ; reads as \DateTimeImmutable
#regex "\\d+"                                   ; reads as a PCRE pattern string
#uuid "550e8400-e29b-41d4-a716-446655440000"   ; reads as Phel\Lang\UUID
```

Register your own tag with `register-tag` from `phel.reader`:

```phel skip
(ns my-app.main
  (:require phel.reader :refer [register-tag]))

(register-tag "money" (fn [s] {:kind :money :raw s}))

#money "10.00 EUR"
;; => {:kind :money :raw "10.00 EUR"}
```

For project-wide tags, drop a `data-readers.phel` at any source root; it auto-loads and should register each tag explicitly:

```phel skip
;; src/phel/data-readers.phel
(ns my-app.data-readers
  (:require phel.reader :refer [register-tag]))

(register-tag "point" (fn [[x y]] {:x x :y y}))
```

Related helpers in `phel.reader`: `tag-registered?`, `unregister-tag`, `registered-tags`. Tagged literals are also covered in [Basic Types](/documentation/language/basic-types#tagged-literals).

## Regex literals `#"..."`

`#"..."` is reader sugar for a PCRE pattern, equivalent to `(re-pattern "...")`:

```phel
(re-find #"\d+" "abc123")    ; => "123"
```

See [Basic Types](/documentation/language/basic-types#regex-literals) for matching helpers.

## Anonymous functions `#(...)`

`#(...)` defines an inline function using `%` placeholders: `%` (or `%1`) is the first argument, `%2` the second, `%&` the rest.

```phel
(map #(* % 2) [1 2 3])          ; => [2 4 6]
(filter #(> % 5) [3 6 2 8 4])   ; => [6 8]
(reduce #(+ %1 %2) 0 [1 2 3 4]) ; => 10
```

The full treatment, including when to reach for a named `fn` instead, lives in [Functions and Recursion](/documentation/language/functions-and-recursion).

> **Deprecated:** the older `|(...)` form with `$`, `$1`, `$&` placeholders. Use `#(...)` with `%`.

## Comments

```phel
(println 1 #_ 2 3)   ; #_ skips the next form  => prints: 1 3
[1 #_(+ 2 3) 4]      ; => [1 4]
```

`;` runs to end of line: `;;` for standalone comments, `;` for inline.

> **Deprecated:** `#` as a line-comment character (use `;`) and `#| ... |#` multiline blocks (use `(comment ...)`).

## Metadata `^`

`^` attaches metadata to the following form. A bare keyword sets it to `true`; a map merges several keys:

```phel skip
^:private (def x 10)
^{:doc "Example"} (defn foo [] nil)
```

Type hints use the same syntax: `^int`, `^"?int"`, `^{:tag "\\Foo\\Bar"}`. See [Functions and Recursion](/documentation/language/functions-and-recursion) for typed defns.

## Summary table

| Syntax      | Name               | Desugars to / meaning                          | Example                          |
|-------------|--------------------|------------------------------------------------|----------------------------------|
| `[]`        | Vector             | `(vector ...)`                                 | `[1 2 3]`                        |
| `{}`        | Hash map           | `(hash-map ...)`                               | `{:a 1 :b 2}`                    |
| `#{}`       | Set                | `(hash-set ...)`                               | `#{1 2 3}`                       |
| `'()`       | List               | quoted `(list ...)`                            | `'(1 2 3)`                       |
| `'`         | Quote              | `(quote x)`                                    | `'x`                             |
| `` ` ``     | Quasiquote         | quote with selective eval                      | `` `(1 ~x) ``                    |
| `~`         | Unquote            | evaluate within quasiquote                     | `~x`                             |
| `~@`        | Unquote-splice     | splice a sequence in quasiquote                | `~@xs`                           |
| `name#`     | Auto-gensym        | fresh hygienic symbol in quasiquote            | `` `(let [g# 1] g#) ``           |
| `#?()`      | Reader conditional | platform-specific form                         | `#?(:phel 1 :default 0)`         |
| `#?@()`     | Conditional splice | splice by platform                             | `#?@(:phel [1 2])`               |
| `#<tag>`    | Tagged literal     | call the tag's reader function                 | `#inst "2026-01-01T00:00:00Z"`   |
| `@`         | Deref              | `(deref x)`                                    | `@my-atom`                       |
| `#'`        | Var-quote          | `(var my-fn)`                                  | `#'my-fn`                        |
| `#"..."`    | Regex literal      | `(re-pattern "...")`                           | `#"\d+"`                         |
| `#(...)`    | Anonymous function | `(fn [...] ...)` with `%` args                 | `#(+ %1 %2)`                     |
| `;` `;;`    | Line comment       | comment to end of line                         | `;; note`                        |
| `#_`        | Form comment       | skip the next form                             | `#_ expr`                        |
| `^`         | Metadata           | attach metadata to next form                   | `^:private`                      |

## See also

- [Basic Types](/documentation/language/basic-types) — tagged literals, regex literals, deref, and comments in depth
- [Functions and Recursion](/documentation/language/functions-and-recursion) — the `#(...)` shorthand and typed defns
- [Macros](/documentation/language/macros) — quasiquote and auto-gensym hygiene in practice
- [Cheat Sheet](/documentation/reference/cheat-sheet) — the one-page syntax overview
