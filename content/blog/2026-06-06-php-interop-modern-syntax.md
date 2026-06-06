+++
title = "PHP Interop in Phel: Modern Syntax"
aliases = [ "/blog/php-interop-modern-syntax" ]
description = "Named arguments, by-reference output, PHP magic methods, typed wrappers, native enums. Calling PHP from a Lisp, the 2026 way."
date = 2026-06-06
+++

Phel compiles to PHP, so the whole ecosystem is one prefix away. Add `php/` to any function, class, or constant and it just runs. Recent releases pushed the interop further: named arguments, by-reference output, PHP magic methods on structs, and typed wrappers that satisfy a framework's type checker. Here is the modern toolkit, with runnable samples.

## The basics

Any PHP function takes a `php/` prefix:

```phel
(php/strlen "test")  ; => 4
(php/strtoupper "phel") ; => "PHEL"
```

Classes get terse shorthands that expand to the verbose `php/*` forms:

| Shorthand | Expands to |
|---|---|
| `(ClassName. args)` | `(php/new ClassName args)` |
| `(.method obj args)` | `(php/-> obj (method args))` |
| `(.-field obj)` | `(php/-> obj field)` |
| `(ClassName/method args)` | `(php/:: ClassName (method args))` |

```phel
(php/-> (php/new \DateTimeImmutable "2024-03-10")
        (modify "+1 day")
        (format "Y-m-d")) ; => "2024-03-11"
```

## Named arguments

PHP 8 named arguments map cleanly to keywords. Put them after a `:&` marker as `:key value` pairs. Works in `php/new`, `php/->`, and `php/::`:

```phel
(let [dt (php/:: \DateTime
                 (createFromFormat :& :format "Y-m-d" :datetime "2026-06-06"))]
  (php/-> dt (format "Y-m-d"))) ; => "2026-06-06"
```

Order no longer matters, and you skip the positional-argument guessing game.

## By-reference output

Some PHP functions write through a `&$ref` parameter (`preg_match`, `sort`, ...). Wrap a `let`-bound local in `php/ref` to pass it by reference:

```phel
(let [subject "order-42"
      matches (php/array)]
  (php/preg_match "/(\d+)/" subject (php/ref matches))
  (php/aget matches 1)) ; => "42"
```

The local must be `let`-bound, because a top-level `def` is not a PHP variable.

## PHP magic methods on structs

A `defstruct` compiles to a real PHP class, so it can expose magic methods (`__invoke`, `__toString`, `__get`, ...). Declare them inline through a `:php` block. The first argument binds to `$this`:

```phel
(defstruct money [cents]
  :php
  (__toString [this] (str "$" (/ (get this :cents) 100))))

(php/strval (money 500)) ; => "$5"
```

A custom `__invoke` must take exactly one call argument or be variadic, since a struct is already callable as a key lookup. Phel rejects an incompatible arity at compile time instead of letting PHP raise a fatal.

## Maps and typed objects

Keep maps as your working representation. At the edge where a typed object is unavoidable, `hydrate` rebuilds an instance from a map (skipping the constructor, like an ORM) and `bean` reads it back:

<!-- phel-test: skip -->
```phel
;; class App\Point { public int $x; public int $y; }
(def p (hydrate "App\\Point" {:x 1 :y 2})) ; => App\Point instance
(bean p)                                    ; => {:x 1, :y 2}
```

## Typed and annotated output

When generated PHP must satisfy a framework, opt-in metadata enriches it. `^{:tag T}` adds typed signatures, `^{:php/attr [...]}` emits PHP 8 attributes, and `^{:php/json true}` implements `\JsonSerializable`:

```phel
(defstruct ^{:php/attr [:ORM/Entity] :php/json true} product
  [^{:tag int :php/attr [:ORM/Id]} id
   ^{:tag string} name])
```

`phel export` then emits `#[ORM\Entity]`, typed properties, and `JsonSerializable` on the wrapper. No hand-written controller shim.

## Native enums and exceptions

`defenum` compiles to a native PHP backed enum (handy for Doctrine/Symfony columns), defined once and consumed from PHP or bridged to keywords with `phel.reflect`. It also defines a `Status?` predicate.

```phel
(defenum Status :active "active" :inactive "inactive")
;; emits: enum Status: string { case active = "active"; case inactive = "inactive"; }
```

`defexception` defines an exception extending a chosen parent, so framework `catch` blocks match it by type:

```phel
(defexception NotFound \RuntimeException)

(try
  (throw (NotFound "missing"))
  (catch \RuntimeException e (php/-> e (getMessage)))) ; => "missing"
```

## Catching PHP exceptions

Native exceptions cross the boundary unchanged. Catch them by class, or `\Throwable` for anything:

```phel
(try
  (php/intdiv 1 0)
  (catch \DivisionByZeroError e
    (php/-> e (getMessage)))) ; => "Division by zero"
```

## Where to go next

The interop layer is small but covers the whole surface: functions, classes, arrays, magic methods, named arguments, references, and typed output. Reach for Phel's immutable data structures by default, and drop to `php/*` exactly where a PHP library expects it.

Full reference: [PHP Interop](/documentation/php-interop/).
