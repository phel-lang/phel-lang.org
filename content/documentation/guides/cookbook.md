+++
title = "Cookbook"
weight = 4
description = "Runnable Phel recipes for files, JSON, HTTP, dates, error handling, schemas, state, and data-transformation pipelines"
aliases = ["/documentation/cookbook", "/documentation/one-liners"]
+++

Practical, self-contained Phel recipes for everyday tasks. Copy one, adapt it, ship it.

> **Recipes, not concepts.** Each entry is a task-oriented snippet. For the language concepts behind them, see the [Language](/documentation/language/) section; for quick syntax lookup, the [Cheat Sheet](/documentation/reference/cheat-sheet/).

## Read and process a CSV file

Read CSV into a vector of maps, headers as keys.

```phel
(ns cookbook.csv-reader)

;; Read a CSV file and return a vector of maps
;; Each row becomes a map with header names as keys
(defn read-csv [filepath]
  (let [handle (php/fopen filepath "r")]
    (if (not handle)
      (do
        (println (str "Error: cannot open " filepath))
        [])
      ;; Pass the escape arg explicitly ("") -- PHP 8.4 deprecates its implicit default
      (let [headers (php/fgetcsv handle nil "," "\"" "")
            header-keys (for [h :in headers] (keyword h))]
        (loop [rows []]
          (let [line (php/fgetcsv handle nil "," "\"" "")]
            (if (= false line)
              (do
                (php/fclose handle)
                rows)
              (let [row (for [[i k] :pairs header-keys]
                          [k (php/aget line i)])]
                (recur (conj rows (into {} row)))))))))))

;; Example usage:
;; Given a file "users.csv" with contents:
;;   name,email,role
;;   Alice,alice@example.com,admin
;;   Bob,bob@example.com,editor

(def users (read-csv "users.csv"))
;; => [{:name "Alice" :email "alice@example.com" :role "admin"}
;;     {:name "Bob" :email "bob@example.com" :role "editor"}]

;; Process the parsed data
(def admin-emails
  (->> users
       (filter #(= "admin" (get % :role)))
       (map :email)
       (into [])))
;; => ["alice@example.com"]
```

**See also:** [PHP Interop](/documentation/php-interop), [Data Structures](/documentation/language/data-structures)

## Build a simple CLI tool

CLI script that reads args, parses flags, produces output.

```phel
(ns cookbook.cli-tool
  (:require phel.string :as str))

;; `*argv*` (in phel.core) holds the user arguments, excluding the program name.
;; `*program*` holds the script path. When running:
;;   vendor/bin/phel run src/cli-tool.phel --name Alice --greeting Hi
;; *argv* is ["--name" "Alice" "--greeting" "Hi"].

;; Parse flags into a map of --key value pairs
(defn parse-flags [flag-args]
  (loop [remaining flag-args
         flags {}]
    (if (empty? remaining)
      flags
      (let [current (first remaining)
            rest-args (rest remaining)]
        (if (str/starts-with? current "--")
          (let [k (keyword (str/subs current 2))
                v (first rest-args)]
            (recur (rest rest-args) (assoc flags k v)))
          (recur rest-args flags))))))

;; Build the tool
(defn run []
  (let [flags (parse-flags *argv*)
        who (get flags :name "World")
        greeting (get flags :greeting "Hello")
        repeat-count (php/intval (get flags :repeat "1"))]
    (dotimes [_ repeat-count]
      (println (str greeting ", " who "!")))))

(run)
;; Running: vendor/bin/phel run src/cli-tool.phel --name Alice --repeat 3
;; Output:
;;   Hello, Alice!
;;   Hello, Alice!
;;   Hello, Alice!
```

**See also:** [PHP Interop](/documentation/php-interop), [Control Flow](/documentation/language/control-flow)

## HTTP request with cURL

GET request via `phel.http-client`. Parse JSON via `phel.json`.

