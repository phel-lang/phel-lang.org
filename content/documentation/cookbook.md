+++
title = "Cookbook"
weight = 20
+++

Practical recipes for common tasks in Phel. Each example is self-contained and ready to use.

## Read and Process a CSV File

Read a CSV file and parse it into a vector of maps, where each map represents a row with column headers as keys.

```phel
(ns cookbook\csv-reader)

# Read a CSV file and return a vector of maps
# Each row becomes a map with header names as keys
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
                (recur (conj rows (apply hash-map (flatten row))))))))))))

# Example usage:
# Given a file "users.csv" with contents:
#   name,email,role
#   Alice,alice@example.com,admin
#   Bob,bob@example.com,editor

(def users (read-csv "users.csv"))
# => [{:name "Alice" :email "alice@example.com" :role "admin"}
#     {:name "Bob" :email "bob@example.com" :role "editor"}]

# Process the parsed data
(def admin-emails
  (->> users
       (filter |(= "admin" (get $ :role)))
       (map :email)))
# => ["alice@example.com"]
```

**See also:** [PHP Interop](/documentation/php-interop), [Data Structures](/documentation/data-structures)

## Build a Simple CLI Tool

A command-line script that reads arguments, parses simple flags, and produces output.

```phel
(ns cookbook\cli-tool)

# Access command-line arguments via PHP's $argv
# When running: vendor/bin/phel run src/cli-tool.phel --name Alice --greeting Hi
(def args (let [argv (php/aget php/$_SERVER "argv")]
            # Skip the first two args (phel binary and script path)
            (for [i :range [2 (php/count argv)]]
              (php/aget argv i))))

# Parse flags into a map of --key value pairs
(defn parse-flags [args]
  (loop [remaining args
         flags {}]
    (if (empty? remaining)
      flags
      (let [current (first remaining)
            rest-args (rest remaining)]
        (if (php/str_starts_with current "--")
          (let [key (keyword (php/substr current 2))
                value (first rest-args)]
            (recur (rest rest-args) (assoc flags key value)))
          (recur rest-args flags))))))

# Build the tool
(defn run []
  (let [flags (parse-flags args)
        name (get flags :name "World")
        greeting (get flags :greeting "Hello")
        repeat-count (php/intval (get flags :repeat "1"))]
    (dotimes [_ repeat-count]
      (println (str greeting ", " name "!")))))

(run)
# Running: vendor/bin/phel run src/cli-tool.phel --name Alice --repeat 3
# Output:
#   Hello, Alice!
#   Hello, Alice!
#   Hello, Alice!
```

**See also:** [PHP Interop](/documentation/php-interop), [Control Flow](/documentation/control-flow)

## HTTP Request with cURL

Make an HTTP GET request using PHP's cURL functions and parse a JSON response.

