+++
title = "XDebug Setup"
weight = 6
description = "Install and configure XDebug for Phel: breakpoints in .phel files, path mappings, and editor setup for VS Code, PhpStorm, Emacs, and Neovim"
aliases = ["/documentation/debug/xdebug-setup"]
+++

[XDebug](https://xdebug.org/) gives you step-through debugging: breakpoints, variable inspection, and call-stack analysis. With the VS Code Phel extension you set breakpoints directly in `.phel` files; in other editors you debug the compiled PHP.

<details class="dev-note">
<summary>
  <span class="dev-note__title">Installation & Configuration</span>
  <span class="dev-note__chevron">›</span>
</summary>
<div class="dev-note__content">

<p style="font-size: 1.5em;font-weight: bold">Installation</p>

**Recommended: [PIE](https://github.com/php/pie)** (PHP Installer for Extensions)

PIE replaces PECL. Get latest `pie.phar` from [releases](https://github.com/php/pie/releases):

```bash
# Install PIE
wget https://github.com/php/pie/releases/latest/download/pie.phar
chmod +x pie.phar
sudo mv pie.phar /usr/local/bin/pie

# Install XDebug with PIE
pie install xdebug/xdebug
```

**Note:** PECL deprecated. PIE is the official successor.

**Alternatives:**

```bash
# Via system package manager (Ubuntu/Debian)
apt-get install php-xdebug

# On macOS with Homebrew
brew install php
brew install php-xdebug
```

**Docker/containers,** add to your `Dockerfile`:

```dockerfile
# Using PIE (recommended)
RUN curl -L https://github.com/php/pie/releases/latest/download/pie.phar -o /usr/local/bin/pie && \
    chmod +x /usr/local/bin/pie && \
    pie install xdebug/xdebug

# Or using PECL (legacy)
RUN pecl install xdebug && \
    docker-php-ext-enable xdebug
```

**Verify installation:**

```bash
php -v
# Should show: "with Xdebug v3.x.x"
```

<hr>

<p style="font-size: 1.5em;font-weight: bold">Configuration</p>

Configure in `php.ini` or a dedicated file (`/etc/php/conf.d/xdebug.ini`):

```ini
[xdebug]
zend_extension=xdebug.so

# XDebug 3.x configuration
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_host=localhost
xdebug.client_port=9003

# For Docker/VM environments, use host.docker.internal or your host IP
# xdebug.client_host=host.docker.internal

# Optional: logging for troubleshooting
# xdebug.log=/tmp/xdebug.log
```

**Settings:**
- `xdebug.mode=debug` enable debugging
- `xdebug.start_with_request=yes` debug on every request
- `xdebug.client_port=9003` default port (XDebug 2.x used 9000)

**Containers/VMs:**
- `xdebug.client_host=host.docker.internal` (Docker Desktop)
- Or use host IP
- Expose/forward port 9003

</div>
</details>

## Editor Setup

### VSCode

Install the [PHP Debug extension](https://marketplace.visualstudio.com/items?itemName=xdebug.php-debug):

Create `.vscode/launch.json` in your Phel project:

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

**Configuration Options:**

| Option              | Type     | Default  | Description                                                 |
|---------------------|----------|----------|-------------------------------------------------------------|
| `phpDebugPort`      | number   | 9003     | XDebug port to listen on                                    |
| `pathMappings`      | object   | {}       | Path mappings for Docker/remote debugging                   |
| `cacheDir`          | string   | auto     | Phel cache directory (auto-detected from `phel-config.php`) |
| `skipPhelInternals` | boolean  | true     | Skip stepping through Phel runtime code                     |
| `skipFiles`         | string[] | []       | Glob patterns for files to skip when stepping               |

**Path mappings** matter for Docker/VMs. Map container path to local workspace:

- Container: `/var/www/html`
- Local: `${workspaceFolder}`

**Usage:**

1. Click left of a line number in `.phel` to set a breakpoint.
2. Press `F5` or "Run and Debug" → "Debug Phel".
3. Run your Phel code (CLI or web).
4. Execution pauses at breakpoints with Phel source context.

**Commands:**

- `Phel: Show Compiled PHP Location` shows the mapped PHP line
- `Phel: Clear Source Map Cache` clears cached source maps

> **Note:** Cache dir auto-detected from `phel-config.php`.

<details>
<summary><strong>Alternative: Using PHP Debug Extension</strong></summary>

For PHP-level debugging (or no Phel extension), use the [PHP Debug extension](https://marketplace.visualstudio.com/items?itemName=xdebug.php-debug):

```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for Xdebug",
            "type": "php",
            "request": "launch",
            "port": 9003,
            "pathMappings": {
                "/var/www/html": "${workspaceFolder}"
            }
        }
    ]
}
```

Set breakpoints in compiled PHP files (in the temp dir). Use `withKeepGeneratedTempFiles(true)` in `phel-config.php` to preserve them.

</details>

### PHPStorm

PHPStorm has built-in XDebug support.

- **PHP Interpreter:** `Settings` → `PHP` → `CLI Interpreter`. Add/select PHP, verify XDebug "Installed ✓".
- **Debug:** `Settings` → `PHP` → `Debug`. Port `9003`. Check "Can accept external connections".
- **Path Mappings (Docker/VM):** `Settings` → `PHP` → `Servers`. New server. Map local to container (e.g. `/Users/you/phel-project` → `/var/www/html`).
- **Start listening:** phone icon in the toolbar, or `Run` → `Start Listening for PHP Debug Connections`.
- Set breakpoints, run.

Guide: [VVV PHPStorm XDebug Setup](https://varyingvagrantvagrants.org/docs/en-US/references/xdebug-and-phpstorm/).

### Emacs

XDebug uses DAP. Set up [dap-mode](https://emacs-lsp.github.io/dap-mode/):

```elisp
(use-package dap-mode
  :config
  (require 'dap-php)
  (dap-php-setup))

(dap-register-debug-template
  "Phel XDebug"
  (list :type "php"
        :request "launch"
        :mode "remote"
        :port 9003
        :pathMappings (ht ("/var/www/html" "/local/path/to/project"))))
```

### Neovim

Set up [nvim-dap](https://github.com/mfussenegger/nvim-dap):

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

## Debugging Phel code

### With VS Code Phel extension

1. **Breakpoints in `.phel`:** click the gutter. Auto-mapped to PHP lines.
2. **Source-level traces:** stack traces show Phel file names and line numbers.
3. **Phel-native variables:** vectors as `[3 items]`, maps as `{2 entries}`, keywords as `:status`, lists as `(5 items)`.
4. **Skip internals:** stepping skips Phel runtime. Disable with `"skipPhelInternals": false`.
5. **Hover for mapping:** hover a breakpoint to see the PHP file/line.

### With other editors

Without native Phel support:

1. **Breakpoints in compiled PHP:** Phel compiles to PHP. Use `withKeepGeneratedTempFiles(true)` to inspect output.
2. **Path mapping:** map container/VM paths to local.
3. **Compiler debugging:** breakpoints in `vendor/phel-lang/phel-lang/src/`.
4. **REPL debugging:** start REPL with XDebug enabled.

## Troubleshooting

**Connection issues:**

```bash
# Check if XDebug is loaded
php -v

# Check XDebug configuration
php -i | grep xdebug

# Test if port 9003 is open
telnet localhost 9003
```

**Enable XDebug logging:**

```ini
xdebug.log=/tmp/xdebug.log
xdebug.log_level=7
```

**Common issues:**

- **Port:** XDebug 3.x uses 9003, not 9000. Update editor config.
- **Firewall:** unblock 9003.
- **Path mappings:** `pwd` inside container to verify.
- **Docker:** use `host.docker.internal` instead of `localhost`.
- **WSL2/VM:** may need host network IP.

**Test connection:**

Simple test:

```php
<?php
xdebug_break(); // Hard breakpoint
echo "XDebug is working!\n";
```

Run it, verify the debugger connects.

## Next steps

- [Editor support](/documentation/tooling/editor-support/) - install the VS Code Phel extension used above
- [PHP debugging tools](/documentation/tooling/php-tools/) - lighter-weight `var_dump`/`dump` debugging
- [Configuration](/documentation/configuration/) - `withKeepGeneratedTempFiles` and other dev settings
