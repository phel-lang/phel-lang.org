+++
title = "CLI Commands"
weight = 21
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
#       --flat            Use flat layout (src/ and tests/ without subdirectory)
#       --force           Overwrite existing files
#       --dry-run         Show what would be created without writing anything
#       --no-gitignore    Skip generating .gitignore
```

By default, `phel init` creates a conventional layout with `src/phel/` and `tests/phel/` subdirectories. Use `--flat` for a simpler `src/` and `tests/` layout.

```bash
# Create a project called "my-app" with flat layout
vendor/bin/phel init my-app --flat

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

## Read-Eval-Print Loop

Start a Repl. This is and interactive prompt (stands for Read-eval-print loop). It is very helpful to test out small tasks or to play around with the language itself.

```bash
vendor/bin/phel repl
```

Read more about the [REPL](/documentation/repl) in its own chapter.

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
#   paths                  The file paths that you want to test.
# 
# Options:
#   -f, --filter[=FILTER]  Filter by test names.
#       --testdox          Report test execution progress in TestDox format.

```

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

## Clear caches

Clear the namespace and compiled code caches:

```bash
vendor/bin/phel cache:clear
```

This removes all cached data from the cache directory. Useful when the cache becomes stale or after upgrading Phel versions.
