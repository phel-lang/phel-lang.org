+++
title = "Macros"
weight = 13
description = "Write compile-time code that rewrites code: defmacro, quasiquote, macroexpand, gensym hygiene, and when a macro is worth it."
aliases = ["/documentation/macros"]

[extra]
difficulty = "advanced"
+++

A macro runs at compile time. It receives code as data, rewrites it, and returns new code for the compiler. After this page you can write a macro, check what it expands to, and avoid name clashes in the generated code.

Most code does not need macros. Write a function first, and read [When to write a macro](#when-to-write-a-macro) before you reach for one.

## Write a macro

`defmacro` takes the same arguments as `defn`. The difference: the arguments arrive unevaluated, as code. Here is `unless`, the opposite of `if`:

```phel
(defmacro unless [test then else]
  `(if (not ~test) ~then ~else))

(unless false "yes" "no") ; => "yes"
```

A call to `(unless false "yes" "no")` becomes `(if (not false) "yes" "no")` before the program runs. Only the chosen branch is evaluated, like a built-in `if`. A function cannot do this: it evaluates all its arguments before the call.

This works because Phel code is data. The call is a plain list, and a macro changes that list with ordinary Phel functions. `defn`, `when`, `and`, `or`, `->` and `->>` are macros in the standard library, not compiler syntax.

{% php_note() %}
PHP has no macros. The usual replacements each have a limit:

- `eval()` runs at runtime and cannot be linted or type-checked.
- Code generation writes files to disk and needs a build step.
- Attributes are metadata only. They cannot change the code they annotate.

Phel macros run inside the compiler, produce normal Phel code, and you can inspect the result with `macroexpand`.
{% end %}

## Quasiquote

The macro body above is a template. Three reader shortcuts build it:

| Shortcut | Name | Effect |
|---|---|---|
| `` ` `` | quasiquote | keep the form as code, do not evaluate it |
| `~` | unquote | insert the value of this expression |
| `~@` | unquote-splicing | insert the items of this list one by one |

A small `defn` built with all three:

```phel
(defmacro mydefn [name args & body]
  `(def ~name (fn ~args ~@body)))

(mydefn add [a b] (+ a b))
(add 1 2) ; => 3
```

Quasiquote also qualifies the symbols it contains: `not` becomes `phel.core/not`. So the expansion still works when the caller has a local named `not`.

{% clojure_note() %}
Same quasiquote, unquote and splicing tokens as Clojure.
{% end %}

## Expand a macro

To see what a macro produces, expand it without running it. Quote the form so it stays code. `macroexpand-1` does one step. `macroexpand` repeats until the outer form is no longer a macro call:

```phel
(defmacro unless [test then else]
  `(if (not ~test) ~then ~else))

(macroexpand-1 '(unless false "yes" "no"))
; => (if (phel.core/not false) "yes" "no")

(macroexpand-1 '(when true 1 2))
; => (if true (do 1 2))
```

When a macro misbehaves, expand it and read the generated code.

## Hygiene and gensym

A macro that binds its own local can hide a name the caller uses. Add `#` to the end of a local name inside a quasiquote. Each expansion then gets a fresh, unique symbol, and the same `name#` means the same symbol throughout the template:

```phel
(defmacro my-or [a b]
  `(let [tmp# ~a]
     (if tmp# tmp# ~b)))

(my-or false 42) ; => 42

(macroexpand-1 '(my-or false 42))
; => (let [tmp__1 false] (if tmp__1 tmp__1 42))
```

`tmp__1` cannot clash with a `tmp` the caller already has. Outside a quasiquote, call `(gensym)` to get a unique symbol such as `__phel_1`. Use `name#` or `gensym` whenever a macro binds a local the user did not write.

## Quote

The single quote returns a form without evaluating it. `'x` is short for `(quote x)`. Macros depend on this: it is how code stays data.

```phel
'my-sym        ; => my-sym, a symbol
'(print 1 2 3) ; => (print 1 2 3), a list. Nothing is printed.
(quote 1)      ; => 1, literals evaluate to themselves
```

Quasiquote is quote with holes: `~` marks the parts that should be evaluated.

## When to write a macro

Prefer a function. Functions are easier to read, test, compose and pass around. Write a macro only when a function cannot do the job:

- **New syntax or binding forms** that the language does not have.
- **Control flow** that must skip or reorder the evaluation of its arguments.
- **Compile-time work**, when code must be generated or checked before the program runs.

If passing values or functions gives the same result, write a function. For a longer walkthrough, read [Writing your first macro](/blog/writing-your-first-macro/). Every reader shortcut, including `` ` `` and `name#`, is listed on [Reader shortcuts](/documentation/language/reader-shortcuts/).
