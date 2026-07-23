+++
title = "Async & Concurrency"
weight = 16
description = "Run work concurrently with Phel's two fiber-based layers: top-level promises and futures, plus an AMPHP event loop for timers, IO, and fan-out"

[extra]
difficulty = "advanced"
+++

Phel ships two concurrency layers built on PHP fibers. Both let independent work overlap on a single thread; neither parallelizes CPU work across cores. Pick the layer that matches your context, then reach for the [async API reference](/documentation/reference/api/async/) for the full signature of every function.

Most primitives live in `phel.core` and need no require. The one exception is `delay`, which lives in `phel.async`.

## The two layers

**Fiber layer** - a cooperative scheduler with no event loop (`\Phel\Fiber\FiberFacade`). Safe at the top level of a script or the REPL. Functions: `promise`, `deliver`, `future-call`, `future-fiber`, `future?`. Good for CPU coordination, producer/consumer handoffs, and a lightweight `deref`-with-timeout.

**AMPHP layer** - built on `amphp/amp`; an event loop drives fibers, timers, and IO combinators. Functions: `async`, `await`, `await-all`, `await-any`, `pmap`, `future`, `future-cancel`, `future-cancelled?` (in `phel.core`), plus `delay` (in `phel.async`). Use it for timers, IO multiplexing, fan-out across many futures, or when mixing AMPHP-based libraries (HTTP clients, servers).

Rule of thumb: the fiber layer for plain scripts, the AMPHP layer when timers, IO, or AMPHP code are involved.

| Need | Use |
|------|-----|
| Top-level script, no event loop | fiber layer (`future-fiber`, `promise`, `deliver`) |
| CPU coordination, producer/consumer handoff | fiber layer |
| IO parallelism, timers, fan-out | AMPHP layer (`async`, `delay`, `await-all`, `await-any`) |
| Mixing AMPHP-based libs | AMPHP layer |

## Fiber layer

### Promises

`(promise)` returns an unrealized promise. `deliver` sets its value exactly once; later deliveries are no-ops and return `nil`, so the first writer wins without a lock. `deref` (or `@`) blocks until a value is available.

```phel
(let [p (promise)]
  (deliver p 1)
  (deliver p 2) ; no-op, p is already realized
  @p)           ; => 1
```

### Running work in a fiber

`future-call` runs a zero-arg function in a new fiber and returns a future you can `deref`. `future-fiber` is a macro wrapper so you can write a body directly, with no outer `async` block:

```phel
@(future-fiber (+ 40 2)) ; => 42
```

Use `future?` to test whether a value is a future from either layer; `deref`, `realized?`, and `future-done?` then dispatch to the right layer at runtime.

## AMPHP layer

### `async` and `await`

`(async body...)` schedules `body` on the event loop in a fresh fiber and returns an `Amp\Future`. The loop is managed automatically (Amp v3), so there is no explicit `Loop::run`. Captured dynamic `binding`s are reinstalled inside the fiber, as with Clojure's `future`.

`(await future)` blocks the current fiber until the future resolves, then returns its value. It must be called from inside a fiber, and accepts either a `Future` wrapper or a bare `Amp\Future`.

```phel
(await (async (+ 1 2))) ; => 3
```

### `delay`

`(delay seconds)` suspends for `seconds` via `Amp\delay`. At the top level it behaves like `php/sleep`; inside an `async`/`future` body it suspends only the current fiber and becomes cancellable.

> **Not Clojure's `delay`.** `clojure.core/delay` is a lazy-thunk wrapper, not a sleep. Phel keeps `delay` in `phel.async` (not `phel.core`) so the difference stays visible to portable `.cljc` code.

```phel
(ns example.delay
  (:require phel.async :refer [delay]))

(await (async (delay 0.05) :done)) ; => :done
```

### `await-all` and `await-any`

`(await-all futures)` awaits every future and collects their resolved values; if any fails, the exception propagates. `(await-any futures)` returns the value of the first future to resolve - losing futures are not auto-cancelled, so pair it with `future-cancel` when you need to stop the others.

```phel
(await-all [(async (* 2 3)) (async (* 4 5))]) ; => [6 20]
```

### `pmap`

`(pmap f coll & colls)` is a concurrent `map` over fibers, with results in input order; multiple collections stop at the shortest. It overlaps IO-bound work but does **not** speed up CPU-bound work - PHP fibers share one thread. (ClojureScript and Basilisp use the same single-threaded model; `clojure.core/pmap` uses a thread pool.)

```phel
(pmap (fn [x] (* x x)) [1 2 3 4]) ; => [1 4 9 16]
```

### `future` and cancellation

`(future body...)` wraps `body` in an `Amp\Future` and returns a wrapper supporting `deref`, `realized?`, 3-arg `deref` timeouts, `future-cancel`, `future-cancelled?`, and `future-done?`. It needs a fiber context, so call it inside `async`.

