+++
title = "Cheat Sheet"
weight = 1
+++

A quick reference for Phel syntax and core functions. Press `/` to filter sections.

## Basic Syntax

```phel
# This is a comment
; This is also a comment
#| This is a
   multiline comment |#

nil                     # null value
true false              # booleans (only false and nil are falsy)
42 -3 1.5 3.14e2        # numbers
0xFF 0b1010 0o17        # hex, binary, octal
"hello" "line\nbreak"   # strings
:keyword :status        # keywords (interned constants)
my-var my-module/fn     # symbols
```

See [Basic Types](/documentation/basic-types), [Truth and Boolean Operations](/documentation/truth-and-boolean-operations).

## Data Structures

```phel
[1 2 3]                 # vector (indexed)
(vector 1 2 3)          # same thing
{:a 1 :b 2}             # map (key-value pairs)
(hash-map :a 1 :b 2)    # same thing
#{1 2 3}                # set (unique values)
(set 1 2 3)             # same thing
'(1 2 3)                # quoted list (data, not a call)
(list 1 2 3)            # same thing
```

See [Data Structures](/documentation/data-structures).

## Accessing Data

```phel
(get [1 2 3] 0)           # => 1
(get {:a 1} :a)            # => 1
(get {:a 1} :b "default")  # => "default"
(get-in {:a {:b 1}} [:a :b])  # => 1
(first [1 2 3])            # => 1
(second [1 2 3])           # => 2
(peek [1 2 3])             # => 3
(:name {:name "Alice"})    # => "Alice" (keyword as function)
({:a 1 :b 2} :a)           # => 1 (map as function)
([10 20 30] 1)             # => 20 (vector as function)
```

## Modifying Data

```phel
(conj [1 2] 3)                    # => [1 2 3]
(conj #{1 2} 3)                   # => #{1 2 3}
(conj {:a 1} [:b 2])              # => {:a 1 :b 2}
(assoc {:a 1} :b 2)               # => {:a 1 :b 2}
(assoc [1 2 3] 0 9)               # => [9 2 3]
(dissoc {:a 1 :b 2} :a)           # => {:b 2}
(update {:a 1} :a inc)            # => {:a 2}
(assoc-in {} [:a :b] 1)           # => {:a {:b 1}}
(update-in {:a {:b 1}} [:a :b] inc)  # => {:a {:b 2}}
(merge {:a 1} {:b 2 :a 3})        # => {:a 3 :b 2}
```

See [Data Structures](/documentation/data-structures).

## Destructuring

```phel
# Sequential destructuring
(let [[a b c] [1 2 3]]
  (+ a b c))                      # => 6

(let [[a b & rest] [1 2 3 4 5]]
  rest)                            # => (3 4 5)

# Associative destructuring
(let [{:name name :age age} {:name "Alice" :age 30}]
  (str name " is " age))          # => "Alice is 30"

# Default values
(let [{:name name :role role :or {role "guest"}}
      {:name "Bob"}]
  role)                            # => "guest"

# Works in defn, fn, loop too
(defn greet [{:name name}]
  (str "Hello, " name))
(greet {:name "Alice"})            # => "Hello, Alice"
```

See [Destructuring](/documentation/destructuring).

## Defining Things

```phel
(def pi 3.14159)                  # global binding
(def secret :private 42)          # private binding

(defn greet [name]                # public function
  (str "Hello, " name))

(defn- helper [x]                 # private function
  (* x 2))

(defstruct point [x y])           # struct (typed map)
(point 1 2)                       # => (point 1 2)
(point? (point 1 2))              # => true

(let [x 1                         # local bindings
      y (+ x 2)]
  (+ x y))                        # => 4
```

See [Global and Local Bindings](/documentation/global-and-local-bindings).

## Functions

```phel
(fn [x] (* x 2))                  # anonymous function
|(* $ 2)                           # short form (single param)
|(+ $1 $2)                         # short form (multiple params)
|(sum $&)                          # short form (variadic)

(defn greet                        # multi-arity
  ([] "Hi")
  ([name] (str "Hi " name)))

(defn sum [& nums]                 # variadic
  (apply + nums))

(apply + [1 2 3])                  # => 6
(partial + 10)                     # => fn that adds 10
(comp inc inc)                     # => fn that increments twice
(identity 42)                      # => 42
(memoize expensive-fn)             # => cached version of fn
(memoize-lru expensive-fn 100)     # => cached with max 100 entries
```

