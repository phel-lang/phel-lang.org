+++
title = "Global and local bindings"
weight = 3
description = "Bind values to names with def and let, manage mutable state with atoms, and rebind dynamic vars with binding"
aliases = ["/documentation/global-and-local-bindings"]

[extra]
difficulty = "beginner"
+++

After this page you can name values: `def` for globals, `let` for locals, `atom` for the rare value that must change, and `binding` for dynamic vars.

## Definition (def)

<!-- phel-test: skip -->
```phel
(def name docstring? value)
```

`def` binds a value to a global name in the current namespace:

```phel
(def my-name "phel")
(def sum-of-three (+ 1 2 3))
```

A global cannot be defined twice. A second `(def my-name ...)` in the same namespace fails at compile time with `PHEL004`.

Add a docstring before the value, or metadata before the name:

```phel
(def answer "The answer to everything." 42)
(def ^:private secret 12)
(def ^{:doc "This is my doc" :private true} other-name "My value")
```

A private definition cannot be used from other namespaces. Use `defn` to define a function: see [Functions and recursion](/documentation/language/functions-and-recursion/).

## Local bindings (let)

<!-- phel-test: skip -->
```phel
(let [bindings*] expr*)
```

`let` binds names for the expressions in its body and returns the value of the last one. Each binding can use the names bound before it:

```phel
(let [x 1
      y (+ x 2)]
  (* x y)) ; => 3
```

Bindings are immutable. An inner `let` can shadow an outer name, but it never changes the outer value:

```phel
(let [x 1]
  [(let [x 10] x) x]) ; => [10 1]
```

`let` can also take a vector or map apart by shape:

```phel
(let [{:keys [name age]} {:name "Alice" :age 30}]
  (str name " is " age)) ; => "Alice is 30"
```

The full rules are on [Destructuring](/documentation/language/destructuring/).

{% php_note() %}
A PHP variable can be reassigned at any time. A Phel local cannot. To compute a new value, bind a new name or pass the value to a function. This removes a whole class of "who changed this variable" bugs.
{% end %}

## Atoms

An atom holds one value that can change over time. Use it for application state, caches, and counters. Read it with `deref` or `@`. Change it with `swap!` (apply a function) or `reset!` (set a value):

```phel
(def counter (atom 0))

@counter              ; => 0
(swap! counter inc)   ; => 1
(swap! counter + 10)  ; => 11
(reset! counter 0)    ; => 0
(deref counter)       ; => 0
```

`swap!` passes the current value as the first argument, then any extra arguments. It works well with map updates:

```phel
(def state (atom {:count 0}))
(swap! state update :count inc) ; => {:count 1}
```

Functions that change state end in `!` by convention. Prefer plain immutable values, and keep atoms at the edges of your program.

{% callout(kind="note") %}
The old atom names `var`, `var?`, and `set!` are gone. Use `atom`, `atom?`, and `reset!`. `var` and `#'sym` now return a [Var handle](#variables).
{% end %}

## Dynamic binding

A dynamic var is a global that can take a different value for the duration of a call. Mark it `^:dynamic` and rebind it with `binding`:

```phel
(def ^:dynamic *env* "production")

(defn current-env [] *env*)

(current-env)                        ; => "production"
(binding [*env* "test"] (current-env)) ; => "test"
(current-env)                        ; => "production"
```

`let` would not work here: it creates a new local, so `current-env` still sees the global. `binding` changes what every function sees while the body runs, then restores the old value. The rebinding is local to the current fiber.

`binding` throws if the var is not `^:dynamic`. To replace any global for the duration of a body, such as a function you want to stub in a test, use `with-redefs`:

```phel
(defn system-arch [] (php/php_uname "m"))
(defn greet [] (str "Hello " (system-arch) " user"))

(with-redefs [system-arch (fn [] "i386")]
  (greet)) ; => "Hello i386 user"
```

`with-bindings` does the same as `binding` from a map of Var handles to values: `(with-bindings {#'*env* "test"} (current-env))`. More stubbing techniques are in [Testing](/documentation/guides/testing/#mocking).

## Vars {#variables}

Every `def` creates a `Var`. `(var name)`, or the `#'name` shorthand, returns the Var itself instead of its value:

```phel
(def my-name "phel")

#'my-name           ; => #'user/my-name
(var? #'my-name)    ; => true
(deref #'my-name)   ; => "phel"
```

Change the value of a Var with `alter-var-root`:

```phel
(def total 0)
(alter-var-root #'total inc)
total ; => 1
```

Most code never needs Var handles. They are useful for REPL tools and for watching a global with `add-watch`. The full list is in the [core API](/documentation/reference/api/core/).

Next: [Control flow](/documentation/language/control-flow/) shows how to branch and loop.
