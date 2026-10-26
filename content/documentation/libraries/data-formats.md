+++
title = "Data Formats"
weight = 2
description = "Exchange data with other Clojure-aligned runtimes using the eval-free EDN and Transit interchange modules."
aliases = ["/documentation/guides/data-formats"]
+++

Phel ships two modules that read and write Phel data as text:

- [`phel.edn`](#phel-edn): [EDN](https://github.com/edn-format/edn), the data subset of Clojure syntax. Use it for config files and other human-edited data, with comments and `#_`.
- [`phel.transit`](#phel-transit): [Transit](https://github.com/cognitect/transit-format), rich types encoded as JSON. Use it to exchange data over HTTP with Clojure, Java, or JavaScript.

Neither calls `eval`, so both are safe on untrusted input. Plain JSON goes through `phel.json` instead (see the [Cookbook](/documentation/guides/cookbook/)). Use EDN or Transit when keywords, sets, symbols, or tagged values must survive the round trip.

## `phel.edn`

Read a config file into a Phel map, and write data back out:

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

`read-string-all` reads every form into a vector. Pass `:readers` to handle tagged values, and `:eof` for the value to return on empty input:

```phel
(ns my-app.edn-tags
  (:require phel.edn :as edn))

(edn/read-string "#my.app/Point [1 2]"
                 {:readers {'my.app/Point (fn [[x y]] {:x x :y y})}})
; => {:x 1, :y 2}

(edn/read-string-all "1 2 3")      ; => [1 2 3]
(edn/read-string "" {:eof :no-data}) ; => :no-data
```

Every value Phel prints readably round-trips, including the built-in tags `#uuid`, `#inst`, and `#regex`.

## `phel.transit`

Send Phel data to a Clojure or JavaScript service and read its answer, with keywords and sets intact:

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

Pass `:handlers` to decode `["~#tag", rep]` arrays, and `:default-handler` for unknown tags (without it, an unknown tag throws):

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

Only Transit JSON-Verbose is supported, and `write-string` takes no handlers: convert custom types to supported values before you write them.

Every function and its signature: [edn](/documentation/reference/api/edn/) and [transit](/documentation/reference/api/transit/) API reference.
