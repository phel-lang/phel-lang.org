+++
title = "REPL"
weight = 1
description = "Use the Phel REPL: history vars, doc/dir/apropos helpers, introspection, tap> debugging, and a REPL-driven workflow"
aliases = ["/documentation/repl", "/documentation/tooling/phel-helpers"]
+++

After this page you can work the way Lisp programmers do: keep a REPL running, try each piece of code as you write it, reload changed files without a restart, and look up docs and source from the prompt.

## Start the REPL

```bash
./vendor/bin/phel repl
```

Type an expression and press Enter:

```phel
Welcome to the Phel Repl (v0.53.0)
Type (exit) or press Ctrl-D to exit.
user:1> (* 6 7)
42
user:2> (defn greet [name]
....:3>   (str "Hello, " name "!"))
#'user/greet
user:4> (greet "Phel")
"Hello, Phel!"
```

- The prompt shows the current namespace (`user` by default) and follows `(ns ...)` and `(in-ns ...)`.
- An unfinished expression switches the prompt to `....` until you close it.
- `Ctrl-C` cancels the current input. `Ctrl-D` or `(exit)` quits.

The REPL keeps recent results in `*1`, `*2`, and `*3`, and the last exception in `*e`:

```phel
user:1> (+ 1 2)
3
user:2> (* *1 10)
30
user:3> (/ 1 0)
[PHEL404] Division by zero
user:4> (.getMessage *e)
"Division by zero"
```

Errors print a headline and a short trace with internal frames hidden. The full PHP exception stays on `*e`.

## The workflow

### Build a transformation step by step

Check each stage before you add the next. `*1` holds the last result, so you do not retype it:

```phel
user:1> (def users [{:name "Alice" :role :admin}
....:2>             {:name "Bob" :role :user}
....:3>             {:name "Carol" :role :admin}])
user:4> (filter #(= :admin (:role %)) users)
({:name "Alice", :role :admin} {:name "Carol", :role :admin})
user:5> (map :name *1)
("Alice" "Carol")
```

### Write and test a function

Define it, call it with a few inputs, change it, and call it again. When it works, copy it into your source file:

```phel
user:1> (defn fizzbuzz [n]
....:2>   (cond
....:3>     (= 0 (% n 15)) "FizzBuzz"
....:4>     (= 0 (% n 3))  "Fizz"
....:5>     (= 0 (% n 5))  "Buzz"
....:6>     :else n))
user:7> (map fizzbuzz (range 1 16))
(1 2 "Fizz" 4 "Buzz" "Fizz" 7 8 "Fizz" "Buzz" 11 "Fizz" 13 14 "FizzBuzz")
```

### Work in your project's namespaces

Load a namespace with `require` (same arguments as `:require` in `ns`), then switch into it with `in-ns`. `in-ns` takes a bare symbol or a string, not a quoted symbol. The REPL helpers and the `repl` alias are available in every namespace you enter:

```phel
user:1> (require phel.html :as h)
phel.html
user:2> (h/html [:span {:class "greeting"} "Hello"])
"<span class=\"greeting\">Hello</span>"
user:3> (in-ns my.app)
my.app:4> (doc map)
```

`(use DateTimeImmutable)` imports a PHP class, like `:use` in `ns`. Every PHP function is available through `php/`, so the REPL is also a quick way to try PHP APIs.

### Reload changed code

Edit files in your editor and load the changes into the running REPL. `reload!` re-evaluates only the project namespaces whose source changed since the last load, plus the namespaces that depend on them, in dependency order:

<!-- phel-test: skip -->
```phel
user:1> (repl/reload!)
["my-app.users" "my-app.handlers"]
user:2> (repl/reload-all!)
; reloads every loaded project namespace, changed or not
```

Editors can bind the matching nREPL operations to commands (see [Editor Support](/documentation/tooling/editor-support/#nrepl-and-editor-integration)).

### Run tests

`test-ns` runs the tests in one namespace. `repl/run-tests` takes one or more namespace symbols, and `repl/run-test` one fully qualified test. Both load the namespace first when needed:

<!-- phel-test: skip -->
```phel
user:1> (test-ns "my-app.users-test")
user:2> (repl/run-tests 'my-app.users-test 'my-app.handlers-test)
user:3> (repl/run-test 'my-app.users-test/creates-a-user)
```

See [Testing](/documentation/guides/testing/) for the test library.

## Look things up

| Helper | What it does | Example |
|---|---|---|
| `doc` | prints the signature and docstring | `(doc map)` |
| `source` | returns the source code as a string | `(source filter)` |
| `dir` | lists the public definitions of a namespace | `(dir "phel.string")` |
| `apropos` | finds qualified names that contain a string | `(apropos "mapc")` gives `["phel.core/mapcat"]` |
| `search-doc` | prints every definition whose docstring matches | `(search-doc "lazy")` |
| `repl/find-fn` | returns maps (`:ns`, `:name`, `:doc`, arity) matching a name or docstring | `(map :name (repl/find-fn "reduce"))` |
| `symbol-info` | returns doc, file, line, arity, and namespace | `(symbol-info map)` |
| `macroexpand-1`, `macroexpand` | expand a macro one level, or fully | `(macroexpand-1 '(defn foo [x] x))` |
| `eval-str` | evaluates code from a string | `(eval-str "(+ 1 2)")` gives `3` |
| `load-file` | loads and evaluates a whole file | `(load-file "src/my/app.phel")` |

### Introspection

These helpers live in `phel.repl`. The REPL refers `doc`, `source`, `symbol-info`, `test-ns`, `eval-str`, `load-file`, `macroexpand`, `macroexpand-1`, and `ns-list` into every namespace. Reach the rest through the `repl` alias (`repl/find-fn`, `repl/reload!`), or require them by name:

<!-- phel-test: skip -->
```phel
(require phel.repl :refer [find-fn reload! reload-all! run-tests run-test])
```

| Function | Returns |
|---|---|
| `(ns-list)` | all loaded namespaces |
| `(ns-publics 'phel.core)` | the public definitions of a namespace |
| `(ns-interns 'my.app)` | all vars defined in a namespace |
| `(ns-aliases 'my.app)` | the namespace aliases |
| `(ns-refers 'my.app)` | the referred symbols |
| `(find-ns 'my.app)` | the namespace, or `nil` |
| `(create-ns 'my.scratch)` | a new namespace |
| `(intern 'my.scratch 'answer 42)` | a new var in that namespace |
| `(remove-ns 'my.scratch)` | removes the namespace |

## Debug helpers

`tap>` sends a value to every handler registered with `add-tap` and returns `true`. The REPL registers `phel.repl/print-tap` on startup, so `(tap> x)` prints `tap> x` at the prompt with no setup. Run `(remove-tap phel.repl/print-tap)` to turn that off. An exception inside one handler does not stop the others.

To collect tapped values, for example during a test, register a handler that adds to an atom:

```phel
(def tapped (atom []))
(def collector (fn [v] (swap! tapped conj v)))

(add-tap collector)
(tap> {:step 1 :result "ok"})
(tap> {:step 2 :result "fail"})

(deref tapped)
; => [{:step 1, :result "ok"} {:step 2, :result "fail"}]

(remove-tap collector)
```

For large nested values, `pprint` from `phel.pprint` prints them over several lines, and `pprint-str` returns the string.

Phel values are PHP objects, so PHP inspection functions work too: `(php/var_dump x)`, `(php/print_r x)`, and Symfony VarDumper's `(php/dump x)`. See [Debugging](/documentation/guides/debugging/) for these, `dbg`, breakpoints, and stack traces.
