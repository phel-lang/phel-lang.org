+++
title = "Configuration"
weight = 60
+++

Phel reads `phel-config.php` from the project root. Most projects only need the factory:

```php
<?php
// phel-config.php
return \Phel\Config\PhelConfig::forProject('your-ns\main');
```

Sets `src/phel/`, `tests/phel/`, and the build entry namespace. Chain setters to override.

## Common tweaks

```php
<?php
return \Phel\Config\PhelConfig::forProject('your-ns\main')
    ->setSrcDirs(['src'])                      // Phel source roots
    ->setTestDirs(['tests'])                   // test roots, picked up by `phel test`
    ->setFormatDirs(['src', 'tests'])          // dirs `phel format` rewrites
    ->setBuildConfig((new \Phel\Config\PhelBuildConfig())
        ->setMainPhelNamespace('your-ns\index')   // entry ns for `phel build`
        ->setMainPhpPath('out/index.php'))        // generated PHP entry
;
```

Covers running, testing, formatting, building. Defaults handle the rest.

## Full reference

<details>
<summary><strong>All available options</strong></summary>

```php
<?php
// phel-config.php, every setter, default values shown
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
    ->setCacheDir(sys_get_temp_dir().'/phel/cache')
    ->setEnableNamespaceCache(true)
    ->setEnableCompiledCodeCache(true)
    ->setBuildConfig((new \Phel\Config\PhelBuildConfig())
        ->setMainPhelNamespace('your-ns\index')
        ->setMainPhpPath('out/index.php'))
    ->setExportConfig((new \Phel\Config\PhelExportConfig())
        ->setFromDirectories(['src'])
        ->setNamespacePrefix('PhelGenerated')
        ->setTargetDirectory('src/PhelGenerated'))
;
```

| Setter | Purpose |
|--------|---------|
| `setSrcDirs` | Source directories scanned by the compiler. |
| `setTestDirs` | Directories `phel test` walks. |
| `setVendorDir` | Composer vendor directory name. |
| `setErrorLogFile` | Path of the `error.log` file. |
| `setIgnoreWhenBuilding` | Phel files skipped by `phel build`. |
| `setNoCacheWhenBuilding` | Files always retranspiled, regardless of `--cache` / `--no-cache`. |
| `setFormatDirs` | Directories rewritten by `phel format`. |
| `setKeepGeneratedTempFiles` | Keep generated temp files after `phel run`. Default `false`. |
| `setTempDir` | Absolute path for temporary files. Throws if not writable. |
| `setCacheDir` | Directory for namespace + compiled-code caches. |
| `setEnableNamespaceCache` | Persistent namespace cache for warm runs. Default `true`. |
| `setEnableCompiledCodeCache` | Compiled-code cache for tests/builds. Default `true`. |
| `setBuildConfig` | `setMainPhelNamespace` (entry ns) + `setMainPhpPath` (generated PHP entry). |
| `setExportConfig` | `setFromDirectories`, `setNamespacePrefix`, `setTargetDirectory` for `phel export`. See [PHP Interop](/documentation/php-interop/#calling-phel-functions-from-php). |

</details>
