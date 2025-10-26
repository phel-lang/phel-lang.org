+++
title = "Immutability in Phel: Why Your Data Should Never Change"
aliases = [ "/blog/immutability" ]
description = "See how persistent data structures make day-to-day Phel code predictable, testable, and side-effect free."
+++

Phel lives and breathes persistent data structures. Instead of rewriting values in place, every operation hands you a fresh value that still shares most of the old data. It is the easiest way to keep code honest in a functional world.

If you're coming from PHP's mutable arrays and objects, that default can feel strange at first. Stick with it: the pay-off is code that is easier to reason about, simpler to test, and naturally safe when things happen in parallel.

## Goodbye in-place updates

[Vectors](/documentation/data-structures/#vectors), [maps](/documentation/data-structures/#maps), [sets](/documentation/data-structures/#sets), [lists](/documentation/data-structures/#lists), and [structs](/documentation/data-structures/#structs) in Phel never mutate. Helpers such as `push` and `put` hand you the updated collection while the original stays exactly the same.

```phel
(def groceries [:milk :bread])
(def extended (push groceries :apples))

groceries
=> [:milk :bread]

extended
=> [:milk :bread :apples]
```

Because `groceries` never changes, any function that already received it can keep using it without worrying about sneaky side effects. Maps behave the same way:

```phel
(def customer {:id 42 :name "Ada"})
(let [with-email (put customer :email "ada@example.com")]
  [customer with-email])
=> [{:id 42 :name "Ada"}
    {:id 42 :name "Ada" :email "ada@example.com"}]
```

`put` returns a new map that shares everything it can with the original. The copy is cheap thanks to structural sharing, Phel only allocates the path that actually changed.

## Benefits of data that never changes

- **Predictable functions**: With immutable inputs, the only variable is the arguments themselves. This guarantees referential transparency and eliminates hidden, hard-to-trace bugs.
- **Stress-free tests**: Testing pure functions is effortless, just pass in data, check the result, and forget about mocking or side effects.
- **Simpler debugging**: Log it once, and it stays true, no sneaky mutations hiding under your traces.
- **Effortless concurrency**: Pass the same data between async jobs without race conditions or surprises.

## Transforming data in steps

Immutability pairs nicely with Phel's pipeline-friendly tools. Each step receives a value, returns a fresh one, and the original remains available for whatever comes next.

```phel
(def scores [10 18 21 7])

(->> scores
     (filter |(>= $ 15))
     (map |(- $ 10))
     (reduce + 0))
=> 19

scores
=> [10 18 21 7]
```

The vector `scores` is still the same after the reduction, ready to reuse later. That makes it trivial to layer different views over the same base data.

## Managing change at the edges

Real programs still need to talk to the outside world; databases, APIs, the filesystem. Immutability doesn’t stop that; it just asks you to keep those side effects in their own little corner.

Do your updates at clear entry points, turn any external data into immutable Phel values, and let the rest of your code run safely on pure data. When you need a new version, use helpers like `put-in`, `unset`, or `push`, and pass the new value forward instead of mutating it.

Once you stop changing data in place, life gets simpler: there’s the value you got, and the value you return. **That's it**. Everything else becomes easier to trust, and that’s why, in Phel, your data never changes.
