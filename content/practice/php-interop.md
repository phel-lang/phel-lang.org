+++
title = "PHP Interop"
weight = 9
description = "Use the PHP functions and classes you already know from Phel, and move data safely between PHP arrays and Phel collections."

[extra]
stage = "Real world"
goals = [
  "Call any PHP function with `php/` and pass it around as a value",
  "Create objects with `ClassName.`, call methods with `.method`, and read properties with `.-field`",
  "Use static methods and constants with `ClassName/member`",
  "Convert between PHP arrays and Phel collections with `to-array`, `phel->php`, and `php->phel`",
  "Catch PHP exceptions and turn them into data",
]
read_first = [
  ["PHP Interop", "/documentation/language/php-interop/"],
]
recap = [
  "You can call PHP functions, constants, and classes from Phel without a wrapper",
  "You can chain method calls on PHP objects with `->`",
  "You know that Phel vectors and maps are not PHP arrays, and you convert at the boundary",
  "You can encode and decode JSON through PHP and get Phel data back",
  "You can catch a PHP exception and return an error as a map",
]
+++

Phel compiles to PHP, so everything you know from PHP is still there: `str_pad`, `DateTimeImmutable`, `json_encode`, Composer packages. This module shows how to call that code from Phel. The one thing to watch is the border between the two worlds: PHP arrays are mutable, Phel collections are not, and you convert when data crosses.

## Calling PHP functions

Put `php/` in front of any PHP function name. PHP constants use the same prefix. In PHP you would write `str_pad("7", 3, "0", STR_PAD_LEFT)`:

```phel
(php/str_pad "7" 3 "0" php/STR_PAD_LEFT) ; => "007"
php/PHP_INT_MAX                          ; => 9223372036854775807
```

{% question(difficulty="easy", kind="predict") %}
What does each line return?
```phel
(php/strtoupper "phel")
(php/str_repeat "ab" 3)
(php/max 3 7 2)
```
{% end %}
{% solution() %}
```phel
(php/strtoupper "phel")  ; => "PHEL"
(php/str_repeat "ab" 3)  ; => "ababab"
(php/max 3 7 2)          ; => 7
```
Same functions, same arguments, same results as in PHP. Only the call moves inside the parentheses.
{% end %}

{% question(difficulty="easy", kind="fill") %}
A `php/` function is a value, like any Phel function. Fill in the blank so each name starts with a capital letter:
<!-- phel-test: skip -->
```phel
(map ___ ["ada" "grace"]) ; => ("Ada" "Grace")
```
{% end %}
{% hint() %}
PHP has a function that uppercases the first character of a string.
{% end %}
{% solution() %}
```phel
(map php/ucfirst ["ada" "grace"]) ; => ("Ada" "Grace")
```
You pass `php/ucfirst` to `map` the same way you pass `inc` or `str`. No `fn` wrapper needed.

