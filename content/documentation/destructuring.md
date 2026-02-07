+++
title = "Destructuring"
weight = 10
+++

Destructuring binds names to values inside data structures. Instead of manually extracting each value, you describe the shape of the data and Phel binds the pieces for you.

Destructuring works in `let` bindings, function parameters (`defn`, `fn`), and `loop` bindings.

## Sequential destructuring

Extract values from vectors and lists by position using vector syntax:

```phel
(let [[a b] [1 2]]
  (+ a b)) # => 3
```

### Nested destructuring

Patterns can be nested arbitrarily deep:

```phel
(let [[a [b c]] [1 [2 3]]]
  (+ a b c)) # => 6
```

### Skipping values

Use `_` to ignore positions you don't care about:

```phel
(let [[a _ b] [1 2 3]]
  (+ a b)) # => 4
```

### Rest arguments

Use `&` to capture remaining elements as a sequence:

```phel
(let [[a b & rest] [1 2 3 4 5]]
  rest) # => (3 4 5)
```

{% php_note() %}
Destructuring is more powerful than PHP's list() or array unpacking:

```php
// PHP - limited destructuring
[$a, $b] = [1, 2];
['a' => $x, 'b' => $y] = ['a' => 1, 'b' => 2];

// Phel - full destructuring with nesting and rest
(let [[a [b c] & rest] [1 [2 3] 4 5 6]]
  ; a = 1, b = 2, c = 3, rest = (4 5 6)
  )
```

Phel's destructuring works in more places (function params, let, loop) and supports more patterns.
{% end %}

{% clojure_note() %}
Destructuring works exactly like Clojure's destructuring—same syntax and behavior.
{% end %}

## Associative destructuring

Extract values from maps by key using map syntax:

```phel
(let [{:a a :b b} {:a 1 :b 2}]
  (+ a b)) # => 3
```

### Nested associative destructuring

Combine map and vector patterns freely:

```phel
(let [{:a [a b] :c c} {:a [1 2] :c 3}]
  (+ a b c)) # => 6
```

### Default values with `:or`

Provide defaults for keys that might be missing:

```phel
(let [{:name name :role role :or {role "guest"}}
      {:name "Alice"}]
  (str name " (" role ")")) # => "Alice (guest)"
```

Without `:or`, missing keys bind to `nil`.

{% php_note() %}
Associative destructuring lets you extract values by key:

```php
// PHP - manual extraction with defaults
$data = ['name' => 'Alice'];
$name = $data['name'];
$role = $data['role'] ?? 'guest';

// Phel - destructuring with :or
(let [{:name name :role role :or {role "guest"}}
      {:name "Alice"}]
  ; name = "Alice", role = "guest"
  )
```
{% end %}

## Index-based destructuring

Vectors can also be destructured by index using map syntax:

```phel
(let [{0 a 1 b} [1 2]]
  (+ a b)) # => 3

(let [{0 [a b] 1 c} [[1 2] 3]]
  (+ a b c)) # => 6
```

This is useful when you only need specific positions from a large vector.

## Destructuring in function parameters

Destructuring works directly in `defn` and `fn` parameter lists:

```phel
(defn greet [{:name name :role role :or {role "member"}}]
  (str "Hello " name " (" role ")"))

(greet {:name "Alice" :role "admin"})  # => "Hello Alice (admin)"
(greet {:name "Bob"})                  # => "Hello Bob (member)"
```

Sequential destructuring in parameters:

```phel
(defn distance [[x1 y1] [x2 y2]]
  (php/sqrt (+ (* (- x2 x1) (- x2 x1))
               (* (- y2 y1) (- y2 y1)))))

(distance [0 0] [3 4]) # => 5.0
```

## Destructuring in `loop`

Use destructuring in loop bindings to work with structured data:

```phel
(loop [[head & tail] [1 2 3 4 5]
       acc 0]
  (if (nil? head)
    acc
    (recur tail (+ acc head)))) # => 15
```
