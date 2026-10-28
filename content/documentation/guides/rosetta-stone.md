+++
title = "Rosetta Stone: PHP → Phel"
weight = 3
description = "Side-by-side PHP and Phel for the same task: variables, functions, collections, strings, control flow, loops, and interop"
aliases = ["/documentation/rosetta-stone", "/documentation/guides/phel-for-php-developers"]

[extra]
scripts = ["rosetta-stone.js"]
+++

Find the Phel form for a PHP pattern you already know. Click a category to filter, or browse them all.

<div class="rosetta-filters" id="rosetta-filters">
  <button class="rosetta-filter active" data-filter="all">All</button>
  <button class="rosetta-filter" data-filter="variables">Variables</button>
  <button class="rosetta-filter" data-filter="functions">Functions</button>
  <button class="rosetta-filter" data-filter="arrays">Arrays/Collections</button>
  <button class="rosetta-filter" data-filter="strings">Strings</button>
  <button class="rosetta-filter" data-filter="control">Control Flow</button>
  <button class="rosetta-filter" data-filter="loops">Loops</button>
  <button class="rosetta-filter" data-filter="oop">OOP/Interop</button>
  <button class="rosetta-filter" data-filter="functional">Functional</button>
</div>

<!-- ==================== VARIABLES ==================== -->

<div class="rosetta-item rosetta-group" data-category="variables">

## Variables

</div>

<div class="rosetta-item" data-category="variables">

### Variable assignment

<div class="rosetta-compare">
<div class="rosetta-php">

**PHP**

```php
$userName = "world";
echo "Hello $userName";
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(def user-name "world")
(println (str "Hello " user-name))
```

</div>
</div>
</div>

<div class="rosetta-item" data-category="variables">

### Local bindings (let)

<div class="rosetta-compare">
<div class="rosetta-php">

**PHP**

```php
function area(float $r): float {
    $pi = 3.14159;
    $squared = $r * $r;
    return $pi * $squared;
}
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(defn area [r]
  (let [pi 3.14159
        squared (* r r)]
    (* pi squared)))
```

</div>
</div>
</div>

<!-- ==================== FUNCTIONS ==================== -->

<div class="rosetta-item rosetta-group" data-category="functions">

## Functions

</div>

<div class="rosetta-item" data-category="functions">

### Basic function

<div class="rosetta-compare">
<div class="rosetta-php">

**PHP**

```php
function add(int $a, int $b): int {
    return $a + $b;
}

echo add(2, 3); // 5
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(defn add [a b]
  (+ a b))

(add 2 3) ; => 5
```

</div>
</div>
</div>

<div class="rosetta-item" data-category="functions">

### Default params / multi-arity

<div class="rosetta-compare">
<div class="rosetta-php">

**PHP**

```php
function greet(string $name = "World"): string {
    return "Hello, $name!";
}

greet();       // "Hello, World!"
greet("Phel"); // "Hello, Phel!"
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(defn greet
  ([] (greet "World"))
  ([name] (str "Hello, " name "!")))

(greet)        ; => "Hello, World!"
(greet "Phel") ; => "Hello, Phel!"
```

</div>
</div>
</div>

<div class="rosetta-item" data-category="functions">

### Anonymous function

<div class="rosetta-compare">
<div class="rosetta-php">

**PHP**

```php
$double = fn($x) => $x * 2;

$add = function($a, $b) {
    return $a + $b;
};

echo $double(5); // 10
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(def double #(* % 2))

(def add (fn [a b] (+ a b)))

(double 5) ; => 10
```

</div>
</div>
</div>

<div class="rosetta-item" data-category="functions">

### Type annotations

<div class="rosetta-compare">
<div class="rosetta-php">

**PHP**

```php
function greet(?string $name): string {
    return "Hello, " . ($name ?? "World");
}
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(defn ^string greet [^"?string" name]
  (str "Hello, " (or name "World")))
```

</div>
</div>
</div>

<div class="rosetta-item" data-category="functions">

### Variadic arguments

<div class="rosetta-compare">
<div class="rosetta-php">

**PHP**

```php
function sum(int ...$numbers): int {
    return array_sum($numbers);
}

echo sum(1, 2, 3, 4); // 10
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(defn sum [& numbers]
  (reduce + 0 numbers))

(sum 1 2 3 4) ; => 10
```

</div>
</div>
</div>

<!-- ==================== ARRAYS / COLLECTIONS ==================== -->

