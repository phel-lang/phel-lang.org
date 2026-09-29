+++
title = "Projects"
weight = 11
description = "Six small programs built step by step, from a word counter to Conway's Game of Life, using everything from the earlier modules."
aliases = ["/practice/challenges/"]

[extra]
stage = "Projects"
goals = [
  "Split a program into small functions and grow it one step at a time",
  "Keep pure logic apart from printing and state",
  "Model a problem as plain data: maps, vectors, and sets",
  "Combine sequences, destructuring, atoms, errors, and PHP interop in one program",
]
read_first = [
  ["Cookbook", "/documentation/guides/cookbook/"],
  ["Control Flow", "/documentation/language/control-flow/"],
  ["Error Handling", "/documentation/language/error-handling/"],
]
recap = [
  "You can turn a short spec into a working Phel program, one function at a time",
  "You can keep the core of a program pure and push printing and state to the edges",
  "You can use an atom, `try`/`catch`, and PHP functions where a program needs them",
  "You can run a small simulation by applying one pure function again and again",
]
+++

This module has no new concepts. You build six small programs, and each one uses ideas from all the earlier modules. Every project is split into steps. Each step adds one function or one feature, and its solution shows the complete program so far, so you can paste it into a file and run it. Plan 20 to 40 minutes per project.

## Word frequency report

You build a tool that reads a piece of text and prints its most common words as a small bar chart. It draws on strings, maps, and sequence pipelines, plus one PHP function for lowercase text.

{% question(difficulty="easy", kind="build") %}
Write `words`: it takes a string and returns a vector of lowercase words. A word is a run of letters, and an apostrophe counts as a letter. Digits and punctuation are dropped.

<!-- phel-test: skip -->
```phel
(words "The cat saw the Cat.") ; => ["the" "cat" "saw" "the" "cat"]
(words "It's 9 o'clock!")      ; => ["it's" "o'clock"]
```
{% end %}
{% hint() %}
Lowercase first with `php/strtolower`. Then `re-seq` with a regex such as `#"[a-z']+"` returns every match.
{% end %}
{% solution() %}
```phel
(defn words [text]
  (re-seq #"[a-z']+" (php/strtolower text)))

(words "The cat saw the Cat.") ; => ["the" "cat" "saw" "the" "cat"]
(words "It's 9 o'clock!")      ; => ["it's" "o'clock"]
```
Lowercasing before matching means "The" and "the" count as the same word, and the regex only needs lowercase letters.
{% end %}

{% question(difficulty="easy", kind="build") %}
Write `count-words`: it counts each word in a text, but skips common filler words from a `stop-words` set.

<!-- phel-test: skip -->
```phel
(def stop-words #{"the" "a" "an" "and" "of" "to" "is" "it" "in"})

(count-words "The cat and the hat saw a cat.")
; => {"cat" 2, "hat" 1, "saw" 1}
```
{% end %}
{% hint() %}
A `->>` pipeline: `words`, then `remove` the stop words, then `frequencies`.
{% end %}
{% solution() %}
```phel
(defn words [text]
  (re-seq #"[a-z']+" (php/strtolower text)))

(def stop-words #{"the" "a" "an" "and" "of" "to" "is" "it" "in"})

(defn count-words [text]
  (->> (words text)
       (remove #(contains? stop-words %))
       (frequencies)))

(count-words "The cat and the hat saw a cat.")
; => {"cat" 2, "hat" 1, "saw" 1}
```
Each line of the pipeline does one job, and you can read it top to bottom. `frequencies` returns a map from each word to its count.
{% end %}

{% question(difficulty="medium", kind="build") %}
Write `top-words`: it returns the `n` most common words as `[word count]` pairs. Higher counts come first. When two words have the same count, sort them alphabetically.

<!-- phel-test: skip -->
```phel
(top-words 2 "The cat and the hat saw a cat. The hat was red.")
; => (["cat" 2] ["hat" 2])
```
{% end %}
{% hint() %}
A map is a sequence of `[key value]` pairs, so you can `sort-by` it. Sort by a vector key: the negated count first, then the word.
{% end %}
{% solution() %}
```phel
(defn words [text]
  (re-seq #"[a-z']+" (php/strtolower text)))

(def stop-words #{"the" "a" "an" "and" "of" "to" "is" "it" "in"})

(defn count-words [text]
  (->> (words text)
       (remove #(contains? stop-words %))
       (frequencies)))

(defn top-words [n text]
  (->> (count-words text)
       (sort-by (fn [[word total]] [(- total) word]))
       (take n)))

(top-words 2 "The cat and the hat saw a cat. The hat was red.")
; => (["cat" 2] ["hat" 2])
```
Vectors compare item by item, so the key `[(- total) word]` sorts by count (largest first, because it is negated) and breaks ties by word. Sorting by count alone would leave ties in an unpredictable order.
{% end %}

{% question(difficulty="medium", kind="build") %}
Finish the tool. Write `report-line`, which turns one `[word count]` pair into a line with a bar of `#` characters, and `report`, which prints the top `n` lines for a text.

<!-- phel-test: skip -->
```phel
(report-line ["fox" 3]) ; => "fox       3 ###"

(report 3 sample)
;; dog       3 ###
;; fox       3 ###
;; jumps     1 #
```

`(format "%-8s %2d %s" ...)` works like PHP's `sprintf`: `%-8s` pads a string to 8 characters, `%2d` right-aligns a number in 2.
{% end %}
{% hint() %}
`php/str_repeat` builds the bar. In `report`, use `foreach` for the printing, since it is a side effect.
{% end %}
{% solution() %}
```phel
(defn words [text]
  (re-seq #"[a-z']+" (php/strtolower text)))

(def stop-words #{"the" "a" "an" "and" "of" "to" "is" "it" "in"})

(defn count-words [text]
  (->> (words text)
       (remove #(contains? stop-words %))
       (frequencies)))

(defn top-words [n text]
  (->> (count-words text)
       (sort-by (fn [[word total]] [(- total) word]))
       (take n)))

(defn report-line [[word total]]
  (format "%-8s %2d %s" word total (php/str_repeat "#" total)))

(defn report [n text]
  (foreach [line (map report-line (top-words n text))]
    (println line)))

(def sample
  "The fox jumps over the dog. The dog sleeps.
   A fox is quick, and a dog is lazy. The fox wins.")

(report 3 sample)
;; dog       3 ###
;; fox       3 ###
;; jumps     1 #
```
Only `report` prints. Everything before it returns data, so you can test each piece in the REPL on its own. In PHP you would reach for `str_word_count` and `arsort`; here the same job is one short pipeline.
{% end %}

