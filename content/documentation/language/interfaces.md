+++
title = "Interfaces"
weight = 11
aliases = ["/documentation/interfaces"]
+++

Interfaces define contracts - abstract sets of functions that structs must implement. They map directly to PHP interfaces, giving you interop with PHP's type system.

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

Struct fields (`radius`, `width`, `height`) are directly accessible inside method bodies - no getter calls needed.

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
  (summary [this] (str "Product - " (php/-> this (describe)))))
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

## Protocols

Protocols provide a flexible way to define a set of functions that can be extended to existing types without modifying them. Unlike interfaces (which require upfront implementation in `defstruct`), protocols can be extended to any type after the fact.

### Defining a protocol

Use `defprotocol` to define a protocol with one or more method signatures:

```phel
(defprotocol Printable
  (to-string [this] "Converts the value to a printable string."))
```

Each method must have at least one parameter (`this`). An optional doc string can follow the parameter list. A protocol can define multiple methods:

```phel
(defprotocol Measurable
  (width [this] "Returns the width.")
  (height [this] "Returns the height.")
  (dimensions [this] "Returns [width height] as a vector."))
```

### Extending protocols to types

Use `extend-type` to implement a protocol for a specific type:

```phel
(extend-type :string
  Printable
  (to-string [this] (str "\"" this "\"")))

(extend-type :int
  Printable
  (to-string [this] (str "int:" this)))

(to-string "hello")  ; => "\"hello\""
(to-string 42)       ; => "int:42"
```

Use `extend-protocol` to implement a single protocol across multiple types at once:

```phel
(extend-protocol Printable
  :float
  (to-string [this] (str "float:" this))

  :bool
  (to-string [this] (if this "true" "false")))

(to-string 3.14)   ; => "float:3.14"
(to-string true)    ; => "true"
```

### Checking protocol support

Use `satisfies?` to check if a value satisfies a protocol, and `extends?` to check if a type extends a protocol:

```phel
(satisfies? Printable "hello")  ; => true
(satisfies? Printable 42)       ; => true

(extends? Printable :string)    ; => true
(extends? Printable :array)     ; => false
```

### When to use protocols vs interfaces

- **Interfaces** are best when you control the type definition (structs) and want compile-time guarantees
- **Protocols** are best when you need to add behavior to existing types or types you don't control

## Hierarchies

Phel provides a hierarchy system for defining relationships between types or values. Hierarchies work with multimethods to enable inheritance-aware dispatch.

### Deriving relationships

Use `derive` to establish parent-child relationships between keywords:

```phel
(derive :circle :shape)
(derive :rectangle :shape)
(derive :square :rectangle)   ; A square is a rectangle
```

### Querying hierarchies

Use `isa?`, `parents`, `ancestors`, and `descendants` to query the hierarchy:

```phel
(isa? :circle :shape)         ; => true
(isa? :square :rectangle)     ; => true
(isa? :square :shape)         ; => true (transitive)
(isa? :shape :circle)         ; => false

(parents :square)             ; => #{:rectangle}
(ancestors :square)           ; => #{:rectangle :shape}
(descendants :shape)          ; => #{:circle :rectangle :square}
```

### Removing relationships

Use `underive` to remove a parent-child relationship:

```phel
(underive :square :rectangle)
(isa? :square :rectangle)     ; => false
```

### Custom hierarchies

By default, `derive` and friends use a global hierarchy. Use `make-hierarchy` to create an isolated hierarchy and pass it explicitly:

```phel
(def animal-h (make-hierarchy))
(def animal-h (derive animal-h :dog :animal))
(def animal-h (derive animal-h :cat :animal))
(def animal-h (derive animal-h :poodle :dog))

(isa? animal-h :poodle :animal)  ; => true
(descendants animal-h :animal)   ; => #{:dog :cat :poodle}
```

### Hierarchy-aware multimethod dispatch

Hierarchies integrate with multimethods. When a multimethod dispatches on a value, it checks the hierarchy for parent matches:

```phel
(derive :circle :shape)
(derive :rectangle :shape)

(defmulti draw :type)

(defmethod draw :shape [s]
  (str "Drawing a generic shape"))

(defmethod draw :circle [s]
  (str "Drawing a circle with radius " (:radius s)))

(draw {:type :circle :radius 5})
; => "Drawing a circle with radius 5"

(draw {:type :rectangle :width 4 :height 3})
; => "Drawing a generic shape" (falls back to :shape via hierarchy)
```
