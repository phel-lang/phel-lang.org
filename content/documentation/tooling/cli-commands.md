+++
title = "CLI Commands"
weight = 1
aliases = ["/documentation/cli-commands"]
+++

Phel includes a series of commands out-of-the-box.

```bash
# To see an overview of all commands.
vendor/bin/phel list
```

## Initialize a new project

Scaffold a new Phel project with minimal configuration:

```bash
vendor/bin/phel init
# Usage:
#   init [options] [--] [<project-name>]
#
# Arguments:
#   project-name          The project/namespace name (default: "app")
#
# Options:
#       --nested          Use legacy nested layout (src/phel/<name>/, tests/phel/<name>/)
#       --force           Overwrite existing files
#       --dry-run         Show what would be created without writing anything
#       --no-gitignore    Skip generating .gitignore
```

`phel init` defaults to the **Flat** layout (`src/`, `tests/`). Pass `--nested` for the legacy `src/phel/<name>/` layout.

```bash
# Flat layout (default)
vendor/bin/phel init my-app

# Legacy nested layout
vendor/bin/phel init my-app --nested

# Preview what would be created
vendor/bin/phel init my-app --dry-run
```

## Build the project

```bash
php phel build
# Usage:
#   build [options]
#
# Options:
#       --cache|--no-cache            Enable cache
#       --source-map|--no-source-map  Enable source maps
```

Build the current project into the main php path. This means that the compiled phel code into PHP will be saved in that directory being the entry point the `out/index.php`, and you can run the PHP code directly using the PHP interpreter. This will improve the runtime performance, because there won't be a need to compile the code again.

[Configuration](/documentation/configuration/#buildconfig) in `phel-config.php`:
```php
<?php
return (new \Phel\Config\PhelConfig())
    ->setBuildConfig((new \Phel\Config\PhelBuildConfig())
        ->setMainPhelNamespace('your-ns\index')
        ->setMainPhpPath('out/index.php'));
```

## Export definitions

Export all definitions with the metadata `{:export true}` as PHP classes. 

It generates PHP classes at namespace level and a method for each exported definition. This allows you to use the exported phel functions from your PHP code.

```bash
vendor/bin/phel export
```

[Configuration](/documentation/configuration/#export-definitions) in `phel-config.php`:
```php
<?php
return (new \Phel\Config\PhelConfig())
    ->setExportConfig((new \Phel\Config\PhelExportConfig())
        ->setFromDirectories(['src'])
        ->setNamespacePrefix('PhelGenerated')
        ->setTargetDirectory('src/PhelGenerated'));
```

## Format phel files

Formats the given files. You can pass relative or absolute paths.

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

The formatter aligns key/value pairs in `cond`, `case`, `condp`, and bindings of `let`/`loop`/`binding`/`for`/`foreach`/`dofor`/`if-let`/`when-let`.

## Read-Eval-Print Loop

Start a Repl. This is and interactive prompt (stands for Read-eval-print loop). It is very helpful to test out small tasks or to play around with the language itself.

```bash
vendor/bin/phel repl
```

Read more about the [REPL](/documentation/tooling/repl) in its own chapter.

## Run a script

Code can be executed from the command line by calling the run command, followed by the file path or namespace:

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

[Configuration](/documentation/configuration/#srcdirs) in `phel-config.php`:
```php
<?php
return (new PhelConfig())
    ->setSrcDirs(['src']);
```

Read more about [running the code](/documentation/getting-started/#running-the-code) in the getting started page.

## Test your phel logic

Tests the given files. If no filenames are provided all tests in the "tests" directory are executed.

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

Test selectors (`--include`, `--exclude`, `--ns`, regex `--filter`) and pluggable reporters (`default`, `testdox`, `dot`, `tap`, `junit-xml`) are documented in [Testing](/documentation/testing/).

[Configuration](/documentation/configuration/#testdirs) in `phel-config.php`:
```php
<?php
return (new PhelConfig())
    ->setTestDirs(['tests']);
```

Use the `filter` option to run only the tests that contain that filter.

[Configuration](/documentation/configuration/) in `phel-config.php`:
```php
<?php
return (new PhelConfig())
    ->setTestDirs(['tests']);
```

## Evaluate an expression

Evaluate a Phel expression and print the result. Pass a literal expression, or `-` to read from stdin.

```bash
vendor/bin/phel eval '(+ 1 2 3)'
# => 6

echo '(map inc [1 2 3])' | vendor/bin/phel eval -
# => [2 3 4]
```

Pass `-` to read the expression from stdin.

## Lint

Static analysis with configurable rules (unresolved-symbol, arity-mismatch, unused-binding, unused-require, unused-import, shadowed-binding, redundant-do, duplicate-key, invalid-destructuring, discouraged-var).

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

Reload changed namespaces in dependency order. Backends: inotify, fswatch, polling.

```bash
vendor/bin/phel watch
# Usage:
#   watch [paths]... [-b backend] [--poll=500] [--debounce=100]
```

Programmatic usage from Phel:

```phel
(ns my-app
  (:require phel\watch :refer [watch!]))

(watch! ["src/"])
```


## nREPL

bencode-over-TCP nREPL server. Supports `eval`, `clone`, `close`, `describe`, `load-file`, `interrupt`, `completions`, `lookup`, `info`, `eldoc`.

```bash
vendor/bin/phel nrepl --port=7888 --host=127.0.0.1
```

Connect from any nREPL-aware editor.


## LSP

Language Server Protocol (v3.17) over stdio. Supports hover, definition, references, completion, document/workspace symbols, rename, formatting, and debounced diagnostics.

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

Write skill/recipe files for AI coding assistants: Claude Code, Cursor, Codex, Gemini, Copilot, Aider.

```bash
vendor/bin/phel agent-install           # pick platform interactively
vendor/bin/phel agent-install claude    # single platform
vendor/bin/phel agent-install --all     # all platforms
#   --with-docs        Include reference docs
#   --dry-run          Show what would be written
#   --force            Overwrite existing files
```


## Clear caches

Clear the namespace and compiled code caches:

```bash
vendor/bin/phel cache:clear
```

This removes all cached data from the cache directory. Useful when the cache becomes stale or after upgrading Phel versions.
