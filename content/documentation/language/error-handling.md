+++
title = "Error handling"
weight = 9
description = "Throw and catch exceptions, handle PHP exceptions, attach data with ex-info, and decide when to throw vs return nil"
aliases = ["/documentation/error-handling", "/documentation/exceptions"]

[extra]
difficulty = "intermediate"
+++

How Phel signals and recovers from failures: `throw` to raise, `try`/`catch`/`finally` to handle, and `ex-info` to carry structured data with an error. Phel uses PHP's exception machinery, so any PHP `Throwable` works here.

> This page is the canonical guide to handling errors in your code. Chasing a specific `[PHEL...]` compiler error code instead? See the [Error Reference](/documentation/reference/errors/).

## Throwing

<!-- phel-test: skip -->
```phel
(throw expr)
```

`throw` evaluates _expr_ and throws it. The value must implement PHP's `Throwable` (every PHP exception does).

<!-- phel-test: skip -->
```phel
(throw (new \Exception "Something went wrong"))

;; Shorthand for constructing a class: (Class. args)
(throw (Exception. "Something went wrong"))
```

## Try, catch, finally

<!-- phel-test: skip -->
```phel
(try expr* catch-clause* finally-clause?)
```

`try` evaluates its body. If nothing throws, it returns the last value. If a `catch` clause matches the thrown type, it returns that clause's value. A `finally` clause always runs last, for cleanup.

```phel
(try
  (throw (Exception. "boom"))
  (catch \Exception e "recovered")) ; => "recovered"

(try
  (+ 1 1)
  (finally (print "cleanup"))) ; => 2, and prints "cleanup"

(try
  (throw (Exception. "boom"))
  (catch \Exception e "recovered")
  (finally (print "cleanup"))) ; => "recovered", and prints "cleanup"
```

A `catch` clause names the exception type and a symbol bound to the caught value. List several clauses to handle types differently; the first matching one wins.

```phel
(try
  (throw (new \InvalidArgumentException "bad input"))
  (catch \InvalidArgumentException e (str "arg error: " (.getMessage e)))
  (catch \Exception e "other error"))
; => "arg error: bad input"
```

## Catching PHP exceptions

Anything PHP can throw, you can catch. Reference the PHP class with a leading backslash (`\Exception`, `\RuntimeException`, `\TypeError`). Read its details with PHP method calls via `.method`.

```phel
(try
  (throw (new \RuntimeException "disk full"))
  (catch \Exception e
    (.getMessage e))) ; => "disk full"
```

`.method` is the PHP method-call form: `(.getMessage e)` is the same as `$e->getMessage()` in PHP. Use it to reach `getCode`, `getFile`, `getLine`, and friends.

{% php_note() %}
Same exceptions, different shape:

```php
// PHP
try {
    throw new \RuntimeException("disk full");
} catch (\Exception $e) {
    echo $e->getMessage();
}
```

```phel
;; Phel
(try
  (throw (new \RuntimeException "disk full"))
  (catch \Exception e (.getMessage e)))
```
{% end %}

## Custom exception types with `defexception`

`defexception` defines a new exception class. Callers can then catch it by type. The parent defaults to `\Exception`; pass another class to extend it:

```phel
(defexception ProductNotFound)
(defexception PaymentFailed \RuntimeException)

(try
  (throw (ProductNotFound. "sku-42"))
  (catch ProductNotFound e
    (str "missing: " (.getMessage e)))) ; => "missing: sku-42"

(try
  (throw (PaymentFailed. "card declined"))
  (catch \RuntimeException e
    (.getMessage e))) ; => "card declined"
```

Use a custom type when callers need to tell your failure apart from others. Use `ex-info` when they need data about it.

## Structured errors with `ex-info`

A message alone is hard for code to act on. `ex-info` builds an exception that carries a data map (and an optional cause), so handlers can branch on machine-readable context instead of parsing strings.

<!-- phel-test: skip -->
```phel
(ex-info message data)
(ex-info message data cause)
```

<!-- phel-test: skip -->
```phel
(throw (ex-info "User not found" {:user-id 42 :status 404}))
```

Read the parts back with `ex-message`, `ex-data`, and `ex-cause`:

```phel
(def err (ex-info "Validation failed" {:field :email :reason "invalid format"}))

(ex-message err) ; => "Validation failed"
(ex-data err)    ; => {:field :email :reason "invalid format"}
(ex-cause err)   ; => nil (no cause provided)
```

### Branching on data

```phel
(try
  (throw (ex-info "User not found" {:status 404}))
  (catch \Exception e
    (case (:status (ex-data e))
      404 "not found"
      403 "forbidden"
      "unknown error"))) ; => "not found"
```

### Chaining a cause

Pass the original exception as the third argument to keep the failure trail. Read it back with `ex-cause`.

```phel
(try
  (try
    (throw (new \Exception "io fail"))
    (catch \Exception e
      (throw (ex-info "save failed" {:op :save} e))))
  (catch \Exception e
    (str (ex-message e) " <- " (ex-message (ex-cause e)))))
; => "save failed <- io fail"
```

{% clojure_note() %}
`ex-info`, `ex-data`, `ex-message`, and `ex-cause` work as in Clojure. The underlying object is a PHP exception, so `catch \Exception` also catches `ex-info` values.
{% end %}

## When to throw vs return nil

Throw for exceptional situations. For ordinary "no result" cases, return `nil`. Callers can then use [`if-let`, `when-let`](/documentation/language/control-flow/#when-if-not-and-binding-conditionals), or a default.

- **Return `nil`** when absence is expected and the caller can handle it: a lookup miss, an empty parse, an optional field.
- **Throw** when continuing would be a bug or the caller cannot reasonably proceed: invalid arguments, broken invariants, failed I/O.

```phel
;; Expected miss: return nil, let the caller decide
(defn find-user [users id]
  (get users id)) ; nil when not present

;; Real failure: throw with context
(defn charge-card [amount]
  (when (<= amount 0)
    (throw (ex-info "Invalid charge amount" {:amount amount})))
  amount)

(find-user {} 42)   ; => nil
(charge-card 10)    ; => 10
```

## Next steps

- [Namespaces](/documentation/language/namespaces/) - split code across files and require it
- [Control flow](/documentation/language/control-flow/) - `if`, `cond`, and `case` for handling results
- [Basic types](/documentation/language/basic-types/) - why only `false` and `nil` are falsy
- [Cheat sheet](/documentation/reference/cheat-sheet/) - keep it open while coding
