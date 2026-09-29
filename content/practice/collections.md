+++
title = "Collections"
weight = 2
description = "Vectors, maps, sets, and lists: build them, read from them, change them without mutation, and reach into nested data."
aliases = ["/practice/data-structures/"]

[extra]
stage = "Foundations"
goals = [
  "Build vectors, maps, sets, and lists with literal syntax",
  "Read values with `get`, `first`, `rest`, and keywords as functions",
  "Add and remove with `conj`, `assoc`, and `dissoc`, knowing the old value stays the same",
  "Read and change nested data with `get-in`, `assoc-in`, `update`, and `update-in`",
]
read_first = [
  ["Data Structures", "/documentation/language/data-structures/"],
]
recap = [
  "You can pick the right collection: vector for order, map for named fields, set for unique values",
  "You can read any value from a collection, even a missing one, without an error",
  "You know that `conj`, `assoc`, and `dissoc` return a new collection and never change the old one",
  "You can read and change nested data with a path of keys",
]
+++

Real programs are mostly data: a user, a cart, a list of orders. In Phel you hold that data in four collections: vectors, maps, sets, and lists. They are **immutable**: every "change" gives you a new collection, and the old one stays as it was. That one rule removes a whole family of bugs, and this module shows you how to work with it.

## Vectors

A vector is an ordered list of values in square brackets. It is the Phel version of a PHP list like `[1, 2, 3]`, without the commas. Indexes start at `0`.

```phel
[10 20 30]           ; a vector of three numbers
(get [10 20 30] 0)   ; => 10
(conj [10 20 30] 40) ; => [10 20 30 40]
```

{% question(difficulty="easy", kind="predict") %}
Predict each result.
```phel
(count [10 20 30])
(first [10 20 30])
(rest [10 20 30])
(get [10 20 30] 1)
```
{% end %}
{% solution() %}
```phel
(count [10 20 30]) ; => 3
(first [10 20 30]) ; => 10
(rest [10 20 30])  ; => [20 30]
(get [10 20 30] 1) ; => 20
```
`first` gives the first item, `rest` gives everything after it. `get` with index `1` gives the second item, because indexes start at `0`.
{% end %}

{% question(difficulty="easy", kind="fill") %}
Replace `___` so each form returns the value in its comment.
<!-- phel-test: skip -->
```phel
(conj [:a :b] ___)      ; => [:a :b :c]
(get [:a :b :c] ___)    ; => :c
(get [:a :b :c] 10 ___) ; => :none
```
{% end %}
{% hint() %}
`get` takes an optional third argument: the value to return when nothing is found.
{% end %}
{% solution() %}
```phel
(conj [:a :b] :c)         ; => [:a :b :c]
(get [:a :b :c] 2)        ; => :c
(get [:a :b :c] 10 :none) ; => :none
```
A missing index is not an error: `get` returns `nil`, or your default if you give one.
{% end %}

## Maps

A map holds key-value pairs in curly braces. Keys are usually keywords. In PHP this is an associative array like `['name' => 'Ada']`.

```phel
{:name "Ada" :age 36}
(get {:name "Ada" :age 36} :name) ; => "Ada"
```

{% question(difficulty="easy", kind="predict") %}
Predict each result.
```phel
(get {:name "Ada" :age 36} :age)
(get {:name "Ada" :age 36} :email)
(get {:name "Ada" :age 36} :email "unknown")
(count {:name "Ada" :age 36})
```
{% end %}
{% solution() %}
```phel
(get {:name "Ada" :age 36} :age)             ; => 36
(get {:name "Ada" :age 36} :email)           ; => nil
(get {:name "Ada" :age 36} :email "unknown") ; => "unknown"
(count {:name "Ada" :age 36})                ; => 2
```
A missing key gives `nil`, not an error. PHP would warn about an undefined array key. `count` counts key-value pairs.
{% end %}

