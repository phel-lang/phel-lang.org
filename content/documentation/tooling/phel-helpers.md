+++
title = "Phel Debug Helpers"
weight = 5
aliases = ["/documentation/tooling/phel-helpers"]
+++

The Phel standard library ships with helpers for inspecting values during development. These tools are perfect for quick debugging without setting up external tools.

## Global Tap System

The tap system provides a global dispatch mechanism for routing debug values to one or more handlers. Values flow through `tap>`, which in turn invokes every function registered with `add-tap`.

### `tap>`

Sends a value to every registered tap handler. Returns `true`.

```phel
(tap> {:event :user-login :user-id 42})
```

### `add-tap` / `remove-tap`

Register or unregister a handler function:

```phel
(defn my-logger [value]
  (println "TAP:" value))

(add-tap my-logger)
(tap> "hello")       ;; Prints: TAP: hello
(remove-tap my-logger)
```

Exceptions thrown by individual taps are swallowed so one misbehaving handler does not affect the others.

**Use cases:**
- Routing debug output to a log file or external tool
- Collecting values during a test run for later inspection
- Building custom debugging dashboards

```phel
;; Collect tapped values during a test
(def tapped (atom []))
(def collector (fn [v] (swap! tapped conj v)))

(add-tap collector)
(tap> {:step 1 :result "ok"})
(tap> {:step 2 :result "fail"})

(deref tapped)
;; => [{:step 1 :result "ok"} {:step 2 :result "fail"}]

(remove-tap collector)
```

## Pretty Printing

The `phel\pprint` module provides `pprint` and `pprint-str` for readable output of nested data structures.

```phel
(ns my-app
  (:require phel\pprint :refer [pprint]))

(pprint {:users [{:name "Alice" :roles [:admin :editor]}
                  {:name "Bob" :roles [:viewer]}]
          :count 2})
;; Prints:
;; {:users [{:name "Alice" :roles [:admin :editor]}
;;          {:name "Bob" :roles [:viewer]}]
;;  :count 2}
```

`pprint-str` returns the formatted string instead of printing it. Both accept an optional width parameter.

## PHP Native Inspection

Since Phel values are PHP objects under the hood, you can call any PHP inspection function with the `php/` prefix:

```phel
(php/var_dump (+ 2 2))
;; int(4)

(php/print_r {:a 1 :b 2})
```

For more advanced output, use [Symfony's VarDumper](/documentation/tooling/php-tools/) via `(php/dump ...)` and `(php/dd ...)`.

## Next Steps

- For deeper debugging, set up [XDebug](/documentation/tooling/xdebug-setup/)
- Use [PHP native tools](/documentation/tooling/php-tools/) for familiar debugging
- Use [`pprint`](/documentation/api/#pprint) for readable output of nested data structures
- Check the [API documentation](/documentation/api/) for more helpers
