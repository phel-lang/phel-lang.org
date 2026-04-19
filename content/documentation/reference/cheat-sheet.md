+++
title = "Cheat Sheet"
weight = 1
aliases = ["/documentation/cheat-sheet"]
+++

A quick reference for Phel syntax and core functions.

## Basic Syntax

```phel
; This is a comment
;; Convention: use ;; for line comments

nil                     ; null value
true false              ; booleans (only false and nil are falsy)
42 -3 1.5 3.14e2        ; numbers
0xFF 0b1010 0o17        ; hex, binary, octal
"hello" "line\nbreak"   ; strings
:keyword :status        ; keywords (interned constants)
my-var my-module/fn     ; symbols
#"[a-z]+"               ; regex literal (PCRE pattern)
```

> **Note:** The `#` line comment and `#| |#` multiline comment syntax are deprecated since v0.31.0. Use `;` for comments instead.

See [Basic Types](/documentation/language/basic-types), [Truth and Boolean Operations](/documentation/language/truth-and-boolean-operations).

## Reader Syntax

```phel
@my-var                 ; shorthand for (deref my-var)
#"pattern"              ; regex literal (PCRE)
#(+ %1 %2)             ; anonymous function shorthand
#(inc %)                ; single-arg: % is the same as %1
#(apply + %&)           ; variadic: %& captures rest args
#?(:phel expr1 :default expr2)  ; reader conditional
#?@(:phel [a b] :default [c])  ; splicing reader conditional
```

The `#(...)` anonymous function shorthand is the preferred form. Use `%` or `%1` for the first argument, `%2` for the second, and `%&` for variadic rest args. The legacy `|(...)` form with `$` placeholders is still accepted but deprecated.

Reader conditionals (`#?()` and `#?@()`) allow code to be shared across `.cljc` files with platform-specific branches using `:phel` and `:default` keys.

## Data Structures

```phel
[1 2 3]                 ; vector (indexed)
(vector 1 2 3)          ; same thing
{:a 1 :b 2}             ; map (key-value pairs)
(hash-map :a 1 :b 2)    ; same thing
#{1 2 3}                ; set (unique values)
(hash-set 1 2 3)        ; set from arguments
(set [1 2 3])           ; coerce collection to set
'(1 2 3)                ; quoted list (data, not a call)
(list 1 2 3)            ; same thing
```

See [Data Structures](/documentation/language/data-structures).

## Accessing Data

```phel
(get [1 2 3] 0)           ; => 1
(get {:a 1} :a)            ; => 1
(get {:a 1} :b "default")  ; => "default"
(get-in {:a {:b 1}} [:a :b])  ; => 1
(first [1 2 3])            ; => 1
(second [1 2 3])           ; => 2
(peek [1 2 3])             ; => 3
(:name {:name "Alice"})    ; => "Alice" (keyword as function)
({:a 1 :b 2} :a)           ; => 1 (map as function)
([10 20 30] 1)             ; => 20 (vector as function)
```

## Modifying Data

```phel
(conj [1 2] 3)                    ; => [1 2 3]
(conj #{1 2} 3)                   ; => #{1 2 3}
(conj {:a 1} [:b 2])              ; => {:a 1 :b 2}
(assoc {:a 1} :b 2)               ; => {:a 1 :b 2}
(assoc [1 2 3] 0 9)               ; => [9 2 3]
(dissoc {:a 1 :b 2} :a)           ; => {:b 2}
(update {:a 1} :a inc)            ; => {:a 2}
(update-keys {:a 1 :b 2} name)    ; => {"a" 1 "b" 2}
(update-vals {:a 1 :b 2} inc)     ; => {:a 2 :b 3}
(assoc-in {} [:a :b] 1)           ; => {:a {:b 1}}
(update-in {:a {:b 1}} [:a :b] inc)  ; => {:a {:b 2}}
(merge {:a 1} {:b 2 :a 3})        ; => {:a 3 :b 2}
```

See [Data Structures](/documentation/language/data-structures).

## Destructuring