`future-cancel` is cooperative: the body runs until its next cancellation-aware checkpoint, after which any `deref` throws `Amp\CancelledException` (the 3-arg form returns its fallback instead). `future-cancelled?` reports whether `future-cancel` was called; use `future-done?` for terminal state.

```phel
(ns example.future-timeout
  (:require phel.async :refer [delay]))

(await (async
  (let [f (future (do (delay 0.1) 99))]
    (deref f 50 :timeout)))) ; => :timeout (the future is still running)
```

### `^:async` on `defn`

Tagging a `defn` with `^:async` wraps each arity's body in `(async ...)`, so the function returns an `Amp\Future` for callers to `await`. `^{:async false}` opts out without removing the key.

```phel skip
(defn ^:async fetch [url]
  (await (http-get url)))

(await (fetch "https://example.com"))
```

## Shared primitives

`deref` dispatches to the right layer at runtime:

| Form | Behavior |
|------|----------|
| `(deref x)` / `@x` | Block until realized. Fiber path suspends cooperatively; AMPHP path awaits via the event loop. |
| `(deref x timeout-ms timeout-val)` | Return `timeout-val` if not realized within `timeout-ms`. |
| `(realized? x)` | `true` once a value is available. Works for promises, fiber futures, and `Future`. |
| `(future-done? x)` | Terminal state, including cancellation - not just "value present". |

## Error and cancellation model

- **AMPHP path** - exceptions in a `future`/`async` body surface from `await`/`deref`. Cancellation uses `Amp\DeferredCancellation`; after `future-cancel`, `deref` raises `Amp\CancelledException` and the 3-arg form returns its fallback.
- **Fiber path** - exceptions in `future-call` bodies re-raise on `deref`. `future-cancel` flips a flag checked at cooperative checkpoints; the 3-arg `deref` returns its fallback without waiting.
- **`deliver` is idempotent** - the first call wins, and the return value tells you whether you set it. Useful for lock-free "first writer wins" handoffs.

## Interop

- `->closure` converts a Phel function to a PHP `\Closure`. Many PHP libraries (AMPHP, ReactPHP) type-hint `\Closure` and reject Phel's `AbstractFn`, so wrap before passing a Phel fn.
- Bare `Amp\Future` values from AMPHP libraries pass straight to `await`, `await-all`, and `await-any` - no wrapping needed.
- To feed a fiber-layer result into AMPHP code, `deref` it inside an `async` block: `(async (use-value @(future-fiber ...)))`.

## Pitfalls

- **`future` outside an event loop** needs a fiber context; use `future-fiber` for top-level scripts.
- **Mixing future types** - `future?` is the safe predicate; `deref`, `realized?`, and `future-done?` dispatch by type.
- **CPU-bound `pmap`** - fibers share one thread, so CPU-heavy work gains nothing and may add overhead. Shell out to worker processes for real parallelism.
- **Blocking PHP calls inside fibers** (`sleep`, `usleep`, synchronous `curl`, blocking socket reads) freeze the scheduler. Use `delay` or non-blocking IO instead.

## Recipes

### Producer / consumer via `promise`

A promise hands a value from one fiber to another. The consumer blocks on `@inbox` until the producer delivers.

```phel
(ns example.producer)

(let [inbox (promise)]
  (future-call (fn [] (deliver inbox {:event :ready})))
  (println "got:" @inbox)) ; prints: got: {:event :ready}
```

### Fan-out, fan-in with `await-all`

Launch several `async` branches, then `await-all` them at once. Wall time tracks the slowest branch, not the sum.

```phel
(ns example.fanout
  (:require phel.async :refer [delay]))

(defn fetch [label ms]
  (async
    (delay (php/fdiv ms 1000))
    (str label ":" ms)))

(println (await-all [(fetch :eu 80) (fetch :us 40) (fetch :asia 60)]))
```

### Timeout with 3-arg `deref`

With no producer wired up, the `deref` deadline expires and returns the fallback.

```phel
(ns example.timeout)

(let [p (promise)]
  (println (deref p 25 :timed-out))) ; prints: :timed-out
```

### Cancel on first error

When one branch fails, cancel its sibling. `future-cancel` is cooperative - `slow` finishes its current step before observing the cancellation, after which any `deref` on it throws `Amp\CancelledException`.

```phel
(ns example.cancel-on-error
  (:require phel.async :refer [delay]))

(defn launch []
  (async
    (let [slow (future (do (delay 0.2) :slow))
          fast (future (do (delay 0.05)
                           (throw (php/new \RuntimeException "boom"))))]
      (try
        (await fast)
        (catch \RuntimeException e
          (future-cancel slow)
          (str "cancelled after: " (php/-> e (getMessage))))))))

(println (await (launch))) ; prints: cancelled after: boom
```

## Next steps

- [Async API reference](/documentation/reference/api/async/) - every function with its full signature
- [Error handling](/documentation/language/error-handling/) - `try`/`catch` around awaited work
- [PHP interop](/documentation/php-interop/) - calling AMPHP and other PHP libraries from Phel
