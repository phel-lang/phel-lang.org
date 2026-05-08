+++
title = "Macros"
weight = 10
aliases = ["/documentation/macros"]
+++

## Macros

Macros are compile-time functions: take code, return transformed code. Used to extend the language's syntax.

Phel's core uses macros throughout. Example: `defn` is a macro.

```phel
(defn add [a b] (+ a b))
```
transforms to:
```phel
(def add (fn [a b] (+ a b)))
```

{% php_note() %}
Macros aren't PHP functions. They run at compile-time, transforming code before execution:

```php
// PHP - No macro system
// You'd need to use code generation or eval()
```

```phel
(defmacro unless [test then else]
  `(if (not ,test) ,then ,else))

(unless false "yes" "no")  ; => "yes"
; Expands to: (if (not false) "yes" "no") at compile time
```

More powerful and safer than `eval()` or codegen.
{% end %}

## Quote

`quote` returns its argument unevaluated. Single-quote prefix is shorthand for `(quote form)`.

```phel
(quote my-sym) ; => my-sym
'my-sym ; same
```

Quote distinguishes code from data, making macros possible. Literals (numbers, strings) evaluate to themselves.

```phel
(quote 1) ; Evaluates to 1
(quote hi) ; Evaluates to the symbol hi
(quote quote) ; Evaluates to the symbol quote

'(1 2 3) ; Evaluates to the list (1 2 3)
'(print 1 2 3) ; Evaluates to the list (print 1 2 3). Nothing is printed.
```

## Define a macro

```phel
(defmacro name docstring? attributes? [params*] expr*)
```

`defmacro` creates a macro. Same params as `defn`.

With `quote` and `defmacro`, define a custom `defn` called `mydefn`:

```phel
(defmacro mydefn [name args & body]
  (list 'def name (apply list 'fn args body)))
```
Simple, doesn't cover all `defn` features, but shows the basics.

## Quasiquote

`quasiquote` improves macro readability. Inverts quoting: marks what *should* evaluate, leaves the rest unevaluated. Shorthand: `` ` `` (quasiquote), `,` (unquote), `,@` (unquote-splicing).

`mydefn` with quasiquote:

```phel
(defmacro mydefn [name args & body]
  `(def ,name (fn ,args ,@body)))
```

{% clojure_note() %}
Quasiquote syntax works like Clojure:
- `` ` `` for quasiquote (syntax-quote)
- `,` for unquote
- `,@` for unquote-splicing
{% end %}
