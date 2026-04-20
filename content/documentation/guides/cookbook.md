+++
title = "Cookbook"
weight = 4
aliases = ["/documentation/cookbook"]
+++

Practical recipes for common tasks in Phel. Each example is self-contained and ready to use.

## Read and Process a CSV File

Read a CSV file and parse it into a vector of maps, where each map represents a row with column headers as keys.

```phel
(ns cookbook\csv-reader)

;; Read a CSV file and return a vector of maps
;; Each row becomes a map with header names as keys
(defn read-csv [filepath]
  (let [handle (php/fopen filepath "r")]
    (if (not handle)
      (do
        (println (str "Error: cannot open " filepath))
        [])
      (let [headers (php/fgetcsv handle)
            header-keys (for [h :in headers] (keyword h))]
        (loop [rows []]
          (let [line (php/fgetcsv handle)]
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

## Build a Simple CLI Tool

A command-line script that reads arguments, parses simple flags, and produces output.

```phel
(ns cookbook\cli-tool)

;; Access command-line arguments via PHP's $argv
;; When running: vendor/bin/phel run src/cli-tool.phel --name Alice --greeting Hi
(def args (let [argv (php/aget php/$_SERVER "argv")]
            ;; Skip the first two args (phel binary and script path)
            (for [i :range [2 (php/count argv)]]
              (php/aget argv i))))

;; Parse flags into a map of --key value pairs
(defn parse-flags [flag-args]
  (loop [remaining flag-args
         flags {}]
    (if (empty? remaining)
      flags
      (let [current (first remaining)
            rest-args (rest remaining)]
        (if (php/str_starts_with current "--")
          (let [k (keyword (php/substr current 2))
                v (first rest-args)]
            (recur (rest rest-args) (assoc flags k v)))
          (recur rest-args flags))))))

;; Build the tool
(defn run []
  (let [flags (parse-flags args)
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

## HTTP Request with cURL

Make an HTTP GET request using the built-in `phel\http-client` module and parse a JSON response with `phel\json`.

```phel
(ns cookbook\http-client
  (:require phel\http-client :as http)
  (:require phel\json :as json))

;; Perform an HTTP GET request. `http/get` returns an http/response struct
;; with :status, :headers, :body, :version, and :reason keys.
(defn http-get [url]
  (let [resp (http/get url {:timeout 30.0 :follow-redirects true})]
    (if (and (>= (get resp :status) 200) (< (get resp :status) 300))
      {:body (get resp :body) :status (get resp :status)}
      {:error (get resp :reason) :status (get resp :status)})))

;; Parse a JSON string into a Phel map using phel\json
(defn parse-json [json-string]
  (try
    (json/decode json-string)
    (catch \JsonException e
      {:error (php/-> e (getMessage))})))

;; Fetch data from a JSON API
(defn fetch-json [url]
  (let [result (http-get url)]
    (if (get result :error)
      result
      (parse-json (get result :body)))))

;; Example: fetch a list of todos from a public API
(def response (fetch-json "https://jsonplaceholder.typicode.com/todos/1"))
;; response is a Phel map (phel\json converts keys to keywords)
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

Use Phel's `html` module to generate HTML markup with nested elements, attributes, and dynamic content.

```phel
(ns cookbook\html-generator
  (:require phel\html :refer [html doctype raw-string]))

;; Generate a simple page layout
(defn page [title & body]
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
        body]]))

;; Generate a user card component
(defn user-card [user]
  [:div {:class "card"}
    [:h3 (get user :name)]
    [:p (str "Email: " (get user :email))]
    [:span {:class [:badge (if (get user :active) "active" "inactive")]}
      (if (get user :active) "Active" "Inactive")]])

;; Generate a navigation bar
(defn nav [links]
  [:nav
    [:ul {:style {:list-style "none" :display "flex" :gap "1rem" :padding "0"}}
      (for [link :in links]
        [:li [:a {:href (get link :url)} (get link :label)]])]])

;; Build a complete page with dynamic content
(def users
  [{:name "Alice" :email "alice@example.com" :active true}
   {:name "Bob" :email "bob@example.com" :active false}
   {:name "Charlie" :email "charlie@example.com" :active true}])

(def links
  [{:label "Home" :url "/"}
   {:label "Users" :url "/users"}
   {:label "About" :url "/about"}])

(def output
  (page "User Directory"
    (nav links)
    [:p (str "Total users: " (count users))]
    (for [user :in users]
      (user-card user))))

