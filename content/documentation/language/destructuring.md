+++
title = "Destructuring"
weight = 8
aliases = ["/documentation/destructuring"]
+++

Destructuring binds names to values inside data structures. Describe the shape, Phel binds the pieces.

Works in `let`, function params (`defn`, `fn`), `loop`.

## Sequential

Extract from vectors/lists by position with vector syntax:

```phel
(let [[a b] [1 2]]
  (+ a b)) ; => 3
```

### Nested

Patterns nest arbitrarily deep:

```phel
(let [[a [b c]] [1 [2 3]]]
  (+ a b c)) ; => 6
```

### Skipping

`_` ignores a position:

```phel
(let [[a _ b] [1 2 3]]
  (+ a b)) ; => 4
```

### Rest args

`&` captures the remaining elements:

```phel
(let [[a b & rest] [1 2 3 4 5]]
  rest) ; => (3 4 5)
```

{% php_note() %}
More powerful than PHP `list()` or array unpacking:

```php
// PHP - limited destructuring
[$a, $b] = [1, 2];
['a' => $x, 'b' => $y] = ['a' => 1, 'b' => 2];

// Phel - full destructuring with nesting and rest
(let [[a [b c] & rest] [1 [2 3] 4 5 6]]
  ; a = 1, b = 2, c = 3, rest = (4 5 6)
  )
```

Works in more places (function params, let, loop) with more patterns.
{% end %}

## Associative

Extract from maps by key with map syntax:

```phel
(let [{:a a :b b} {:a 1 :b 2}]
  (+ a b)) ; => 3
```

### Nested associative

Mix map and vector patterns:

```phel
(let [{:a [a b] :c c} {:a [1 2] :c 3}]
  (+ a b c)) ; => 6
```

### Defaults with `:or`

Defaults for missing keys:

```phel
(let [{:name name :role role :or {role "guest"}}
      {:name "Alice"}]
  (str name " (" role ")")) ; => "Alice (guest)"
```

Without `:or`, missing keys bind to `nil`.

{% php_note() %}
Extract values by key:

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

## Index-based

Destructure vectors by index using map syntax:

```phel
(let [{0 a 1 b} [1 2]]
  (+ a b)) ; => 3

(let [{0 [a b] 1 c} [[1 2] 3]]
  (+ a b c)) ; => 6
```

Useful for specific positions in a large vector.

## In function parameters

Works directly in `defn` and `fn` params:

```phel
(defn greet [{:name name :role role :or {role "member"}}]
  (str "Hello " name " (" role ")"))

(greet {:name "Alice" :role "admin"})  ; => "Hello Alice (admin)"
(greet {:name "Bob"})                  ; => "Hello Bob (member)"
```

Sequential in params:

```phel
(defn distance [[x1 y1] [x2 y2]]
  (php/sqrt (+ (* (- x2 x1) (- x2 x1))
               (* (- y2 y1) (- y2 y1)))))

(distance [0 0] [3 4]) ; => 5.0
```

## In `loop`

Loop bindings:

```phel
(loop [[head & tail] [1 2 3 4 5]
       acc 0]
  (if (nil? head)
    acc
    (recur tail (+ acc head)))) ; => 15
```
