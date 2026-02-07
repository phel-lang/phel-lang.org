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

{% php_note() %}
The `php/` prefix gives you direct access to PHP's global scope:

```php
// PHP
$_SERVER['key']
$GLOBALS['argv']
MY_SETTING

// Phel
(get php/$_SERVER "key")
(get php/$GLOBALS "argv")
php/MY_SETTING
```

**Note:** Use Phel's immutable data structures when possible. Only use PHP arrays when you need to interop with PHP libraries that expect them.
{% end %}

## Calling PHP functions

PHP comes with huge set of functions that can be called from Phel by just adding a `php/` prefix to the function name.

```phel
(php/strlen "test") # Calls PHP's strlen function and evaluates to 4
(php/date "l") # Evaluates to something like "Monday"
```

{% php_note() %}
Any PHP function can be called by adding the `php/` prefix:

```php
// PHP
strlen("test");
date("l");
array_map($fn, $array);

// Phel
(php/strlen "test")
(php/date "l")
(php/array_map fn array)
```

However, Phel provides functional equivalents for many operations. For example, use `(count "test")` instead of `(php/strlen "test")` when working with Phel data structures.
{% end %}

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

{% php_note() %}
Class instantiation in Phel uses `php/new` instead of PHP's `new` keyword:

```php
// PHP
new DateTime();
new DateTime("now");
new \DateTimeImmutable();

// Phel
(php/new DateTime)
(php/new DateTime "now")
(php/new "\\DateTimeImmutable")
```

You can import classes with `:use` to avoid repeating the namespace, just like PHP's `use` statement.
{% end %}

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

{% php_note() %}
The `php/->` operator is similar to PHP's `->` but allows chaining in a more functional style:

```php
// PHP
$di->format("%s seconds");
$di->s;
(new DateTimeImmutable("2024-03-10"))->modify("+1 day")->format("Y-m-d");
$user->profile->getDisplayName();

// Phel
(php/-> di (format "%s seconds"))
(php/-> di s)
(php/-> (php/new \DateTimeImmutable "2024-03-10")
        (modify "+1 day")
        (format "Y-m-d"))
(php/-> user profile (getDisplayName))
```

Method calls use parentheses `(methodname args)`, while property access is just the symbol name.
{% end %}

{% clojure_note() %}
The `php/->` operator is inspired by Clojure's thread-first macro `->`, but specifically designed for PHP object method chaining.
{% end %}

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

{% php_note() %}
The `php/::` operator is equivalent to PHP's `::` for static method and property access:

```php
// PHP
DateTimeImmutable::ATOM;
DateTimeImmutable::createFromFormat("Y-m-d", "2020-03-22");

// Phel
(php/:: DateTimeImmutable ATOM)
(php/:: DateTimeImmutable (createFromFormat "Y-m-d" "2020-03-22"))
```
{% end %}

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

{% php_note() %}
`php/oset` is the Phel equivalent of PHP's property assignment:

```php
// PHP
$x = new stdClass();
$x->name = "foo";

// Phel
(def x (php/new \stdclass))
(php/oset (php/-> x name) "foo")
```

**Note:** This mutates the PHP object. When possible, use Phel's immutable data structures instead.
{% end %}

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

{% php_note() %}
`php/aget` safely accesses PHP array elements:

```php
// PHP
$arr[0] ?? null;
$arr[1] ?? null;
$arr[5] ?? null;  // Returns null

// Phel
(php/aget arr 0)
(php/aget arr 1)
(php/aget arr 5)  # Returns nil
```

**Important distinction:**
- Use `php/aget` for **PHP arrays** (mutable)
- Use `get` for **Phel data structures** (immutable vectors, maps)
{% end %}

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

{% php_note() %}
`php/aget-in` provides safe nested array access:

```php
// PHP - manual nested access with null coalescing
$users['users'][1]['name'] ?? null;
$data['meta']['status'] ?? null;
$data['meta']['missing'] ?? null;

// Phel - clean path-based access
(php/aget-in users ["users" 1 "name"])
(php/aget-in data ["meta" "status"])
(php/aget-in data ["meta" "missing"])  # Returns nil safely
```

This is similar to Phel's `get-in` for immutable data structures, but specifically for PHP arrays.
{% end %}

## Set PHP-Array value

```phel
(php/aset arr index value)
```

Equivalent to PHP's `arr[index] = value`.

{% php_note() %}
`php/aset` mutates a PHP array in place:

```php
// PHP
$arr[0] = "value";

// Phel
(php/aset arr 0 "value")
```

**Important:** This mutates the array. For immutable operations, use Phel's `assoc` on Phel data structures instead.
{% end %}

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

{% php_note() %}
`php/aset-in` creates nested structures automatically:

```php
// PHP - manual nested array creation
$data = [];
$data['user']['profile']['name'] = 'Charlie';

// Phel - automatic path creation
(def data (php/array))
(php/aset-in data ["user" "profile" "name"] "Charlie")
```

This is the mutable counterpart to Phel's `assoc-in` for immutable data structures.
{% end %}

## Append PHP-Array value

```phel
(php/apush arr value)
```

Equivalent to PHP's `arr[] = value`.

{% php_note() %}
`php/apush` appends to a PHP array:

```php
// PHP
$arr[] = "new value";

// Phel
(php/apush arr "new value")
```

For immutable operations, use `conj` on Phel vectors instead.
{% end %}

## Unset PHP-Array value

```phel
(php/aunset arr index)
```

Equivalent to PHP's `unset(arr[index])`.

{% php_note() %}
`php/aunset` removes an element from a PHP array:

```php
// PHP
unset($arr[0]);

// Phel
(php/aunset arr 0)
```

For immutable operations, use `dissoc` on Phel maps instead.
{% end %}

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

{% php_note() %}
`php/aunset-in` removes nested array elements:

```php
// PHP
unset($data['user']['profile']['name']);

// Phel
(php/aunset-in data ["user" "profile" "name"])
```

Parent arrays remain intact even if they become empty after the unset.
{% end %}

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

{% php_note() %}
**Important distinction:**

```php
// PHP magic constants
__DIR__   // Points to .phel/cache directory (generated PHP)
__FILE__  // Points to cached .php file

// Phel special var
*file*    // Points to your actual .phel source file
```

Use `*file*` when you need to reference the original Phel source location, such as for loading resources relative to your source code.
{% end %}

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
return (new \Phel\Config\PhelConfig())
    ->setExportConfig((new \Phel\Config\PhelExportConfig())
        ->setFromDirectories(['src'])
        ->setNamespacePrefix('PhelGenerated')
        ->setTargetDirectory('src/PhelGenerated'))
;
```

A detailed description of the options can be found in the [Configuration](/documentation/configuration/#exportconfig) chapter.

To mark a function as exported the following metadata needs to be added to the function:

```phel
(defn my-function
  {:export true}
  [a b]
  (+ a b))
```

Now the `phel export` command will generate a PHP wrapper class in the target directory (in this case `src/PhelGenerated`). This class can then be used in the PHP application to call Phel functions.
