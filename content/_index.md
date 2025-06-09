+++
title = "Getting Started & Developer Experience"
weight = 1
+++

## Requirements

Phel requires PHP 8.2 or higher and [Composer](https://getcomposer.org/).

---

## Quick Start with a Scaffolding Template

You can create a new Phel commandline project via Composer’s `create-project` command:

```bash
composer create-project --stability dev phel-lang/cli-skeleton example-app
cd example-app
composer repl
```

> Alternatively, use [phel-lang/web-skeleton](https://github.com/phel-lang/web-skeleton) for a web project. More details in the [README](https://packagist.org/packages/phel-lang/cli-skeleton).

---

## Manual Setup with Composer

1. Initialize a new project:

```bash
mkdir hello-world
cd hello-world
composer init
```

2. Require Phel:

```bash
composer require phel-lang/phel-lang
```

> Optionally create `phel-config.php`:
> ```php
> <?php
> return (new \Phel\Config\PhelConfig())->setSrcDirs(['src']);
> ```
> See all [configuration options](/documentation/configuration).

3. Create the source directory and a file:

```bash
mkdir src
```

4. Write your first Phel program:

```phel
;; src/main.phel
(ns hello-world\main)
(println "Hello, World!")
```

---

## Running the Code

### From the Command Line

```bash
vendor/bin/phel run src/main.phel
# or
vendor/bin/phel run hello-world\\main
# or
vendor/bin/phel run "hello-world\main"
```

Output:

```
Hello, World!
```

### With a PHP Server

```php
// src/index.php
<?php

use Phel\Phel;

$projectRootDir = __DIR__ . '/../';

require $projectRootDir . 'vendor/autoload.php';

Phel::run($projectRootDir, 'hello-world\\main');
```

Start the server:

```bash
php -S localhost:8000 ./src/index.php
```

Visit [http://localhost:8000](http://localhost:8000) to see the output.

> Consider using `phel build` for performance. See [Build the project](/documentation/cli-commands/#build-the-project).

---

## Launch the REPL

Start an interactive REPL in any project with Phel installed:

```bash
./vendor/bin/phel repl
```

You can evaluate Phel expressions:

```phel
phel:1> (def name "World")
phel:2> (println "Hello" name)
Hello World
```

The REPL understands multi-line expressions and supports `doc`, `require` and `use` helpers.

> More in the [REPL documentation](/documentation/repl).

---

## Debugging Helpers

Use PHP debugging tools:

```phel
(def result (+ 40 2))
(php/dump result)
```

Enable temporary PHP files for inspection:

```php
// phel-config-local.php
return (require __DIR__ . '/phel-config.php')
    ->setKeepGeneratedTempFiles(true);
```

> Learn more on the [Debug page](/documentation/debug).

---

## Building and Deploying

Run directly:

```bash
vendor/bin/phel run src/main.phel
```

Build for production:

```bash
php phel build
php out/index.php
```

> More in the [CLI commands](/documentation/cli-commands/#run-a-script).

---

## Testing

Run Phel tests:

```bash
vendor/bin/phel test --filter foo
```

Run PHP-based tests:

```bash
composer test
```

> More in the [Testing section](/documentation/testing).

---

## Handy Macros

Example:

```phel
(when condition
  (println "only printed when condition is true"))

(-> {:name "Phel"}
    (:name)
    (str "Lang"))
```

These macros keep code concise and readable. Explore the rest of the library for more utilities.

> See the [Macros page](/documentation/macros) for more.

---

## Editor Support

Phel supports:

- [VSCode extension](https://github.com/phel-lang/phel-vs-code-extension)
- [PhpStorm syntax plugin](https://github.com/phel-lang/phel-phpstorm-syntax)
- [Emacs interactive mode](https://codeberg.org/mmontone/interactive-lang-tools/src/branch/master/backends/phel)
- [Vim plugin (in progress)](https://github.com/danirod/phel.vim)

> Details also in the [Editor Support](/documentation/getting-started/#editor-support) section.
