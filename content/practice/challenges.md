+++
title = "Putting It All Together"
weight = 6
+++

Time to combine everything you've learned! These challenges are bigger exercises that draw on multiple concepts. Take your time, break them into smaller pieces, and have fun.

{% question() %}
**FizzBuzz**: Write a function that takes a number `n` and returns a vector where each number from 1 to `n` is replaced by:
- `"Fizz"` if divisible by 3
- `"Buzz"` if divisible by 5
- `"FizzBuzz"` if divisible by both
- The number itself otherwise

```phel
(fizzbuzz 15)
# => [1 2 "Fizz" 4 "Buzz" "Fizz" 7 8 "Fizz" "Buzz" 11 "Fizz" 13 14 "FizzBuzz"]
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
This combines `for` (list comprehension), `cond` (multi-branch conditionals), and modular arithmetic. Note that we check divisibility by 15 first — order matters in `cond`!

Learn more: [Control Flow](/documentation/control-flow), [Arithmetic](/documentation/arithmetic)
{% end %}

{% question() %}
**Fibonacci**: Implement a function that returns the first `n` Fibonacci numbers.
```phel
(fib 8) # => [0 1 1 2 3 5 8 13]
```
Hint: use `loop`/`recur` to build up the vector.
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
Each new number is the sum of the two before it. We use `loop`/`recur` with an accumulator vector, and `slice` at the end to handle edge cases.

Learn more: [Control Flow](/documentation/control-flow), [Data Structures](/documentation/data-structures)
{% end %}

{% question() %}
**Caesar cipher**: Write `encode` and `decode` functions that shift letters by a given number of positions.
```phel
(encode "hello" 3)  # => "khoor"
(decode "khoor" 3)  # => "hello"
```
Only shift lowercase letters a-z. Leave other characters unchanged. Hint: use `php/ord` and `php/chr` for character codes.
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

(encode "hello" 3)  # => "khoor"
(decode "khoor" 3)  # => "hello"
```
This combines: `map` over a string (treating it as a sequence of characters), anonymous functions, PHP interop for character codes, and modular arithmetic for wrapping around the alphabet.

Learn more: [PHP Interop](/documentation/php-interop), [Functions and Recursion](/documentation/functions-and-recursion)
{% end %}

{% question() %}
**Word frequency analyzer**: Find the five most used words from a book, ignoring common stop words.

Tips:
1. Load the book content into a variable
2. Split it into words
3. Filter out stop words
4. Count word frequencies
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

# Output:
# [whale 566] [like 323] [then 302] [upon 298] [ye 288]
```
This is a beautiful example of the `->>` threading macro in action. Each step is a clear transformation: lowercase, filter, count, sort, take. The pipeline reads like a recipe.

Learn more: [PHP Interop](/documentation/php-interop), [Data Structures](/documentation/data-structures), [Functions and Recursion](/documentation/functions-and-recursion)
{% end %}

{% question() %}
**Rock, Paper, Scissors**: Create an interactive CLI game where the computer picks randomly and you type your choice.

Requirements:
- The computer generates a random guess
- The player enters "r", "p", or "s"
- Determine the winner and print the result
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
  (print "# ")
  (let [guess (sanitize-input (php/readline))]
    (if (php/in_array guess (to-php-array possible-guesses)) guess)))

(defn calculate-winner [{:computer cg :player pg}]
  (let [guesses [cg pg]]
    (cond
      (= cg pg)                   tie
      (= guesses [paper rock])    computer-wins
      (= guesses [scissors paper]) computer-wins
      (= guesses [rock scissors]) computer-wins
      (= guesses [rock paper])    player-wins
      (= guesses [paper scissors]) player-wins
      (= guesses [scissors rock]) player-wins)))

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

# Game loop
(loop []
  (play-hand)
  (println)
  (recur))
```
This exercise brings together: global definitions, destructuring, `cond`, `let`, `loop`/`recur`, PHP interop for user input, and data-driven design (using maps for game state).

Learn more: [PHP Interop](/documentation/php-interop), [Control Flow](/documentation/control-flow), [Destructuring](/documentation/destructuring)
{% end %}
