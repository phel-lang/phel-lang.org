+++
title = "Cookbook"
weight = 4
description = "Runnable Phel recipes grouped by task: collections, files, JSON, HTTP, dates, and CLI tools."
aliases = ["/documentation/cookbook", "/documentation/one-liners", "/documentation/guides/one-liners", "/documentation/reference/one-liners"]
+++

Copy a recipe, change the data, and run it. For the concepts behind the code, see the [Language](/documentation/language/) section.

## Collections

### Transform a list of records

```phel
(ns cookbook.pipeline
  (:require phel.string :as str))

(def users
  [{:name "Alice"   :age 32 :role "engineer" :active true}
   {:name "Bob"     :age 28 :role "designer" :active false}
   {:name "Charlie" :age 45 :role "engineer" :active true}
   {:name "Diana"   :age 35 :role "manager"  :active true}])

(def by-role
  (->> users
       (filter :active)
       (map #(update % :name str/upper-case))
       (sort-by :age)
       (group-by :role)))
; => {"engineer" [{:name "ALICE" ...} {:name "CHARLIE" ...}], "manager" [{:name "DIANA" ...}]}

(foreach [role members by-role]
  (println role (count members)))
; prints: engineer 2, manager 1

(->> users (map :role) (into #{}))   ; => #{"engineer" "designer" "manager"}
```

`->>` reads top to bottom, one step per line. `foreach` with three bindings walks a map as key and value.

### Aggregate without intermediate collections

```phel
(ns cookbook.transducers)

(def events
  [{:type :page-view :path "/"          :ms 12}
   {:type :api-call  :path "/api/users" :ms 230}
   {:type :api-call  :path "/api/users" :ms 180}
   {:type :api-call  :path "/api/posts" :ms 340}
   {:type :page-view :path "/about"     :ms 9}])

(def slow-api-paths
  (comp (filter #(= :api-call (:type %)))
        (filter #(> (:ms %) 150))
        (map :path)))

(into [] slow-api-paths events)   ; => ["/api/users" "/api/users" "/api/posts"]
(into #{} slow-api-paths events)  ; => #{"/api/users" "/api/posts"}
(transduce slow-api-paths (completing (fn [n _] (inc n))) 0 events)   ; => 3
(transduce (map :ms) max 0 events)                                   ; => 340
```

A transducer is a pipeline you define once and apply to any target (`into`, `transduce`, `sequence`). No lazy sequence is built between steps, which matters for large inputs. See [Transducers](/documentation/language/transducers).

## Files

### Read and write files

```phel
(ns cookbook.files)

(def dir (str (php/sys_get_temp_dir) "/phel-cookbook"))
(when-not (php/is_dir dir) (php/mkdir dir 0755 true))
(def path (str dir "/notes.txt"))

(spit path "first line\n")
(spit path "second line\n" {:flags php/FILE_APPEND})
(slurp path)               ; => "first line\nsecond line\n"
(into [] (line-seq path))  ; => ["first line" "second line"]
(php/file_exists path)     ; => true

(->> (file-seq dir)
     (filter #(php/str_ends_with % ".txt"))
     (into []))            ; => [".../phel-cookbook/notes.txt"]
```

`slurp` and `spit` wrap `file_get_contents` and `file_put_contents`. `line-seq` and `file-seq` are lazy, so they also work on files and trees too big for memory. `spit` does not create directories: create them first.

### Read a CSV file into maps

```phel
(ns cookbook.csv)

(def path (str (php/sys_get_temp_dir) "/users.csv"))
(spit path "name,email,role\nAlice,alice@example.com,admin\nBob,bob@example.com,editor\n")

(defn read-csv [path]
  (let [[header & rows] (csv-seq path)
        ks (map keyword header)]
    (mapv #(zipmap ks %) rows)))

(read-csv path)
; => [{:name "Alice", :email "alice@example.com", :role "admin"}
;     {:name "Bob", :email "bob@example.com", :role "editor"}]
```

`csv-seq` reads rows lazily as vectors of strings. Zipping each row with the header row gives you maps with keyword keys.

### Store data in a JSON file

