+++
title = "Cheat Sheet"
weight = 1
description = "The Phel forms and core functions you use every day, one screen per topic, with links to the full API reference."
aliases = ["/documentation/cheat-sheet"]
+++

The forms and functions you reach for every day, one block per topic. For every function and its full signature, see the [API reference](/documentation/reference/api/). For how a form works, follow the link under each block.

{% callout(kind="tip") %}
**AI agents:** load [Agentic Coding](/documentation/reference/agentic-coding) first for the truncation-safe rules and PHP-interop gotchas.
{% end %}

## Basic syntax

<!-- phel-test: skip -->
```phel
;; standalone comment        ; inline comment
nil  true  false              ; only false and nil are falsy
42  -3  1.5  0xFF  0b1010     ; numbers
"hello\n"                     ; string
:status  :user/id             ; keywords
my-var  my-module/fn          ; symbols
@my-atom                      ; (deref my-atom)
#"[a-z]+"                     ; regex literal (PCRE)
#(+ %1 %2)  #(inc %)  #(apply + %&)   ; anonymous fn shorthand
#'my-fn                       ; (var my-fn)
#inst "2026-01-15T12:00:00Z"  ; DateTimeImmutable
#uuid "550e8400-e29b-41d4-a716-446655440000"
#?(:phel a :default b)        ; reader conditional (.cljc)
```

`#` and `#| |#` comments were removed: use `;`. See [Basic Types](/documentation/language/basic-types) and [Reader Shortcuts](/documentation/language/reader-shortcuts).

## Data structures

```phel
[1 2 3]  {:a 1 :b 2}  #{1 2 3}  '(1 2 3)   ; vector, map, set, list

(get {:a 1} :b "default")          ; => "default"
(get-in {:a {:b 1}} [:a :b])       ; => 1
(:name {:name "Alice"})            ; => "Alice"
([10 20 30] 1)                     ; => 20
(first [1 2 3])                    ; => 1
(peek [1 2 3])                     ; => 3

(conj [1 2] 3)                     ; => [1 2 3]
(assoc {:a 1} :b 2)                ; => {:a 1, :b 2}
(dissoc {:a 1 :b 2} :a)            ; => {:b 2}
(update {:a 1} :a inc)             ; => {:a 2}
(assoc-in {} [:a :b] 1)            ; => {:a {:b 1}}
(update-in {:a {:b 1}} [:a :b] inc) ; => {:a {:b 2}}
(merge {:a 1} {:b 2 :a 3})         ; => {:a 3, :b 2}
(update-vals {:a 1 :b 2} inc)      ; => {:a 2, :b 3}
(select-keys {:a 1 :b 2} [:a])     ; => {:a 1}
(keys {:a 1})  (vals {:a 1})       ; => [:a] [1]
```

See [Data Structures](/documentation/language/data-structures).

## Destructuring

```phel
(let [[a b & more] [1 2 3 4]] more)              ; => [3 4]
(let [{n :name} {:name "Alice"}] n)              ; => "Alice"
(let [{r :role :or {r "guest"}} {}] r)           ; => "guest"
(defn greet [{name :name}] (str "Hello, " name))
(greet {:name "Alice"})                          ; => "Hello, Alice"
```

Works in `let`, `fn`, `defn`, `loop`, and `for`. See [Destructuring](/documentation/language/destructuring).

## Defining things

<!-- phel-test: skip -->
```phel
(def pi 3.14159)                   ; global binding
(def secret :private 42)           ; private binding
(defonce conn (connect!))          ; skipped on reload if already defined
(defn greet [name] (str "Hi " name))
(defn- helper [x] (* x 2))         ; private function
(let [x 1 y (+ x 2)] (+ x y))      ; => 4

(defstruct point [x y])
(point 1 2)                        ; => (user.point 1 2)
(point? (point 1 2))               ; => true

(defmulti area :shape)             ; multimethod, dispatch on :shape
(defmethod area :circle [{r :radius}] (* 3.14 r r))
```

See [Global and Local Bindings](/documentation/language/global-and-local-bindings).

## Functions

