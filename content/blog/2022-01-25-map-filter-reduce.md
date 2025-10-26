+++
title = "Map, Filter, Reduce: Your First Functional Toolkit in Phel"
description = "A quick tour of map, filter, and reduce in Phel with easy examples you can paste into the REPL."
+++

Phel, as many other functional programming languages, comes with three basic tools you should learn right from the beginning:

Map, filter and reduce.

- Map transforms one sequence into another sequence of the same length.
- Filter removes elements from a sequence depending on some predicate function.
- Reduce takes a sequence of elements and aggregates it into some value.

Let's see them in action:

The map function takes two arguments. The first argument is a one-argument function that transforms a single value. The second argument is the sequence that should be transformed.

```phel
# Increment by 1
(map inc [1 2 3]) # evaluates to [2 3 4]
# Multiply by 2 using fn syntax
(map (fn [x] (* 2 x)) [1 2 3]) # evaluates to [2 4 6]
# Multiply by 2 using fn shorthand syntax
(map |(* 2 $) [1 2 3]) # evaluates to [2 4 6]
```

The filter function takes two arguments. The first argument is a one-argument function that returns true if it should keep the value in the list. The second argument is the sequence that should be filtered.

```phel
# keep even numbers
(filter even? [1 2 3]) # evaluates to [2]
# keep odd numbers
(filter odd? [1 2 3]) # evaluates to [1 3]
# keep number bigger 2
(filter |(> $ 2) [1 2 3]) # evaluates to [3]
```

The reduce function takes three arguments. The first argument is a two-argument function (accumulated value and sequence value) that return a new accumulated value. The second argument is the initial accumulated value and the third argument is the sequence that should be reduced.

```phel
# sum all value starting by 0
(reduce + 0 [1 2 3]) # evaluates to 6
# sum all values use first value as starting point
(reduce (fn [acc x] (* acc x)) 1 [2 3 4]) # evaluates to 24
# concat all numbers to a string
(reduce str "" [1 2 3]) # evaluates to "123"
```
