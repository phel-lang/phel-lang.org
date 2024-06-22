+++
title = "Getting started"
weight = 1
+++

## Requirements

Phel requires PHP 8.2 or higher and [Composer](https://getcomposer.org/).

## Quick start with a scaffolding template

To get started right away, you can create a new Phel commandline project via Composer's `create-project` command:

```bash
composer create-project --stability dev phel-lang/cli-skeleton example-app
```

Once the project has been created, start the REPL (read-evaluate-print loop) to try Phel.

```bash
cd example-app
composer repl
```

> Alternatively to the [phel-lang/cli-skeleton](https://github.com/phel-lang/cli-skeleton), you can also use [phel-lang/web-skeleton](https://github.com/phel-lang/web-skeleton) for a web project. More information can be found in the [README](https://packagist.org/packages/phel-lang/cli-skeleton) of the project.


## Manually initialize a new project using Composer

The easiest way to get started is by setting up a new Composer project. First, create a new directory and initialize a new Composer project.

```bash
mkdir hello-world
cd hello-world
composer init
```

Next, require Phel as a dependency.

```bash
composer require phel-lang/phel-lang
```

> Optionally, you can create `phel-config.php` at the root of the project:
> ```php
> <?php
> 
> return (new \Phel\Config\PhelConfig())
>     ->setSrcDirs(['src']);
> ```
> Read the docs to see all available [configuration](/documentation/configuration) options for Phel.

Then, create a new directory `src` with a file `main.phel` inside this directory.

```bash
mkdir src
```

The file `main.phel` contains the actual code of the project. It defines the namespace and prints "Hello, World!".

```phel
# inside `src/main.phel`
(ns hello-world\main)

(println "Hello, World!")
```

## Running the code

There are two ways to run the code: from the command line and with a PHP Server.

### From the Command line

Code can be executed from the command line by calling the `vendor/bin/phel run` command, followed by the file path or namespace:

```bash
vendor/bin/phel run src/main.phel
# or
vendor/bin/phel run hello-world\\main
# or
vendor/bin/phel run "hello-world\main"
```

The output will be:

```
Hello, World!
```

### With a PHP Server

> Check the [web-skeleton project on GitHub](https://github.com/phel-lang/web-skeleton).

The file `index.php` will be executed by the PHP Server. It initializes the Phel Runtime and loads the namespace from the `main.phel` file described above, to start the application.

```php
// src/index.php
<?php

use Phel\Phel;

$projectRootDir = __DIR__ . '/../';

require $projectRootDir . 'vendor/autoload.php';

Phel::run($projectRootDir, 'hello-world\\main');
```

The PHP Server can now be started.

```bash
# Start server
php -S localhost:8000 ./src/index.php
```

In the browser, the URL `http://localhost:8000` will now print "Hello, World!".

> When using a web server, consider building the project to avoid compilation time for each request; so PHP will run the transpiled PHP code instead to gain performance. See more [Buid the project](/documentation/cli-commands/#build-the-project).

## Launch the REPL

To try Phel you can run a REPL by executing the `./vendor/bin/phel repl` command.

> Read more about the [REPL](/documentation/repl) in its own chapter.

## Editor support

Phel comes with basic support for <a href="https://github.com/phel-lang/phel-vs-code-extension" target="_blank">
VSCode</a>, <a href="https://github.com/phel-lang/phel-phpstorm-syntax" target="_blank">PhpStorm</a>, a
<a href="https://codeberg.org/mmontone/interactive-lang-tools/src/branch/master/backends/phel" target="_blank">
Emacs mode with interactive capabilities</a> and a <a href="https://github.com/danirod/phel.vim" target="_blank">Vim
plugin</a> in the making.
