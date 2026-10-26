+++
title = "Xdebug Setup"
weight = 3
description = "Install and configure Xdebug for Phel: breakpoints in .phel files, path mappings, and editor setup for VS Code, PhpStorm, Emacs, and Neovim"
aliases = ["/documentation/debug/xdebug-setup"]
+++

This page gets [Xdebug](https://xdebug.org/) running for Phel so you can set breakpoints, step through code, and inspect variables in your editor. With the VS Code Phel extension you set breakpoints in `.phel` files. Other editors debug the compiled PHP. For when to use Xdebug instead of lighter tools, see [Debugging](/documentation/guides/debugging/).

## Install

Use [PIE](https://github.com/php/pie), the official successor to PECL:

```bash
wget https://github.com/php/pie/releases/latest/download/pie.phar
chmod +x pie.phar
sudo mv pie.phar /usr/local/bin/pie

pie install xdebug/xdebug
```

Other options:

```bash
# Ubuntu/Debian
apt-get install php-xdebug

# macOS with Homebrew (Homebrew's PHP ships pecl)
brew install php
pecl install xdebug
```

In a `Dockerfile`:

```dockerfile
RUN curl -L https://github.com/php/pie/releases/latest/download/pie.phar -o /usr/local/bin/pie && \
    chmod +x /usr/local/bin/pie && \
    pie install xdebug/xdebug

# Or with PECL
RUN pecl install xdebug && \
    docker-php-ext-enable xdebug
```

Check that it loaded:

```bash
php -v
# ... with Xdebug v3.x.x
```

## Configure

Add this to `php.ini` or a separate file such as `/etc/php/conf.d/xdebug.ini`:

```ini
[xdebug]
zend_extension=xdebug.so
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_host=localhost
xdebug.client_port=9003
```

| Setting | Purpose |
|---|---|
| `xdebug.mode=debug` | Turn on step debugging |
| `xdebug.start_with_request=yes` | Start a session on every request or CLI run |
| `xdebug.client_host` | Where your editor listens. In Docker Desktop use `host.docker.internal`, in a VM use the host IP |
| `xdebug.client_port=9003` | The Xdebug 3 default (Xdebug 2 used 9000). Expose or forward it from containers |

## Editor setup

### VS Code

Install the [Phel VS Code extension](https://github.com/phel-lang/phel-vs-code-extension). It provides the `phel` debug adapter. The generic PHP Debug extension only provides `"type": "php"` and cannot step through `.phel` files.

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

To debug:

1. Click left of a line number in a `.phel` file to set a breakpoint.
2. Press `F5`, or open "Run and Debug" and pick "Debug Phel".
3. Run your Phel code (CLI or web).
4. Execution pauses at the breakpoint. Stack traces show Phel files and lines, and variables show as Phel values (`[3 items]`, `{2 entries}`, `:status`). Hover a breakpoint to see its PHP file and line.

Two commands help when a breakpoint does not map: `Phel: Show Compiled PHP Location` shows the mapped PHP line, and `Phel: Clear Source Map Cache` clears cached source maps.

To debug at PHP level instead, use the [PHP Debug extension](https://marketplace.visualstudio.com/items?itemName=xdebug.php-debug) with `"type": "php"` and `"port": 9003`, and set breakpoints in the compiled PHP files. The next section explains how to keep them.

### Other editors: debug the compiled PHP

PhpStorm, Emacs, and Neovim have no Phel debug adapter, so you set breakpoints in the PHP that Phel generates. `phel run` deletes those files after each run. Keep them with `withKeepGeneratedTempFiles(true)` (see [Debugging](/documentation/guides/debugging/#keep-the-generated-files)). To debug the compiler itself, set breakpoints in `vendor/phel-lang/phel-lang/src/`.

### PhpStorm

PhpStorm supports Xdebug out of the box.

1. `Settings` > `PHP` > `CLI Interpreter`: select a PHP with Xdebug installed.
2. `Settings` > `PHP` > `Debug`: port `9003`, check "Can accept external connections".
3. For Docker or a VM, `Settings` > `PHP` > `Servers`: add a server and map the local path to the container path (for example `/Users/you/phel-project` to `/var/www/html`).
4. Click the phone icon in the toolbar, or `Run` > `Start Listening for PHP Debug Connections`.

More detail: [VVV PhpStorm Xdebug guide](https://varyingvagrantvagrants.org/docs/en-US/references/xdebug-and-phpstorm/).

### Emacs

Use [dap-mode](https://emacs-lsp.github.io/dap-mode/):

```elisp
(use-package dap-mode
  :config
  (require 'dap-php)
  (dap-php-setup))

(dap-register-debug-template
  "Phel Xdebug"
  (list :type "php"
        :request "launch"
        :mode "remote"
        :port 9003
        :pathMappings (ht ("/var/www/html" "/local/path/to/project"))))
```

### Neovim

Use [nvim-dap](https://github.com/mfussenegger/nvim-dap) with the vscode-php-debug adapter:

```lua
local dap = require('dap')
dap.adapters.php = {
  type = 'executable',
  command = 'node',
  args = { '/path/to/vscode-php-debug/out/phpDebug.js' }
}

dap.configurations.php = {
  {
    type = 'php',
    request = 'launch',
    name = 'Listen for Xdebug',
    port = 9003,
    pathMappings = {
      ["/var/www/html"] = "${workspaceFolder}"
    }
  }
}
```

## Troubleshooting

Check that Xdebug is loaded and configured, and that the port is open:

```bash
php -v
php -i | grep xdebug
telnet localhost 9003
```

Turn on the Xdebug log:

```ini
xdebug.log=/tmp/xdebug.log
xdebug.log_level=7
```

| Symptom | Fix |
|---|---|
| Editor never connects | Xdebug 3 uses port 9003, not 9000. Check the editor config and the firewall |
| Connects from Docker fails | Set `xdebug.client_host=host.docker.internal` instead of `localhost` |
| Fails under WSL2 or a VM | Use the host's network IP as `xdebug.client_host` |
| Breakpoints never hit | Check path mappings. Run `pwd` inside the container to get the real path |

To test the connection, run a script with a hard breakpoint. Your debugger should stop on it:

```php
<?php
xdebug_break();
echo "Xdebug is working\n";
```

{% <callout kind="tip"> %}
No Xdebug? Phel's built-in [`(break)`](/documentation/guides/debugging/#pause-with-break) pauses in a sub-REPL with all locals in scope, with no extension or editor setup.
{% </callout> %}