```phel
;; Sequential destructuring
(let [[a b c] [1 2 3]]
  (+ a b c))                      ; => 6

(let [[a b & rest] [1 2 3 4 5]]
  rest)                            ; => (3 4 5)

;; Associative destructuring
(let [{:name name :age age} {:name "Alice" :age 30}]
  (str name " is " age))          ; => "Alice is 30"

;; Default values
(let [{:name name :role role :or {role "guest"}}
      {:name "Bob"}]
  role)                            ; => "guest"

;; Works in defn, fn, loop too
(defn greet [{:name name}]
  (str "Hello, " name))
(greet {:name "Alice"})            ; => "Hello, Alice"
```

See [Destructuring](/documentation/language/destructuring).

## Defining Things

```phel
(def pi 3.14159)                  ; global binding
(def secret :private 42)          ; private binding

(defn greet [name]                ; public function
  (str "Hello, " name))

(defn- helper [x]                 ; private function
  (* x 2))

(defstruct point [x y])           ; struct (typed map)
(point 1 2)                       ; => (point 1 2)
(point? (point 1 2))              ; => true

(let [x 1                         ; local bindings
      y (+ x 2)]
  (+ x y))                        ; => 4

(defmulti area :shape)             ; multimethod (dispatch on :shape)
(defmethod area :circle [{:radius r}]
  (* 3.14 r r))
```

See [Global and Local Bindings](/documentation/language/global-and-local-bindings).

## Functions

```phel
(fn [x] (* x 2))                  ; anonymous function
#(* % 2)                           ; short form (single param)
#(+ %1 %2)                        ; short form (multiple params)
#(apply + %&)                     ; short form (variadic)
|(* $ 2)                           ; legacy short form (deprecated)

(defn greet                        ; multi-arity
  ([] "Hi")
  ([name] (str "Hi " name)))

(defn sum [& nums]                 ; variadic
  (apply + nums))

(apply + [1 2 3])                  ; => 6
(partial + 10)                     ; => fn that adds 10
(comp inc inc)                     ; => fn that increments twice
(identity 42)                      ; => 42
(memoize expensive-fn)             ; => cached version of fn
(memoize-lru expensive-fn 100)     ; => cached with max 100 entries
```

See [Functions and Recursion](/documentation/language/functions-and-recursion).

## Control Flow

```phel
(if (> x 0) "pos" "non-pos")      ; if/else
(when (> x 0) (print "pos"))      ; when (no else branch)

(cond
  (< n 0) "negative"
  (= n 0) "zero"
  :else "positive")

(case status
  200 "OK"
  404 "Not Found")

(do (print "a") (print "b") 42)   ; evaluate multiple exprs, return last
```

See [Control Flow](/documentation/language/control-flow).

## Loops & Recursion

```phel
(loop [acc 0 n 10]                 ; loop with recur
  (if (= n 0)
    acc
    (recur (+ acc n) (dec n))))    ; => 55

(foreach [v [1 2 3]]              ; side-effects only, returns nil
  (print v))

(for [x :in [1 2 3]] (* x 2))    ; => [2 4 6] (list comprehension)
(for [x :range [0 5]] x)          ; => [0 1 2 3 4]
(for [x :in [1 2 3 4]
      :when (even? x)] x)         ; => [2 4]

(dotimes [i 3] (print i))         ; prints 0, 1, 2
```

See [Functions and Recursion](/documentation/language/functions-and-recursion), [Control Flow](/documentation/language/control-flow).

## Collections

