+++
title = "Interfaces"
weight = 12
description = "Define contracts with definterface, implement them in structs, extend existing types with protocols, and dispatch through hierarchies."
aliases = ["/documentation/interfaces"]

[extra]
difficulty = "advanced"
+++

After this page you can define shared behavior for several types. Use an interface when you own the type (a struct), a protocol when you add behavior to a type you do not own, and a hierarchy when multimethods should dispatch on "is a" relations.

| Tool | Works on | Where you implement it |
|---|---|---|
| `definterface` | structs (compiles to a PHP interface) | inside `defstruct` |
| `defprotocol` | any type, structs included | later, with `extend-type` or `extend-protocol` |
| `derive` | namespaced keywords | anywhere, read by `isa?` and multimethods |

## Define and implement an interface

`definterface` lists methods. Each method takes at least `this` and can have a doc string. A struct implements the interface by naming it after its field list, followed by the method bodies. Inside a method, the struct fields are plain locals:

```phel
(definterface Shape
  (area [this] "Computes the area of the shape.")
  (perimeter [this] "Computes the perimeter of the shape."))

(defstruct circle [radius]
  Shape
  (area [this] (* 3.14159 radius radius))
  (perimeter [this] (* 2 3.14159 radius)))

(defstruct rectangle [width height]
  Shape
  (area [this] (* width height))
  (perimeter [this] (* 2 (+ width height))))

(area (circle 5))           ; => 78.53975
(perimeter (rectangle 4 6)) ; => 20
(map area [(circle 1) (rectangle 2 3)]) ; => (3.14159 6)

(circle? (circle 5))        ; => true
(circle? (rectangle 4 6))   ; => false
```

Each method becomes a normal function: call it with the struct first, pass it to `map`, compose it. Each struct also gets a predicate (`circle?`).

{% callout(kind="note") %}
Only structs implement interfaces, and a Phel interface cannot extend another interface. For structs themselves, see [Structs](/documentation/language/data-structures/#structs).
{% end %}

### Several interfaces on one struct

List each interface followed by its methods. To call another interface method on the same struct from inside a method, use the [method-call form](/documentation/language/php-interop/#method-and-property-call) on `this`:

```phel
(definterface Describable
  (describe [this]))

(definterface HasSummary
  (summary [this]))

(defstruct product [name price]
  Describable
  (describe [this] (str name ": $" price))
  HasSummary
  (summary [this] (str "Product - " (.describe this))))

(summary (product "Pen" 2)) ; => "Product - Pen: $2"
```

### PHP interfaces

Phel interfaces compile to PHP interfaces, and a struct can implement any PHP interface the same way:

```phel
(defstruct email [address]
  \Stringable
  (__toString [this] address))

(php/strval (email "ada@example.com")) ; => "ada@example.com"
```

For typed signatures, attributes and `\JsonSerializable` support, see [Typed PHP from Phel definitions](/documentation/web/framework-integration/#typed-php-from-phel-definitions).

## Protocols

A protocol adds functions to types after the fact, without changing them. It works for built-in types and for structs.

`extend-type` implements a protocol for one type. `extend-protocol` implements one protocol for several types at once:

```phel
(defprotocol Printable
  (to-string [this] "Converts the value to a printable string."))

(extend-type :int
  Printable
  (to-string [this] (str "int:" this)))

(extend-protocol Printable
  :string
  (to-string [this] (str "\"" this "\""))

  :boolean
  (to-string [this] (if this "yes" "no")))

(to-string 42)      ; => "int:42"
(to-string "hello") ; => "\"hello\""
(to-string true)    ; => "yes"
```

A struct cannot implement a protocol inside `defstruct`. Use `extend-type` with the struct name:

```phel
(defprotocol Printable
  (to-string [this]))

(defstruct point [x y])

(extend-type point
  Printable
  (to-string [this] (str "(" (:x this) ", " (:y this) ")")))

(to-string (point 1 2)) ; => "(1, 2)"
```

### Check support

`satisfies?` checks a value. `extends?` checks a type keyword such as `:string` or `:int`, and returns `false` for struct types, so use `satisfies?` on an instance there:

```phel
(defprotocol Printable
  (to-string [this]))

(extend-type :int
  Printable
  (to-string [this] (str "int:" this)))

(satisfies? Printable 42)    ; => true
(satisfies? Printable "a")   ; => false
(extends? Printable :int)    ; => true
(extends? Printable :array)  ; => false
```

## Hierarchies

`derive` makes one namespaced keyword a child of another. Multimethods use these relations: a method for a parent handles its children. For multimethod basics, see [Multimethods](/documentation/language/functions-and-recursion/#multimethods).

```phel
(derive :shapes/circle :shapes/shape)
(derive :shapes/rectangle :shapes/shape)

(defmulti draw :type)

(defmethod draw :shapes/shape [s]
  "Drawing a generic shape")

(defmethod draw :shapes/circle [s]
  (str "Drawing a circle with radius " (:radius s)))

(draw {:type :shapes/circle :radius 5})
; => "Drawing a circle with radius 5"

(draw {:type :shapes/rectangle :width 4 :height 3})
; => "Drawing a generic shape"
```

### Query and change the hierarchy

Relations are transitive. `isa?`, `parents`, `ancestors` and `descendants` read them, and `underive` removes one:

```phel
(derive :shapes/rectangle :shapes/shape)
(derive :shapes/square :shapes/rectangle)

(isa? :shapes/square :shapes/shape)     ; => true
(isa? :shapes/shape :shapes/square)     ; => false
(parents :shapes/square)                ; => #{:shapes/rectangle}
(ancestors :shapes/square)              ; => #{:shapes/rectangle :shapes/shape}
(descendants :shapes/shape)             ; => #{:shapes/rectangle :shapes/square}

(underive :shapes/square :shapes/rectangle)
(isa? :shapes/square :shapes/rectangle) ; => false
```

These functions work on one global hierarchy. `(make-hierarchy)` returns the empty hierarchy shape, `{:parents {}, :descendants {}, :ancestors {}}`.
