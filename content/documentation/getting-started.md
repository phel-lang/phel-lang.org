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

- Set up Phel another way? See [Installation](/documentation/installation).
- Dive into the CLI workflow: [CLI Commands](/documentation/cli-commands).
- Explore the REPL deeper: [REPL guide](/documentation/repl).
- Configure your editor: [Editor Support](/documentation/editor-support).
- Learn the core language features: [Basic Types](/documentation/basic-types).