```phel
(ns cookbook.http-client
  (:require phel.http-client :as http)
  (:require phel.json :as json)
  (:use JsonException))

;; Perform an HTTP GET request. `http/get` returns an http/response struct
;; with :status, :headers, :body, :version, and :reason keys.
(defn http-get [url]
  (let [resp (http/get url {:timeout 30.0 :follow-redirects true})]
    (if (and (>= (get resp :status) 200) (< (get resp :status) 300))
      {:body (get resp :body) :status (get resp :status)}
      {:error (get resp :reason) :status (get resp :status)})))

;; Parse a JSON string into a Phel map using phel.json
(defn parse-json [json-string]
  (try
    (json/decode json-string)
    (catch JsonException e
      {:error (.getMessage e)})))

;; Fetch data from a JSON API
(defn fetch-json [url]
  (let [result (http-get url)]
    (if (get result :error)
      result
      (parse-json (get result :body)))))

;; Example: fetch a list of todos from a public API
(def response (fetch-json "https://jsonplaceholder.typicode.com/todos/1"))
;; response is a Phel map (phel.json converts keys to keywords)
(println (str "Title: " (get response :title)))
(println (str "Completed: " (if (get response :completed) "yes" "no")))

;; Example: fetch multiple items and process them
(defn fetch-todos [limit]
  (let [data (fetch-json (str "https://jsonplaceholder.typicode.com/todos?_limit=" limit))]
    (->> data
         (map (fn [todo]
                {:id (get todo :id)
                 :title (get todo :title)
                 :completed (get todo :completed)}))
         (into []))))

(def todos (fetch-todos 5))
(def completed-count (count (filter :completed todos)))
(println (str completed-count " of " (count todos) " todos completed"))
```

**See also:** [PHP Interop](/documentation/php-interop)

## Generate HTML

`html` module: nested elements, attributes, dynamic content.

`html` is a macro: it walks the literal hiccup at compile time and splices any
`(for ...)` it finds **inline**. So build the whole page in a single `html` call
with the loops written in place. Plain element helpers (no embedded loop) like
`user-card` below still compose -- they return a single element vector that an
inline `for` can emit.

```phel
(ns cookbook.html-generator
  (:require phel.html :refer [html doctype raw-string]))

;; A reusable component: takes data, returns a single element vector (no `for`).
(defn user-card [user]
  [:div {:class "card"}
    [:h3 (get user :name)]
    [:p (str "Email: " (get user :email))]
    [:span {:class [:badge (if (get user :active) "active" "inactive")]}
      (if (get user :active) "Active" "Inactive")]])

;; Render the whole page in one `html` call so every `for` stays inline.
(defn render-page [title users links]
  (html
    (doctype :html5)
    [:html {:lang "en"}
      [:head
        [:meta {:charset "UTF-8"}]
        [:meta {:name "viewport" :content "width=device-width, initial-scale=1.0"}]
        [:title title]
        [:style (raw-string "
          body { font-family: sans-serif; max-width: 800px; margin: 0 auto; padding: 2rem; }
          .card { border: 1px solid #ddd; border-radius: 8px; padding: 1rem; margin: 1rem 0; }
          .badge { display: inline-block; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; }
          .active { background: #d4edda; color: #155724; }
          .inactive { background: #f8d7da; color: #721c24; }
        ")]]
      [:body
        [:h1 title]
        [:nav
          [:ul {:style {:list-style "none" :display "flex" :gap "1rem" :padding "0"}}
            (for [link :in links]
              [:li [:a {:href (get link :url)} (get link :label)]])]]
        [:p (str "Total users: " (count users))]
        (for [user :in users]
          (user-card user))]]))

;; Build a complete page with dynamic content
(def users
  [{:name "Alice" :email "alice@example.com" :active true}
   {:name "Bob" :email "bob@example.com" :active false}
   {:name "Charlie" :email "charlie@example.com" :active true}])

(def links
  [{:label "Home" :url "/"}
   {:label "Users" :url "/users"}
   {:label "About" :url "/about"}])

(println (render-page "User Directory" users links))
```

**See also:** [HTML Rendering](/documentation/web/html-rendering)

## Working with dates

PHP DateTime via interop: create, format, compare.

```phel
(ns cookbook.dates
  (:use DateTimeImmutable DateInterval DateTimeZone))

;; Create dates - ClassName. is a shorthand constructor
(def now (DateTimeImmutable.))
(def specific-date (DateTimeImmutable. "2024-06-15"))
(def from-format
  (DateTimeImmutable/createFromFormat "d/m/Y" "25/12/2024"))

;; Format dates - .method is shorthand for (php/-> obj (method ...))
(println (.format now "Y-m-d H:i:s"))       ; 2024-03-10 14:30:00
(println (.format now "l, F j, Y"))         ; Sunday, March 10, 2024
(println (.format specific-date "D, M j"))  ; Sat, Jun 15

;; Date arithmetic
(def tomorrow (.modify now "+1 day"))
(def next-week (.modify now "+7 days"))
(def three-months-later (.add now (DateInterval. "P3M")))

(println (str "Tomorrow: "   (.format tomorrow "Y-m-d")))
(println (str "Next week: "  (.format next-week "Y-m-d")))
(println (str "In 3 months: " (.format three-months-later "Y-m-d")))

;; Compare dates
(defn date-before? [a b] (< (.getTimestamp a) (.getTimestamp b)))
(defn date-after?  [a b] (> (.getTimestamp a) (.getTimestamp b)))

(println (str "Tomorrow is after today: " (date-after? tomorrow now)))  ; true

;; Calculate difference - `.-days` reads a PHP public property
(defn days-between [date1 date2]
  (.-days (.diff date1 date2)))

(def start (DateTimeImmutable. "2024-01-01"))
(def end   (DateTimeImmutable. "2024-12-31"))
(println (str "Days in 2024: " (days-between start end)))  ; 365

;; Work with time zones
(def utc-now   (DateTimeImmutable. "now" (DateTimeZone. "UTC")))
(def tokyo-now (.setTimezone utc-now (DateTimeZone. "Asia/Tokyo")))

(println (str "UTC:   " (.format utc-now   "H:i:s")))
(println (str "Tokyo: " (.format tokyo-now "H:i:s")))

;; Utility: human-readable relative time
(defn time-ago [date]
  (let [seconds (- (.getTimestamp (DateTimeImmutable.))
                   (.getTimestamp date))]
    (cond
      (< seconds 60)    "just now"
      (< seconds 3600)  (str (php/intval (/ seconds 60)) " minutes ago")
      (< seconds 86400) (str (php/intval (/ seconds 3600)) " hours ago")
      :else             (str (php/intval (/ seconds 86400)) " days ago"))))
```

