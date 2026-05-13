+++
title = "REPL-Driven Development in Phel"
aliases = [ "/blog/repl-driven-development" ]
description = "Live functions, *1 chaining, load-file reloads, tap> debugging. The Lisp workflow on PHP."
date = 2026-05-13

[extra]
og_image = "https://phel-lang.org/images/og-repl-driven-development.png"
+++

PHP feedback loop: *edit, save, refresh*. Phel offers another: keep a process alive, send code in, watch values come back.

The REPL holds a real namespace. Every `def`, `defn`, and `require` lands in a live image you can poke, redefine, and inspect. No restart.

## Start the loop

```bash
./vendor/bin/phel repl
```

```phel
Welcome to the Phel Repl
Type "exit" or press Ctrl-D to exit.
user:1> (* 6 7)
42
```

Prompt shows the current namespace and counter. Multi-line continues with `....` until the expression closes. `Ctrl-D` exits.

## `*1` and friends

The REPL binds the last three results and the last exception:

- `*1`: last result
- `*2`: previous
- `*3`: two back
- `*e`: last exception

```phel
user:1> (range 1 11)
(1 2 3 4 5 6 7 8 9 10)
user:2> (filter odd? *1)
(1 3 5 7 9)
user:3> (reduce + *1)
25
```

Three transformations, no variable names. On a throw, `*e` holds the exception:

```phel
user:4> (/ 1 0)
; => exception
user:5> (php/-> *e (getMessage))
"Division by zero"
```

Internal frames hidden by default since 0.37. Reach into `*e` for the full PHP stack.

## Build in pieces

Try one step, inspect, wrap the next around it:

```phel
user:1> (def users [{:name "Alice" :role :admin}
....:2>             {:name "Bob"   :role :user}
....:3>             {:name "Carol" :role :admin}])

user:4> (filter #(= :admin (:role %)) users)
({:name "Alice" :role :admin} {:name "Carol" :role :admin})

user:5> (map :name *1)
("Alice" "Carol")

user:6> (sort *1)
("Alice" "Carol")
```

Lift the verified chain into a `defn`:

```phel
(defn admin-names [users]
  (->> users
       (filter #(= :admin (:role %)))
       (map :name)
       sort))
```

## Reload with `load-file`

```phel
user:1> (load-file "src/my/app.phel")
```

Re-evaluating a `defn` swaps the function under existing callers. Keep state alive (HTTP clients, fixtures, DI bootstrap), swap code under it.

## Look things up

```phel
user:1> (doc map)
(map f & colls)
Returns a sequence consisting of the result of applying f to ...

user:2> (apropos "map")
phel.core/map
phel.core/mapcat
phel.core/hash-map
...

user:3> (search-doc "lazy")
phel.core/lazy-seq
  Creates a lazy sequence from a thunk...

user:4> (source filter)
(defn filter [pred xs] ...)
```

`find-fn` goes the other way: input and output, get candidates.

```phel
user:5> (find-fn [1 2 3] 3)
phel.core/count
phel.core/last
```

`(in-ns 'my.app)` auto-injects `doc`, `require`, `use` into the new namespace.

## Probe PHP interop

```phel
user:1> (use DateTimeImmutable)
user:2> (def now (php/new DateTimeImmutable))
user:3> (php/-> now (format "l, F j, Y"))
"Wednesday, May 13, 2026"
user:4> (php/-> now (modify "+3 days") (format "Y-m-d"))
"2026-05-16"
```

`now` stays put. `DateTimeImmutable` returns a new instance per call.

## Debug without `var_dump`

`tap>` routes values to handlers you control:

```phel
user:1> (def captured (atom []))
user:2> (add-tap (fn [v] (swap! captured conj v)))

;; somewhere in production code:
;; (tap> {:step :auth :user-id 42})
;; (tap> {:step :charge :amount 100})

user:3> @captured
[{:step :auth :user-id 42} {:step :charge :amount 100}]
```

Subscribe and unsubscribe handlers as you investigate. Exceptions in one handler are swallowed.

`pprint` for nested data:

```phel
user:4> (require phel.pprint :refer [pprint])
user:5> (pprint {:users [{:name "Alice" :roles [:admin :editor]}
....:6>                  {:name "Bob"   :roles [:viewer]}]
....:7>          :count 2})
{:users [{:name "Alice" :roles [:admin :editor]}
         {:name "Bob" :roles [:viewer]}]
 :count 2}
```

`(php/dump x)` and `(php/dd x)` hand off to Symfony VarDumper when installed.

## Run tests from the REPL

```phel
user:1> (require phel.test :refer [test-ns])
user:2> (test-ns 'my.app.user-tests)
```

Combine with `load-file`: edit, reload, re-run. Bootstrapping (autoload, fixtures, DI) stays warm. Sub-second test runs.

## Macroexpand

```phel
user:1> (macroexpand-1 '(when true :a :b))
user:2> (macroexpand '(when true :a :b))
```

See [first macro post](/blog/writing-your-first-macro) for context.

## Gotchas

- **State sticks.** A `def` at form #4 still bound at #400. `(ns-interns 'user)` to see what's interned, or restart.
- **`*1` shifts every expression.** Capture with `def` to refer back.
- **Closures hold old refs.** `(defn foo ...)`, `(def f #(foo %))`, redefine `foo`: `f` still calls the first `foo`.
- **`load-file` does not unload.** Removed `defn`s stay until restart or `(ns-unmap ...)`.

## Editor integration

- **PhpStorm:** [Phel IntelliJ plugin](https://github.com/phel-lang/phel-intellij-plugin)
- **VS Code:** [Phel VS Code extension](https://github.com/phel-lang/phel-vs-code-extension)
- **Vim:** [`phel.vim`](https://github.com/danirod/phel.vim)

Send-form-to-REPL is the keystroke that turns this from "neat tool" into "primary workflow."

## Try it

Open the REPL, paste a fixture, build the function one expression at a time. Copy the working version into the file. The [REPL reference](/documentation/tooling/repl/) lists every helper.
