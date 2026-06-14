+++
title = "REPL"
weight = 2
description = "Use the Phel REPL: history vars, doc/dir/apropos helpers, introspection, tap> debugging, and a REPL-driven workflow"
aliases = ["/documentation/repl", "/documentation/tooling/phel-helpers"]
+++

## Interactive prompt

The REPL is your fastest feedback loop in Phel: type an expression, press Enter, see the result. Use it to explore the language, test functions as you write them, and debug live.

Start:

```bash
./vendor/bin/phel repl
```

Type any expression, press Enter:

```phel
Welcome to the Phel Repl
Type "exit" or press Ctrl-D to exit.
user:1> (* 6 7)
42
user:2> (str "Hello, " "world!")
"Hello, world!"
```

Multiline works: prompt switches to `....` until the expression is complete.

```phel
user:1> (defn greet [name]
....:2>   (str "Hello, " name "!"))
user:3> (greet "Phel")
"Hello, Phel!"
```

`Ctrl-D` or `exit` to quit.

Prompt shows current namespace (defaults to `user`), tracks `(ns ...)` and `(in-ns ...)`. `def` returns a printable var ref (e.g. `#'user/my-var`).

## History variables

REPL tracks recent results and the last exception:

- `*1` last result
- `*2` previous
- `*3` two before
- `*e` last exception

```phel
user:1> (+ 1 2)
3
user:2> (* *1 10)
30
user:3> (/ 1 0)
; => exception
user:4> (.getMessage *e)
"Division by zero"
```

Eval errors render as a headline + optional hint + trace with internal frames hidden. Full PHP frames remain on `*e` for inspection via interop.

## Built-in helpers

### doc

Show docs for any function or macro in scope:

```phel
user:1> (doc all?)
(all? pred coll)

Returns true if predicate is true for every element in collection, false otherwise.
nil
user:2> (doc map)
(map f & colls)

...
```

Fastest way to check signatures without leaving the REPL.

### require

Import a Phel namespace. Same args as `:require` in `ns`:

```phel
user:1> (require phel.html :as h)
phel.html
user:2> (h/html [:span {:class "greeting"} "Hello"])
<span class="greeting">Hello</span>
```

### dir

List public definitions in a namespace:

```phel
user:1> (dir "phel.string")
blank?
capitalize
ends-with?
escape
...
```

### apropos

Search symbols by name across loaded namespaces. Returns a sorted vector of fully qualified names:

```phel
user:1> (apropos "map")
@["phel.core/flat-map" "phel.core/hash-map" "phel.core/map" "phel.core/map-indexed" "phel.core/mapcat"]
```

### search-doc

Search docstrings. Prints each matching definition with its docs:

```phel
user:1> (search-doc "lazy")
--- phel.core/concat ---
(concat & xs)
Returns the concatenation of all xs ... Lazily evaluated, so xs can be lazy seqs.

...
```

### use

Alias a PHP class. Same as `:use` in `ns`:

```phel
user:1> (use DateTimeImmutable)
DateTimeImmutable
user:2> (.format (DateTimeImmutable.) "Y-m-d")
"2026-02-07"
```

## Introspection

Inspect code, namespaces, and macros. These helpers live in `phel.repl` and load automatically in the REPL and over nREPL.

### source

Return the source code of a function or macro as a string:

```phel
user:1> (source filter)
"(defn filter\n  \"Returns a lazy sequence of elements where predicate returns true...\"\n  [pred & args]\n  ...)"
```

### find-fn

Search functions by name or docstring. Returns a vector of maps with `:ns`, `:name`, `:doc`, and arity info:

```phel
user:1> (find-fn "reduce")
@[{:ns "phel.core", :name "reduce", :doc "...", :private false, :min-arity 3, :max-arity 3, :is-variadic false}
  ...]
```

### symbol-info

Structured metadata for a symbol: docs, source location, arity, namespace:

