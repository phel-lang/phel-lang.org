+++
title = "Deployment"
weight = 13
description = "Deploy Phel apps on plain PHP-FPM or keep namespaces warm across requests with FrankenPHP and RoadRunner worker runtimes."
aliases = ["/documentation/deployment/"]
+++

This page shows you how to ship a Phel app: build it ahead of time, load it once per process, and run it on PHP-FPM or on a worker runtime (FrankenPHP, RoadRunner).

PHP shares nothing between requests by default. Every request starts fresh, so Phel namespaces do not persist. [`phel build`](/documentation/reference/cli-commands/#build-the-project) compiles your namespaces to PHP ahead of time and opcache caches the bytecode, so no request parses Phel (see [Performance](/documentation/guides/performance/) for the cache setup). Each request still runs the top-level forms of every loaded namespace to register its `def`s.

A **worker runtime** keeps the PHP process alive across requests. Namespaces load once at boot, and in-memory state survives between requests, close to the JVM and Clojure model.

## What a request pays

One measurement (PHP 8.5, Phel 0.53, Apple M4 Pro, a host that boots and then calls `phel.core/str`, median of 15 runs, 5 for the cold column):

| | cold `.phel/cache` | warm cache, no opcache | warm cache + opcache file cache |
|---|---|---|---|
| `vendor/autoload.php` | 1ms | 1ms | 1ms |
| `Phel::bootstrap()` | 7ms | 5ms | 4ms |
| load `phel.core` | 1054ms | 48ms | 33ms |
| **first call reachable after** | **1062ms** | **54ms** | **38ms** |
| peak memory | 88MB | 20MB | 6MB |
| per call after that | 0.1µs | 0.1µs | 0.1µs |

What this means:

- Calling Phel from PHP costs 0.1µs per call. The boundary is never the cost.
- Loading namespaces is the cost, and compiling them is the expensive part: a cold cache costs 20 times a warm one. Never let a request compile. Ship `phel build` output or a warm `.phel/cache`.
- Opcache takes another third off the load time and most of the memory.
- Under PHP-FPM every request pays the whole column. A worker runtime pays it once per process.

The absolute figures move with the machine and with how much your app loads, so measure your own:

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

{% php_note() %}
**State is per-worker.** FrankenPHP runs several worker instances, each with its own memory. An in-process value (an `atom`, a cache) is shared across requests handled by the *same* worker, not across all of them. For global state, use Redis, APCu, or a database. Append `,1` to the worker path (`--worker ./worker.php,1`) to pin a single worker.
{% end %}

## RoadRunner

RoadRunner is a Go server that keeps PHP workers alive and hands them PSR-7 requests. The worker has the same shape as the FrankenPHP one. Install the worker library, a PSR-7 implementation, and the `rr` binary:

```bash
composer require spiral/roadrunner-http nyholm/psr7
composer require --dev spiral/roadrunner-cli
vendor/bin/rr get-binary    # downloads ./rr
```

`worker.php`:

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

`.rr.yaml`:

```yaml
version: "3"

server:
  command: "php worker.php"

http:
  address: 0.0.0.0:8080
```

Run it:

```bash
./rr serve
```

{% php_note() %}
**State is per-worker here too.** RoadRunner starts one worker per CPU core by default. An `atom` counting hits goes up once per request that lands on *its* worker, not once per request. Pin a single worker with `pool: { num_workers: 1 }` under `http`, or keep shared state in Redis, APCu, or a database.
{% end %}

## When you do not need a worker runtime

Plain PHP-FPM with opcache is fine for most apps. The last column of the table is the boot cost each request pays (38ms in that measurement). Use a worker runtime when that cost shows up in profiling, or when you want in-memory state (caches, connection pools) to live across requests.
