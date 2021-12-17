+++
title = "Interfaces"
weight = 13
+++

## Defining interfaces

An interface in Phel defines an abstract set of functions. It is directly mapped to a PHP interface. An interface can be defined by using the `definterface` macro:

```phel
(definterface MyInterface
  (foo [this] "foo doc string")
  (bar [this a b] "bar doc string"))
```

Each method in the interface has at least one parameter that is bound to the current object (`$this`) later in the implementation. An optional documentation string can be provided as last argument of each method. Additionally, the macro defines functions for each method of the interface.

Compared to PHP interfaces, it is not possible to extend a new interface from another existing interfaces.

## Implementing interfaces

The only way to directly implement interfaces in Phel is to use Structs. To recap the definition from the previous chapter: A Struct is a special kind of Map that supports a predefined number of keys and is associated to a global name. Internally, Phel Structs are PHP classes.

```phel
(defstruct my-struct [a b c]) # Defines the struct my-struct
```

The `defstruct` macro allows additional parameters after the list of fields in order to implement one or more interfaces. First you have to define the name of the interface and afterwards you need to implement all methods of the interface. This can be repeated for all interfaces that the struct should implement. Since Phel interfaces are just PHP interface you can also implement any other PHP interface.

```phel
(definterface MyFirstInterface
  (foo [this])
  (bar [this a b]))

(definterface MySecondInterface
  (foobar [this]))

(defstruct my-type-with-two-interfaces [v]
  MyFirstInterface
  (foo [this] v) # Direct access to the values of the struct
  (bar [this a b] (+ a b v))
  MySecondInterface
  (foobar [this] (php/-> this (foo)))) # Call other functions of the struct
```

The methods on the struct can be called just like any other function in Phel:

```phel
(let [x (my-type-with-two-interfaces 1)]
  (foo x)
  (bar x 1 2)
  (foobar x))
```
