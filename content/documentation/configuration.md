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
    ->setOutDir('out')
    ->setExport((new \Phel\Config\PhelExportConfig())
        ->setDirectories(['src/phel'])
        ->setNamespacePrefix('PhelGenerated')
        ->setTargetDirectory('src/PhelGenerated'))
    ->setIgnoreWhenBuilding(['src/phel/local.phel'])
    ->setKeepGeneratedTempFiles(false)
    ->setFormatDirs(['src', 'tests'])
;
```

## Options in detail

This chapter contains all configuration options explained in detail.


### `setSrcDirs`

Sets a list of directories in which the source files for the project are located.

### `setTestDirs`

Sets a list of directories in which the test files are located.

### `setVendorDir`

Sets the name of the composer vendor directory. Default is `vendor`.

### `setOutDir`

Sets the directory where all compiled Phel code will be generated when running `phel build` command.

### `setExport`

Sets configuration options that are being used for the Phel export command that is described in the [PHP Interop](/documentation/php-interop/#calling-phel-functions-from-php) chapter. Currently, the export command requires three options:

- `setDirectories`: Sets a list of directories in which the export command should search for export functions.
- `setNamespacePrefix`: Sets a namespace prefix for all generated PHP classes.
- `setTargetDirectory`: Sets the directory where the generated PHP classes are stored.

### `setKeepGeneratedTempFiles`

A flag that removes automatically all generated temporal files once the command `phel run` has been executed. Default is `false`.

### `setIgnoreWhenBuilding`

Sets a list of Phel files that should be ignored when building the code.

### `setFormatDirs`

Sets a list of directories whose files will be formatted when running the format command.