+++
title = "Schema Validation"
weight = 1
description = "Validate, coerce, and generate data with phel.schema, using plain Phel data as declarative schemas"
aliases = ["/documentation/guides/schema"]
+++

After this page you can check incoming data against a schema, turn form strings into typed values, and report what is wrong in a readable way. `phel.schema` ships with Phel. A schema is plain Phel data (a keyword or a vector), so you build, combine, and store schemas like any other value.

## Validate a form

The most common task: a form arrives as a map of strings, and you want typed, checked data. Describe the shape once, then `coerce` the input and `validate` the result:

```phel
(ns my-app.signup
  (:require phel.schema :as s)
  (:require phel.walk :refer [keywordize-keys]))

(def Signup
  [:map {:closed true}
   [:name  [:and :string [:fn #(> (count %) 0)]]]
   [:email [:re #"^[^@]+@[^@]+$"]]
   [:age   {:optional true} :int]])

(def form {"name" "Ada" "email" "ada@example.com" "age" "36"})

(def input (s/coerce Signup (keywordize-keys form)))
input                   ; => {:name "Ada", :email "ada@example.com", :age 36}
(s/validate Signup input) ; => true
```

- `coerce` converts values (`"36"` to `36`), not keys. Form data has string keys, so convert them with `keywordize-keys` first.
- `{:closed true}` rejects keys the schema does not list. Maps are open by default.
- `{:optional true}` on an entry lets the key be missing.

When validation fails, `explain` says where and why. It returns `nil` when the value conforms:

```phel
(ns my-app.signup
  (:require phel.schema :as s))

(def Signup
  [:map {:closed true}
   [:name  :string]
   [:email [:re #"^[^@]+@[^@]+$"]]])

(def result (s/explain Signup {:name "Ada" :email "nope"}))

(get result :errors)
; => [{:path [:map :email], :in [:email], :schema [:re "/^[^@]+@[^@]+\$/"], :value "nope", :type :mismatch}]

(println (s/human-readable-explain result))
; Schema [:map ...] failed for value {:name Ada, :email nope}
;   [:email] -> mismatch: expected [:re /^[^@]+@[^@]+$/], got nope
```

Each error has `:path` (position in the schema), `:in` (position in the value), `:schema`, `:value`, and `:type`. Use `:in` to attach a message to the right form field.

## Schema kinds

| Kind | Example |
|------|---------|
| scalar | `:int`, `:string`, `:bool`, `:keyword`, `:any` |
| collection | `[:vector :int]`, `[:set :string]`, `[:map-of :keyword :int]` |
| map | `[:map [:k :int] [:k2 {:optional true} :string]]` |
| tuple | `[:tuple :int :string]` |
| choice | `[:enum :a :b]`, `[:or :int :string]`, `[:and :int [:fn pos-int?]]`, `[:maybe :int]` |
| regex | `[:re #"pattern"]` |
| predicate | `[:fn even?]` |
| reference | `[:ref :my/User]` |
| function | `[:=> [:int :int] :int]` |

Nest vectors to describe nested data:

```phel
(ns my-app.kinds
  (:require phel.schema :as s))

(def Order
  [:map
   [:id     :int]
   [:status [:enum :pending :shipped :done]]
   [:items  [:vector [:map [:sku :string] [:qty :int]]]]
   [:tags   [:set :keyword]]])

(s/validate Order {:id 7 :status :shipped :items [{:sku "A1" :qty 2}] :tags #{:rush}})
; => true
```

## Operations

| Function | Returns |
|---|---|
| `(validate schema value)` | `true` or `false` |
| `(explain schema value)` | `nil` on success, `{:schema s :value v :errors [...]}` on failure |
| `(human-readable-explain result)` | an `explain` result as a multi-line string |
| `(coerce schema value)` | the value with loose types converted to the schema's types |
| `(conform schema value)` | the coerced value, or `:phel.schema/invalid` |
| `(generate schema)` | a random value that fits the schema |

`conform` never throws. Compare its result with `s/invalid-marker` to branch:

```phel
(ns my-app.conform
  (:require phel.schema :as s))

(s/conform :int "42")                         ; => 42
(= (s/conform :int "x") s/invalid-marker)     ; => true
```

`generate` feeds property-based tests: `phel.test.gen` builds generators from the same schemas with `schema->gen`. See [Testing](/documentation/guides/testing/).

## Named schemas

Register a schema under a name and refer to it with `[:ref name]`. Schemas can then reference each other, and large shapes stay readable:

```phel
(ns my-app.registry
  (:require phel.schema :as s))

(s/register! :my/User
  [:map {:closed true}
   [:id    :int]
   [:email [:re #"^[^@]+@[^@]+$"]]])

(s/registered? :my/User)                        ; => true
(s/validate [:ref :my/User] {:id 1 :email "a@b.co"}) ; => true
```

`unregister!` removes a name, and `deref-ref` returns the schema behind a name.

## Function instrumentation

`instrument!` wraps a function so its arguments and return value are checked on every call against a `[:=> [arg-schemas] ret-schema]` schema. It returns the wrapped function and keeps the original so `unstrument!` can restore it:

```phel
(ns my-app.instrument
  (:require phel.schema :as s))

(defn add [a b] (+ a b))
(def add! (s/instrument! :add add [:=> [:int :int] :int]))

(add! 2 3) ; => 5
```

A call with arguments that fail the schema throws:

<!-- phel-test: skip -->
```phel
(add! "x" 2) ; throws: argument 0 failed schema
```

Turn checks on or off globally with `set-schema-check!`, read the setting with `schema-check?`, or scope it to a function with `with-schema-check`. With checks off, instrumented functions run at full speed in production and stay checked in development.

## Pitfalls

- The map option is `:closed`, not `:closed?`. An unknown option is ignored without a warning.
- `[:maybe T]` allows a `nil` value but still requires the key. Use `{:optional true}` to allow a missing key.
- `[:and ...]` children must be schemas. Wrap a bare predicate: `[:fn pos-int?]`, not `pos-int?`.
- `[:re ...]` expects a `#"regex"` literal, or a PCRE string with delimiters such as `"/^[0-9]+$/"`. A bare pattern like `"^[0-9]+$"` never matches, and PHP prints a `preg_match` warning.
- `generate` can fail on tight `[:and ...]` or `[:re ...]` schemas. Pass `{:gen <gen-fn>}` in the schema options to supply your own generator.

Every function and its signature: [schema API reference](/documentation/reference/api/schema/).
