+++
title = "Configuration"
weight = 60
+++

Phel reads `phel-config.php` from the project root. Most projects only need the factory:

```php
<?php
// phel-config.php
return \Phel\Config\PhelConfig::forProject(\Phel\Config\ProjectLayout::Flat, 'your-ns.main');
```

Sets `src/`, `tests/`, and the build entry namespace. Chain `withX()` methods to override.

## Common tweaks

```php
<?php
use Phel\Config\PhelConfig;
use Phel\Config\ProjectLayout;

return PhelConfig::forProject(ProjectLayout::Flat, 'your-ns.main')
    ->withSrcDirs(['src'])                      // Phel source roots
    ->withTestDirs(['tests'])                   // test roots, picked up by `phel test`
    ->withFormatDirs(['src', 'tests'])          // dirs `phel format` rewrites
    ->withMainPhelNamespace('your-ns.index')    // entry ns for `phel build`
    ->withMainPhpPath('out/index.php')          // generated PHP entry
;
```

Covers running, testing, formatting, building. Defaults handle the rest.

## Full reference

<details>
<summary><strong>All available options</strong></summary>

```php
<?php
// phel-config.php, every with*() option, default values shown
return (new \Phel\Config\PhelConfig())
    ->withSrcDirs(['src'])
    ->withTestDirs(['tests'])
    ->withVendorDir('vendor')
    ->withErrorLogFile('.phel/error.log')
    ->withIgnoreWhenBuilding(['ignore-when-building.phel'])
    ->withNoCacheWhenBuilding([])
    ->withFormatDirs(['src', 'tests'])
    ->withKeepGeneratedTempFiles(false)
    ->withTempDir(sys_get_temp_dir().'/phel')
    ->withCacheDir('.phel/cache')
    ->withPhelDir('.phel')
    ->withEnableNamespaceCache(true)
    ->withEnableCompiledCodeCache(true)
    ->withMainPhelNamespace('your-ns.index')
    ->withMainPhpPath('out/index.php')
    ->withBuildDestDir('out')
    ->withExportFromDirectories(['src'])
    ->withExportNamespacePrefix('PhelGenerated')
    ->withExportTargetDirectory('src/PhelGenerated')
;
```

| Method                                 | Purpose                                                                                                |
|----------------------------------------|--------------------------------------------------------------------------------------------------------|
| `withLayout`                           | Apply `ProjectLayout::Flat`, `Nested`, or `Root`. Sets src/test/format/export dirs.                    |
| `withSrcDirs`                          | Source directories scanned by the compiler.                                                            |
| `withTestDirs`                         | Directories `phel test` walks.                                                                         |
| `withVendorDir`                        | Composer vendor directory name.                                                                        |
| `withErrorLogFile`                     | Path of the `error.log` file (under `.phel/` by default).                                              |
| `withIgnoreWhenBuilding`               | Phel files skipped by `phel build`.                                                                    |
| `withNoCacheWhenBuilding`              | Files always retranspiled, regardless of `--cache` / `--no-cache`.                                     |
| `withFormatDirs`                       | Directories rewritten by `phel format`.                                                                |
| `withKeepGeneratedTempFiles`           | Keep generated temp files after `phel run`. Default `false`.                                           |
| `withTempDir`                          | Absolute path for temporary files. Throws if not writable.                                             |
| `withCacheDir`                         | Directory for namespace + compiled-code caches. Default `.phel/cache`.                                 |
| `withPhelDir`                          | Root for runtime state (cache, REPL history, error log). Default `.phel`. Override via `PHEL_DIR` env. |
| `withEnableNamespaceCache`             | Persistent namespace cache for warm runs. Default `true`.                                              |
| `withEnableCompiledCodeCache`          | Compiled-code cache for tests/builds. Default `true`.                                                  |
| `withEnableAsserts`                    | Toggle runtime `assert` checks.                                                                        |
| `withWarnDeprecations`                 | Emit warnings on deprecated APIs.                                                                      |
| `withMainPhelNamespace`                | Entry ns for `phel build`.                                                                             |
| `withMainPhpPath`                      | Generated PHP entry path.                                                                              |
| `withBuildDestDir`                     | Output directory for `phel build`.                                                                     |
| `withExportFromDirectories`            | Source dirs scanned by `phel export`.                                                                  |
| `withExportNamespacePrefix`            | PHP namespace prefix for exported wrappers.                                                            |
| `withExportTargetDirectory`            | Output dir for `phel export`. See [PHP Interop](/documentation/php-interop/#calling-phel-from-php).    |
| `withBuildConfig` / `withExportConfig` | Replace nested config objects wholesale (rarely needed).                                               |

</details>

> **Note:** Old `setX()` setters are deprecated since 0.37 and emit notices. Use the `withX()` chain - the API is immutable.
