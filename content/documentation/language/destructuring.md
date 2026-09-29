+++
title = "Destructuring"
weight = 6
description = "Bind names to values inside vectors, lists, and maps by shape in let, function params, and loop"
aliases = ["/documentation/destructuring"]

[extra]
difficulty = "intermediate"
+++

After this page you can pull values out of vectors and maps by describing their shape, instead of calling `get` and `first` by hand.

Destructuring works everywhere Phel binds names: `let`, `defn` and `fn` parameters, `loop`, and `for`.

## Sequential

A vector pattern binds elements by position. It works on vectors, lists, and other sequences:

```phel
(let [[a b] [1 2]]
  (+ a b)) ; => 3
```

`_` skips a position. `&` binds the rest:

```phel
(let [[first-item _ third] [1 2 3]]
  [first-item third]) ; => [1 3]

(let [[head & tail] [1 2 3 4]]
  tail) ; => [2 3 4]
```

Patterns nest:

```phel
(let [[a [b c]] [1 [2 3]]]
  (+ a b c)) ; => 6
```

Missing elements bind to `nil`.

## Associative

A map pattern binds values by key. `:keys` is the common short form: it binds each keyword key to a local with the same name:

```phel
(let [{:keys [name age]} {:name "Alice" :age 30}]
  (str name " is " age)) ; => "Alice is 30"
```

The long form pairs a name with a key, name first. Use it to rename, or when keys are not keywords:

```phel
(let [{n :name a :age} {:name "Alice" :age 30}]
  [n a]) ; => ["Alice" 30]
```

For string keys, like decoded JSON, use `:strs`:

```phel
(let [{:strs [name]} {"name" "Alice"}]
  name) ; => "Alice"
```

### Defaults with `:or`

Missing keys bind to `nil`. `:or` gives defaults:

```phel
(let [{:keys [name role] :or {role "guest"}}
      {:name "Bob"}]
  (str name " (" role ")")) ; => "Bob (guest)"
```

### Whole map with `:as`

`:as` binds the whole map next to the pieces:

```phel
(let [{:keys [name] :as user} {:name "Alice" :id 7}]
  [name (:id user)]) ; => ["Alice" 7]
```

### Nested

Map and vector patterns mix freely:

```phel
(let [{:keys [name] {:keys [city]} :address}
      {:name "Alice" :address {:city "Berlin"}}]
  (str name " lives in " city)) ; => "Alice lives in Berlin"

(let [{:point [x y]} {:point [3 4]}]
  (+ x y)) ; => 7
```

### By index

A map pattern with integer keys reads a vector by index. It helps when you need one position from a long vector:

```phel
(let [{2 third} [10 20 30 40]]
  third) ; => 30
```

{% clojure_note() %}
`:keys`, `:strs`, `:or`, and `:as` work in map patterns as in Clojure. Vector patterns do not support `:as`: `(let [[a :as all] [1 2]] all)` fails with `PHEL008 Cannot destructure Keyword`. Bind the vector to a name first, then destructure it.
{% end %}

## In function parameters

Any parameter can be a pattern:

```phel
(defn greet [{:keys [name role] :or {role "member"}}]
  (str "Hello " name " (" role ")"))

(greet {:name "Alice" :role "admin"}) ; => "Hello Alice (admin)"
(greet {:name "Bob"})                 ; => "Hello Bob (member)"

(defn distance [[x1 y1] [x2 y2]]
  (php/sqrt (+ (* (- x2 x1) (- x2 x1))
               (* (- y2 y1) (- y2 y1)))))

(distance [0 0] [3 4]) ; => 5.0
```

## In `loop` and `for`

```phel
(loop [[head & tail] [1 2 3 4 5]
       acc 0]
  (if (nil? head)
    acc
    (recur tail (+ acc head)))) ; => 15

(for [[k v] :pairs {:a 1 :b 2}]
  [v k]) ; => [[1 :a] [2 :b]]
```

{% php_note() %}
PHP has `[$a, $b] = $arr;` and `['a' => $x] = $arr;`. Phel patterns also nest, take the rest with `&`, give defaults with `:or`, and work directly in function parameters. `{:keys [role] :or {role "guest"}}` replaces `$role = $data['role'] ?? 'guest';`.
{% end %}
