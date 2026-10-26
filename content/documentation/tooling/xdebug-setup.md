+++
title = "Xdebug Setup"
weight = 3
description = "Set up Xdebug for Phel: breakpoints in .phel files with VS Code, and debugging the compiled PHP in other editors"
aliases = ["/documentation/debug/xdebug-setup"]
+++

With [Xdebug](https://xdebug.org/) and the VS Code Phel extension you set breakpoints in `.phel` files. Other editors debug the compiled PHP. For lighter tools, see [Debugging](/documentation/guides/debugging/).

## Install and configure

Install Xdebug 3 with the [official instructions](https://xdebug.org/docs/install), then turn on step debugging in `php.ini` or a file such as `conf.d/xdebug.ini`:

```ini
[xdebug]
zend_extension=xdebug.so
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_host=localhost
xdebug.client_port=9003
```

`php -v` should print `with Xdebug v3.x.x`. In Docker Desktop set `xdebug.client_host=host.docker.internal`; in a VM or WSL2, use the host's IP.

## Editor setup

### VS Code

The [Phel VS Code extension](https://github.com/phel-lang/phel-vs-code-extension) provides the `phel` debug adapter. The generic PHP Debug extension cannot step through `.phel` files.

Create `.vscode/launch.json`:

```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "type": "phel",
            "request": "launch",
            "name": "Debug Phel",
            "phpDebugPort": 9003
        },
        {
            "type": "phel",
            "request": "launch",
            "name": "Debug Phel (Docker)",
            "phpDebugPort": 9003,
            "pathMappings": {
                "/var/www/html": "${workspaceFolder}"
            }
        }
    ]
}
```

| Option | Type | Default | Description |
|---|---|---|---|
| `phpDebugPort` | number | 9003 | Xdebug port to listen on |
| `pathMappings` | object | {} | Container or remote path to local path, for Docker and VMs |
| `cacheDir` | string | auto | Phel cache directory, read from `phel-config.php` |
| `skipPhelInternals` | boolean | true | Skip the Phel runtime when stepping |
| `skipFiles` | string[] | [] | Glob patterns for files to skip when stepping |

Set a breakpoint in a `.phel` file, press `F5` ("Debug Phel"), and run your code. Stack traces show Phel files and lines, and variables show as Phel values.

Two commands help when a breakpoint does not map: `Phel: Show Compiled PHP Location` shows the mapped PHP line, and `Phel: Clear Source Map Cache` clears cached source maps.

To debug at PHP level instead, use the [PHP Debug extension](https://marketplace.visualstudio.com/items?itemName=xdebug.php-debug) as described below.

### Other editors: debug the compiled PHP

PhpStorm, Emacs (dap-mode), and Neovim (nvim-dap) have no Phel debug adapter. Set them up as for any PHP project ([PhpStorm guide](https://www.jetbrains.com/help/phpstorm/configuring-xdebug.html)) and set breakpoints in the PHP that Phel generates. `phel run` deletes those files after each run. Keep them with `withKeepGeneratedTempFiles(true)` (see [Debugging](/documentation/guides/debugging/#keep-the-generated-files)). To debug the compiler itself, set breakpoints in `vendor/phel-lang/phel-lang/src/`.

## Troubleshooting

| Symptom | Fix |
|---|---|
| Editor never connects | Xdebug 3 uses port 9003, not 9000. Check the editor config and the firewall |
| Breakpoints never hit in Docker | Check `xdebug.client_host` and the path mappings. Run `pwd` inside the container to get the real path |

For more, set `xdebug.log=/tmp/xdebug.log` and read the [Xdebug docs](https://xdebug.org/docs/step_debug#troubleshoot).

{% <callout kind="tip"> %}
No Xdebug? Phel's built-in [`(break)`](/documentation/guides/debugging/#pause-with-break) pauses in a sub-REPL with all locals in scope, with no extension or editor setup.
{% </callout> %}