## Shopping cart with discounts

You build a cart that holds products, adds up prices, applies discount rules, and prints a receipt. It draws on maps, multi-arity functions, `for`, `case`, and destructuring. Prices are whole cents, so the math stays exact.

{% question(difficulty="easy", kind="build") %}
The cart is a map from product keyword to quantity. Write `add-item`, which adds a quantity to the cart. When you leave out the quantity, it adds 1.

<!-- phel-test: skip -->
```phel
(-> {}
    (add-item :apple 3)
    (add-item :bread)
    (add-item :apple))
; => {:apple 4, :bread 1}
```
{% end %}
{% hint() %}
Use two arities. `(get cart sku 0)` gives the current quantity, or 0 when the product is not in the cart yet.
{% end %}
{% solution() %}
```phel
(defn add-item
  ([cart sku] (add-item cart sku 1))
  ([cart sku qty]
   (assoc cart sku (+ (get cart sku 0) qty))))

(-> {}
    (add-item :apple 3)
    (add-item :bread)
    (add-item :apple))
; => {:apple 4, :bread 1}
```
The short arity calls the long one with a default, so the logic lives in one place. The cart is never changed; each call returns a new map.
{% end %}

{% question(difficulty="medium", kind="build") %}
Add a product `catalog`, then write `line-items` and `subtotal`. `line-items` returns one map per product with its name, quantity, and line total. `subtotal` adds up all line totals.

<!-- phel-test: skip -->
```phel
(def catalog
  {:apple  {:name "Apple" :price 50}
   :bread  {:name "Bread" :price 220}
   :cheese {:name "Cheese" :price 480}})

(def cart (-> {} (add-item :apple 4) (add-item :bread) (add-item :cheese 2)))

(first (line-items cart)) ; => {:sku :apple, :name "Apple", :qty 4, :total 200}
(subtotal cart)           ; => 1380
```
{% end %}
{% hint() %}
`for` with `:pairs` walks a map as `[key value]` pairs. A `:let` inside `for` can destructure the catalog entry.
{% end %}
{% solution() %}
```phel
(def catalog
  {:apple  {:name "Apple" :price 50}
   :bread  {:name "Bread" :price 220}
   :cheese {:name "Cheese" :price 480}})

(defn add-item
  ([cart sku] (add-item cart sku 1))
  ([cart sku qty]
   (assoc cart sku (+ (get cart sku 0) qty))))

(defn line-items [cart]
  (for [[sku qty] :pairs cart
        :let [{name :name price :price} (get catalog sku)]]
    {:sku sku :name name :qty qty :total (* qty price)}))

(defn subtotal [cart]
  (reduce + 0 (map :total (line-items cart))))

(def cart (-> {} (add-item :apple 4) (add-item :bread) (add-item :cheese 2)))

(first (line-items cart)) ; => {:sku :apple, :name "Apple", :qty 4, :total 200}
(subtotal cart)           ; => 1380
```
`line-items` shapes the raw cart into rows that are easy to sum and to print later. The keyword `:total` works as a function, so `(map :total ...)` pulls one field out of every row.
{% end %}

{% question(difficulty="medium", kind="build") %}
Discounts are data, not code. Write `discount-amount`, which takes the cart and one rule and returns how many cents it takes off. Support two rule types:

- `:bulk`: when the cart has at least `:min-qty` of `:sku`, take `:percent` off that product's line.
- `:over`: when the subtotal is at least `:min-total`, take `:off` cents off.

Any other type takes nothing off. Round down to whole cents.

<!-- phel-test: skip -->
```phel
(def discounts
  [{:type :bulk :sku :apple :min-qty 3 :percent 20}
   {:type :over :min-total 1000 :off 150}])

(map #(discount-amount cart %) discounts) ; => (40 150)
```
{% end %}
{% hint() %}
Destructure `:type` from the rule and branch with `case`. `quot` divides and drops the remainder.
{% end %}
{% solution() %}
```phel
(def catalog
  {:apple  {:name "Apple" :price 50}
   :bread  {:name "Bread" :price 220}
   :cheese {:name "Cheese" :price 480}})

(defn add-item
  ([cart sku] (add-item cart sku 1))
  ([cart sku qty]
   (assoc cart sku (+ (get cart sku 0) qty))))

(defn line-items [cart]
  (for [[sku qty] :pairs cart
        :let [{name :name price :price} (get catalog sku)]]
    {:sku sku :name name :qty qty :total (* qty price)}))

(defn subtotal [cart]
  (reduce + 0 (map :total (line-items cart))))

(def discounts
  [{:type :bulk :sku :apple :min-qty 3 :percent 20}
   {:type :over :min-total 1000 :off 150}])

(defn discount-amount [cart {type :type :as rule}]
  (case type
    :bulk (let [{sku :sku min-qty :min-qty percent :percent} rule
                qty (get cart sku 0)
                price (get-in catalog [sku :price])]
            (if (>= qty min-qty)
              (quot (* qty price percent) 100)
              0))
    :over (if (>= (subtotal cart) (:min-total rule))
            (:off rule)
            0)
    0))

(def cart (-> {} (add-item :apple 4) (add-item :bread) (add-item :cheese 2)))

(map #(discount-amount cart %) discounts) ; => (40 150)
```
Because rules are plain maps, a shop owner could store them in a database or a config file. Adding a new rule type means adding one `case` branch.
{% end %}

{% question(difficulty="medium", kind="build") %}
Finish the cart. Write `checkout`, which returns the subtotal, the total discount, and the final total, and `print-receipt`, which prints each line and the three sums. Show money as `13.80`.