```phel
(ns cookbook.kv-store
  (:require phel.json :as json))

(def store-path (str (php/sys_get_temp_dir) "/phel-store.json"))
(spit store-path "{}")

(defn store-load []
  (if (php/file_exists store-path)
    (json/decode (slurp store-path))
    {}))

(defn store-update! [f & args]
  (spit store-path (json/encode (apply f (store-load) args))))

(store-update! assoc :theme "dark")
(store-update! assoc :lang "phel")
(store-update! dissoc :lang)
(store-load)   ; => {:theme "dark"}
```

`store-update!` takes any map function (`assoc`, `dissoc`, `merge`, `update`), so one writer covers every change. This is fine for small local state. It has no locking, so use a database when several processes write at once.

## JSON and HTTP

### Call a JSON API

<!-- phel-test: skip -->
```phel
(ns cookbook.http
  (:require phel.http-client :as http)
  (:require phel.json :as json))

(defn fetch-json [url]
  (let [resp (http/get url {:timeout 10.0})]
    (if (<= 200 (:status resp) 299)
      (json/decode (:body resp))
      (throw (ex-info "Request failed" {:url url :status (:status resp)})))))

(:title (fetch-json "https://jsonplaceholder.typicode.com/todos/1"))

(http/post "https://api.example.com/users" {:json {:name "Alice"}})
```

`phel.json` converts between JSON objects and Phel maps with keyword keys; `json/encode` takes `{:flags php/JSON_PRETTY_PRINT}` and the other `json_encode` constants. The response is a map with `:status`, `:headers`, and `:body`. Throwing `ex-info` on a bad status keeps the URL and status with the error. The `:json` option encodes the body and sets the content type. See [phel.http-client](/documentation/reference/api/http-client/).

## Dates

### Format, shift, and compare dates

```phel
(ns cookbook.dates
  (:use DateTimeImmutable DateInterval DateTimeZone))

(def d (DateTimeImmutable. "2026-01-15 09:30:00" (DateTimeZone. "UTC")))

(.format d "Y-m-d H:i")                                        ; => "2026-01-15 09:30"
(.format (.modify d "+1 day") "Y-m-d")                         ; => "2026-01-16"
(.format (.add d (DateInterval. "P3M")) "Y-m-d")               ; => "2026-04-15"
(.format (.setTimezone d (DateTimeZone. "Asia/Tokyo")) "H:i")  ; => "18:30"
(< d (.modify d "+1 day"))                                     ; => true

(.-days (.diff (DateTimeImmutable. "2026-01-01")
               (DateTimeImmutable. "2026-12-31")))             ; => 364

(.format (DateTimeImmutable/createFromFormat "d/m/Y" "25/12/2026") "Y-m-d")
; => "2026-12-25"
```

Use `DateTimeImmutable`: every change returns a new object, as Phel values do. `<` and `>` compare dates directly. The `#inst "2026-01-15T12:00:00Z"` literal also reads as a `DateTimeImmutable`.

## CLI

### Build a command-line tool

<!-- phel-test: skip -->
```phel
(ns cookbook.greeter
  (:require phel.cli :as cli))

(def greet
  {:name "greet"
   :doc  "Print a greeting"
   :args [{:name "who" :mode :optional :default "World"}]
   :opts [{:name "greeting" :short "g" :mode :required :default "Hello"}
          {:name "repeat" :mode :required :default 1 :coerce :int}]
   :run  (fn [ctx]
           (dotimes [_ (cli/opt ctx "repeat")]
             (cli/writeln ctx (str (cli/opt ctx "greeting") ", " (cli/arg ctx "who") "!"))))})

(cli/run (cli/application {:name "greeter" :commands [greet] :default "greet"})
         (cli/argv *argv*))
```

```bash
vendor/bin/phel run src/greeter.phel greet Alice -g Hi --repeat 2
# Hi, Alice!
# Hi, Alice!
```

`phel.cli` builds on Symfony Console, so you get `--help`, validation, and type coercion (`:coerce :int`) for free. Pass `(cli/argv *argv*)`: `*argv*` holds only the user arguments, without `phel run` and the script path. For a quick script, reading `*argv*` directly is enough. See [phel.cli](/documentation/reference/api/cli/).
