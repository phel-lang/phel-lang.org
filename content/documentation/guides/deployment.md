+++
title = "Deployment"
weight = 13
description = "Deploy Phel apps on plain PHP-FPM or keep namespaces warm across requests with FrankenPHP and RoadRunner worker runtimes, and keep the CLI fast with opcache."
aliases = ["/documentation/deployment/", "/documentation/guides/performance/", "/documentation/performance/"]
+++

PHP shares nothing between requests by default. Every request starts fresh, so Phel namespaces do not persist. [`phel build`](/documentation/reference/cli-commands/#build-the-project) compiles your namespaces to PHP ahead of time and opcache caches the bytecode, so no request parses Phel. Each request still runs the top-level forms of every loaded namespace to register its `def`s.

A **worker runtime** keeps the PHP process alive across requests. Namespaces load once at boot, and in-memory state survives between requests, close to the JVM and Clojure model.

## What a request pays

Calling Phel from PHP costs about 0.1µs per call, so the boundary is never the cost. Loading namespaces is the cost, and compiling them is the expensive part: in one measurement a cold cache took 20 times longer than a warm one. Never let a request compile. Ship `phel build` output or a warm `.phel/cache`. Opcache takes another third off the load time and most of the memory.

Under PHP-FPM every request pays the load. A worker runtime pays it once per process. Measure your own app:

```php
require 'vendor/autoload.php';
Phel\Phel::bootstrap(__DIR__);
$t = hrtime(true);
new Phel\Run\RunFacade()->runNamespace('app.main');
printf("%.1f ms, %.1f MB\n", (hrtime(true) - $t) / 1e6, memory_get_peak_usage(true) / 1048576);
```

## The one rule

Require the built entry point **once, before the request loop**. Inside the loop, only call your exported functions.

```php
<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/out/app/main.php'; // loads Phel namespaces ONCE
```

To produce it, set `withMainPhelNamespace('app.main')` in [`phel-config.php`](/documentation/reference/configuration/) and run [`phel build`](/documentation/reference/cli-commands/#build-the-project). The build writes each namespace to `out/` by default, so `app.main` lands in `out/app/main.php`. Change the folder with `withBuildDestDir`.

To call Phel functions from the PHP worker, mark them `{:export true}` and run [`phel export`](/documentation/reference/cli-commands/#export-definitions). It generates one PHP class per namespace, such as `\PhelGenerated\App\Main`.

## Loading Phel: prod vs dev

One boot hook covers both environments if you guard the load. In production the built file exists and you `require` it. In development it does not, so you fall back to `\Phel::run()`, which boots Gacela and compiles on the first call:

```php
$built = $root . '/out/app/main.php';

if (is_file($built)) {
    require $built;                // prod: precompiled, self-contained \Phel::addDefinition() calls, no Gacela, no compiler
} else {
    \Phel::run($root, 'app.main'); // dev: boots Gacela and compiles to temp files on first call
}
```

- `$root` is the project root your framework already knows (`base_path()`, `getProjectDir()`, `__DIR__`).
- Run this **once** per process, behind a static flag, never on a per-request path.
- Ship `out/` in the deploy artifact, or run `phel build` in CI. Keep it out of your dev checkout, so `is_file()` is false and `\Phel::run()` takes over.

[Framework Integration](/documentation/web/framework-integration/) wires this guard into Laravel, Symfony, and framework-less kernels.

## FrankenPHP

`worker.php`:

```php
<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/out/app/main.php'; // once, outside the loop

$handler = static function (): void {
    // call an exported Phel wrapper per request
    echo \PhelGenerated\App\Main::handleRequest();
};

while (frankenphp_handle_request($handler)) {
    gc_collect_cycles();
}
```

Run it:

```bash
frankenphp php-server --root . --worker ./worker.php
```

## RoadRunner

RoadRunner is a Go server that keeps PHP workers alive and hands them PSR-7 requests. It needs `spiral/roadrunner-http`, a PSR-7 implementation such as `nyholm/psr7`, and the `rr` binary. The worker has the same shape as the FrankenPHP one:

```php
<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/out/app/main.php'; // once, outside the loop

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Spiral\RoadRunner\Http\PSR7Worker;
use Spiral\RoadRunner\Worker;

$factory = new Psr17Factory();
$psr7 = new PSR7Worker(Worker::create(), $factory, $factory, $factory);

while ($request = $psr7->waitRequest()) {
    try {
        // call an exported Phel wrapper per request
        $body = \PhelGenerated\App\Main::handleRequest($request->getUri()->getPath());
        $psr7->respond(new Response(200, [], $body));
    } catch (\Throwable $e) {
        $psr7->respond(new Response(500, [], 'Internal error'));
        $psr7->getWorker()->error((string) $e);
    }
}
```

Point `server.command` in `.rr.yaml` at `php worker.php`, set `http.address`, and run `./rr serve`.

{% <php_note> %}
**State is per-worker.** Both runtimes start several workers, each with its own memory (RoadRunner starts one per CPU core). An `atom` or cache is shared across requests handled by the *same* worker, not across all of them. For global state, use Redis, APCu, or a database. To pin a single worker, append `,1` to the FrankenPHP worker path (`--worker ./worker.php,1`) or set `pool: { num_workers: 1 }` under `http` in `.rr.yaml`.
{% </php_note> %}

## When you do not need a worker runtime

Plain PHP-FPM with opcache is fine for most apps. Use a worker runtime when the per-request boot cost shows up in profiling, or when you want in-memory state (caches, connection pools) to live across requests.

## Performance

### Opcache for the CLI

Each `vendor/bin/phel` call is a fresh PHP process. Without CLI opcache, PHP parses every `.php` file again on every run. The `phel` binary fixes this for you: when the opcache extension is loaded and `pcntl` is available, it restarts itself with a file cache under `.phel/opcache/`. `vendor/bin/phel doctor` shows the status under `Checking performance`. Set `PHEL_NO_OPCACHE_REEXEC=1` to turn this off and keep your own opcache settings.

Configure opcache yourself when `pcntl` is missing, or when you run PHP directly instead of the `phel` binary:

```ini
; /your/php/conf.d/ext-opcache.ini (find it with `php --ini`)
opcache.enable_cli=1
opcache.file_cache=/tmp/php-opcache
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.interned_strings_buffer=16
```

Create the cache directory with `mkdir -p /tmp/php-opcache`, and again after a reboot empties `/tmp`. With a warm cache, repeat runs of `vendor/bin/phel test` drop from seconds to under one second.

`vendor/bin/phel` also sets `memory_limit` to `-1`. If you call PHP directly or embed Phel, raise the limit yourself: on large projects the compiler can use more than 128M.

### The compiled-code cache

Phel stores the PHP compiled from each `.phel` file under `.phel/cache/`, keyed by an `md5` of the source. On a mismatch it recompiles that file and every file that depends on it. Opcache then skips parsing the generated PHP again. The cache flags live in [Configuration](/documentation/reference/configuration/).

If a run behaves oddly (stale compiled code, missing definitions, a crash on a cache hit), clear the caches. This also clears `.phel/opcache/`; wipe a manual file cache such as `/tmp/php-opcache` yourself.

```bash
vendor/bin/phel cache:clear
```

### Faster code

Profile before you change code: [`phel profile`](/documentation/reference/cli-commands/#profile) shows per-function timings. Then see [type tags](/documentation/language/functions-and-recursion/#return-and-parameter-types-tag), [memoization](/documentation/language/functions-and-recursion/#defn-metadata-shortcuts), and [Configuration](/documentation/reference/configuration/) for compiler optimization levels.
