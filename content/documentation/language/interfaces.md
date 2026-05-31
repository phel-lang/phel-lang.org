+++
title = "Interfaces"
weight = 9
description = "Define contracts with definterface, implement them in structs, extend types with protocols, and dispatch via hierarchies"
aliases = ["/documentation/interfaces"]
+++

Interfaces define contracts: sets of methods that structs implement. They map directly to PHP interfaces. Protocols and hierarchies extend the same idea to types you do not control.

## Defining interfaces

`definterface` declares one or more methods:

```phel
(definterface Describable
  (describe [this] "Returns a human-readable description."))
```

Methods need at least `this`. Optional doc string follows the parameter list.

Multiple methods per interface:

```phel
(definterface Shape
  (area [this] "Computes the area of the shape.")
  (perimeter [this] "Computes the perimeter of the shape."))
```

Generates callable functions per method: `(area my-shape)` works like any function.

> **Note:** Unlike PHP, Phel interfaces don't extend other interfaces.

## Implementing with structs

Only structs implement interfaces. A struct is a typed map with fixed keys, compiled to a PHP class.

Add implementations after the field list in `defstruct`:

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

Struct fields (`radius`, `width`, `height`) are directly accessible inside methods. No getters.

### Calling methods

Like regular functions, struct first:

```phel
(area (circle 5))           ; => 78.53975
(perimeter (circle 5))      ; => 31.4159

(area (rectangle 4 6))      ; => 24
(perimeter (rectangle 4 6)) ; => 20
```

### Multiple interfaces

A struct can implement many. List each followed by its methods:

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

(describe (circle 5))  ; => "Circle with radius 5"
```

### Calling other methods on same struct

Interface dispatch routes through the generated function, not through `this` directly. To call another interface method on the same struct from within a method body, use the PHP method call syntax via `php/-> this`:

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

Each struct gets a predicate:

```phel
(circle? (circle 5))       ; => true
(circle? (rectangle 4 6))  ; => false
```

## Example: a renderer

Interfaces shine when types share behavior:

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

;; Render a page from mixed elements
(let [elements [(heading 1 "Welcome")
                (paragraph "Hello from Phel!")
                (image "/logo.png" "Phel logo")]]
  (->> elements
       (map render)
       (phel.string/join "\n")))
;; => "<h1>Welcome</h1>\n<p>Hello from Phel!</p>\n<img src=\"/logo.png\" alt=\"Phel logo\">"
```

## Implementing PHP interfaces

Phel interfaces compile to PHP interfaces. Structs can implement any PHP interface:

```phel
(defstruct json-config [data]
  \JsonSerializable
  (jsonSerialize [this] data))
```

## Protocols

Protocols extend functions to existing types without modifying them. Unlike interfaces (require `defstruct`), protocols extend to any type after the fact.

### Defining

`defprotocol` defines method signatures:

```phel
(defprotocol Printable
  (to-string [this] "Converts the value to a printable string."))
```

Each method needs `this`. Optional doc string. Multiple methods allowed:

```phel
(defprotocol Measurable
  (width [this] "Returns the width.")
  (height [this] "Returns the height.")
  (dimensions [this] "Returns [width height] as a vector."))
```

### Extending to types

`extend-type` implements a protocol for one type:

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

`extend-protocol` implements one protocol across many types:

```phel
(extend-protocol Printable
  :float
  (to-string [this] (str "float:" this))

  :boolean
  (to-string [this] (if this "true" "false")))

(to-string 3.14)   ; => "float:3.14"
(to-string true)    ; => "true"
```

### Checking

`satisfies?` (value) and `extends?` (type):

```phel
(satisfies? Printable "hello")  ; => true
(satisfies? Printable 42)       ; => true

(extends? Printable :string)    ; => true
(extends? Printable :array)     ; => false
```

### Protocols vs interfaces

- **Interfaces:** when you control the type (structs), compile-time guarantees.
- **Protocols:** add behavior to existing types or types you don't control.

## Hierarchies

Define relationships between types or values. Hierarchies + multimethods enable inheritance-aware dispatch.

### Deriving

`derive` sets parent-child between keywords:

```phel
(derive :circle :shape)
(derive :rectangle :shape)
(derive :square :rectangle)   ; A square is a rectangle
```

### Querying

`isa?`, `parents`, `ancestors`, `descendants`:

```phel
(isa? :circle :shape)         ; => true
(isa? :square :rectangle)     ; => true
(isa? :square :shape)         ; => true (transitive)
(isa? :shape :circle)         ; => false

(parents :square)             ; => #{:rectangle}
(ancestors :square)           ; => #{:rectangle :shape}
(descendants :shape)          ; => #{:circle :rectangle :square}
```

### Removing

`underive`:

```phel
(underive :square :rectangle)
(isa? :square :rectangle)     ; => false
```

### Empty hierarchy maps

`make-hierarchy` creates the empty hierarchy shape. Public `derive`, `underive`, `isa?`, `parents`, `ancestors`, `descendants` operate on the global hierarchy.

```phel
(make-hierarchy)
; => {:parents {}, :descendants {}, :ancestors {}}
```

### Hierarchy-aware multimethod dispatch

Multimethods check the hierarchy for parent matches when dispatching:

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

## Next steps

- [Functions and recursion](/documentation/language/functions-and-recursion/) - multimethods for open dispatch
- [Data structures](/documentation/language/data-structures/) - structs and the maps they build on
