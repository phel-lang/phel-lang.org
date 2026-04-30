+++
title = "Global and local bindings"
weight = 5
aliases = ["/documentation/global-and-local-bindings"]
+++

## Definition (def)

```phel
(def name meta? value)
```

Binds a value to a global symbol. Cannot be redefined later.

```phel
(def my-name "phel")
(def sum-of-three (+ 1 2 3))
```

Attach metadata: a Keyword, String, or Map.

```phel
(def my-private-definition :private 12)
(def my-name "Stores the name of this language" "Phel")
(def my-other-name {:private true :doc "This is my doc"} "My value")
```

## Local bindings (let)

```phel
(let [bindings*] expr*)
```

Creates a lexical context with the bindings, then evaluates the expressions. Returns the last value (or `nil` if no expressions).

```phel
(let [x 1
      y 2]
  (+ x y)) ; Evaluates to 3

(let [x 1
      y (+ x 2)]) ; Evaluates to nil
```

All bindings are immutable.

{% php_note() %}
Block-scoped bindings, like PHP, but immutable:

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

## Binding

`binding` temporarily redefines existing globals while executing the body. Useful for tests, mocking, dependency injection.

**Difference:**
- `let`: new local variables (lexical scope)
- `binding`: temporarily overrides globals (dynamic scope)

```phel
;; Example 1: Simple binding demonstration
(def *config* "production")

(defn get-config []
  *config*)

(get-config)  ; => "production"

;; let doesn't affect the global definition
(let [*config* "test"]
  (get-config))  ; => "production" (still uses global!)

;; binding temporarily overrides the global definition
(binding [*config* "test"]
  (get-config))  ; => "test" (uses binding!)

(get-config)  ; => "production" (back to original)

;; Example 2: Mocking functions for testing
(defn get-system-architecture []
  (php/php_uname "m"))

(defn greet-user-by-architecture []
  (str "Hello " (get-system-architecture) " user!"))

;; Without binding - calls actual system function
(greet-user-by-architecture)  ; => "Hello x86_64 user!" (or your system arch)

;; With let - doesn't work! Function still calls original
(let [get-system-architecture #(str "i386")]
  (greet-user-by-architecture))  ; => "Hello x86_64 user!" (original still used!)

;; With binding - successfully mocks the function
(binding [get-system-architecture #(str "i386")]
  (greet-user-by-architecture))  ; => "Hello i386 user!" (mocked!)

;; Example 3: Testing with binding
(ns my-app\tests\demo
  (:require phel\test :refer [deftest is]))

(deftest greeting-test-binding
  (binding [get-system-architecture #(str "i386")]
    (is (= "Hello i386 user!" (greet-user-by-architecture))
        "i386 system user is greeted accordingly")))
;; ✔ greeting-test-binding: i386 system user is greeted accordingly

;; Example 4: Multiple bindings at once
(def *db-host* "production-db")
(def *db-port* 5432)

(defn connect []
  (str "Connecting to " *db-host* ":" *db-port*))

(binding [*db-host* "test-db"
          *db-port* 3306]
  (connect))  ; => "Connecting to test-db:3306"

(connect)  ; => "Connecting to production-db:5432"
```

{% php_note() %}
Useful for DI and testing, similar to PHP mocking frameworks:

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

Useful for testing code with global state or external systems.
{% end %}

## Atoms

```phel
(atom value)
```

Atoms manage mutable state. Each holds a single value. Create with `atom`:

```phel
(def foo (atom 10)) ; Define an atom with value 10
```

`deref` (or `@` shorthand) extracts the value. `reset!` replaces it. `swap!` applies a function:

```phel
(def foo (atom 10))

(deref foo)        ; Evaluates to 10
@foo               ; Same as (deref foo)
(reset! foo 20)    ; Set foo to 20
@foo               ; Evaluates to 20

(swap! foo + 2)    ; Evaluates to 22
@foo               ; Evaluates to 22
```

> **Note:** `var`, `set!`, `var?`, `function?` are deprecated aliases for `atom`, `reset!`, `atom?`, `fn?`. Use the Clojure-compatible names.

{% php_note() %}
Atoms are explicit, contained mutable state:

```php
// PHP - everything is mutable by default
$count = 0;
$count++;

// Phel - explicit mutability with atoms
(def count (atom 0))
(swap! count inc)
```

Prefer immutable data structures. Atoms mainly for PHP interop or app state.
{% end %}

