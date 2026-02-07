+++
title = "Data Structures"
weight = 2
+++

Phel has powerful built-in data structures: vectors, maps, sets, and keywords. Let's explore how to create them, access their contents, and discover that they're all **immutable**.

{% question() %}
Define a vector with the elements `2`, `"nice"`, and `true`.
{% end %}
{% solution() %}
```phel
[2 "nice" true]
# or
(vector 2 "nice" true)
```
Vectors are ordered collections that can hold any mix of types. Square brackets `[]` are the most common way to create them.

Learn more: [Data Structures](/documentation/data-structures)
{% end %}

{% question() %}
Define a vector that contains the keywords `:hello` and `:world`.
{% end %}
{% solution() %}
```phel
[:hello :world]
```
Keywords are lightweight identifiers that start with `:`. They're often used as map keys or as enum-like values.

Learn more: [Basic Types](/documentation/basic-types)
{% end %}

{% question() %}
Create a map with keys `:name` and `:age`, with values `"Ada"` and `36`.
{% end %}
{% solution() %}
```phel
{:name "Ada" :age 36}
# or
(hash-map :name "Ada" :age 36)
```
Maps are key-value collections. Keyword keys are idiomatic in Phel.

Learn more: [Data Structures](/documentation/data-structures)
{% end %}

{% question() %}
Create a set containing the numbers `1`, `2`, `3`, and `2`. How many elements does it have?
{% end %}
{% solution() %}
```phel
(set 1 2 3 2)
# => (set 1 2 3) — duplicates are removed!

(count (set 1 2 3 2))
# => 3
```
Sets are unordered collections of unique values. Adding a duplicate has no effect.

Learn more: [Data Structures](/documentation/data-structures)
{% end %}

{% question() %}
Use the `get` function to retrieve the second element from the vector `[10 20 30]`.
{% end %}
{% solution() %}
```phel
(get [10 20 30] 1)
# => 20
```
Vector indices are zero-based, so index `1` is the second element.

Learn more: [Data Structures](/documentation/data-structures)
{% end %}

{% question() %}
Given this map, retrieve the value for `:name` in three different ways:
```phel
(def person {:name "Ada" :age 36})
```
{% end %}
{% solution() %}
```phel
(def person {:name "Ada" :age 36})

# Way 1: using get
(get person :name) # => "Ada"

# Way 2: using the map as a function
(person :name)     # => "Ada"

# Way 3: using the keyword as a function
(:name person)     # => "Ada"
```
All three are equivalent. Using keywords as functions (`:name person`) is the most idiomatic style.

Learn more: [Data Structures](/documentation/data-structures)
{% end %}

{% question() %}
Use `get-in` to retrieve the value `:treasure` from this nested structure:
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
# => :treasure
```
`get-in` navigates nested structures using a vector of keys/indices. It works through any combination of maps and vectors.

Learn more: [Data Structures](/documentation/data-structures)
{% end %}

{% question() %}
Use `put` to add `:email "ada@example.com"` to the `person` map. What does `put` return? What is `person` after the call?
```phel
(def person {:name "Ada" :age 36})
```
{% end %}
{% solution() %}
```phel
(def person {:name "Ada" :age 36})

(put person :email "ada@example.com")
# => {:name "Ada" :age 36 :email "ada@example.com"}

person
# => {:name "Ada" :age 36}  — unchanged!
```
Phel data structures are **immutable**. `put` returns a *new* map — it never modifies the original. This is a core principle that makes your code predictable and safe.

Learn more: [Data Structures](/documentation/data-structures)
{% end %}

{% question() %}
Use `push` to add the number `4` to the vector `[1 2 3]`. Then use `count` to verify the length of both the original and the new vector.
{% end %}
{% solution() %}
```phel
(def nums [1 2 3])
(def more-nums (push nums 4))

(count nums)      # => 3 (original unchanged)
(count more-nums) # => 4
more-nums         # => [1 2 3 4]
```
Again, `push` returns a new vector. The original is untouched.

Learn more: [Data Structures](/documentation/data-structures)
{% end %}

{% question() %}
Use `contains?` to check if the set `#{:apple :banana :cherry}` contains `:banana`. Then check for `:grape`.
{% end %}
{% solution() %}
```phel
(def fruits (set :apple :banana :cherry))

(contains? fruits :banana) # => true
(contains? fruits :grape)  # => false
```
`contains?` is the natural way to check set membership.

Learn more: [Data Structures](/documentation/data-structures)
{% end %}

{% question() %}
Use `keys` and `values` on the map `{:a 1 :b 2 :c 3}`. What do you get?
{% end %}
{% solution() %}
```phel
(keys {:a 1 :b 2 :c 3})   # => (:a :b :c)
(values {:a 1 :b 2 :c 3}) # => (1 2 3)
```
These are handy when you need to work with just the keys or just the values of a map.

Learn more: [Data Structures](/documentation/data-structures)
{% end %}
