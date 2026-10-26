+++
title = "Async and Concurrency"
weight = 17
description = "Run work concurrently with Phel's two fiber-based layers: top-level promises and futures, plus an AMPHP event loop for timers, IO, and fan-out."

[extra]
difficulty = "advanced"
+++

Phel builds on PHP fibers: tasks overlap on one thread while they wait for IO or timers. CPU work does not run in parallel across cores. Most functions are in `phel.core`; `delay` is in `phel.async`. Every signature is in the [async API reference](/documentation/reference/api/async/).

## Run tasks concurrently

`async` starts a task and returns a future. `await-all` waits for a list of futures and returns their values in order. Total time follows the slowest task, not the sum:

```phel
(ns example.fanout
  (:require phel.async :refer [delay]))

(defn fetch [region ms]
  (async
    (delay (php/fdiv ms 1000)) ; wait ms milliseconds
    (str region ":" ms)))

(await-all [(fetch "eu" 80) (fetch "us" 40) (fetch "asia" 60)])
; => ["eu:80" "us:40" "asia:60"], after about 80 ms
```

`await` waits for one future. `await-any` returns the value of the first future to finish. It does not cancel the others; use `future-cancel` for that.

`pmap` is a concurrent `map`, with results in input order. It helps IO-bound work only, because fibers share one thread:

```phel
(pmap (fn [x] (* x x)) [1 2 3 4]) ; => [1 4 9 16]
```

{% <clojure_note> %}
`clojure.core/pmap` uses a thread pool. Phel's `pmap` uses fibers on one thread, like ClojureScript and Basilisp.
{% </clojure_note> %}

## AMPHP layer

Everything above runs on the AMPHP event loop (`amphp/amp` v3). Use it for IO, timers, fan-out, and AMPHP libraries such as HTTP clients. The loop runs on its own, so you never call `Loop::run`. Dynamic `binding`s are carried into each `async` task, as with Clojure's `future`.

### `delay`

`(delay seconds)` pauses. At the top level it works like `php/sleep`. Inside an `async` or `future` body it pauses only the current task, and it can be cancelled.

{% <callout kind="note"> %}
**Not Clojure's `delay`.** `clojure.core/delay` wraps a lazy value; it does not sleep. Phel keeps `delay` in `phel.async`, not `phel.core`, so portable `.cljc` code sees the difference.
{% </callout> %}

### `future`, timeouts and cancellation

`(future body...)` runs `body` as a task and returns a future you can `deref`, test with `realized?` and `future-done?`, and cancel with `future-cancel`.

The 3-argument `deref` returns a fallback value when the future is not done within the timeout in milliseconds:

```phel
(ns example.future-timeout
  (:require phel.async :refer [delay]))

(let [f (future (do (delay 0.1) 99))]
  (deref f 50 :timeout)) ; => :timeout (the future keeps running)
```

`future-cancel` is cooperative. The body runs until its next checkpoint (such as `delay`). After that, `deref` throws `Amp\CancelledException`, and the 3-argument `deref` returns its fallback. `future-cancelled?` tells whether `future-cancel` was called. `future-done?` tells whether the future finished in any way, cancellation included.

This example cancels a slow task when a sibling fails:

```phel
(ns example.cancel-on-error
  (:require phel.async :refer [delay]))

(defn launch []
  (async
    (let [slow (future (do (delay 0.2) :slow))
          fast (future (do (delay 0.05)
                           (throw (RuntimeException. "boom"))))]
      (try
        (await fast)
        (catch \RuntimeException e
          (future-cancel slow)
          (str "cancelled after: " (.getMessage e)))))))

(await (launch)) ; => "cancelled after: boom"
```

An exception thrown inside a task comes out of `await` or `deref`, so catch it there. See [Error handling](/documentation/language/error-handling/).

`^:async` on a `defn` wraps each body in `async`, so the function returns a future. `^{:async false}` turns it off without removing the key.

## Fiber layer

This layer uses Phel's own fiber scheduler and no AMPHP event loop. Use it in plain scripts and the REPL, and to hand values between tasks.

`future-fiber` runs its body in a new fiber. `future-call` does the same with a zero-argument function. `deref` (or `@`) waits for the result:

```phel
@(future-fiber (+ 40 2)) ; => 42
```

A promise hands a value from one task to another. `deliver` sets it once. Later calls do nothing and return `nil`, so the first writer wins without a lock. `@` waits until a value arrives:

```phel
(let [inbox (promise)]
  (future-call (fn [] (deliver inbox {:event :ready})))
  @inbox) ; => {:event :ready}
```

An exception inside `future-fiber` or `future-call` is thrown again on `deref`.

## Mixing the layers

`deref` (with or without a timeout), `realized?`, `future-done?`, and `future?` work on both layers. `async` returns a bare `Amp\Future`, so `future?` is `false` for it. `await`, `await-all` and `await-any` accept bare `Amp\Future` values from AMPHP libraries as they are. To use a fiber-layer result in AMPHP code, `deref` it inside an `async` block.

## Pitfalls

- **Blocking PHP calls** inside a task (`sleep`, `usleep`, synchronous `curl`, blocking socket reads) stop every task. Use `delay` and non-blocking IO.
- **CPU-heavy work** gains nothing from fibers. Use worker processes for real parallelism.
- **PHP libraries that expect `\Closure`** (AMPHP, ReactPHP) reject a Phel function. Convert it with `(->closure f)`.