<!-- phel-test: skip -->
```phel
(fn [x] (* x 2))
#(* % 2)
(defn greet ([] "Hi") ([name] (str "Hi " name)))   ; multi-arity
(defn sum [& nums] (apply + nums))                  ; variadic

(apply + [1 2 3])                  ; => 6
(partial + 10)                     ; fn that adds 10
(comp inc inc)                     ; fn that increments twice
(juxt :id :name)                   ; fn returning [(:id x) (:name x)]
(complement even?)                 ; fn returning the opposite
(some-fn pos? even?)  (every-pred pos? even?)
(memoize f)  (memoize-lru f 100)   ; cached versions of f

(defn ^:memoize fib [n] ...)       ; metadata shorthands
(defn ^:async fetch [url] ...)     ; body runs in (async ...)
(defn ^int add [^int a ^int b] (+ a b))   ; PHP type declarations
```

See [Functions and Recursion](/documentation/language/functions-and-recursion).

## Control flow

```phel
(def n 5)
(if (> n 0) "pos" "non-pos")       ; => "pos"
(when (> n 0) "pos")               ; => "pos" (nil otherwise)
(cond (< n 0) "neg" (= n 0) "zero" :else "pos")   ; => "pos"
(case n 1 "one" 5 "five" "other")  ; => "five"
(if-let [x (get {:a 1} :a)] x 0)   ; => 1
(when-let [x nil] x)               ; => nil
(and 1 nil 2)  (or nil 2)          ; => nil 2

(loop [acc 0 i 10]
  (if (= i 0) acc (recur (+ acc i) (dec i))))    ; => 55
(for [x :in [1 2 3 4] :when (even? x)] (* x 10)) ; => [20 40]
(for [x :range [0 3]] x)           ; => [0 1 2]
(foreach [v [1 2 3]] (print v))    ; side effects, returns nil
(dotimes [i 3] (print i))          ; prints 012
```

`for` builds a vector. `foreach`, `doseq`, and `dotimes` run side effects. See [Control Flow](/documentation/language/control-flow).

## Collections

```phel
(map inc [1 2 3])                  ; => (2 3 4)
(mapv inc [1 2 3])                 ; => [2 3 4]
(filter even? [1 2 3 4])           ; => (2 4)
(remove neg? [1 -2 3])             ; => (1 3)
(reduce + 0 [1 2 3])               ; => 6
(map-indexed vector [:a :b])       ; => ([0 :a] [1 :b])
(mapcat reverse [[1 2] [3 4]])     ; => (2 1 4 3)
(keep :id [{:id 1} {} {:id 2}])    ; => (1 2)
(sort [3 1 2])                     ; => [1 2 3]
(sort-by :age [{:age 30} {:age 20}]) ; => [{:age 20} {:age 30}]
(group-by even? [1 2 3 4])         ; => {false [1 3], true [2 4]}
(frequencies [:a :b :a])           ; => {:a 2, :b 1}
(distinct [1 2 1 3])               ; => (1 2 3)
(zipmap [:a :b] [1 2])             ; => {:a 1, :b 2}
(into #{} [1 2 1])                 ; => #{1 2}
(concat [1 2] [3])                 ; => (1 2 3)
(flatten [[1 2] [3 [4]]])          ; => (1 2 3 4)
(count [1 2 3])                    ; => 3
(empty? [])                        ; => true
(contains? {:a 1} :a)              ; => true
(some even? [1 3 4])               ; => true
(every? pos? [1 2 3])              ; => true
```

Sorted collections, set relations (`subseq`, `select`, `index`), and `phel.walk` helpers are in the [API reference](/documentation/reference/api/core/). See [Data Structures](/documentation/language/data-structures).

## Lazy sequences

```phel
(take 5 (range))                   ; => (0 1 2 3 4)
(take 3 (iterate #(* 2 %) 1))      ; => (1 2 4)
(take 5 (cycle [1 2]))             ; => (1 2 1 2 1)
(take 2 (repeat :x))               ; => (:x :x)
(drop 3 (range 6))                 ; => (3 4 5)
(take-while pos? [3 2 0 -1])       ; => (3 2)
(partition 2 [1 2 3 4])            ; => ([1 2] [3 4])
(partition 2 1 [1 2 3])            ; => ([1 2] [2 3])
(partition-all 2 [1 2 3])          ; => ([1 2] [3])
(interleave [:a :b] [1 2])         ; => (:a 1 :b 2)
(doall (map inc [1 2]))            ; => [2 3] (realize now)
```

