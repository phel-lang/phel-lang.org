+++
title = "One-liner Gallery"
weight = 21
+++

Elegant solutions in a single expression. These one-liners showcase Phel's expressiveness and functional power.

## Math & Numbers

Factorial of 10:

```phel
(reduce * 1 (range 1 11))
# => 3628800
```

Multiplies all numbers from 1 to 10 using `reduce`.

Sum of squares from 1 to 100:

```phel
(->> (range 1 101) (map |(* $ $)) (reduce + 0))
# => 338350
```

Threads the range through squaring each element, then summing.

Fibonacci sequence (first 10):

```phel
(->> (range 2 10)
     (reduce (fn [acc _]
               (conj acc (+ (peek acc) (get acc (- (count acc) 2)))))
             [0 1]))
# => [0 1 1 2 3 5 8 13 21 34]
```

Builds the sequence by always appending the sum of the last two elements.

Check if a number is prime:

```phel
(let [n 17]
  (and (> n 1)
       (every? |(not= 0 (% n $))
               (range 2 (php/intval (+ 1 (php/sqrt n)))))))
# => true
```

Tests that no integer from 2 up to the square root divides `n` evenly.

Greatest common divisor:

```phel
(loop [a 48 b 18] (if (= b 0) a (recur b (% a b))))
# => 6
```

The classic Euclidean algorithm using `loop`/`recur`.

Power via reduce:

```phel
(let [base 2 exp 10]
  (reduce (fn [acc _] (* acc base)) 1 (range 0 exp)))
# => 1024
```

Multiplies the base by itself `exp` times.

## Strings

Reverse a string:

```phel
(str/reverse "hello")
# => "olleh"
```

Uses Phel's string library to reverse characters.

Palindrome check:

```phel
(let [s "racecar"] (= s (str/reverse s)))
# => true
```

A string is a palindrome if it equals its own reversal.

Count vowels in a string:

```phel
(->> (seq "functional programming")
     (filter |(contains? (set "a" "e" "i" "o" "u") $))
     count)
# => 6
```

Converts the string to a sequence of characters, filters vowels, and counts them.

Title case a string:

```phel
(->> (str/split "hello world of phel" "/ /")
     (map str/capitalize)
     (str/join " "))
# => "Hello World Of Phel"
```

Splits on spaces, capitalizes each word, then joins them back.

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
# => "Uryyb"
```

Shifts each letter by 13 positions, wrapping around the alphabet.

Repeat string pattern:

```phel
(str/join "" (map |(if (even? $) "*" "-") (range 0 10)))
# => "*-*-*-*-*-"
```

Alternates characters based on even/odd index positions.

## Collections

Flatten nested vectors one level:

```phel
(apply concat [[1 2] [3 4] [5 6]])
# => (1 2 3 4 5 6)
```

Concatenates all inner collections into a single lazy sequence.

Unique elements preserving order:

```phel
(distinct [3 1 4 1 5 9 2 6 5 3])
# => (3 1 4 5 9 2 6)
```

Returns a lazy sequence with duplicates removed, keeping first occurrences.

Zip two vectors together:

```phel
(map vector [:a :b :c] [1 2 3])
# => ([:a 1] [:b 2] [:c 3])
```

`map` with multiple collections applies the function to parallel elements.

Partition into pairs:

```phel
(partition 2 [1 2 3 4 5 6])
# => [[1 2] [3 4] [5 6]]
```

Groups consecutive elements into chunks of size 2.

Transpose a matrix:

```phel
(apply map vector [[1 2 3] [4 5 6] [7 8 9]])
# => ([1 4 7] [2 5 8] [3 6 9])
```

Turns rows into columns by applying `map vector` across all rows.

Character frequencies:

```phel
(frequencies (seq "abracadabra"))
# => {"a" 5 "b" 2 "r" 2 "c" 1 "d" 1}
```

Counts how many times each character appears in the string.

Index a collection by key:

```phel
(reduce (fn [acc item] (assoc acc (get item :id) item))
        {}
        [{:id 1 :name "Alice"} {:id 2 :name "Bob"}])
# => {1 {:id 1 :name "Alice"} 2 {:id 2 :name "Bob"}}
```

Builds a lookup map keyed by `:id` from a vector of maps.

Interleave and take:

```phel
(take 7 (interleave [:a :b :c :d] [1 2 3 4]))
# => (:a 1 :b 2 :c 3 :d)
```

Weaves two sequences together, then takes the first 7 elements.

## Data Processing

Group and count:

```phel
(->> [{:role "admin"} {:role "user"} {:role "admin"}
      {:role "user"} {:role "user"}]
     (group-by :role)
     (map-indexed (fn [_ [k v]] [k (count v)])))
# => (["admin" 2] ["user" 3])
```

Groups items by `:role`, then maps each group to its count.

Top N items by key:

```phel
(->> [{:name "A" :score 42} {:name "B" :score 99} {:name "C" :score 71}]
     (sort-by :score)
     reverse
     (take 2))
# => ({:name "B" :score 99} {:name "C" :score 71})
```

Sorts by `:score`, reverses to descending, and takes the top 2.

Merge maps with defaults:

```phel
(merge {:host "localhost" :port 3306 :db "test"}
       {:port 5432 :db "prod"})
# => {:host "localhost" :port 5432 :db "prod"}
```

Later maps override earlier ones, perfect for config defaults.

Sum values by category:

```phel
(->> [{:cat "a" :v 10} {:cat "b" :v 20} {:cat "a" :v 30}]
     (group-by :cat)
     (reduce (fn [acc [k items]]
               (assoc acc k (reduce + 0 (map :v items))))
             {}))
# => {"a" 40 "b" 20}
```

Groups by `:cat`, then reduces each group to the sum of its `:v` values.

Build a frequency-sorted leaderboard:

```phel
(->> (frequencies [:alice :bob :alice :carol :bob :alice])
     (sort-by second)
     reverse)
# => ([:alice 3] [:bob 2] [:carol 1])
```

Counts occurrences, then sorts by frequency descending.

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
# => (1 2 "Fizz" 4 "Buzz" "Fizz" 7 8 "Fizz" "Buzz" 11 "Fizz" 13 14 "FizzBuzz" 16 17 "Fizz" 19 "Buzz")
```

The classic interview question in a single `map` expression.

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
# => "Dwwdfn dw gdzq"
```

Shifts each letter forward by 3 positions in the alphabet.

Simple slug generator:

```phel
(->> "Hello World, This is Phel!"
     (str/lower-case)
     (str/replace " " "-")
     (str/replace "/[^a-z0-9-]/" ""))
# => "hello-world-this-is-phel"
```

Lowercases, replaces spaces with hyphens, and strips non-alphanumeric characters.

Collatz sequence from a starting number:

```phel
(loop [n 12 acc []]
  (if (= n 1)
    (conj acc 1)
    (recur (if (even? n) (/ n 2) (+ 1 (* 3 n)))
           (conj acc n))))
# => [12 6 3 10 5 16 8 4 2 1]
```

Generates the Collatz sequence: if even divide by 2, if odd multiply by 3 and add 1.

Diamond pattern (width 5):

```phel
(->> (concat (range 1 6 2) (range 3 0 -2))
     (map |(str/join ""
             [(str/repeat " " (/ (- 5 $) 2))
              (str/repeat "*" $)]))
     (str/join "\n"))
# => "  *\n ***\n*****\n ***\n  *"
```

Builds each row with leading spaces and stars, then joins with newlines.
