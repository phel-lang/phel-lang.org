+++
title = "Shaping Data"
weight = 7
description = "Pull values out of vectors and maps with destructuring, then reshape a list of orders with merge, into, mapcat, and pipelines."

[extra]
stage = "Functional core"
goals = [
  "Destructure vectors and maps in `let` and in function parameters",
  "Give missing keys a default with `:or`",
  "Model a small domain as plain maps and vectors",
  "Build and combine maps with `merge`, `select-keys`, `zipmap`, and `into`",
  "Flatten and chunk data with `mapcat` and `partition`",
]
read_first = [
  ["Destructuring", "/documentation/language/destructuring/"],
  ["Data Structures", "/documentation/language/data-structures/#working-with-collections"],
]
recap = [
  "You can bind names to parts of a vector or map by describing its shape",
  "You can write functions that take a map and name only the keys they need",
  "You can build, merge, and trim maps without touching the original",
  "You can turn a vector of nested maps into a report with one readable pipeline",
]
+++

Real programs spend most of their time moving data from one shape to another. In Phel that data is plain maps and vectors, not classes. This module gives you two tools: destructuring, to read the parts you need, and a handful of functions to build new shapes. Most exercises use the same small list of shop orders, so by the end you will have written a small sales report.

## The orders

Every order is a map. Its line items are a vector of maps. This is the whole dataset:

```phel
(def orders
  [{:id 1 :customer "Ada" :status :paid
    :items [{:sku "pen" :qty 3 :price 2} {:sku "book" :qty 1 :price 15}]}
   {:id 2 :customer "Linus" :status :open
    :items [{:sku "mug" :qty 2 :price 8}]}
   {:id 3 :customer "Ada" :status :paid
    :items [{:sku "book" :qty 2 :price 15}]}
   {:id 4 :customer "Grace" :status :paid
    :items [{:sku "pen" :qty 10 :price 2} {:sku "mug" :qty 1 :price 8}]}])
```

In PHP you would reach for an array of associative arrays, or a set of classes. Here the maps are the model: no getters, no setters, and every function in the core library works on them.

## Destructuring vectors

A vector in a binding position takes values by position. `&` collects the rest.

```phel
(let [[a b] [1 2 3]]
  (+ a b)) ; => 3
```

{% question(difficulty="easy", kind="predict") %}
What do these two expressions return?
```phel
(let [[x y] [3 4 5]]
  (* x y))

(let [[head & others] [3 4 5]]
  others)
```
{% end %}
{% solution() %}
```phel
(let [[x y] [3 4 5]]
  (* x y)) ; => 12

(let [[head & others] [3 4 5]]
  others) ; => [4 5]
```
Extra elements are ignored, so `5` never gets a name in the first form. In the second, `&` binds everything after `head`.
{% end %}

{% question(difficulty="easy", kind="fill") %}
Fill the blank so `swap-pair` takes a two-element vector and returns it reversed. Destructure the parameter directly.
<!-- phel-test: skip -->
```phel
(defn swap-pair [___]
  [b a])

(swap-pair [:x :y]) ; => [:y :x]
```
{% end %}
{% solution() %}
```phel
(defn swap-pair [[a b]]
  [b a])

(swap-pair [:x :y]) ; => [:y :x]
```
A pattern can sit anywhere a parameter name can. The function still takes one argument; the pattern names its parts.
{% end %}

## Destructuring maps

A map pattern reads values by key. Put the new name first, then the key: `{c :customer}` binds `c` to the value at `:customer`. When the names match the keys, `:keys` is shorter.

```phel
(let [{c :customer} {:customer "Ada"}]
  c) ; => "Ada"

(let [{:keys [customer status]} {:customer "Ada" :status :paid}]
  [customer status]) ; => ["Ada" :paid]
```

{% question(difficulty="easy", kind="predict") %}
What does this return?
```phel
(let [{who :customer n :id} {:id 1 :customer "Ada" :status :paid}]
  (str who " placed order " n))
```
{% end %}
{% solution() %}
```phel
(let [{who :customer n :id} {:id 1 :customer "Ada" :status :paid}]
  (str who " placed order " n)) ; => "Ada placed order 1"
```
Each pair reads "bind `who` to the value at `:customer`". Keys you do not mention, like `:status`, are ignored.
{% end %}