**See also:** [PHP Interop](/documentation/php-interop)

## Filesystem operations

Read, write, list, exist checks via PHP interop.

```phel
(ns cookbook.filesystem)

;; Read entire file contents
(defn read-file [path]
  (let [contents (php/file_get_contents path)]
    (if (= false contents)
      nil
      contents)))

;; Write content to a file (creates or overwrites)
(defn write-file [path content]
  (let [result (php/file_put_contents path content)]
    (if (= false result)
      (do (println (str "Error: could not write to " path)) false)
      true)))

;; Append content to a file
(defn append-file [path content]
  (let [result (php/file_put_contents path content php/FILE_APPEND)]
    (if (= false result)
      (do (println (str "Error: could not append to " path)) false)
      true)))

;; Check if a file or directory exists
(defn exists? [path]
  (php/file_exists path))

(defn file? [path]
  (php/is_file path))

(defn directory? [path]
  (php/is_dir path))

;; List directory contents, excluding . and ..
(defn list-dir [path]
  (when (directory? path)
    (for [entry :in (php/scandir path)
          :when (and (not= entry ".") (not= entry ".."))]
      entry)))

;; List files matching a pattern
(defn glob-files [pattern]
  (let [matches (php/glob pattern)]
    (if (= false matches) []
      (for [f :in matches] f))))

;; Get file info
(defn file-info [path]
  (if (not (exists? path))
    nil
    {:path path
     :size (php/filesize path)
     :modified (php/filemtime path)
     :readable (php/is_readable path)
     :writable (php/is_writable path)}))

;; Create directory recursively
(defn mkdir [path]
  (when (not (exists? path))
    (php/mkdir path 0755 true)))

;; Example usage
(write-file "output/example.txt" "Hello from Phel!\n")
(append-file "output/example.txt" "Another line.\n")

(when (exists? "output/example.txt")
  (println (read-file "output/example.txt")))

;; List all .phel files in a directory
(def phel-files (glob-files "src/**/*.phel"))
(foreach [f phel-files]
  (println (str "Found: " f)))

;; Get info about each file. `map` / `filter` are lazy -- finish the pipeline
;; with `into` (or `vec` / `doall`) to realise the result.
(def file-report
  (->> phel-files
       (map file-info)
       (sort-by :size)
       (reverse)
       (into [])))
```

**See also:** [PHP Interop](/documentation/php-interop)

## Data transformation pipeline

Filter, transform, group via threading macros and collection functions.

