+++
title = "Configuration"
weight = 19
+++

Phel comes with some configuration options. They are stored in the `phel-config.php` file in the root directory of every project.

## Structure

These are all Phel specific configuration options available, along with the values that are set by default.

```php
<?php
// phel-config.php
return (new \Phel\Config\PhelConfig())
    ->setSrcDirs(['src/phel'])
    ->setTestDirs(['tests/phel'])
    ->setVendorDir('vendor')
    ->setOut((new PhelOutConfig())
        ->setMainPhelNamespace('your-ns\main')
        ->setMainPhpPath('out/main.php'))
    ->setExport((new \Phel\Config\PhelExportConfig())
        ->setDirectories(['src/phel'])
        ->setNamespacePrefix('PhelGenerated')
        ->setTargetDirectory('src/PhelGenerated'))
    ->setIgnoreWhenBuilding(['src/phel/local.phel'])
    ->setNoCacheWhenBuilding(['src/phel/local.phel'])
    ->setFormatDirs(['src', 'tests'])
    ->setKeepGeneratedTempFiles(false)
;
```

## Options in detail

This chapter contains all configuration options explained in detail.

### `setSrcDirs(list<string>)`

Set a list of directories in which the source files for the project are located.

### `setTestDirs(list<string>)`

Set a list of directories in which the test files are located.

### `setVendorDir(string)`

Set the name of the composer vendor directory. Default is `vendor`.

### `setOut(PhelOutConfig)`

When running the `phel build` command:

- `setMainPhelNamespace`: the main phel namespace to start transpiling the Phel code.
- `setMainPhpPath`: the entry point of the build PHP result.

### `setExport(PhelExportConfig)`

Sets configuration options that are being used for the Phel export command that is described in the [PHP Interop](/documentation/php-interop/#calling-phel-functions-from-php) chapter. Currently, the export command requires three options:

- `setDirectories`: Sets a list of directories in which the export command should search for export functions.
- `setNamespacePrefix`: Sets a namespace prefix for all generated PHP classes.
- `setTargetDirectory`: Sets the directory where the generated PHP classes are stored.

### `setIgnoreWhenBuilding(list<string>)`

Set a list of Phel files that should be ignored when building the code.

### `setNoCacheWhenBuilding(list<string>)`

Set a list of Phel files that should be not cached when building the code. This means, they will be transpiled all the time; regardless when you use the `--cache` or `--no-cache` flag.

### `setFormatDirs(list<string>)`

Set a list of directories whose files will be formatted when running the format command.

### `setKeepGeneratedTempFiles(bool)`

A flag that automatically removes all generated temporal files once the command `phel run` has been executed. Default is `false`.
