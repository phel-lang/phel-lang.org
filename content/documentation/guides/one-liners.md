+++
title = "One-liners"
weight = 5
description = "Small Phel tricks, each a single expression: primes, Fibonacci, string ciphers, matrix transpose, grouping, FizzBuzz, and more."
aliases = ["/documentation/one-liners"]
+++

Each entry is one expression. Paste it into the REPL and see the result. Some wrap across lines for reading, but it's still one form.

> **Tricks, not basics.** The plain `map`, `filter`, `reduce`, and `frequencies` calls live in the [Cheat Sheet](/documentation/reference/cheat-sheet/). The PHP side-by-side lives in the [Rosetta Stone](/documentation/guides/rosetta-stone/). This page combines them.

## Math & Numbers

Fibonacci sequence (first 10):

```phel
(take 10 (map first (iterate (fn [[a b]] [b (+ a b)]) [0 1])))
;; => (0 1 1 2 3 5 8 13 21 34)
```

Check if a number is prime:

```phel
(let [n 17]
  (and (> n 1)
       (every? (fn [d] (not= 0 (% n d)))
               (range 2 (php/intval (+ 1 (php/sqrt n)))))))
;; => true
```

Greatest common divisor (Euclidean algorithm):

```phel
(loop [a 48 b 18] (if (= b 0) a (recur b (% a b))))
;; => 6
```

Power via reduce:

```phel
(let [base 2 exp 10]
  (reduce (fn [acc _] (* acc base)) 1 (range 0 exp)))
;; => 1024
```

## Strings

Palindrome check:

```phel
(let [s "racecar"] (= s (phel.string/reverse s)))
;; => true
```

Count vowels:

```phel
(->> (seq "functional programming")
     (filter #(contains? #{"a" "e" "i" "o" "u"} %))
     count)
;; => 7
```

Title case:

```phel
(->> (phel.string/split "hello world of phel" #" ")
     (map phel.string/capitalize)
     (phel.string/join " "))
;; => "Hello World Of Phel"
```

ROT13 cipher:

```phel
(->> (seq "Hello")
     (map (fn [c]
            (let [o (php/ord c)]
              (cond
                (and (>= o 65) (<= o 90))
                  (php/chr (+ 65 (% (+ (- o 65) 13) 26)))
                (and (>= o 97) (<= o 122))
                  (php/chr (+ 97 (% (+ (- o 97) 13) 26)))
                :else c))))
     (apply str))
;; => "Uryyb"
```

Alternating character pattern:

```phel
(phel.string/join "" (map #(if (even? %) "*" "-") (range 0 10)))
;; => "*-*-*-*-*-"
```

## Collections

Flatten nested vectors one level:

```phel
(apply concat [[1 2] [3 4] [5 6]])
;; => (1 2 3 4 5 6)
```

Zip two vectors together:

```phel
(map vector [:a :b :c] [1 2 3])
;; => ([:a 1] [:b 2] [:c 3])
```

Transpose a matrix:

```phel
(apply map vector [[1 2 3] [4 5 6] [7 8 9]])
;; => ([1 4 7] [2 5 8] [3 6 9])
```

Index a collection by key:

```phel
(let [coll [{:id 1 :name "Alice"} {:id 2 :name "Bob"}]]
  (zipmap (map :id coll) coll))
;; => {1 {:id 1, :name "Alice"}, 2 {:id 2, :name "Bob"}}
```

## Data Processing

Group and count:

```phel
(->> [{:role "admin"} {:role "user"} {:role "admin"}
      {:role "user"} {:role "user"}]
     (group-by :role)
     pairs                          ; map -> [key value] tuples
     (map (fn [[k v]] [k (count v)])))
;; => (["admin" 2] ["user" 3])
```

Top N items by key:

```phel
(->> [{:name "A" :score 42} {:name "B" :score 99} {:name "C" :score 71}]
     (sort-by :score)
     reverse
     (take 2))
;; => ({:name "B", :score 99} {:name "C", :score 71})
```

Sum values by category:

```phel
(->> [{:cat "a" :v 10} {:cat "b" :v 20} {:cat "a" :v 30}]
     (group-by :cat)
     pairs                          ; map -> [key value] tuples
     (reduce (fn [acc [k items]]
               (assoc acc k (reduce + 0 (map :v items))))
             {}))
;; => {"a" 40, "b" 20}
```

Frequency-sorted leaderboard:

```phel
(->> (frequencies [:alice :bob :alice :carol :bob :alice])
     (into [])
     (sort-by second)
     reverse)
;; => [[:alice 3] [:bob 2] [:carol 1]]
```

## Fun & Creative

FizzBuzz (1 to 20):

```phel
(map (fn [n]
       (cond
         (= 0 (% n 15)) "FizzBuzz"
         (= 0 (% n 3))  "Fizz"
         (= 0 (% n 5))  "Buzz"
         :else n))
     (range 1 21))
;; => (1 2 "Fizz" 4 "Buzz" "Fizz" 7 8 "Fizz" "Buzz" 11 "Fizz" 13 14 "FizzBuzz" 16 17 "Fizz" 19 "Buzz")
```

Caesar cipher (shift by 3):

```phel
(->> (seq "Attack at dawn")
     (map (fn [c]
            (let [o (php/ord c)]
              (cond
                (and (>= o 65) (<= o 90))
                  (php/chr (+ 65 (% (+ (- o 65) 3) 26)))
                (and (>= o 97) (<= o 122))
                  (php/chr (+ 97 (% (+ (- o 97) 3) 26)))
                :else c))))
     (apply str))
;; => "Dwwdfn dw gdzq"
```

Simple slug generator:

```phel
(-> "Hello World, This is Phel!"
    (phel.string/lower-case)
    (phel.string/replace " " "-")
    (phel.string/replace #"[^a-z0-9-]" ""))
;; => "hello-world-this-is-phel"
```

Collatz sequence from a starting number:

```phel
(loop [n 12 acc []]
  (if (= n 1)
    (conj acc 1)
    (recur (if (even? n) (/ n 2) (+ 1 (* 3 n)))
           (conj acc n))))
;; => [12 6 3 10 5 16 8 4 2 1]
```

Diamond pattern (width 5):

```phel
(->> (concat (range 1 6 2) (range 3 0 -2))
     (map #(phel.string/join ""
             [(phel.string/repeat " " (/ (- 5 %) 2))
              (phel.string/repeat "*" %)]))
     (phel.string/join "\n"))
;; => "  *\n ***\n*****\n ***\n  *"
```

## Next steps

- [Cookbook](/documentation/guides/cookbook/): full recipes for files, HTTP, dates, and state
- [Cheat Sheet](/documentation/reference/cheat-sheet/): every core form on one page
- [Lazy sequences](/documentation/language/lazy-sequences/): how `iterate`, `range`, and `take` stay lazy