<!-- phel-test: skip -->
```phel
(checkout cart) ; => {:subtotal 1380, :discount 190, :total 1190}

(print-receipt cart)
;; Apple    x4     2.00
;; Bread    x1     2.20
;; Cheese   x2     9.60
;; Subtotal       13.80
;; Discount       -1.90
;; Total          11.90
```
{% end %}
{% hint() %}
A small `money` helper can format cents with `(format "%d.%02d" (quot cents 100) (mod cents 100))`. Destructure each line item in the `foreach` binding.
{% end %}
{% solution() %}
```phel
(def catalog
  {:apple  {:name "Apple" :price 50}
   :bread  {:name "Bread" :price 220}
   :cheese {:name "Cheese" :price 480}})

(defn add-item
  ([cart sku] (add-item cart sku 1))
  ([cart sku qty]
   (assoc cart sku (+ (get cart sku 0) qty))))

(defn line-items [cart]
  (for [[sku qty] :pairs cart
        :let [{name :name price :price} (get catalog sku)]]
    {:sku sku :name name :qty qty :total (* qty price)}))

(defn subtotal [cart]
  (reduce + 0 (map :total (line-items cart))))

(def discounts
  [{:type :bulk :sku :apple :min-qty 3 :percent 20}
   {:type :over :min-total 1000 :off 150}])

(defn discount-amount [cart {type :type :as rule}]
  (case type
    :bulk (let [{sku :sku min-qty :min-qty percent :percent} rule
                qty (get cart sku 0)
                price (get-in catalog [sku :price])]
            (if (>= qty min-qty)
              (quot (* qty price percent) 100)
              0))
    :over (if (>= (subtotal cart) (:min-total rule))
            (:off rule)
            0)
    0))

(defn checkout [cart]
  (let [sub (subtotal cart)
        off (reduce + 0 (map #(discount-amount cart %) discounts))]
    {:subtotal sub :discount off :total (- sub off)}))

(defn money [cents]
  (format "%d.%02d" (quot cents 100) (mod cents 100)))

(defn print-receipt [cart]
  (foreach [{name :name qty :qty total :total} (line-items cart)]
    (println (format "%-8s x%d %8s" name qty (money total))))
  (let [{sub :subtotal off :discount total :total} (checkout cart)]
    (println (format "%-11s %8s" "Subtotal" (money sub)))
    (println (format "%-11s %8s" "Discount" (str "-" (money off))))
    (println (format "%-11s %8s" "Total" (money total)))))

(def cart (-> {} (add-item :apple 4) (add-item :bread) (add-item :cheese 2)))

(checkout cart) ; => {:subtotal 1380, :discount 190, :total 1190}

(print-receipt cart)
;; Apple    x4     2.00
;; Bread    x1     2.20
;; Cheese   x2     9.60
;; Subtotal       13.80
;; Discount       -1.90
;; Total          11.90
```
Storing cents as integers avoids float rounding errors like `0.1 + 0.2`. A common mistake is to format with `(/ cents 100)`: in Phel, `(/ 1380 100)` gives the ratio `69/5`, not a decimal.
{% end %}

## Text adventure

You build a tiny text adventure: rooms, doors, a locked door, and items to pick up. The whole game is one map, and every command is a function from the old game to a new one. It draws on nested maps, `get-in`/`update-in`, `cond`, `case`, and `reduce`.

{% question(difficulty="easy", kind="build") %}
Here is the world. Write `describe`, which returns a sentence about a room: its name, the items you see (only when there are any), and its exits in alphabetical order.

```phel
(def world
  {:hall    {:name "Hall" :exits {:north :library :east :kitchen} :items #{}}
   :kitchen {:name "Kitchen" :exits {:west :hall} :items #{:key}}
   :library {:name "Library" :exits {:south :hall} :items #{:book}
             :locked-by :key}})
```

<!-- phel-test: skip -->
```phel
(describe world :hall)
; => "You are in the Hall. Exits: east, north."
(describe world :kitchen)
; => "You are in the Kitchen. You see: key. Exits: west."
```
{% end %}
{% hint() %}
`(name :north)` returns `"north"`. To join strings with `", "`, turn the list into a PHP array with `to-php-array` and pass it to `php/implode`. `(seq items)` is `nil` for an empty set.
{% end %}
{% solution() %}
```phel
(def world
  {:hall    {:name "Hall" :exits {:north :library :east :kitchen} :items #{}}
   :kitchen {:name "Kitchen" :exits {:west :hall} :items #{:key}}
   :library {:name "Library" :exits {:south :hall} :items #{:book}
             :locked-by :key}})

(defn join-names [keywords]
  (php/implode ", " (to-php-array (map name (sort keywords)))))

(defn describe [rooms room-id]
  (let [{title :name exits :exits items :items} (get rooms room-id)]
    (str "You are in the " title ". "
         (when (seq items)
           (str "You see: " (join-names items) ". "))
         "Exits: " (join-names (keys exits)) ".")))

(describe world :hall)
; => "You are in the Hall. Exits: east, north."
(describe world :kitchen)
; => "You are in the Kitchen. You see: key. Exits: west."
```
`str` skips `nil`, so the `when` drops the "You see" part for empty rooms. The room's name is bound to `title`, not `name`, so it does not hide the `name` function.
{% end %}

{% question(difficulty="medium", kind="build") %}
The game state is a map: `{:rooms world :here :hall :inventory #{} :message ""}`. Write `go`, which moves the player in a direction and sets `:message`. Three cases:

- no exit that way: stay, message "You cannot go that way."
- the target room has `:locked-by` an item you do not carry: stay, message "The door is locked. You need the key."
- otherwise: move, and the message is the description of the new room.

