+++
title = "Data Structures"
weight = 2
description = "Phel's persistent collections: vectors, maps, sets, lists, queues, and structs, plus conj, assoc, get-in, update, and into"
aliases = ["/documentation/data-structures"]

[extra]
difficulty = "beginner"
+++

After this page you can create, read, and update Phel's collections: vectors, maps, sets, lists, queues, and structs.

All Phel collections are **persistent**: they never change. An update returns a new collection that shares most of its structure with the old one, so updates stay cheap and the original stays valid.

```phel
(def config {:theme "dark" :lang "en"})
(def new-config (assoc config :theme "light"))

config     ; => {:theme "dark" :lang "en"}
new-config ; => {:theme "light" :lang "en"}
```

{% <php_note> %}
PHP arrays copy on write, and code that holds a reference can change them in place. A Phel function can never change the data you pass to it. When you need a mutable PHP array for interop, create one with `(php/array)` and change it with `php/aset`.
{% </php_note> %}

## Choosing a collection

| Collection | Literal | Use it for |
|------------|---------|------------|
| Vector | `[1 2 3]` | ordered values, access by index, append at the end |
| Map | `{:a 1 :b 2}` | values looked up by key |
| Set | `#{1 2 3}` | unique values, fast membership tests |
| List | `'(1 2 3)` | code as data, adding at the front |
| Queue | `(queue 1 2 3)` | first in, first out |

## Vectors

A vector is an indexed sequence. Reading by index and appending at the end are fast.

```phel
[1 2 3]          ; literal
(vector 1 2 3)   ; => [1 2 3]
(vec '(1 2 3))   ; => [1 2 3], from any collection
```

Read by index with `get` (or `nth`). `get` returns `nil`, or a default, when the index is missing:

```phel
(get [10 20 30] 0)       ; => 10
(get [10 20 30] 5)       ; => nil
(get [10 20 30] 5 :none) ; => :none
(first [10 20 30])       ; => 10
(peek [10 20 30])        ; => 30 (the last element)
```

Add at the end with `conj`. Replace by index with `assoc`. Drop the last element with `pop`:

```phel
(conj [1 2 3] 4)     ; => [1 2 3 4]
(assoc [1 2 3] 0 9)  ; => [9 2 3]
(assoc [1 2 3] 3 4)  ; => [1 2 3 4] (one past the end appends)
(pop [1 2 3])        ; => [1 2]
(count [1 2 3])      ; => 3
```

## Maps

A map stores key-value pairs, each key once. Keys are usually keywords, but any value can be a key.

```phel
{:name "Alice" :age 30}         ; literal
(hash-map :name "Alice" :age 30) ; same map
```

Read with `get`, or call the keyword as a function. Both accept a default:

```phel
(get {:a 1 :b 2} :a)      ; => 1
(get {:a 1 :b 2} :c 0)    ; => 0
(:a {:a 1 :b 2})          ; => 1
(contains? {:a 1} :a)     ; => true
```

Add or replace keys with `assoc`. Remove them with `dissoc`. Combine maps with `merge`, where later maps win:

```phel
(assoc {:a 1} :b 2 :c 3)          ; => {:a 1 :b 2 :c 3}
(dissoc {:a 1 :b 2 :c 3} :a :c)   ; => {:b 2}
(merge {:theme "light" :lang "en"} {:theme "dark"})
; => {:theme "dark" :lang "en"}
```

`keys`, `vals`, and `select-keys` read parts of a map: `(select-keys {:a 1 :b 2 :c 3} [:a :c])` returns `{:a 1 :c 3}`.

{% <php_note> %}
A map is like a PHP associative array with two differences: keys can be any type, and `assoc` returns a new map instead of changing the old one.
{% </php_note> %}

### Map entries

Calling `seq` or `first` on a map gives map entries. An entry is equal to a `[key value]` vector:

```phel
(first {:a 1 :b 2})       ; => [:a 1]
(key (map-entry :a 1))    ; => :a
(val (map-entry :a 1))    ; => 1
(= (map-entry :a 1) [:a 1]) ; => true
```

## Sets

A set holds unique values. Testing membership is fast.

```phel
#{1 2 3}          ; literal
(hash-set 1 2 3)  ; => #{1 2 3}, from arguments
(set [1 1 2 3])   ; => #{1 2 3}, from a collection

(conj #{1 2} 3)           ; => #{1 2 3}
(conj #{1 2} 2)           ; => #{1 2} (already present)
(disj #{1 2 3} 2)         ; => #{1 3}
(contains? #{1 2 3} 2)    ; => true
```

Set algebra functions are in core:

```phel
(union #{1 2} #{2 3})        ; => #{1 2 3}
(intersection #{1 2} #{2 3}) ; => #{2}
(difference #{1 2} #{2 3})   ; => #{1}
(subset? #{1} #{1 2})        ; => true
```

`symmetric-difference` and `superset?` are also available. See the [core API](/documentation/reference/api/core/).

## Lists

A list is a linked list. Adding and reading at the front is fast. Reading by index is slow. Phel code is itself written as lists, so quote a list to keep it as data:

```phel
'(1 2 3)          ; => (1 2 3)
(list 1 2 3)      ; => (1 2 3)
(first '(1 2 3))  ; => 1
(rest '(1 2 3))   ; => (2 3)
(conj '(1 2 3) 0) ; => (0 1 2 3), adds at the front
```

`rest` returns an empty collection when nothing is left. `next` returns `nil`, which is handy in a loop test.

## Queues

A queue is first in, first out. `conj` adds at the back. `peek` reads the front and `pop` removes it. There is no literal; build one with `queue`:

