+++
title = "Reader Conditionals"
weight = 16
description = "Write platform-specific code in shared .cljc source files with #?() and #?@(), resolved at parse time using :phel and :default keys."

[extra]
difficulty = "advanced"
+++

Reader conditionals let one source file hold code for several platforms. After this page you can write a `.cljc` file that runs on both Phel and Clojure.

| Syntax | Does | Where |
|--------|------|-------|
| `#?(...)` | keeps one form, chosen by platform key | anywhere |
| `#?@(...)` | splices the items of the chosen collection into the parent form | inside a collection only |

Both resolve while Phel parses the file, before compilation. The compiler only sees the selected form. The syntax is the same as in Clojure; see also [Coming from Clojure](/documentation/guides/coming-from-clojure/#reader-conditionals).

## Choose a form with `#?()`

`#?()` reads key and form pairs and keeps one:

```phel
#?(:phel (php/time)
   :clj  (System/currentTimeMillis)
   :cljs (js/Date.now))
; In Phel this reads as (php/time). The other branches are dropped.
```

Phel picks a branch by these rules:

1. `:phel` wins when present, in any position.
2. Otherwise `:default` is used.
3. With neither, the whole form is dropped, as if it were whitespace.

Other keys (`:clj`, `:cljs`, anything else) are ignored.

```phel
#?(:default 0 :phel 42) ; => 42
#?(:clj 99 :default 0)  ; => 0
[1 #?(:clj 99) 2]       ; => [1 2]
```

## Splice with `#?@()`

`#?@()` inserts the items of the chosen vector or list into the surrounding form. The branch must be a vector or a list:

```phel
[1 #?@(:phel [2 3]) 4]                   ; => [1 2 3 4]
[1 #?@(:clj [8 9] :default [2 3]) 4]     ; => [1 2 3 4]
[1 #?@(:clj [8 9]) 4]                    ; => [1 4]
```

It works in maps too, which is useful for platform-specific entries:

```phel
(def config
  {:name "my-app"
   #?@(:phel [:runtime "php" :min-version "8.5"]
       :clj  [:runtime "jvm" :min-version "21"])})

config ; => {:name "my-app", :runtime "php", :min-version "8.5"}
```

`#?@()` at the top level of a file is an error, because there is no parent form to splice into:

<!-- phel-test: skip -->
```phel
;; ERROR: Reader conditional splicing #?@() is not allowed at the top level
#?@(:phel [1 2])
```

## Share `.cljc` files

Phel finds and compiles `.cljc` files next to `.phel` files, so one file can serve both runtimes:

```phel
;; src/shared/utils.cljc
(ns shared.utils)

(defn now []
  #?(:phel (php/time)
     :clj  (quot (System/currentTimeMillis) 1000)))

(defn platform []
  #?(:phel    "phel"
     :clj     "clojure"
     :default "unknown"))

(platform) ; => "phel"
```

Use `.` as the [namespace](/documentation/language/namespaces/) separator (`shared.utils`), so the file also parses under Clojure.

### Platform-specific requires

Reader conditionals work inside `ns`, so each platform can require its own libraries:

```phel
(ns app.http
  #?(:phel (:require [phel.json :as json])
     :clj  (:require [clojure.data.json :as json])))

(defn parse [s]
  #?(:phel (json/decode s)
     :clj  (json/read-str s)))
```

Phel accepts both the vector form (`[phel.json :as json]`) and the list form (`phel.json :as json`) inside `:require`, so the same `ns` parses on both sides.

For a worked example, see [Cookbook: reader conditionals for cross-platform code](/documentation/guides/cookbook/#reader-conditionals-for-cross-platform-code).