<!-- phel-test: skip -->
```phel
(:message (go start :south)) ; => "You cannot go that way."
(:message (go start :north)) ; => "The door is locked. You need the key."
(:message (go start :east))  ; => "You are in the Kitchen. You see: key. Exits: west."
```
{% end %}
{% hint() %}
`(get-in rooms [here :exits direction])` finds the target, or `nil`. A `cond` with three branches covers the cases in order.
{% end %}
{% solution() %}
```phel
(def world
  {:hall    {:name "Hall" :exits {:north :library :east :kitchen} :items #{}}
   :kitchen {:name "Kitchen" :exits {:west :hall} :items #{:key}}
   :library {:name "Library" :exits {:south :hall} :items #{:book}
             :locked-by :key}})

(defn join-names [keywords]
  (php/implode ", " (to-php-array (map name (sort keywords)))))

(defn describe [rooms room-id]
  (let [{title :name exits :exits items :items} (get rooms room-id)]
    (str "You are in the " title ". "
         (when (seq items)
           (str "You see: " (join-names items) ". "))
         "Exits: " (join-names (keys exits)) ".")))

(def start {:rooms world :here :hall :inventory #{} :message ""})

(defn go [game direction]
  (let [{rooms :rooms here :here inventory :inventory} game
        target (get-in rooms [here :exits direction])
        needs (get-in rooms [target :locked-by])]
    (cond
      (nil? target)
      (assoc game :message "You cannot go that way.")

      (and needs (not (contains? inventory needs)))
      (assoc game :message (str "The door is locked. You need the " (name needs) "."))

      :else
      (assoc game :here target :message (describe rooms target)))))

(:message (go start :south)) ; => "You cannot go that way."
(:message (go start :north)) ; => "The door is locked. You need the key."
(:message (go start :east))  ; => "You are in the Kitchen. You see: key. Exits: west."
```
`go` never prints. It returns a new game, and the message travels inside it. That keeps the rules easy to test.
{% end %}

{% question(difficulty="medium", kind="build") %}
Write `take-item`. When the item is in the current room, move it from the room's `:items` set to `:inventory`. Otherwise, leave the game as it is and set a message.

<!-- phel-test: skip -->
```phel
(def g (-> start (go :east) (take-item :key)))
(:message g)                      ; => "You take the key."
(:inventory g)                    ; => #{:key}
(get-in g [:rooms :kitchen :items]) ; => #{}
(:message (take-item start :key)) ; => "There is no key here."
(:here (-> g (go :west) (go :north))) ; => :library
```
{% end %}
{% hint() %}
`disj` removes a value from a set, like `dissoc` for maps. `update-in` reaches the room's items, `update` reaches the inventory.
{% end %}
{% solution() %}
```phel
(def world
  {:hall    {:name "Hall" :exits {:north :library :east :kitchen} :items #{}}
   :kitchen {:name "Kitchen" :exits {:west :hall} :items #{:key}}
   :library {:name "Library" :exits {:south :hall} :items #{:book}
             :locked-by :key}})

(defn join-names [keywords]
  (php/implode ", " (to-php-array (map name (sort keywords)))))

(defn describe [rooms room-id]
  (let [{title :name exits :exits items :items} (get rooms room-id)]
    (str "You are in the " title ". "
         (when (seq items)
           (str "You see: " (join-names items) ". "))
         "Exits: " (join-names (keys exits)) ".")))

(def start {:rooms world :here :hall :inventory #{} :message ""})

(defn go [game direction]
  (let [{rooms :rooms here :here inventory :inventory} game
        target (get-in rooms [here :exits direction])
        needs (get-in rooms [target :locked-by])]
    (cond
      (nil? target)
      (assoc game :message "You cannot go that way.")

      (and needs (not (contains? inventory needs)))
      (assoc game :message (str "The door is locked. You need the " (name needs) "."))

      :else
      (assoc game :here target :message (describe rooms target)))))

(defn take-item [game item]
  (let [here (:here game)]
    (if (contains? (get-in game [:rooms here :items]) item)
      (-> game
          (update-in [:rooms here :items] disj item)
          (update :inventory conj item)
          (assoc :message (str "You take the " (name item) ".")))
      (assoc game :message (str "There is no " (name item) " here.")))))

(def g (-> start (go :east) (take-item :key)))
(:message g)                          ; => "You take the key."
(:inventory g)                        ; => #{:key}
(get-in g [:rooms :kitchen :items])   ; => #{}
(:message (take-item start :key))     ; => "There is no key here."
(:here (-> g (go :west) (go :north))) ; => :library
```
The key leaves the kitchen and enters the inventory in one `->` chain. Now the locked door opens, because `go` finds `:key` in the inventory.
{% end %}

{% question(difficulty="hard", kind="build") %}
Make the game playable from text commands. Write `parse-command` (turns `"Go North"` into `["go" :north]`), `run-command` (handles `go`, `take`, `look`, and unknown verbs), and `play`, which runs a list of commands in order, prints each command and its message, and returns the final game.

<!-- phel-test: skip -->
```phel
(parse-command "Go North") ; => ["go" :north]
(parse-command "look")     ; => ["look" nil]

(play start ["go north" "go east" "take key" "dance" "go west" "go north"])
;; > go north
;; The door is locked. You need the key.
;; > go east
;; You are in the Kitchen. You see: key. Exits: west.
;; ...
```
{% end %}
{% hint() %}
`keyword` turns a string into a keyword. `play` is a `reduce` over the commands, with the game as the accumulator.
{% end %}
{% solution() %}
```phel
(def world
  {:hall    {:name "Hall" :exits {:north :library :east :kitchen} :items #{}}
   :kitchen {:name "Kitchen" :exits {:west :hall} :items #{:key}}
   :library {:name "Library" :exits {:south :hall} :items #{:book}
             :locked-by :key}})

(defn join-names [keywords]
  (php/implode ", " (to-php-array (map name (sort keywords)))))

(defn describe [rooms room-id]
  (let [{title :name exits :exits items :items} (get rooms room-id)]
    (str "You are in the " title ". "
         (when (seq items)
           (str "You see: " (join-names items) ". "))
         "Exits: " (join-names (keys exits)) ".")))

(def start {:rooms world :here :hall :inventory #{} :message ""})

(defn go [game direction]
  (let [{rooms :rooms here :here inventory :inventory} game
        target (get-in rooms [here :exits direction])
        needs (get-in rooms [target :locked-by])]
    (cond
      (nil? target)
      (assoc game :message "You cannot go that way.")

      (and needs (not (contains? inventory needs)))
      (assoc game :message (str "The door is locked. You need the " (name needs) "."))

      :else
      (assoc game :here target :message (describe rooms target)))))

(defn take-item [game item]
  (let [here (:here game)]
    (if (contains? (get-in game [:rooms here :items]) item)
      (-> game
          (update-in [:rooms here :items] disj item)
          (update :inventory conj item)
          (assoc :message (str "You take the " (name item) ".")))
      (assoc game :message (str "There is no " (name item) " here.")))))

(defn parse-command [line]
  (let [[verb target] (re-seq #"[a-z]+" (php/strtolower line))]
    [verb (when target (keyword target))]))

(defn run-command [game line]
  (let [[verb target] (parse-command line)]
    (case verb
      "go"   (go game target)
      "take" (take-item game target)
      "look" (assoc game :message (describe (:rooms game) (:here game)))
      (assoc game :message (str "I do not know how to " verb ".")))))

(defn play [game lines]
  (reduce (fn [game line]
            (let [next-game (run-command game line)]
              (println (str "> " line))
              (println (:message next-game))
              next-game))
          game
          lines))

(def final
  (play start ["look" "go north" "go east" "take key" "dance"
               "go west" "go north" "take book"]))
;; > look
;; You are in the Hall. Exits: east, north.
;; > go north
;; The door is locked. You need the key.
;; > go east
;; You are in the Kitchen. You see: key. Exits: west.
;; > take key
;; You take the key.
;; > dance
;; I do not know how to dance.
;; > go west
;; You are in the Hall. Exits: east, north.
;; > go north
;; You are in the Library. You see: book. Exits: south.
;; > take book
;; You take the book.

(:inventory final) ; => #{:key :book}
```
The whole game is a `reduce`: each command turns one game value into the next. To make it interactive, read lines with `php/readline` in a loop and call `run-command` on each one; the rules stay the same.
{% end %}

