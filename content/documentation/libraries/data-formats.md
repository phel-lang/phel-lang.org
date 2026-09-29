+++
title = "Data Formats"
weight = 2
description = "Exchange data with other Clojure-aligned runtimes using the eval-free EDN and Transit interchange modules."
aliases = ["/documentation/guides/data-formats"]
+++

After this page you can read and write Phel data as text, for config files or to exchange it with Clojure, Java, or JavaScript. Phel ships two modules for this:

- [`phel.edn`](#phel-edn): [EDN](https://github.com/edn-format/edn), the data subset of Clojure syntax.
- [`phel.transit`](#phel-transit): [Transit](https://github.com/cognitect/transit-format), rich types encoded as JSON.

Neither calls `eval`, so both are safe on untrusted input. Plain JSON goes through `phel.json` instead (see the [Cookbook](/documentation/guides/cookbook/)). Use EDN or Transit when keywords, sets, symbols, or tagged values must survive the round trip.

| Need | Use |
|---|---|
| Config files and other human-edited data, with comments and `#_` | EDN |
| Phel-to-Phel data that looks like source code | EDN |
| Data over HTTP with Clojure, Java, or JavaScript | Transit |
| JSON on the wire that keeps rich types | Transit |

## `phel.edn`

The main use: read a config file into a Phel map, and write data back out.

```phel
(ns my-app.config
  (:require phel.edn :as edn))

(edn/read-string "{:host \"localhost\" :port 4000 :tags #{:web} ; the web node\n}")
; => {:host "localhost", :port 4000, :tags #{:web}}

(edn/write-string {:users [{:name "Alice"} {:name "Bob"}]})
; => "{:users [{:name \"Alice\"} {:name \"Bob\"}]}"

(edn/read-string "(+ 1 2)")
; => (+ 1 2)
```

The last line returns a list, not `3`: reading never evaluates. For a file, read its contents first: `(edn/read-string (php/file_get_contents "config.edn"))`.

### Functions

| Function | Returns |
|---|---|
| `(read-string s)`, `(read-string s opts)` | the first EDN form in `s` |
| `(read-string-all s)`, `(read-string-all s opts)` | every form in `s`, as a vector |
| `(write-string v)` | `v` as an EDN string |
| `(write-string-all xs)` | the values in `xs` as EDN, joined by one space |

### Options

| Key | Value | Effect |
|---|---|---|
| `:readers` | `{tag fn}` | Handlers for tagged values in this call. A tag can be a symbol, keyword, or string. The handler gets the parsed form. |
| `:eof` | any value | Returned when the input is empty, blank, or only comments. Default `nil`. |

```phel
(ns my-app.edn-tags
  (:require phel.edn :as edn))

(edn/read-string "#my.app/Point [1 2]"
                 {:readers {'my.app/Point (fn [[x y]] {:x x :y y})}})
; => {:x 1, :y 2}

(edn/read-string-all "1 2 3")      ; => [1 2 3]
(edn/read-string "" {:eof :no-data}) ; => :no-data
```

### What round-trips

`phel.edn` reads with Phel's own reader and writes with its readable printer, so every value Phel prints readably comes back equal: `nil`, booleans, numbers, strings, characters, keywords, symbols, lists, vectors, maps, sets, and the built-in tags `#uuid`, `#inst`, and `#regex`. Namespaced tags such as `#my.app/Person` read as one tag.

## `phel.transit`

The main use: send Phel data to a Clojure or JavaScript service over HTTP and read its answer, with keywords and sets intact.

```phel
(ns my-app.api
  (:require phel.transit :as transit))

(def wire (transit/write-string {:status :ok :tags #{:a}}))
wire
; => "[\"~#cmap\",[\"~:status\",\"~:ok\",\"~:tags\",[\"~#set\",[\"~:a\"]]]]"

(transit/read-string wire)
; => {:status :ok, :tags #{:a}}

(transit/write-string {"name" "Alice"})
; => "{\"name\":\"Alice\"}"
```

A map with only string keys becomes a plain JSON object. Any other map, including one with keyword keys, becomes a `~#cmap` array.

### Functions

| Function | Returns |
|---|---|
| `(read-string s)`, `(read-string s opts)` | the decoded value |
| `(write-string v)` | `v` encoded as Transit JSON-Verbose |

### Options

| Key | Value | Effect |
|---|---|---|
| `:handlers` | `{tag-string fn}` | Decoder for `["~#tag", rep]` arrays. The handler gets the decoded `rep`. |
| `:default-handler` | `(fn [tag rep] ...)` | Fallback for any unknown tag. Without it, an unknown tag throws `InvalidArgumentException`. |

```phel
(ns my-app.transit-tags
  (:require phel.transit :as transit))

(transit/read-string "[\"~#point\",[1,2]]"
                     {:handlers {"point" (fn [[x y]] {:x x :y y})}})
; => {:x 1, :y 2}

(transit/read-string "[\"~#myapp/Foo\",[1,2]]"
                     {:default-handler (fn [tag rep] [:unknown tag rep])})
; => [:unknown "myapp/Foo" [1 2]]
```

### Type mapping

| Phel | Transit JSON-Verbose |
|---|---|
| `nil` | `null` |
| boolean | `true` / `false` |
| int, float | JSON number |
| string | JSON string; a leading `~`, `^`, or `` ` `` gets an extra `~` |
| keyword | `"~:name"` |
| symbol | `"~$name"` |
| `Phel\Lang\UUID` | `"~u..."` |
| `DateTimeImmutable` | `"~t"` followed by an ISO-8601 date |
| vector | JSON array |
| list | `["~#list", [...]]` |
| set | `["~#set", [...]]` |
| map with string keys | JSON object |
| any other map | `["~#cmap", [k1, v1, k2, v2, ...]]` |

### Limits

- Only JSON-Verbose. The cached-key encoding (`^` markers) and Transit+MessagePack are not supported.
- `write-string` has no `:handlers` option. Convert custom types to supported values before you write them.

Every function and its signature: [edn API reference](/documentation/reference/api/edn/).
