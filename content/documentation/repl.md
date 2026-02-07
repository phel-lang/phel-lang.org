+++
title = "REPL"
weight = 20
+++

## Interactive prompt

Phel comes with an interactive Read-Eval-Print Loop. The REPL lets you evaluate Phel expressions and see results immediately — invaluable for exploring the language, testing ideas, and debugging.

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

Multiline expressions work automatically — the prompt changes to `....` until the expression is complete:

```bash
phel:1> (defn greet [name]
....:2>   (str "Hello, " name "!"))
phel:3> (greet "Phel")
"Hello, Phel!"
```

Press `Ctrl-D` or type `exit` to end the session.

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

### use

Add an alias for a PHP class, same as the `:use` clause in `ns`:

```bash
phel:1> (use \DateTimeImmutable)
\DateTimeImmutable
phel:2> (php/-> (php/new DateTimeImmutable) (format "Y-m-d"))
"2026-02-07"
```

## REPL-driven development workflow

The REPL is most powerful when used as your primary development feedback loop — not just for one-off tests.

### Explore data interactively

Build up data transformations step by step, verifying each stage:

```bash
phel:1> (def users [{:name "Alice" :role :admin}
....:2>             {:name "Bob" :role :user}
....:3>             {:name "Carol" :role :admin}])

phel:4> (filter |(= :admin (:role $)) users)
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
{:a 1 :b 2 :c 3}   # Original unchanged!

phel:4> (type m)
phel:5> (keys m)
(:a :b :c)
phel:6> (vals m)
(1 2 3)
```

## Tips

- **Use `doc` liberally** — it's faster than switching to the browser to look up a function.
- **Build up complex expressions incrementally** — start simple, verify, then compose.
- **Copy working REPL expressions into your source files** — the REPL is a scratchpad for your final code.
- **Use `require` to load your project modules** — test your own code interactively.
- **`Ctrl-C` cancels the current input** if you get stuck in an incomplete expression.
