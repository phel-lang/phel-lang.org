+++
title = "Destructuring"
weight = 10
+++

Destructuring is a way to bind names to values inside a data structure. It provides a concise syntax for extracting values from collections.

Destructuring works in function parameters, `let` bindings, and `loop` bindings.

### Sequential data structures

Sequential data structures can be extracted using the vector syntax.

```phel
(let [[a b] [1 2]]
  (+ a b)) # Evaluates to 3

(let [[a [b c]] [1 [2 3]]]
  (+ a b c)) # Evaluates to 6

(let [[a _ b] [1 2 3]]
  (+ a b)) # Evaluates to 4

(let [[a b & rest] [1 2 3 4]]
  (apply + a b rest)) # Evaluates to 10
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

### Associative data structures

Associative data structures can be extracted using the map syntax.

```phel
(let [{:a a :b b} {:a 1 :b 2}]
  (+ a b)) # Evaluates to 3

(let [{:a [a b] :c c} {:a [1 2] :c 3}]
  (+ a b c)) # Evaluates to 6
```

{% php_note() %}
Associative destructuring lets you extract values by key:

```php
// PHP - manual extraction
$data = ['a' => 1, 'b' => 2];
$a = $data['a'];
$b = $data['b'];

// Phel - destructuring
(let [{:a a :b b} {:a 1 :b 2}]
  ; a = 1, b = 2
  )
```
{% end %}

### Indexed sequential

Indexed sequential can also be extracted by indices using the map syntax.

```phel
(let [{0 a 1 b} [1 2]]
  (+ a b)) # Evaluates to 3

(let [{0 [a b] 1 c} [[1 2] 3]]
  (+ a b c)) # Evaluates to 6
```
