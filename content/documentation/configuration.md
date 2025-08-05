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
    ->setSrcDirs(['src'])
    ->setTestDirs(['tests'])
    ->setVendorDir('vendor')
    ->setErrorLogFile('data/error.log')
    ->setIgnoreWhenBuilding(['ignore-when-building.phel'])
    ->setNoCacheWhenBuilding([])
    ->setFormatDirs(['src', 'tests'])
    ->setKeepGeneratedTempFiles(false)
    ->setTempDir(sys_get_temp_dir().'/phel')
    ->setBuildConfig((new \Phel\Config\PhelBuildConfig())
        ->setMainPhelNamespace('your-ns\index')
        ->setMainPhpPath('out/index.php'))
    ->setExportConfig((new \Phel\Config\PhelExportConfig())
        ->setFromDirectories(['src'])
        ->setNamespacePrefix('PhelGenerated')
        ->setTargetDirectory('src/PhelGenerated'))
;
```

## Options in detail

This chapter contains all configuration options explained in detail.

### SrcDirs

Set a list of directories in which the source files for the project are located.

```php
<?php
return (new \Phel\Config\PhelConfig())
    ->setSrcDirs(['src'])
    # ...
;
```

### TestDirs

Set a list of directories in which the test files are located.

```php
<?php
return (new \Phel\Config\PhelConfig())
    ->setTestDirs(['tests'])
    # ...
;
```

### VendorDir

Set the name of the composer vendor directory.

```php
<?php
return (new \Phel\Config\PhelConfig())
    ->setVendorDir('vendor')
    # ...
;
```

### ErrorLogFile

Set the path to the `error.log` file

```php
<?php
return (new \Phel\Config\PhelConfig())
    ->setErrorLogFile('data/error.log')
    # ...
;
```

### IgnoreWhenBuilding

Set a list of Phel files that should be ignored when building the code.


```php
<?php
return (new \Phel\Config\PhelConfig())
    ->setIgnoreWhenBuilding(['ignore-when-building.phel'])
    # ...
;
```

### NoCacheWhenBuilding

Set a list of Phel files that should be not cached when building the code. This means, they will be transpiled all the time; regardless when you use the `--cache` or `--no-cache` flag.

```php
<?php
return (new \Phel\Config\PhelConfig())
    ->setNoCacheWhenBuilding([])
    # ...
;
```

### FormatDirs

Set a list of directories whose files will be formatted when running the format command.


```php
<?php
return (new \Phel\Config\PhelConfig())
    ->setFormatDirs(['src', 'tests'])
    # ...
;
```

### KeepGeneratedTempFiles

A flag that automatically removes all generated temporal files once the command `phel run` has been executed. Default is `false`.

```php
<?php
return (new \Phel\Config\PhelConfig())
    ->setKeepGeneratedTempFiles(false)
    # ...
;
```

### TempDir

Allows setting a custom absolute path for temporary files.

```php
<?php
return (new \Phel\Config\PhelConfig())
    ->setTempDir('/tmp/phel')
    # ...
;
```

If the path does not exist and cannot be created or written to, an exception is thrown.

### BuildConfig

The configuration when running the `phel build` command.

```php
<?php
return (new \Phel\Config\PhelConfig())
    ->setBuildConfig((new \Phel\Config\PhelBuildConfig())
        ->setMainPhelNamespace('your-ns\index')
        ->setMainPhpPath('out/index.php'))
    # ...
;
```

- `setMainPhelNamespace`: the main phel namespace to start transpiling the Phel code.
- `setMainPhpPath`: the entry point of the build PHP result.

### ExportConfig

Set configuration options that are being used for the `phel export` command that is described in the [PHP Interop](/documentation/php-interop/#calling-phel-functions-from-php) chapter.

```php
<?php
return (new \Phel\Config\PhelConfig())
    ->setExportConfig((new \Phel\Config\PhelExportConfig())
        ->setFromDirectories(['src'])
        ->setNamespacePrefix('PhelGenerated')
        ->setTargetDirectory('src/PhelGenerated'))
    # ...
;
```

Currently, the export command requires three options:

- `setFromDirectories`: Sets a list of directories in which the export command should search for export functions.
- `setNamespacePrefix`: Sets a namespace prefix for all generated PHP classes.
- `setTargetDirectory`: Sets the directory where the generated PHP classes are stored.