See [Functions and Recursion](/documentation/functions-and-recursion).

## Control Flow

```phel
(if (> x 0) "pos" "non-pos")      # if/else
(when (> x 0) (print "pos"))      # when (no else branch)

(cond
  (< n 0) "negative"
  (= n 0) "zero"
  :else "positive")

(case status
  200 "OK"
  404 "Not Found")

(do (print "a") (print "b") 42)   # evaluate multiple exprs, return last
```

See [Control Flow](/documentation/control-flow).

## Loops & Recursion

```phel
(loop [acc 0 n 10]                 # loop with recur
  (if (= n 0)
    acc
    (recur (+ acc n) (dec n))))    # => 55

(foreach [v [1 2 3]]              # side-effects only, returns nil
  (print v))

(for [x :in [1 2 3]] (* x 2))    # => [2 4 6] (list comprehension)
(for [x :range [0 5]] x)          # => [0 1 2 3 4]
(for [x :in [1 2 3 4]
      :when (even? x)] x)         # => [2 4]

(dotimes [i 3] (print i))         # prints 0, 1, 2
```

See [Functions and Recursion](/documentation/functions-and-recursion), [Control Flow](/documentation/control-flow).

## Collections

```phel
(map inc [1 2 3])                  # => (2 3 4)
(filter even? [1 2 3 4])          # => (2 4)
(reduce + 0 [1 2 3])              # => 6
(sort [3 1 2])                    # => [1 2 3]
(sort-by :age [{:age 30} {:age 20}])  # sort by key
(group-by :role users)             # map of role -> [users]
(frequencies [:a :b :a :a])        # => {:a 3 :b 1}
(count [1 2 3])                    # => 3
(empty? [])                        # => true
(contains? {:a 1} :a)             # => true
(some even? [1 3 4])              # => true
(every? pos? [1 2 3])             # => true
(into #{} [1 2 1 3])              # => #{1 2 3}
(distinct [1 2 1 3 2])            # => (1 2 3)
(flatten [[1 2] [3 [4]]])         # => (1 2 3 4)
(reverse [1 2 3])                  # => (3 2 1)
(concat [1 2] [3 4])              # => (1 2 3 4)
(compact [1 nil 2 nil 3])         # => (1 2 3)
(remove neg? [1 -2 3 -4])        # => (1 3)
```

See [Data Structures](/documentation/data-structures).

## Lazy Sequences

```phel
(take 5 (range))                   # => (0 1 2 3 4)
(take 5 (iterate inc 0))          # => (0 1 2 3 4)
(take 7 (cycle [1 2 3]))          # => (1 2 3 1 2 3 1)
(take 4 (repeat :x))              # => (:x :x :x :x)
(take 5 (repeatedly |(php/rand 1 100)))  # 5 random numbers

(drop 3 (range 10))               # => (3 4 5 6 7 8 9)
(take-while pos? [3 2 1 0 -1])   # => (3 2 1)
(drop-while pos? [3 2 1 0 -1])   # => (0 -1)
(partition 2 [1 2 3 4 5 6])       # => ((1 2) (3 4) (5 6))
(interleave [:a :b :c] [1 2 3])  # => (:a 1 :b 2 :c 3)

# Lazy filtering + transformation
(->> (range)
     (filter even?)
     (take 5))                     # => (0 2 4 6 8)

# Custom lazy sequence
(defn fibs []
  (lazy-seq (cons 0 (cons 1
    (map + (fibs) (rest (fibs)))))))

(doall (take 8 (fibs)))           # => (0 1 1 2 3 5 8 13)
(realized? (lazy-seq [1 2 3]))    # => false
```

Lazy file I/O:

```phel
(line-seq (php/fopen "file.txt" "r"))  # lazy line-by-line reading
(file-seq "src/")                       # lazy recursive directory listing
(csv-seq (php/fopen "data.csv" "r"))   # lazy CSV parsing
(read-file-lazy "big.txt" 4096)        # lazy chunked reading
```

Lazy sequences were added in v0.25.0. `map`, `filter`, `take`, `drop`, `concat`, `mapcat`, `interleave`, and `partition` all return lazy sequences.

## Threading Macros

