+++
title = "Data structures"
weight = 2
description = "Phel's persistent collections: lists, vectors, maps, sets, structs, plus core functions like conj, assoc, get-in, and into"
aliases = ["/documentation/data-structures"]

[extra]
difficulty = "beginner"
+++

Phel's four core collections are lists, vectors, maps, and sets. All are **persistent** (immutable): an operation returns a new version that shares structure with the old one, and the original never changes.

{% php_note() %}
"Copy-on-write" for collections. Prevents bugs from unexpected mutations.
{% end %}

## Lists

Linked list. Fast first-element access, slow random access. Lists are function/macro/special-form calls.

Create with `list` or by quoting a parenthesized form:

```phel
(list 1 2 3) ; use the list function to create a new list
'(1 2 3)     ; use a quote to create a list
```

Access values with `get`, `first`, `second`, `next`, `rest`, `peek`:

```phel
(get (list 1 2 3) 0)  ; Evaluates to 1
(first (list 1 2 3))  ; Evaluates to 1
(second (list 1 2 3)) ; Evaluates to 2
(peek (list 1 2 3))   ; Evaluates to 3
(next (list 1 2 3))   ; Evaluates to (2 3)
(next (list))         ; Evaluates to nil
(rest (list 1 2 3))   ; Evaluates to (2 3)
(rest (list))         ; Evaluates to ()
```

Add to the front with `cons`:

```phel
(cons 1 (list))     ; Evaluates to (1)
(cons 3 (list 1 2)) ; Evaluates to (3 1 2)
```

`count` for length:

```phel
(count (list))       ; Evaluates to 0
(count (list 1 2 3)) ; Evaluates to 3
```

## Vectors

Indexed, sequential. Fast random access by index, fast append at end.

Create with brackets, `vector`, or coerce with `vec`:

```phel
[1 2 3]       ; Creates a new vector with three values
(vector 1 2)  ; Creates a new vector with two values
(vec '(1 2 3)) ; Coerce a list to a vector: [1 2 3]
(vec #{1 2 3}) ; Coerce a set to a vector
```

`get` by index. `first`, `second`, `peek` for first/second/last:

```phel
(get [1 2 3] 0)  ; Evaluates to 1
(first [1 2 3])  ; Evaluates to 1
(second [1 2 3]) ; Evaluates to 2
(peek [1 2 3])   ; Evaluates to 3
```

Append with `conj`:

```phel
(conj [1 2 3] 4) ; Evaluates to [1 2 3 4]
```

Change a value with `assoc`:

```phel
(assoc [1 2 3] 0 4) ; Evaluates to [4 2 3]
(assoc [1 2 3] 3 4) ; Evaluates to [1 2 3 4]
```

Length with `count`:

```phel
(count [])      ; Evaluates to 0
(count [1 2 3]) ; Evaluates to 3
```

{% php_note() %}
Like PHP indexed arrays (`[0 => 'a', 1 => 'b']`), but immutable.
{% end %}

## Maps

Key-value pairs in any order. Each key once. Any value implementing `HashableInterface` and `EqualsInterface` can be a key (vectors, lists, maps).

Create with braces or `hash-map`:

```phel
{:key1 "value1" :key2 "value2"}          ; A new hash-map using shortcut syntax
(hash-map :key1 "value1" :key2 "value2") ; A new hash-map using the function

;; Any type can be a key
{[1 2] "vector-key" :keyword "keyword-key" "string" "string-key"}
```

Access with `get`:

```phel
(get {:a 1 :b 2} :a) ; Evaluates to 1
(get {:a 1 :b 2} :b) ; Evaluates to 2
(get {:a 1 :b 2} :c) ; Evaluates to nil
```

Add or update with `assoc`. Multiple pairs at once:

```phel
(assoc {} :a "hello")           ; Evaluates to {:a "hello"}
(assoc {:a "foo"} :a "bar")     ; Evaluates to {:a "bar"}
(assoc {} :a 1 :b 2 :c 3)      ; Evaluates to {:a 1 :b 2 :c 3}
```

Remove with `dissoc`:

```phel
(dissoc {:a "foo"} :a) ; Evaluates to {}
```

`count` for size:

```phel
(count {})         ; Evaluates to 0
(count {:a "foo"}) ; Evaluates to 1
```

