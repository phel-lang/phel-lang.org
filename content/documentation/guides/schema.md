+++
title = "Schema Validation"
weight = 5
description = "Validate, coerce, and generate data with phel.schema, using plain Phel data as declarative schemas"
+++

`phel.schema` validates, coerces, and generates values from declarative schemas. A schema is plain Phel data, a keyword or a vector, so there is no separate DSL to learn: schemas are built, composed, and stored like any other value.

## Quickstart

A schema describes the shape data should have. `validate` answers yes or no, `explain` tells you what went wrong, and `coerce` reshapes loosely typed input (for example string-keyed request data) into the required types.

```phel
(ns my-app.quickstart
  (:require phel.schema :as s))

(def User
  [:map {:closed true}
   [:id    :int]
   [:email [:re #"^[^@]+@[^@]+$"]]
   [:age   [:maybe :int]]])

(println (s/validate User {:id 1 :email "a@b.co" :age nil}))   ; => true
(println (s/explain  User {:id 1 :email "a@b.co" :age nil}))   ; => nil (conforms)
(println (s/coerce   User {"id" "1" "email" "a@b.co" "age" nil}))
; => {:id 1 :email "a@b.co" :age nil}
```

`[:maybe T]` makes the *value* nilable, but the key is still required to be present. Omit the key on a `{:closed true}` map and validation fails with `:type :missing`.

## Schema kinds

| Kind | Example |
|------|---------|
| scalar | `:int`, `:string`, `:bool`, `:keyword`, `:any` |
| collection | `[:vector :int]`, `[:set :string]`, `[:map-of :keyword :int]` |
| map | `[:map [:k :int] [:k2 :string]]` |
| tuple | `[:tuple :int :string]` |
| choice | `[:enum :a :b]`, `[:or :int :string]`, `[:and :int [:fn pos-int?]]`, `[:maybe :int]` |
| regex | `[:re #"pattern"]` |
| predicate | `[:fn even?]` |
| reference | `[:ref :my/User]` |
| function | `[:=> [:int :int] :int]` |

Because schemas are data, you build a nested shape by nesting vectors:

```phel
(ns my-app.kinds
  (:require phel.schema :as s))

(def Order
  [:map
   [:id    :int]
   [:status [:enum :pending :shipped :done]]
   [:items [:vector [:map [:sku :string] [:qty :int]]]]
   [:tags  [:set :keyword]]])

(println (s/validate Order
  {:id 7
   :status :shipped
   :items [{:sku "A1" :qty 2}]
   :tags #{:rush}}))
; => true
```

## Core operations

| Fn | Purpose |
|----|---------|
| `(validate schema value)` | `true` / `false` |
| `(explain schema value)` | `nil` on success, `{:schema s :value v :errors [...]}` on failure |
| `(human-readable-explain result)` | render an `explain` result as a multi-line string |
| `(coerce schema value)` | reshape loosely typed input into the required types |
| `(conform schema value)` | coerced value, or `:phel.schema/invalid` if it cannot fit |
| `(generate schema)` | a random value conforming to `schema` |

`explain` returns `nil` when the value conforms, so a non-nil result means failure. Each error carries `:path`, `:in`, `:schema`, `:value`, and `:type`. Pass the result to `human-readable-explain` for a printable summary:

```phel
(ns my-app.explain
  (:require phel.schema :as s))

(def result (s/explain :int :oops))
(println (s/human-readable-explain result))
```

`conform` never throws: it returns the coerced value on success or the sentinel `:phel.schema/invalid` on failure, so compare against it (or `s/invalid-marker`) to branch:

```phel
(ns my-app.conform
  (:require phel.schema :as s))

(let [v (s/conform :int "42")]
  (println (if (= v s/invalid-marker) :failed v)))
; => 42
```

## Named-schema registry

Register a schema under a name and refer to it from anywhere with `[:ref name]`. This lets schemas reference each other and keeps large shapes readable.

```phel
(ns my-app.registry
  (:require phel.schema :as s))

(def User
  [:map {:closed true}
   [:id    :int]
   [:email [:re #"^[^@]+@[^@]+$"]]])

(s/register! :my/User User)
(println (s/registered? :my/User))                            ; => true
(println (s/validate [:ref :my/User] {:id 1 :email "a@b.co"})) ; => true
```

`unregister!`, `deref-ref`, and `registered?` round out the registry.

## Function instrumentation

`instrument!` wraps a function so its arguments and return value are checked against a `[:=> [arg-schemas] ret-schema]` schema on every call. It returns the wrapped function (the original is kept so `unstrument!` can restore it):

```phel
(ns my-app.instrument
  (:require phel.schema :as s))

(defn add [a b] (+ a b))
(def add! (s/instrument! :add add [:=> [:int :int] :int]))

(println (add! 2 3)) ; => 5
```

Calling the wrapped function with arguments that fail the schema throws:

```phel skip
(add! "x" 2) ; throws: argument 0 failed schema
```

Toggle checking globally with `set-schema-check!`, inspect it with `schema-check?`, or scope it to a thunk with `with-schema-check`. Disabling checks lets instrumented functions run at full speed in production while staying validated in development.

## Pitfalls

- `:map` is open by default; add `{:closed true}` to reject extra keys. The key is `:closed`, not `:closed?`, and a `?` variant is silently ignored.
- `[:maybe T]` allows a nil value but does not make the key optional; use `{:optional true}` on the map entry for that.
- `[:and ...]` children must be schemas; wrap a bare predicate as `[:fn pred]` (for example `[:fn pos-int?]`, not `pos-int?`).
- `[:re ...]` expects a `#"regex"` literal, or a PCRE string *with* delimiters such as `"/^[0-9]+$/"`; a bare pattern string like `"^[0-9]+$"` silently fails.
- `generate` may fail on over-constrained `[:and ...]` or `[:re ...]` schemas; pass `{:gen <gen-fn>}` in the schema options to override.

## See also

- [Coming from Clojure](/documentation/guides/coming-from-clojure) maps `schema` against the Clojure ecosystem.
- `phel.test.gen` drives property-based testing from the same schemas via `generate` and `schema->gen`.
