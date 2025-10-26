+++
title = "Immutability in Phel: Why Your Data Should Never Change"
aliases = [ "/blog/immutability" ]
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

`put` returns a new map that shares everything it can with the original. The copy is cheap thanks to structural sharing—Phel only allocates the path that actually changed.

## Benefits of data that never moves

- **Predictable functions**: When inputs stay frozen, the only way to change results is to change the arguments. That keeps functions referentially transparent and stops ghost bugs.
- **Stress-free tests**: Pure functions are a breeze to unit test—you pass data in, check the value coming out, and never mock hidden state changes.
- **Simpler debugging**: Log a value once and you can trust it forever. Nothing mutates underneath any traces.
- **Effortless concurrency**: Share the same collection between threads or async tasks with zero drama.

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

Of course, real systems still have to talk to databases, HTTP clients, or the filesystem. Immutability just asks you to fence off those effectful bits. Keep updates at well-defined entry points, translate external state into immutable Phel values, and let the rest of your code cruise on pure data. When you do need a new version, reach for helpers like `put-in`, `unset`, or `push` and pass the result forward.

Once you stop mutating data in place, your mental load shrinks: there's the value you received, and the value you return. **That's it**. Everything else becomes easier to trust — and that's why, in Phel, your data should never change.
