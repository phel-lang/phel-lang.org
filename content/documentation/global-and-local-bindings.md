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

{% php_note() %}
`let` creates block-scoped bindings, similar to PHP's block scope, but with immutability:

```php
// PHP - mutable variables
$x = 1;
$y = $x + 2;
$x = 10;  // Can reassign

// Phel - immutable bindings
(let [x 1
      y (+ x 2)]
  ; x = 10  <- This would be a compile error!
  (+ x y))
```
{% end %}

{% clojure_note() %}
`let` works exactly like Clojure's `let`—creates lexically scoped, immutable bindings.
{% end %}

## Binding

While `let` creates a new lexical context, `binding` temporarily redefines existing definitions while executing the body. This can be useful when writing tests on functions depending on external state as `binding` allows to remap existing functions or values with mocks.

**Key difference:**
- `let` creates new local variables (lexical scope only)
- `binding` temporarily overrides global definitions (dynamic scope)

```phel
# Example 1: Simple binding demonstration
(def *config* "production")

(defn get-config []
  *config*)

(get-config)  # => "production"

# let doesn't affect the global definition
(let [*config* "test"]
  (get-config))  # => "production" (still uses global!)

# binding temporarily overrides the global definition
(binding [*config* "test"]
  (get-config))  # => "test" (uses binding!)

(get-config)  # => "production" (back to original)

# Example 2: Mocking functions for testing
(defn get-system-architecture []
  (php/php_uname "m"))

(defn greet-user-by-architecture []
  (str "Hello " (get-system-architecture) " user!"))

# Without binding - calls actual system function
(greet-user-by-architecture)  # => "Hello x86_64 user!" (or your system arch)

# With let - doesn't work! Function still calls original
(let [get-system-architecture |(str "i386")]
  (greet-user-by-architecture))  # => "Hello x86_64 user!" (original still used!)

# With binding - successfully mocks the function
(binding [get-system-architecture |(str "i386")]
  (greet-user-by-architecture))  # => "Hello i386 user!" (mocked!)

# Example 3: Testing with binding
(ns my-app\tests\demo
  (:require phel\test :refer [deftest is]))

(deftest greeting-test-binding
  (binding [get-system-architecture |(str "i386")]
    (is (= "Hello i386 user!" (greet-user-by-architecture))
        "i386 system user is greeted accordingly")))
# ✔ greeting-test-binding: i386 system user is greeted accordingly

# Example 4: Multiple bindings at once
(def *db-host* "production-db")
(def *db-port* 5432)

(defn connect []
  (str "Connecting to " *db-host* ":" *db-port*))

(binding [*db-host* "test-db"
          *db-port* 3306]
  (connect))  # => "Connecting to test-db:3306"

(connect)  # => "Connecting to production-db:5432"
```

{% php_note() %}
`binding` is useful for dependency injection and testing, similar to mocking frameworks in PHP:

```php
// PHP - using dependency injection
class UserService {
    public function __construct(private DbConnection $db) {}
}

// In tests:
$mockDb = $this->createMock(DbConnection::class);
$service = new UserService($mockDb);

// Phel - using binding (simpler for testing)
(defn get-user [id]
  (query-db (str "SELECT * FROM users WHERE id=" id)))

(deftest test-get-user
  (binding [query-db (fn [q] {:id 1 :name "Alice"})]
    (is (= "Alice" (:name (get-user 1))))))
```

Binding is particularly useful for testing code that depends on global state or external systems.
{% end %}

{% clojure_note() %}
`binding` works exactly like Clojure's `binding`—it creates dynamic scope bindings that affect all code executed within the binding form.
{% end %}

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

{% php_note() %}
Variables provide mutable state similar to PHP variables, but are explicit and contained:

```php
// PHP - everything is mutable by default
$count = 0;
$count++;

// Phel - explicit mutability with variables
(def count (var 0))
(swap! count inc)
```

Use Phel's immutable data structures when possible. Variables are mainly useful for interop with PHP code or managing application state.
{% end %}

{% clojure_note() %}
Phel variables work like Clojure atoms—they're thread-safe containers for mutable state. Use `deref` or `@` to read, `swap!` to update.
{% end %}