(println output)
```

**See also:** [HTML Rendering](/documentation/web/html-rendering)

## Working with Dates

Use PHP's DateTime classes via Phel interop to create, format, and compare dates.

```phel
(ns cookbook\dates
  (:use DateTimeImmutable)
  (:use DateInterval)
  (:use DateTimeZone))

;; Create dates -- `(new ClassName args)` shorthand for `(php/new ClassName args)`
(def now (new DateTimeImmutable))
(def specific-date (new DateTimeImmutable "2024-06-15"))
(def from-format
  (DateTimeImmutable/createFromFormat "d/m/Y" "25/12/2024"))

;; Tagged literal form
(def tagged #inst "2024-06-15T00:00:00Z")

;; Format dates
(println (php/-> now (format "Y-m-d H:i:s")))       ; 2024-03-10 14:30:00
(println (php/-> now (format "l, F j, Y")))         ; Sunday, March 10, 2024
(println (php/-> specific-date (format "D, M j")))  ; Sat, Jun 15

;; Date arithmetic -- add and subtract intervals
(def tomorrow
  (php/-> now (modify "+1 day")))
(def next-week
  (php/-> now (modify "+7 days")))
(def three-months-later
  (php/-> now (add (DateInterval. "P3M"))))

(println (str "Tomorrow: " (php/-> tomorrow (format "Y-m-d"))))
(println (str "Next week: " (php/-> next-week (format "Y-m-d"))))
(println (str "In 3 months: " (php/-> three-months-later (format "Y-m-d"))))

;; Compare dates
(defn date-before? [a b]
  (< (php/-> a (getTimestamp)) (php/-> b (getTimestamp))))

(defn date-after? [a b]
  (> (php/-> a (getTimestamp)) (php/-> b (getTimestamp))))

(println (str "Tomorrow is after today: " (date-after? tomorrow now)))  ; true

;; Calculate the difference between two dates.
;; `(php/-> obj -prop)` reads a PHP public property (note the leading dash).
(defn days-between [date1 date2]
  (let [interval (php/-> date1 (diff date2))]
    (php/-> interval -days)))

(def start (DateTimeImmutable. "2024-01-01"))
(def end (DateTimeImmutable. "2024-12-31"))
(println (str "Days in 2024: " (days-between start end)))  ; 365

;; Work with time zones
(def utc-now (DateTimeImmutable. "now" (DateTimeZone. "UTC")))
(def tokyo-now
  (php/-> utc-now (setTimezone (DateTimeZone. "Asia/Tokyo"))))

(println (str "UTC:   " (php/-> utc-now (format "H:i:s"))))
(println (str "Tokyo: " (php/-> tokyo-now (format "H:i:s"))))

;; Utility: human-readable relative time
(defn time-ago [date]
  (let [seconds (- (php/-> (DateTimeImmutable.) (getTimestamp))
                   (php/-> date (getTimestamp)))]
    (cond
      (< seconds 60) "just now"
      (< seconds 3600) (str (php/intval (/ seconds 60)) " minutes ago")
      (< seconds 86400) (str (php/intval (/ seconds 3600)) " hours ago")
      :else (str (php/intval (/ seconds 86400)) " days ago"))))
```

**See also:** [PHP Interop](/documentation/php-interop)

## File System Operations

Read files, write files, list directories, and check file existence using PHP interop.

```phel
(ns cookbook\filesystem)

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
  (if (not (directory? path))
    []
    (let [entries (php/scandir path)]
      (for [i :range [0 (php/count entries)]
            :let [entry (php/aget entries i)]
            :when (and (not= entry ".") (not= entry ".."))]
        entry))))

;; List files matching a pattern
(defn glob-files [pattern]
  (let [matches (php/glob pattern)]
    (if (= false matches)
      []
      (for [i :range [0 (php/count matches)]]
        (php/aget matches i)))))

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

## Data Transformation Pipeline

Take raw data, filter it, transform it, and group it using Phel's threading macros and collection functions.

