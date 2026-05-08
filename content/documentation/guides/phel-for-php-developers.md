+++
title = "Phel for PHP Developers"
weight = 1
aliases = ["/documentation/phel-for-php-developers"]
+++

PHP patterns mapped to Phel. Quick reference for PHP devs. Each section: PHP code, then idiomatic Phel.

## Variables and constants

PHP variables are mutable. Phel bindings are immutable: create new values, don't change existing ones.

```php
// PHP
$x = 42;
$x = 100;            // Reassignment is fine

const TAX_RATE = 0.21;
define('APP_NAME', 'MyApp');

function example() {
    $local = 10;
    $local = 20;      // Can mutate freely
}
```

```phel
;; Phel
(def x 42)            ; Global binding, cannot be redefined

(def tax-rate 0.21)   ; Constants are just defs
(def app-name "MyApp")

;; Local bindings with let
(let [local 10
      other (+ local 5)]
  (+ local other))    ; Evaluates to 25
;; local and other do not exist outside the let block
```

Key: `def` and `let` are immutable. Create a new value, don't modify. See [Global and Local Bindings](/documentation/language/global-and-local-bindings).

For mutable state, use atoms:

```phel
(def counter (atom 0))
(swap! counter inc)    ; counter is now 1
(deref counter)        ; Evaluates to 1
```

## Functions

`defn` defines a function. Last expression is the return value. No `return`.

```php
// PHP
function add(int $a, int $b): int {
    return $a + $b;
}

$double = fn($x) => $x * 2;

function greet(string $name = "World"): string {
    return "Hello, $name!";
}

function sum(...$numbers): int {
    return array_sum($numbers);
}
```

```phel
;; Phel
(defn add [a b]
  (+ a b))

(def double #(* % 2))       ; Short anonymous function

;; Multi-arity for default parameters
(defn greet
  ([] (greet "World"))
  ([name] (str "Hello, " name "!")))

(greet)          ; => "Hello, World!"
(greet "Phel")   ; => "Hello, Phel!"

;; Variadic functions with &
(defn sum [& numbers]
  (reduce + 0 numbers))

(sum 1 2 3 4)    ; => 10
```

`#(...)` replaces PHP arrow functions. `%` for one param, `%1`, `%2` for multiple:

```phel
#(+ %1 %2)          ; Same as fn($a, $b) => $a + $b
#(str "Hi " %)      ; Same as fn($x) => "Hi " . $x
```

Full reference: [Functions and Recursion](/documentation/language/functions-and-recursion).

## Arrays to vectors and maps

PHP arrays are both indexed and associative. Phel splits these into distinct immutable data structures.

### Indexed arrays, vectors

```php
// PHP
$numbers = [1, 2, 3];
$numbers[] = 4;               // Append
$first = $numbers[0];         // Access by index
```

```phel
;; Phel
(def numbers [1 2 3])
(def updated (conj numbers 4))  ; => [1 2 3 4], numbers is unchanged
(get numbers 0)                 ; => 1
(first numbers)                 ; => 1
```

### Associative arrays, maps

```php
// PHP
$user = ['name' => 'Alice', 'age' => 30];
$user['email'] = 'alice@example.com';  // Add key
$name = $user['name'];                  // Access
unset($user['age']);                     // Remove key
```

```phel
;; Phel
(def user {:name "Alice" :age 30})
(def with-email (assoc user :email "alice@example.com"))
(get user :name)           ; => "Alice"
(:name user)               ; => "Alice" (keywords are functions!)
(dissoc user :age)         ; => {:name "Alice"}
```

### Quick reference

| PHP | Phel | Notes |
|-----|------|-------|
| `$arr[] = $val` | `(conj vec val)` | Returns new vector |
| `$arr['k'] = $v` | `(assoc map :k v)` | Returns new map |
| `$arr['k']` | `(get map :k)` or `(:k map)` | |
| `unset($arr['k'])` | `(dissoc map :k)` | Returns new map |
| `count($arr)` | `(count coll)` | Works on all collections |
| `in_array($v, $arr)` | `(some #(= % v) coll)` | |
| `array_key_exists` | `(contains? map :k)` | |

All operations return **new** collections. Originals never change. See [Data Structures](/documentation/language/data-structures).

## Control flow

### if / else

```php
// PHP
if ($age >= 18) {
    $status = 'adult';
} else {
    $status = 'minor';
}
```

```phel
;; Phel
(def status (if (>= age 18) "adult" "minor"))
```

### when (if without else)

```php
// PHP
if ($debug) {
    echo "Debug mode on";
    log("enabled");
}
```

```phel
;; Phel
(when debug
  (println "Debug mode on")
  (log "enabled"))
```

`when` returns `nil` if condition is false. For side-effects or no else.

### switch, case

