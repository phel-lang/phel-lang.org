+++
title = "Commands"
weight = 21
+++

## build (Build the project)

```bash
vendor/bin/phel build
```

Build the current project into the `out-dir` folder. This means that the compiled phel code into PHP will be saved in that directory, so you can run the PHP code directly using the PHP interpreter. This will improve the runtime performance -because there won't be a need to compile the code again.

[Configuration](/documentation/configuration/) in `phel-config.php`:
```php
<?php
return [
    // [...]
    'out-dir' => 'out',
];
```

## export (Export definitions)

Export all definitions with the meta data `{:export true}` as PHP classes. 

It generates PHP classes at namespace level and a method for each exported definition. This allows you to use the exported phel functions from your PHP code.

```bash
vendor/bin/phel export
```

[Configuration](/documentation/configuration/) in `phel-config.php`:
```php
<?php
return [
    // [...]
    'export' => [
        'directories' => ['src'],
        'namespace-prefix' => 'PhelGenerated',
        'target-directory' => 'src/PhelGenerated'
    ]
];
```

## format (Format phel files)

Formats the given files. You can pass relative or absolute paths.

```bash
vendor/bin/phel format src tests
```

## repl (Read-eval-print loop)

Start a Repl. This is and interactive prompt (stands for Read-eval-print loop). It is very helpful to test out small tasks or to play around with the language itself.

```bash
vendor/bin/phel repl
```

Read more about the [REPL](/documentation/repl) in its own chapter.

## run (Run a script)

Code can be executed from the command line by calling the run command, followed by the file path or namespace:

```bash
vendor/bin/phel run src/main.phel
```

[Configuration](/documentation/configuration/) in `phel-config.php`:
```php
<?php
return [
    // [...]
    'src-dirs' => ['src'],
    'vendor-dir' => 'vendor',
]
```

Read more about [running the code](/documentation/getting-started/#running-the-code) in the getting started page.

## test (Test your phel logic)

Tests the given files. If no filenames are provided all tests in the "tests" directory are executed.

```bash
vendor/bin/phel test
```

Use the `filter` option to run only the tests that contain that filter. In this example, it will find and run all tests which contain `find-me` in their names.

```bash
vendor/bin/phel test --filter=find-me
```

[Configuration](/documentation/configuration/) in `phel-config.php`:
```php
<?php
return [
    // [...]
    'test-dirs' => ['tests'],
];
```
