+++
title = "Rosetta Stone: PHP → Phel"
weight = 6
+++

See how common PHP patterns translate to Phel. Click a category to filter, or browse them all.

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

<div class="rosetta-item" data-category="variables">

### Variable assignment

<div class="rosetta-compare">
<div class="rosetta-php">

**PHP**

```php
$name = "world";
echo "Hello $name";
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(def name "world")
(println (str "Hello " name))
```

</div>
</div>
</div>

<div class="rosetta-item" data-category="variables">

### Constants

<div class="rosetta-compare">
<div class="rosetta-php">

**PHP**

```php
const TAX_RATE = 0.21;
define('APP_NAME', 'MyApp');
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(def tax-rate 0.21)
(def app-name "MyApp")
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

<div class="rosetta-item" data-category="variables">

### Type checking

<div class="rosetta-compare">
<div class="rosetta-php">

**PHP**

```php
is_string($x);    // true/false
is_int($x);       // true/false
is_null($x);      // true/false
is_array($x);     // true/false
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(string? x)       # true/false
(int? x)          # true/false
(nil? x)          # true/false
(vector? x)       # true/false
```

</div>
</div>
</div>

<!-- ==================== FUNCTIONS ==================== -->

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

(println (add 2 3)) # 5
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

(greet)       # "Hello, World!"
(greet "Phel") # "Hello, Phel!"
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
(def double |(* $ 2))

(def add (fn [a b] (+ a b)))

(println (double 5)) # 10
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

(println (sum 1 2 3 4)) # 10
```

</div>
</div>
</div>

<!-- ==================== ARRAYS / COLLECTIONS ==================== -->

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
(def first-num (first numbers))  # 1
(def cnt (count numbers))        # 5
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
$name = $user['name']; // "Alice"
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(def user {:name "Alice"
           :age 30
           :role "admin"})
(def name (:name user)) # "Alice"
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
(def updated (conj items 4))     # [1 2 3 4]

(def m {:a 1})
(def with-b (assoc m :b 2))     # {:a 1 :b 2}
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

$items = [1, 2, 3];
$filtered = array_filter($items, fn($x) => $x !== 2);
// [1, 3]
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(def user {:name "Alice" :age 30})
(dissoc user :age)
# {:name "Alice"}

(def items [1 2 3])
(filter |(not= $ 2) items)
# [1 3]
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
(def city (get-in data [:user :address :city]))
# "Berlin"
```

</div>
</div>
</div>

<!-- ==================== STRINGS ==================== -->

<div class="rosetta-item" data-category="strings">

### Concatenation

<div class="rosetta-compare">
<div class="rosetta-php">

**PHP**

```php
$full = $first . " " . $last;
$greeting = "Hello, " . $name . "!";
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(def full (str first " " last))
(def greeting (str "Hello, " name "!"))
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
$msg = sprintf("Hello, %s! You are %d.", $name, $age);
$price = sprintf("$%.2f", $amount);
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(def msg (format "Hello, %s! You are %d." name age))
(def price (format "$%.2f" amount))
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
(def parts (php/explode "," "a,b,c"))  # PHP array
(def joined (php/implode "-" parts))   # "a-b-c"
```

</div>
</div>
</div>

<div class="rosetta-item" data-category="strings">

### Substring / search

<div class="rosetta-compare">
<div class="rosetta-php">

**PHP**

```php
$sub = substr("Hello World", 0, 5);     // "Hello"
$pos = strpos("Hello World", "World");   // 6
$has = str_contains("Hello", "ell");     // true
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(def sub (php/substr "Hello World" 0 5))     # "Hello"
(def pos (php/strpos "Hello World" "World")) # 6
(def has (php/str_contains "Hello" "ell"))   # true
```

</div>
</div>
</div>

<!-- ==================== CONTROL FLOW ==================== -->

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
(def msg
  (case code
    200 "OK"
    404 "Not Found"
    500 "Server Error"))
```

</div>
</div>
</div>

<div class="rosetta-item" data-category="control">

### Ternary / inline if

<div class="rosetta-compare">
<div class="rosetta-php">

**PHP**