```php
// PHP
switch ($code) {
    case 200: $msg = 'OK'; break;
    case 404: $msg = 'Not Found'; break;
    default: $msg = 'Unknown';
}
```

```phel
;; Phel
(def msg
  (case code
    200 "OK"
    404 "Not Found"))  ; Returns nil if no match
```

### match, cond

```php
// PHP
$label = match(true) {
    $temp <= 0 => 'freezing',
    $temp <= 20 => 'cold',
    $temp <= 30 => 'warm',
    default => 'hot',
};
```

```phel
;; Phel
(def label
  (cond
    (<= temp 0)  "freezing"
    (<= temp 20) "cold"
    (<= temp 30) "warm"
    :else         "hot"))
```

### Truthiness difference

Common PHP-dev gotcha:

```php
// PHP falsy values: false, null, 0, "", "0", [], 0.0
if (0) { /* NOT reached */ }
if ("") { /* NOT reached */ }
if ([]) { /* NOT reached */ }
```

```phel
;; Phel: ONLY false and nil are falsy
(if 0 "truthy" "falsy")    ; => "truthy"
(if "" "truthy" "falsy")   ; => "truthy"
(if [] "truthy" "falsy")   ; => "truthy"
```

See [Control Flow](/documentation/language/control-flow), [Truth and Boolean Operations](/documentation/language/truth-and-boolean-operations).

## Loops

Phel prefers higher-order functions. Most PHP loops become `map`, `filter`, `reduce`.

### foreach

```php
// PHP
foreach ($items as $item) {
    echo $item;
}
foreach ($map as $key => $value) {
    echo "$key: $value";
}
```

```phel
;; Phel - side-effects only (returns nil)
(foreach [item items]
  (println item))

(foreach [k v my-map]
  (println (str k ": " v)))

;; Phel - building a new collection (prefer this)
(for [item :in items] (process item))
```

### for loop

```php
// PHP
for ($i = 0; $i < 10; $i++) {
    echo $i;
}
```

```phel
;; Phel - using loop/recur
(loop [i 0]
  (when (< i 10)
    (println i)
    (recur (inc i))))

;; Phel - using for comprehension (when building a collection)
(for [i :range [0 10]] i)  ; => [0 1 2 3 4 5 6 7 8 9]
```

### array_map, array_filter, array_reduce

```php
// PHP
$doubled = array_map(fn($x) => $x * 2, $numbers);
$evens = array_filter($numbers, fn($x) => $x % 2 === 0);
$sum = array_reduce($numbers, fn($carry, $x) => $carry + $x, 0);
```

```phel
;; Phel
(def doubled (map #(* % 2) numbers))
(def evens (filter even? numbers))
(def sum (reduce + 0 numbers))
```

Phel's arg order: function first, collection last. Makes composition and threading natural.

## Strings

```php
// PHP
$full = $first . " " . $last;
$len = strlen($greeting);
$formatted = sprintf("Hello, %s! You are %d.", $name, $age);
$upper = strtoupper($str);
$contains = str_contains($haystack, $needle);
```

```phel
;; Phel
(def full (str first " " last))
(def len (php/strlen greeting))
(def formatted (format "Hello, %s! You are %d." name age))
(def upper (php/strtoupper str))
(def contains (php/str_contains haystack needle))
```

Any PHP string function via `php/` prefix. `str` concatenates, `format` for sprintf-style. See [PHP Interop](/documentation/php-interop).

## Classes and objects

Phel isn't OO, but has full interop with PHP's object system.

### Creating objects

```php
// PHP
$now = new DateTime();
$date = new DateTimeImmutable('2024-01-15');
```

```phel
;; Phel
(ns my.module
  (:use \DateTime)
  (:use \DateTimeImmutable))

(def now (php/new DateTime))
(def date (php/new DateTimeImmutable "2024-01-15"))
```

### Calling methods and accessing properties

```php
// PHP
$formatted = $date->format('Y-m-d');
$timestamp = $date->getTimestamp();
$obj->name;

// Chaining
$result = (new DateTimeImmutable('2024-01-15'))
    ->modify('+1 month')
    ->format('Y-m-d');
```

```phel
;; Phel
(def formatted (php/-> date (format "Y-m-d")))
(def timestamp (php/-> date (getTimestamp)))
(php/-> obj name)

;; Chaining
(def result
  (php/-> (php/new DateTimeImmutable "2024-01-15")
          (modify "+1 month")
          (format "Y-m-d")))
```

### Static methods and constants

```php
// PHP
$atom = DateTimeImmutable::ATOM;
$parsed = DateTimeImmutable::createFromFormat('Y-m-d', '2024-03-22');
```

```phel
;; Phel
(def atom (php/:: DateTimeImmutable ATOM))
(def parsed (php/:: DateTimeImmutable (createFromFormat "Y-m-d" "2024-03-22")))
```

