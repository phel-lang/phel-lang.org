+++
title = "Interfaces"
weight = 13
+++

Interfaces define contracts — abstract sets of functions that structs must implement. They map directly to PHP interfaces, giving you interop with PHP's type system.

## Defining interfaces

Use `definterface` to declare an interface with one or more methods:

```phel
(definterface Describable
  (describe [this] "Returns a human-readable description."))
```

Each method must have at least one parameter (`this`), which binds to the struct instance at call time. An optional documentation string can follow the parameter list.

You can define multiple methods in a single interface:

```phel
(definterface Shape
  (area [this] "Computes the area of the shape.")
  (perimeter [this] "Computes the perimeter of the shape."))
```

`definterface` also generates callable functions for each method, so you call `(area my-shape)` like any other function.

> **Note:** Unlike PHP interfaces, Phel interfaces cannot extend other interfaces.

## Implementing interfaces with structs

Structs are the only way to implement interfaces in Phel. A struct is a typed map with predefined keys, compiled to a PHP class internally.

Add interface implementations after the field list in `defstruct`:

```phel
(defstruct circle [radius]
  Shape
  (area [this] (* 3.14159 radius radius))
  (perimeter [this] (* 2 3.14159 radius)))

(defstruct rectangle [width height]
  Shape
  (area [this] (* width height))
  (perimeter [this] (* 2 (+ width height))))
```

Struct fields (`radius`, `width`, `height`) are directly accessible inside method bodies — no getter calls needed.

### Calling interface methods

Interface methods are called like regular functions, with the struct as the first argument:

```phel
(area (circle 5))           # => 78.53975
(perimeter (circle 5))      # => 31.4159

(area (rectangle 4 6))      # => 24
(perimeter (rectangle 4 6)) # => 20
```

### Multiple interfaces

A struct can implement multiple interfaces. List each interface followed by its method implementations:

```phel
(definterface Describable
  (describe [this]))

(defstruct circle [radius]
  Shape
  (area [this] (* 3.14159 radius radius))
  (perimeter [this] (* 2 3.14159 radius))
  Describable
  (describe [this]
    (str "Circle with radius " radius)))

(describe (circle 5))  # => "Circle with radius 5"
```

### Calling other methods from within a struct

Use `php/-> this` to call another method on the same struct:

```phel
(definterface HasSummary
  (summary [this]))

(defstruct product [name price]
  Describable
  (describe [this] (str name ": $" price))
  HasSummary
  (summary [this] (str "Product — " (php/-> this (describe)))))
```

### Type checking

Each struct gets an auto-generated predicate:

```phel
(circle? (circle 5))       # => true
(circle? (rectangle 4 6))  # => false
```

## Practical example: a renderer

Interfaces shine when you have different types that share behavior:

```phel
(definterface Renderable
  (render [this]))

(defstruct paragraph [text]
  Renderable
  (render [this] (str "<p>" text "</p>")))

(defstruct heading [level text]
  Renderable
  (render [this] (str "<h" level ">" text "</h" level ">")))

(defstruct image [src alt]
  Renderable
  (render [this] (str "<img src=\"" src "\" alt=\"" alt "\">")))

# Render a page from mixed elements
(let [elements [(heading 1 "Welcome")
                (paragraph "Hello from Phel!")
                (image "/logo.png" "Phel logo")]]
  (->> elements
       (map render)
       (str/join "\n")))
# => "<h1>Welcome</h1>\n<p>Hello from Phel!</p>\n<img src=\"/logo.png\" alt=\"Phel logo\">"
```

## Implementing PHP interfaces

Since Phel interfaces compile to PHP interfaces, structs can also implement any PHP interface:

```phel
(defstruct json-config [data]
  \JsonSerializable
  (jsonSerialize [this] data))
```
