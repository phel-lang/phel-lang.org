+++
title = "Loop and Recur: Tail-Recursive Iteration Made Easy"
aliases = [ "/blog/loop" ]
description = "Learn how Phel's loop and recur forms give you tail-recursive iteration without losing readability."
+++

Many functional programming models prefer to express repetition by **recursive** function calls.

In Phel's iteration process, there is a highly functional and convenient `for` macro, but there is also a `loop` special form that performs more primitive loop processing.

[Loop Document | Control flow | The Phel Language](https://phel-lang.org/documentation/control-flow/#loop)

Phel's `loop` allows you to write repetitive processing just like a recursive function.

```phel
(loop [sum 0
       cnt 10]
  (if (= cnt 0)
    sum
    (recur (+ cnt sum) (dec cnt))))
=> 55
```

When the same function is written as a recursive function, it looks like this:

```phel
(defn my-sum-to-n [sum cnt]
  (if (= cnt 0)
    sum
    (my-sum-to-n (+ cnt sum) (dec cnt))))
(my-sum-to-n 0 10)
=> 55
```

There are some differences between the `loop` format and the recursive function format.
When calling as recursion, the `loop` format specifies `recur`, but the recursive function format specifies its own function name `my-sum-to-n`.
For everything else, you can see that you can use `loop` to write iterations in the same way you would write recursive functions.

However, there is one more thing to keep in mind.
The recursive structure of `loop` must be _**tail recursive**_.
This means that `recur` can only be placed at the location where it is evaluated last in the iteration process within `loop`.
If you try to place `recur` in any other location, the following error will be displayed:

```
ERROR: Can't call 'recur here
```

If this error is displayed, please check whether the recursive structure of `loop` is tail recursive.

> Note: _**Tail recursion** is a recursive function where the recursive call is the final action in the function. This means the function has nothing else to do after the recursive call, which makes it possible for the compiler or interpreter to optimize the function by transforming it into a loop._
