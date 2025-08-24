+++
title = "Thread-first (->) vs Thread-last (->>)"
+++

Phel includes two handy threading macros that let you express a sequence of transformations in a linear, readable style.

* `->` (**thread-first**): inserts the previous value as the **first argument** of the next form.
* `->>` (**thread-last**): inserts the previous value as the **last argument** of the next form.

## Thread-first `->`

Use `->` when the function you call expects the data as its first argument.

```phel
(-> 5
    (+ 3)   # becomes (+ 5 3)
    (* 2))  # becomes (* 8 2)
=> 16
```

## Thread-last `->>`

Use `->>` when the function expects the data as its last argument. This is common with functions that operate on sequences such as `map`, `filter` or `reduce`.

```phel
(->> [1 2 3 4]
     (map inc)      # becomes (map inc [1 2 3 4])
     (filter odd?)  # becomes (filter odd? [2 3 4 5])
     (reduce +))    # becomes (reduce + [3 5])
=> 8
```

## When to choose which

Pick the macro that matches the position of the data argument in the next form:

* Use `->` for functions like `inc`, `assoc` or your own functions where the data comes first.
* Use `->>` for collection-processing functions where the data comes last.

Knowing the difference keeps your pipelines clear and avoids confusing argument order.