```phel
(ns cookbook\http-client)

# Perform an HTTP GET request and return the response body as a string
(defn http-get [url]
  (let [ch (php/curl_init)]
    (php/curl_setopt ch php/CURLOPT_URL url)
    (php/curl_setopt ch php/CURLOPT_RETURNTRANSFER true)
    (php/curl_setopt ch php/CURLOPT_FOLLOWLOCATION true)
    (php/curl_setopt ch php/CURLOPT_TIMEOUT 30)
    (let [response (php/curl_exec ch)
          error (php/curl_error ch)
          status (php/curl_getinfo ch php/CURLINFO_HTTP_CODE)]
      (php/curl_close ch)
      (if (= false response)
        {:error error :status 0}
        {:body response :status status}))))

# Parse a JSON string into a Phel map
(defn parse-json [json-string]
  (let [decoded (php/json_decode json-string true)]
    (if (nil? decoded)
      {:error (php/json_last_error_msg)}
      decoded)))

# Fetch data from a JSON API
(defn fetch-json [url]
  (let [result (http-get url)]
    (if (get result :error)
      result
      (parse-json (get result :body)))))

# Example: fetch a list of todos from a public API
(def response (fetch-json "https://jsonplaceholder.typicode.com/todos/1"))
# response is a PHP associative array, access with php/aget
(println (str "Title: " (php/aget response "title")))
(println (str "Completed: " (if (php/aget response "completed") "yes" "no")))

# Example: fetch multiple items and process them
(defn fetch-todos [limit]
  (let [data (fetch-json (str "https://jsonplaceholder.typicode.com/todos?_limit=" limit))]
    (for [i :range [0 (php/count data)]]
      (let [todo (php/aget data i)]
        {:id (php/aget todo "id")
         :title (php/aget todo "title")
         :completed (php/aget todo "completed")}))))

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

# Generate a simple page layout
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

# Generate a user card component
(defn user-card [user]
  [:div {:class "card"}
    [:h3 (get user :name)]
    [:p (str "Email: " (get user :email))]
    [:span {:class [:badge (if (get user :active) "active" "inactive")]}
      (if (get user :active) "Active" "Inactive")]])

# Generate a navigation bar
(defn nav [links]
  [:nav
    [:ul {:style {:list-style "none" :display "flex" :gap "1rem" :padding "0"}}
      (for [link :in links]
        [:li [:a {:href (get link :url)} (get link :label)]])]])

# Build a complete page with dynamic content
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

**See also:** [HTML Rendering](/documentation/html-rendering)

## Working with Dates

Use PHP's DateTime classes via Phel interop to create, format, and compare dates.

```phel
(ns cookbook\dates
  (:use \DateTimeImmutable)
  (:use \DateInterval)
  (:use \DateTimeZone))

# Create dates
(def now (php/new DateTimeImmutable))
(def specific-date (php/new DateTimeImmutable "2024-06-15"))
(def from-format
  (php/:: DateTimeImmutable (createFromFormat "d/m/Y" "25/12/2024")))

# Format dates
(println (php/-> now (format "Y-m-d H:i:s")))       # 2024-03-10 14:30:00
(println (php/-> now (format "l, F j, Y")))          # Sunday, March 10, 2024
(println (php/-> specific-date (format "D, M j")))   # Sat, Jun 15

# Date arithmetic — add and subtract intervals
(def tomorrow
  (php/-> now (modify "+1 day")))
(def next-week
  (php/-> now (modify "+7 days")))
(def three-months-later
  (php/-> now (add (php/new DateInterval "P3M"))))

(println (str "Tomorrow: " (php/-> tomorrow (format "Y-m-d"))))
(println (str "Next week: " (php/-> next-week (format "Y-m-d"))))
(println (str "In 3 months: " (php/-> three-months-later (format "Y-m-d"))))

# Compare dates
(defn date-before? [a b]
  (< (php/-> a (getTimestamp)) (php/-> b (getTimestamp))))

(defn date-after? [a b]
  (> (php/-> a (getTimestamp)) (php/-> b (getTimestamp))))

(println (str "Tomorrow is after today: " (date-after? tomorrow now)))  # true

# Calculate the difference between two dates
(defn days-between [date1 date2]
  (let [interval (php/-> date1 (diff date2))]
    (php/-> interval days)))

(def start (php/new DateTimeImmutable "2024-01-01"))
(def end (php/new DateTimeImmutable "2024-12-31"))
(println (str "Days in 2024: " (days-between start end)))  # 365

# Work with time zones
(def utc-now (php/new DateTimeImmutable "now" (php/new DateTimeZone "UTC")))
(def tokyo-now
  (php/-> utc-now (setTimezone (php/new DateTimeZone "Asia/Tokyo"))))

(println (str "UTC:   " (php/-> utc-now (format "H:i:s"))))
(println (str "Tokyo: " (php/-> tokyo-now (format "H:i:s"))))

