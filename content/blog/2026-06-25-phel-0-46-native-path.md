+++
title = "Phel 0.46: Native Path"
aliases = [ "/blog/phel-0-46-native-path" ]
description = "Config validation in phel doctor, per-phase build timing, honest build exit codes, a cascading incremental cache, and a steadier LSP. What changed in 0.46 and how to upgrade."
date = 2026-06-25
+++

Phel 0.46 (*Native Path*) sharpens the tooling around your project: configuration is now validated before it can bite you, the build reports where its time goes and fails honestly when it can't finish, and the editor experience holds up under real use. Here is what changed and how to move over.

## Upgrade

```bash
composer require phel-lang/phel-lang:^0.46
./vendor/bin/phel cache:clear        # or: rm -rf .phel/cache
```

Always clear the cache after upgrading: compiled PHP from an earlier install can reference renamed internals and fail to load otherwise.

## Config you can trust

A typo in `phel-config.php` used to surface as an uncaught exception and a stack trace. Now a broken config fails with a clear message that names the file and the structure it expected, and exits with status `1`:

```bash
phel doctor
```

`phel config` and `phel doctor` also validate the config itself (relative paths, the source and test directories, optimization levels, and value types), so problems show up before a build does.

This pairs with the one breaking change in 0.46: the deprecated `setX()` setters and `useLayout()` / `useNestedLayout()` / `useFlatLayout()` on `PhelConfig` are gone, along with the `setX()` shims on `PhelBuildConfig` and `PhelExportConfig`. Use the `with*()` methods instead:

```php
<?php

use Phel\Config\PhelConfig;
use Phel\Config\PhelBuildConfig;

return (new PhelConfig())
    ->setSrcDirs(['src'])          // old, removed
    ->withSrcDirs(['src'])         // new
    ->withBuildConfig(
        (new PhelBuildConfig())->withMainPhelNamespace('app\\main'),
    );
```

## A build that tells the truth

Two changes make `phel build` honest about what it did:

- It now exits **non-zero when compilation aborts**, instead of printing errors while returning `0`. If your CI trusted the old exit code, it may start failing, as it should have all along.
- `phel build --timing` reports per-phase compile durations across namespaces, so you can see where a slow build actually spends its time.

The incremental cache got smarter too: when a required namespace changes, recompiles now **cascade to dependent namespaces** instead of reusing stale output. You get correct builds without reaching for `cache:clear` as often.

```bash
phel build --timing
```

## A steadier editor

The language server now stays alive during idle periods and distinguishes a read timeout from end-of-stream, so it no longer drops out mid-session. `textDocument/documentSymbol` lists definitions from **unsaved buffer edits**, so the outline keeps up with what you are typing rather than what you last saved.

## Same language, sharper tools

None of this touches the language: your namespaces, macros, and structs compile exactly as before:

```phel
(defn greet [name]
  (str "Hello, " name "!"))

(greet "Phel")
# => "Hello, Phel!"
```

For the full list, see the [0.46 release notes](/releases/0-46-native-path/). Upgrade, run `phel doctor`, and let the tooling catch the problems you used to find the hard way.
