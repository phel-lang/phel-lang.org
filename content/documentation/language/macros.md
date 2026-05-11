+++
title = "Macros"
weight = 10
aliases = ["/documentation/macros"]
+++

## Macros

PHP has functions and classes. Phel has those too, plus *macros* - callables that run at **compile time**, not runtime. They receive unevaluated code as data, transform it, and return new code for the compiler to process.

**Why does this matter?** In PHP, you can't add new language constructs. Want `unless` (opposite of `if`)? The closest is a function - but functions evaluate all arguments before the call, which breaks short-circuit logic and makes them second-class compared to `if`:

```php
// PHP: forced to use closures to avoid premature evaluation
function unless(bool $cond, callable $then, callable $else): mixed {
    return $cond ? $else() : $then();
}
```

In Phel, a macro receives the raw code unevaluated, rewrites it, and the result compiles normally:

```phel
(defmacro unless [test then else]
  `(if (not ,test) ,then ,else))

(unless false "yes" "no")  ; => "yes"
;; Expands to: (if (not false) "yes" "no")
;; Only "yes" is ever evaluated - identical to a built-in if.
```

This works because **Phel code is data**. The call `(unless false "yes" "no")` is a plain Phel list - the same persistent list you work with everywhere. Macros manipulate that list at compile time using ordinary Phel functions.

`defn`, `when`, `and`, `or`, `->`, `->>` - all macros in Phel's standard library. Not special compiler syntax: just Phel code that rewrites other Phel code.

`defn` itself expands to `def` + `fn`:

```phel
(defn add [a b] (+ a b))
;; expands to:
(def add (fn [a b] (+ a b)))
```

{% php_note() %}
PHP has no macro system. The alternatives - `eval()`, code generation, Attributes - all operate differently:

- `eval()` runs at runtime, has security implications, can't be type-checked or linted
- Code generation produces files on disk, requires a build step, output is opaque
- Attributes are metadata only - they can't transform the code they annotate

Phel macros are none of these. They run at compile time inside the compiler pipeline, produce normal Phel AST nodes, and are fully inspectable with `macroexpand`.
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
