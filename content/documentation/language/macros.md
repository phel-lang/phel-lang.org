+++
title = "Macros"
weight = 10
description = "Write compile-time code that rewrites code: defmacro, quasiquote, macroexpand, gensym hygiene, and when a macro is worth it"
aliases = ["/documentation/macros"]
+++

Macros are compile-time callables. They receive unevaluated code as data, transform it, and return new code for the compiler to process. This lets you add new syntax that functions cannot express.

## Why macros

In PHP, you cannot add new language constructs. Want `unless` (the opposite of `if`)? You are stuck with a function. Functions evaluate all arguments before the call, which breaks short-circuit logic and makes them second-class compared to `if`:

```php
// PHP: forced to use closures to avoid premature evaluation
function unless(bool $cond, callable $then, callable $else): mixed {
    return $cond ? $else() : $then();
}
```

In Phel, a macro receives the raw code unevaluated, rewrites it, and the result compiles normally:

```phel
(defmacro unless [test then else]
  `(if (not ~test) ~then ~else))

(unless false "yes" "no")  ; => "yes"
;; Expands to: (if (not false) "yes" "no")
;; Only "yes" is ever evaluated. Behaves identically to a built-in if.
```

This works because **Phel code is data**. The call `(unless false "yes" "no")` is a plain Phel list, the same persistent list you work with everywhere. Macros manipulate that list at compile time using ordinary Phel functions.

`defn`, `when`, `and`, `or`, `->`, `->>` are all macros in Phel's standard library. They are not special compiler syntax. They are Phel code that rewrites other Phel code.

`defn` itself expands to `def` + `fn`:

<!-- phel-test: skip -->
```phel
(defn add [a b] (+ a b))
;; expands to:
(def add (fn [a b] (+ a b)))
```

{% php_note() %}
PHP has no macro system. The common alternatives each have significant limitations:

- `eval()` runs at runtime, has security implications, and cannot be type-checked or linted
- Code generation produces files on disk, requires a build step, and the output is opaque
- Attributes are metadata only. They cannot transform the code they annotate.

Phel macros run at compile time inside the compiler pipeline, produce normal Phel AST nodes, and are fully inspectable with `macroexpand`.
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

<!-- phel-test: skip -->
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

`quasiquote` improves macro readability. Inverts quoting: marks what *should* evaluate, leaves the rest unevaluated. Shorthand: `` ` `` (quasiquote), `~` (unquote), `~@` (unquote-splicing).

`mydefn` with quasiquote:

```phel
(defmacro mydefn [name args & body]
  `(def ~name (fn ~args ~@body)))
```

{% clojure_note() %}
Quasiquote syntax is identical to Clojure:
- `` ` `` for quasiquote (syntax-quote)
- `~` for unquote
- `~@` for unquote-splicing
{% end %}

## Expanding macros

To see what a macro produces, expand it without running it. `macroexpand-1` does a single expansion step; `macroexpand` keeps expanding until the top form is no longer a macro call. Quote the form so it stays code.

```phel
(defmacro unless [test then else]
  `(if (not ~test) ~then ~else))

(macroexpand-1 '(unless false "yes" "no"))
; => (if (phel.core/not false) "yes" "no")

(macroexpand-1 '(when true 1 2))
; => (if true (do 1 2))
```

Quasiquote fully qualifies referenced symbols (`not` becomes `phel.core/not`), which is what keeps macros from breaking when the caller has shadowed a name. This is your main debugging tool: if a macro misbehaves, expand it and read the generated code.

## Hygiene and `gensym`

A macro that introduces its own local bindings can accidentally capture (shadow) a name from the caller. To avoid this, generate a unique symbol with `gensym`:

```phel
(gensym) ; => __phel_1 (a fresh, unique name on every call)
(gensym) ; => __phel_2
```

Inside a quasiquote, the `name#` suffix auto-generates a `gensym` for you, so the same `name#` refers to one fresh symbol throughout the template:

```phel
(defmacro my-or [a b]
  `(let [tmp# ~a]
     (if tmp# tmp# ~b)))

(my-or false 42) ; => 42

(macroexpand-1 '(my-or false 42))
; => (let [tmp__1 false] (if tmp__1 tmp__1 42))
```

The expanded `tmp__1` is unique per expansion, so it cannot clash with a `tmp` the caller already has. Reach for `gensym` (or `name#`) whenever a macro binds a local the user did not write.

## When to write a macro

Most of the time you do not need one. **Prefer a function.** Functions are easier to read, test, compose, and pass around. Reach for a macro only when a function genuinely cannot do the job:

- **New syntax or binding forms** the language does not provide.
- **Control flow** that must skip or reorder evaluation of its arguments (a function evaluates all its arguments first).
- **Compile-time work**, where you want code generated or checked before the program runs.

If the same result is achievable by passing values or functions, write a function.

## Next steps

- [Functions and recursion](/documentation/language/functions-and-recursion/) - the default tool; prefer it over macros
- [Basic types](/documentation/language/basic-types/) - quote, lists, and symbols that macros manipulate
- [Cheat sheet](/documentation/reference/cheat-sheet/) - keep it open while coding
