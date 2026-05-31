+++
title = "Namespaces"
weight = 8
description = "Declare namespaces with ns, require Phel modules and PHP classes, and use aliases, :refer, and namespaced keywords"
aliases = ["/documentation/namespaces"]
+++

How Phel organizes code across files: every file declares a namespace with `ns`, then pulls in Phel modules and PHP classes through requires.

## Namespace (ns)

Every Phel file needs a namespace. Names start with a letter, then letters/numbers/dashes. Parts separated by `.` (canonical) or `\` (legacy, still parses). Last part must match filename.

```phel
(ns name imports*)
```

Sets the namespace and registers imports. `:use` for PHP classes, `:require` for Phel modules, `:require-file` for PHP files.

```phel
(ns my.custom.module
  (:require-file "vendor/autoload.php")
  (:require my.phel.module)
  (:use Some.Php.Class))
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
(ns my.custom.module
  (:use Some.Php.Class)
  (:require my.phel.module :as utilities))
```

**Differences:**
- `.` separator for Phel namespaces (PHP class FQNs in `:use` use `.`)
- `:require` for Phel modules, `:use` for PHP classes
- Access via `/`, not `::`
{% end %}

{% clojure_note() %}
Like Clojure: `.` namespace separator. PHP class FQNs in `:use` use `.`.
- `:use` is for PHP classes
- `:require` works as in Clojure
{% end %}

### Import a Phel module

Import with `:require`, then access as `module/name`. Namespaces resolve from `src/` (override with [configuration](/documentation/configuration/)).

Module `util` in namespace `hello-world`:

```phel
(ns hello-world.util)

(def my-name "Phel")

(defn greet [name]
  (print (str "Hello, " name)))
```

Module `main` imports `util`:

```phel
(ns hello-world.main
  (:require hello-world.util))

(util/greet util/my-name)
```

Use aliases to avoid collisions:

```phel
(ns hello-world.main
  (:require hello-world.util :as utilities))
```

On collision, use a fully-qualified name to reach the original. A locally defined `get` shadows `phel.core/get` by its short name, but the full `phel.core/get` still works:

```phel
(ns hello-world.http-client)

(defn get [uri]
  {:status 200 :body "Hello World" :headers {}})

(phel.core/get (get "https://example.com") :status) ; Evaluates to 200
```

`:refer` brings specific symbols into the current namespace so you can call them unqualified:

```phel
(ns hello-world.main
  (:require hello-world.util :refer [greet]))

(greet util/my-name)
```

This works for standard-library modules too:

```phel
(ns my.app
  (:require phel.string :refer [join split]))

(join ", " ["a" "b" "c"]) ; => "a, b, c"
(split "a,b,c" #",")      ; => ["a" "b" "c"]
```

`:refer` and `:as` combine in any order.

### Import a PHP class

`:use` imports PHP classes:

```phel
(ns my.custom.module
  (:use Some.Php.ClassName))
```

Reference by name:

```phel
(ClassName.)          ; preferred shorthand
(php/new ClassName)   ; also valid
```

Aliases avoid collisions:

```phel
(ns my.custom.module
  (:use Some.Php.ClassName :as BetterClassName))
```

Importing is preferred, but optional. Use full namespace inline if needed:

```phel
(php/new Some.Php.ClassName)   ; or: (Some.Php.ClassName.)
```

## Require PHP files

Load external PHP files via `:require-file` (calls `require_once`). Example for Composer autoload:

```phel
(ns hello-world.main
  (:require-file "vendor/autoload.php"))
```

`(php/require_once "vendor/autoload.php")` works elsewhere, but for autoload it runs too late since Phel's core needs the autoloader. Use `:require-file`.

## Namespaced keywords

Plain keywords collide when sharing data. Namespaced keywords solve this.

Fully qualified: namespace, `/`, keyword name.

```phel
:my.namespace/foo ; absolute namespaced keyword
```

`::` shortcut binds current namespace:

```phel
(ns bar)
::foo ; => :bar/foo
```

`ns` aliases also work:

```phel
(ns foobar
  (:require abc.xyz :as bar))

::bar/foo ; evaluates to :abc.xyz/foo
```

## Best practices

- **One namespace per file.** The last part of the namespace must match the filename, so a file maps to exactly one `ns`.
- **Dashes map to PHP.** Use `kebab-case` namespace names (`my.user-service`); Phel translates dashes to a valid PHP namespace when compiling.
- **Prefer `:as` over heavy `:refer`.** A short alias (`(:require phel.string :as str)`) keeps call sites clear about where a function comes from. Reserve `:refer` for a few frequently used names. Over-referring hides origins and invites collisions.

## Next steps

- [Interfaces](/documentation/language/interfaces/) - share behavior across types within a namespace
- [Configuration](/documentation/configuration/) - set the source paths namespaces resolve from
