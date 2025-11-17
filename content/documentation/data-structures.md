+++
title = "Data structures"
weight = 9
+++

Phel has four main data structures: **Lists**, **Vectors**, **Maps**, and **Sets**.

All data structures are **persistent** (immutable). A persistent data structure preserves the previous version of itself when it is modified. Unlike naive immutable structures that copy everything, persistent data structures efficiently share unmodified values with their previous versions. When you "modify" a collection, you get a new version while the original remains unchanged.

{% php_note() %}
Think of this as "copy-on-write" for collections, similar to how PHP's copy-on-write works for variables. This prevents bugs from unexpected mutations—a common issue in PHP where passing arrays to functions can lead to surprising behavior.
{% end %}

{% clojure_note() %}
Phel's data structures work exactly like Clojure's—they're built on the same persistent data structure algorithms (Bagwell's Hash Array Mapped Tries and similar structures).
{% end %}

## Lists

A persistent list is simple a linked list. Access or modifications on the first element is efficient, random access is not. In Phel, a list has a special meaning. They are interpreted as function calls, macro calls or special forms by the compiler.

To create a list surround the white space separated values with parentheses or use the `list` function.

```phel
(do 1 2 3)   # list with 4 entries
(list 1 2 3) # use the list function to create a new list
'(1 2 3)     # use a quote to create a list
```

To access values in a list the functions `get`, `first`, `second`, `next`, `rest` and `peek` can be used.

```phel
(get (list 1 2 3) 0)  # Evaluates to 1
(first (list 1 2 3))  # Evaluates to 1
(second (list 1 2 3)) # Evaluates to 2
(peek (list 1 2 3))   # Evaluates to 3
(next (list 1 2 3))   # Evaluates to (2 3)
(next (list))         # Evaluates to nil
(rest (list 1 2 3))   # Evaluates to (2 3)
(rest (list))         # Evaluates to ()
```

New values can only be added to the front of the list with the `cons` function.

```phel
(cons 1 (list))     # Evaluates to (1)
(cons 3 (list 1 2)) # Evaluates to (3 1 2)
```

To get the length of the list the `count` function can be used

```phel
(count (list))       # Evaluates to 0
(count (list 1 2 3)) # Evaluates to 3
```

## Vectors

Vectors are an indexed, sequential data structure. They offer efficient random access (by index) and are very efficient in appending values at the end.

To create a vector, wrap the white space separated values with brackets or use the `vector` function.

```phel
[1 2 3]      # Creates a new vector with three values
(vector 1 2) # Creates a new vector with two values
```

To get a value by its index use the `get` function. Similar to list you can use the `first`, `second` and `peek` function to access the first, second and last values of the vector.

```phel
(get [1 2 3] 0)  # Evaluates to 1
(first [1 2 3])  # Evaluates to 1
(second [1 2 3]) # Evaluates to 2
(peek [1 2 3])   # Evaluates to 3
```

New values can be appended by using the `conj` function.

```phel
(conj [1 2 3] 4) # Evaluates to [1 2 3 4]
```

To change an existing value use the `assoc` function

```phel
(assoc [1 2 3] 0 4) # Evaluates to [4 2 3]
(assoc [1 2 3] 3 4) # Evaluates to [1 2 3 4]
```

A vector can be counted using the `count` function.

```phel
(count [])      # Evaluates to 0
(count [1 2 3]) # Evaluates to 3
```

{% php_note() %}
Vectors are like PHP's indexed arrays (`[0 => 'a', 1 => 'b']`), but immutable. Use vectors when you need indexed access.
{% end %}

{% clojure_note() %}
Vectors work exactly like Clojure vectors—use them for indexed collections and when you need to append efficiently to the end.
{% end %}

## Maps

A Map contains key-value-pairs in random order. Each possible key appears at most once in the collection. Any type that implements the `HashableInterface` and `EqualsInterface` can be used as a key—including vectors, lists, or even other maps.

To create a map, wrap the key and values in curly brackets or use the `hash-map` function.

```phel
{:key1 value1 :key2 value2}          # A new hash-map using shortcut syntax
(hash-map :key1 value1 :key2 value2) # A new hash-map using the function

# Any type can be a key
{[1 2] "vector-key" :keyword "keyword-key" "string" "string-key"}
```

Use the `get` function to access a value by its key

