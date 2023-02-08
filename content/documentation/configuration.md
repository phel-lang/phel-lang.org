+++
title = "Configuration"
weight = 19
+++

Phel comes with some configuration options. They are stored in the `phel-config.php` file in the root directory of every project.

## Structure

These are all Phel specific configuration options available.

```php
<?php
// phel-config.php
return [
    'src-dirs' => ['src'],
    'test-dirs' => ['tests'],
    'vendor-dir' => 'vendor',
    'out-dir' => 'out',
    'export' => [
        'directories' => ['src'],
        'namespace-prefix' => 'PhelGenerated',
        'target-directory' => 'src/PhelGenerated',
    ],
    'keep-generated-temp-files' => false,
    'ignore-when-building' => ['src/ignore.phel'],
];
```

## Options in detail

This chapter contains all configuration options explained in detail.


### `src-dirs`

A list of directories in which the source files for the project are located.

### `test-dirs`

A list of directories in which the test files are located.

### `vendor-dir`

The name of the composer vendor directory. Default is `vendor`.

### `out-dir`

The directory where all compiled Phel code will be generated when running `phel build` command.

### `export`

These configuration options are used for the Phel export command that is described in the [PHP Interop](/documentation/php-interop/#calling-phel-functions-from-php) chapter. Currently, the export command requires three options:

- `directories`: Defines a list of directories in which the export command should search for export functions.
- `namespace-prefix`: Defines a namespace prefix for all generated PHP classes.
- `target-directory`: Defines the directory where the generated PHP classes are stored.

### `keep-generated-temp-files`

A flag that removes automatically all generated temporal files once the command `phel run` has been executed. Default is `false`.

### `ignore-when-building`

A list of Phel files that should be ignored when building the code.