{% question(difficulty="easy", kind="fill") %}
`assoc` adds or replaces a key. `dissoc` removes one. Replace `___` so each form returns the value in its comment.
<!-- phel-test: skip -->
```phel
(assoc {:name "Ada"} ___ ___)       ; => {:name "Ada", :age 36}
(assoc {:name "Ada"} :name ___)     ; => {:name "Grace"}
(dissoc {:name "Ada" :age 36} ___)  ; => {:name "Ada"}
```
{% end %}
{% solution() %}
```phel
(assoc {:name "Ada"} :age 36)       ; => {:name "Ada", :age 36}
(assoc {:name "Ada"} :name "Grace") ; => {:name "Grace"}
(dissoc {:name "Ada" :age 36} :age) ; => {:name "Ada"}
```
When the key already exists, `assoc` replaces its value. The REPL prints commas between pairs to help you read; in your code they are optional.
{% end %}

{% question(difficulty="easy", kind="predict") %}
Predict each result.
```phel
(keys {:name "Ada" :age 36})
(vals {:name "Ada" :age 36})
(contains? {:name "Ada" :age 36} :age)
(contains? {:name "Ada" :age 36} :email)
```
{% end %}
{% solution() %}
```phel
(keys {:name "Ada" :age 36})             ; => [:name :age]
(vals {:name "Ada" :age 36})             ; => ["Ada" 36]
(contains? {:name "Ada" :age 36} :age)   ; => true
(contains? {:name "Ada" :age 36} :email) ; => false
```
`keys` and `vals` split a map into its two sides. `contains?` asks about the key, not the value. It is PHP's `array_key_exists`.
{% end %}

## Sets and lists

A set holds unique values, written `#{...}`. Adding a value that is already there changes nothing. A list is written `'(1 2 3)`. The quote `'` tells Phel "this is data, do not call `1`". You will see lists more in code than in data.

```phel
#{:red :green}      ; a set
'(1 2 3)            ; a list
(contains? #{:red :green} :red) ; => true
```

{% question(difficulty="medium", kind="predict") %}
`conj` means "add in the natural way for this collection". Predict each result.
```phel
(conj [1 2] 3)
(conj '(1 2) 3)
(conj #{1 2} 2)
(conj #{1 2} 3)
```
{% end %}
{% hint() %}
A vector grows at the end. A list grows at the front. A set never holds the same value twice.
{% end %}
{% solution() %}
```phel
(conj [1 2] 3)   ; => [1 2 3]
(conj '(1 2) 3)  ; => (3 1 2)
(conj #{1 2} 2)  ; => #{1 2}
(conj #{1 2} 3)  ; => #{1 2 3}
```
Each collection adds where it is fastest. If you need order and want to add at the end, use a vector.