```phel
(get {:a 1 :b 2} :a) # Evaluates to 1
(get {:a 1 :b 2} :b) # Evaluates to 2
(get {:a 1 :b 2} :c) # Evaluates to nil
```

To add or update a key-value pair in the map use the `assoc` function

```phel
(assoc {} :a "hello")       # Evaluates to {:a "hello"}
(assoc {:a "foo"} :a "bar") # Evaluates to {:a "bar"}
```

A value in a map can be removed with the `dissoc` function

```phel
(dissoc {:a "foo"} :a) # Evaluates to {}
```

As in the other data structures, the `count` function can be used to count the key-value-pairs.

```phel
(count {})         # Evaluates to 0
(count {:a "foo"}) # Evaluates to 1
```

{% php_note() %}
Maps are like PHP's associative arrays, but with two key differences:

1. **Any type can be a key** (not just strings/integers): vectors, lists, or even other maps
2. **Immutable**: "updating" a map returns a new map; the original is unchanged

```phel
# PHP: $arr = ['name' => 'Alice', 'age' => 30];
# Phel:
{:name "Alice" :age 30}

# PHP: $arr['age'] = 31;
# Phel:
(assoc {:name "Alice" :age 30} :age 31)
# => {:name "Alice" :age 31}
# Original map is unchanged!
```
{% end %}

{% clojure_note() %}
Maps work exactly like Clojure maps—use keywords for keys in most cases.
{% end %}

## Working with Collections

Phel provides several core functions for manipulating collections. These functions work across different data structure types.

### Adding Elements with `conj`

The `conj` function adds elements to collections. The behavior depends on the collection type to maintain efficiency:

```phel
# Vectors - appends to end
(conj [1 2 3] 4)         # Evaluates to [1 2 3 4]
(conj [] 1 2 3)          # Evaluates to [1 2 3]

# Sets - adds element
(conj #{1 2 3} 4)        # Evaluates to #{1 2 3 4}
(conj #{1 2 3} 2)        # Evaluates to #{1 2 3} (already present)

# Lists - prepends to front (for efficiency)
(conj (list 1 2 3) 0)    # Evaluates to (0 1 2 3)

# Maps - adds key-value pair
(conj {:a 1} [:b 2])     # Evaluates to {:a 1 :b 2}
(conj {} [:a 1] [:b 2])  # Evaluates to {:a 1 :b 2}
```

### Associating Values with `assoc`

The `assoc` function associates a value with a key in associative data structures (maps, vectors by index, structs).

```phel
# Maps - set or update key-value pairs
(assoc {} :a "hello")           # Evaluates to {:a "hello"}
(assoc {:a "foo"} :a "bar")     # Evaluates to {:a "bar"}
(assoc {:a 1} :b 2 :c 3)        # Evaluates to {:a 1 :b 2 :c 3}

# Vectors - set value at index (can extend by one position)
(assoc [1 2 3] 0 4)             # Evaluates to [4 2 3]
(assoc [1 2 3] 3 4)             # Evaluates to [1 2 3 4]
(assoc [] 0 "first")            # Evaluates to ["first"]
```

### Removing Values with `dissoc`

The `dissoc` function removes a key from a data structure, returning the structure without that key.

```phel
# Maps - remove key-value pair
(dissoc {:a 1 :b 2} :a)         # Evaluates to {:b 2}
(dissoc {:a 1 :b 2 :c 3} :a :c) # Evaluates to {:b 2}

# Sets - remove element
(dissoc #{1 2 3} 2)             # Evaluates to #{1 3}
(dissoc #{1 2 3} 2 3)           # Evaluates to #{1}
```

### Nested Operations

For working with nested data structures, Phel provides `-in` variants:

```phel
# get-in - Access nested values
(get-in {:a {:b {:c 1}}} [:a :b :c])     # Evaluates to 1
(get-in {:users [{:name "Alice"}]} [:users 0 :name]) # Evaluates to "Alice"

# assoc-in - Set nested values
(assoc-in {} [:a :b :c] 1)               # Evaluates to {:a {:b {:c 1}}}
(assoc-in {:a {:b 1}} [:a :c] 2)         # Evaluates to {:a {:b 1 :c 2}}

# update - Update a value by applying a function
(update {:a 1} :a inc)                   # Evaluates to {:a 2}
(update [1 2 3] 0 + 10)                  # Evaluates to [11 2 3]

# update-in - Update nested values
(update-in {:a {:b 1}} [:a :b] inc)      # Evaluates to {:a {:b 2}}
```

