+++
title = "Global and local bindings"
weight = 6
+++

## Definition (def)

```phel
(def name meta? value)
```
This special form binds a value to a global symbol. A definition can be redefined in both of REPL and built one.

```phel
(def my-name "phel")
(def sum-of-three (+ 1 2 3))

# OK.
(def duplicated "first-duplication")
(def duplicated "second-duplication")
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

## Binding

While `let` creates a new lexical context, `binding` temporarily redefines existing definitions while executing the body. This can be useful when writing tests on functions depending on external state as `binding` allows to remap existing functions or values with mocks.

```phel
(ns my-app\tests\demo
  (:require phel\test :refer [deftest is]))

# Function that would return e.g. "x86_64", depending on the environment:
(defn get-system-architecture [] (php/php_uname "m"))

(defn greet-user-by-architecture []
  (print "Hello" (get-system-architecture) "user!"))

# Bindings with let are not effective outside it's lexical scope
(deftest greeting-test-let
  (let [get-system-architecture |(str "i386")] # <- mock function
    (let [greeting-out (with-output-buffer (greet-user-by-architecture))]
      (is (= "Hello i386 user!" greeting-out)
          "i386 system user is greeted accordingly"))))

# Test fails on a x86_64 system, evaluating to "Hello x86_64 user!":
# ✘ greeting-test-let: i386 system user is greeted accordingly

# With binding, a mock function can bound in place of the original one
(deftest greeting-test-binding
  (binding [get-system-architecture |(str "i386")] # <- mock function
    (let [greeting-out (with-output-buffer (greet-user-by-architecture))]
      (is (= "Hello i386 user!" greeting-out)
          "i386 system user is greeted accordingly"))))

# Test is successful:
# ✔ greet-test-binding: i386 system user is greeted accordingly

```

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