For data modeling, Phel uses structs and maps:

```phel
(defstruct user [name email role])

(def alice (user "Alice" "alice@example.com" :admin))
(get alice :name)            ; => "Alice"
(assoc alice :role :editor)  ; => new struct with role changed
```

See [PHP Interop](/documentation/php-interop) for the full reference.

## Protocols vs PHP interfaces

PHP interfaces require implementation at class definition. Phel protocols extend to types **after** they're defined, without modifying the original code.

```php
// PHP -- interface must be declared at class definition
interface Loggable {
    public function toLogString(): string;
}

class Order implements Loggable {
    public function __construct(
        public int $id,
        public float $total
    ) {}

    public function toLogString(): string {
        return "Order#{$this->id} \${$this->total}";
    }
}

// Cannot make a third-party class implement Loggable
// without wrapping or extending it
```

```phel
;; Phel -- define a protocol
(defprotocol Loggable
  (to-log-string [this]))

;; Define a struct
(defstruct order [id total])

;; Extend the struct to implement the protocol
(extend-type order
  Loggable
  (to-log-string [this]
    (str "Order#" (get this :id) " $" (get this :total))))

(to-log-string (order 1 29.99))   ; => "Order#1 $29.99"

;; Extend ANY existing type after the fact
(extend-protocol Loggable
  :string (to-log-string [this] (str "String: " this))
  :int    (to-log-string [this] (str "Int: " this)))

(to-log-string "hello")           ; => "String: hello"
(to-log-string 42)                ; => "Int: 42"

;; Check if a value supports the protocol
(satisfies? Loggable (order 1 0)) ; => true
```

Advantage: any type can satisfy a protocol at any time, even external library types. PHP would need a wrapper, adapter, or inheritance.

## Regex: literals vs preg_match

PHP uses `preg_match` with pattern strings. Phel has regex literals (`#"..."`) and matching functions.

```php
// PHP
if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $input, $matches)) {
    $year = $matches[1];
    $month = $matches[2];
    $day = $matches[3];
}

$isEmail = (bool) preg_match('/^.+@.+\..+$/', $email);

// Find all matches
preg_match_all('/\d+/', 'a1b2c3', $all);
// $all[0] = ['1', '2', '3']
```

```phel
;; Phel -- regex literals and matching functions
(let [m (re-matches #"(\d{4})-(\d{2})-(\d{2})" input)]
  (when m
    (let [year (get m 1)
          month (get m 2)
          day (get m 3)]
      (str year "/" month "/" day))))

(def email? #(not (nil? (re-matches #".+@.+\..+" %))))
(email? "alice@example.com")       ; => true

;; re-find returns the first match (does not require full string match)
(re-find #"\d+" "abc123def")       ; => "123"
```

`re-matches` requires full-string match (like wrapping with `^...$`). `re-find` returns first match anywhere (like `preg_match` without anchors).

## Error handling

```php
// PHP
try {
    $result = riskyOperation();
} catch (InvalidArgumentException $e) {
    $result = "Invalid: " . $e->getMessage();
} catch (RuntimeException $e) {
    $result = "Runtime error";
} finally {
    cleanup();
}

throw new RuntimeException("Something went wrong");
```

```phel
;; Phel
(def result
  (try
    (risky-operation)
    (catch \InvalidArgumentException e
      (str "Invalid: " (php/-> e (getMessage))))
    (catch \RuntimeException e
      "Runtime error")
    (finally
      (cleanup))))

(throw (php/new \RuntimeException "Something went wrong"))
```

Single form, similar structure to PHP try/catch. See [Control Flow](/documentation/language/control-flow) exceptions section.

### Structured exceptions with ex-info

PHP exceptions: message string, integer code, optional previous. Phel's `ex-info` adds a **data map**: more informative without custom exception classes.

```php
// PHP -- custom exception to carry context
class UserNotFoundException extends RuntimeException {
    public function __construct(
        public readonly int $userId,
        string $message = "User not found",
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}

try {
    throw new UserNotFoundException(userId: 42);
} catch (UserNotFoundException $e) {
    echo $e->getMessage();  // "User not found"
    echo $e->userId;        // 42
}
```

```phel
;; Phel -- no custom class needed
(try
  (throw (ex-info "User not found" {:user-id 42 :type :not-found}))
  (catch \Exception e
    (println (ex-message e))       ; => "User not found"
    (println (ex-data e))          ; => {:user-id 42 :type :not-found}
    (println (ex-cause e))))       ; => nil
```

`ex-info` attaches context without new classes. `ex-message`, `ex-data`, `ex-cause` inspect.

## Common patterns

Real PHP converted to idiomatic Phel.

### Processing a list of users

