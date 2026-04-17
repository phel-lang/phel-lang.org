+++
title = "Real Programs"
weight = 6
+++

Time to put everything together. These challenges grow from gentle warm-ups into bigger programs that combine data, control flow, and the functional toolbox. Take your time, break problems into pieces, and have fun.

{% question(difficulty="hard") %}
**Temperature converter**: write `c->f` and `f->c` to convert between Celsius and Fahrenheit. Then build `convert` so that `(convert 100 :c->f)` returns `212.0`.
```phel
(c->f 100)            ;; => 212.0
(f->c 32)             ;; => 0.0
(convert 100 :c->f)   ;; => 212.0
(convert 32 :f->c)    ;; => 0.0
```
{% end %}
{% solution() %}
```phel
(defn c->f [c] (+ (* c 1.8) 32))
(defn f->c [f] (/ (- f 32) 1.8))

(defn convert [degrees direction]
  (case direction
    :c->f (c->f degrees)
    :f->c (f->c degrees)
    (throw (php/new \Exception (str "Unknown direction: " direction)))))
```
A friendly warm-up: small functions, `case` to dispatch on a keyword, and a defensive default. Once you have the building blocks, `convert` is just a router.

Learn more: [Functions and Recursion](/documentation/language/functions-and-recursion), [Control Flow](/documentation/language/control-flow)
{% end %}

{% question(difficulty="hard") %}
**FizzBuzz**: return a vector where each number from 1 to `n` is replaced by:
- `"Fizz"` if divisible by 3
- `"Buzz"` if divisible by 5
- `"FizzBuzz"` if divisible by both
- the number itself otherwise

```phel
(fizzbuzz 15)
;; => [1 2 "Fizz" 4 "Buzz" "Fizz" 7 8 "Fizz" "Buzz" 11 "Fizz" 13 14 "FizzBuzz"]
```
{% end %}
{% solution() %}
```phel
(defn fizzbuzz [n]
  (for [i :in (range 1 (inc n))]
    (cond
      (zero? (% i 15)) "FizzBuzz"
      (zero? (% i 3))  "Fizz"
      (zero? (% i 5))  "Buzz"
      i)))
```
A `for` comprehension over `range`, with `cond` doing the dispatch. Notice we test divisibility by 15 first - in `cond`, order matters.

Learn more: [Control Flow](/documentation/language/control-flow), [Arithmetic](/documentation/language/arithmetic)
{% end %}

{% question(difficulty="hard") %}
**Fibonacci**: return the first `n` Fibonacci numbers.
```phel
(fib 8) ;; => [0 1 1 2 3 5 8 13]
```
Hint: `loop`/`recur` with an accumulator.
{% end %}
{% solution() %}
```phel
(defn fib [n]
  (loop [nums [0 1]]
    (if (>= (count nums) n)
      (slice nums 0 n)
      (let [a (get nums (- (count nums) 2))
            b (get nums (- (count nums) 1))]
        (recur (push nums (+ a b)))))))
```
Each new number is the sum of the two before it. We grow a vector with `loop`/`recur` and `slice` at the end so `(fib 1)` and `(fib 0)` behave.

Learn more: [Control Flow](/documentation/language/control-flow), [Data Structures](/documentation/language/data-structures)
{% end %}

{% question(difficulty="hard") %}
**Caesar cipher**: write `encode` and `decode` that shift lowercase letters by `n` positions. Leave other characters alone.
```phel
(encode "hello" 3)  ;; => "khoor"
(decode "khoor" 3)  ;; => "hello"
```
Hint: `php/ord` and `php/chr` give you character codes.
{% end %}
{% solution() %}
```phel
(defn shift-char [c amount]
  (let [code (php/ord c)]
    (if (and (>= code 97) (<= code 122))
      (php/chr (+ 97 (% (+ (- code 97) amount) 26)))
      c)))

(defn encode [text n]
  (apply str (map |(shift-char $ n) text)))

(defn decode [text n]
  (encode text (- 26 n)))

(encode "hello" 3)  ;; => "khoor"
(decode "khoor" 3)  ;; => "hello"
```
This combines `map` over a string (treated as a sequence of characters), the short anonymous `|` form, PHP interop for character codes, and modular arithmetic for the wrap-around.

Learn more: [PHP Interop](/documentation/php-interop), [Functions and Recursion](/documentation/language/functions-and-recursion)
{% end %}

{% question(difficulty="hard") %}
**Word frequency analyzer**: find the five most-used words in a book, ignoring stop words.

Tips:
1. Load the book content
2. Split into words
3. Filter out stop words
4. Count frequencies
5. Sort and take the top 5