<!-- phel-test: skip -->
```phel
(line-seq "file.txt")              ; lines, read on demand
(csv-seq "data.csv")               ; CSV rows as vectors of strings
(file-seq "src/")                  ; recursive directory listing
(slurp "file.txt")  (spit "out.txt" "text")   ; whole file in, whole file out
```

`map`, `filter`, `take`, `drop`, `concat`, and `mapcat` are lazy. To write your own with `lazy-seq`, see [Lazy Sequences](/documentation/language/lazy-sequences).

## Threading macros

```phel
(-> {:name "Alice"} (assoc :role "admin") (dissoc :name))  ; => {:role "admin"}
(->> [1 2 3 4] (filter odd?) (map inc))                    ; => (2 4)
(as-> [1 2] v (conj v 3) (count v))                        ; => 3
(cond-> 1 true inc false (* 42))                           ; => 2
(some-> {:a {:b 1}} :a :b inc)                             ; => 2
```

## Strings

```phel
(ns my-app.strings
  (:require phel.string :as str))

(str "n=" 42)                      ; => "n=42"
(format "%s is %d" "Jo" 25)        ; => "Jo is 25"
(str/join ", " ["a" "b"])          ; => "a, b"
(str/split "a,b,c" #",")           ; => ["a" "b" "c"]
(str/replace "foo" "o" "0")        ; => "f00"
(str/trim "  hi  ")                ; => "hi"
(str/upper-case "hi")              ; => "HI"
(str/starts-with? "hello" "he")    ; => true
(str/includes? "hello" "ell")      ; => true
(str/subs "hello" 1 3)             ; => "el"
(str/blank? "  ")                  ; => true
```

Full list: [phel.string](/documentation/reference/api/string/).

## Regular expressions

```phel
(re-find #"\d+" "abc123def")       ; => "123"
(re-find #"(\w+)@(\w+)" "me@host") ; => ["me@host" "me" "host"]
(re-matches #"\d+" "abc123")       ; => nil (must match the whole string)
(re-seq #"\d+" "a1b2c3")           ; => ["1" "2" "3"]
```

## Mutable state

```phel
(def counter (atom 0))
@counter                           ; => 0
(swap! counter inc)                ; => 1
(swap! counter + 10)               ; => 11
(reset! counter 0)                 ; => 0
(add-watch counter :log (fn [k ref old new] (println old "->" new)))
(set-validator! counter #(>= % 0)) ; reject negative values
```

`compare-and-set!`, `swap-vals!`, and `reset-vals!` are in [phel.core](/documentation/reference/api/core/).

## Error handling

<!-- phel-test: skip -->
```phel
(try
  (risky)
  (catch InvalidArgumentException e (.getMessage e))
  (catch Exception e (log e))
  (finally (cleanup)))

(throw (InvalidArgumentException. "bad input"))
(throw (ex-info "User not found" {:id 42}))

(ex-message e)                     ; => "User not found"
(ex-data e)                        ; => {:id 42}
(ex-cause e)                       ; => wrapped exception or nil
```

See [Error Handling](/documentation/language/error-handling).

## Structs and interfaces

```phel
(definterface HasArea (area [this]))

(defstruct circle [radius]
  HasArea
  (area [this] (* 3 radius radius)))

(area (circle 2))                  ; => 12
(:radius (circle 2))               ; => 2
```

See [Interfaces](/documentation/language/interfaces).

## Protocols

Dispatch on the type of the first argument. Unlike interfaces, you can extend a protocol to types you do not own.

```phel
(defprotocol Describe (describe [this]))
(defstruct dog [name])

(extend-type dog Describe (describe [this] (str "dog " (:name this))))
(extend-protocol Describe
  :string (describe [this] (str "text " this)))

(describe (dog "Rex"))             ; => "dog Rex"
(describe "hi")                    ; => "text hi"
(satisfies? Describe (dog "Rex"))  ; => true
```

A struct cannot implement a protocol inline: use `extend-type`. See [Interfaces](/documentation/language/interfaces).

## Transducers