<div class="rosetta-item rosetta-group" data-category="arrays">

## Arrays and collections

</div>

<div class="rosetta-item" data-category="arrays">

### Create array / vector

<div class="rosetta-compare">
<div class="rosetta-php">

**PHP**

```php
$numbers = [1, 2, 3, 4, 5];
$first = $numbers[0];       // 1
$count = count($numbers);   // 5
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(def numbers [1 2 3 4 5])
(first numbers) ; => 1
(count numbers) ; => 5
```

</div>
</div>
</div>

<div class="rosetta-item" data-category="arrays">

### Associative array / map

<div class="rosetta-compare">
<div class="rosetta-php">

**PHP**

```php
$user = [
    'name' => 'Alice',
    'age' => 30,
    'role' => 'admin',
];
$userName = $user['name']; // "Alice"
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(def user {:name "Alice"
           :age 30
           :role "admin"})
(:name user) ; => "Alice"
```

</div>
</div>
</div>

<div class="rosetta-item" data-category="arrays">

### Add element

<div class="rosetta-compare">
<div class="rosetta-php">

**PHP**

```php
$items = [1, 2, 3];
$items[] = 4;          // [1, 2, 3, 4]

$map = ['a' => 1];
$map['b'] = 2;         // ['a' => 1, 'b' => 2]
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(def items [1 2 3])
(conj items 4)  ; => [1 2 3 4]

(def m {:a 1})
(assoc m :b 2)  ; => {:a 1 :b 2}
```

</div>
</div>
</div>

<div class="rosetta-item" data-category="arrays">

### Remove element

<div class="rosetta-compare">
<div class="rosetta-php">

**PHP**

```php
$user = ['name' => 'Alice', 'age' => 30];
unset($user['age']);
// ['name' => 'Alice']
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(def user {:name "Alice" :age 30})
(dissoc user :age) ; => {:name "Alice"}
```

</div>
</div>
</div>

<div class="rosetta-item" data-category="arrays">

### Nested access

<div class="rosetta-compare">
<div class="rosetta-php">

**PHP**

```php
$data = [
    'user' => [
        'address' => [
            'city' => 'Berlin',
        ],
    ],
];
$city = $data['user']['address']['city'];
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(def data {:user {:address {:city "Berlin"}}})
(get-in data [:user :address :city]) ; => "Berlin"
```

</div>
</div>
</div>

<!-- ==================== STRINGS ==================== -->

<div class="rosetta-item rosetta-group" data-category="strings">

## Strings

</div>

<div class="rosetta-item" data-category="strings">

### Concatenation

<div class="rosetta-compare">
<div class="rosetta-php">

**PHP**

```php
$firstName = "Ada";
$lastName = "Lovelace";
$full = $firstName . " " . $lastName;
$greeting = "Hello, " . $firstName . "!";
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(def first-name "Ada")
(def last-name "Lovelace")
(str first-name " " last-name) ; => "Ada Lovelace"
(str "Hello, " first-name "!") ; => "Hello, Ada!"
```

</div>
</div>
</div>

<div class="rosetta-item" data-category="strings">

### Formatting / interpolation

<div class="rosetta-compare">
<div class="rosetta-php">

**PHP**

```php
$msg = sprintf("Hello, %s! You are %d.", $userName, $age);
$price = sprintf("$%.2f", $amount);
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(def user-name "Alice")
(def age 30)
(def amount 9.5)
(format "Hello, %s! You are %d." user-name age) ; => "Hello, Alice! You are 30."
(format "$%.2f" amount)                         ; => "$9.50"
```

</div>
</div>
</div>

<div class="rosetta-item" data-category="strings">

### Split / join

<div class="rosetta-compare">
<div class="rosetta-php">

**PHP**

```php
$parts = explode(",", "a,b,c");     // ["a", "b", "c"]
$joined = implode("-", $parts);      // "a-b-c"
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(ns my-app
  (:require phel.string :as str))

(def parts (str/split "a,b,c" #",")) ; => ["a" "b" "c"]
(str/join "-" parts)                 ; => "a-b-c"
```

</div>
</div>
</div>

<!-- ==================== CONTROL FLOW ==================== -->

<div class="rosetta-item rosetta-group" data-category="control">

## Control flow

</div>

<div class="rosetta-item" data-category="control">

### If / else

<div class="rosetta-compare">
<div class="rosetta-php">