```phel
(map inc [1 2 3])                  ; => (2 3 4)
(filter even? [1 2 3 4])          ; => (2 4)
(reduce + 0 [1 2 3])              ; => 6
(sort [3 1 2])                    ; => [1 2 3]
(sort-by :age [{:age 30} {:age 20}])  ; sort by key
(group-by :role users)             ; map of role -> [users]
(frequencies [:a :b :a :a])        ; => {:a 3 :b 1}
(count [1 2 3])                    ; => 3
(empty? [])                        ; => true
(contains? {:a 1} :a)             ; => true
(some even? [1 3 4])              ; => true
(every? pos? [1 2 3])             ; => true
(into #{} [1 2 1 3])              ; => #{1 2 3}
(vec '(1 2 3))                     ; => [1 2 3] (coerce to vector)
(subset? #{1 2} #{1 2 3})         ; => true
(superset? #{1 2 3} #{1 2})       ; => true
(distinct [1 2 1 3 2])            ; => (1 2 3)
(flatten [[1 2] [3 [4]]])         ; => (1 2 3 4)
(reverse [1 2 3])                  ; => (3 2 1)
(concat [1 2] [3 4])              ; => (1 2 3 4)
(compact [1 nil 2 nil 3])         ; => (1 2 3)
(remove neg? [1 -2 3 -4])        ; => (1 3)
```

See [Data Structures](/documentation/language/data-structures).

## Walking Data Structures

```phel
(postwalk f nested)                ; transform bottom-up
(prewalk f nested)                 ; transform top-down
(postwalk-replace {:a :x} [:a :b]) ; => [:x :b]
(keywordize-keys {"name" "Alice"}) ; => {:name "Alice"}
(stringify-keys {:name "Alice"})   ; => {"name" "Alice"}
```

