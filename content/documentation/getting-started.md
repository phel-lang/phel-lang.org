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

### Use the standalone PHAR

Prefer to try Phel without installing Composer dependencies? Download the
pre-built [`phel.phar`](https://github.com/phel-lang/phel-lang/releases) from the
latest GitHub release:

```bash
curl -L https://github.com/phel-lang/phel-lang/releases/latest/download/phel.phar -o phel.phar
php phel.phar --version
```

You can execute the same commands as the Composer-installed binary. For example:

```bash
php phel.phar repl
php phel.phar run src/main.phel
php phel.phar test --filter foo
```

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

> <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px; display: inline-block; vertical-align: middle; margin-right: 4px;"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg> [More on running code](/documentation/cli-commands#run-a-script)

## REPL

```bash
vendor/bin/phel repl
```

Try:

```phel
(def name "World")
(println "Hello" name)
```

> <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px; display: inline-block; vertical-align: middle; margin-right: 4px;"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg> [More on REPL](/documentation/repl)

## Testing

```bash
vendor/bin/phel test --filter foo
```

> <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px; display: inline-block; vertical-align: middle; margin-right: 4px;"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg> [More on testing](/documentation/testing)

## Editor Support

- [PhpStorm](https://github.com/phel-lang/phel-intellij-plugin)
- [VSCode](https://github.com/phel-lang/phel-vs-code-extension)
- [Emacs](https://codeberg.org/mmontone/interactive-lang-tools)
- [Vim](https://github.com/danirod/phel.vim)
