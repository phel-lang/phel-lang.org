+++
title = "Namespaces"
weight = 10
description = "Declare namespaces with ns, require Phel modules and PHP classes, and use aliases, :refer, and namespaced keywords."
aliases = ["/documentation/namespaces"]

[extra]
difficulty = "intermediate"
+++

After this page you can split code across files, load Phel modules and PHP classes into a file, and write namespaced keywords.

## Declare a namespace

Every Phel file starts with `ns`. The name has parts separated by `.`, and the last part matches the file name. Use one namespace per file. Each part starts with a letter or underscore, followed by letters, numbers, underscores or dashes. Use kebab-case (`my.user-service`); the compiler turns dashes into a valid PHP namespace.

<!-- phel-test: skip -->
```phel
(ns hello-world.main
  (:require hello-world.util :as util)   ; Phel module
  (:use Some.Php.ClassName)              ; PHP class
  (:require-file "helpers.php"))         ; PHP file
```

`ns` also sets `*ns*` to the current namespace name. The backslash separator (`hello\world`) still parses but prints a deprecation warning.

{% php_note() %}
```php
// PHP
namespace My\Custom\Module;
use Some\Php\ClassName;
use My\Phel\Module as Utilities;
```

<!-- phel-test: skip -->
```phel
;; Phel
(ns my.custom.module
  (:use Some.Php.ClassName)
  (:require my.phel.module :as utilities))
```

Phel uses `.` between namespace parts, `:require` for Phel modules and `:use` for PHP classes. You call a module function with `/`, not `::`.
{% end %}

## Require a Phel module

`:require` loads another namespace. Give it a short alias with `:as` and call its functions as `alias/name`. Namespaces resolve from `src/` by default; change the source paths in [Configuration](/documentation/reference/configuration/).

A module `hello-world.util`:

```phel
(ns hello-world.util)

(def my-name "Phel")

(defn greet [name]
  (str "Hello, " name))
```

Another module requires it:

<!-- phel-test: skip -->
```phel
(ns hello-world.main
  (:require hello-world.util :as util))

(util/greet util/my-name) ; => "Hello, Phel"
```

Without `:as`, the alias is the last part of the name (`util` here).

### Refer names directly

`:refer` brings chosen names into the current namespace, so you call them without a prefix. It combines with `:as` in any order:

```phel
(ns my.app
  (:require phel.string :as s :refer [join]))

(join ", " ["a" "b" "c"]) ; => "a, b, c"
(s/split "a,b,c" #",")    ; => ["a" "b" "c"]
```

Prefer `:as` for most names. The prefix shows where a function comes from. Keep `:refer` for a few names you call often.

### Shadowed names

A local definition hides a core function with the same short name. The full name still reaches the original:

```phel
(ns hello-world.http-client)

(defn get [uri]
  {:status 200 :body "Hello World"})

(phel.core/get (get "https://example.com") :status) ; => 200
```

## Use a PHP class

`:use` imports a PHP class. Write its namespace with dots. Add `:as` to rename it when two classes share a name:

<!-- phel-test: skip -->
```phel
(ns my.module
  (:use Symfony.Component.String.UnicodeString)
  (:use App.Model.User :as UserModel))

(UnicodeString. "hello")
(UserModel. 42)
```

Importing is optional. `(Some.Php.ClassName.)` works with the full name inline. For calling methods and statics, see [PHP Interop](/documentation/language/php-interop/).

## Require a PHP file

`:require-file` loads a PHP file with `require_once` before the rest of the namespace:

<!-- phel-test: skip -->
```phel
(ns hello-world.main
  (:require-file "vendor/autoload.php"))
```

`vendor/bin/phel` loads Composer's autoloader for you. You need the line above when you run Phel another way, for example from the PHAR. Use `:require-file` instead of `(php/require_once ...)` for an autoloader: a call in the body runs too late, because Phel's core needs the autoloader first.

## Namespaced keywords

Plain keywords can collide when libraries share data. A namespaced keyword adds a namespace before the name:

```phel
:my.namespace/foo ; namespaced keyword

(ns bar)
::foo             ; => :bar/foo
```

`::` fills in the current namespace. With an alias, `::alias/name` expands to the aliased namespace:

```phel
(ns my.app
  (:require phel.string :as s))

::s/foo ; => :phel.string/foo
```
