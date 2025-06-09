+++
title = "Getting Started"
weight = 1
+++

## Requirements

- PHP 8.2+
- [Composer](https://getcomposer.org/)

## Quick Start

Scaffold a new project:

```bash
composer create-project --stability dev phel-lang/cli-skeleton example-app
cd example-app
composer repl
```

> For web projects: [web-skeleton](https://github.com/phel-lang/web-skeleton)  

## Manual Setup

```bash
mkdir hello-world && cd hello-world
composer init
composer require phel-lang/phel-lang
mkdir src
```

Optional config (`phel-config.php`):

```php
<?php
return (new \Phel\Config\PhelConfig())
  ->setSrcDirs(['src']);
```

Sample Phel file (`src/main.phel`):

```phel
(ns hello-world\main)
(println "Hello, World!")
```

## Run Code

**From CLI:**

```bash
vendor/bin/phel run src/main.phel
```

**With PHP Server:**

```php
<?php
require __DIR__ . '/../vendor/autoload.php';
\Phel\Phel::run(__DIR__ . '/../', 'hello-world\\main');
```

```bash
php -S localhost:8000 ./src/index.php
```

> 📘 [More on running code](/documentation/cli-commands#run-a-script)

## REPL

```bash
vendor/bin/phel repl
```

Try:

```phel
(def name "World")
(println "Hello" name)
```

> 📘 [More on REPL](/documentation/repl)

## Debugging

```phel
(php/dump (+ 40 2))
```

Enable temp files in `phel-config-local.php`:

```php
<?php
return (require __DIR__ . '/phel-config.php')
  ->setKeepGeneratedTempFiles(true);
```

> 📘 [More on debugging](/documentation/debug)

## Build & Deploy

```bash
vendor/bin/phel build
php out/index.php
```

> 📘 [More on build](/documentation/cli-commands/#build-the-project)

## Testing

```bash
vendor/bin/phel test --filter foo
```

> 📘 [More on testing](/documentation/testing)

## Handy Macros

```phel
(when condition (println "if true"))
(-> {:name "Phel"} (:name) (str "Lang"))
```

> 📘 [More on macros](/documentation/macros)

## Editor Support

- [VSCode](https://github.com/phel-lang/phel-vs-code-extension)
- [PhpStorm](https://github.com/phel-lang/phel-phpstorm-syntax)
- [Emacs](https://codeberg.org/mmontone/interactive-lang-tools)
- [Vim](https://github.com/danirod/phel.vim)