**PHP**

```php
if ($age >= 18) {
    $status = "adult";
} else {
    $status = "minor";
}
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(def age 21)
(def status
  (if (>= age 18) "adult" "minor"))
```

</div>
</div>
</div>

<div class="rosetta-item" data-category="control">

### Switch / case

<div class="rosetta-compare">
<div class="rosetta-php">

**PHP**

```php
switch ($code) {
    case 200: $msg = "OK"; break;
    case 404: $msg = "Not Found"; break;
    case 500: $msg = "Server Error"; break;
    default:  $msg = "Unknown";
}
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(def code 404)
(def msg
  (case code
    200 "OK"
    404 "Not Found"
    500 "Server Error"
    "Unknown"))
; => "Not Found"
```

</div>
</div>
</div>

<div class="rosetta-item" data-category="control">

### Ternary and null coalescing

<div class="rosetta-compare">
<div class="rosetta-php">

**PHP**

```php
$label = $itemCount > 0 ? "has items" : "empty";
$userName = $input ?? "default";
$host = $config['db']['host'] ?? "localhost";
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(def item-count 3)
(def input nil)
(def config {:db {:host "db.example.com"}})

(if (> item-count 0) "has items" "empty")         ; => "has items"
(or input "default")                              ; => "default"
(or (get-in config [:db :host]) "localhost")      ; => "db.example.com"
```

</div>
</div>
</div>

<!-- ==================== LOOPS ==================== -->

<div class="rosetta-item rosetta-group" data-category="loops">

## Loops

</div>

<div class="rosetta-item" data-category="loops">

### Foreach

<div class="rosetta-compare">
<div class="rosetta-php">

**PHP**

```php
foreach ($items as $item) {
    echo $item . "\n";
}

foreach ($map as $key => $value) {
    echo "$key: $value\n";
}
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(def items ["a" "b" "c"])
(def my-map {:x 1 :y 2})

(foreach [item items]
  (println item))

(foreach [k v my-map]
  (println (str k ": " v)))
```

</div>
</div>
</div>

<div class="rosetta-item" data-category="loops">

### Accumulator loop / reduce

<div class="rosetta-compare">
<div class="rosetta-php">

**PHP**

```php
$sum = 0;
for ($i = 1; $i <= 10; $i++) {
    $sum += $i;
}
// $sum = 55
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(reduce + 0 (range 1 11))
; => 55
```

</div>
</div>
</div>

<div class="rosetta-item" data-category="loops">

### While / loop-recur

<div class="rosetta-compare">
<div class="rosetta-php">

**PHP**

```php
$n = 10;
$acc = 0;
while ($n > 0) {
    $acc += $n;
    $n--;
}
// $acc = 55
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(loop [n 10
       acc 0]
  (if (> n 0)
    (recur (dec n) (+ acc n))
    acc))
; => 55
```

</div>
</div>
</div>

<!-- ==================== OOP / INTEROP ==================== -->

<div class="rosetta-item rosetta-group" data-category="oop">

## OOP and interop

</div>

<div class="rosetta-item" data-category="oop">

### Method call

<div class="rosetta-compare">
<div class="rosetta-php">

**PHP**

```php
$formatted = $date->format("Y-m-d");
$result = $date->modify("+1 month")->format("Y-m-d");
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(ns my.module
  (:use DateTimeImmutable))

(def date (DateTimeImmutable. "2024-01-15"))

(def formatted (.format date "Y-m-d"))
(def result
  (-> date
    (.modify "+1 month")
    (.format "Y-m-d")))
```

</div>
</div>
</div>

<!-- ==================== FUNCTIONAL ==================== -->

<div class="rosetta-item rosetta-group" data-category="functional">

## Functional

</div>

<div class="rosetta-item" data-category="functional">

### Pipe / threading

<div class="rosetta-compare">
<div class="rosetta-php">

**PHP**

```php
// Nested calls (inside-out)
$result = implode(", ",
    array_map(
        fn($x) => strtoupper($x),
        array_filter(
            $names,
            fn($x) => strlen($x) > 3
        )
    )
);
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(ns my-app
  (:require phel.string :as str))

(def names ["Alice" "Bob" "Eve" "Charlie"])
(def result
  (->> names
       (filter #(> (count %) 3))
       (map str/upper-case)
       (str/join ", ")))
; => "ALICE, CHARLIE"
```

</div>
</div>
</div>
