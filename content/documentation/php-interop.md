+++
title = "PHP Interop"
weight = 14
+++

## Accessing global variables and named constants

Use the `php/` prefix to access the global variables (superglobals) in combination with `get`.

```phel
(get php/$_SERVER "key") # Similar to $_SERVER['key']
(get php/$GLOBALS "argv") # Similar to $GLOBALS['argv']
```

Named constants set with PHP [`define`](https://www.php.net/manual/en/function.define.php) can be accessed in Phel via `php/CONSTANT_NAME`.

```phel
(php/define "MY_SETTING" "My value") # Calls PHP define('MY_SETTING', 'My value");
php/MY_SETTING # Returns "My value"
```

## Calling PHP functions

PHP comes with huge set of functions that can be called from Phel by just adding a `php/` prefix to the function name.

```phel
(php/strlen "test") # Calls PHP's strlen function and evaluates to 4
(php/date "l") # Evaluates to something like "Monday"
```

## PHP class instantiation

```phel
(php/new expr args*)
```

Evaluates `expr` and creates a new PHP class using the arguments. The instance of the class is returned.

```phel
(ns my\module
  (:use \DateTime))

(php/new DateTime) # Returns a new instance of the DateTime class
(php/new DateTime "now") # Returns a new instance of the DateTime class

(php/new "\\DateTimeImmutable") # instantiate a new PHP class from string
```

## PHP method and property call

```phel
(php/-> object (methodname expr*))
(php/-> object property)
```

Calls a method or property on a PHP object. Both `methodname` and `property` must be symbols and cannot be an evaluated value.

You can chain multiple method calls or property accesses in one `php/->` expression. Each element is evaluated sequentially on the result of the previous call, allowing fluent-style interactions or access to nested properties.

```phel
(ns my\module)

(def di (php/new \DateInterval "PT30S"))

(php/-> di (format "%s seconds")) # Evaluates to "30 seconds"
(php/-> di s) # Evaluates to 30

# Chain multiple calls: 
# (new DateTimeImmutable("2024-03-10"))->modify("+1 day")->format("Y-m-d")
(php/-> (php/new \DateTimeImmutable "2024-03-10")
        (modify "+1 day")
        (format "Y-m-d"))

# Mix methods and properties: $user->profile->getDisplayName()
(php/-> user profile (getDisplayName))

# Other example using nested properties:
(def address (php/new \stdClass))
(def user (php/new \stdClass))
(php/oset (php/-> address city) "Berlin")
(php/oset (php/-> user address) address)
(php/-> user address city) # Evaluates to "Berlin"
```

## PHP static method and property call

```phel
(php/:: class (methodname expr*))
(php/:: class property)
```

Same as above, but for static calls on PHP classes.

```phel
(ns my\module
  (:use \DateTimeImmutable))

(php/:: DateTimeImmutable ATOM) # Evaluates to "Y-m-d\TH:i:sP"

# Evaluates to a new instance of DateTimeImmutable
(php/:: DateTimeImmutable (createFromFormat "Y-m-d" "2020-03-22"))

```

## PHP set object properties

```phel
(php/oset (php/-> object property) value)
(php/oset (php/:: class property) value)
```

Use `php/oset` to set a value to a class/object property.

```phel
(def x (php/new \stdclass))
(php/oset (php/-> x name) "foo")
```

## Get PHP-Array value

```phel
(php/aget arr index)
```

Equivalent to PHP's `arr[index] ?? null`.

```phel
(php/aget ["a" "b" "c"] 0) # Evaluates to "a"
(php/aget (php/array "a" "b" "c") 1) # Evaluates to "b"
(php/aget (php/array "a" "b" "c") 5) # Evaluates to nil
```

## Get nested PHP-Array value

```phel
(php/aget-in arr path)
```

Resolves nested values in a PHP array using a sequence of keys or indexes. The
`path` must be a sequential collection such as a vector. If any step in the
path is missing, `nil` is returned.

```phel
(def users
  (php/array
    "users"
    (php/array
      (php/array "name" "Alice")
      (php/array "name" "Bob"))))

(php/aget-in users ["users" 1 "name"]) # Evaluates to "Bob"

(php/aget-in 
    (php/array "meta" (php/array "status" "ok")) 
    ["meta" "status"]) # Evaluates to "ok"

(php/aget-in 
    (php/array "meta" (php/array "status" "ok")) 
    ["meta" "missing"]) # Evaluates to nil
```

## Set PHP-Array value

```phel
(php/aset arr index value)
```

Equivalent to PHP's `arr[index] = value`.

## Set nested PHP-Array value

```phel
(php/aset-in arr path value)
```

Creates or updates nested entries inside a PHP array. Intermediate arrays are
created as needed to ensure the path exists before writing the value.

```phel
(def data (php/array))
(php/aset-in data ["user" "profile" "name"] "Charlie")
(php/aget-in data ["user" "profile" "name"]) # Evaluates to "Charlie"
# Equivalent to $data['user']['profile']['name'] = 'Charlie';
```

## Append PHP-Array value

```phel
(php/apush arr value)
```

Equivalent to PHP's `arr[] = value`.

## Unset PHP-Array value

```phel
(php/aunset arr index)
```

Equivalent to PHP's `unset(arr[index])`.

## Unset nested PHP-Array value

```phel
(php/aunset-in arr path)
```

Removes a nested entry in a PHP array. Once the value is removed, parent arrays
remain untouched even if they become empty.

```phel
(def data (php/array "user" (php/array "profile" (php/array "name" "Dora"))))
(php/aunset-in data ["user" "profile" "name"])
(php/aget-in data ["user" "profile" "name"]) # Evaluates to nil
# Equivalent to unset($data['user']['profile']['name']);
```

## `__DIR__`, `__FILE__`, and `*file*`

In Phel you can also use PHP Magic Methods `__DIR__` and `__FILE__`. When the
compiler runs, these constants are expanded by PHP and therefore point to the
generated PHP file that is executed (e.g. the temporary file under
`.phel/cache`).

Sometimes you need the path of the original Phel source file instead. For that
Phel exposes the special var `*file*`, which contains the absolute path of the
current Phel file. Combine it with `php/dirname` if you need the source
directory.

```phel
(println __DIR__)  # Directory name of the generated PHP file
(println __FILE__) # Filename of the generated PHP file

(println (php/dirname *file*)) # Directory of the original Phel file
(println *file*)               # Absolute path of the original file
```

## Calling Phel functions from PHP

Phel also provides a way to let you call Phel functions from PHP. This is useful for existing PHP application that wants to integrate Phel.
Therefore, you have to load the Phel namespace that you want to call at the beginning of your script. This can be done directly after the `autoload.php` file was loaded.

For example, see [using-exported-phel-function.php](https://github.com/phel-lang/cli-skeleton/blob/main/example/using-exported-phel-function.php)

```php
<?php

use Phel\Phel;
use PhelGenerated\CliSkeleton\Modules\AdderModule;

$projectRootDir = dirname(__DIR__);

require $projectRootDir . '/vendor/autoload.php';

Phel::run($projectRootDir, 'cli-skeleton\modules\adder-module');

$adder = new AdderModule();
$result = $adder->adder(1, 2, 3);

echo 'Result = ' . $result . PHP_EOL;
```

Phel provide two ways to call Phel functions, manually or by using the `export` command.

### Manually

The `PhelCallerTrait` can be used to call any Phel function from an existing PHP class.
Simply inject the trait in the class and call the `callPhel` function.

```php
<?php
use Phel\Interop\PhelCallerTrait;

class MyExistingClass {
  use PhelCallerTrait;

  public function myExistingMethod(...$arguments) {
    return $this->callPhel(
        'my\phel\namespace', 
        'phel-function-name', 
        ...$arguments
    );
  }
}
```

### Using the `export` command

Alternatively, the `phel export` command can be used. This command will generate a wrapper class for all Phel functions that are marked as *export*.

Before using the `export` command the required configuration options need to be added to `phel-config.php`:

```php
<?php
return (new PhelConfig())
    ->setExport((new PhelExportConfig())
        ->setDirectories(['src'])
        ->setNamespacePrefix('PhelGenerated')
        ->setTargetDirectory('src/PhelGenerated'))
;
```

A detailed description of the options can be found in the [Configuration](/documentation/configuration/#export) chapter.

To mark a function as exported the following metadata needs to be added to the function:

```phel
(defn my-function
  {:export true}
  [a b]
  (+ a b))
```

Now the `phel export` command will generate a PHP wrapper class in the target directory (in this case `src/PhelGenerated`). This class can then be used in the PHP application to call Phel functions.
