+++
title = "Data Structures"
weight = 2
+++

Phel ships with four go-to collections: vectors, maps, sets, and lists. They share two superpowers - **immutability** (every "change" returns a new value) and **structural sharing** (so it stays fast). Let's meet them.

{% question(difficulty="easy") %}
Build a vector with the elements `2`, `"nice"`, and `true`.
{% end %}
{% solution() %}
```phel
[2 "nice" true]
; or
(vector 2 "nice" true)
```
Vectors are ordered collections that hold any mix of types. Square brackets are the idiomatic literal.

Learn more: [Data Structures](/documentation/language/data-structures)
{% end %}

{% question(difficulty="easy") %}
Build a vector containing the keywords `:hello` and `:world`.
{% end %}
{% solution() %}
```phel
[:hello :world]
```
Keywords are lightweight identifiers prefixed with `:`. They're cheap, comparable by identity, and perfect for map keys or enum-like tags.

Learn more: [Basic Types](/documentation/language/basic-types)
{% end %}

{% question(difficulty="easy") %}
Build a map with keys `:name` and `:age`, values `"Ada"` and `36`.
{% end %}
{% solution() %}
```phel
{:name "Ada" :age 36}
; or
(hash-map :name "Ada" :age 36)
```
Maps are key-value collections. Keyword keys are the idiomatic default in Phel.

Learn more: [Data Structures](/documentation/language/data-structures)
{% end %}

{% question(difficulty="easy") %}
Build a set from `1`, `2`, `3`, `2`. How many elements does it hold?
{% end %}
{% solution() %}
```phel
(set 1 2 3 2)
; => (set 1 2 3) - duplicates dropped

(count (set 1 2 3 2))
; => 3
```
Sets store unique values. Adding a duplicate is a no-op.

Learn more: [Data Structures](/documentation/language/data-structures)
{% end %}

{% question(difficulty="easy") %}
Use `count` to find the size of `[10 20 30]` and `{:a 1 :b 2}`. Then check `(empty? [])` and `(empty? [1])`.
{% end %}
{% solution() %}
```phel
(count [10 20 30])    ; => 3
(count {:a 1 :b 2})   ; => 2
(empty? [])           ; => true
(empty? [1])          ; => false
```
`count` works on every Phel collection (and strings). `empty?` is the predicate version of "is the count zero?".

Learn more: [Data Structures](/documentation/language/data-structures)
{% end %}

{% question(difficulty="easy") %}
Use `get` to read the second element from `[10 20 30]`.
{% end %}
{% solution() %}
```phel
(get [10 20 30] 1)
; => 20
```
Vector indices are zero-based, so index `1` is the second element.

Learn more: [Data Structures](/documentation/language/data-structures)
{% end %}

{% question(difficulty="easy") %}
Given this map, retrieve `:name` three different ways:
```phel
(def person {:name "Ada" :age 36})
```
{% end %}
{% solution() %}
```phel
(def person {:name "Ada" :age 36})

(get person :name) ; => "Ada"  - explicit
(person :name)     ; => "Ada"  - map as function
(:name person)     ; => "Ada"  - keyword as function
```
All three are equivalent. The keyword-as-function form (`(:name person)`) is the most idiomatic style.

Learn more: [Data Structures](/documentation/language/data-structures)
{% end %}

{% question(difficulty="easy") %}
Use `get-in` to grab `:treasure` from this nested structure:
```phel
(def dungeon {:description "dark cave"
              :rooms [{:contents :monster}
                      nil
                      {:contents [:trinket :treasure]}]})
```
{% end %}
{% solution() %}
```phel
(get-in dungeon [:rooms 2 :contents 1])
; => :treasure
```
`get-in` walks nested maps and vectors via a path of keys/indices.

Learn more: [Data Structures](/documentation/language/data-structures)
{% end %}

{% question(difficulty="easy") %}
Use `put` to add `:email "ada@example.com"` to `person`. What does `put` return? What is `person` after?
```phel
(def person {:name "Ada" :age 36})
```
{% end %}
{% solution() %}
```phel
(def person {:name "Ada" :age 36})

(put person :email "ada@example.com")
; => {:name "Ada" :age 36 :email "ada@example.com"}

person
; => {:name "Ada" :age 36}  - unchanged!
```
Phel's data structures are **immutable**. `put` returns a fresh map; the original stays as it was. This is the foundation of safe, predictable code.

Learn more: [Data Structures](/documentation/language/data-structures)
{% end %}

{% question(difficulty="easy") %}
Use `push` to append `4` to `[1 2 3]`. Verify the original length didn't change.
{% end %}
{% solution() %}
```phel
(def nums [1 2 3])
(def more-nums (push nums 4))

(count nums)      ; => 3 (original untouched)
(count more-nums) ; => 4
more-nums         ; => [1 2 3 4]
```
Same immutability story: `push` returns a new vector, the original stays put.

Learn more: [Data Structures](/documentation/language/data-structures)
{% end %}

{% question(difficulty="easy") %}
Use `contains?` to check whether the set `(set :apple :banana :cherry)` has `:banana`. Then check `:grape`.
{% end %}
{% solution() %}
```phel
(def fruits (set :apple :banana :cherry))

(contains? fruits :banana) ; => true
(contains? fruits :grape)  ; => false
```
`contains?` is the natural way to ask "is this here?" - works on sets and on map keys.

Learn more: [Data Structures](/documentation/language/data-structures)
{% end %}

{% question(difficulty="easy") %}
Inspect `{:a 1 :b 2 :c 3}` with `keys` and `values`.
{% end %}
{% solution() %}
```phel
(keys {:a 1 :b 2 :c 3})   ; => (:a :b :c)
(values {:a 1 :b 2 :c 3}) ; => (1 2 3)
```
Useful when you only care about one side of a map.

Learn more: [Data Structures](/documentation/language/data-structures)
{% end %}

{% question(difficulty="easy") %}
Use `merge` to combine `{:name "Ada"}` and `{:age 36 :role :admin}` into one map.
{% end %}
{% solution() %}
```phel
(merge {:name "Ada"} {:age 36 :role :admin})
; => {:name "Ada" :age 36 :role :admin}
```
`merge` builds a new map containing all entries. Later values win on conflicts - great for layering defaults with overrides.

Learn more: [Data Structures](/documentation/language/data-structures)
{% end %}
