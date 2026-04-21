+++
title = "REPL"
weight = 2
aliases = ["/documentation/repl"]
+++

## Interactive prompt

Phel comes with an interactive Read-Eval-Print Loop. The REPL lets you evaluate Phel expressions and see results immediately - invaluable for exploring the language, testing ideas, and debugging.

Start it with:

```bash
./vendor/bin/phel repl
```

Type any Phel expression and press Enter:

```bash
Welcome to the Phel Repl
Type "exit" or press Ctrl-D to exit.
phel:1> (* 6 7)
42
phel:2> (str "Hello, " "world!")
"Hello, world!"
```

Multiline expressions work automatically - the prompt changes to `....` until the expression is complete:

```bash
phel:1> (defn greet [name]
....:2>   (str "Hello, " name "!"))
phel:3> (greet "Phel")
"Hello, Phel!"
```

Press `Ctrl-D` or type `exit` to end the session.

The prompt shows the current namespace and tracks `(ns ...)` switches. `def` returns a printable var ref (e.g. `#'user/my-var`).

## History variables

The REPL tracks recent evaluations and the last exception:

- `*1`, result of the last expression
- `*2`, result of the previous one
- `*3`, two before that
- `*e`, last exception thrown at the prompt

```bash
user:1> (+ 1 2)
3
user:2> (* *1 10)
30
user:3> (/ 1 0)
; => exception
user:4> (php/-> *e (getMessage))
"Division by zero"
```

## Built-in helpers

### doc

Look up documentation for any function or macro in scope:

```bash
phel:1> (doc all?)
(all? pred xs)

Returns true if `(pred x)` is logical true for every `x` in `xs`, else false.
nil
phel:2> (doc map)
(map f & colls)

...
```

This is the fastest way to check function signatures and behavior without leaving the REPL.

### require

Import a Phel namespace into the REPL session. The arguments are the same as the `:require` clause in `ns`:

```bash
phel:1> (require phel\html :as h)
phel\html
phel:2> (h/html [:span {:class "greeting"} "Hello"])
<span class="greeting">Hello</span>
```

### dir

List all public definitions in a namespace:

```bash
phel:1> (dir phel\string)
blank?
capitalize
ends-with?
escape
...
```

### apropos

Search for symbols matching a pattern across all loaded namespaces:

```bash
phel:1> (apropos "map")
phel\core/map
phel\core/mapcat
phel\core/hash-map
phel\core/map-indexed
phel\core/zipmap
...
```

### search-doc

Search docstrings for a keyword or phrase:

```bash
phel:1> (search-doc "lazy")
phel\core/lazy-seq
  Creates a lazy sequence from a thunk...
phel\core/take
  Returns a lazy sequence of the first n items...
...
```

### use

Add an alias for a PHP class, same as the `:use` clause in `ns`:

```bash
phel:1> (use \DateTimeImmutable)
\DateTimeImmutable
phel:2> (php/-> (php/new DateTimeImmutable) (format "Y-m-d"))
"2026-02-07"
```

## Introspection functions

The REPL provides several functions for inspecting code, namespaces, and macros.

### source

Display the source code of a function or macro:

```bash
phel:1> (source filter)
(defn filter [pred xs]
  ...)
```

### find-fn

Search for functions by example -- provide an input and expected output, and Phel will find matching functions:

```bash
phel:1> (find-fn [1 2 3] 3)
phel\core/count
phel\core/last
...
```

### symbol-info

Get detailed metadata about a symbol, including its type, namespace, and documentation:

```bash
phel:1> (symbol-info map)
{:name "map" :ns "phel\\core" :type :function ...}
```

### Namespace introspection

Inspect namespaces and their contents:

```bash
phel:1> (ns-publics 'phel\core)
; Returns all public definitions in the namespace

phel:2> (ns-aliases 'my\app)
; Returns all namespace aliases

phel:3> (ns-refers 'my\app)
; Returns all referred symbols

phel:4> (ns-list)
; Returns all loaded namespaces

phel:5> (ns-interns 'my\app)
; Returns all interned vars in the namespace
```

