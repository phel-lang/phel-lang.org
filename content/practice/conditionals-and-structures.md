+++
title = "Conditionals & Structures"
weight = 2
+++

{% question() %}
Use the `let` structure inside a function `f1` to define a local variable `b` with
the value `"funcy"`. Then use the `str` function to combine two `b`s into `"funcyfuncy"`.
{% end %}
{% solution() %}
```phel
(defn f1 []
  (let [b "funcy"]
    (str b b)))
(f1)
```
{% end %}

{% question() %}
Define a function `small?` that returns `true` for numbers under 100.
{% end %}
{% solution() %}
```phel
(defn small? [n] (< n 100))
(small? 99)  # true
(small? 100) # false
```
{% end %}

{% question() %}
Define a function `message` that has three cases:
```phel
(message :boink) # -> "Boink!"
(message :pig)   # -> "Oink!"
(message :ping)  # -> "Pong"
```
{% end %}
{% solution() %}
```phel
(defn message [k]
  (let [m {:boink "Boink!"
           :pig "Oink!"
           :ping "Pong"}]
    (get m k)))
```
{% end %}

{% question() %}
Reimplement `message` using the `if` structure.
{% end %}
{% solution() %}
```phel
(defn message [k]
  (if (= k :boink)
    "Boink!"
    (if (= k :pig)
      "Oink!"
      (if (= k :ping)
        "Pong!"))))
```
{% end %}

{% question() %}
Reimplement `message` using the `cond` structure.
{% end %}
{% solution() %}
```phel
(defn message [k]
  (cond
    (= k :boink) "Boink!"
    (= k :pig) "Oink!"
    (= k :ping) "Pong!"))
```
{% end %}

{% question() %}
Reimplement `message` using the `case` structure.
{% end %}
{% solution() %}
```phel
(defn message [k]
  (case k
    :boink "Boink!"
    :pig "Oink!"
    :ping "Pong!"))
```
{% end %}

{% question() %}
Use the `loop` structure to add `1` to an empty vector until it has 10 elements.
{% end %}
{% solution() %}
```phel
(loop [v []]
  (if (= (count v) 10)
       v
       (recur (push v 1))))
```
{% end %}