{% php_note() %}

### Understanding Immutability vs PHP's Mutability

```phel
# PHP: Mutable operations
$users = ['Alice', 'Bob'];
$users[] = 'Charlie';  # $users is now ['Alice', 'Bob', 'Charlie']
echo $users[0];        # Still 'Alice'

# Phel: Immutable operations
(def users ["Alice" "Bob"])
(def updated-users (conj users "Charlie"))  # New collection
# users is still ["Alice" "Bob"]
# updated-users is ["Alice" "Bob" "Charlie"]

# PHP: Mutating a map
$config = ['theme' => 'dark', 'lang' => 'en'];
$config['theme'] = 'light';  # Overwrites in place

# Phel: Creating a new map
(def config {:theme "dark" :lang "en"})
(def new-config (assoc config :theme "light"))
# config is still {:theme "dark" :lang "en"}
# new-config is {:theme "light" :lang "en"}
```

**Why immutability matters:**
- **Thread-safe**: Multiple threads can safely read the same data
- **Predictable**: Functions can't unexpectedly modify your data
- **Time-travel**: Keep old versions for undo/history features
- **Easier debugging**: Data doesn't change "magically"

**When working with PHP code:** Use `php/aset` for PHP arrays that must be mutable:
```phel
(def php-arr (php/array))
(php/aset php-arr "key" "value")  # Mutates the PHP array
```

{% end %}

{% clojure_note() %}

### Clojure Compatibility

Phel follows Clojure's naming conventions exactly:

| Function | Behavior | Clojure Compatible? |
|----------|----------|---------------------|
| `conj` | Add element (type-specific) | ✓ Yes |
| `assoc` | Associate key with value | ✓ Yes |
| `dissoc` | Dissociate key | ✓ Yes |
| `get` | Get value by key | ✓ Yes |
| `get-in` | Get nested value | ✓ Yes |
| `assoc-in` | Set nested value | ✓ Yes |
| `update` | Update with function | ✓ Yes |
| `update-in` | Update nested with function | ✓ Yes |

**Migration note:** The older `push`, `put`, and `unset` functions are deprecated since v0.25.0. Use `conj`, `assoc`, and `dissoc` instead for Clojure compatibility.

{% end %}

## Structs

A Struct is a special kind of Map. It only supports a predefined number of keys and is associated with a global name. The Struct not only defines itself but also a predicate function.

```phel
(defstruct my-struct [a b c]) # Defines the struct
(let [x (my-struct 1 2 3)]    # Create a new struct
  (my-struct? x)              # Evaluates to true
  (get x :a)                  # Evaluates to 1
  (assoc x :a 12))            # Evaluates to (my-struct 12 2 3)
```

Internally, Phel Structs are PHP classes where each key correspondence to an object property. Therefore, Structs can be faster than Maps.

## Sets

A Set contains unique values in random order. All types of values are allowed that implement the `HashableInterface` and the `EqualsInterface`.

A new set can be created by using the `set` function or shortcut syntax `#{}`

```phel
#{1 2 3}    # A new set using shortcut syntax
(set 1 2 3) # A new set using the function
```

The `conj` function can be used to add a new value to the Set.

```phel
(conj #{1 2 3} 4) # Evaluates to #{1 2 3 4}
(conj #{1 2 3} 2) # Evaluates to #{1 2 3}
```

Similar to the Map the `dissoc` function can be used to remove a value from the list

```phel
(dissoc #{1 2 3} 2) # Evaluates to #{1 3}
```

Again the `count` function can be used to count the elements in the set

```phel
(count #{})  # Evaluates to 0
(count #{2}) # Evaluates to 1
```

Additionally, the union of a collection of sets is the set of all elements in the collection.

```phel
(union)               # Evaluates to #{}
(union #{1 2})        # Evaluates to #{1 2}
(union #{1 2} #{0 3}) # Evaluates to #{0 1 2 3}
```

The intersection of two sets or more is the set containing all elements shared between those sets.

```phel
(intersection #{1 2} #{0 3})     # Evaluates to #{}
(intersection #{1 2} #{0 1 2 3}) # Evaluates to #{1 2}
```

The difference of two sets or more is the set containing all elements in the first set that aren't in the other sets.

