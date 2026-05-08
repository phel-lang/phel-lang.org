+++
title = "CLI Commands"
weight = 1
aliases = ["/documentation/cli-commands"]
+++

Built-in commands.

```bash
# Overview of all commands
vendor/bin/phel list
```

## Initialize a new project

Scaffold a new Phel project:

```bash
vendor/bin/phel init
# Usage:
#   init [options] [--] [<project-name>]
#
# Arguments:
#   project-name          The project/namespace name (default: "app")
#
# Options:
#       --nested          Use nested layout (src/phel/, tests/phel/)
#   -m, --minimal         Use root layout (single main.phel at project root)
#       --force           Overwrite existing files
#       --dry-run         Show what would be created without writing anything
#       --no-gitignore    Skip generating .gitignore
#       --no-tests        Skip generating a test file
```

Defaults to **Flat** layout (`src/`, `tests/`). `--nested` for `src/phel/`. `--minimal` for a single root file.

```bash
# Flat layout (default)
vendor/bin/phel init my-app

# Nested layout
vendor/bin/phel init my-app --nested

# Preview what would be created
vendor/bin/phel init my-app --dry-run
```

## Build the project

```bash
vendor/bin/phel build
# Usage:
#   build [options]
#
# Options:
#       --cache|--no-cache            Enable cache
#       --source-map|--no-source-map  Enable source maps
```

Compiles Phel to PHP, writing to the configured main path (entry point `out/index.php`). Run the resulting PHP directly. Skips recompilation, improving runtime.

[Configuration](/documentation/configuration/) in `phel-config.php`:
```php
<?php
return (new \Phel\Config\PhelConfig())
    ->setBuildConfig((new \Phel\Config\PhelBuildConfig())
        ->setMainPhelNamespace('your-ns.index')
        ->setMainPhpPath('out/index.php'));
```

## Export definitions

Exports definitions with `{:export true}` metadata as PHP classes. Generates one class per namespace, one method per exported definition. Lets you call Phel functions from PHP.

```bash
vendor/bin/phel export
```

[Configuration](/documentation/configuration/) in `phel-config.php`:
```php
<?php
return (new \Phel\Config\PhelConfig())
    ->setExportConfig((new \Phel\Config\PhelExportConfig())
        ->setFromDirectories(['src'])
        ->setNamespacePrefix('PhelGenerated')
        ->setTargetDirectory('src/PhelGenerated'));
```

## Format phel files

Formats files. Accepts relative or absolute paths.

```bash
vendor/bin/phel format
# Usage:
#   format <paths>...
# 
# Arguments:
#   paths                 The file paths that you want to format.
```

[Configuration](/documentation/configuration/) in `phel-config.php`:
```php
<?php
return (new PhelConfig())
    ->setFormatDirs(['src', 'tests']);
```

Aligns key/value pairs in `cond`, `case`, `condp`, and bindings of `let`/`loop`/`binding`/`for`/`foreach`/`dofor`/`if-let`/`when-let`.

## Read-eval-print loop

Interactive prompt for quick tests and language exploration.

```bash
vendor/bin/phel repl
```

See [REPL](/documentation/tooling/repl).

## Run a script

Run a file or namespace:

```bash
vendor/bin/phel run
# Usage:
#   run [options] [--] <path> [<argv>...]
# 
# Arguments:
#   path                  The file path that you want to run.
#   argv                  Optional arguments
# 
# Options:
#   -t, --with-time       With time awareness
```

[Configuration](/documentation/configuration/) in `phel-config.php`:
```php
<?php
return (new PhelConfig())
    ->setSrcDirs(['src']);
```

See [Getting Started](/documentation/getting-started/).

## Test your Phel logic

Runs tests. No paths runs everything in `tests/`.