## Bank account ledger

You build an account that accepts deposits and withdrawals, rejects bad ones with clear error data, and keeps a history. The rules are pure functions; one atom holds the current account. It draws on `case`, `cond`, `if-let`, `ex-info`, `try`/`catch`, and atoms.

{% question(difficulty="easy", kind="build") %}
Write `apply-tx`, a pure function that applies one transaction to an account map. A transaction is `{:type :deposit :amount 50}` or `{:type :withdraw :amount 30}`.

<!-- phel-test: skip -->
```phel
(apply-tx {:balance 100} {:type :deposit :amount 50}) ; => {:balance 150}

(reduce apply-tx {:balance 0} [{:type :deposit :amount 100}
                               {:type :withdraw :amount 30}])
; => {:balance 70}
```
{% end %}
{% hint() %}
`(update account :balance + amount)` calls `(+ old-balance amount)`.
{% end %}
{% solution() %}
```phel
(defn apply-tx [account {type :type amount :amount}]
  (case type
    :deposit  (update account :balance + amount)
    :withdraw (update account :balance - amount)))

(apply-tx {:balance 100} {:type :deposit :amount 50}) ; => {:balance 150}

(reduce apply-tx {:balance 0} [{:type :deposit :amount 100}
                               {:type :withdraw :amount 30}])
; => {:balance 70}
```
Because `apply-tx` takes the account first and a transaction second, it fits `reduce` directly: a list of transactions folds into a final balance.
{% end %}

{% question(difficulty="medium", kind="build") %}
Write `validate-tx`. It returns `nil` when a transaction is fine, or a map that describes the problem:

- unknown type: `{:error :unknown-type :type ...}`
- amount not a positive integer: `{:error :bad-amount :amount ...}`
- withdrawal larger than the balance: `{:error :insufficient-funds :balance ... :amount ...}`

<!-- phel-test: skip -->
```phel
(validate-tx {:balance 10} {:type :deposit :amount 5})   ; => nil
(validate-tx {:balance 10} {:type :steal :amount 5})     ; => {:error :unknown-type, :type :steal}
(validate-tx {:balance 10} {:type :deposit :amount -5})  ; => {:error :bad-amount, :amount -5}
(validate-tx {:balance 10} {:type :withdraw :amount 50})
; => {:error :insufficient-funds, :balance 10, :amount 50}
```
{% end %}
{% hint() %}
A `cond` without an `:else` branch returns `nil` when no test matches. `int?` and `pos?` check the amount.
{% end %}
{% solution() %}
```phel
(defn apply-tx [account {type :type amount :amount}]
  (case type
    :deposit  (update account :balance + amount)
    :withdraw (update account :balance - amount)))

(defn validate-tx [{balance :balance} {type :type amount :amount}]
  (cond
    (not (contains? #{:deposit :withdraw} type))
    {:error :unknown-type :type type}

    (not (and (int? amount) (pos? amount)))
    {:error :bad-amount :amount amount}

    (and (= type :withdraw) (> amount balance))
    {:error :insufficient-funds :balance balance :amount amount}))

(validate-tx {:balance 10} {:type :deposit :amount 5})   ; => nil
(validate-tx {:balance 10} {:type :steal :amount 5})     ; => {:error :unknown-type, :type :steal}
(validate-tx {:balance 10} {:type :deposit :amount -5})  ; => {:error :bad-amount, :amount -5}
(validate-tx {:balance 10} {:type :withdraw :amount 50})
; => {:error :insufficient-funds, :balance 10, :amount 50}
```
Errors as data are easy to test and to show to a user. The order of the checks matters: the type check runs first, so the later checks can trust the type.
{% end %}

{% question(difficulty="hard", kind="build") %}
Now add state. Write `record-tx`, which throws an `ex-info` with the problem map when a transaction is invalid, and otherwise applies it and adds it to `:history` together with the new balance. Then hold the account in an atom and write `transact!`, which returns `{:ok balance}` or `{:rejected problem}`.

