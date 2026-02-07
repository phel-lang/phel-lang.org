+++
title = "Cheat Sheet"
weight = 1
+++

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
```

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

## Collections

```phel
(map inc [1 2 3])                  # => [2 3 4]
(filter even? [1 2 3 4])          # => [2 4]
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
```

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
