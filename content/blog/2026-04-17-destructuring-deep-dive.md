+++
title = "Destructuring Deep Dive in Phel"
aliases = [ "/blog/destructuring-deep-dive" ]
description = "Pull apart nested vectors and maps, use :keys, :or, :as, & rest, and destructure JSON payloads the way Clojure developers do."
+++

If you have read the [immutability post](/blog/immutability-in-phel) you already know Phel's collections are persistent and nested. What you probably spend half your time doing is pulling pieces *out* of them. Destructuring is how you stop writing that glue.

PHP gives you array unpacking (`[$a, $b] = [1, 2]`) and keyed destructuring (`['name' => $name] = $data`). Phel gives you a tiny pattern language that works in `let`, function parameters, and `loop` bindings, with nesting, defaults, rest capture, and aliasing.

## The shape is the code

The rule is one line: **write the shape of the data on the left, put the data on the right**. Phel walks both together and binds names wherever you wrote a symbol.

```phel
(let [[a b c] [1 2 3]]
  (+ a b c)) ; => 6
```

Same idea for maps:

```phel
(let [{:name name :age age} {:name "Ada" :age 36}]
  (str name " is " age)) ; => "Ada is 36"
```

No helper functions, no `nth`, no `get`. The pattern is the extraction.

## Nesting without ceremony

Patterns compose. Put a vector pattern inside a map pattern, put a map pattern inside a vector pattern, nest as deep as your data goes:

```phel
(let [{:user {:name name :roles [primary & other]}}
      {:user {:name "Ada"
              :roles [:admin :editor :reviewer]}}]
  [name primary other])
;; => ["Ada" :admin [:editor :reviewer]]
```

One binding form, three names pulled from three different depths. Try that with `$data['user']['roles'][0]` and you end up with the same six lookups and a stack of `isset()` checks.

## `& rest`: keep the tail

Sequential patterns support `&` to capture everything past a given position:

```phel
(let [[first-line & body] ["GET /users HTTP/1.1"
                           "Host: example.com"
                           "Accept: application/json"]]
  {:request-line first-line
   :headers      body})
```

Pair `&` with recursion for clean list processing:

```phel
(defn sum [[head & tail]]
  (if (nil? head)
    0
    (+ head (sum tail))))

(sum [1 2 3 4 5]) ; => 15
```

Note: this version is not tail-recursive, so deep inputs can blow the stack. For real work use `loop`/`recur` (covered below) to keep the recursion flat.

## `:keys`: stop repeating yourself

Writing `{:name name :age age :role role}` gets old fast. `:keys` is the shorthand: give it a vector of symbols and Phel assumes the keys are keywords with the same name:

```phel
(let [{:keys [name age role]}
      {:name "Ada" :age 36 :role :admin}]
  (str name ", " age ", " role))
```

Prefer string keys (like you get from `json_decode` with `true`)? Use `:strs`:

```phel
(let [{:strs [name age]}
      (php/json_decode "{\"name\":\"Ada\",\"age\":36}" true)]
  [name age])
```

## `:or`: defaults for missing keys

Missing keys destructure to `nil`. That is rarely what you want for config or options maps. `:or` provides a default per binding:

```phel
(defn connect [{:keys [host port timeout]
                :or   {host "localhost"
                       port 5432
                       timeout 30}}]
  (str "Connecting to " host ":" port " (timeout " timeout "s)"))

(connect {:host "db.internal"})
;; => "Connecting to db.internal:5432 (timeout 30s)"
```

The default only fires when the key is absent. Explicit `nil` stays `nil`.

## `:as`: keep the whole thing

Sometimes you destructure *and* still want the original value: to log it, pass it along, or fall back on keys you did not name. `:as` binds the whole value to a symbol:

```phel
(defn audit [{:keys [user action] :as event}]
  (println "event:" event)
  (str user " did " action))

(audit {:user "ada" :action "login" :ip "10.0.0.1"})
;; => "ada did login"
```

`:as` works inside map destructuring (sequential vector patterns do not currently support it; bind the source to a name with `let` first if you need both the parts and the whole).