```php
$label = $count > 0 ? "has items" : "empty";
$display = $user['name'] ?: "Anonymous";
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(def label (if (> count 0) "has items" "empty"))
(def display (or (:name user) "Anonymous"))
```

</div>
</div>
</div>

<div class="rosetta-item" data-category="control">

### Null coalescing / or

<div class="rosetta-compare">
<div class="rosetta-php">

**PHP**

```php
$name = $input ?? "default";
$host = $config['db']['host'] ?? "localhost";
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(def name (or input "default"))
(def host
  (or (get-in config [:db :host]) "localhost"))
```

</div>
</div>
</div>

<!-- ==================== LOOPS ==================== -->

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
(foreach [item items]
  (println item))

(foreach [k v my-map]
  (println (str k ": " v)))
```

</div>
</div>
</div>

<div class="rosetta-item" data-category="loops">

### For with accumulator / reduce

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
(def sum
  (reduce + 0 (range 1 11)))
# 55
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
# 55
```

</div>
</div>
</div>

<div class="rosetta-item" data-category="loops">

### List comprehension / for

<div class="rosetta-compare">
<div class="rosetta-php">

**PHP**

```php
$evens = [];
foreach (range(0, 9) as $x) {
    if ($x % 2 === 0) {
        $evens[] = $x * $x;
    }
}
// [0, 4, 16, 36, 64]
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(for [x :range [0 10]
      :when (even? x)]
  (* x x))
# [0 4 16 36 64]
```

</div>
</div>
</div>

<!-- ==================== OOP / INTEROP ==================== -->

<div class="rosetta-item" data-category="oop">

### Create object

<div class="rosetta-compare">
<div class="rosetta-php">

**PHP**

```php
$now = new DateTime();
$date = new DateTimeImmutable("2024-01-15");
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(ns my\module
  (:use \DateTime)
  (:use \DateTimeImmutable))

(def now (php/new DateTime))
(def date (php/new DateTimeImmutable "2024-01-15"))
```

</div>
</div>
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
(def formatted (php/-> date (format "Y-m-d")))
(def result
  (php/-> date
    (modify "+1 month")
    (format "Y-m-d")))
```

</div>
</div>
</div>

<div class="rosetta-item" data-category="oop">

### Static method

<div class="rosetta-compare">
<div class="rosetta-php">

**PHP**

```php
$atom = DateTimeImmutable::ATOM;
$parsed = DateTimeImmutable::createFromFormat(
    "Y-m-d",
    "2024-03-22"
);
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(def atom (php/:: DateTimeImmutable ATOM))
(def parsed
  (php/:: DateTimeImmutable
    (createFromFormat "Y-m-d" "2024-03-22")))
```

</div>
</div>
</div>

<!-- ==================== FUNCTIONAL ==================== -->

<div class="rosetta-item" data-category="functional">

### Map

<div class="rosetta-compare">
<div class="rosetta-php">

**PHP**

```php
$doubled = array_map(
    fn($x) => $x * 2,
    [1, 2, 3, 4]
);
// [2, 4, 6, 8]
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(map |(* $ 2) [1 2 3 4])
# [2 4 6 8]
```

</div>
</div>
</div>

<div class="rosetta-item" data-category="functional">

### Filter

<div class="rosetta-compare">
<div class="rosetta-php">

**PHP**

```php
$evens = array_filter(
    [1, 2, 3, 4, 5, 6],
    fn($x) => $x % 2 === 0
);
// [2, 4, 6]
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(filter even? [1 2 3 4 5 6])
# [2 4 6]
```

</div>
</div>
</div>

<div class="rosetta-item" data-category="functional">

### Reduce

<div class="rosetta-compare">
<div class="rosetta-php">

**PHP**

```php
$sum = array_reduce(
    [1, 2, 3, 4],
    fn($carry, $x) => $carry + $x,
    0
);
// 10
```

</div>
<div class="rosetta-phel">

**Phel**

```phel
(reduce + 0 [1 2 3 4])
# 10
```

</div>
</div>
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
# Thread-last (top-down, reads naturally)
(def result
  (->> names
       (filter |(> (php/strlen $) 3))
       (map php/strtoupper)
       (php/implode ", ")))
```

</div>
</div>
</div>

<script src="/rosetta-stone.js" defer></script>