<!-- phel-test: skip -->
```phel
(def account (atom {:balance 0 :history []}))

(transact! {:type :deposit :amount 100})  ; => {:ok 100}
(transact! {:type :withdraw :amount 500})
; => {:rejected {:error :insufficient-funds, :balance 100, :amount 500}}
(transact! {:type :withdraw :amount 40})  ; => {:ok 60}
```
{% end %}
{% hint() %}
`swap!` passes the current value as the first argument, then your extra arguments, and returns the new value. When the function throws, the atom keeps its old value. Use `if-let` on the result of `validate-tx`.
{% end %}
{% solution() %}
```phel
(defn apply-tx [account {type :type amount :amount}]
  (case type
    :deposit  (update account :balance + amount)
    :withdraw (update account :balance - amount)))

(defn validate-tx [{balance :balance} {type :type amount :amount}]
  (cond
    (not (contains? #{:deposit :withdraw} type))
    {:error :unknown-type :type type}

    (not (and (int? amount) (pos? amount)))
    {:error :bad-amount :amount amount}

    (and (= type :withdraw) (> amount balance))
    {:error :insufficient-funds :balance balance :amount amount}))

(defn record-tx [account tx]
  (if-let [problem (validate-tx account tx)]
    (throw (ex-info "Transaction rejected" problem))
    (let [updated (apply-tx account tx)]
      (update updated :history conj (assoc tx :balance (:balance updated))))))

(def account (atom {:balance 0 :history []}))

(defn transact! [tx]
  (try
    {:ok (:balance (swap! account record-tx tx))}
    (catch \Exception e
      {:rejected (ex-data e)})))

(transact! {:type :deposit :amount 100})  ; => {:ok 100}
(transact! {:type :withdraw :amount 500})
; => {:rejected {:error :insufficient-funds, :balance 100, :amount 500}}
(transact! {:type :withdraw :amount 40})  ; => {:ok 60}

(:balance @account) ; => 60
```
`record-tx` is still pure: give it an account and a transaction, get a new account or an exception. Only `transact!` touches the atom, and its name ends in `!` to say so.
{% end %}

{% question(difficulty="medium", kind="build") %}
Finish the ledger. Write `statement-line`, which formats one history entry, and `run-batch!`, which resets the account, runs a list of transactions, prints the statement and the number of rejected transactions, and returns the final balance.

<!-- phel-test: skip -->
```phel
(run-batch! [{:type :deposit :amount 200}
             {:type :withdraw :amount 50}
             {:type :withdraw :amount 999}
             {:type :deposit :amount 25}
             {:type :refund :amount 10}])
;; deposit      200      200
;; withdraw      50      150
;; deposit       25      175
;; Rejected: 2
; => 175
```
{% end %}
{% hint() %}
`map` is lazy: `(map transact! txs)` runs nothing until someone reads the result. Force it with `(into [] ...)` before you print the history.
{% end %}
{% solution() %}
```phel
(defn apply-tx [account {type :type amount :amount}]
  (case type
    :deposit  (update account :balance + amount)
    :withdraw (update account :balance - amount)))

(defn validate-tx [{balance :balance} {type :type amount :amount}]
  (cond
    (not (contains? #{:deposit :withdraw} type))
    {:error :unknown-type :type type}

    (not (and (int? amount) (pos? amount)))
    {:error :bad-amount :amount amount}

    (and (= type :withdraw) (> amount balance))
    {:error :insufficient-funds :balance balance :amount amount}))

(defn record-tx [account tx]
  (if-let [problem (validate-tx account tx)]
    (throw (ex-info "Transaction rejected" problem))
    (let [updated (apply-tx account tx)]
      (update updated :history conj (assoc tx :balance (:balance updated))))))

(def account (atom {:balance 0 :history []}))

(defn transact! [tx]
  (try
    {:ok (:balance (swap! account record-tx tx))}
    (catch \Exception e
      {:rejected (ex-data e)})))

(defn statement-line [{type :type amount :amount balance :balance}]
  (format "%-9s %6d %8d" (name type) amount balance))

(defn run-batch! [txs]
  (reset! account {:balance 0 :history []})
  (let [results (into [] (map transact! txs))
        rejected (filter :rejected results)]
    (foreach [entry (:history @account)]
      (println (statement-line entry)))
    (println (str "Rejected: " (count rejected)))
    (:balance @account)))

(run-batch! [{:type :deposit :amount 200}
             {:type :withdraw :amount 50}
             {:type :withdraw :amount 999}
             {:type :deposit :amount 25}
             {:type :refund :amount 10}])
;; deposit      200      200
;; withdraw      50      150
;; deposit       25      175
;; Rejected: 2
; => 175
```
With a plain `(map transact! txs)`, the history prints empty, because no transaction has run yet when `foreach` reads the atom. Laziness and side effects do not mix; force the sequence first.
{% end %}

## RPN calculator

You build a calculator for Reverse Polish Notation, where the operator comes after its numbers: `3 4 +` means `3 + 4`. A stack holds the numbers. It draws on PHP string functions, `reduce`, destructuring, `ex-info`, and catching a PHP exception.

{% question(difficulty="easy", kind="build") %}
Write `tokenize`: split a line on whitespace and turn numeric tokens into integers. Other tokens stay strings.

<!-- phel-test: skip -->
```phel
(tokenize "3 4 +")          ; => (3 4 "+")
(tokenize "  10 2 8 * + ")  ; => (10 2 8 "*" "+")
```
{% end %}
{% hint() %}
`re-seq` with `#"\S+"` finds every run of non-space characters. `php/is_numeric` and `php/intval` handle the numbers.
{% end %}
{% solution() %}
```phel
(defn parse-token [token]
  (if (php/is_numeric token)
    (php/intval token)
    token))

(defn tokenize [line]
  (map parse-token (re-seq #"\S+" line)))

(tokenize "3 4 +")          ; => (3 4 "+")
(tokenize "  10 2 8 * + ")  ; => (10 2 8 "*" "+")
```
Matching the tokens is safer than splitting on a single space, because extra spaces would give empty strings. `php/is_numeric` also accepts `"-3"`, so negative numbers work.
{% end %}

{% question(difficulty="medium", kind="build") %}
The stack is a list, with the top first. Write `push-token`: a number goes on top of the stack; an operator takes the top two numbers, applies itself, and puts the result on top. Use this map of operators:

```phel
(def operators {"+" + "-" - "*" * "/" /})
```

