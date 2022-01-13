+++
title = "Global and local bindings"
weight = 6
+++

## Definition (def)

```phel
(def name meta? value)
```
This special form binds a value to a global symbol. A definition cannot be redefined at a later point.

```phel
(def my-name "phel")
(def sum-of-three (+ 1 2 3))
```

To each definition metadata can be attached. Metadata is either a Keyword, a String or a Map.

```phel
(def my-private-definition :private 12)
(def my-name "Stores the name of this language" "Phel")
(def my-other-name {:private true :doc "This is my doc"} "My value")
```

## Local bindings (let)

```phel
(let [bindings*] expr*)
```
Creates a new lexical context with assignments defined in bindings. Afterwards the list of expressions is evaluated and the value of the last expression is returned. If no expression is given `nil` is returned.

```phel
(let [x 1
      y 2]
  (+ x y)) # Evaluates to 3

(let [x 1
      y (+ x 2)]) # Evaluates to nil
```
All assignments defined in _bindings_ are immutable and cannot be changed.

## Variables

```phel
(var value)
```

Variables provide a way to manage mutable state. Each variable contains a single value. To create a variable use the `var` function.

```phel
(def foo (var 10)) # Define a variable with value 10
```

The `deref` function can be used to extract the value from the variable. The `set!` function is use to set a new value to the variable.

```phel
(def foo (var 10))

(deref foo) # Evaluates to 10
(set! foo 20) # Set foo to 20
(deref foo) # Evaluates to 20
```

To update a variable with a function the `swap!` function can be used.

```phel
(def foo (var 10))
(swap! foo + 2) # Evaluates to 12
(deref foo) # Evaluates to 12
```