```phel
(def xf (comp (filter even?) (map inc)))
(into [] xf [1 2 3 4])             ; => [3 5]
(transduce xf + 0 [1 2 3 4])       ; => 8
(sequence xf [1 2 3 4])            ; => [3 5]
(into [] cat [[1 2] [3]])          ; => [1 2 3]
```

`map`, `filter`, `take`, `drop`, `partition-all` and others return a transducer when called without a collection. See [Transducers](/documentation/language/transducers).

## PHP interop

| Phel | PHP |
|---|---|
| `(php/strlen "x")` | `strlen("x")` |
| `(DateTime. "now")` | `new DateTime("now")` |
| `(.format d "Y-m-d")` | `$d->format("Y-m-d")` |
| `(.-days interval)` | `$interval->days` |
| `(DateTime/createFromFormat f s)` | `DateTime::createFromFormat($f, $s)` |
| `DateTime/ATOM` | `DateTime::ATOM` |
| `(php/aget arr 0)` | `$arr[0] ?? null` |
| `(php/aset arr "k" "v")` | `$arr["k"] = "v"` |
| `(to-php-array [1 2])` | `[1, 2]` |
| `(php-array-to-map arr)` | Phel map from a PHP array |

See [PHP Interop](/documentation/language/php-interop).

## Namespaces

<!-- phel-test: skip -->
```phel
(ns my-app.handlers
  (:require my-app.db)                         ; use as db/query
  (:require my-app.utils :as u)                ; use as u/format-date
  (:require my-app.auth :refer [login])        ; use as login
  (:use DateTimeImmutable)                     ; PHP class
  (:use Some.Long.Name :as Short))
```

See [Namespaces](/documentation/language/namespaces).

## Testing

```phel
(ns my-app.tests
  (:require phel.test :refer [deftest is are]))

(deftest math-test
  (is (= 4 (+ 2 2)) "optional message")
  (is (thrown? Exception (throw (Exception. "boom"))))
  (are [expected x] (= expected (inc x))
    2 1
    3 2))
```

```bash
./vendor/bin/phel test                    # all tests
./vendor/bin/phel test --filter math-test # by name
```

See [Testing](/documentation/guides/testing).

## Async

```phel
(def f (async (+ 1 2)))
(await f)                          ; => 3
(await-all [(async 1) (async 2)])  ; => [1 2]
(pmap inc [1 2 3])                 ; => [2 3 4]
(force (delay (+ 1 2)))            ; => 3 (evaluated once, then cached)
```

See [Async](/documentation/language/async).

## Arithmetic

```phel
(/ 10 3)                           ; => 10/3 (exact Ratio)
(/ 10.0 3)                         ; => 3.3333333333333
(quot 10 3)  (rem 10 3)            ; => 3 1
(mod -10 3)                        ; => 2
(** 2 10)                          ; => 1024
(parse-long "42")                  ; => 42
(parse-double "3.14")              ; => 3.14
(min 3 1 2)  (max 3 1 2)           ; => 1 3
```

See [Numeric Tower](/documentation/language/numeric-tower).

## REPL and debugging

<!-- phel-test: skip -->
```phel
(doc map)                          ; docstring
(source my-fn)                     ; source code as a string
(macroexpand '(when true 1))       ; expand a macro
(dbg (* w h))                      ; print [file:line] form => value, return value
(inspect x)                        ; structural view of any value
(break)                            ; pause in a sub-REPL over the local bindings
(tap> {:event :login})             ; send a value to every tap
```

See [REPL](/documentation/tooling/repl) and [Debugging](/documentation/guides/debugging).

## More in the API reference

- Printing: `pr-str`, `prn`, `println-str` in [phel.core](/documentation/reference/api/core/)
- Data walking: `postwalk`, `keywordize-keys` in [phel.walk](/documentation/reference/api/walk/)
- Serialization: [phel.edn](/documentation/reference/api/edn/), [phel.transit](/documentation/reference/api/transit/), [phel.json](/documentation/reference/api/json/)
- Hierarchies: `derive`, `isa?`, `ancestors` in [phel.core](/documentation/reference/api/core/)
- Reflection: [phel.reflect](/documentation/reference/api/reflect/)
- Tracing: `deftrace`, `dotrace` in [phel.trace](/documentation/reference/api/trace/)
