+++
title = "Call PHP the Clojure Way"
aliases = [ "/blog/phel-0-50-the-last-zero" ]
description = "Create PHP objects with new, call methods with a leading dot, and reach static members with a slash. One spelling for every PHP member, the same one Clojure uses."
date = 2026-08-14
+++

Phel runs on PHP. Every PHP class, function and Composer package is one call away. That is the reason to pick Phel over another Lisp.

But the calls used to look like nothing else. You wrote `(php/new ...)` to build an object, `(php/-> obj (method ...))` to call it, and `(php/:: Class (method ...))` for a static method. Three forms to learn. None of them looked like Clojure, so what you knew from Clojure did not help.

Now PHP interop reads the way Clojure reads Java. `new` builds, `.method` calls, `Class/member` reaches the static side. Every PHP member has one spelling.

> Learn it once in Clojure. Use it in Phel.

## `new` builds an object, and so does a trailing dot

`new` takes a class and its constructor arguments. A dot after the class name does the same thing:

```phel
(def launch (new DateTimeImmutable "2026-08-14"))

(.format launch "Y-m-d")
; => "2026-08-14"

(.format (DateTimeImmutable. "2026-08-14") "l")
; => "Friday"
```

Global PHP classes work as they are. For a namespaced class, import it with `:use` and write the namespace with dots. You name the namespace once, at the top:

```phel
(ns app.dice
  (:use Random.Randomizer)
  (:use Random.Engine.Mt19937))

(def dice (Randomizer. (Mt19937. 42)))

(.getInt dice 1 6)
; => 1
```

The same works for any class Composer installs. Phel loads `vendor/autoload.php` for you.

## A leading dot calls a method, and `->` chains the calls

`(.method obj args)` is `$obj->method(args)`. The method name is part of the symbol, so it reads like a normal function call. That means it also fits the threading macros:

```phel
(def launch (new DateTimeImmutable "2026-08-14"))

(-> launch
    (.modify "+1 day")
    (.format "D, d M"))
; => "Sat, 15 Aug"
```

Compare the PHP it compiles to:

```php
$launch->modify("+1 day")->format("D, d M");
```

Same order, same steps. And a method call is an ordinary form, so you can put it inside `#(...)` and pass it to `map`:

```phel
(->> ["2026-08-14" "2026-12-25"]
     (map #(DateTimeImmutable. %))
     (map #(.format % "D")))
; => ("Fri" "Fri")
```

## `.-field` reads a property, and `set!` writes one

A dash after the dot reads a public property. `set!` assigns it:

```phel
(def interval (DateInterval. "PT30S"))

(.-s interval)
; => 30

(def box (new stdClass))
(set! (.-count box) 1)
(.-count box)
; => 1
```

`set!` mutates a PHP object in place. Phel data never changes, PHP objects do. Keep these calls at the edge of your code, where Phel meets a library.

## A slash reaches static members, constants and enum cases

`Class/member` is the static side of a class: a static method, a class constant, or an enum case.

```phel
(.format (DateTimeImmutable/createFromFormat "Y-m-d" "2020-03-22") "Y-m-d")
; => "2020-03-22"

DateTimeImmutable/ATOM
; => "Y-m-d\\TH:i:sP"

(.-name RoundingMode/HalfEven)
; => "HalfEven"
```

An enum case is an object, so `.-name` reads its name like any other property. Static properties work too, and `(set! Class/field v)` assigns one.

Plain PHP functions keep their `php/` prefix: `(php/strlen "abc")`. So do global constants such as `php/PHP_EOL`. The prefix tells you the name comes from PHP, not from Phel.

The editor knows these forms. The language server completes and hovers methods, properties, static members and PHP superglobals such as `php/$_SERVER`.

## The old forms are gone

In Phel 0.50 the old spellings started to print a deprecation warning. Since 0.52 they don't compile. This is the old syntax, shown here only so you can recognise it:

<!-- phel-test: skip -->
```phel
(php/new DateTimeImmutable "2026-08-14")
```

Phel 0.53 stops at it and names the fix:

```text
[PHEL012] "php/new" is no longer valid source for constructing a PHP object. Use "(new \Foo arg)" or "(\Foo. arg)" instead.
```

The move is mechanical. `php/new` becomes `new`, `(php/-> obj (m a))` becomes `(.m obj a)`, and `(php/:: Foo (m a))` becomes `(Foo/m a)`. The design is in [ADR 0007](https://github.com/phel-lang/phel-lang/blob/v0.50.0/docs/adr/0007-clojure-style-interop-is-the-source-spelling.md).

## Also in Phel 0.50

0.50 was the clean-up before 1.0. Everything that printed a deprecation notice in 0.49 is gone.

- **Breaking:** the old reader syntax is removed. `#` comments become `;`, `|(...)` becomes `#(...)` with `%`, `,` and `,@` become `~` and `~@`, and `foo$` gensyms become `foo#`.
- **Breaking:** `,` is now whitespace, as in Clojure. An old macro that unquotes with `,` still compiles and quietly quotes its argument. Run the grep from the upgrade notes.
- **Breaking:** the old core aliases are removed: `push`, `put`, `unset` and `values` are now `conj`, `assoc`, `dissoc` and `vals`.
- **Breaking:** core functions declare real arities. A wrong argument count is an error, and `(max)` with no arguments fails at compile time.
- **Breaking:** lazy sequences print as `(2 3 4)`, not `@[2 3 4]`.
- **Breaking:** `phel index --out` is now `--output`, and `phel config --json` is now `--format=json`.
- `sort-by` calls its key function once per element, not twice per comparison.
- `phel balance` finds unbalanced `()`, `[]` and `{}`, and `--fix` appends the missing closers.
- `phel bench` runs `defbench` benchmarks, stores a baseline and fails a run that regresses.
- Real arities make calls faster: `partial` 30x, `fnil` 50x, `sort` on strings 22.7x.
- Go to definition works on local bindings from `let`, `fn`, `loop` and friends.

One spelling per idea. The one Clojure already taught you.

Shipped in [Phel 0.50](/releases/0-50-the-last-zero/). Upgrade notes: [0.50](/documentation/reference/upgrading/#0-50).
