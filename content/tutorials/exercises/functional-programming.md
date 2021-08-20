+++
title = "Functional Programming?"
weight = 3
+++

{% question() %}
Increment all the numbers in the vector `[4 7 9 10]` by one.
Use the `map` function. Hint: the function `inc`.
{% end %}
{% solution() %}
```phel
(map (fn [x] (inc x)) [4 7 9 10])

# or using the shorter form to define an anonymous function:
(map |(inc $) [4 7 9 10])

# or simply:
(map inc [4 7 9 10])
```
{% end %}

{% question() %}
Do the same as in the previous exercise, but leave only the even results in the vector.
Use the functions `filter` and `even?`
{% end %}
{% solution() %}
```phel
(filter even? (map inc [4 7 9 10]))
```
{% end %}

{% question() %}
Use the for structure to go through this vector of maps
and return a sequence of the `:values`, aka this `[10.3 20.06 30.1]`
```phel
[{:id 1 :value 10.3} {:id 2 :value 20.06} {:id 7 :value 30.1}]
```
{% end %}
{% solution() %}
```phel
(def data [{:id 1 :value 10.3} {:id 2 :value 20.06} {:id 7 :value 30.1}])
(for [m :in data]
  (m :value))
```
{% end %}

{% question() %}
Use the function `update-in` to change 3 into 4 in the value below:
```phel
{:shops [:shop-1]
         :customers [{:id "Bob"
                      :account {:balance 3}}]}
```
{% end %}
{% solution() %}
```phel
(def data {:shops [:shop-1]
           :customers [{:id "Bob"
                        :account {:balance 3}}]})
(update-in data [:customers 0 :account :balance] inc)
```
{% end %}

{% question() %}
Create a function that combine a vector of maps like this:
```
(combine [{:a 1 :b 2} {:c 3} {:d 4 :e 5}])
==> {:a 1 :b 2 :c 3 :d 4 :e 5}
```
{% end %}
{% solution() %}
```phel
(defn combine [maps]
  (apply merge maps))
```
{% end %}
