+++
title = "Namespaces"
weight = 12
+++

## Namespace (ns)

Every Phel file is required to have a namespace. A valid namespace name starts with a letter, followed by any number of letters, numbers, or dashes. Individual parts of the namespace are separated by the `\` character. The last part of the namespace has to match the name of the file.

```phel
(ns name imports*)
```

Defines the namespace for the current file and adds imports to the environment. Imports can either be _uses_ or _requires_. The keyword `:use` is used to import PHP classes, the keyword `:require` is used to import Phel modules and the keyword `:require-file` is used to load php files.

```phel
(ns my\custom\module
  (:require-file "vendor/autoload.php")
  (:require my\phel\module)
  (:use Some\Php\Class))
```

The call also sets the `*ns*` variable to the given namespace.

### Import a Phel module

Before a Phel module can be used, it has to be imported with the keyword `:require`. Once imported, the module can be accessed by its name followed by a slash and the name of the public function or value. Namespaces are indexed from source file directory which is `src/` by default and can changed with [SrcDirs configuration option](/documentation/configuration/#srcdirs) in `phel-config.php`.

Given, a module `util` is defined in the namespace `hello-world`.

```phel
(ns hello-world\util)

(def my-name "Phel")

(defn greet [name]
  (print (str "Hello, " name)))
```

Module `boot` imports module `util` and uses its functions and values.

```phel
(ns hello-world\boot
  (:require hello-world\util))

(util/greet util/my-name)
```

To prevent name collision from other modules in different namespaces, aliases can be used.

```phel
(ns hello-world\boot
  (:require hello-world\util :as utilities))
```

When names collide, names from different namespaces remain available by prefixing them with a namespace identifier (such as `phel\core`). However, care should be taken when referring to names before redefining them, as the names retain their values from the original namespaces before the redefinition.

```phel
(ns hello-world\http-client)

(defn get [uri]
  {:status 200 :body "Hello World" :headers {}})

(phel\core/get (get "https://example.com") :status) # Evaluates to 200
```

Additionally, it is possible to refer symbols of other modules in the current namespace by using `:refer` keyword.

```phel
(ns hello-world\boot
  (:require hello-world\util :refer [greet]))

(greet util/my-name)
```

Both, `:refer` and `:as` can be combined in any order.

### Import a PHP class

PHP classes are imported with the keyword `:use`.

```phel
(ns my\custom\module
  (:use Some\Php\ClassName)
```

Once imported, a class can be referenced by its name.

```phel
(php/new ClassName)
```

To prevent name collision from other classes in different namespaces, aliases can be used.

```phel
(ns my\custom\module
  (:use Some\Php\ClassName :as BetterClassName)
```

Importing PHP classes is considered a "better" coding style, but it is optional. Any PHP class can be used by typing its namespace with the class name.

```phel
(php/new \Some\Php\ClassName)
```

## Require PHP files

In some cases it is necessary to load external PHP file via PHP's `require_once` statement. This can be archived by using the `:require-file` keyword. For example, to load composer's autoload file the following code can be used:

```
(ns hello-world\boot
  (:require-file "vendor/autoload.php"))
```

As alternative, you can also call `(php/require_once "vendor/autload.php")` anywhere in your code. However, especially for the autoload file this statement is executed to late, because Phel's core library needs to load PHP files via the autoloader. Therefore, it is recommended to use the `:require-file` method.

## Namespaced keywords

If code or data is shared to the outside world simple keywords can lead to collisions. This problem can be solved by using namespaced keywords.

There are multiple options to define namespaced keywords. The most simple one is to define a fully qualified keyword with the full namespace followed by a `/` and the keyword name.

```phel
:my\namespace/foo # a absolute namespaced keyword
```

The `::` shortcut can be used to assign the current namespace to the keyword

```phel
(ns bar)
::foo # Evaluates to :bar/foo
```

Aliases defined in the `ns` expression can also be used

```phel
(ns foobar
  (:require abc\xyz :as bar))
  ::bar/foo # evaluates to :abc\xyz/bar
```