{% question(difficulty="easy", kind="fix") %}
`greet` should say hello to the customer of an order, but it prints an empty name. Fix it.
<!-- phel-test: skip -->
```phel
(defn greet [{:keys [name]}]
  (str "Hello, " name))

(greet {:id 1 :customer "Ada" :status :paid}) ; => "Hello, " (wrong)
```
{% end %}
{% hint() %}
`:keys [name]` looks up the key `:name`. Does the order have one?
{% end %}
{% solution() %}
```phel
(defn greet [{:keys [customer]}]
  (str "Hello, " customer))

(greet {:id 1 :customer "Ada" :status :paid}) ; => "Hello, Ada"
```
A missing key binds `nil`, and `(str "Hello, " nil)` is `"Hello, "`. There is no error, so check key names first when a destructured value is empty.
{% end %}

{% question(difficulty="medium", kind="write") %}
Write `line-total`, which multiplies `:qty` by `:price` of one line item, and `order-total`, which adds up the line totals of an order. Destructure the parameters.
<!-- phel-test: skip -->
```phel
(line-total {:sku "pen" :qty 3 :price 2}) ; => 6
(order-total {:id 1 :items [{:sku "pen" :qty 3 :price 2}
                            {:sku "book" :qty 1 :price 15}]}) ; => 21
```
{% end %}
{% hint() %}
`order-total` only needs `:items`. Map `line-total` over them, then `reduce` with `+`.
{% end %}
{% solution() %}
```phel
(defn line-total [{:keys [qty price]}]
  (* qty price))

(defn order-total [{:keys [items]}]
  (reduce + (map line-total items)))

(line-total {:sku "pen" :qty 3 :price 2}) ; => 6
(order-total {:id 1 :items [{:sku "pen" :qty 3 :price 2}
                            {:sku "book" :qty 1 :price 15}]}) ; => 21
```
The parameter list now documents which keys each function reads. You will reuse these two functions in the rest of the module.
{% end %}

{% question(difficulty="medium", kind="write") %}
Write `order-label`. A new order may not have a `:status` yet; treat it as `:open`. Use `:or` for the default.
<!-- phel-test: skip -->
```phel
(order-label {:id 7 :customer "Linus"})               ; => "#7 Linus (open)"
(order-label {:id 1 :customer "Ada" :status :paid})   ; => "#1 Ada (paid)"
```
{% end %}
{% hint() %}
`:or` takes a map from local name to default: `{:keys [a b] :or {b 0}}`. `(name :open)` gives `"open"` without the colon.
{% end %}
{% solution() %}
```phel
(defn order-label [{:keys [id customer status] :or {status :open}}]
  (str "#" id " " customer " (" (name status) ")"))

(order-label {:id 7 :customer "Linus"})             ; => "#7 Linus (open)"
(order-label {:id 1 :customer "Ada" :status :paid}) ; => "#1 Ada (paid)"
```
The keys of the `:or` map are the local names (`status`), not keywords. In PHP this is `$status = $order['status'] ?? 'open';`.

