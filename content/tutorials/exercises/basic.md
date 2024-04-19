+++
title = "Basic syntax"
weight = 1
+++

{% question() %}
Compute `1 + 1`
{% end %}
{% solution() %}
```phel
(+ 1 1)
```
{% end %}

{% question() %}
Call the function `get` with arguments `"hello"` and `1`
{% end %}
{% solution() %}
```phel
(get "hello" 1)
```
{% end %}

{% question() %}
Compute `(3 + 4 / 5) * 6`
{% end %}
{% solution() %}
```phel
(* (+ 3 (/ 4 5)) 6)
```
{% end %}

{% question() %}
Define a vector with the elements `2`, `"nice"` and `true`
{% end %}
{% solution() %}
```phel
[2 "nice" true]
# or
(vector 2 "nice" true)
```
{% end %}

{% question() %}
Define a vector that contains the keywords `:key` and `:word`
{% end %}
{% solution() %}
```phel
[:key :word]
# or
(vector :key :word)
```
{% end %}

{% question() %}
Define a map with the key `:name` associated with the value `"Frederick"`
{% end %}
{% solution() %}
```phel
{:name "Frederick"}
# or
(hash-map :name "Frederick")
```
{% end %}

{% question() %}
Use `def` to define a variable `my-map` that refers to the map `{:1 2}`.
Use the `put` function to add a new key and value to `my-map`.
- What does the `put` call return?
- What is the value of `my-map` after the call?
{% end %}
{% solution() %}
```phel
(def my-map {:1 2})
(put my-map :3 4)
(def my-new-map (put my-map :5 6))
```
{% end %}

{% question() %}
Use `push` to add a value to a vector
{% end %}
{% solution() %}
```phel
(def my-vector [1 2])
(def new-vector (push my-vector 3))
```
{% end %}

{% question() %}
Use the function `get` to get the second element from a vector
{% end %}
{% solution() %}
```phel
(def my-vector [1 2])
(get my-vector 1)
```
{% end %}

{% question() %}
Use the function `get` to get the value of a key from a map
{% end %}
{% solution() %}
```phel
(def my-map {:k1 "v1" :k2 "v2"})
(get my-map :k2)
```
{% end %}

{% question() %}
Get the value of a key from a map using the map itself as a function
{% end %}
{% solution() %}
```phel
(def my-map {:k1 "v1" :k2 "v2"})
(my-map :k2)
```
{% end %}

{% question() %}
Get the value of a key from a map using a keyword as a function
{% end %}
{% solution() %}
```phel
(def my-map {:k1 "v1" :k2 "v2"})
(:k2 my-map)
```
{% end %}

{% question() %}
Use the function `get-in` to return the value `:treasure` from the map:
 ```phel
(def my-map {:description "cave"
             :crossroads [{:contents :monster}
                          nil
                          {:contents [:trinket :treasure]}]})
 ```
{% end %}
{% solution() %}
```phel
(get-in my-map [:crossroads 2 :contents 1])
```
{% end %}

{% question() %}
Use `defn` to define a function hello that works like this: 
`(hello) ==> "hello!"`
{% end %}
{% solution() %}
```phel
(defn hello [] "hello!")
```
{% end %}

{% question() %}
Define a function double that works like this: `(double 5) ==> 10`
{% end %}
{% solution() %}
```phel
(defn double [n] (* n 2))
```
{% end %}

{% question() %}
Add a docstring to the function double. Then show it using `(doc double)`
{% end %}
{% solution() %}
```phel
(defn double
  "It doubles the received number."
  [n]
  (* n 2))
```
{% end %}

{% question() %}
Implement a `factorial` function using recursion.
{% end %}
{% solution() %}
```phel
(defn factorial
  "Calculate the factorial number for n." 
  [n]
  (if (<= n 1)
    n
    (* n (factorial (dec n)))))
```
{% end %}