<!-- phel-test: skip -->
```phel
(push-token '() 3)                 ; => (3)
(reduce push-token '() [3 4 "+"])  ; => (7)
(reduce push-token '() [10 2 "-"]) ; => (8)
```
{% end %}
{% hint() %}
`conj` on a list adds to the front. Destructure the stack as `[b a & more]`: the top is the second operand, so `10 2 -` is `(- 10 2)`.
{% end %}
{% solution() %}
```phel
(defn parse-token [token]
  (if (php/is_numeric token)
    (php/intval token)
    token))

(defn tokenize [line]
  (map parse-token (re-seq #"\S+" line)))

(def operators {"+" + "-" - "*" * "/" /})

(defn push-token [stack token]
  (if (int? token)
    (conj stack token)
    (let [[b a & more] stack
          op (get operators token)]
      (conj more (op a b)))))

(push-token '() 3)                              ; => (3)
(reduce push-token '() [3 4 "+"])               ; => (7)
(reduce push-token '() [10 2 "-"])              ; => (8)
(reduce push-token '() (tokenize "10 2 8 * +")) ; => (26)
```
The operators are ordinary functions stored in a map, so one branch handles all four. Getting `a` and `b` in the wrong order is the classic bug: `10 2 -` would give `-8`.
{% end %}

{% question(difficulty="medium", kind="build") %}
Write `evaluate`, which runs a whole line and returns the single number left on the stack. Throw an `ex-info` with a clear message when:

- a token is not a number or a known operator: "Unknown token: x"
- an operator finds fewer than two numbers: "Not enough numbers for +"
- the line ends with more or less than one number: "Expected exactly one result"

<!-- phel-test: skip -->
```phel
(evaluate "5 1 2 + 4 * + 3 -") ; => 14
(evaluate "7 2 /")             ; => 7/2
(evaluate "1 +")               ; throws "Not enough numbers for +"
```
{% end %}
{% hint() %}
Check the cases with `cond` inside the step function before you destructure. Then `reduce` over the tokens and look at the final stack.
{% end %}
{% solution() %}
```phel
(defn parse-token [token]
  (if (php/is_numeric token)
    (php/intval token)
    token))

(defn tokenize [line]
  (map parse-token (re-seq #"\S+" line)))

(def operators {"+" + "-" - "*" * "/" /})

(defn push-token [stack token]
  (cond
    (int? token)
    (conj stack token)

    (not (contains? operators token))
    (throw (ex-info (str "Unknown token: " token) {:token token}))

    (< (count stack) 2)
    (throw (ex-info (str "Not enough numbers for " token) {:token token :stack stack}))

    :else
    (let [[b a & more] stack]
      (conj more ((get operators token) a b)))))

(defn evaluate [line]
  (let [stack (reduce push-token '() (tokenize line))]
    (if (= 1 (count stack))
      (first stack)
      (throw (ex-info "Expected exactly one result" {:stack stack})))))

(evaluate "5 1 2 + 4 * + 3 -") ; => 14
(evaluate "7 2 /")             ; => 7/2

(try (evaluate "1 +")
  (catch \Exception e (.getMessage e)))
; => "Not enough numbers for +"
```
`/` on two integers gives an exact ratio in Phel, so `7 2 /` is `7/2`, not `3.5`. The data map in each `ex-info` records the token and the stack, which helps when you debug a long expression.
{% end %}

{% question(difficulty="hard", kind="build") %}
Finish the calculator with `calc`, which never throws. It returns `"3 4 + = 7"` on success and `"... -> error: ..."` on failure. Dividing by zero raises PHP's own `DivisionByZeroError`; catch it and print "division by zero".

<!-- phel-test: skip -->
```phel
(calc "3 4 +") ; => "3 4 + = 7"
(calc "1 0 /") ; => "1 0 / -> error: division by zero"
(calc "2 3 ^") ; => "2 3 ^ -> error: Unknown token: ^"
```
{% end %}
{% hint() %}
A `try` can have several `catch` clauses. The first one whose class matches wins, so put the specific class before `\Exception`.
{% end %}
{% solution() %}
```phel
(defn parse-token [token]
  (if (php/is_numeric token)
    (php/intval token)
    token))

(defn tokenize [line]
  (map parse-token (re-seq #"\S+" line)))

(def operators {"+" + "-" - "*" * "/" /})

(defn push-token [stack token]
  (cond
    (int? token)
    (conj stack token)

    (not (contains? operators token))
    (throw (ex-info (str "Unknown token: " token) {:token token}))

    (< (count stack) 2)
    (throw (ex-info (str "Not enough numbers for " token) {:token token :stack stack}))

    :else
    (let [[b a & more] stack]
      (conj more ((get operators token) a b)))))

(defn evaluate [line]
  (let [stack (reduce push-token '() (tokenize line))]
    (if (= 1 (count stack))
      (first stack)
      (throw (ex-info "Expected exactly one result" {:stack stack})))))

(defn calc [line]
  (try
    (str line " = " (evaluate line))
    (catch \DivisionByZeroError e
      (str line " -> error: division by zero"))
    (catch \Exception e
      (str line " -> error: " (.getMessage e)))))

(foreach [line ["3 4 +" "5 1 2 + 4 * + 3 -" "7 2 /" "1 0 /" "2 +" "1 2" "2 3 ^"]]
  (println (calc line)))
;; 3 4 + = 7
;; 5 1 2 + 4 * + 3 - = 14
;; 7 2 / = 7/2
;; 1 0 / -> error: division by zero
;; 2 + -> error: Not enough numbers for +
;; 1 2 -> error: Expected exactly one result
;; 2 3 ^ -> error: Unknown token: ^
```
`DivisionByZeroError` is a PHP `Error`, not an `Exception`, so the `\Exception` clause would not catch it; it needs its own clause. In PHP you would write the same thing as `catch (DivisionByZeroError $e)`.
{% end %}

## Conway's Game of Life

You build a small simulation. The board is a grid of cells, each alive or dead. On every tick, a live cell with 2 or 3 live neighbors survives, a dead cell with exactly 3 comes to life, and every other cell dies or stays dead. It draws on sets, `for` comprehensions, `frequencies`, `iterate`, and laziness.

{% question(difficulty="easy", kind="build") %}
A cell is a `[x y]` vector. Write `neighbors`, which returns the 8 cells around a cell.