```phel
user:1> (symbol-info map)
{:doc "...", :file ".../seq-fns.phel", :line 54, :min-arity 1, :is-variadic true, :ns "phel.core", :name "map"}
```

### Namespace introspection

Inspect namespaces:

```phel
user:1> (ns-publics 'phel.core)
; Returns all public definitions in the namespace

user:2> (ns-aliases 'my.app)
; Returns all namespace aliases

user:3> (ns-refers 'my.app)
; Returns all referred symbols

user:4> (ns-list)
; Returns all loaded namespaces

user:5> (ns-interns 'my.app)
; Returns all interned vars in the namespace
```

### Namespace manipulation

Create, find, remove namespaces and intern vars at runtime (`phel.repl`):

```phel
(find-ns 'my.app)              ; => namespace or nil
(create-ns 'my.scratch)        ; create and return
(intern 'my.scratch 'answer 42) ; intern a var
(remove-ns 'my.scratch)
```

### Macro expansion

Expand macros to see generated code:

```phel
user:1> (macroexpand-1 '(defn foo [x] x))
; Expands one level of macro

user:2> (macroexpand '(defn foo [x] x))
; Fully expands all macros
```

### Evaluation

Evaluate code from strings or files:

```phel
user:1> (eval-str "(+ 1 2)")
3

user:2> (load-file "src/my/app.phel")
; Loads and evaluates an entire file
```

### Interactive testing

Run tests for a namespace from the REPL:

```phel
user:1> (require phel.test :refer [test-ns])
user:2> (test-ns 'my.app.tests)
; Runs all tests in the namespace and prints results
```

`phel.repl` also exposes `run-tests` and `run-test`, which load the namespace first if needed: `run-tests` takes one or more namespace symbols, `run-test` a single fully qualified test symbol:

<!-- phel-test: skip -->
```phel
user:3> (run-tests 'my-app.users-test 'my-app.handlers-test)
user:4> (run-test 'my-app.users-test/creates-a-user)
```

See also [Testing](/documentation/testing/) for `reset-stats`, `get-stats`, and `restore-stats`.

## Auto-injected utilities

`(in-ns ...)` auto-injects `doc`, `require`, `use` into the new namespace. No manual imports.

```phel
user:1> (in-ns 'my.app)
my.app:2> (doc map)
; Works immediately: no require needed
```

## REPL-driven workflow

Use the REPL as your primary feedback loop, not just one-off tests.

### Explore data interactively

Build transformations step by step, verifying each stage:

```phel
user:1> (def users [{:name "Alice" :role :admin}
....:2>             {:name "Bob" :role :user}
....:3>             {:name "Carol" :role :admin}])

user:4> (filter #(= :admin (:role %)) users)
@[{:name "Alice", :role :admin} {:name "Carol", :role :admin}]

user:5> (map :name *1)
@["Alice" "Carol"]
```

### Test functions as you write them

Define, test, refine, repeat:

```phel
user:1> (defn fizzbuzz [n]
....:2>   (cond
....:3>     (= 0 (% n 15)) "FizzBuzz"
....:4>     (= 0 (% n 3))  "Fizz"
....:5>     (= 0 (% n 5))  "Buzz"
....:6>     :else n))

user:7> (fizzbuzz 15)
"FizzBuzz"
user:8> (fizzbuzz 7)
7
user:9> (map fizzbuzz (range 1 16))
@[1 2 "Fizz" 4 "Buzz" "Fizz" 7 8 "Fizz" "Buzz" 11 "Fizz" 13 14 "FizzBuzz"]
```

### Reload changed code

Edit files in your editor and pull the changes into the running REPL without restarting it. `(reload!)` re-evaluates only project namespaces whose source changed since the last load, plus their dependents, in dependency order:

<!-- phel-test: skip -->
```phel
user:1> (reload!)
; => @[my-app.users my-app.handlers]   ; reloaded the changed ns and what depends on it

user:2> (reload-all!)
; => force-reloads every loaded project namespace, ignoring mtimes
```

