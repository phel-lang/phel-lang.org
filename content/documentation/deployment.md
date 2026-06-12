+++
title = "Deployment"
weight = 80
description = "Deploy Phel apps on plain PHP-FPM or keep namespaces warm across requests with FrankenPHP and RoadRunner worker runtimes."
+++

PHP is **shared-nothing** by default: every request boots a fresh process, so a Phel namespace does not persist between requests. [`phel build`](/documentation/tooling/cli-commands/) compiles your namespaces to PHP ahead of time and opcache caches that bytecode, so nothing re-parses per request. But each request still re-runs every loaded namespace's top-level forms to register its `def`s.

A **worker runtime** keeps the PHP process alive across requests: namespaces load **once** at boot and in-memory state survives between requests, much closer to the JVM/Clojure model.

## The one rule

Require the built entry point **once, before the request loop**. Everything inside the loop should only call your exported functions.

```php
<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/build/app/main.php'; // loads Phel namespaces ONCE
```

To produce that entry point, see [`phel build`](/documentation/tooling/cli-commands/) and configure `withMainPhelNamespace` / `withMainPhpPath` in [`phel-config.php`](/documentation/configuration/). To expose Phel functions to the PHP worker, mark them `{:export true}` and run [`phel export`](/documentation/tooling/cli-commands/), which generates one PHP class per namespace.

## FrankenPHP

`worker.php`:

```php
<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/build/app/main.php'; // once, outside the loop

$handler = static function (): void {
    // call an exported Phel wrapper per request
    echo \App\PhelGenerated\App\Main::handleRequest();
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

Same shape: require the built entry point once, then handle requests in the worker loop (via `spiral/roadrunner-http`'s PSR-7 worker), calling exported Phel functions per request.

## When you do not need a worker runtime

Plain PHP-FPM with opcache is fine for most apps. Reach for a worker runtime when boot cost or per-request namespace registration shows up in profiling, or when you want persistent in-memory state (caches, connection pools) across requests.
