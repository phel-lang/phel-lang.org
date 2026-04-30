+++
title = "Namespaces"
weight = 9
aliases = ["/documentation/namespaces"]
+++

## Namespace (ns)

Every Phel file needs a namespace. Names start with a letter, then letters/numbers/dashes. Parts separated by `\`. Last part must match filename.

```phel
(ns name imports*)
```

Sets the namespace and registers imports. `:use` for PHP classes, `:require` for Phel modules, `:require-file` for PHP files.

```phel
(ns my\custom\module
  (:require-file "vendor/autoload.php")
  (:require my\phel\module)
  (:use Some\Php\Class))
```

Also sets `*ns*` to the namespace.

{% php_note() %}
Similar to PHP namespaces, with differences:

```php
// PHP
namespace My\Custom\Module;
use Some\Php\Class;
use My\Phel\Module as Utilities;

// Phel
(ns my\custom\module
  (:use Some\Php\Class)
  (:require my\phel\module :as utilities))
```

**Differences:**
- `\` separator (like PHP)
- `:require` for Phel modules, `:use` for PHP classes
- Access via `/`, not `::`
{% end %}

{% clojure_note() %}
Like Clojure, but:
- `\` separator instead of `.` (PHP convention)
- `:use` is for PHP classes
- `:require` works as in Clojure
{% end %}

### Import a Phel module

Import with `:require`, then access as `module/name`. Namespaces resolve from `src/` (override with [SrcDirs](/documentation/configuration/#srcdirs)).

Module `util` in namespace `hello-world`:

```phel
(ns hello-world\util)

(def my-name "Phel")

(defn greet [name]
  (print (str "Hello, " name)))
```

Module `boot` imports `util`:

```phel
(ns hello-world\boot
  (:require hello-world\util))

(util/greet util/my-name)
```

Use aliases to avoid collisions:

```phel
(ns hello-world\boot
  (:require hello-world\util :as utilities))
```

On collision, prefix with the namespace (e.g. `phel\core`). Names retain values from their original namespace before redefinition.

```phel
(ns hello-world\http-client)

(defn get [uri]
  {:status 200 :body "Hello World" :headers {}})

(phel\core/get (get "https://example.com") :status) ; Evaluates to 200
```

`:refer` brings symbols into the current namespace:

```phel
(ns hello-world\boot
  (:require hello-world\util :refer [greet]))

(greet util/my-name)
```

`:refer` and `:as` combine in any order.

### Import a PHP class

`:use` imports PHP classes:

```phel
(ns my\custom\module
  (:use Some\Php\ClassName)
```

Reference by name:

```phel
(php/new ClassName)
```

Aliases avoid collisions:

```phel
(ns my\custom\module
  (:use Some\Php\ClassName :as BetterClassName)
```

Importing is preferred, but optional. Use full namespace inline if needed:

```phel
(php/new \Some\Php\ClassName)
```

## Require PHP files

Load external PHP files via `:require-file` (calls `require_once`). Example for Composer autoload:

```
(ns hello-world\boot
  (:require-file "vendor/autoload.php"))
```

`(php/require_once "vendor/autoload.php")` works elsewhere, but for autoload it runs too late since Phel's core needs the autoloader. Use `:require-file`.

## Namespaced keywords

Plain keywords collide when sharing data. Namespaced keywords solve this.

Fully qualified: namespace, `/`, keyword name.

```phel
:my\namespace/foo ; absolute namespaced keyword
```

`::` shortcut binds current namespace:

```phel
(ns bar)
::foo ; => :bar/foo
```

`ns` aliases also work:

```phel
(ns foobar
  (:require abc\xyz :as bar))
  ::bar/foo ; evaluates to :abc\xyz/bar
```
