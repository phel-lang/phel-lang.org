+++
title = "Real challenges"
weight = 4
+++

{% question(title="Challenge") %}
Print the last 5 most used words from a book.

Some tips:
1) Save the book content in a variable
2) Split the book by their words
3) Make pairs by word appearance/frequency
4) Sort and print

> Hint: you can use the content of a book from this URL:
> ```phel
> (def book-url "https://gist.githubusercontent.com/Chemaclass/da9a0ba72adee6644193c730d4f307b2/raw/1164593f76ae7157d816bcc8d700937dfb73420e/moby-dick.txt")
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
(def top-words (set "the" "he" "at" "but" "there" "of" "was" "be" "not" "use" "and" "for" "this" "what" "an" "a" "on" "have" "all" "each" "to" "are" "from" "were" "which" "in" "as" "or" "we" "she" "is" "with" "ine" "when" "do" "you" "his" "had" "your" "how" "that" "they" "by" "can" "their" "it" "I" "word" "said" "if" "i" "s"))

# To each word
(->> words
     # map them as lower case
     (map php/strtolower)
     # filter out the common words
     (filter |(nil? (top-words $)))
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