### Namespace manipulation

Create, find, remove namespaces and intern vars at runtime (`phel\repl`):

```phel
(find-ns 'my\app)              ; => namespace or nil
(create-ns 'my\scratch)        ; create and return
(intern 'my\scratch 'answer 42) ; intern a var
(remove-ns 'my\scratch)
```

### Macro expansion

Expand macros to see the code they generate:

```bash
phel:1> (macroexpand-1 '(defn foo [x] x))
; Expands one level of macro

phel:2> (macroexpand '(defn foo [x] x))
; Fully expands all macros
```

### Evaluation functions

Evaluate code from strings or files:

```bash
phel:1> (eval-str "(+ 1 2)")
3

phel:2> (load-file "src/my/app.phel")
; Loads and evaluates an entire file
```

### Interactive testing

Run tests for a namespace directly from the REPL:

```bash
phel:1> (require phel\test :refer [test-ns])
phel:2> (test-ns 'my\app\tests)
; Runs all tests in the namespace and prints results
```

See also [Testing](/documentation/testing/) for `reset-stats`, `get-stats`, and `restore-stats`.

## Auto-injected utilities

When you switch namespaces with `(in-ns ...)`, the REPL automatically injects the core utilities (`doc`, `require`, `use`) into the new namespace so they are always available without manual imports.

```bash
phel:1> (in-ns 'my\app)
my\app:2> (doc map)
; Works immediately -- no require needed
```

## REPL-driven development workflow

The REPL is most powerful when used as your primary development feedback loop - not just for one-off tests.

### Explore data interactively

Build up data transformations step by step, verifying each stage:

```bash
phel:1> (def users [{:name "Alice" :role :admin}
....:2>             {:name "Bob" :role :user}
....:3>             {:name "Carol" :role :admin}])

phel:4> (filter #(= :admin (:role %)) users)
({:name "Alice" :role :admin} {:name "Carol" :role :admin})

phel:5> (map :name *1)
("Alice" "Carol")
```

### Test functions as you write them

Define a function, test it immediately, refine, repeat:

```bash
phel:1> (defn fizzbuzz [n]
....:2>   (cond
....:3>     (= 0 (% n 15)) "FizzBuzz"
....:4>     (= 0 (% n 3))  "Fizz"
....:5>     (= 0 (% n 5))  "Buzz"
....:6>     :else n))

phel:7> (fizzbuzz 15)
"FizzBuzz"
phel:8> (fizzbuzz 7)
7
phel:9> (map fizzbuzz (range 1 16))
(1 2 "Fizz" 4 "Buzz" "Fizz" 7 8 "Fizz" "Buzz" 11 "Fizz" 13 14 "FizzBuzz")
```

### Explore PHP interop

The REPL is great for discovering how PHP functions and classes behave in Phel:

```bash
phel:1> (use \DateTimeImmutable)
phel:2> (def now (php/new DateTimeImmutable))
phel:3> (php/-> now (format "l, F j, Y"))
"Friday, February 7, 2026"
phel:4> (php/-> now (modify "+3 days") (format "Y-m-d"))
"2026-02-10"

phel:5> (php/json_encode (php/array 1 2 3))
"[1,2,3]"
```

### Inspect data structures

Use the REPL to understand how Phel's persistent data structures work:

```bash
phel:1> (def m {:a 1 :b 2 :c 3})
phel:2> (assoc m :d 4)
{:a 1 :b 2 :c 3 :d 4}
phel:3> m
{:a 1 :b 2 :c 3}   ; Original unchanged!

phel:4> (type m)
phel:5> (keys m)
(:a :b :c)
phel:6> (vals m)
(1 2 3)
```

## Tips

- **Use `doc` liberally** - it's faster than switching to the browser to look up a function.
- **Build up complex expressions incrementally** - start simple, verify, then compose.
- **Copy working REPL expressions into your source files** - the REPL is a scratchpad for your final code.
- **Use `require` to load your project modules** - test your own code interactively.
- **`Ctrl-C` cancels the current input** if you get stuck in an incomplete expression.