Learn more: [Adding with conj](/documentation/language/data-structures/#adding-elements-with-conj)
{% end %}

## Nothing changes in place

In PHP, `$cart[] = 'pear'` changes `$cart`. In Phel, `(conj cart "pear")` returns a **new** vector, and `cart` stays the same. Nothing can change a value behind your back. So when you want several changes, you pass the result of one change into the next, from the inside out.

```phel
(assoc (assoc {} :a 1) :b 2) ; => {:a 1, :b 2}
```

{% question(difficulty="medium", kind="write") %}
Start from this map:
```phel
{:name "Ada" :role :guest}
```
Write one form that sets `:role` to `:admin` and then removes `:name`. It should return:
```phel
{:role :admin}
```
{% end %}
{% hint() %}
The first change goes on the inside. Wrap the second change around it.
{% end %}
{% solution() %}
```phel
(dissoc (assoc {:name "Ada" :role :guest} :role :admin) :name)
; => {:role :admin}
```
`assoc` builds a new map, then `dissoc` builds another one from it. The first map is never changed. In the Sequences module you will learn `->`, which lets you write this top to bottom.
{% end %}

## Keywords as functions

A keyword can look itself up in a map: `(:name user)` is the same as `(get user :name)`. This is the most common way to read a field in Phel. Maps and sets can be called too.

```phel
(:name {:name "Ada"}) ; => "Ada"
```

{% question(difficulty="medium", kind="predict") %}
Predict each result.
```phel
(:age {:name "Ada" :age 36})
(:email {:name "Ada"})
(:role {:name "Ada"} :guest)
({:name "Ada"} :name)
(#{:red :green} :blue)
```
{% end %}
{% hint() %}
Every one of these works like `get`, including the default value.
{% end %}
{% solution() %}
```phel
(:age {:name "Ada" :age 36}) ; => 36
(:email {:name "Ada"})       ; => nil
(:role {:name "Ada"} :guest) ; => :guest
({:name "Ada"} :name)        ; => "Ada"
(#{:red :green} :blue)       ; => nil
```
Calling a set asks "is this value in you?": you get the value back, or `nil`.

Learn more: [Data structures as functions](/documentation/language/data-structures/#data-structures-as-functions)
{% end %}

## Nested data

Real data nests: a map holds a vector, which holds maps. The `-in` functions take a **path**, a vector of keys and indexes, and walk it for you. `update` changes one value with a function, like `inc` (add one).

```phel
(get-in {:user {:city "Berlin"}} [:user :city])          ; => "Berlin"
(assoc-in {:user {:city "Berlin"}} [:user :city] "Rome") ; => {:user {:city "Rome"}}
(update {:visits 1} :visits inc)                         ; => {:visits 2}
```

{% question(difficulty="medium", kind="write") %}
Write a form that finds `:treasure` in this dungeon.
```phel
{:description "dark cave"
 :rooms [{:contents :monster}
         nil
         {:contents [:trinket :treasure]}]}
```
{% end %}
{% hint() %}
Write the path one step at a time: which key, then which room index, then which key, then which index.
{% end %}
{% solution() %}
```phel
(get-in {:description "dark cave"
         :rooms [{:contents :monster}
                 nil
                 {:contents [:trinket :treasure]}]}
        [:rooms 2 :contents 1])
; => :treasure
```
Keys and indexes mix freely in one path. If any step is missing, `get-in` returns `nil` instead of failing.
{% end %}

{% question(difficulty="medium", kind="predict") %}
Predict each result.
```phel
(update {:visits 1} :visits inc)
(update {:visits 1} :visits dec)
(update [1 2 3] 0 inc)
(get-in {:user {:city "Berlin"}} [:user :phone :number])
```
{% end %}
{% hint() %}
For a vector, the "key" is the index.
{% end %}
{% solution() %}
```phel
(update {:visits 1} :visits inc)                         ; => {:visits 2}
(update {:visits 1} :visits dec)                         ; => {:visits 0}
(update [1 2 3] 0 inc)                                   ; => [2 2 3]
(get-in {:user {:city "Berlin"}} [:user :phone :number]) ; => nil
```
`update` reads the old value, calls the function on it, and puts the result back. You write "what to do", not "read, change, write".
{% end %}

{% question(difficulty="hard", kind="write") %}
Add one pear to this cart. Write one form that returns the cart with the pear's `:qty` raised from `1` to `2`.
```phel
{:items [{:name "apple" :qty 2}
         {:name "pear" :qty 1}]}
```
Expected result:
```phel
{:items [{:name "apple" :qty 2}
         {:name "pear" :qty 2}]}
```
{% end %}
{% hint() %}
`update-in` is `update` with a path. What is the path to the pear's quantity?
{% end %}
{% solution() %}
```phel
(update-in {:items [{:name "apple" :qty 2}
                    {:name "pear" :qty 1}]}
           [:items 1 :qty]
           inc)
; => {:items [{:name "apple", :qty 2} {:name "pear", :qty 2}]}
```
The path goes into `:items`, then index `1`, then `:qty`, and `inc` does the change. A common mistake is `(assoc-in ... [:items 1 :qty] 2)`: it works here, but it hardcodes the new number instead of adding one.
{% end %}

{% question(difficulty="hard", kind="fix") %}
This form should switch the theme to `"light"`. Run it. It does not fail, but the result is wrong. Fix it.
```phel
(assoc {:user {:name "Ada" :settings {:theme "dark"}}} :theme "light")
```
{% end %}
{% hint() %}
Compare the result with the input. Where did the new `:theme` go?
{% end %}
{% solution() %}
```phel
(assoc-in {:user {:name "Ada" :settings {:theme "dark"}}}
          [:user :settings :theme]
          "light")
; => {:user {:name "Ada", :settings {:theme "light"}}}
```
`assoc` only works on the top level, so the broken form added a new `:theme` key next to `:user` and left the real one unchanged. For nested data, use `assoc-in` with the full path.
{% end %}
