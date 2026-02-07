+++
title = "Authoring libraries"
weight = 22
+++

The reason to create a Phel library is to be able to use the same code across both PHP and Phel projects without manually copying the code around.

## Important files

There are two files to keep in mind while developing a Phel library:

- composer.json
- phel-config.php

To better provide you with an example, you can view the source code of the first Phel library [mabasic/phel-json](https://github.com/mabasic/phel-json). The library has been merged to core Phel in namespace [phel\json](/documentation/api/#json-decode), but you can still install it and see how it all works. Read more about `phel\json` in the blog post [Release: v0.8.0](/blog/release-0-8/).

### composer.json

The most important part in this file is the `require` section. In here, you need to declare which Phel version your library supports.

```json
"require": {
    "phel-lang/phel-lang": "^0.29"
}
```

The `^` constraint means your library supports Phel from the specified version up to the next major release. Adjust this to match the Phel versions you've tested against. See [Composer](https://getcomposer.org/doc/articles/versions.md) documentation for more info on version constraints.


### phel-config.php

Since the `mabasic/phel-json` library was written, there is a new way of writing the configuration file. The old way used an array (you can still use this today), but the newer way is much more elegant and preferred way of configuring your Phel project.

Here is an example config:

```php
<?php
declare(strict_types=1);

use Phel\Config\PhelConfig;
use Phel\Config\PhelOutConfig;

return (new PhelConfig())
    ->setSrcDirs(['src'])
    ->setTestDirs(['tests'])
    ->setOut((new PhelOutConfig())
        ->setMainPhelNamespace('your-ns\main')
        ->setMainPhpPath('out/main.php'))
    ->setFormatDirs(['src', 'tests'])
    ->setIgnoreWhenBuilding(['local.phel'])
    ->setKeepGeneratedTempFiles(false);
```

To find out more about what each configuration option means read the documentation for [Configuration](/documentation/configuration/).

## Topics of interest

### Namespaces

You can namespace your library however you want, but to keep to best practices your library should follow this convention: `{username}\{library-name}`.

Read the documentation on [Namespaces](/documentation/namespaces/). 

### Testing

Having tests for your library makes it more stable because you can easily see which Phel version makes your library not work.

Read the documentation on [Testing](/documentation/testing/). 

### PHP interop

This applies when you want to use your Phel library from PHP. Be sure to double check the configuration file.

Read the documentation on [PHP interop](/documentation/php-interop/#calling-phel-functions-from-php). 

### Private code

When writing a library you get to decide what function, variables or macros you want to expose to the library users. This is important in cases where you don't want the library users to use a specific function or value for some reason.

Available macros:

- [`def-`](/documentation/api/#def) - Define a private value that will not be exported.
- [`defn-`](/documentation/api/#defn-1) - Define a private function that will not be exported.
- [`defmacro-`](/documentation/api/#defmacro-1) - Define a private macro that will not be exported.

## Publishing

Phel library is just like any PHP library in the sense that the process for publishing is the same. You login to [Packagist](https://packagist.org/) and submit your repository. Then, you can install the library in your Phel or PHP application in the same way:

```bash
# For example:
composer require mabasic/phel-json
```

Happy Pheling!
