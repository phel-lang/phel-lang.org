+++
title = "Debug"
weight = 18
+++

## Phel debug helpers

The Phel standard library ships with helper functions and macros that make it
easier to inspect values during development.

### `dbg`

`dbg` evaluates an expression, prints the expression together with the
resulting value, and finally returns that value. It is handy for quick
one-off inspections in the middle of a pipeline:

```phel
(def result
  (-> 41
      (inc)
      (dbg)))
# OUTPUT:
; (inc 41) => 42
```

### `spy`

`spy` works like `dbg` but lets you provide an optional label so you can
distinguish multiple probes:

```phel
(spy "before" (inc 10))
(spy "after" (* 2 11))
# OUTPUT:
; SPY "before" => 11
; SPY "after" => 22
```

### `tap`

`tap` passes the value through unchanged while optionally executing a handler
for side effects (logging, assertions, etc.). Without a handler the value is
printed using `print-str`:

```phel
(-> (range 3)
    (tap)
    (tap (fn [value] (println "count" (count value)))))
# OUTPUT:
; TAP => (0 1 2)
; count 3
```

### `dotrace`

`dotrace` wraps a function so every call and result are printed with
indentation that reflects nesting depth. This is useful to understand the
flow of recursive functions:

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

You can reset the tracing counters between runs with `reset-trace-state!` and
configure the amount of zero-padding for trace identifiers with
`set-trace-id-padding!`.

## Native var_dump()

You can use any php function simply using the `php/` prefix, so you can use:

```phel
# Dumping a definition by its name
(def v (+ 2 2))
(php/var_dump v)
# OUTPUT:
int(4)
```

```phel 
# Directly dumping the result of a function
(php/var_dump (+ 3 3))
# OUTPUT:
int(6)
```

Additionally, you can call `(php/die)` to force the execution of the process so that you can debug a particular value on your own rhythm.

## Symfony dumper: dump() & dd()

Symfony has an awesome [VarDumper Component](https://symfony.com/doc/current/components/var_dumper.html) which you can use in your phel projects as well. You can install it by using composer, under your `require-dev` dependencies.

```json
"require-dev": {
    "symfony/var-dumper": "^5.4"
},
```

And then, the same drill, you can `dump()` a definition by its name or the function result:

```phel
(php/dump (+ 4 4))
# OUTPUT:
8
```

Additionally, you can also use `dd()` to dump and die the execution of the program as soon as it reaches that point:

```phel 
(php/dd (+ 5 5))
# OUTPUT:
10
```

## Check the evaluated PHP

You can keep the generated temporal PHP files for debugging purposes. Useful when you see an error occurring on `/private/var/folders/qq/dvftwj.../T/__phelV2KvGD` but the file does not exist. Read the [docs](/documentation/configuration/#keepgeneratedtempfiles).

```php
<?php # phel-config-local.php

return (require __DIR__ . '/phel-config.php')
    ->setKeepGeneratedTempFiles(true)
;
```

> TIP: Add this file to the `.gitignore` of the project, so you can have control over the configuration while on development without changing the global config.