## Real-world example: parsing a JSON webhook

Here is the payload shape for a simplified GitHub-style pull request event:

```json
{
  "action": "opened",
  "number": 42,
  "pull_request": {
    "title": "Fix auth middleware",
    "user":  { "login": "ada" },
    "labels": [
      { "name": "bug" },
      { "name": "priority:high" }
    ]
  }
}
```

Use `phel\json/decode`. It turns JSON objects into Phel maps with keyword keys, so `:keys` works directly:

```phel
(ns my\app
  (:require phel\json :as json))

(defn handle-pr-event [payload]
  (let [{:keys [action number pull_request]}  payload
        {:keys [title user labels]}           pull_request
        {:keys [login]}                       user
        label-names (map |(get $ :name) labels)]
    (println (str "PR #" number " by " login ": " title))
    (println (str "Action: " action))
    (println (str "Labels: " (php/implode ", " (to-array label-names))))))

(handle-pr-event
  (json/decode
    "{\"action\":\"opened\",\"number\":42,\"pull_request\":{\"title\":\"Fix auth middleware\",\"user\":{\"login\":\"ada\"},\"labels\":[{\"name\":\"bug\"},{\"name\":\"priority:high\"}]}}"))
```

Three `:keys` patterns, one `map` over labels. Zero `isset()` chains. If a field is missing you get a `nil` instead of a PHP warning, and `:or` can supply defaults where you care.

If you already hold a plain PHP associative array (from a third-party library, say), use `:strs` instead. It destructures by string key. For anything coming through `phel\json`, stick with `:keys`.

## Destructuring in function parameters

Everything above works directly in `defn` / `fn`. It turns function signatures into mini documentation:

```phel
(defn distance [[x1 y1] [x2 y2]]
  (php/sqrt (+ (* (- x2 x1) (- x2 x1))
               (* (- y2 y1) (- y2 y1)))))

(distance [0 0] [3 4]) ; => 5.0
```

Or combined with options maps, the standard Clojure pattern for functions with many optional arguments:

```phel
(defn make-request
  [url {:keys [method headers timeout]
        :or   {method "GET"
               headers {}
               timeout 30}
        :as   opts}]
  ...)
```

The caller gets a single map, the body gets individual names *and* the full map if it needs to pass the options along untouched.

## Loop bindings and `recur`

`loop` accepts the same patterns, so recursion on structured data stays readable:

```phel
(defn parse-csv-line [line]
  (loop [[field & rest] (vec (php/explode "," line))
         acc []]
    (if (nil? field)
      acc
      (recur rest (conj acc (php/trim field))))))

(parse-csv-line " foo, bar ,baz ")
;; => ["foo" "bar" "baz"]
```

## A few gotchas

- **`_` is just a symbol.** Phel does not give it special meaning like Clojure's `_` convention; it is a normal binding you agree to ignore. Do not rely on it being "unused": the value is still evaluated.
- **Order of `:keys` / `:or` / `:as` does not matter.** Phel's reader collects them regardless of position inside the map pattern.
- **`:or` defaults are inert expressions, not lazy values.** They are evaluated only when the key is missing, but they are evaluated in the surrounding scope. No special deferred semantics.
- **`&` rest yields a sequence, not always the same collection type.** For a vector source you get a sub-vector; for a list you get a list. If you need a concrete vector, call `(vec rest)`.

## When not to destructure

Destructuring makes shape obvious. It also makes noise when the shape is trivial. If you only need one field from a ten-key map, `(get event :action)` is shorter and clearer than wrapping it in a `let`. Save destructuring for when it earns its keep: when you pull several names or walk into nested structure.

## Go pull things apart

Spot a function in your codebase that opens with three or four `get` calls. Rewrite its parameter list with a destructuring pattern. The body shrinks, the signature documents the input, and the intent jumps off the page.

When you want to dig further, the [destructuring reference](/documentation/language/destructuring/) has the full grammar, and the [pattern matching post](/blog/pattern-matching) shows where destructuring pairs up with `case` and `cond` for branching logic.
