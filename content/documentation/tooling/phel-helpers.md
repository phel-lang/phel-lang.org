+++
title = "Phel Debug Helpers"
weight = 5
aliases = ["/documentation/tooling/phel-helpers"]
+++

Stdlib ships helpers for inspecting values during development. Quick debugging without external tools.

## Global tap system

Routes debug values to handlers. `tap>` invokes every function registered via `add-tap`.

### `tap>`

Sends a value to every registered handler. Returns `true`.

```phel
(tap> {:event :user-login :user-id 42})
```

### `add-tap` / `remove-tap`

Register or unregister a handler function:

```phel
(defn my-logger [value]
  (println "TAP:" value))

(add-tap my-logger)
(tap> "hello")       ; Prints: TAP: hello
(remove-tap my-logger)
```

Exceptions in individual taps are swallowed so one bad handler doesn't break others.

**Use cases:**
- Route debug output to a log file or external tool
- Collect values during a test run
- Custom debugging dashboards

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

## Pretty printing

`phel.pprint` provides `pprint` and `pprint-str` for readable nested data output.

```phel
(ns my-app
  (:require phel.pprint :refer [pprint]))

(pprint {:users [{:name "Alice" :roles [:admin :editor]}
                  {:name "Bob" :roles [:viewer]}]
          :count 2})
;; Prints:
;; {:users [{:name "Alice" :roles [:admin :editor]}
;;          {:name "Bob" :roles [:viewer]}]
;;  :count 2}
```

`pprint-str` returns the formatted string. Both accept an optional width.

## PHP native inspection

Phel values are PHP objects. Any PHP inspection function works via `php/`:

```phel
(php/var_dump (+ 2 2))
;; int(4)

(php/print_r {:a 1 :b 2})
```

Advanced output: [Symfony VarDumper](/documentation/tooling/php-tools/) via `(php/dump ...)` and `(php/dd ...)`.

## Next steps

- Deeper debugging: [XDebug](/documentation/tooling/xdebug-setup/)
- Familiar debugging: [PHP native tools](/documentation/tooling/php-tools/)
- Readable output: [`pprint`](/documentation/reference/api/pprint/#pprint)
- More: [API docs](/documentation/reference/api/)