See [Data Structures](/documentation/language/data-structures#walking-data-structures).

## Lazy Sequences

```phel
(take 5 (range))                   ; => (0 1 2 3 4)
(take 5 (iterate inc 0))          ; => (0 1 2 3 4)
(take 7 (cycle [1 2 3]))          ; => (1 2 3 1 2 3 1)
(take 4 (repeat :x))              ; => (:x :x :x :x)
(take 5 (repeatedly #(php/rand 1 100)))  ; 5 random numbers

(drop 3 (range 10))               ; => (3 4 5 6 7 8 9)
(take-while pos? [3 2 1 0 -1])   ; => (3 2 1)
(drop-while pos? [3 2 1 0 -1])   ; => (0 -1)
(partition 2 [1 2 3 4 5 6])       ; => ((1 2) (3 4) (5 6))
(interleave [:a :b :c] [1 2 3])  ; => (:a 1 :b 2 :c 3)

;; Lazy filtering + transformation
(->> (range)
     (filter even?)
     (take 5))                     ; => (0 2 4 6 8)

;; Custom lazy sequence
(defn fibs []
  (lazy-seq (cons 0 (cons 1
    (map + (fibs) (rest (fibs)))))))

(doall (take 8 (fibs)))           ; => (0 1 1 2 3 5 8 13)
(realized? (lazy-seq [1 2 3]))    ; => false
```

Lazy file I/O:

```phel
(line-seq (php/fopen "file.txt" "r"))  ; lazy line-by-line reading
(file-seq "src/")                       ; lazy recursive directory listing
(csv-seq (php/fopen "data.csv" "r"))   ; lazy CSV parsing
(read-file-lazy "big.txt" 4096)        ; lazy chunked reading
```

Lazy sequences were added in v0.25.0. `map`, `filter`, `take`, `drop`, `concat`, `mapcat`, `interleave`, and `partition` all return lazy sequences.

## Threading Macros

```phel
(-> {:name "Alice" :age 30}        ; thread-first
    (assoc :role "admin")
    (dissoc :age))

(->> [1 2 3 4 5]                   ; thread-last
     (filter odd?)
     (map inc))                    ; => [2 4 6]

(as-> [1 2 3] v                    ; thread with named binding
      (conj v 4)
      (count v))                   ; => 4

(cond-> 1                          ; conditional thread-first
        true inc
        false (* 42))              ; => 2

(cond->> [1 2 3]                   ; conditional thread-last
         true (map inc)
         false (filter odd?))      ; => (2 3 4)
```

## Strings

```phel
(str "Hello" " " "World")         ; => "Hello World"
(str "n=" 42)                      ; => "n=42"
(format "Hi %s, age %d" "Jo" 25)  ; => "Hi Jo, age 25"
(php/strtolower "HELLO")           ; => "hello"
(php/strtoupper "hello")           ; => "HELLO"
(php/str_replace "o" "0" "foo")    ; => "f00"
(php/substr "hello" 1 3)           ; => "ell"
(php/explode "," "a,b,c")          ; => PHP array ["a" "b" "c"]
```

## Regular Expressions

```phel
;; Regex literals use #"..." syntax (PCRE patterns)
(re-find #"\d+" "abc123def")       ; => "123"
(re-find #"(\w+)@(\w+)" "user@host")
                                   ; => ["user@host" "user" "host"]
(re-matches #"\d+" "123")          ; => "123"
(re-matches #"\d+" "abc123")       ; => nil (must match entire string)

;; Use regex for validation
(defn valid-email? [s]
  (not (nil? (re-matches #".+@.+\..+" s))))

(valid-email? "alice@example.com") ; => true
(valid-email? "not-an-email")      ; => false
```

## Mutable State

```phel
(def counter (atom 0))             ; create an atom (mutable container)
(deref counter)                    ; => 0
@counter                           ; => 0 (shorthand for deref)
(reset! counter 42)                ; direct reset
@counter                           ; => 42
(swap! counter inc)                ; apply function, counter is now 43
(swap! counter + 10)               ; counter is now 53

;; Watchers: react to state changes
(add-watch counter :logger
  (fn [key ref old-val new-val]
    (println (str "Changed from " old-val " to " new-val))))
(remove-watch counter :logger)

;; Validators: constrain allowed values
(set-validator! counter #(>= % 0))  ; only non-negative values
(get-validator counter)             ; => the validator fn
```

See [Global and Local Bindings](/documentation/language/global-and-local-bindings).

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

;; Structured exceptions with ex-info (v0.31.0+)
(throw (ex-info "User not found" {:id 42 :type :not-found}))

(try
  (throw (ex-info "Validation failed" {:field :email} nil))
  (catch \Exception e
    (ex-message e)                 ; => "Validation failed"
    (ex-data e)                    ; => {:field :email}
    (ex-cause e)))                 ; => nil
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

(greet (person "Alice" 30))        ; => "Hello, I'm Alice"
(area (circle 5))                  ; => 78.53975
(person? (person "Alice" 30))      ; => true
```

See [Interfaces](/documentation/language/interfaces).

## Protocols

Protocols provide polymorphic dispatch based on the type of the first argument. They are more flexible than interfaces because you can extend existing types without modifying them.

```phel
;; Define a protocol
(defprotocol Stringable
  (to-string [this]))

;; Extend a struct to implement the protocol
(defstruct dog [name breed])

(extend-type dog
  Stringable
  (to-string [this] (str (get this :name) " the " (get this :breed))))

(to-string (dog "Rex" "Labrador")) ; => "Rex the Labrador"

;; Extend multiple types at once with extend-protocol
(extend-protocol Stringable
  :string  (to-string [this] this)
  :int     (to-string [this] (str this)))

;; Check protocol support
(satisfies? Stringable (dog "Rex" "Labrador"))  ; => true
(extends? Stringable dog)                        ; => true
```

## Hierarchy System

Derive ad-hoc hierarchies for use with multimethods and `isa?` checks.

```phel
(derive :square :shape)
(derive :circle :shape)
(derive :filled-square :square)

(isa? :square :shape)              ; => true
(isa? :filled-square :shape)       ; => true
(parents :square)                  ; => #{:shape}
(ancestors :filled-square)         ; => #{:square :shape}
(descendants :shape)               ; => #{:square :circle :filled-square}

;; Use a custom hierarchy
(def h (make-hierarchy))
(def h (derive h :dog :animal))
(isa? h :dog :animal)              ; => true
```

## Transducers

Transducers are composable transformations that work independently of the data source. They avoid creating intermediate collections, making pipelines more efficient.

```phel
;; Basic transducer usage with transduce
(transduce (map inc) + 0 [1 2 3])       ; => 9
(transduce (filter even?) + 0 [1 2 3 4]) ; => 6

;; Compose transducers (left-to-right order)
(def xf (comp (filter even?) (map inc)))
(transduce xf conj [] [1 2 3 4 5 6])    ; => [3 5 7]

;; into with a transducer (3-arg form)
(into [] (map inc) [1 2 3])              ; => [2 3 4]
(into #{} (filter odd?) [1 2 3 2 1])     ; => #{1 3}

;; sequence: lazy transducer application
(sequence (map inc) [1 2 3])             ; => (2 3 4)

;; cat: concatenating transducer for nested collections
(into [] cat [[1 2] [3 4] [5]])          ; => [1 2 3 4 5]

;; completing: supply a final step to a reducing function
(transduce (map inc) (completing + str) 0 [1 2 3])  ; => "9"

;; Many core fns have transducer arities (called with no collection):
;; (map f), (filter pred), (take n), (drop n), (partition-all n), etc.
```

## PHP Interop

```phel
;; Calling PHP functions
(php/strlen "test")                ; => 4
(php/date "Y-m-d")                 ; => "2026-02-07"
(php/array_merge arr1 arr2)        ; call any PHP function

;; Instantiation
(php/new \DateTime "now")          ; new DateTime("now")

;; Instance methods & properties
(php/-> obj (method arg))          ; $obj->method($arg)
(php/-> obj property)              ; $obj->property
(php/-> obj (a) (b) (c))           ; chained: $obj->a()->b()->c()

;; Static methods & properties
(php/:: MyClass CONST)             ; MyClass::CONST
(php/:: MyClass (create "x"))      ; MyClass::create("x")

;; PHP arrays
(php/aget arr 0)                   ; $arr[0] ?? null
(php/aset arr "k" "v")             ; $arr["k"] = "v"
(php/apush arr "v")                ; $arr[] = "v"
```

See [PHP Interop](/documentation/php-interop).

## Namespaces

```phel
(ns my-app\handlers
  (:require my-app\db)              ; import Phel module
  (:require my-app\utils :as u)     ; with alias
  (:require my-app\auth :refer [login logout])  ; import symbols
  (:use \DateTimeImmutable)          ; import PHP class
  (:use \Some\Long\Name :as Short)) ; PHP class with alias

(db/query "SELECT 1")               ; use module prefix
(u/format-date date)                 ; use alias
(login credentials)                  ; use referred symbol
(php/new DateTimeImmutable)          ; use imported class
```

See [Namespaces](/documentation/language/namespaces).

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
./vendor/bin/phel test --fail-fast           # stop on first failure
```

See [Testing](/documentation/testing).

## Delay & Force

```phel
;; Delay defers evaluation until first access
(def d (delay (do (println "computing...") 42)))
(delay? d)                         ; => true
(force d)                          ; prints "computing...", => 42
(force d)                          ; => 42 (cached, no recomputation)
```

## Iteration

```phel
;; iteration: produce a lazy sequence from a step function
;; Useful for paginated APIs or stateful producers
(defn fetch-page [token]
  {:items [1 2 3] :next-token (when (nil? token) "page2")})

(iteration fetch-page
  :kf :next-token
  :vf :items
  :initk nil)
```

## Utility Functions

```phel
(parse-long "42")                  ; => 42
(parse-double "3.14")              ; => 3.14
(parse-boolean "true")             ; => true
(abs -5)                           ; => 5
(inf? php/INF)                     ; => true
(random-uuid)                      ; => "550e8400-e29b-..." (random UUID string)
```

## REPL Utilities

```phel
(source my-fn)                     ; print source code of a function
(find-fn "map")                    ; search for functions by name
(symbol-info 'map)                 ; detailed info about a symbol
(ns-publics 'phel\core)           ; all public vars in a namespace
(ns-aliases 'my-app\core)         ; namespace aliases
(ns-refers 'my-app\core)          ; referred symbols
(ns-list)                          ; list all loaded namespaces
(macroexpand-1 '(when true 1))    ; expand one level of macro
(macroexpand '(when true 1))      ; fully expand macro
(eval-str "(+ 1 2)")              ; evaluate a string of Phel code
(load-file "src/my-module.phel")  ; load and evaluate a file
(test-ns 'my-app\tests)           ; run tests in a namespace
```
