+++
title = "Getting Started"
weight = 0
+++

## Requirements

- PHP 8.3+
- [Composer](https://getcomposer.org/)

## Quick Start

Create a fresh project and drop into the REPL:

```bash
composer create-project --stability dev phel-lang/cli-skeleton example-app
cd example-app
composer repl
```

Alternatively, initialize a project from scratch using `phel init`:

```bash
mkdir my-app && cd my-app
composer require phel-lang/phel-lang
vendor/bin/phel init my-app
vendor/bin/phel repl
```

Inside the REPL try:

```phel
(def name "World")
(println "Hello" name)
```

Exit the REPL with `Ctrl+D` and run the default script:

```bash
vendor/bin/phel run src/main.phel
```

## Where to go next

- **Learn by doing**: Try the [Practice exercises](/practice/basic) — hands-on challenges from basics to real programs.
- Set up Phel another way? See [Installation](/documentation/installation).
- Dive into the CLI workflow: [CLI Commands](/documentation/cli-commands).
- Explore the REPL deeper: [REPL guide](/documentation/repl).
- Configure your editor: [Editor Support](/documentation/editor-support).
- Learn the core language features: [Basic Types](/documentation/basic-types).
- Coming from PHP? See [Phel for PHP Developers](/documentation/phel-for-php-developers).
- Coming from Clojure? See [Coming from Clojure](/documentation/coming-from-clojure).
- Quick reference: [Cheat Sheet](/documentation/cheat-sheet).
- Side-by-side comparisons: [Rosetta Stone: PHP → Phel](/documentation/rosetta-stone).
- Real-world examples: [Cookbook](/documentation/cookbook).