{% php_note() %}
Like PHP associative arrays, but with two differences: keys can be **any type** (vectors, lists, other maps), and maps are **immutable**: "updating" with `assoc` returns a new map and leaves the original untouched. Worked comparison in [Immutability vs PHP mutability](#immutability-vs-php-mutability) below.
{% end %}

## Working with collections

Core functions span data structures.

### Adding with `conj`

`conj` adds elements. Behavior depends on type for efficiency:

```phel
;; Vectors - appends to end
(conj [1 2 3] 4)         ; Evaluates to [1 2 3 4]
(conj [] 1 2 3)          ; Evaluates to [1 2 3]

;; Sets - adds element
(conj #{1 2 3} 4)        ; Evaluates to #{1 2 3 4}
(conj #{1 2 3} 2)        ; Evaluates to #{1 2 3} (already present)

;; Lists - prepends to front (for efficiency)
(conj (list 1 2 3) 0)    ; Evaluates to (0 1 2 3)

;; Maps - adds key-value pair
(conj {:a 1} [:b 2])     ; Evaluates to {:a 1 :b 2}
(conj {} [:a 1] [:b 2])  ; Evaluates to {:a 1 :b 2}
```

### Associating with `assoc`

`assoc` sets a key in maps, vectors (by index), structs:

```phel
;; Maps - set or update key-value pairs
(assoc {} :a "hello")           ; Evaluates to {:a "hello"}
(assoc {:a "foo"} :a "bar")     ; Evaluates to {:a "bar"}
(assoc {:a 1} :b 2 :c 3)        ; Evaluates to {:a 1 :b 2 :c 3}

;; Vectors - set value at index (can extend by one position)
(assoc [1 2 3] 0 4)             ; Evaluates to [4 2 3]
(assoc [1 2 3] 3 4)             ; Evaluates to [1 2 3 4]
(assoc [] 0 "first")            ; Evaluates to ["first"]
```

### Removing with `dissoc`

`dissoc` removes a key:

```phel
;; Maps - remove key-value pair
(dissoc {:a 1 :b 2} :a)         ; Evaluates to {:b 2}
(dissoc {:a 1 :b 2 :c 3} :a :c) ; Evaluates to {:b 2}

;; Sets - remove element
(dissoc #{1 2 3} 2)             ; Evaluates to #{1 3}
(dissoc #{1 2 3} 2 3)           ; Evaluates to #{1}
```

### Nested operations

`-in` variants for nested structures:

```phel
;; get-in - Access nested values
(get-in {:a {:b {:c 1}}} [:a :b :c])     ; Evaluates to 1
(get-in {:users [{:name "Alice"}]} [:users 0 :name]) ; Evaluates to "Alice"

;; assoc-in - Set nested values
(assoc-in {} [:a :b :c] 1)               ; Evaluates to {:a {:b {:c 1}}}
(assoc-in {:a {:b 1}} [:a :c] 2)         ; Evaluates to {:a {:b 1 :c 2}}

;; update - Update a value by applying a function
(update {:a 1} :a inc)                   ; Evaluates to {:a 2}
(update [1 2 3] 0 + 10)                  ; Evaluates to [11 2 3]

;; update-in - Update nested values
(update-in {:a {:b 1}} [:a :b] inc)      ; Evaluates to {:a {:b 2}}
```

{% php_note() %}

### Immutability vs PHP mutability

```php
// PHP: Mutable operations
$users = ['Alice', 'Bob'];
$users[] = 'Charlie';  // $users is now ['Alice', 'Bob', 'Charlie']
echo $users[0];        // Still 'Alice'

// PHP: Mutating a map
$config = ['theme' => 'dark', 'lang' => 'en'];
$config['theme'] = 'light';  // Overwrites in place
```

```phel
;; Phel: Immutable operations
(def users ["Alice" "Bob"])
(def updated-users (conj users "Charlie"))  ; New collection
;; users is still ["Alice" "Bob"]
;; updated-users is ["Alice" "Bob" "Charlie"]

;; Phel: Creating a new map
(def config {:theme "dark" :lang "en"})
(def new-config (assoc config :theme "light"))
;; config is still {:theme "dark" :lang "en"}
;; new-config is {:theme "light" :lang "en"}
```

**Why immutability matters:**
- **Thread-safe** reads
- **Predictable**: functions can't mutate your data
- **Time-travel**: keep old versions for undo/history
- **Easier debugging**: no surprise changes

**With PHP code:** use `php/aset` for mutable PHP arrays:
```phel
(def php-arr (php/array))
(php/aset php-arr "key" "value")  ; Mutates the PHP array
```

{% end %}

{% clojure_note() %}

### Clojure compatibility

Phel matches Clojure's names:

| Function    | Behavior                    | Clojure Compatible?  |
|-------------|-----------------------------|----------------------|
| `conj`      | Add element (type-specific) | ✓ Yes                |
| `assoc`     | Associate key with value    | ✓ Yes                |
| `dissoc`    | Dissociate key              | ✓ Yes                |
| `get`       | Get value by key            | ✓ Yes                |
| `get-in`    | Get nested value            | ✓ Yes                |
| `assoc-in`  | Set nested value            | ✓ Yes                |
| `update`    | Update with function        | ✓ Yes                |
| `update-in` | Update nested with function | ✓ Yes                |

**Migration:** `push`, `put`, `unset` deprecated. Use `conj`, `assoc`, `dissoc`.

{% end %}

## Structs

A struct is a Map with a fixed set of keys and a global name. `defstruct` also defines a predicate function.

```phel
(defstruct my-struct [a b c]) ; Defines the struct
(let [x (my-struct 1 2 3)]    ; Create a new struct
  (my-struct? x)              ; Evaluates to true
  (get x :a)                  ; Evaluates to 1
  (assoc x :a 12))            ; Evaluates to (my-struct 12 2 3)
```

Internally, Structs are PHP classes (one property per key). Faster than Maps. Every struct implements `\Countable`, `\ArrayAccess`, and `\IteratorAggregate`, so PHP code can `count($s)` and read fields by string offset (`$s['name']`) as well as by keyword.

Expose PHP magic methods (`__invoke`, `__toString`, `__get`, ...) through a `:php` block. The first arg binds to `$this`; read fields with `(get this :field)`.

```phel
(defstruct multiplier [factor]
  :php
  (__invoke   [this x] (* x (get this :factor)))
  (__toString [this]   (str "x" (get this :factor))))

(let [m (multiplier 3)]
  (m 14)) ; => 42  (PHP calls __invoke)
```

A `:php` block coexists with regular interface implementations. A custom `__invoke` must take exactly one call argument or be variadic (a struct is already callable as a key lookup), else the compiler rejects it.

## Sets

Unique values in any order. Values must implement `HashableInterface` and `EqualsInterface`.

Create with `#{}`, `hash-set`, or coerce with `set`:

```phel
#{1 2 3}         ; A new set using shortcut syntax
(hash-set 1 2 3) ; A new set from individual arguments
(set [1 2 3])    ; Coerce a collection to a set
(set '(1 2 3))   ; Works with any collection type
```

> **Note:** `set` coerces a collection (Clojure alignment). `hash-set` builds from individual args.

Add with `conj`:

```phel
(conj #{1 2 3} 4) ; Evaluates to #{1 2 3 4}
(conj #{1 2 3} 2) ; Evaluates to #{1 2 3}
```

Remove with `dissoc`:

```phel
(dissoc #{1 2 3} 2) ; Evaluates to #{1 3}
```

Size with `count`:

```phel
(count #{})  ; Evaluates to 0
(count #{2}) ; Evaluates to 1
```

`union`: all elements of multiple sets.

```phel
(union)               ; Evaluates to #{}
(union #{1 2})        ; Evaluates to #{1 2}
(union #{1 2} #{0 3}) ; Evaluates to #{0 1 2 3}
```

`intersection`: elements shared by all sets.

```phel
(intersection #{1 2} #{0 3})     ; Evaluates to #{}
(intersection #{1 2} #{0 1 2 3}) ; Evaluates to #{1 2}
```

`difference`: elements in first set not in the others.

```phel
(difference #{1 2} #{0 3})     ; Evaluates to #{1 2}
(difference #{1 2} #{0 1 2 3}) ; Evaluates to #{}
(difference #{0 1 2 3} #{1 2}) ; Evaluates to #{0 3}
```

`symmetric-difference`: elements in some sets but not in their intersection.

```phel
(symmetric-difference #{1 2} #{0 3})     ; Evaluates to #{0 1 2 3}
(symmetric-difference #{1 2} #{0 1 2 3}) ; Evaluates to #{0 3}
```

`subset?` and `superset?`:

```phel
(subset? (hash-set 1 2) (hash-set 1 2 3))   ; Evaluates to true
(subset? (hash-set 1 4) (hash-set 1 2 3))   ; Evaluates to false
(superset? (hash-set 1 2 3) (hash-set 1 2)) ; Evaluates to true
(superset? (hash-set 1 2 3) (hash-set 1 4)) ; Evaluates to false
```

## Transients

Most persistent structures have a transient (mutable) version (not lists). Same storage, but modifies in place.

Faster, used as builders. Conversion to/from persistent is cheap.

Convert a PHP array to a persistent map:

```phel
(defn php-array-to-map
  "Converts a PHP Array to a map."
  [arr]
  (let [res (transient {})] ; Convert a persistent data to a transient
    (foreach [k v arr]
      (assoc res k v)) ; Fill the transient map (mutable)
    (persistent res))) ; Convert the transient map to a persistent map.
```

## Data structures as functions

All data structures are callable:

```phel
((list 1 2 3) 0) ; Same as (get (list 1 2 3) 0)
([1 2 3] 0)      ; Same as (get [1 2 3] 0)
({:a 1 :b 2} :a) ; Same as (get {:a 1 :b 2} :a)
(#{1 2 3} 1)     ; Same as (get #{1 2 3} 1)

;; Practical use with map
(def users [{:name "Alice" :age 30}
            {:name "Bob" :age 25}])
(map :name users)  ; Evaluates to @["Alice" "Bob"]
```

## Example: working with user data

```phel
;; Start with user data
(def user {:id 1
           :name "Alice"
           :email "alice@example.com"
           :settings {:theme "dark" :notifications true}})

;; Access nested data
(get-in user [:settings :theme])  ; => "dark"

;; Update nested settings immutably
(def updated-user
  (assoc-in user [:settings :theme] "light"))
;; user still has "dark", updated-user has "light"

;; Add a new field
(def user-with-role
  (assoc updated-user :role "admin"))

;; Update using a function
(def user-with-incremented-id
  (update user-with-role :id inc))

;; Working with collections of users
(def users
  [{:name "Alice" :active true}
   {:name "Bob" :active false}
   {:name "Charlie" :active true}])

;; Filter active users and get their names
(->> users
     (filter :active)          ; Keep only active users
     (map :name)              ; Extract names
     (into #{}))              ; Convert to a set
;; => #{"Alice" "Charlie"}

;; Build a map from a PHP array (common when interoping with PHP)
(defn php-response-to-map
  "Convert a PHP API response to Phel data structures"
  [php-arr]
  (let [data (transient {})]
    (foreach [k v php-arr]
      (assoc data (keyword k) v))
    (persistent data)))

;; Use with nested structures
(def api-response
  (php/array "user_id" 123
             "user_name" "Alice"
             "is_active" true))

(php-response-to-map api-response)
;; => {:user_id 123 :user_name "Alice" :is_active true}
```

### Common patterns

**Building data incrementally:**
```phel
;; PHP way (mutable)
;; $result = [];
;; $result['id'] = 1;
;; $result['name'] = 'Alice';
;; return $result;

;; Phel way (immutable)
(-> {}
    (assoc :id 1)
    (assoc :name "Alice"))
;; Or all at once:
{:id 1 :name "Alice"}
```

**Updating deeply nested data:**
```phel
(def app-state
  {:ui {:sidebar {:width 200 :visible true}}
   :user {:name "Alice"}})

;; Change sidebar visibility
(assoc-in app-state [:ui :sidebar :visible] false)

;; Increment sidebar width
(update-in app-state [:ui :sidebar :width] + 50)
```

**Merging data:**
```phel
(def defaults {:theme "light" :lang "en" :debug false})
(def user-prefs {:theme "dark"})

(merge defaults user-prefs)
; => {:theme "dark" :lang "en" :debug false}
```

### Transforming map keys and values

`update-keys`, `update-vals` apply a function across keys/values:

```phel
; Transform all keys
(update-keys {:a 1 :b 2 :c 3} name)
; => {"a" 1 "b" 2 "c" 3}

(update-keys {"name" "Alice" "age" "30"} keyword)
; => {:name "Alice" :age "30"}

; Transform all values
(update-vals {:a 1 :b 2 :c 3} inc)
; => {:a 2 :b 3 :c 4}

(update-vals {:x "hello" :y "world"} phel.string/upper-case)
; => {:x "HELLO" :y "WORLD"}
```

### Building collections with `into`

`into` pours elements from one collection into another. Third arg applies a transducer:

```phel
; Two-argument form: pour elements into a collection
(into [] '(1 2 3))          ; => [1 2 3]
(into #{} [1 2 2 3 3])     ; => #{1 2 3}
(into {} [[:a 1] [:b 2]])  ; => {:a 1 :b 2}

; Three-argument form: apply a transducer during transfer
(into [] (map inc) [1 2 3])           ; => [2 3 4]
(into #{} (filter odd?) [1 2 3 4 5])  ; => #{1 3 5}
(into {} (map (fn [[k v]] [k (* v 2)])) (pairs {:a 1 :b 2}))
; => {:a 2 :b 4}
```

### Transducers

Composable transformations independent of context. `map`, `filter`, `remove`, `take`, `drop`, `take-while`, `drop-while`, `take-nth`, `keep`, `keep-indexed`, `distinct`, `dedupe`, `mapcat`, `interpose` return a transducer when called without a collection:

```phel
; Create a transducer by calling map/filter without a collection
(def xf (comp (filter odd?) (map #(* % 10))))

; Apply with transduce (reduces with a function)
(transduce xf + 0 [1 2 3 4 5])       ; => 90 (10 + 30 + 50)

; Apply with into (pours into a collection)
(into [] xf [1 2 3 4 5])             ; => [10 30 50]

; Apply with sequence (returns a lazy sequence)
(sequence xf [1 2 3 4 5])            ; => [10 30 50]
```

Common transducer producers:

```phel
(into [] (take 3) (range 10))                ; => [0 1 2]
(into [] (drop 7) (range 10))                ; => [7 8 9]
(into [] (take-while #(< % 5)) (range 10))   ; => [0 1 2 3 4]
(into [] (drop-while #(< % 5)) (range 10))   ; => [5 6 7 8 9]
(into [] (take-nth 3) (range 10))             ; => [0 3 6 9]
(into [] (distinct) [1 2 1 3 2 4])            ; => [1 2 3 4]
(into [] (dedupe) [1 1 2 2 3 1 1])            ; => [1 2 3 1]
(into [] (interpose :sep) [1 2 3])            ; => [1 :sep 2 :sep 3]
```

`completing` adapts a plain 2-arity reducing function into a full reducing function with 0-arity init and 1-arity completion (defaults to `identity`):

```phel
(def my-rf (completing conj))
(transduce (map inc) my-rf [1 2 3])  ; => [2 3 4]
```

`cat` concatenates inner collections:

```phel
(into [] cat [[1 2] [3 4] [5 6]])  ; => [1 2 3 4 5 6]
```

## Walking data structures

`phel.walk` recursively transforms nested data.

### walk

`walk` traverses a structure, applying `inner` to each element, then `outer` to the result:

```phel
(ns my-app
  (:require phel.walk :refer [walk postwalk prewalk
                               postwalk-replace prewalk-replace
                               keywordize-keys stringify-keys]))

(walk inc identity [1 2 3])  ; => [2 3 4]
```

### postwalk and prewalk

`postwalk` applies bottom-up (children first). `prewalk` applies top-down:

```phel
(ns example
  (:require phel.walk :refer [postwalk prewalk]))

;; Double every number in a nested structure
(postwalk #(if (number? %) (* % 2) %)
          {:a 1 :b [2 3] :c {:d 4}})
;; => {:a 2 :b [4 6] :c {:d 8}}

;; prewalk visits parent before children
(prewalk #(if (number? %) (* % 2) %)
         [1 [2 [3]]])
;; => [2 [4 [6]]]
```

### postwalk-replace and prewalk-replace

Replace values via map lookup:

```phel
(ns example
  (:require phel.walk :refer [postwalk-replace]))

(postwalk-replace {:a :alpha :b :beta}
                  [:a {:b :c}])
;; => [:alpha {:beta :c}]
```

### keywordize-keys and stringify-keys

Convert map keys between keywords and strings. Useful for PHP arrays or JSON:

```phel
(ns example
  (:require phel.walk :refer [keywordize-keys stringify-keys]))

(keywordize-keys {"name" "Alice" "age" 30})
;; => {:name "Alice" :age 30}

(stringify-keys {:name "Alice" :age 30})
;; => {"name" "Alice" "age" 30}
```

## Next steps

- [Destructuring](/documentation/language/destructuring/) - pull values out of collections by shape
- [Control flow](/documentation/language/control-flow/) - iterate and build collections with `for` and `loop`
- [Cheat sheet](/documentation/reference/cheat-sheet/) - keep it open while coding