```phel
(ns cookbook.data-pipeline
  (:require phel.string :as str))

;; Sample dataset: a vector of user maps
(def users
  [{:name "Alice"   :age 32 :role "engineer" :active true}
   {:name "Bob"     :age 28 :role "designer" :active false}
   {:name "Charlie" :age 45 :role "engineer" :active true}
   {:name "Diana"   :age 35 :role "manager"  :active true}
   {:name "Eve"     :age 29 :role "designer" :active true}
   {:name "Frank"   :age 52 :role "manager"  :active false}
   {:name "Grace"   :age 38 :role "engineer" :active true}])

;; Pipeline: get active users, uppercase names, sort by age, group by role
(def result
  (->> users
       (filter :active)                                          ; keep only active users
       (map #(update % :name str/upper-case))                   ; uppercase names
       (sort-by :age)                                           ; sort by age ascending
       (group-by :role)))                                       ; group into a map by role

;; result =>
;; {"engineer" [{:name "ALICE"   :age 32 ...}
;;              {:name "GRACE"   :age 38 ...}
;;              {:name "CHARLIE" :age 45 ...}]
;;  "manager"  [{:name "DIANA"   :age 35 ...}]
;;  "designer" [{:name "EVE"     :age 29 ...}]}

;; Print a summary report. A 3-element `foreach` binds key and value of a map.
(foreach [role members result]
  (println (str "== " (str/upper-case role) " (" (count members) ") =="))
  (foreach [m members]
    (println (str "  " (:name m) " (age " (:age m) ")"))))

;; More pipeline examples:

;; Average age of active users.
;; (/ int int) returns a Ratio when not evenly divisible.
(def avg-age
  (let [active (filter :active users)
        total-age (reduce + 0 (map :age active))]
    (/ total-age (count active))))
(println (str "Average age of active users: " avg-age))

;; Find the oldest user per role.
;; `pairs` turns the grouped map into [key value] tuples -- iterating a map
;; directly (map/reduce) walks its *values* only, not key/value pairs.
(def oldest-per-role
  (->> users
       (group-by :role)
       pairs
       (map (fn [[role members]]
              [role (:name (last (sort-by :age members)))]))
       (into {})))

;; Count users by status
(def status-counts
  {:active (count (filter :active users))
   :inactive (count (filter #(not (get % :active)) users))})
(println (str "Active: " (get status-counts :active)
              ", Inactive: " (get status-counts :inactive)))

;; Extract unique roles
(def roles
  (->> users
       (map :role)
       (into #{})))
(println (str "Roles: " roles))
```

**See also:** [Data Structures](/documentation/language/data-structures), [Control Flow](/documentation/language/control-flow)

## Simple key-value store

Persistent KV store backed by JSON. Get, put, delete, list keys.

```phel
(ns cookbook.kv-store
  (:require phel.json :as json)
  (:use JsonException))

;; Path to the JSON storage file
(def default-store-path "data/store.json")

;; Load the store from disk, returning a Phel map
(defn store-load [path]
  (if (not (php/file_exists path))
    {}
    (let [contents (php/file_get_contents path)]
      (if (or (= false contents) (= "" contents))
        {}
        (try
          (json/decode contents)
          (catch JsonException _ {}))))))

;; Save the store to disk as pretty-printed JSON
(defn store-save [path data]
  (let [dir (php/dirname path)]
    (when (not (php/is_dir dir))
      (php/mkdir dir 0755 true))
    (php/file_put_contents
      path
      (json/encode data {:flags php/JSON_PRETTY_PRINT}))))

;; Get a value by key, with an optional default
(defn store-get
  ([k] (store-get default-store-path k nil))
  ([k default] (store-get default-store-path k default))
  ([path k default]
    (get (store-load path) k default)))

;; Put a key-value pair into the store
(defn store-put
  ([k v] (store-put default-store-path k v))
  ([path k v]
    (let [data (store-load path)
          updated (assoc data k v)]
      (store-save path updated)
      updated)))

;; Delete a key from the store
(defn store-delete
  ([k] (store-delete default-store-path k))
  ([path k]
    (let [data (store-load path)
          updated (dissoc data k)]
      (store-save path updated)
      updated)))

;; List all keys in the store
(defn store-keys
  ([] (store-keys default-store-path))
  ([path] (keys (store-load path))))

;; Check if a key exists
(defn store-has?
  ([k] (store-has? default-store-path k))
  ([path k] (contains? (store-load path) k)))

;; Example usage
(store-put :user-1 "Alice")
(store-put :user-2 "Bob")
(store-put :config-theme "dark")

(println (str "User 1: " (store-get :user-1)))             ; Alice
(println (str "User 3: " (store-get :user-3 "unknown")))   ; unknown
(println (str "Keys: " (store-keys)))                       ; [:user-1 :user-2 :config-theme]

(store-delete :user-2)
(println (str "Has :user-2? " (store-has? :user-2)))       ; false

;; Bulk operations using Phel's functional tools
(defn store-put-many [pairs]
  (let [path default-store-path
        data (store-load path)
        updated (reduce (fn [acc [k v]] (assoc acc k v)) data pairs)]
    (store-save path updated)
    updated))

(store-put-many [[:lang "phel"] [:version "0.41"] [:status "awesome"]])
(println (str "All keys: " (store-keys)))
```

**See also:** [Data Structures](/documentation/language/data-structures), [PHP Interop](/documentation/php-interop)

## Defining and using protocols

Protocols define polymorphic behavior, extendable to any type. More flexible than PHP interfaces.