```phel
(ns cookbook\data-pipeline)

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
       (filter :active)                                       ; keep only active users
       (map #(assoc % :name (php/strtoupper (get % :name))))  ; uppercase names
       (sort-by :age)                                         ; sort by age ascending
       (group-by :role)))                                     ; group into a map by role

;; result =>
;; {"engineer" [{:name "ALICE"   :age 32 ...}
;;              {:name "GRACE"   :age 38 ...}
;;              {:name "CHARLIE" :age 45 ...}]
;;  "manager"  [{:name "DIANA"   :age 35 ...}]
;;  "designer" [{:name "EVE"     :age 29 ...}]}

;; Print a summary report. A 3-element `foreach` binds key and value of a map.
(foreach [role members result]
  (println (str "== " (php/strtoupper role) " (" (count members) ") =="))
  (foreach [m members]
    (println (str "  " (get m :name) " (age " (get m :age) ")"))))

;; More pipeline examples:

;; Average age of active users
(def avg-age
  (let [active (filter :active users)
        total-age (reduce + 0 (map :age active))]
    (/ total-age (count active))))
(println (str "Average age of active users: " avg-age))

;; Find the oldest user per role
(def oldest-per-role
  (->> users
       (group-by :role)
       (map (fn [[role members]]
              [role (get (last (sort-by :age members)) :name)]))
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

## Simple Key-Value Store

Build a persistent key-value store backed by a JSON file, with functions for get, put, delete, and listing keys.

```phel
(ns cookbook\kv-store
  (:require phel\json :as json))

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
          (catch \JsonException _ {}))))))

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
(println (str "Keys: " (store-keys)))                       ; (:user-1 :user-2 :config-theme)

(store-delete :user-2)
(println (str "Has :user-2? " (store-has? :user-2)))       ; false

;; Bulk operations using Phel's functional tools
(defn store-put-many [pairs]
  (let [path default-store-path
        data (store-load path)
        updated (reduce (fn [acc [k v]] (assoc acc k v)) data pairs)]
    (store-save path updated)
    updated))

(store-put-many [[:lang "phel"] [:version "0.34"] [:status "awesome"]])
(println (str "All keys: " (store-keys)))
```

**See also:** [Data Structures](/documentation/language/data-structures), [PHP Interop](/documentation/php-interop)

## Defining and Using Protocols

Protocols let you define polymorphic behavior that can be extended to any type -- even types you didn't create. This is similar to PHP interfaces but more flexible.

```phel
(ns cookbook\protocols)

;; Define a protocol for rendering things as HTML
(defprotocol Renderable
  (render-html [this]))

;; Define some structs
(defstruct paragraph [text])
(defstruct heading [level text])
(defstruct link [url label])

;; Extend each struct to implement Renderable
(extend-type paragraph
  Renderable
  (render-html [this]
    (str "<p>" (get this :text) "</p>")))

(extend-type heading
  Renderable
  (render-html [this]
    (let [lvl (get this :level)]
      (str "<h" lvl ">" (get this :text) "</h" lvl ">"))))

(extend-type link
  Renderable
  (render-html [this]
    (str "<a href=\"" (get this :url) "\">" (get this :label) "</a>")))

;; Render a collection of mixed elements
(def page-elements
  [(heading 1 "Welcome")
   (paragraph "This is a Phel-powered page.")
   (link "https://phel-lang.org" "Learn Phel")
   (paragraph "Protocols make this extensible.")])

(def html-output
  (->> page-elements
       (map render-html)
       (apply str)))

(println html-output)
;; => <h1>Welcome</h1><p>This is a Phel-powered page.</p>...

;; Check if a value supports the protocol
(satisfies? Renderable (paragraph "hi"))  ; => true
(satisfies? Renderable "plain string")    ; => false
```

**See also:** [Cheat Sheet -- Protocols](/documentation/reference/cheat-sheet#protocols)

## Data Processing with Transducers

Transducers let you compose data transformation pipelines without creating intermediate collections. They are faster and more memory-efficient than chaining `map`, `filter`, etc.

```phel
(ns cookbook\transducers)

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

;; Compute average response time of API calls using transduce
(defn avg-transducer [xf coll]
  (let [result (transduce xf
                 (completing
                   (fn [[sum cnt] ms] [(+ sum ms) (inc cnt)])
                   (fn [[sum cnt]] (/ sum cnt)))
                 [0 0]
                 coll)]
    result))

