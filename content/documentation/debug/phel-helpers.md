+++
title = "Phel Debug Helpers"
weight = 1
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

- For deeper debugging, set up [XDebug](/documentation/debug/xdebug-setup/)
- Use [PHP native tools](/documentation/debug/php-tools/) for familiar debugging
- Check the [API documentation](/documentation/api/#debug) for more debug functions