Learn more: [PHP functions as values](/documentation/language/php-interop/#php-first-class-callable)
{% end %}

## Objects and methods

Add a dot after a class name to create an object: `(DateTimeImmutable. "2024-03-10")` is `new DateTimeImmutable("2024-03-10")`. Call a method with `.method`, and chain calls with `->`:

```phel
;; (new DateTimeImmutable("2024-03-10"))->modify("+1 day")->format("Y-m-d")
(-> (DateTimeImmutable. "2024-03-10")
    (.modify "+1 day")
    (.format "Y-m-d")) ; => "2024-03-11"
```

{% question(difficulty="easy", kind="predict") %}
`DateTimeImmutable` never changes. `.modify` returns a new object. What does this return?
```phel
(let [start (DateTimeImmutable. "2024-03-10")
      later (.modify start "+1 day")]
  [(.format start "Y-m-d") (.format later "Y-m-d")])
```
{% end %}
{% solution() %}
```phel
(let [start (DateTimeImmutable. "2024-03-10")
      later (.modify start "+1 day")]
  [(.format start "Y-m-d") (.format later "Y-m-d")])
; => ["2024-03-10" "2024-03-11"]
```
`start` keeps its value, like a Phel vector after `conj`. That is why `DateTimeImmutable` feels natural in Phel.
{% end %}

{% question(difficulty="medium", kind="write") %}
Write `add-days`. It takes a date string in `Y-m-d` format and a number of days, and returns the new date as a `Y-m-d` string.
<!-- phel-test: skip -->
```phel
(add-days "2024-03-10" 1) ; => "2024-03-11"
(add-days "2024-02-28" 2) ; => "2024-03-01"
```
{% end %}
{% hint() %}
`.modify` accepts strings such as `"+2 days"`. Build that string with `str`, then chain with `->`.
{% end %}
{% solution() %}
```phel
(defn add-days [date n]
  (-> (DateTimeImmutable. date)
      (.modify (str "+" n " days"))
      (.format "Y-m-d")))

(add-days "2024-03-10" 1) ; => "2024-03-11"
(add-days "2024-02-28" 2) ; => "2024-03-01"
```
PHP handles the calendar (2024 is a leap year). Phel only builds the string and chains the calls.
{% end %}

{% question(difficulty="medium", kind="fix") %}
`due-date` should not touch the date it receives. But after the call, `created` has moved too. Fix it.
<!-- phel-test: skip -->
```phel
(defn due-date [start]
  (.modify start "+30 days"))

(let [created (DateTime. "2024-01-01")
      due     (due-date created)]
  [(.format created "Y-m-d") (.format due "Y-m-d")])
; => ["2024-01-31" "2024-01-31"], expected ["2024-01-01" "2024-01-31"]
```
{% end %}
{% hint() %}
The bug is not in `due-date`. Look at the class.
{% end %}
{% solution() %}
```phel
(defn due-date [start]
  (.modify start "+30 days"))

(let [created (DateTimeImmutable. "2024-01-01")
      due     (due-date created)]
  [(.format created "Y-m-d") (.format due "Y-m-d")])
; => ["2024-01-01" "2024-01-31"]
```
`DateTime` is mutable: `.modify` changes the object in place and returns it, so both names point to the same object. Prefer `DateTimeImmutable` in Phel code, for the same reason Phel collections are immutable.
{% end %}

{% question(difficulty="medium", kind="write") %}
`.diff` returns a `DateInterval` object. Its public property `days` holds the number of days. Read a property with `.-field`. Write `days-between`:
<!-- phel-test: skip -->
```phel
(days-between "2024-01-01" "2024-03-01") ; => 60
```
{% end %}
{% hint() %}
`(.-days interval)` is `$interval->days` in PHP.
{% end %}
{% solution() %}
```phel
(defn days-between [from to]
  (.-days (.diff (DateTimeImmutable. from) (DateTimeImmutable. to))))

(days-between "2024-01-01" "2024-03-01") ; => 60
```
`.diff` is a method call, `.-days` is a property read. The dash tells them apart.
{% end %}

## Static methods and class constants

`ClassName/member` calls a static method or reads a class constant, like `ClassName::member` in PHP:

```phel
DateTimeImmutable/ATOM ; => "Y-m-d\\TH:i:sP"
```

{% question(difficulty="medium", kind="fill") %}
Parse a European date with the static method `createFromFormat` and return it as `Y-m-d`. Fill in the blanks:
<!-- phel-test: skip -->
```phel
(-> (DateTimeImmutable/___ "d/m/Y" "22/03/2020")
    (___ "Y-m-d"))
; => "2020-03-22"
```
{% end %}
{% hint() %}
The first blank is the static method name. The second is the method that turns a date into a string.
{% end %}
{% solution() %}
```phel
(-> (DateTimeImmutable/createFromFormat "d/m/Y" "22/03/2020")
    (.format "Y-m-d"))
; => "2020-03-22"
```
In PHP: `DateTimeImmutable::createFromFormat("d/m/Y", "22/03/2020")->format("Y-m-d")`.
{% end %}

## PHP arrays and Phel collections

Numbers, strings, booleans, and `nil` cross between PHP and Phel unchanged. Collections do not. A Phel vector is not a PHP array, so convert at the border:

- `to-array`: a Phel vector or map to a PHP array (one level).
- `phel->php`: Phel data to PHP arrays, nested.
- `php->phel`: PHP arrays to Phel vectors and maps, nested.

```phel
(php/implode ", " (to-array ["a" "b"])) ; => "a, b"
(php->phel (php/explode "," "a,b,c"))    ; => ["a" "b" "c"]
```

{% question(difficulty="medium", kind="predict") %}
`php/explode` returns a PHP array. What do these return?
```phel
(vector? (php/explode "," "red,green"))
(vector? (php->phel (php/explode "," "red,green")))
(count (php/explode "," "red,green"))
```
{% end %}
{% hint() %}
Core functions such as `count` and `map` can read a PHP array. That does not make it a vector.
{% end %}
{% solution() %}
```phel
(vector? (php/explode "," "red,green"))             ; => false
(vector? (php->phel (php/explode "," "red,green"))) ; => true
(count (php/explode "," "red,green"))               ; => 2
```
A PHP array prints as `<PHP-Array ["red", "green"]>`. Convert it with `php->phel` as soon as it enters your code, so the rest of your functions work with Phel data.
{% end %}

{% question(difficulty="medium", kind="fix") %}
This fails with `implode(): Argument #2 ($array) must be of type ?array, Phel\Lang\Collections\Vector\PersistentVector given`. Fix it.
<!-- phel-test: skip -->
```phel
(defn join-tags [tags]
  (php/implode ", " tags))

(join-tags ["php" "lisp"]) ; expected "php, lisp"
```
{% end %}
{% hint() %}
The error tells you the type PHP wanted and the type it got.
{% end %}
{% solution() %}
```phel
(defn join-tags [tags]
  (php/implode ", " (to-array tags)))

(join-tags ["php" "lisp"]) ; => "php, lisp"
```
PHP functions typed `array` need a real PHP array. For this task Phel has its own tool too: `(phel.string/join ", " tags)`, but converting is the general fix for any PHP function.
{% end %}

{% question(difficulty="medium", kind="write") %}
`ArrayObject` is a mutable PHP object that wraps an array. Write `append-all`: wrap `xs` in an `ArrayObject`, `.append` every item of `ys`, then return a Phel vector.
<!-- phel-test: skip -->
```phel
(append-all [1 2] [3 4]) ; => [1 2 3 4]
```
{% end %}
{% hint() %}
`(ArrayObject. php-array)` takes a PHP array, so convert `xs` first. Use `doseq` for the side effect. `.getArrayCopy` gives you a PHP array back.
{% end %}
{% solution() %}
```phel
(defn append-all [xs ys]
  (let [box (ArrayObject. (to-array xs))]
    (doseq [y ys]
      (.append box y))
    (php->phel (.getArrayCopy box))))

(append-all [1 2] [3 4]) ; => [1 2 3 4]
```
The mutation stays inside the function. Callers give a vector and get a vector back. In real code `(into xs ys)` does this; the point here is the round trip.
{% end %}

## JSON

`php/json_encode` expects PHP arrays, so convert with `phel->php` first. For decoding, pass `true` to get arrays instead of objects, then convert with `php->phel`:

```phel
(php->phel (php/json_decode "{\"a\":1,\"b\":[1,2]}" true))
; => {"a" 1, "b" [1 2]}
```

{% question(difficulty="medium", kind="predict") %}
What does this return? Is it what you want?
```phel
(php/json_encode {:name "Ada"})
```
{% end %}
{% hint() %}
`json_encode` sees a PHP object, not an array.
{% end %}
{% solution() %}
```phel
(php/json_encode {:name "Ada"})              ; => "{}"
(php/json_encode (phel->php {:name "Ada"}))  ; => "{\"name\":\"Ada\"}"
```
A Phel map is an object with no public properties, so PHP encodes it as `{}`. No error, but the data is wrong. Convert with `phel->php`; keyword keys become strings. Phel also ships a `phel.json` module with `encode` and `decode` that do the conversion for you.
{% end %}

{% question(difficulty="hard", kind="write") %}
Write `order-total`. It takes a JSON string with a list of items and returns the sum of `price * qty`.
<!-- phel-test: skip -->
```phel
(order-total "{\"items\":[{\"price\":5,\"qty\":2},{\"price\":3,\"qty\":1}]}")
; => 13
```
{% end %}
{% hint() %}
Decode into PHP arrays, convert with `php->phel`, then use the tools from Sequences. The keys are strings, not keywords.
{% end %}
{% solution() %}
```phel
(defn order-total [json]
  (let [order (php->phel (php/json_decode json true))]
    (->> (get order "items")
         (map #(* (get % "price") (get % "qty")))
         (reduce +))))

(order-total "{\"items\":[{\"price\":5,\"qty\":2},{\"price\":3,\"qty\":1}]}")
; => 13
```
Convert once at the border, then write plain Phel. A common mistake is `(get % :price)`: JSON keys arrive as strings, so it returns `nil`.
{% end %}

## Catching PHP exceptions

PHP exceptions reach Phel unchanged. Catch them by class name with a leading backslash:

```phel
(try
  (php/intdiv 1 0)
  (catch \DivisionByZeroError e
    (.getMessage e)))
; => "Division by zero"
```

{% question(difficulty="hard", kind="write") %}
`(DateTimeImmutable. "tomorrow-ish")` throws. Write `parse-date` that never throws: it returns `{:ok "Y-m-d"}` for a valid date and `{:error "Invalid date: ..."}` otherwise.
<!-- phel-test: skip -->
```phel
(parse-date "2024-03-10")   ; => {:ok "2024-03-10"}
(parse-date "tomorrow-ish") ; => {:error "Invalid date: tomorrow-ish"}
```
{% end %}
{% hint() %}
Catch `\Exception`. The exact class changed between PHP versions, and every one of them extends `Exception`.
{% end %}
{% solution() %}
```phel
(defn parse-date [s]
  (try
    {:ok (.format (DateTimeImmutable. s) "Y-m-d")}
    (catch \Exception e
      {:error (str "Invalid date: " s)})))

(parse-date "2024-03-10")   ; => {:ok "2024-03-10"}
(parse-date "tomorrow-ish") ; => {:error "Invalid date: tomorrow-ish"}
```
The PHP exception stops at the border and becomes data, the same pattern as in State and Errors. The rest of your code checks `:ok` or `:error` and never needs a `try`.

Learn more: [Catch PHP exceptions](/documentation/language/php-interop/#catch-php-exceptions)
{% end %}