Learn more: [Destructuring](/documentation/language/destructuring/#defaults-with-or)
{% end %}

## Building maps

Four functions cover most map building. `merge` combines maps, and later keys win. `select-keys` keeps only some keys. `zipmap` pairs a vector of keys with a vector of values. `into` pours pairs into a map.

```phel
(merge {:a 1} {:b 2})               ; => {:a 1, :b 2}
(select-keys {:a 1 :b 2} [:a])      ; => {:a 1}
(zipmap [:a :b] [1 2])              ; => {:a 1, :b 2}
(into {} [[:a 1] [:b 2]])           ; => {:a 1, :b 2}
```

{% question(difficulty="easy", kind="predict") %}
A new order starts from defaults. What does this return?
```phel
(merge {:status :open :items []}
       {:id 5 :customer "Linus"}
       {:status :paid})
```
{% end %}
{% solution() %}
```phel
(merge {:status :open :items []}
       {:id 5 :customer "Linus"}
       {:status :paid})
; => {:status :paid, :items [], :id 5, :customer "Linus"}
```
When two maps share a key, the value from the map further right wins. This "defaults first, overrides last" pattern is common for options and config.
{% end %}

{% question(difficulty="medium", kind="fill") %}
Orders arrive from a CSV file as plain rows. Fill the blanks to turn one row into an order map.
<!-- phel-test: skip -->
```phel
(def columns [:id :customer :status])

(defn row->order [row]
  (___ ___ row))

(row->order [5 "Linus" :open]) ; => {:id 5, :customer "Linus", :status :open}
```
{% end %}
{% hint() %}
You have one vector of keys and one vector of values, in the same order.
{% end %}
{% solution() %}
```phel
(def columns [:id :customer :status])

(defn row->order [row]
  (zipmap columns row))

(row->order [5 "Linus" :open]) ; => {:id 5, :customer "Linus", :status :open}
```
`zipmap` walks both vectors together. It is the Phel version of PHP's `array_combine($columns, $row)`.
{% end %}

{% question(difficulty="medium", kind="write") %}
Write `order-summary`. It keeps only `:id` and `:customer` from an order and adds a `:total` key. Reuse `order-total` from earlier.
<!-- phel-test: skip -->
```phel
(order-summary {:id 1 :customer "Ada" :status :paid
                :items [{:sku "pen" :qty 3 :price 2}
                        {:sku "book" :qty 1 :price 15}]})
; => {:id 1, :customer "Ada", :total 21}
```
{% end %}
{% hint() %}
Trim first with `select-keys`, then add the new key with `assoc`.
{% end %}
{% solution() %}
```phel
(defn line-total [{:keys [qty price]}]
  (* qty price))

(defn order-total [{:keys [items]}]
  (reduce + (map line-total items)))

(defn order-summary [order]
  (assoc (select-keys order [:id :customer])
         :total (order-total order)))

(order-summary {:id 1 :customer "Ada" :status :paid
                :items [{:sku "pen" :qty 3 :price 2}
                        {:sku "book" :qty 1 :price 15}]})
; => {:id 1, :customer "Ada", :total 21}
```
The original order is unchanged. `order-summary` builds a new, smaller map for a different reader, like a list view.
{% end %}

{% question(difficulty="medium", kind="write") %}
Looking up an order by id in a vector means scanning it every time. Write `index-by-id`, which turns the vector of orders into a map from id to order.
<!-- phel-test: skip -->
```phel
(:customer (get (index-by-id orders) 3)) ; => "Ada"
(keys (index-by-id orders))              ; => [1 2 3 4]
```
{% end %}
{% hint() %}
Turn each order into a pair `[id order]`, then pour the pairs into `{}` with `into`.
{% end %}
{% solution() %}
```phel
(def orders
  [{:id 1 :customer "Ada" :status :paid
    :items [{:sku "pen" :qty 3 :price 2} {:sku "book" :qty 1 :price 15}]}
   {:id 2 :customer "Linus" :status :open
    :items [{:sku "mug" :qty 2 :price 8}]}
   {:id 3 :customer "Ada" :status :paid
    :items [{:sku "book" :qty 2 :price 15}]}
   {:id 4 :customer "Grace" :status :paid
    :items [{:sku "pen" :qty 10 :price 2} {:sku "mug" :qty 1 :price 8}]}])

(defn index-by-id [orders]
  (into {} (map (fn [order] [(:id order) order]) orders)))

(:customer (get (index-by-id orders) 3)) ; => "Ada"
(keys (index-by-id orders))              ; => [1 2 3 4]
```
`into` adds each pair to the empty map. `group-by :id` looks close, but it returns a vector of orders per id, not one order.
{% end %}

## Flattening and chunking

`mapcat` maps a function that returns a collection, then joins the results into one sequence. `partition` cuts a sequence into chunks of a fixed size.

```phel
(mapcat (fn [x] [x x]) [1 2])  ; => (1 1 2 2)
(partition 2 [1 2 3 4])        ; => ([1 2] [3 4])
```

{% question(difficulty="medium", kind="predict") %}
Which products appear in the orders? Predict the result. (`orders` is the dataset from the top of this page.)
<!-- phel-test: skip -->
```phel
(->> orders
     (mapcat :items)
     (map :sku)
     distinct)
```
{% end %}
{% solution() %}
```phel
(def orders
  [{:id 1 :customer "Ada" :status :paid
    :items [{:sku "pen" :qty 3 :price 2} {:sku "book" :qty 1 :price 15}]}
   {:id 2 :customer "Linus" :status :open
    :items [{:sku "mug" :qty 2 :price 8}]}
   {:id 3 :customer "Ada" :status :paid
    :items [{:sku "book" :qty 2 :price 15}]}
   {:id 4 :customer "Grace" :status :paid
    :items [{:sku "pen" :qty 10 :price 2} {:sku "mug" :qty 1 :price 8}]}])

(->> orders
     (mapcat :items)
     (map :sku)
     distinct)
; => ("pen" "book" "mug")
```
`(map :items orders)` would give a sequence of four vectors. `mapcat` flattens them into one sequence of six line items, so the next step sees items, not orders.
{% end %}

{% question(difficulty="medium", kind="predict") %}
You want to show order ids two per page. What do these return, and which one is right for paging?
```phel
(partition 2 [1 2 3 4 5])
(partition-all 2 [1 2 3 4 5])
```
{% end %}
{% hint() %}
Five ids do not split evenly into pairs. What happens to the last one?
{% end %}
{% solution() %}
```phel
(partition 2 [1 2 3 4 5])     ; => ([1 2] [3 4])
(partition-all 2 [1 2 3 4 5]) ; => ([1 2] [3 4] [5])
```
`partition` drops a last chunk that is too short, so order 5 would never be shown. Use `partition-all` for paging, and `partition` when every chunk must be full, like pairs of coordinates.
{% end %}

## Pipelines

Now combine everything. A report is a pipeline: filter the rows you care about, group or flatten them, then build the result map. Keep each step small and name the helpers.

{% question(difficulty="hard", kind="write") %}
Write `revenue-by-customer`. Count only `:paid` orders. Return a map from customer name to the sum of their order totals. Reuse `order-total`.
<!-- phel-test: skip -->
```phel
(revenue-by-customer orders) ; => {"Ada" 51, "Grace" 28}
```
{% end %}
{% hint() %}
Four steps: `filter` the paid orders, `group-by :customer`, turn each `[customer customer-orders]` entry into `[customer total]`, then `into {}`. Destructure the entry in the `fn` parameters.
{% end %}
{% solution() %}
```phel
(def orders
  [{:id 1 :customer "Ada" :status :paid
    :items [{:sku "pen" :qty 3 :price 2} {:sku "book" :qty 1 :price 15}]}
   {:id 2 :customer "Linus" :status :open
    :items [{:sku "mug" :qty 2 :price 8}]}
   {:id 3 :customer "Ada" :status :paid
    :items [{:sku "book" :qty 2 :price 15}]}
   {:id 4 :customer "Grace" :status :paid
    :items [{:sku "pen" :qty 10 :price 2} {:sku "mug" :qty 1 :price 8}]}])

(defn line-total [{:keys [qty price]}]
  (* qty price))

(defn order-total [{:keys [items]}]
  (reduce + (map line-total items)))

(defn revenue-by-customer [orders]
  (->> orders
       (filter #(= :paid (:status %)))
       (group-by :customer)
       (map (fn [[customer customer-orders]]
              [customer (reduce + (map order-total customer-orders))]))
       (into {})))

(revenue-by-customer orders) ; => {"Ada" 51, "Grace" 28}
```
Mapping over a map gives you `[key value]` entries, and `[customer customer-orders]` destructures each one. Linus is missing because his only order is still open.
{% end %}

{% question(difficulty="hard", kind="refactor") %}
This function counts how many units of each product were ordered. It works, but you have to read it from the inside out. Rewrite it as a `->>` pipeline with `mapcat` and destructuring.
```phel
(defn units-by-sku [orders]
  (reduce (fn [acc item]
            (assoc acc (:sku item) (+ (get acc (:sku item) 0) (:qty item))))
          {}
          (reduce (fn [acc order] (into acc (:items order))) [] orders)))
```
Expected, with the dataset from the top of the page:
<!-- phel-test: skip -->
```phel
(units-by-sku orders) ; => {"pen" 13, "book" 3, "mug" 3}
```
{% end %}
{% hint() %}
The inner `reduce` flattens the line items: that is one `mapcat`. In the outer `reduce`, destructure `sku` and `qty` from each item. `(get acc sku 0)` returns `0` when the key is missing.
{% end %}
{% solution() %}
```phel
(def orders
  [{:id 1 :customer "Ada" :status :paid
    :items [{:sku "pen" :qty 3 :price 2} {:sku "book" :qty 1 :price 15}]}
   {:id 2 :customer "Linus" :status :open
    :items [{:sku "mug" :qty 2 :price 8}]}
   {:id 3 :customer "Ada" :status :paid
    :items [{:sku "book" :qty 2 :price 15}]}
   {:id 4 :customer "Grace" :status :paid
    :items [{:sku "pen" :qty 10 :price 2} {:sku "mug" :qty 1 :price 8}]}])

(defn units-by-sku [orders]
  (->> orders
       (mapcat :items)
       (reduce (fn [acc {:keys [sku qty]}]
                 (assoc acc sku (+ (get acc sku 0) qty)))
               {})))

(units-by-sku orders) ; => {"pen" 13, "book" 3, "mug" 3}
```
The pipeline reads top to bottom: all line items, then a running count per product. `frequencies` looks tempting, but it counts rows, not quantities, so it would say `"pen"` appears 2 times instead of 13 units.
{% end %}
