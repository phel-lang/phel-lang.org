+++
title = "Performance"
weight = 75
description = "Speed up phel test and phel run with CLI opcache, the compiled-code cache, optimization levels, profiling, and type tags."
+++

Make `phel test`, `phel run`, and the other CLI commands fast. Everything here applies to both source-checkout and PHAR installs.

## TL;DR: enable CLI opcache

Each `./vendor/bin/phel` invocation is a fresh PHP process. Without CLI opcache, PHP re-parses every `.php` file on every run (the whole of `vendor/`, the Phel compiler, the Symfony console, your own classes). Persisting compiled bytecode across processes is the single biggest win.

```ini
; /your/php/conf.d/ext-opcache.ini
opcache.enable_cli=1
opcache.file_cache=/tmp/php-opcache
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.interned_strings_buffer=16
```

Create the cache directory once and restart your shell:

```bash
mkdir -p /tmp/php-opcache
```

Repeat runs of `./vendor/bin/phel test` then drop from seconds to sub-second on a warm cache.

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

## Two caches that complement each other

Phel keeps its own compiled-code cache under `.phel/cache/` that memoizes Phel-to-PHP compilation per source hash. It pairs with opcache:

- **Phel's compiled-code cache** skips recompiling unchanged `.phel` source.
- **opcache's file cache** skips re-parsing the resulting PHP.

Invalidation is automatic. Each run hashes the `.phel` source (`md5`) against the stored entry; on a mismatch it recompiles that file and its transitive dependents, then `opcache_compile_file()`s the generated PHP. Changing the optimization level forces a full recompile.

The cache flags (`withEnableCompiledCodeCache`, `withEnableNamespaceCache`, `withCacheDir`) and their defaults live in [Configuration](/documentation/configuration/). You rarely need to touch them.

### Reset the caches

If a run behaves oddly (stale compiled code, missing definitions, a cache-hit crash), wipe both caches and retry. The next invocation repopulates cleanly.

```bash
rm -rf .phel/cache /tmp/php-opcache
```

The CLI also exposes a dedicated command for the Phel side:

```bash
vendor/bin/phel cache:clear
```

## Optimization levels

`phel-config.php` can opt the compiler into higher optimization levels (default `0`):

```php
<?php
return (new \Phel\Config\PhelConfig())
    ->withOptimizationLevel(2);
```

| Level | Effect |
|---|---|
| 0 | Off (default); output is byte-identical to previous releases. |
| 1 | Reserved for auto-inlining single-expression private `defn-` (not implemented yet). |
| 2 | `^:pure` call-site inlining plus rewrite of self-recursive tail calls into an implicit loop. |

The level applies to `phel build`, `phel run`, `phel test`, `phel eval`, and `phel compile`. The REPL and nREPL always compile at level `0` so interactive redefinition stays predictable. `phel build -O2` (long form `--optimization-level=2`) overrides the configured level for a single build.

Level 2 trade-offs:

- `^:pure` is your promise that a single-arity `defn` is side-effect free and safe to inline at call sites; the compiler trusts the annotation rather than verifying it.
- Tail-call rewriting eliminates per-iteration PHP stack frames (deep self-recursion no longer overflows) at the cost of a shorter stack trace inside the loop.
- Changing the level invalidates the compiled-code cache and the incremental `phel build` output, so the next run recompiles everything once.

### Spot build bloat

`phel build --report` prints a per-namespace breakdown after the build: namespace count, each namespace's compiled size, the total, the fresh/cached split, and build time. Use it to catch a namespace that compiles much larger than expected and to confirm CI builds hit the cache.

```bash
vendor/bin/phel build --report
```

## Faster functions

A few language features pay off in hot paths. They are covered in full under [Functions and Recursion](/documentation/language/functions-and-recursion/#return-and-parameter-types-tag); the performance angle:

### Type tags

For hot numeric or string functions, add `:tag` annotations on the parameters and the return slot. The compiler emits matching PHP type declarations and infers the return type from primitive ops in tail position, which lets the tracing JIT specialize the call. Tag mismatches surface as Phel diagnostics at compile time.

```phel
(defn ^int add [^int a ^int b]
  (+ a b))

(add 2 3)
```

### Memoization

`^:memoize` and `^{:memoize-lru N}` cache results per argument tuple, turning repeated expensive calls into a lookup:

```phel
(defn ^:memoize fib [n]
  (if (< n 2) n (+ (fib (- n 1)) (fib (- n 2)))))

(fib 30)
```

`^:memoize` keeps every result forever; `^{:memoize-lru N}` bounds the cache to the `N` most-recent entries. See [`memoize`](/documentation/reference/api/core/#memoize) and [`memoize-lru`](/documentation/reference/api/core/#memoize-lru).

### Find the hot functions first

Do not guess. Profile a script to see per-function timings and compile-phase costs, then tag or memoize only what matters:

```bash
vendor/bin/phel profile path/to/file.phel
```

See [Profile](/documentation/tooling/cli-commands/#profile) for output formats.

## Memory limit

`./vendor/bin/phel` raises `memory_limit` to `-1` automatically. If you invoke PHP directly or embed Phel, bump the limit yourself: the compiler's `token_get_all` validation can exceed 128M on large projects.

```bash
php -d memory_limit=-1 ./vendor/bin/phel test
```

## Next steps

- [Configuration](/documentation/configuration/) - cache flags and the full `phel-config.php` reference.
- [CLI Commands](/documentation/tooling/cli-commands/#profile) - `phel profile` and `phel cache:clear`.
- [Functions and Recursion](/documentation/language/functions-and-recursion/) - the full story on `:tag`, `^:memoize`, and `recur`.
- [Deployment](/documentation/deployment/) - worker runtimes (FrankenPHP, RoadRunner) that drop per-request boot cost in production.
- PHP manual: [opcache configuration](https://www.php.net/manual/en/opcache.configuration.php).