```phel
(ns cookbook.protocols
  (:require phel.string :as str))

;; Define a protocol for rendering things as HTML
(defprotocol Renderable
  (render-html [this]))

(defstruct paragraph [text])
(defstruct heading [level text])
(defstruct link [url label])

(extend-type paragraph
  Renderable
  (render-html [this]
    (str "<p>" (:text this) "</p>")))

(extend-type heading
  Renderable
  (render-html [this]
    (let [lvl (:level this)]
      (str "<h" lvl ">" (:text this) "</h" lvl ">"))))

(extend-type link
  Renderable
  (render-html [this]
    (str "<a href=\"" (:url this) "\">" (:label this) "</a>")))

;; Render a collection of mixed elements
(def page-elements
  [(heading 1 "Welcome")
   (paragraph "This is a Phel-powered page.")
   (link "https://phel-lang.org" "Learn Phel")
   (paragraph "Protocols make this extensible.")])

(def html-output
  (str/join "" (map render-html page-elements)))

(println html-output)
;; => <h1>Welcome</h1><p>This is a Phel-powered page.</p>...

;; Check if a value supports the protocol
(satisfies? Renderable (paragraph "hi"))  ; => true
(satisfies? Renderable "plain string")    ; => false
```

**See also:** [Cheat Sheet -- Protocols](/documentation/reference/cheat-sheet/#protocols)

## Data processing with transducers

Compose pipelines without intermediate collections. Faster, less memory than chaining `map`/`filter`.

```phel
(ns cookbook.transducers)

;; Sample data: a log of events
(def events
  [{:type :page-view  :path "/"         :ms 12}
   {:type :api-call   :path "/api/users" :ms 230}
   {:type :page-view  :path "/about"    :ms 8}
   {:type :api-call   :path "/api/users" :ms 180}
   {:type :page-view  :path "/"         :ms 15}
   {:type :api-call   :path "/api/posts" :ms 340}
   {:type :page-view  :path "/about"    :ms 9}
   {:type :api-call   :path "/api/users" :ms 200}])

;; Build a transducer pipeline: keep only slow API calls, extract paths
(def slow-api-paths
  (comp
    (filter #(= :api-call (get % :type)))   ; only API calls
    (filter #(> (get % :ms) 150))            ; slower than 150ms
    (map :path)))                            ; extract the path

;; Apply with transduce to count slow calls
(def slow-count
  (transduce slow-api-paths
    (completing (fn [acc _] (inc acc)))
    0
    events))
(println (str "Slow API calls: " slow-count))  ; => 4

;; Apply with into to collect results
(def slow-paths
  (into [] slow-api-paths events))
(println (str "Paths: " slow-paths))
;; => ["/api/users" "/api/users" "/api/posts" "/api/users"]

;; Get unique slow paths using a set
(def unique-slow-paths
  (into #{} slow-api-paths events))
(println (str "Unique: " unique-slow-paths))
;; => #{"/api/users" "/api/posts"}

;; Compute average response time of API calls. The reducing step accumulates
;; a running [sum count]; divide once afterwards. (`transduce` already wraps the
;; reducing fn, so a `completing` finalizer here would be ignored.)
(defn avg-transducer [xf coll]
  (let [[sum cnt] (transduce xf
                    (fn [[sum cnt] x] [(+ sum x) (inc cnt)])
                    [0 0]
                    coll)]
    (if (zero? cnt) 0 (/ (php/floatval sum) cnt))))

(def avg-api-ms
  (avg-transducer
    (comp (filter #(= :api-call (get % :type)))
          (map :ms))
    events))
(println (str "Avg API response: " avg-api-ms "ms"))  ; => 237.5ms

;; Use cat to flatten nested collections
(def nested [[1 2 3] [4 5] [6]])
(into [] cat nested)               ; => [1 2 3 4 5 6]
```

**See also:** [Cheat Sheet -- Transducers](/documentation/reference/cheat-sheet/#transducers)

## Reader conditionals for cross-platform code

Write `.cljc` targeting multiple platforms. `:phel` for Phel-specific, `:default` as fallback.

```phel
(ns cookbook.conditionals)

;; Reader conditional: select platform-specific expression
;; In a .cljc file, this compiles only the :phel branch:
(def platform
  #?(:phel "Phel on PHP"
     :default "Unknown platform"))

(println platform)  ; => "Phel on PHP"

;; Practical use: platform-specific implementations
(defn now-timestamp []
  #?(:phel (php/time)
     :default 0))

;; Splicing reader conditional inserts multiple elements
;; into the surrounding form:
(def features
  [:core
   :macros
   #?@(:phel [:php-interop :composer]
       :default [:generic])])
;; On Phel: => [:core :macros :php-interop :composer]
```

## Regex matching and validation

Regex literals (`#"..."`) and matching functions for PCRE patterns.

```phel
(ns cookbook.regex)

;; Basic matching with re-find (returns first match)
(re-find #"\d+" "Order #12345 confirmed")
;; => "12345"

;; Capture groups return a vector: [full-match group1 group2 ...]
(re-find #"(\d{4})-(\d{2})-(\d{2})" "Date: 2026-04-03")
;; => ["2026-04-03" "2026" "04" "03"]

;; re-matches requires the pattern to match the entire string
(re-matches #"\d+" "123")          ; => "123"
(re-matches #"\d+" "abc123")       ; => nil

;; Validate an input format
(defn parse-color [s]
  (let [m (re-matches #"#([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})" s)]
    (when m
      {:hex s
       :r (php/hexdec (get m 1))
       :g (php/hexdec (get m 2))
       :b (php/hexdec (get m 3))})))

(parse-color "#FF8800")
;; => {:hex "#FF8800" :r 255 :g 136 :b 0}

(parse-color "not-a-color")
;; => nil

;; Extract all successive matches with `re-seq` (lazy sequence of matches).
(re-seq #"\b[A-Z][a-z]+" "Alice met Bob and Charlie")
;; => ["Alice" "Bob" "Charlie"]
```

**See also:** [Cheat Sheet -- Regular Expressions](/documentation/reference/cheat-sheet/#regular-expressions)

## Structured exceptions with ex-info

`ex-info` carries structured data with exceptions. More informative than plain messages.

```phel
(ns cookbook.exceptions
  (:require phel.json :as json)
  (:use Exception))

;; Stub user lookup -- replace with real datasource
(def users {1 {:id 1 :name "Alice"}
            2 {:id 2 :name "Bob"}})

(defn lookup-user-by-id [id]
  (get users id))

;; Throw a structured exception
(defn find-user [id]
  (let [user (lookup-user-by-id id)]
    (when (nil? user)
      (throw (ex-info "User not found"
                      {:user-id id :type :not-found})))
    user))

;; Catch and inspect structured exceptions
(defn handle-request [user-id]
  (try
    (let [user (find-user user-id)]
      {:status 200 :body user})
    (catch Exception e
      (let [data (ex-data e)]
        (case (get data :type)
          :not-found   {:status 404 :body (ex-message e)}
          :forbidden   {:status 403 :body (ex-message e)}
          {:status 500 :body "Internal error"})))))

;; Chain exceptions with a cause
(defn load-config [path]
  (try
    (let [raw (php/file_get_contents path)]
      (when (= false raw)
        (throw (ex-info "File not readable" {:path path})))
      (json/decode raw))
    (catch Exception e
      (throw (ex-info "Config load failed"
                      {:path path :step :read}
                      e)))))

;; Later, inspect the chain
(try
  (load-config "missing.json")
  (catch Exception e
    (println (str "Error: " (ex-message e)))
    (println (str "Data: " (ex-data e)))
    (when (ex-cause e)
      (println (str "Caused by: " (ex-message (ex-cause e)))))))
```

**See also:** [Cheat Sheet -- Error Handling](/documentation/reference/cheat-sheet/#error-handling)

## Pattern matching with `phel.match`

`match` takes a **vector of targets** and clauses of `[pattern-vector expr]`.
Each pattern vector must have the same length as the target vector. A trailing
`:else` is the default. Pattern elements: literals, `_` wildcard, bare symbols
(bindings), nested vectors with optional `& rest`, map patterns `{:k p}`,
`(inner :guard pred)`, `(inner :as name)`, and `(:or a b ...)`.

```phel
(ns cookbook.match
  (:require phel.match :refer [match]))

;; Multiple targets: classify a 2D point.
(defn classify [x y]
  (match [x y]
    [0 0]                             "origin"
    [_ 0]                             "on the x-axis"
    [0 _]                             "on the y-axis"
    [(a :guard pos?) (b :guard pos?)] "first quadrant"
    [a b]                             (str "point (" a ", " b ")")
    :else                             "unknown"))

(classify 0 0)   ; => "origin"
(classify 5 0)   ; => "on the x-axis"
(classify 3 4)   ; => "first quadrant"
(classify -1 4)  ; => "point (-1, 4)"

;; Single target: wrap one value in a 1-element target vector and match its
;; shape with nested patterns.
(defn describe [v]
  (match [v]
    [0]                     "zero"
    [[_ _]]                 "pair"
    [[_ _ & rest]]          (str "tuple+" (count rest))
    [{:type :error :msg m}] (str "error: " m)
    [(:or "hi" "hello")]    "greeting"
    [(n :guard int?)]       (str "int " n)
    :else                   "other"))

(describe 0)                       ; => "zero"
(describe [1 2])                   ; => "pair"
(describe [1 2 3 4])               ; => "tuple+2"
(describe {:type :error :msg "x"}) ; => "error: x"
(describe "hello")                 ; => "greeting"
(describe 99)                      ; => "int 99"
```

## Schemas with `phel.schema`

Validate, coerce, and generate data from declarative schemas built out of plain Phel data, and wrap functions with `instrument!` to check args and returns on every call. See the [Schema Validation guide](/documentation/guides/schema/) for the full reference.

## Async with `phel.async`

Fiber-backed promises and futures. `promise`, `deliver`, `future-call`, `future?`, and `deref` live in `phel.core` and are available without a require:

```phel
(ns cookbook.async)

(def p (promise))
(future-call (fn [] (deliver p 42)))

(deref p)                ; blocks until delivered
(deref p 1000 :timeout)  ; wait up to 1000 ms, return :timeout on expiry

(def f (future-call (fn [] (+ 1 2))))
(future? f)              ; => true
@f                       ; => 3
```

## File Watching

Reload namespaces on file change:

```phel
(ns cookbook.watcher
  (:require phel.watch :refer [watch!]))

(watch! ["src/" "tests/"])
```

Or from the shell: `vendor/bin/phel watch src/`.

## Property Tests with `phel.test.gen`

`defspec` takes a name, an options map (`:num-tests`, `:size`, `:seed`,
`:shrink?`), a generator producing the property's arguments, and a property
function that returns truthy on success:

```phel
(ns cookbook.gen-tests
  (:require phel.test :refer [deftest is])
  (:require phel.test.gen :as gen :refer [defspec]))

(defspec addition-commutes {:num-tests 100}
  (gen/tuple gen/int gen/int)
  (fn [a b] (= (+ a b) (+ b a))))
```

Failing cases shrink automatically; the reported seed makes them reproducible.

## One-liners

Single-expression solutions showcasing Phel's expressiveness and functional power.

### Math & Numbers

Factorial of 10:

```phel
(reduce * 1 (range 1 11))
;; => 3628800
```

Sum of squares from 1 to 100:

```phel
(->> (range 1 101) (map #(* % %)) (reduce + 0))
;; => 338350
```

Fibonacci sequence (first 10):

```phel
(->> (range 2 10)
     (reduce (fn [acc _]
               (conj acc (+ (peek acc) (get acc (- (count acc) 2)))))
             [0 1]))
;; => [0 1 1 2 3 5 8 13 21 34]
```

Check if a number is prime:

```phel
(let [n 17]
  (and (> n 1)
       (every? (fn [d] (not= 0 (% n d)))
               (range 2 (php/intval (+ 1 (php/sqrt n)))))))
;; => true
```

Greatest common divisor (Euclidean algorithm):

```phel
(loop [a 48 b 18] (if (= b 0) a (recur b (% a b))))
;; => 6
```

Power via reduce:

```phel
(let [base 2 exp 10]
  (reduce (fn [acc _] (* acc base)) 1 (range 0 exp)))
;; => 1024
```

### Strings

Reverse a string:

```phel
(phel.string/reverse "hello")
;; => "olleh"
```

Palindrome check:

```phel
(let [s "racecar"] (= s (phel.string/reverse s)))
;; => true
```

Count vowels:

```phel
(->> (seq "functional programming")
     (filter #(contains? #{"a" "e" "i" "o" "u"} %))
     count)
;; => 7
```

Title case:

```phel
(->> (phel.string/split "hello world of phel" #" ")
     (map phel.string/capitalize)
     (phel.string/join " "))
;; => "Hello World Of Phel"
```

ROT13 cipher:

```phel
(->> (seq "Hello")
     (map (fn [c]
            (let [o (php/ord c)]
              (cond
                (and (>= o 65) (<= o 90))
                  (php/chr (+ 65 (% (+ (- o 65) 13) 26)))
                (and (>= o 97) (<= o 122))
                  (php/chr (+ 97 (% (+ (- o 97) 13) 26)))
                :else c))))
     (apply str))
;; => "Uryyb"
```

Alternating character pattern:

```phel
(phel.string/join "" (map #(if (even? %) "*" "-") (range 0 10)))
;; => "*-*-*-*-*-"
```

### Collections

Flatten nested vectors one level:

```phel
(apply concat [[1 2] [3 4] [5 6]])
;; => @[1 2 3 4 5 6]
```

Unique elements preserving order:

```phel
(distinct [3 1 4 1 5 9 2 6 5 3])
;; => @[3 1 4 5 9 2 6]
```

Zip two vectors together:

```phel
(map vector [:a :b :c] [1 2 3])
;; => @[[:a 1] [:b 2] [:c 3]]
```

Partition into pairs:

```phel
(partition 2 [1 2 3 4 5 6])
;; => @[[1 2] [3 4] [5 6]]
```

Transpose a matrix:

```phel
(apply map vector [[1 2 3] [4 5 6] [7 8 9]])
;; => @[[1 4 7] [2 5 8] [3 6 9]]
```

Character frequencies:

```phel
(frequencies (seq "abracadabra"))
;; => {"a" 5 "b" 2 "r" 2 "c" 1 "d" 1}
```

Index a collection by key:

```phel
(let [coll [{:id 1 :name "Alice"} {:id 2 :name "Bob"}]]
  (zipmap (map :id coll) coll))
;; => {1 {:id 1 :name "Alice"} 2 {:id 2 :name "Bob"}}
```

Interleave and take:

```phel
(take 7 (interleave [:a :b :c :d] [1 2 3 4]))
;; => @[:a 1 :b 2 :c 3 :d]
```

### Data Processing

Group and count:

```phel
(->> [{:role "admin"} {:role "user"} {:role "admin"}
      {:role "user"} {:role "user"}]
     (group-by :role)
     pairs                          ; map -> [key value] tuples
     (map (fn [[k v]] [k (count v)])))
;; => @[["admin" 2] ["user" 3]]
```

Top N items by key:

```phel
(->> [{:name "A" :score 42} {:name "B" :score 99} {:name "C" :score 71}]
     (sort-by :score)
     reverse
     (take 2))
;; => @[{:name "B" :score 99} {:name "C" :score 71}]
```

Merge maps with defaults:

```phel
(merge {:host "localhost" :port 3306 :db "test"}
       {:port 5432 :db "prod"})
;; => {:host "localhost" :port 5432 :db "prod"}
```

Sum values by category:

```phel
(->> [{:cat "a" :v 10} {:cat "b" :v 20} {:cat "a" :v 30}]
     (group-by :cat)
     pairs                          ; map -> [key value] tuples
     (reduce (fn [acc [k items]]
               (assoc acc k (reduce + 0 (map :v items))))
             {}))
;; => {"a" 40 "b" 20}
```

Frequency-sorted leaderboard:

```phel
(->> (frequencies [:alice :bob :alice :carol :bob :alice])
     (into [])
     (sort-by second)
     reverse)
;; => [[:alice 3] [:bob 2] [:carol 1]]
```

### Fun & Creative

FizzBuzz (1 to 20):

```phel
(map (fn [n]
       (cond
         (= 0 (% n 15)) "FizzBuzz"
         (= 0 (% n 3))  "Fizz"
         (= 0 (% n 5))  "Buzz"
         :else n))
     (range 1 21))
;; => @[1 2 "Fizz" 4 "Buzz" "Fizz" 7 8 "Fizz" "Buzz" 11 "Fizz" 13 14 "FizzBuzz" 16 17 "Fizz" 19 "Buzz"]
```

Caesar cipher (shift by 3):

```phel
(->> (seq "Attack at dawn")
     (map (fn [c]
            (let [o (php/ord c)]
              (cond
                (and (>= o 65) (<= o 90))
                  (php/chr (+ 65 (% (+ (- o 65) 3) 26)))
                (and (>= o 97) (<= o 122))
                  (php/chr (+ 97 (% (+ (- o 97) 3) 26)))
                :else c))))
     (apply str))
;; => "Dwwdfn dw gdzq"
```

Simple slug generator:

```phel
(-> "Hello World, This is Phel!"
     (phel.string/lower-case)
     (phel.string/replace " " "-")
     (phel.string/replace #"[^a-z0-9-]" ""))
;; => "hello-world-this-is-phel"
```

Collatz sequence from a starting number:

```phel
(loop [n 12 acc []]
  (if (= n 1)
    (conj acc 1)
    (recur (if (even? n) (/ n 2) (+ 1 (* 3 n)))
           (conj acc n))))
;; => [12 6 3 10 5 16 8 4 2 1]
```

Diamond pattern (width 5):

```phel
(->> (concat (range 1 6 2) (range 3 0 -2))
     (map #(phel.string/join ""
             [(phel.string/repeat " " (/ (- 5 %) 2))
              (phel.string/repeat "*" %)]))
     (phel.string/join "\n"))
;; => "  *\n ***\n*****\n ***\n  *"
```

## Next steps

- [Rosetta Stone (PHP to Phel)](/documentation/guides/rosetta-stone/) - look up the Phel form for a PHP idiom
- [Data structures](/documentation/language/data-structures/) - the collections behind these recipes
- [PHP interop](/documentation/php-interop/) - call any PHP function from Phel
