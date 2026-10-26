+++
title = "REPL"
weight = 1
description = "Use the Phel REPL: history vars, doc/dir/apropos helpers, introspection, tap> debugging, and a REPL-driven workflow"
aliases = ["/documentation/repl", "/documentation/tooling/phel-helpers"]
+++

## Start the REPL

```bash
./vendor/bin/phel repl
```

Type an expression and press Enter:

```phel
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

The REPL keeps recent results in `*1`, `*2`, and `*3`, and the last exception in `*e`. Errors print a short trace; the full PHP exception stays on `*e`:

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

## The workflow

### Build a transformation step by step

Check each stage before you add the next. `*1` holds the last result, so you do not retype it:

```phel
user:1> (def users [{:name "Alice" :role :admin} {:name "Bob" :role :user}])
user:2> (filter #(= :admin (:role %)) users)
({:name "Alice", :role :admin})
user:3> (map :name *1)
("Alice")
```

When a function works, copy it into your source file.

### Work in your project's namespaces

Load a namespace with `require` (same arguments as `:require` in `ns`), then switch into it with `in-ns`. `in-ns` takes a bare symbol or a string, not a quoted symbol. The REPL helpers and the `repl` alias work in every namespace you enter:

```phel
user:1> (require phel.html :as h)
phel.html
user:2> (h/html [:span {:class "greeting"} "Hello"])
"<span class=\"greeting\">Hello</span>"
user:3> (in-ns my.app)
my.app:4> (doc map)
```

`(use DateTimeImmutable)` imports a PHP class, like `:use` in `ns`. Every PHP function is available through `php/`.

### Reload changed code

`reload!` re-evaluates the project namespaces whose source changed since the last load, plus the namespaces that depend on them, in dependency order:

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

### Introspection

The other helpers live in `phel.repl`: `search-doc`, `find-fn`, `symbol-info`, `macroexpand-1`, `eval-str`, `load-file`, and the `ns-*` functions (`ns-list`, `ns-publics`, `ns-interns`, and more). The REPL refers `doc`, `source`, `symbol-info`, `test-ns`, `eval-str`, `load-file`, `macroexpand`, `macroexpand-1`, and `ns-list` into every namespace. Reach the rest through the `repl` alias (`repl/find-fn`, `repl/reload!`), or require them by name:

<!-- phel-test: skip -->
```phel
(require phel.repl :refer [find-fn reload! reload-all! run-tests run-test])
```

Run `(dir "phel.repl")` for the full list.

## Debug helpers

The REPL prints every `(tap> x)` with no setup; [Debugging](/documentation/guides/debugging/#watch-values-with-tap) covers `tap>`, `dbg`, breakpoints, and stack traces. Turn the printer off with `(remove-tap phel.repl/print-tap)`. To collect tapped values, for example during a test, register a handler that adds to an atom:

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
