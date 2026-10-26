+++
title = "Cookbook"
weight = 4
description = "Runnable Phel recipes grouped by task: collections, strings, files, JSON, HTTP, HTML, dates, CLI tools, errors, and validation."
aliases = ["/documentation/cookbook", "/documentation/one-liners", "/documentation/guides/one-liners"]
+++

Copy a recipe, change the data, and run it. Each one solves a single task and says why it is written that way. For the concepts behind the code, see the [Language](/documentation/language/) section. For a quick syntax lookup, see the [Cheat Sheet](/documentation/reference/cheat-sheet/).

- **Collections:** [transform records](#transform-a-list-of-records), [aggregate with transducers](#aggregate-without-intermediate-collections), [one-liners](#one-liners)
- **Strings:** [match and extract with regex](#match-and-extract-with-regex)
- **Files:** [read and write files](#read-and-write-files), [read a CSV file](#read-a-csv-file-into-maps), [store data in a JSON file](#store-data-in-a-json-file)
- **JSON and HTTP:** [encode and decode JSON](#encode-and-decode-json), [call a JSON API](#call-a-json-api)
- **HTML:** [render a page](#render-an-html-page)
- **Dates:** [format, shift, and compare dates](#format-shift-and-compare-dates)
- **CLI:** [build a command-line tool](#build-a-command-line-tool)
- **Errors and validation:** [throw errors with data](#throw-errors-with-data), [validate input](#validate-input-with-a-schema), [branch on the shape of data](#branch-on-the-shape-of-data)

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
;; => {"engineer" [{:name "ALICE" ...} {:name "CHARLIE" ...}], "manager" [{:name "DIANA" ...}]}

(foreach [role members by-role]
  (println role (count members)))
;; prints: engineer 2, manager 1

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

### One-liners

Single expressions that combine core functions.

```phel
(ns cookbook.one-liners
  (:require phel.string :as str))

(def users [{:id 1 :name "Alice" :role "admin" :score 42}
            {:id 2 :name "Bob"   :role "user"  :score 99}
            {:id 3 :name "Carol" :role "admin" :score 71}])

;; Index records by id
(zipmap (map :id users) users)          ; => {1 {:id 1 ...}, 2 {...}, 3 {...}}

;; Count per group
(update-vals (group-by :role users) count)   ; => {"admin" 2, "user" 1}

;; Sum per group
(update-vals (group-by :role users) #(reduce + (map :score %)))
;; => {"admin" 113, "user" 99}

;; Top N by a key (pass > to sort descending)
(->> users (sort-by :score >) (take 2) (map :name))   ; => ("Bob" "Carol")

;; Most frequent first
(sort-by second > (frequencies [:a :b :a :c :a :b]))  ; => [[:a 3] [:b 2] [:c 1]]

;; Zip two collections, transpose a matrix
(map vector [:a :b :c] [1 2 3])         ; => ([:a 1] [:b 2] [:c 3])
(apply map vector [[1 2 3] [4 5 6]])    ; => ([1 4] [2 5] [3 6])

;; Drop nil values from a map
(into {} (remove (fn [[_ v]] (nil? v)) (pairs {:a 1 :b nil})))   ; => {:a 1}

;; Swap keys and values
(reduce-kv (fn [acc k v] (assoc acc v k)) {} {:a 1 :b 2})   ; => {1 :a, 2 :b}

;; Fibonacci from an iterated pair
(take 10 (map first (iterate (fn [[a b]] [b (+ a b)]) [0 1])))
;; => (0 1 1 2 3 5 8 13 21 34)

;; URL slug
(-> "Hello World, This is Phel!"
    str/lower-case
    (str/replace #"[^a-z0-9]+" "-")
    (str/replace #"^-|-$" ""))          ; => "hello-world-this-is-phel"

;; Title case
(->> (str/split "hello world of phel" #" ")
     (map str/capitalize)
     (str/join " "))                    ; => "Hello World Of Phel"
```

`reduce` and `filter` over a map see only its values, so wrap the map in `pairs` when you need `[key value]` entries.

## Strings

### Match and extract with regex

```phel
(ns cookbook.regex)

(re-find #"\d+" "Order #12345 confirmed")                ; => "12345"
(re-find #"(\d{4})-(\d{2})-(\d{2})" "Date: 2026-04-03")  ; => ["2026-04-03" "2026" "04" "03"]
(re-seq #"\b[A-Z][a-z]+" "Alice met Bob and Charlie")    ; => ["Alice" "Bob" "Charlie"]

(defn parse-color [s]
  (when-let [[_ r g b] (re-matches #"#([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})" s)]
    {:r (php/hexdec r) :g (php/hexdec g) :b (php/hexdec b)}))

(parse-color "#FF8800")      ; => {:r 255, :g 136, :b 0}
(parse-color "not-a-color")  ; => nil
```

`re-matches` must match the whole string, so it works as a validator. With capture groups it returns a vector you can destructure. `re-find` returns the first match anywhere in the string.

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
;; => [{:name "Alice", :email "alice@example.com", :role "admin"}
;;     {:name "Bob", :email "bob@example.com", :role "editor"}]
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

### Encode and decode JSON

```phel
(ns cookbook.json
  (:require phel.json :as json))

(json/encode {:name "Alice" :tags ["a" "b"]})    ; => "{\"name\":\"Alice\",\"tags\":[\"a\",\"b\"]}"
(json/decode "{\"name\":\"Alice\",\"age\":30}")  ; => {:name "Alice", :age 30}
(json/encode {:a 1} {:flags php/JSON_PRETTY_PRINT})
```

`phel.json` converts between JSON objects and Phel maps with keyword keys. `:flags` takes the same constants as PHP's `json_encode`.

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

The response is a map with `:status`, `:headers`, and `:body`. Throwing `ex-info` on a bad status keeps the URL and status with the error. The `:json` option encodes the body and sets the content type. See [phel.http-client](/documentation/reference/api/http-client/).

## HTML

### Render an HTML page

```phel
(ns cookbook.html
  (:require phel.html :refer [html doctype]))

(defn user-card [user]
  [:div {:class "card"}
    [:h3 (:name user)]
    [:span {:class [:badge (if (:active user) "active" "inactive")]}
      (if (:active user) "Active" "Inactive")]])

(defn render-page [title users]
  (html
    (doctype :html5)
    [:html {:lang "en"}
      [:head [:meta {:charset "UTF-8"}] [:title title]]
      [:body
        [:h1 title]
        (for [user :in users]
          (user-card user))]]))

(println (render-page "Users" [{:name "Alice" :active true}
                               {:name "Bob" :active false}]))
```

`html` is a macro that walks the vector at compile time. Write every `for` inline inside the one `html` call. Helpers like `user-card` that return a single element vector compose fine. See [HTML Rendering](/documentation/web/html-rendering).

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
;; => "2026-12-25"
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

## Errors and validation

### Throw errors with data

```phel
(ns cookbook.errors
  (:use Exception RuntimeException))

(def users {1 {:id 1 :name "Alice"}})

(defn find-user [id]
  (or (get users id)
      (throw (ex-info "User not found" {:type :not-found :user-id id}))))

(defn handle-request [id]
  (try
    {:status 200 :body (find-user id)}
    (catch Exception e
      (case (:type (ex-data e))
        :not-found {:status 404 :body (ex-message e)}
        {:status 500 :body "Internal error"}))))

(handle-request 1)   ; => {:status 200, :body {:id 1, :name "Alice"}}
(handle-request 7)   ; => {:status 404, :body "User not found"}

;; Wrap a low-level error and keep it as the cause
(try
  (try (throw (RuntimeException. "disk full"))
       (catch Exception e (throw (ex-info "Save failed" {:step :write} e))))
  (catch Exception e
    (ex-message (ex-cause e))))   ; => "disk full"
```

`ex-info` attaches a data map, so the caller branches on `:type` instead of parsing the message. The third argument keeps the original exception for logs. See [Error Handling](/documentation/language/error-handling).

### Validate input with a schema

```phel
(ns cookbook.schema
  (:require phel.schema :as s))

(def Signup
  [:map {:closed true}
   [:email [:re #"^[^@]+@[^@]+$"]]
   [:age   :int]])

(defn parse-signup [input]
  (let [data (s/coerce Signup input)]
    (if (s/validate Signup data)
      {:ok data}
      {:error (s/human-readable-explain (s/explain Signup data))})))

(parse-signup {:email "ada@example.com" :age "36"})
;; => {:ok {:email "ada@example.com", :age 36}}

(contains? (parse-signup {:email "nope" :age "36"}) :error)
;; => true
```

Form values arrive as strings, so `coerce` first, then `validate`. `coerce` works on keyword keys: convert string keys first, for example with `keywordize-keys` from `phel.walk`. See [Schema Validation](/documentation/libraries/schema/).

### Branch on the shape of data

```phel
(ns cookbook.match
  (:require phel.match :refer [match]))

(defn describe [v]
  (match [v]
    [0]                     "zero"
    [[_ _]]                 "pair"
    [[_ _ & more]]          (str "tuple+" (count more))
    [{:type :error :msg m}] (str "error: " m)
    [(n :guard int?)]       (str "int " n)
    :else                   "other"))

(describe [1 2])                   ; => "pair"
(describe [1 2 3 4])               ; => "tuple+2"
(describe {:type :error :msg "x"}) ; => "error: x"
(describe 99)                      ; => "int 99"
```

`match` takes a vector of targets, and each pattern is a vector of the same length. It replaces nested `cond` checks on vector length and map keys. See [Control Flow](/documentation/language/control-flow).

## Next steps

- [Rosetta Stone](/documentation/guides/rosetta-stone/): the Phel form for a PHP idiom
- [Build a Web App](/documentation/guides/build-a-web-app/): put these recipes together in a running app
