+++
title = "Performance"
weight = 12
description = "Speed up phel test and phel run with CLI opcache, the compiled-code cache, optimization levels, profiling, and type tags."
aliases = ["/documentation/performance/"]
+++

This page shows you how to make `phel test`, `phel run`, and the other CLI commands fast, and how to find and speed up slow functions. It applies to both source-checkout and PHAR installs.

## Opcache is on by default

Each `vendor/bin/phel` call is a fresh PHP process. Without CLI opcache, PHP parses every `.php` file again on every run: all of `vendor/`, the Phel compiler, the Symfony console, and your own classes. Keeping the compiled bytecode on disk between runs is the biggest win.

The `phel` binary does this for you. When the opcache extension is loaded and `pcntl` is available, it restarts itself with a file cache under `.phel/opcache/`. You do not need to change `php.ini`.

Check the status under `Checking performance`:

```bash
vendor/bin/phel doctor
```

Set `PHEL_NO_OPCACHE_REEXEC=1` to turn this off and keep your own opcache settings.

### Manual setup

Configure opcache yourself when `pcntl` is missing, or when you run PHP directly instead of the `phel` binary:

```ini
; /your/php/conf.d/ext-opcache.ini
opcache.enable_cli=1
opcache.file_cache=/tmp/php-opcache
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.interned_strings_buffer=16
```

Create the cache directory. Most systems empty `/tmp` on reboot, so recreate it after one:

```bash
mkdir -p /tmp/php-opcache
```

With a warm cache, repeat runs of `vendor/bin/phel test` drop from seconds to under one second.

### Find your php.ini

```bash
php --ini
```

Look for `Loaded Configuration File` and `Additional .ini files parsed`. On Homebrew (macOS) the opcache settings live in `/opt/homebrew/etc/php/<version>/conf.d/ext-opcache.ini`.

### Verify opcache is active

```bash
php -r 'var_dump(opcache_get_status(false) !== false);'
```

Prints `bool(true)` when CLI opcache is on.

## The compiled-code cache

Phel keeps its own cache under `.phel/cache/`. It stores the PHP compiled from each `.phel` file, keyed by a hash of the source. The two caches work together:

| Cache | Skips |
|---|---|
| Phel compiled-code cache (`.phel/cache/`) | Compiling unchanged `.phel` source to PHP |
| Opcache file cache (`.phel/opcache/`) | Parsing the generated PHP again |

Invalidation is automatic. Each run compares the `md5` of each `.phel` file with the stored entry. On a mismatch it recompiles that file and every file that depends on it, then passes the new PHP to `opcache_compile_file()`. Changing the optimization level forces a full recompile.

The cache flags (`withEnableCompiledCodeCache`, `withEnableNamespaceCache`, `withCacheDir`) and their defaults live in [Configuration](/documentation/reference/configuration/). You rarely need to touch them.

### Reset the caches

If a run behaves oddly (stale compiled code, missing definitions, a crash on a cache hit), clear the caches and try again. The next run fills them again.

```bash
vendor/bin/phel cache:clear
```

It clears the Phel caches under `.phel/`, including the `.phel/opcache/` file cache. If you set up a manual opcache file cache, wipe it too:

```bash
rm -rf /tmp/php-opcache
```

## Optimization levels

Set a higher compiler optimization level in `phel-config.php` (the default is `0`):

```php
<?php
return (new \Phel\Config\PhelConfig())
    ->withOptimizationLevel(2);
```

| Level | Effect |
|---|---|
| 0 | Off (default). No inlining, no tail-call rewrite. |
| 1 | Same as 0 today. Reserved for inlining single-expression private `defn-`. |
| 2 | `^:pure` call-site inlining plus rewrite of self-recursive tail calls into an implicit loop. |

The level applies to `phel build`, `phel run`, `phel test`, `phel eval`, and `phel compile`. The REPL and nREPL always compile at level `0` so interactive redefinition stays predictable. `phel build -O2` (long form `--optimization-level=2`) overrides the configured level for a single build.

Level 2 trade-offs:

- `^:pure` is your promise that a single-arity `defn` has no side effects and is safe to inline at call sites. The compiler trusts the annotation and does not check it.
- Tail-call rewriting removes the PHP stack frame per iteration, so deep self-recursion no longer overflows. The cost is a shorter stack trace inside the loop.
- Changing the level invalidates the compiled-code cache and the incremental `phel build` output, so the next run recompiles everything once.

### Spot build bloat

`phel build --report` prints a per-namespace breakdown after the build: namespace count, each namespace's compiled size, the total, the fresh/cached split, and build time. Use it to catch a namespace that compiles much larger than expected and to confirm CI builds hit the cache.

```bash
vendor/bin/phel build --report
```

## Faster functions

### Find the hot functions first

Do not guess. Profile a script to see per-function timings and compile phase costs, then tag or memoize only what matters:

```bash
vendor/bin/phel profile path/to/file.phel
```

See [Profile](/documentation/reference/cli-commands/#profile) for sort options and JSON output. Two language features then pay off in hot paths. [Functions and Recursion](/documentation/language/functions-and-recursion/#return-and-parameter-types-tag) covers them in full.

### Type tags

For hot numeric or string functions, add `:tag` annotations on the parameters and the return value. The compiler emits matching PHP type declarations and infers the return type from primitive operations in tail position, so the tracing JIT can specialize the call. A tag mismatch shows up as a Phel diagnostic at compile time.

```phel
(defn ^int add [^int a ^int b]
  (+ a b))

(add 2 3) ; => 5
```

### Memoization

`^:memoize` and `^{:memoize-lru N}` cache results per set of arguments, so a repeated expensive call becomes a lookup:

```phel
(defn ^:memoize fib [n]
  (if (< n 2) n (+ (fib (- n 1)) (fib (- n 2)))))

(fib 30) ; => 832040
```

`^:memoize` keeps every result forever. `^{:memoize-lru N}` keeps only the `N` most recent entries. See [`memoize`](/documentation/reference/api/core/#memoize) and [`memoize-lru`](/documentation/reference/api/core/#memoize-lru).

## Memory limit

`vendor/bin/phel` sets `memory_limit` to `-1` for you. If you call PHP directly or embed Phel, raise the limit yourself: on large projects the compiler's `token_get_all` validation can use more than 128M.

```bash
php -d memory_limit=-1 vendor/bin/phel test
```

## Next steps

- [Deployment](/documentation/guides/deployment/): worker runtimes (FrankenPHP, RoadRunner) that remove the per-request boot cost in production.
- PHP manual: [opcache configuration](https://www.php.net/manual/en/opcache.configuration.php).
