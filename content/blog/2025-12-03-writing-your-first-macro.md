+++
title = "Writing Your First Macro in Phel"
aliases = [ "/blog/first-macro" ]
description = "Learn how macros let you extend the language itself, with step-by-step examples that turn repetitive patterns into reusable syntax."
+++

If you have played with [threading macros](/blog/threading-macros) or [pattern matching](/blog/pattern-matching), you have already been using macros without thinking about it. Now it is time to write your own.

PHP developers sometimes reach for `eval()` or code generation tools when they need to produce code dynamically. Clojure developers know a better way: macros. Phel brings that same power to the PHP ecosystem, letting you extend the language at compile time instead of juggling strings at runtime.

## Functions vs macros: when code is data

Regular functions receive values and return values. Macros receive *code* and return *code*. The transformation happens at compile time, so there is zero runtime cost.

Say you keep writing this pattern:

```phel
(when (not logged-in?)
  (redirect "/login"))
```

You could wrap it in a function, but then `logged-in?` would be evaluated before the function even sees it. With a macro you create actual new syntax:

```phel
(defmacro unless [test & body]
  `(if ,test nil (do ,@body)))

(unless logged-in?
  (redirect "/login"))
```

At compile time, Phel rewrites that call into an `if` form. Your condition stays lazy, just like the built-in control flow. Clojure folks will feel right at home; the syntax is nearly identical.

> Fun fact: Phel's core already has `when-not` which does exactly this. Peek at the source and you will find the same pattern we just wrote.

## Quote and unquote: treating code as data

Two concepts make macros tick: **quote** and **unquote**.

**Quote** (the `'` character) stops evaluation. It hands you raw code instead of running it:

```phel
(+ 1 2)    # => 3
'(+ 1 2)   # => (+ 1 2), just a list
```

**Quasiquote** (the backtick `` ` ``) works like quote, but you can poke holes with **unquote** (`,`) to let specific pieces evaluate:

```phel
`(1 2 ,(+ 1 2))   # => (1 2 3)
```

Think of quasiquote as a template. Most of it stays literal; the `,` parts get filled in. If you have written Clojure, this is exactly what you know.

## Building `unless` step by step

```phel
(defmacro unless
  "Evaluates body when test is false."
  [test & body]
  `(if ,test nil (do ,@body)))
```

What is happening here:

- `defmacro` defines a macro, just like `defn` defines a function.
- The docstring explains what the macro does.
- `test` and `body` receive *unevaluated code*, not values.
- The backtick starts a code template.
- `,test` splices in the test expression; `,@body` splices the body expressions inline.

When you write:

```phel
(unless (> x 10)
  (print "x is small")
  (log-warning "check the value"))
```

Phel transforms it at compile time to:

```phel
(if (> x 10) nil (do (print "x is small") (log-warning "check the value")))
```

No runtime overhead, no string manipulation, no `eval()`.

## A practical example: timing code

Here is something you cannot do with a plain function. Say you want to measure how long a chunk of code takes. Phel's core has a `time` macro that does exactly this:

```phel
(defmacro time
  "Evaluates expr and prints the time it took. Returns the value of expr."
  [expr]
  `(let [start$ (php/microtime true)
         ret$ ,expr]
     (println "Elapsed time:" (* 1000 (- (php/microtime true) start$)) "msecs")
     ret$))

(time (slow-operation))
# Prints: Elapsed time: 142.3 msecs
```

The `$` suffix is a convention for local bindings inside macros. It helps avoid name collisions with user code. The body runs between the two `microtime` calls, and you get the elapsed time printed for free.

In PHP you would wrap this in a closure or duplicate the timing code everywhere. The macro keeps it clean and reusable.

## Avoiding name collisions with gensym

The `$` suffix works for simple cases, but what if your macro could be nested or the user happens to use `start$` as a variable? For guaranteed uniqueness, use `gensym` to generate fresh symbols.

Here is how Phel's core implements `with-output-buffer`:

```phel
(defmacro with-output-buffer
  "Everything printed inside the body is captured and returned as a string."
  [& body]
  (let [res (gensym)]
    `(do
       (php/ob_start)
       ,@body
       (let [,res (php/ob_get_contents)]
         (php/ob_end_clean)
         ,res))))

(with-output-buffer
  (print "Hello ")
  (print "World"))
# => "Hello World"
```

We call `gensym` outside the quasiquote to get a unique symbol, then unquote it with `,res` wherever we need it. No matter how many times you nest `with-output-buffer`, each expansion gets its own symbol.

## More patterns from Phel's core

**Short-circuit evaluation with `or`:**

```phel
(defmacro or
  "Returns the first truthy value, or the last value."
  [& args]
  (case (count args)
    0 nil
    1 (first args)
    (let [v (gensym)]
      `(let [,v ,(first args)]
         (if ,v ,v (or ,@(next args)))))))
```

Notice how `or` uses `gensym` because it recursively expands itself. Each level needs its own unique binding.

**Auto-logging function calls:**

```phel
(defmacro defn-traced
  "Defines a function that logs when called."
  [name args & body]
  `(defn ,name ,args
     (println "Calling" ',name)
     ,@body))
```

The `',name` pattern (quote then unquote) inserts the literal symbol name into the output, so the log shows the actual function name.

## When to reach for a macro

Macros are powerful, but they are not always the right tool:

- **Use a macro** when you need to control evaluation order.
- **Use a macro** when you want new syntax (like `time` or `with-output-buffer`).
- **Use a macro** to eliminate boilerplate at compile time.
- **Use a function** for everything else.

Functions are easier to debug, compose, and pass around. If a function can do the job, stick with it. Reach for macros when functions hit their limits.

## Debugging with macroexpand

When a macro misbehaves, `macroexpand` shows you the generated code without running it:

```phel
(macroexpand '(unless (> x 10) (print "small")))
# => (if (> x 10) nil (do (print "small")))
```

Paste in your macro call, see what comes out. It takes the mystery out of debugging.

## Go build something

You now have the same metaprogramming tools that make Lisp so flexible. Start small: spot a pattern you repeat often and wrap it in a macro. Check the [macro documentation](/documentation/macros/) when you want to dig deeper.

Once you get comfortable, explore Phel's core. Even `defn` is just a macro that expands to `def` plus `fn`. 

The `->` and `->>` threading macros, `cond`, `case`, `for` — they are all built with the same primitives you just learned. That is the Lisp way, and now it runs on PHP.