```phel
(difference #{1 2} #{0 3})     # Evaluates to #{1 2}
(difference #{1 2} #{0 1 2 3}) # Evaluates to #{}
(difference #{0 1 2 3} #{1 2}) # Evaluates to #{0 3}
```

The symmetric difference of two sets or more is the set of elements which are in either of the sets and not in their intersection.

```phel
(symmetric-difference #{1 2} #{0 3})     # Evaluates to #{0 1 2 3}
(symmetric-difference #{1 2} #{0 1 2 3}) # Evaluates to #{0 3}
```

## Transients

Nearly all persistent data structures have a transient version (except for Persistent List). The transient version of each persistent data structure is a mutable version of them. It stores the value in the same way as the persistent version, but instead of returning a new persistent version with every modification, it modifies the current version. 

Transient versions are faster and can be used as builders for new persistent collections. Since transients use the same underlying storage, it is rapid to convert a persistent data structure to a transient and back.

For example, if we want to convert a PHP Array to a persistent map. This function can be used:

```phel
(defn php-array-to-map
  "Converts a PHP Array to a map."
  [arr]
  (let [res (transient {})] # Convert a persistent data to a transient
    (foreach [k v arr]
      (assoc res k v)) # Fill the transient map (mutable)
    (persistent res))) # Convert the transient map to a persistent map.
```

## Data structures are functions

In Phel all data structures can also be used as functions. This enables concise, elegant code:

```phel
((list 1 2 3) 0) # Same as (get (list 1 2 3) 0)
([1 2 3] 0)      # Same as (get [1 2 3] 0)
({:a 1 :b 2} :a) # Same as (get {:a 1 :b 2} :a)
(#{1 2 3} 1)     # Same as (get #{1 2 3} 1)

# Practical use with map
(def users [{:name "Alice" :age 30}
            {:name "Bob" :age 25}])
(map :name users)  # Evaluates to ["Alice" "Bob"]
```

## Practical Example: Working with User Data

Here's a real-world example combining multiple concepts:

```phel
# Start with user data
(def user {:id 1
           :name "Alice"
           :email "alice@example.com"
           :settings {:theme "dark" :notifications true}})

# Access nested data
(get-in user [:settings :theme])  # => "dark"

# Update nested settings immutably
(def updated-user
  (assoc-in user [:settings :theme] "light"))
# user still has "dark", updated-user has "light"

# Add a new field
(def user-with-role
  (assoc updated-user :role "admin"))

# Update using a function
(def user-with-incremented-id
  (update user-with-role :id inc))

# Working with collections of users
(def users
  [{:name "Alice" :active true}
   {:name "Bob" :active false}
   {:name "Charlie" :active true}])

# Filter active users and get their names
(->> users
     (filter :active)          # Keep only active users
     (map :name)              # Extract names
     (into #{}))              # Convert to a set
# => #{"Alice" "Charlie"}

# Build a map from a PHP array (common when interoping with PHP)
(defn php-response-to-map
  "Convert a PHP API response to Phel data structures"
  [php-arr]
  (let [data (transient {})]
    (foreach [k v php-arr]
      (assoc data (keyword k) v))
    (persistent data)))

# Use with nested structures
(def api-response
  (php/array "user_id" 123
             "user_name" "Alice"
             "is_active" true))

(php-response-to-map api-response)
# => {:user_id 123 :user_name "Alice" :is_active true}
```

### Common Patterns

**Building data incrementally:**
```phel
# PHP way (mutable)
# $result = [];
# $result['id'] = 1;
# $result['name'] = 'Alice';
# return $result;

# Phel way (immutable)
(-> {}
    (assoc :id 1)
    (assoc :name "Alice"))
# Or all at once:
{:id 1 :name "Alice"}
```

**Updating deeply nested data:**
```phel
(def app-state
  {:ui {:sidebar {:width 200 :visible true}}
   :user {:name "Alice"}})

# Change sidebar visibility
(assoc-in app-state [:ui :sidebar :visible] false)

# Increment sidebar width
(update-in app-state [:ui :sidebar :width] + 50)
```

**Merging data:**
```phel
(def defaults {:theme "light" :lang "en" :debug false})
(def user-prefs {:theme "dark"})

(merge defaults user-prefs)
# => {:theme "dark" :lang "en" :debug false}
```
