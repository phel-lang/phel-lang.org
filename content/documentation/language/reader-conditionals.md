+++
title = "Reader Conditionals"
weight = 14
description = "Write platform-specific code in shared .cljc source files with #?() and #?@(), resolved at parse time using :phel and :default keys"

[extra]
difficulty = "advanced"
+++

Reader conditionals let one source file hold platform-specific code. They resolve during the **parsing phase**, before compilation, so the analyzer and emitter only ever see the selected form. Phel picks the `:phel` branch, ignores other platforms (`:clj`, `:cljs`, ...), and falls back to `:default` when present.

This makes `.cljc` files shareable between Phel, Clojure, and other Lisp dialects. If you are arriving from Clojure, the syntax is identical; see [Coming from Clojure](/documentation/guides/coming-from-clojure/#reader-conditionals).

## Selecting a form: `#?()`

`#?()` reads keyword/form pairs and keeps exactly one:

```phel
(println
  #?(:phel (php/time)
     :clj  (System/currentTimeMillis)
     :cljs (js/Date.now)))
; In Phel this is just (php/time); the :clj and :cljs branches are dropped at parse time.
```

### Platform keys

| Key | Platform | Matched by Phel? |
|-----|----------|-------------------|
| `:phel` | Phel | Yes |
| `:default` | Any platform (fallback) | Yes (when no `:phel`) |
| `:clj` | Clojure (JVM) | No |
| `:cljs` | ClojureScript | No |
| any other | ignored | No |

### Priority

1. `:phel` always wins when present, regardless of position.
2. `:default` is the fallback when there is no `:phel` branch.
3. If neither is present, the whole form is dropped (treated as whitespace).

```phel
(println #?(:default 0 :phel 42))   ; => 42  (:phel wins)
(println #?(:clj 99 :default 0))    ; => 0   (fallback)
;; #?(:clj 99 :cljs 88) reads as nothing and is dropped entirely.
```

## Splicing: `#?@()`

`#?@()` splices the elements of the matched collection into the surrounding form and removes the wrapper. The selected branch **must be a sequential collection** (vector or list):

```phel
(println [1 #?@(:phel [2 3]) 4])              ; => [1 2 3 4]
(println (php/array 0 #?@(:phel [1 2 3]) 4))  ; => array(0, 1, 2, 3, 4)

;; Fallback and no-match behave like #?():
(println [1 #?@(:clj [8 9] :default [2 3]) 4]) ; => [1 2 3 4]
(println [1 #?@(:clj [8 9]) 4])                ; => [1 4]
```

### Top-level restriction

`#?@()` is only valid **inside** a collection (list, vector, map, or set). Splicing at the top level is an error, because there is no parent form to splice into:

```phel skip
;; ERROR: Reader conditional splicing #?@() is not allowed at the top level
#?@(:phel [1 2])
```

## Use cases

### Cross-platform source files (`.cljc`)

Phel discovers and compiles `.cljc` files alongside `.phel` files, so a single file can serve multiple runtimes:

```phel
;; src/shared/utils.cljc
(ns shared.utils)

(defn now []
  #?(:phel (php/time)
     :clj  (/ (System/currentTimeMillis) 1000)))

(defn platform []
  #?(:phel    "phel"
     :clj     "clojure"
     :cljs    "clojurescript"
     :default "unknown"))

(println (platform)) ; => "phel"
```

> **Tip:** use `.` as the [namespace](/documentation/language/namespaces/) separator (`shared.utils`) so `.cljc` files parse cleanly under Clojure too. The legacy `\` separator still resolves but is deprecated.

### Platform-specific dependencies

Reader conditionals work inside the `(ns ...)` form, so each platform can require its own libraries:

```phel
(ns app.http
  #?(:phel (:require [phel.json :as json])
     :clj  (:require [clojure.data.json :as json])))

(defn parse [s]
  #?(:phel (json/decode s)
     :clj  (json/read-str s)))
```

> Phel accepts both vector entries (`[phel.json :as json :refer [encode]]`) and the list form (`phel.json :as json`) inside `:require`, so the same `(ns ...)` parses on both sides.

### Conditional data structures

Use splicing to add platform-specific entries to a map:

```phel
(def config
  {:name "my-app"
   :version "1.0"
   #?@(:phel [:runtime "php" :min-version "8.4"]
       :clj  [:runtime "jvm" :min-version "21"])})

(println config)
; => {:name "my-app" :version "1.0" :runtime "php" :min-version "8.4"}
```

### Inside control flow

Because conditionals resolve at parse time, they nest inside any form:

```phel
(if #?(:phel true :clj false)
  (println "Running on Phel!")
  (println "Running on Clojure!"))
; => "Running on Phel!"
```

## Summary

| Syntax | Name | Behavior | Context |
|--------|------|----------|---------|
| `#?()` | Reader conditional | Selects one form by platform key | Anywhere |
| `#?@()` | Reader conditional splicing | Splices collection elements into parent | Inside collections only |

## Next steps

- [Namespaces](/documentation/language/namespaces/) - the `ns` form that conditional `:require` entries plug into
- [Cookbook -- Reader conditionals for cross-platform code](/documentation/guides/cookbook/#reader-conditionals-for-cross-platform-code) - a worked `.cljc` recipe
- [Cheat sheet](/documentation/reference/cheat-sheet/) - keep it open while coding
