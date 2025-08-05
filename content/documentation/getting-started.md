+++
title = "Getting Started"
weight = 1
+++

## Requirements

- PHP 8.3+
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

## Testing

```bash
vendor/bin/phel test --filter foo
```

> 📘 [More on testing](/documentation/testing)

## Editor Support

- [VSCode](https://github.com/phel-lang/phel-vs-code-extension)
- [PhpStorm](https://github.com/phel-lang/phel-phpstorm-syntax)
- [Emacs](https://codeberg.org/mmontone/interactive-lang-tools)
- [Vim](https://github.com/danirod/phel.vim)