```phel
(def q (queue 1 2 3))
(peek q)   ; => 1
(conj q 4) ; => <-(1 2 3 4)-<
(pop q)    ; => <-(2 3)-<
```

A queue prints as `<-(...)-<`: items enter on the right and leave on the left.

## Working with collections

### Adding with `conj` { #adding-elements-with-conj }

`conj` adds where it is cheapest for the type:

| Type | `conj` adds | Example |
|------|-------------|---------|
| Vector | at the end | `(conj [1 2] 3)` => `[1 2 3]` |
| List | at the front | `(conj '(1 2) 0)` => `(0 1 2)` |
| Set | if not present | `(conj #{1} 2)` => `#{1 2}` |
| Map | a `[key value]` pair | `(conj {:a 1} [:b 2])` => `{:a 1 :b 2}` |

### Nested data

The `-in` functions take a path of keys and indexes:

```phel
(def user {:name "Alice"
           :settings {:theme "dark" :font-size 14}})

(get-in user [:settings :theme])               ; => "dark"
(get-in user [:settings :missing] "default")   ; => "default"
(assoc-in user [:settings :theme] "light")
; => {:name "Alice" :settings {:theme "light" :font-size 14}}
(update-in user [:settings :font-size] + 2)
; => {:name "Alice" :settings {:theme "dark" :font-size 16}}
```

`update` applies a function to one value. Extra arguments go after the old value:

```phel
(update {:count 1} :count inc) ; => {:count 2}
(update [1 2 3] 0 + 10)        ; => [11 2 3]
```

`update-vals` applies a function to every value of a map, and `update-keys` to every key: `(update-vals {:a 1 :b 2} inc)` returns `{:a 2 :b 3}`.

### Building with `into`

`into` adds every element of one collection to another. Use it to convert between types:

```phel
(into [] '(1 2 3))         ; => [1 2 3]
(into #{} [1 2 2 3])       ; => #{1 2 3}
(into {} [[:a 1] [:b 2]])  ; => {:a 1 :b 2}
```

### Transducers

`map`, `filter`, `take`, and similar functions return a **transducer** when you call them without a collection. A transducer is a reusable transformation. Pass it as the middle argument of `into`, or compose several with `comp`:

```phel
(into [] (map inc) [1 2 3]) ; => [2 3 4]

(def xf (comp (filter odd?) (map #(* % 10))))
(into [] xf [1 2 3 4 5])       ; => [10 30 50]
(transduce xf + 0 [1 2 3 4 5]) ; => 90
```

Composition order, early termination, and custom transducers: [Transducers](/documentation/language/transducers/).

## Data structures as functions

Lists, vectors, maps, and sets are functions of their keys. Keywords are functions of maps. This keeps lookups short, especially with `map`:

```phel
([10 20 30] 1)    ; => 20
({:a 1 :b 2} :a)  ; => 1
(#{1 2 3} 2)      ; => 2
(#{1 2 3} 4)      ; => nil

(map :name [{:name "Alice"} {:name "Bob"}]) ; => ("Alice" "Bob")
```

## Structs

A struct is a map with a fixed set of keys and a name. `defstruct` defines a constructor and a predicate:

```phel
(defstruct point [x y])

(def p (point 1 2))
(point? p)       ; => true
(get p :x)       ; => 1
(:y p)           ; => 2
(assoc p :x 10)  ; => (point 10 2)
```

A struct compiles to a PHP class with one property per key, so it is faster than a map. PHP code can call `count($p)` and read `$p['x']`: every struct implements `\Countable`, `\ArrayAccess`, and `\IteratorAggregate`.

A `:php` block adds PHP magic methods such as `__invoke` and `__toString`. The first argument is the struct itself:

```phel
(defstruct multiplier [factor]
  :php
  (__invoke   [this x] (* x (get this :factor)))
  (__toString [this]   (str "x" (get this :factor))))

((multiplier 3) 14) ; => 42
```

A custom `__invoke` must take exactly one argument or be variadic, because a struct is already callable as a key lookup. Implementing interfaces on structs: [Interfaces](/documentation/language/interfaces/).

## Transients

A transient is a mutable version of a vector, map, or set (lists have none). Use one as a fast builder inside a function, then turn it back into a persistent collection. Both conversions are cheap:

```phel
(defn php-array-to-map [arr]
  (let [res (transient {})]
    (foreach [k v arr]
      (assoc res k v))  ; changes res in place
    (persistent res)))

(php-array-to-map (php-associative-array "a" 1 "b" 2)) ; => {"a" 1 "b" 2}
```

Keep a transient local to one function. Never share it.

## Walking data structures

`phel.walk` transforms every level of nested data. `postwalk` visits children before their parent. `prewalk` visits the parent first:

```phel
(ns my-app
  (:require phel.walk :refer [postwalk]))

(postwalk #(if (number? %) (* % 2) %)
          {:a 1 :b [2 3] :c {:d 4}})
; => {:a 2 :b [4 6] :c {:d 8}}
```

`keywordize-keys` and `stringify-keys` convert map keys at every level, which helps with decoded JSON. `walk`, `prewalk-replace`, and `postwalk-replace` are in the [walk API](/documentation/reference/api/walk/).

{% <clojure_note> %}
`conj`, `assoc`, `dissoc`, `disj`, `get`, `get-in`, `assoc-in`, `update`, and `update-in` behave as in Clojure. The old Phel names `push`, `put`, and `unset` were removed.
{% </clojure_note> %}

Next: [Global and local bindings](/documentation/language/global-and-local-bindings/) shows how to name these values with `def` and `let`.