<!-- phel-test: skip -->
```phel
(neighbors [0 0])
; => [[-1 -1] [-1 0] [-1 1] [0 -1] [0 1] [1 -1] [1 0] [1 1]]
(count (neighbors [5 5])) ; => 8
```
{% end %}
{% hint() %}
Two `for` bindings over `[-1 0 1]` give 9 offsets. Skip `[0 0]` with `:when`.
{% end %}
{% solution() %}
```phel
(defn neighbors [[x y]]
  (for [dx :in [-1 0 1]
        dy :in [-1 0 1]
        :when (not (and (= dx 0) (= dy 0)))]
    [(+ x dx) (+ y dy)]))

(neighbors [0 0])
; => [[-1 -1] [-1 0] [-1 1] [0 -1] [0 1] [1 -1] [1 0] [1 1]]
(count (neighbors [5 5])) ; => 8
```
Destructuring `[x y]` in the parameter list keeps the body short. The board has no edges: negative coordinates are fine.
{% end %}

{% question(difficulty="hard", kind="build") %}
The board is a set of live cells. Write `step`, which returns the next board. A "blinker" (three cells in a row) flips between horizontal and vertical:

<!-- phel-test: skip -->
```phel
(def blinker #{[0 1] [1 1] [2 1]})

(= (step blinker) #{[1 0] [1 1] [1 2]}) ; => true
(= blinker (step (step blinker)))      ; => true
```
{% end %}
{% hint() %}
Only cells next to a live cell can be alive next tick. `(mapcat neighbors board)` lists every neighbor of every live cell, and `frequencies` then tells you how many live neighbors each of those cells has.
{% end %}
{% solution() %}
```phel
(defn neighbors [[x y]]
  (for [dx :in [-1 0 1]
        dy :in [-1 0 1]
        :when (not (and (= dx 0) (= dy 0)))]
    [(+ x dx) (+ y dy)]))

(defn step [board]
  (into #{} (for [[cell n] :pairs (frequencies (mapcat neighbors board))
                  :when (or (= n 3)
                            (and (= n 2) (contains? board cell)))]
              cell)))

(def blinker #{[0 1] [1 1] [2 1]})

(= (step blinker) #{[1 0] [1 1] [1 2]}) ; => true
(= blinker (step (step blinker)))      ; => true
```
This is the whole rule set in four lines. The common first attempt loops over every cell of a fixed grid and counts neighbors one by one; storing only live cells in a set makes the board infinite and the code shorter.
{% end %}

{% question(difficulty="medium", kind="build") %}
Add input and output. Write `parse-board`, which reads a board from strings (`#` is alive, `.` is dead), and `render`, which turns a board back into a vector of strings for a given width and height.

<!-- phel-test: skip -->
```phel
(parse-board ["..." "###" "..."]) ; => #{[0 1] [1 1] [2 1]}

(render (step (parse-board ["..." "###" "..."])) 3 3)
; => [".#." ".#." ".#."]
```
{% end %}
{% hint() %}
`php/str_split` turns a string into an array of characters. `for` with `:pairs` over a vector gives `[index value]`, which is `[y row]` for rows and `[x char]` for characters.
{% end %}
{% solution() %}
```phel
(defn neighbors [[x y]]
  (for [dx :in [-1 0 1]
        dy :in [-1 0 1]
        :when (not (and (= dx 0) (= dy 0)))]
    [(+ x dx) (+ y dy)]))

(defn step [board]
  (into #{} (for [[cell n] :pairs (frequencies (mapcat neighbors board))
                  :when (or (= n 3)
                            (and (= n 2) (contains? board cell)))]
              cell)))

(defn parse-board [rows]
  (into #{} (for [[y row] :pairs rows
                  [x ch] :pairs (php/str_split row)
                  :when (= ch "#")]
              [x y])))

(defn render [board width height]
  (for [y :range [0 height]]
    (apply str (for [x :range [0 width]]
                 (if (contains? board [x y]) "#" ".")))))

(parse-board ["..." "###" "..."]) ; => #{[0 1] [1 1] [2 1]}

(render (step (parse-board ["..." "###" "..."])) 3 3)
; => [".#." ".#." ".#."]
```
`parse-board` and `render` are opposites, so `(render (parse-board rows) w h)` gives back `rows`. That makes a quick check that both are right.
{% end %}

{% question(difficulty="hard", kind="build") %}
Run the simulation. Write `run-life`, which takes the starting rows and a number of generations and prints each generation. Try it with a "glider", a shape that moves one cell diagonally every 4 generations.

<!-- phel-test: skip -->
```phel
(run-life [".#...."
           "..#..."
           "###..."
           "......"
           "......"] 3)
;; Generation 0:
;; .#....
;; ..#...
;; ###...
;; ...
```
{% end %}
{% hint() %}
`(iterate step board)` is the infinite, lazy sequence of all future boards. `take` the ones you need, then print them.
{% end %}
{% solution() %}
```phel
(defn neighbors [[x y]]
  (for [dx :in [-1 0 1]
        dy :in [-1 0 1]
        :when (not (and (= dx 0) (= dy 0)))]
    [(+ x dx) (+ y dy)]))

(defn step [board]
  (into #{} (for [[cell n] :pairs (frequencies (mapcat neighbors board))
                  :when (or (= n 3)
                            (and (= n 2) (contains? board cell)))]
              cell)))

(defn parse-board [rows]
  (into #{} (for [[y row] :pairs rows
                  [x ch] :pairs (php/str_split row)
                  :when (= ch "#")]
              [x y])))

(defn render [board width height]
  (for [y :range [0 height]]
    (apply str (for [x :range [0 width]]
                 (if (contains? board [x y]) "#" ".")))))

(defn run-life [rows generations]
  (let [width (php/strlen (first rows))
        height (count rows)
        boards (take generations (iterate step (parse-board rows)))]
    (foreach [i board (into [] boards)]
      (println (str "Generation " i ":"))
      (foreach [line (render board width height)]
        (println line)))))

(run-life [".#...."
           "..#..."
           "###..."
           "......"
           "......"] 3)
;; Generation 0:
;; .#....
;; ..#...
;; ###...
;; ......
;; ......
;; Generation 1:
;; ......
;; #.#...
;; .##...
;; .#....
;; ......
;; Generation 2:
;; ......
;; ..#...
;; #.#...
;; .##...
;; ......
```
`iterate` describes every future board, but laziness means only the boards you `take` are computed. The rules live in `step`, a pure function; `run-life` only prints. Try a larger board and more generations to watch the glider travel.
{% end %}
