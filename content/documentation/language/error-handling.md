+++
title = "Error Handling"
weight = 9
description = "Throw and catch exceptions, attach data with ex-info, define exception types, and decide when to throw vs return nil."
aliases = ["/documentation/error-handling", "/documentation/exceptions"]

[extra]
difficulty = "intermediate"
+++

After this page you can raise errors with `throw`, handle them with `try`/`catch`/`finally`, and attach data to an error with `ex-info`. Phel uses PHP exceptions, so any PHP `Throwable` works here.

{% <callout kind="note"> %}
Looking for a `[PHEL...]` compiler error code? See the [Error Reference](/documentation/reference/errors/).
{% </callout> %}

<a id="try-catch-finally"></a><a id="throwing"></a>

## Throw and catch

`throw` raises any value that implements PHP's `Throwable`. `try` runs its body and returns the last value. When the body throws, the first `catch` clause whose type matches handles it, and its value becomes the result:

```phel
(try
  (throw (InvalidArgumentException. "bad input"))
  (catch \InvalidArgumentException e (str "arg error: " (.getMessage e)))
  (catch \Exception e "other error"))
; => "arg error: bad input"
```

Each `catch` names a class and a symbol bound to the exception. `(.getMessage e)` is `$e->getMessage()` in PHP; `getCode`, `getFile` and `getLine` work the same way.

A `finally` clause always runs last, whether the body threw or not. Use it for cleanup. Its value is ignored:

```phel
(try
  (throw (Exception. "boom"))
  (catch \Exception e "recovered")
  (finally (print "cleanup"))) ; => "recovered", and prints "cleanup"
```

{% <php_note> %}
Same exceptions, different shape:

```php
try {
    throw new \RuntimeException("disk full");
} catch (\Exception $e) {
    echo $e->getMessage();
}
```

```phel
(try
  (throw (RuntimeException. "disk full"))
  (catch \Exception e (.getMessage e)))
```
{% </php_note> %}

## Attach data with `ex-info`

A message alone is hard for code to act on. `ex-info` builds an exception that carries a data map, so a handler can branch on data instead of parsing strings:

```phel
(try
  (throw (ex-info "User not found" {:user-id 42 :status 404}))
  (catch \Exception e
    (case (:status (ex-data e))
      404 "not found"
      403 "forbidden"
      "unknown error"))) ; => "not found"
```

Read the parts back with `ex-message`, `ex-data` and `ex-cause`:

```phel
(def err (ex-info "Validation failed" {:field :email}))

(ex-message err) ; => "Validation failed"
(ex-data err)    ; => {:field :email}
(ex-cause err)   ; => nil
```

### Keep the cause

Pass the original exception as a third argument to keep the failure trail:

```phel
(try
  (try
    (throw (Exception. "io fail"))
    (catch \Exception e
      (throw (ex-info "save failed" {:op :save} e))))
  (catch \Exception e
    (str (ex-message e) " <- " (ex-message (ex-cause e)))))
; => "save failed <- io fail"
```

{% <clojure_note> %}
`ex-info`, `ex-data`, `ex-message` and `ex-cause` work as in Clojure. The value is a PHP exception that extends `\Exception`, so `catch \Exception` catches it.
{% </clojure_note> %}

<a id="custom-exception-types-with-defexception"></a>

## Define exception types

`defexception` defines a new exception class. The parent is `\Exception` unless you pass another class:

```phel
(defexception ProductNotFound)
(defexception PaymentFailed \RuntimeException)

(try
  (throw (ProductNotFound "sku-42"))
  (catch ProductNotFound e
    (str "missing: " (.getMessage e)))) ; => "missing: sku-42"

(try
  (throw (PaymentFailed "card declined"))
  (catch \RuntimeException e
    (.getMessage e))) ; => "card declined"
```

Use a custom type when callers need to tell your failure apart from others. Use `ex-info` when they need data about it.

## PHP errors and exceptions

PHP functions throw native exceptions, and they reach Phel unchanged. PHP has two families: `\Exception` and `\Error` (`TypeError`, `DivisionByZeroError`). `catch \Exception` does not catch an `\Error`. Catch `\Throwable` to handle both:

```phel
(try
  (php/intdiv 1 0)
  (catch \Exception e "exception")
  (catch \Throwable e (.getMessage e)))
; => "Division by zero"
```

For more PHP interop, see [PHP Interop](/documentation/language/php-interop/).

## Throw or return nil

Throw when continuing would be a bug or the caller cannot go on: invalid arguments, broken invariants, failed I/O. Return `nil` when absence is expected and the caller can handle it: a lookup miss, an empty parse, an optional field. Callers handle `nil` with [`if-let` or `when-let`](/documentation/language/control-flow/#when-if-not-and-binding-conditionals), or a default.

```phel
(defn find-user [users id]
  (get users id)) ; nil when not present

(defn charge-card [amount]
  (when (<= amount 0)
    (throw (ex-info "Invalid charge amount" {:amount amount})))
  amount)

(find-user {} 42) ; => nil
(charge-card 10)  ; => 10
```
