+++
sort_by = "weight"
+++

# Phel in a nutshell

```phel
(defn- fill-keys
  "Exchanges all keys with their associated values in an array."
  [xs v]
  (-> (to-php-array xs)
      (php/array_fill_keys v)
      (php-array-to-map)))

(def book-url "https://gist.githubusercontent.com/Chemaclass/da9a0ba72adee6644193c730d4f307b2/raw/1164593f76ae7157d816bcc8d700937dfb73420e/moby-dick.txt")
(def full-book (php/file_get_contents book-url)) # total length 643063 chars

# Take only a part of the full-book in order to speed the execution example
(def book (php/substr full-book 0 30000))
(def words (re-seq "/\b\w+\b/" book))

(def top-english-words ["the" "he" "at" "but" "there" "of" "was" "be" "not" "use" "and" "for" "this" "what" "an" "a" "on" "have" "all" "each" "to" "are" "from" "were" "which" "in" "as" "or" "we" "she" "is" "with" "ine" "when" "do" "you" "his" "had" "your" "how" "that" "they" "by" "can" "their" "it" "I" "word" "said" "if" "i" "s"])
(def words-to-filter-out (fill-keys top-english-words true))

(->> words
     (map php/strtolower)
     (filter |(not (get words-to-filter-out $)))
     (frequencies)
     (pairs)
     (sort-by second)
     (take-last 5)
     (reverse)
     (apply println))

# Output:
# [whale 81] [whales 26] [sea 21] [some 19] [up 17]
# Time: 00:03.026, Memory: 14.00 MB
```