```phel
(-> {:name "Alice" :age 30}        # thread-first
    (assoc :role "admin")
    (dissoc :age))

(->> [1 2 3 4 5]                   # thread-last
     (filter odd?)
     (map inc))                    # => [2 4 6]

(as-> [1 2 3] v                    # thread with named binding
      (conj v 4)
      (count v))                   # => 4
```

## Strings

```phel
(str "Hello" " " "World")         # => "Hello World"
(str "n=" 42)                      # => "n=42"
(format "Hi %s, age %d" "Jo" 25)  # => "Hi Jo, age 25"
(php/strtolower "HELLO")           # => "hello"
(php/strtoupper "hello")           # => "HELLO"
(php/str_replace "o" "0" "foo")    # => "f00"
(php/substr "hello" 1 3)           # => "ell"
(php/explode "," "a,b,c")          # => PHP array ["a" "b" "c"]
```

## Mutable State

```phel
(def counter (var 0))              # create a mutable variable
(deref counter)                    # => 0
(set! counter 42)                  # direct reset
(deref counter)                    # => 42
(swap! counter inc)                # apply function, counter is now 43
(swap! counter + 10)               # counter is now 53

# Vars are the only mutable primitive in Phel
# (no atoms, agents, or refs)
```

See [Global and Local Bindings](/documentation/global-and-local-bindings).

## Error Handling

```phel
(try
  (/ 1 0)
  (catch \DivisionByZeroError e
    (str "Error: " (php/-> e (getMessage)))))

(try
  (do-risky-thing)
  (catch \Exception e
    (println (str "Failed: " (php/-> e (getMessage)))))
  (finally
    (cleanup)))

(throw (php/new \InvalidArgumentException "bad input"))
```

See [PHP Interop](/documentation/php-interop).

## Interfaces & Structs

```phel
(definterface Greetable
  (greet [this]))

(definterface HasArea
  (area [this]))

(defstruct circle [radius]
  HasArea
  (area [this] (* 3.14159 radius radius)))

(defstruct person [name age]
  Greetable
  (greet [this] (str "Hello, I'm " name)))

(greet (person "Alice" 30))        # => "Hello, I'm Alice"
(area (circle 5))                  # => 78.53975
(person? (person "Alice" 30))      # => true
```

See [Interfaces](/documentation/interfaces).

## PHP Interop

```phel
# Calling PHP functions
(php/strlen "test")                # => 4
(php/date "Y-m-d")                 # => "2026-02-07"
(php/array_merge arr1 arr2)        # call any PHP function

# Instantiation
(php/new \DateTime "now")          # new DateTime("now")

# Instance methods & properties
(php/-> obj (method arg))          # $obj->method($arg)
(php/-> obj property)              # $obj->property
(php/-> obj (a) (b) (c))           # chained: $obj->a()->b()->c()

# Static methods & properties
(php/:: MyClass CONST)             # MyClass::CONST
(php/:: MyClass (create "x"))      # MyClass::create("x")

# PHP arrays
(php/aget arr 0)                   # $arr[0] ?? null
(php/aset arr "k" "v")             # $arr["k"] = "v"
(php/apush arr "v")                # $arr[] = "v"
```

See [PHP Interop](/documentation/php-interop).

## Namespaces

```phel
(ns my-app\handlers
  (:require my-app\db)              # import Phel module
  (:require my-app\utils :as u)     # with alias
  (:require my-app\auth :refer [login logout])  # import symbols
  (:use \DateTimeImmutable)          # import PHP class
  (:use \Some\Long\Name :as Short)) # PHP class with alias

(db/query "SELECT 1")               # use module prefix
(u/format-date date)                 # use alias
(login credentials)                  # use referred symbol
(php/new DateTimeImmutable)          # use imported class
```

See [Namespaces](/documentation/namespaces).

## Testing

```phel
(ns my-app\tests
  (:require phel\test :refer [deftest is are]))

(deftest addition-test
  (is (= 4 (+ 2 2)))
  (is (= 4 (+ 2 2)) "optional description"))

(deftest multiple-assertions
  (are (= expected (inc input))
    2 1
    3 2
    4 3))

(deftest exception-test
  (is (thrown? \Exception
    (throw (php/new \Exception "boom")))))
```

```bash
./vendor/bin/phel test                       # run all tests
./vendor/bin/phel test tests/main.phel       # run specific file
./vendor/bin/phel test --filter my-test      # filter by name
```

See [Testing](/documentation/testing).