```php
// PHP
$users = [
    ['name' => 'Alice', 'active' => true, 'age' => 30],
    ['name' => 'Bob', 'active' => false, 'age' => 25],
    ['name' => 'Charlie', 'active' => true, 'age' => 35],
];

$activeNames = array_map(
    fn($u) => $u['name'],
    array_filter($users, fn($u) => $u['active'])
);
// ['Alice', 'Charlie']
```

```phel
;; Phel
(def users
  [{:name "Alice"   :active true  :age 30}
   {:name "Bob"     :active false :age 25}
   {:name "Charlie" :active true  :age 35}])

(def active-names
  (->> users
       (filter :active)
       (map :name)))
;; => ["Alice" "Charlie"]
```

### Building an API response

```php
// PHP
function jsonResponse(array $data, int $status = 200): array {
    return [
        'status' => $status,
        'body' => json_encode($data),
        'headers' => ['Content-Type' => 'application/json'],
    ];
}

$response = jsonResponse(['user' => 'Alice', 'role' => 'admin']);
```

```phel
;; Phel
(defn json-response
  ([data] (json-response data 200))
  ([data status]
    {:status status
     :body (php/json_encode (to-php-array data))
     :headers {:content-type "application/json"}}))

(def response (json-response {:user "Alice" :role "admin"}))
```

### Working with dates

```php
// PHP
$now = new DateTimeImmutable();
$nextWeek = $now->modify('+7 days');
$formatted = $nextWeek->format('Y-m-d');
$isWeekend = in_array($now->format('N'), ['6', '7']);
```

```phel
;; Phel
(ns my.dates
  (:use \DateTimeImmutable))

(def now (php/new DateTimeImmutable))
(def next-week (php/-> now (modify "+7 days")))
(def formatted (php/-> next-week (format "Y-m-d")))
(def weekend?
  (let [day-of-week (php/-> now (format "N"))]
    (or (= day-of-week "6") (= day-of-week "7"))))
```

### Reading a config file

```php
// PHP
$config = json_decode(file_get_contents('config.json'), true);
$dbHost = $config['database']['host'] ?? 'localhost';
$dbPort = $config['database']['port'] ?? 3306;
```

```phel
;; Phel
(def config
  (let [raw (php/file_get_contents "config.json")]
    (php/json_decode raw true)))

(def db-host (or (php/aget-in config ["database" "host"]) "localhost"))
(def db-port (or (php/aget-in config ["database" "port"]) 3306))
```

## Transducers vs array pipelines

Chaining `array_filter`, `array_map`, `array_reduce` creates intermediate arrays per step. Transducers compose into a single pass, no intermediate collections.

```php
// PHP -- each step creates a new array
$numbers = range(1, 1000);

$result = array_reduce(
    array_map(
        fn($x) => $x * $x,
        array_filter($numbers, fn($x) => $x % 2 === 0)
    ),
    fn($carry, $x) => $carry + $x,
    0
);
// Sum of squares of even numbers: 3 intermediate arrays created
```

```phel
;; Phel -- threading macros (creates intermediate lazy sequences)
(def result
  (->> (range 1 1001)
       (filter even?)
       (map #(* % %))
       (reduce + 0)))

;; Phel -- transducers (single pass, no intermediate collections)
(def result
  (transduce
    (comp (filter even?) (map #(* % %)))
    + 0
    (range 1 1001)))
```

### When to use transducers

| Approach | When to use |
|----------|-------------|
| `->>` threading | Default. Readable, lazy seqs, good for typical data |
| `transduce` | Hot paths, large collections, or reusable transforms |
| `into` with xf | Want a specific collection type as result |

```phel
;; Reusable transducer: define once, apply to any data source
(def process-events
  (comp
    (filter #(= :error (get % :level)))
    (map :message)
    (take 10)))

;; Apply to different collections
(into [] process-events log-stream-a)
(into [] process-events log-stream-b)
(transduce process-events str "" log-stream-c)
```

Transducers shine when the same transform applies to different sources (vectors, lazy seqs, channels). Decoupled from input/output type.

## Key mindset shifts

PHP to Phel:

- **Immutable data:** transform to new values, originals stay intact.
- **Functions are values:** pass them, return them, store them.
- **Prefix notation:** operator first. `(+ 1 2)` not `1 + 2`. Consistent across all calls.
- **No return:** last expression is the return value.
- **No semicolons or braces:** just parens. Indentation for humans, parens for the compiler.
- **Truthiness:** only `false` and `nil` falsy. `0`, `""`, `[]` truthy. Catches PHP devs off guard.
- **Everything is an expression:** `if`, `let`, `case`, `cond` all return values. No statements.
- **Thread-last (`->>`) replaces method chaining:** instead of `$arr->filter()->map()->sort()`, write `(->> coll (filter pred) (map f) (sort))`.
