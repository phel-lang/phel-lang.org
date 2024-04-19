+++
title = "Advanced"
weight = 4
+++

{% question() %}
Implement a `fibonacci` function.
{% end %}
{% solution() %}
```phel
(defn fib [n]
  (loop [fib-nums [0 1]]
    (if (>= (count fib-nums) n)
      (slice fib-nums 0 n)
      (let [[n1 n2] (reverse fib-nums)]
        (recur (push fib-nums (+ n1 n2)))))))
```
{% end %}

{% question() %} 
Print the five most used words from a book. 

> For better results, it is useful to remove the most common words in the language the book was written in (these are called "[stop words](https://en.wikipedia.org/wiki/Stop_word)").

Some tips:
1) Save the book content in a variable
2) Split the book by their words
3) Make pairs by word appearance/frequency
4) Sort and print

> Hints:
> ```phel
> (def book-url "https://gist.githubusercontent.com/Chemaclass/da9a0ba72adee6644193c730d4f307b2/raw/1164593f76ae7157d816bcc8d700937dfb73420e/moby-dick.txt")
> (def stop-words (set "the" "he" "at" "but" "there" "of" "was" "be" "not" "use" "and" "for" "this" "what" "an" "a" "on" "have" "all" "each" "to" "are" "from" "were" "which" "in" "as" "or" "we" "she" "is" "with" "ine" "when" "do" "you" "his" "had" "your" "how" "that" "they" "by" "can" "their" "it" "I" "word" "said" "if" "i" "s"))
> ...
> # Output example:
> # [whale 81] [whales 26] [sea 21] [some 19] [up 17]
{% end %}
{% solution() %}
```phel
# Load the full book content from the web into the `full-book` constant
(def book-url "https://gist.githubusercontent.com/Chemaclass/da9a0ba72adee6644193c730d4f307b2/raw/1164593f76ae7157d816bcc8d700937dfb73420e/moby-dick.txt")
(def full-book (php/file_get_contents book-url)) # total length 643063 chars

# Take only a part of the full-book in order to speed the execution example.
(def book (php/substr full-book 0 30000))

# Create a vector using all words from the book
(def words (re-seq "/\b\w+\b/" book))

# Create a set with the common words that you want to filter out
(def stop-words (set "the" "he" "at" "but" "there" "of" "was" "be" "not" "use" "and" "for" "this" "what" "an" "a" "on" "have" "all" "each" "to" "are" "from" "were" "which" "in" "as" "or" "we" "she" "is" "with" "ine" "when" "do" "you" "his" "had" "your" "how" "that" "they" "by" "can" "their" "it" "I" "word" "said" "if" "i" "s"))

# To each word
(->> words
     # map them as lower case
     (map php/strtolower)
     # filter out the common words
     (filter |(nil? (stop-words $)))
     # calculate the frequencies of their appearance
     (frequencies)
     # and create pairs of `word -> number of occurrences`
     (pairs)
     # sort by the number of occurrences
     (sort-by second)
     # Take the last 5 items
     (take-last 5)
     # Reverse them from higher to lower
     (reverse)
     # Print each of them
     (apply println))

# Output:
# [whale 81] [whales 26] [sea 21] [some 19] [up 17]
```
{% end %}


{% question() %}
Create the "Rock, paper, scissors!" game.

Requirements:
- The program computes a random letter (the computer-guess).
- The cli-app ask you for a letter "r", "p" or "s" (the player-guess).
- The logic for the game is world known [[wikipedia](https://en.wikipedia.org/wiki/Rock_paper_scissors)].
Each of the three beats one of the other two, and loses to the other.
- Print the result of logic with the winner in the console.

Hints:
```phel
(def rock "r")
(def paper "p")
(def scissors "s")
(def possible-guesses [rock paper scissors])

(defn read-player-guess ...

(defn calculate-winner ...

(defn play-hand ...
```
{% end %}
{% solution() %}
```phel
# First, declare some constants according to the program domain
(def rock "r")
(def paper "p")
(def scissors "s")
(def possible-guesses [rock paper scissors])

(def computer-wins 1)
(def player-wins 2)
(def tie 3)

(defn sanitize-guess-input
  "Returns the first non-empty char from the input as lowercase"
  [input]
  (get (php/trim (php/strtolower input)) 0))

(defn read-player-guess []
  (println "Play your hand: (r)ock, (p)aper, (s)cissors")
  (print "# ")
  (let [guess (sanitize-guess-input (php/readline))]
    (if (php/in_array guess (to-php-array possible-guesses)) guess)))

(defn calculate-winner
  "Return the winner based on the computer and player guesses"
  [{:computer computer-guess :player player-guess}]
  (let [guesses [computer-guess player-guess]]
    (cond
      (= computer-guess player-guess) tie
      (= guesses [paper rock])        computer-wins
      (= guesses [scissors paper])    computer-wins
      (= guesses [rock scissors])     computer-wins
      (= guesses [rock paper])        player-wins
      (= guesses [paper scissors])    player-wins
      (= guesses [scissors rock])     player-wins)))

(defn winner-result-text [winner]
  (cond
    (= winner tie)           "Game tied!"
    (= winner computer-wins) "Computer wins!"
    (= winner player-wins)   "Player wins!"))

(defn play-hand
  "The logic game to play one time"
  []
  (let [computer-guess (rand-nth possible-guesses)
        player-guess   (read-player-guess)
        winner         (calculate-winner {:computer computer-guess
                                          :player player-guess})]
    (println "The computer guessed:" computer-guess)
    (println "You guessed:" player-guess)
    (if (nil? player-guess)
      (println "> Your entry was invalid")
      (println ">" (winner-result-text winner)))))

# Infinite loop till Ctrl+C to kill the program :)
(loop []
  (play-hand)
  (println)
  (recur))
```
{% end %}