```phel
(def book-url "https://gist.githubusercontent.com/Chemaclass/da9a0ba72adee6644193c730d4f307b2/raw/1164593f76ae7157d816bcc8d700937dfb73420e/moby-dick.txt")
(def stop-words (set "the" "he" "at" "but" "there" "of" "was" "be" "not" "use" "and" "for" "this" "what" "an" "a" "on" "have" "all" "each" "to" "are" "from" "were" "which" "in" "as" "or" "we" "she" "is" "with" "ine" "when" "do" "you" "his" "had" "your" "how" "that" "they" "by" "can" "their" "it" "I" "word" "said" "if" "i" "s"))
```
{% end %}
{% solution() %}
```phel
(def book-url "https://gist.githubusercontent.com/Chemaclass/da9a0ba72adee6644193c730d4f307b2/raw/1164593f76ae7157d816bcc8d700937dfb73420e/moby-dick.txt")
(def full-book (php/file_get_contents book-url))
(def words (re-seq "/\\w+/" full-book))

(def stop-words (set "the" "he" "at" "but" "there" "of" "was" "be" "not" "use" "and" "for" "this" "what" "an" "a" "on" "have" "all" "each" "to" "are" "from" "were" "which" "in" "as" "or" "we" "she" "is" "with" "ine" "when" "do" "you" "his" "had" "your" "how" "that" "they" "by" "can" "their" "it" "I" "word" "said" "if" "i" "s"))

(->> words
     (map php/strtolower)
     (filter |(not (contains? stop-words $)))
     (frequencies)
     (pairs)
     (sort-by second)
     (take-last 5)
     (reverse)
     (apply println))

;; Output:
;; [whale 566] [like 323] [then 302] [upon 298] [ye 288]
```
A textbook `->>` pipeline. Each step reads as a sentence: lowercase, drop stop words, count, sort, take. This is the shape a lot of real Phel data work takes.

Learn more: [PHP Interop](/documentation/php-interop), [Data Structures](/documentation/language/data-structures), [Functions and Recursion](/documentation/language/functions-and-recursion)
{% end %}

{% question(difficulty="hard") %}
**Counter with mutable state**: build a tiny counter using `var` and `swap!`.
```phel
(reset-counter!)
(tick!) (tick!) (tick!)
(current) ;; => 3
```
{% end %}
{% solution() %}
```phel
(def counter (var 0))

(defn current [] @counter)
(defn tick! [] (swap! counter inc))
(defn reset-counter! [] (swap! counter (fn [_] 0)))

(reset-counter!)
(tick!) (tick!) (tick!)
(current) ;; => 3
```
Most Phel data is immutable, but sometimes you need a single mutable cell - a request counter, a cached result, an in-memory app state. `var` gives you exactly that, `swap!` updates it with a function, and `@` (or `deref`) reads the current value. By convention, functions that mutate end with `!`.

Learn more: [Global and Local Bindings](/documentation/language/global-and-local-bindings)
{% end %}

{% question(difficulty="hard") %}
**Rock, Paper, Scissors**: build an interactive CLI game where the computer picks randomly and you type your choice.

Requirements:
- Computer generates a random guess
- Player enters `"r"`, `"p"`, or `"s"`
- Print the result
- Loop until the user stops the program (Ctrl+C)

Hints:
```phel
(def rock "r")
(def paper "p")
(def scissors "s")
(def possible-guesses [rock paper scissors])

(defn read-player-guess ...)
(defn calculate-winner ...)
(defn play-hand ...)
```
{% end %}
{% solution() %}
```phel
(def rock "r")
(def paper "p")
(def scissors "s")
(def possible-guesses [rock paper scissors])

(def computer-wins 1)
(def player-wins 2)
(def tie 3)

(defn sanitize-input [input]
  (get (php/trim (php/strtolower input)) 0))

(defn read-player-guess []
  (println "Play your hand: (r)ock, (p)aper, (s)cissors")
  (print "; ")
  (let [guess (sanitize-input (php/readline))]
    (if (php/in_array guess (to-php-array possible-guesses)) guess)))

(defn calculate-winner [{:computer cg :player pg}]
  (let [guesses [cg pg]]
    (cond
      (= cg pg)                    tie
      (= guesses [paper rock])     computer-wins
      (= guesses [scissors paper]) computer-wins
      (= guesses [rock scissors])  computer-wins
      (= guesses [rock paper])     player-wins
      (= guesses [paper scissors]) player-wins
      (= guesses [scissors rock])  player-wins)))

(defn winner-text [winner]
  (cond
    (= winner tie)           "Game tied!"
    (= winner computer-wins) "Computer wins!"
    (= winner player-wins)   "Player wins!"))

(defn play-hand []
  (let [computer-guess (rand-nth possible-guesses)
        player-guess   (read-player-guess)
        winner         (calculate-winner {:computer computer-guess
                                          :player player-guess})]
    (println "Computer:" computer-guess "| You:" player-guess)
    (if (nil? player-guess)
      (println "> Invalid input, try again!")
      (println ">" (winner-text winner)))))

;; Game loop
(loop []
  (play-hand)
  (println)
  (recur))
```
The boss fight: global definitions, map destructuring in parameters, `cond`, `let`, `loop`/`recur`, PHP interop for input, and data-driven design with maps. Once you've finished this, you've used most of Phel's core toolkit.

Learn more: [PHP Interop](/documentation/php-interop), [Control Flow](/documentation/language/control-flow), [Destructuring](/documentation/language/destructuring)
{% end %}