# Utility: human-readable relative time
(defn time-ago [date]
  (let [seconds (- (php/-> (php/new DateTimeImmutable) (getTimestamp))
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

# Read entire file contents
(defn read-file [path]
  (let [contents (php/file_get_contents path)]
    (if (= false contents)
      nil
      contents)))

# Write content to a file (creates or overwrites)
(defn write-file [path content]
  (let [result (php/file_put_contents path content)]
    (if (= false result)
      (do (println (str "Error: could not write to " path)) false)
      true)))

# Append content to a file
(defn append-file [path content]
  (let [result (php/file_put_contents path content php/FILE_APPEND)]
    (if (= false result)
      (do (println (str "Error: could not append to " path)) false)
      true)))

# Check if a file or directory exists
(defn exists? [path]
  (php/file_exists path))

(defn file? [path]
  (php/is_file path))

(defn directory? [path]
  (php/is_dir path))

# List directory contents, excluding . and ..
(defn list-dir [path]
  (if (not (directory? path))
    []
    (let [entries (php/scandir path)]
      (for [i :range [0 (php/count entries)]
            :let [entry (php/aget entries i)]
            :when (and (not= entry ".") (not= entry ".."))]
        entry))))

# List files matching a pattern
(defn glob-files [pattern]
  (let [matches (php/glob pattern)]
    (if (= false matches)
      []
      (for [i :range [0 (php/count matches)]]
        (php/aget matches i)))))

# Get file info
(defn file-info [path]
  (if (not (exists? path))
    nil
    {:path path
     :size (php/filesize path)
     :modified (php/filemtime path)
     :readable (php/is_readable path)
     :writable (php/is_writable path)}))

# Create directory recursively
(defn mkdir [path]
  (when (not (exists? path))
    (php/mkdir path 0755 true)))

# Example usage
(write-file "output/example.txt" "Hello from Phel!\n")
(append-file "output/example.txt" "Another line.\n")

(when (exists? "output/example.txt")
  (println (read-file "output/example.txt")))

# List all .phel files in a directory
(def phel-files (glob-files "src/**/*.phel"))
(foreach [f phel-files]
  (println (str "Found: " f)))

# Get info about each file
(def file-report
  (->> phel-files
       (map file-info)
       (sort-by :size)
       (reverse)))
```

**See also:** [PHP Interop](/documentation/php-interop)

## Data Transformation Pipeline

Take raw data, filter it, transform it, and group it using Phel's threading macros and collection functions.

```phel
(ns cookbook\data-pipeline)

# Sample dataset: a vector of user maps
(def users
  [{:name "Alice"   :age 32 :role "engineer" :active true}
   {:name "Bob"     :age 28 :role "designer" :active false}
   {:name "Charlie" :age 45 :role "engineer" :active true}
   {:name "Diana"   :age 35 :role "manager"  :active true}
   {:name "Eve"     :age 29 :role "designer" :active true}
   {:name "Frank"   :age 52 :role "manager"  :active false}
   {:name "Grace"   :age 38 :role "engineer" :active true}])

# Pipeline: get active users, uppercase names, sort by age, group by role
(def result
  (->> users
       (filter :active)                              # keep only active users
       (map |(assoc $ :name (php/strtoupper (get $ :name))))  # uppercase names
       (sort-by :age)                                # sort by age ascending
       (group-by :role)))                            # group into a map by role

# result =>
# {"engineer" [{:name "ALICE"   :age 32 ...}
#              {:name "GRACE"   :age 38 ...}
#              {:name "CHARLIE" :age 45 ...}]
#  "manager"  [{:name "DIANA"   :age 35 ...}]
#  "designer" [{:name "EVE"     :age 29 ...}]}

# Print a summary report
(foreach [role members result]
  (println (str "== " (php/strtoupper role) " (" (count members) ") =="))
  (foreach [m members]
    (println (str "  " (get m :name) " (age " (get m :age) ")"))))

# More pipeline examples:

# Average age of active users
(def avg-age
  (let [active (filter :active users)
        total-age (reduce + 0 (map :age active))]
    (/ total-age (count active))))
(println (str "Average age of active users: " avg-age))

# Find the oldest user per role
(def oldest-per-role
  (->> users
       (group-by :role)
       (map (fn [[role members]]
              [role (get (last (sort-by :age members)) :name)]))
       (apply hash-map (flatten $&))))

# Count users by status
(def status-counts
  {:active (count (filter :active users))
   :inactive (count (filter |(not (get $ :active)) users))})
(println (str "Active: " (get status-counts :active)
              ", Inactive: " (get status-counts :inactive)))

# Extract unique roles
(def roles
  (->> users
       (map :role)
       (into #{})))
(println (str "Roles: " roles))
```

**See also:** [Data Structures](/documentation/data-structures), [Control Flow](/documentation/control-flow)

## Simple Key-Value Store

Build a persistent key-value store backed by a JSON file, with functions for get, put, delete, and listing keys.

```phel
(ns cookbook\kv-store)

# Path to the JSON storage file
(def default-store-path "data/store.json")

# Load the store from disk, returning a Phel map
(defn store-load [path]
  (if (not (php/file_exists path))
    {}
    (let [contents (php/file_get_contents path)]
      (if (or (= false contents) (= "" contents))
        {}
        (let [decoded (php/json_decode contents true)]
          (if (nil? decoded)
            {}
            # Convert PHP associative array to Phel map
            (for [[k v] :pairs decoded :reduce [m {}]]
              (assoc m k v))))))))

# Save the store to disk as JSON
(defn store-save [path data]
  (let [dir (php/dirname path)]
    (when (not (php/is_dir dir))
      (php/mkdir dir 0755 true))
    # Convert Phel map to PHP array for json_encode
    (let [php-arr (php/array)]
      (foreach [k v data]
        (php/aset php-arr k v))
      (php/file_put_contents
        path
        (php/json_encode php-arr php/JSON_PRETTY_PRINT)))))

# Get a value by key, with an optional default
(defn store-get
  ([key] (store-get default-store-path key nil))
  ([key default] (store-get default-store-path key default))
  ([path key default]
    (get (store-load path) key default)))

# Put a key-value pair into the store
(defn store-put
  ([key value] (store-put default-store-path key value))
  ([path key value]
    (let [data (store-load path)
          updated (assoc data key value)]
      (store-save path updated)
      updated)))

# Delete a key from the store
(defn store-delete
  ([key] (store-delete default-store-path key))
  ([path key]
    (let [data (store-load path)
          updated (dissoc data key)]
      (store-save path updated)
      updated)))

# List all keys in the store
(defn store-keys
  ([] (store-keys default-store-path))
  ([path] (keys (store-load path))))

# Check if a key exists
(defn store-has?
  ([key] (store-has? default-store-path key))
  ([path key] (contains? (store-load path) key)))

# Example usage
(store-put "user:1" "Alice")
(store-put "user:2" "Bob")
(store-put "config:theme" "dark")

(println (str "User 1: " (store-get "user:1")))           # Alice
(println (str "User 3: " (store-get "user:3" "unknown"))) # unknown
(println (str "Keys: " (store-keys)))                      # ("user:1" "user:2" "config:theme")

(store-delete "user:2")
(println (str "Has user:2? " (store-has? "user:2")))       # false

# Bulk operations using Phel's functional tools
(defn store-put-many [pairs]
  (let [path default-store-path
        data (store-load path)
        updated (reduce (fn [acc [k v]] (assoc acc k v)) data pairs)]
    (store-save path updated)
    updated))

(store-put-many [["lang" "phel"] ["version" "0.29"] ["status" "awesome"]])
(println (str "All keys: " (store-keys)))
```

**See also:** [Data Structures](/documentation/data-structures), [PHP Interop](/documentation/php-interop)