`reload!`, `reload-all!`, `run-tests`, and `run-test` live in `phel.repl` and load automatically in the REPL and over nREPL. Editors can bind the matching nREPL ops `reload` (with an `all` param) and `run-tests` (an `ns` plus optional `var` param) to "reload changed" and "run the test under the cursor".

### Explore PHP interop

Try PHP functions and classes interactively:

```phel
user:1> (use DateTimeImmutable)
user:2> (def now (DateTimeImmutable.))
user:3> (.format now "l, F j, Y")
"Saturday, February 7, 2026"
user:4> (-> now (.modify "+3 days") (.format "Y-m-d"))
"2026-02-10"

user:5> (php/json_encode (php/array 1 2 3))
"[1,2,3]"
```

### Inspect data structures

See persistent data structures in action:

```phel
user:1> (def m {:a 1 :b 2 :c 3})
user:2> (assoc m :d 4)
{:a 1, :b 2, :c 3, :d 4}
user:3> m
{:a 1, :b 2, :c 3}   ; Original unchanged!

user:4> (type m)
:hash-map
user:5> (keys m)
[:a :b :c]
user:6> (vals m)
[1 2 3]
```

## Debug helpers

Stdlib ships helpers for inspecting values during development.

### Global tap system

Routes debug values to handlers. `tap>` invokes every function registered via `add-tap`.

`tap>` sends a value to every registered handler and returns `true`:

```phel
(tap> {:event :user-login :user-id 42})
```

`add-tap` / `remove-tap` register or unregister a handler function:

```phel
(defn my-logger [value]
  (println "TAP:" value))

(add-tap my-logger)
(tap> "hello")       ; Prints: TAP: hello
(remove-tap my-logger)
```

Exceptions in individual taps are swallowed so one bad handler doesn't break others.

Collect tapped values during a test:

```phel
(def tapped (atom []))
(def collector (fn [v] (swap! tapped conj v)))

(add-tap collector)
(tap> {:step 1 :result "ok"})
(tap> {:step 2 :result "fail"})

(deref tapped)
;; => [{:step 1, :result "ok"} {:step 2, :result "fail"}]

(remove-tap collector)
```

### Pretty printing

`phel.pprint` provides `pprint` and `pprint-str` for readable nested data output:

```phel
(ns my-app
  (:require phel.pprint :refer [pprint]))

(pprint {:users [{:name "Alice" :roles [:admin :editor]}
                  {:name "Bob" :roles [:viewer]}]
          :count 2})
;; Prints:
;; {:users [{:name "Alice", :roles [:admin :editor]}
;;          {:name "Bob", :roles [:viewer]}]
;;  :count 2}
```

`pprint-str` returns the formatted string instead of printing it.

### PHP native inspection

Phel values are PHP objects, so PHP inspection functions work via `php/`:

```phel
(php/var_dump (+ 2 2))
;; int(4)

;; print_r expects a native PHP array, so convert first:
(php/print_r (php/array 1 2 3))
;; Array
;; (
;;     [0] => 1
;;     [1] => 2
;;     [2] => 3
;; )
```

For richer output, [Symfony VarDumper](/documentation/tooling/php-tools/) via `(php/dump ...)` and `(php/dd ...)`.

## Tips

- **Use `doc` liberally:** faster than the browser.
- **Build expressions incrementally:** start simple, verify, compose.
- **Copy working expressions into source files:** the REPL is a scratchpad.
- **Use `require` to load your modules:** test your code live.
- **`Ctrl-C` cancels current input** if stuck mid-expression.

## Next steps

- [CLI commands](/documentation/tooling/cli-commands/) - run, test, and build from the terminal
- [Editor support](/documentation/tooling/editor-support/) - get the same eval loop inside your editor via `phel nrepl`
- [Testing](/documentation/testing/) - run and inspect tests from the REPL
