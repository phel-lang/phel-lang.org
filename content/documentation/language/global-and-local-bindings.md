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
  // x = 10  <- This would be a compile error!
  (+ x y))
```
{% end %}

## Binding

`binding` temporarily rebinds dynamic vars while executing the body. Useful for tests, mocking, dependency injection.

**Difference:**
- `let`: new local variables (lexical scope)
- `binding`: temporarily rebinds dynamic vars (dynamic scope, fiber-local)

Vars must be tagged `^:dynamic` at their `def`, otherwise `binding` throws. To swap a non-dynamic var for the duration of an expression (e.g. mocking), use `with-redefs`.

```phel
;; Example 1: Simple binding demonstration
(def ^:dynamic *config* "production")

(defn get-config []
  *config*)

(get-config)  ; => "production"

;; let doesn't affect the global definition
(let [*config* "test"]
  (get-config))  ; => "production" (still uses global!)

;; binding temporarily rebinds the dynamic var
(binding [*config* "test"]
  (get-config))  ; => "test" (uses binding!)

(get-config)  ; => "production" (back to original)

;; Example 2: Mocking functions for testing with with-redefs
(defn get-system-architecture []
  (php/php_uname "m"))

(defn greet-user-by-architecture []
  (str "Hello " (get-system-architecture) " user!"))

;; Without redef - calls actual system function
(greet-user-by-architecture)  ; => "Hello x86_64 user!" (or your system arch)

;; with-redefs swaps any var, restores on exit (works for non-dynamic too)
(with-redefs [get-system-architecture (fn [] "i386")]
  (greet-user-by-architecture))  ; => "Hello i386 user!" (mocked!)

;; Example 3: Testing with with-redefs
(ns my-app.tests.demo
  (:require phel.test :refer [deftest is]))

(deftest greeting-test
  (with-redefs [get-system-architecture (fn [] "i386")]
    (is (= "Hello i386 user!" (greet-user-by-architecture))
        "i386 system user is greeted accordingly")))

;; Example 4: Multiple dynamic bindings at once
(def ^:dynamic *db-host* "production-db")
(def ^:dynamic *db-port* 5432)

(defn connect []
  (str "Connecting to " *db-host* ":" *db-port*))

(binding [*db-host* "test-db"
          *db-port* 3306]
  (connect))  ; => "Connecting to test-db:3306"

(connect)  ; => "Connecting to production-db:5432"
```

`with-bindings` rebinds dynamic vars from a `Var -> value` map:

```phel
(with-bindings {#'*db-host* "test-db"
                #'*db-port* 3306}
  (connect))
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

// Phel - using with-redefs (simpler for testing)
(defn get-user [id]
  (query-db (str "SELECT * FROM users WHERE id=" id)))

(deftest test-get-user
  (with-redefs [query-db (fn [q] {:id 1 :name "Alice"})]
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

> **Note:** `function?` is a deprecated alias for `fn?`. Use the Clojure-compatible name. The atom-related aliases `var`, `var?`, `set!` are gone: use `atom`, `atom?`, `reset!`. `var` / `var?` / `#'sym` now refer to first-class `Var` handles for global definitions, not atoms.

## Vars

`def` creates a global binding backed by a `Var`. Get a first-class handle with `(var sym)` or the `#'sym` reader macro:

```phel
(def my-name "phel")

(var my-name)        ; => #'user/my-name
#'my-name            ; same as (var my-name)
(var? #'my-name)     ; => true
(deref #'my-name)    ; => "phel"
(var-get #'my-name)  ; => "phel"
(find-var 'user/my-name)  ; lookup by qualified symbol
```

Modify a var's root binding with `alter-var-root`:

```phel
(def counter 0)
(alter-var-root #'counter inc)
counter  ; => 1
```

Watch a var's value with `add-watch` / `remove-watch`. Adjust metadata with `alter-meta!` / `reset-meta!`. See the [API reference](/documentation/reference/api/core/) for the full surface.

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