(def avg-api-ms
  (avg-transducer
    (comp (filter #(= :api-call (get % :type)))
          (map :ms))
    events))
(println (str "Avg API response: " avg-api-ms "ms"))

;; Use cat to flatten nested collections
(def nested [[1 2 3] [4 5] [6]])
(into [] cat nested)               ; => [1 2 3 4 5 6]
```

**See also:** [Cheat Sheet -- Transducers](/documentation/reference/cheat-sheet#transducers)

## Reader Conditionals for Cross-Platform Code

Reader conditionals allow you to write `.cljc` files that can target different platforms. Use `:phel` for Phel-specific code and `:default` as a fallback.

```phel
(ns cookbook\conditionals)

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

## Regex Matching and Validation

Phel provides regex literals (`#"..."`) and matching functions for working with PCRE patterns.

```phel
(ns cookbook\regex)

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
;; => ("Alice" "Bob" "Charlie")
```

**See also:** [Cheat Sheet -- Regular Expressions](/documentation/reference/cheat-sheet#regular-expressions)

## Structured Exceptions with ex-info

Use `ex-info` to create exceptions that carry structured data, making error handling more informative than plain string messages.

```phel
(ns cookbook\exceptions
  (:require phel\json :as json))

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
    (catch \Exception e
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
    (catch \Exception e
      (throw (ex-info "Config load failed"
                      {:path path :step :read}
                      e)))))

;; Later, inspect the chain
(try
  (load-config "missing.json")
  (catch \Exception e
    (println (str "Error: " (ex-message e)))
    (println (str "Data: " (ex-data e)))
    (when (ex-cause e)
      (println (str "Caused by: " (ex-message (ex-cause e)))))))
```

**See also:** [Cheat Sheet -- Error Handling](/documentation/reference/cheat-sheet#error-handling)

## Pattern Matching with `phel\match`

The `phel\match` module provides a `match` macro with literal, vector, map, wildcard, `:as`, `:guard`, `:or`, and rest-binding patterns.

```phel
(ns cookbook\match
  (:require phel\match :refer [match]))

(defn describe [x]
  (match x
    0              "zero"
    [_ _]          "pair"
    [_ _ & rest]   (str "tuple+" (count rest))
    {:type :error :msg m} (str "error: " m)
    (:or "hi" "hello")    "greeting"
    (:guard n #(php/is_int %)) (str "int " n)
    _              "other"))

(describe 0)                        ; => "zero"
(describe [1 2])                    ; => "pair"
(describe [1 2 3 4])                ; => "tuple+2"
(describe {:type :error :msg "x"}) ; => "error: x"
```

## Schemas with `phel\schema`

Validate, coerce, and generate data from declarative schemas. Kinds include `:vector`, `:set`, `:map`, `:map-of`, `:tuple`, `:enum`, `:and`, `:or`, `:maybe`, `:re`, `:fn`, `:ref`, and function schemas `[:=> args ret]`.

```phel
(ns cookbook\schema
  (:require phel\schema :as s))

(def User
  [:map
   [:id    :int]
   [:name  :string]
   [:role  [:enum :admin :user]]
   [:tags  [:set :keyword]]])

(s/validate User {:id 1 :name "Alice" :role :admin :tags #{:beta}})
; => true

(s/explain User {:id "bad" :name 1 :role :guest :tags []})
; => {:errors [...]}

(s/coerce User {:id "42" :name "Bob" :role "user" :tags ["a"]})
; => {:id 42 :name "Bob" :role :user :tags #{:a}}
```

Instrument a function to check args/return at call sites:

```phel
(defn greet [u] (str "Hi " (:name u)))
(s/instrument! `greet [:=> [User] :string])
```

## Async with `phel\async`

Fiber-backed promises and futures:

```phel
(ns cookbook\async
  (:require phel\async :refer [promise deliver future-call future? deref]))

(def p (promise))
(future-call (fn [] (deliver p 42)))

(deref p)          ; blocks
(deref p 1000 :timeout)  ; 3-arg: wait ms, default on timeout

(def f (future-call (fn [] (+ 1 2))))
(future? f)        ; => true
@f                 ; => 3
```

## File Watching

Reload namespaces on file change:

```phel
(ns cookbook\watcher
  (:require phel\watch :refer [watch!]))

(watch! ["src/" "tests/"])
```

Or from the shell: `vendor/bin/phel watch src/`.

## Property Tests with `phel\test\gen`

```phel
(ns cookbook\gen-tests
  (:require phel\test :refer [deftest is defspec])
  (:require phel\test\gen :as gen))

(defspec addition-commutes
  [a (gen/int) b (gen/int)]
  (is (= (+ a b) (+ b a))))
```

Failing cases shrink automatically; the reported seed makes them reproducible.