```bash
vendor/bin/phel test
# Usage:
#   test [options] [--] [<paths>...]
#
# Arguments:
#   paths                   The file paths that you want to test.
#
# Options:
#   -f, --filter[=REGEX]    Filter by test name regex. Repeatable.
#       --fail-fast         Stop on first failure or error.
#       --include=TAG       Only run tests tagged TAG. Repeatable.
#       --exclude=TAG       Skip tests tagged TAG. Repeatable.
#       --ns=GLOB           Only run namespaces matching GLOB. Repeatable.
#       --reporter=NAME     Reporter: default|testdox|dot|tap|junit-xml. Repeatable.
#       --output=PATH       Output path (for junit-xml).
#       --testdox           Shortcut for --reporter=testdox.
```

Test selectors and reporters: see [Testing](/documentation/testing/).

[Configuration](/documentation/configuration/) in `phel-config.php`:
```php
<?php
return (new PhelConfig())
    ->setTestDirs(['tests']);
```

Use `filter` to run matching tests only.

## Evaluate an expression

Evaluate and print. Pass a literal expression, or `-` for stdin.

```bash
vendor/bin/phel eval '(+ 1 2 3)'
# => 6

echo '(map inc [1 2 3])' | vendor/bin/phel eval -
# => [2 3 4]
```

## Lint

Static analysis. Rules: unresolved-symbol, arity-mismatch, unused-binding, unused-require, unused-import, shadowed-binding, redundant-do, duplicate-key, invalid-destructuring, discouraged-var.

```bash
vendor/bin/phel lint
# Usage:
#   lint [options] [--] [<paths>...]
#
# Options:
#       --format=FORMAT   human (default), json, github
#       --config=PATH     Path to phel-lint.phel
#       --no-cache        Disable linter cache
```

Configure rules in `phel-lint.phel` at the project root.

## Watch

Reloads changed namespaces in dependency order. Backends: inotify, fswatch, polling.

```bash
vendor/bin/phel watch
# Usage:
#   watch [options] [--] [<paths>...]
#
# Arguments:
#   paths                 Files or directories to watch (default: configured src dirs)
#
# Options:
#   -b, --backend=BACKEND Watcher backend: auto, inotify, fswatch, polling (default: auto)
#       --poll=MS         Polling interval in ms, polling backend only (default: 500)
#       --debounce=MS     Debounce window in ms (default: 100)
```

From Phel code, use `phel.watch`:

```phel
(ns my-app
  (:require phel.watch :refer [watch!]))

(watch! ["src/"])
```


## nREPL

Bencode-over-TCP nREPL server. Ops: `eval`, `clone`, `close`, `describe`, `load-file`, `interrupt`, `completions`, `lookup`, `info`, `eldoc`.

```bash
vendor/bin/phel nrepl --port=7888 --host=127.0.0.1
```

Connect from any nREPL-aware editor.

## LSP

LSP v3.17 over stdio. Supports hover, definition, references, completion, document/workspace symbols, rename, formatting, debounced diagnostics.

```bash
vendor/bin/phel lsp
```


## Analyze and index

`phel analyze <file>` emits JSON diagnostics; `phel index <dir>...` builds a symbol table for tooling.

```bash
vendor/bin/phel analyze src/main.phel
vendor/bin/phel index src --out=symbols.json
```

`phel api-daemon` serves the Api facade as JSON-RPC over stdio.

```bash
vendor/bin/phel api-daemon
```


## Agent install

Writes skill/recipe files for AI coding assistants: Claude Code, Cursor, Codex, Gemini, Copilot, Aider.

```bash
vendor/bin/phel agent-install           # pick platform interactively
vendor/bin/phel agent-install claude    # single platform
vendor/bin/phel agent-install --all     # all platforms
#   --with-docs        Include reference docs
#   --dry-run          Show what would be written
#   --force            Overwrite existing files
```


## Clear caches

Clear namespace and compiled-code caches:

```bash
vendor/bin/phel cache:clear
```

Removes everything in the cache dir. Useful for stale caches or after upgrades.
