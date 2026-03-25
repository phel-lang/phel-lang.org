+++
title = "Phel Debug Helpers"
weight = 5
aliases = ["/documentation/tooling/phel-helpers"]
+++

The Phel standard library ships with helper functions and macros that make it easier to inspect values during development. These tools are perfect for quick debugging without setting up external tools.

## `dbg`

`dbg` evaluates an expression, prints the expression together with the resulting value, and finally returns that value. It is handy for quick one-off inspections in the middle of a pipeline:

```phel
(def result
  (-> 41
      (inc)
      (dbg)))
# OUTPUT:
; (inc 41) => 42
```

**Use cases:**
- Debugging data transformation pipelines
- Checking intermediate values in threading macros
- Quick value inspection without breaking code flow

## `spy`

`spy` works like `dbg` but lets you provide an optional label so you can distinguish multiple probes:

```phel
(spy "before" (inc 10))
(spy "after" (* 2 11))
# OUTPUT:
; SPY "before" => 11
; SPY "after" => 22
```

**Use cases:**
- Multiple debug points in the same function
- Tracking values at different stages
- Distinguishing between similar expressions

## `tap`

`tap` passes the value through unchanged while optionally executing a handler for side effects (logging, assertions, etc.). Without a handler the value is printed using `print-str`:

```phel
(-> (range 3)
    (tap)
    (tap (fn [value] (println "count" (count value)))))
# OUTPUT:
; TAP => (0 1 2)
; count 3
```

**Use cases:**
- Non-intrusive debugging in pipelines
- Logging without modifying data flow
- Custom inspection with handler functions
- Assertions during development

## `dotrace`

`dotrace` wraps a function so every call and result are printed with indentation that reflects nesting depth. This is useful to understand the flow of recursive functions:

```phel
(defn fib [n]
  (if (< n 2)
    n
    (+ (fib (dec n)) (fib (- n 2)))))

(def traced-fib (dotrace 'fib fib))

(traced-fib 3)
# OUTPUT:
; TRACE t00: (fib 3)
; TRACE t01: |    (fib 2)
; TRACE t02: |    |    (fib 1)
; TRACE t02: |    |    => 1
; TRACE t03: |    |    (fib 0)
; TRACE t03: |    |    => 0
; TRACE t01: |    => 1
; TRACE t04: |    (fib 1)
; TRACE t04: |    => 1
; TRACE t00: => 2
```

**Use cases:**
- Understanding recursive function behavior
- Debugging complex call chains
- Visualizing function execution flow
- Performance analysis (counting calls)

### Trace Utilities

You can reset the tracing counters between runs with `reset-trace-state!` and configure the amount of zero-padding for trace identifiers with `set-trace-id-padding!`.

```phel
# Reset counters
(reset-trace-state!)

# Adjust ID padding (default is 2)
(set-trace-id-padding! 3)  # t000, t001, etc.
```

## Global Tap System

The `phel\debug` module provides a global tap handler system for routing debug values to one or more handlers. Unlike `tap` (which works inline in pipelines), the tap system is a global dispatch mechanism.

### `tap>`

Sends a value to all registered tap handlers. Returns `nil`.

```phel
(tap> {:event :user-login :user-id 42})
```

### `add-tap` / `remove-tap`

Register or unregister a handler function:

```phel
(defn my-logger [value]
  (println "TAP:" value))

(add-tap my-logger)
(tap> "hello")       # Prints: TAP: hello
(remove-tap my-logger)
```

### `reset-taps!`

Remove all registered tap handlers at once:

```phel
(reset-taps!)
```

**Use cases:**
- Routing debug output to a log file or external tool
- Collecting values during a test run for later inspection
- Building custom debugging dashboards

```phel
# Collect tapped values during a test
(def tapped (var []))
(add-tap (fn [v] (swap! tapped conj v)))

(tap> {:step 1 :result "ok"})
(tap> {:step 2 :result "fail"})

(deref tapped)
# => [{:step 1 :result "ok"} {:step 2 :result "fail"}]

(reset-taps!)
```

## Pretty Printing

The `phel\pprint` module provides `pprint` and `pprint-str` for readable output of nested data structures.

```phel
(ns my-app
  (:require phel\pprint :refer [pprint]))

(pprint {:users [{:name "Alice" :roles [:admin :editor]}
                  {:name "Bob" :roles [:viewer]}]
          :count 2})
# Prints:
# {:users [{:name "Alice" :roles [:admin :editor]}
#          {:name "Bob" :roles [:viewer]}]
#  :count 2}
```

`pprint-str` returns the formatted string instead of printing it. Both accept an optional width parameter.

## Best Practices

### Use `dbg` for Quick Checks

```phel
# Instead of breaking the pipeline
(def result
  (-> data
      (transform)
      (filter some?)
      (map process)))

# Just add dbg where needed
(def result
  (-> data
      (transform)
      (dbg)  # Check after transform
      (filter some?)
      (map process)))
```

### Use `spy` with Labels

```phel
# Clear labels help identify output
(defn complex-calc [x]
  (let [step1 (spy "input" x)
        step2 (spy "doubled" (* 2 step1))
        step3 (spy "squared" (* step2 step2))]
    step3))
```

### Use `tap` for Custom Logic

```phel
# Custom validation during development
(-> user-data
    (tap (fn [data] 
           (when-not (valid? data)
             (println "WARNING: Invalid data!" data))))
    (save-to-db))
```

### Use `dotrace` Sparingly

Tracing generates a lot of output. Use it for specific functions you need to understand, not entire codebases:

```phel
# Good: Trace specific recursive function
(def traced-factorial (dotrace 'factorial factorial))
(traced-factorial 5)

# Bad: Don't trace everything
# (def traced-everything (dotrace 'main main))
```

## Removing Debug Code

All these helpers are designed to be easy to add and remove:

```phel
# During development
(-> data (dbg) (process))

# For production - just remove the (dbg)
(-> data (process))
```

Consider using a macro to conditionally enable debugging:

```phel
(defmacro when-debug [& body]
  (when (php/getenv "DEBUG")
    `(do ~@body)))

# Only runs when DEBUG env var is set
(when-debug
  (spy "checking value" x))
```

## Next Steps

- For deeper debugging, set up [XDebug](/documentation/tooling/xdebug-setup/)
- Use [PHP native tools](/documentation/tooling/php-tools/) for familiar debugging
- Use [`pprint`](/documentation/api/#pprint) for readable output of nested data structures
- Check the [API documentation](/documentation/api/#debug) for more debug functions
