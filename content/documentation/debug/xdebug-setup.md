+++
title = "XDebug Setup"
weight = 2
+++

[XDebug](https://xdebug.org/) is a powerful PHP debugging extension that enables step-through debugging with breakpoints, variable inspection, and call stack analysis. This is particularly useful for Phel core/compiler development and understanding how your Phel code compiles to PHP.

<details class="dev-note">
<summary>
  <span class="dev-note__title">Installation & Configuration</span>
  <span class="dev-note__chevron">›</span>
</summary>
<div class="dev-note__content">

<p style="font-size: 1.5em;font-weight: bold">Installation</p>

**Recommended: Install via [PIE](https://github.com/php/pie)** (PHP Installer for Extensions)

PIE is the modern replacement for PECL. Download the latest `pie.phar` from the [PIE releases](https://github.com/php/pie/releases), then:

```bash
# Install PIE
wget https://github.com/php/pie/releases/latest/download/pie.phar
chmod +x pie.phar
sudo mv pie.phar /usr/local/bin/pie

# Install XDebug with PIE
pie install xdebug/xdebug
```

**Note:** PECL is now deprecated. PIE is the official successor and recommended installation method.

**Alternative methods:**

```bash
# Via system package manager (Ubuntu/Debian)
apt-get install php-xdebug

# On macOS with Homebrew
brew install php
brew install php-xdebug
```

**For Docker/container environments**, add to your `Dockerfile`:

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

Configure XDebug in your `php.ini` or create a dedicated config file (`/etc/php/conf.d/xdebug.ini` or similar):

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

**Key settings:**
- `xdebug.mode=debug` - Enable debugging mode
- `xdebug.start_with_request=yes` - Start debugging on every request
- `xdebug.client_port=9003` - Default port for XDebug 3.x (was 9000 in XDebug 2.x)

**For containers/VMs:**
- Set `xdebug.client_host=host.docker.internal` (Docker Desktop)
- Or set to your host machine's IP address
- Ensure the debug port (9003) is exposed/forwarded

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

**Path Mappings** are crucial when using Docker/VMs. Map the container's path to your local workspace:

- Container path: `/var/www/html` (or wherever your code lives in the container)
- Local path: `${workspaceFolder}` (your local project directory)

**Usage:**

1. Set breakpoints directly in `.phel` files by clicking left of line numbers
2. Press `F5` or click "Run and Debug" → "Debug Phel"
3. Run your Phel code (CLI or web request)
4. Execution will pause at breakpoints, showing Phel source context

**Additional Commands:**

- `Phel: Show Compiled PHP Location` - Shows which PHP line a Phel line maps to
- `Phel: Clear Source Map Cache` - Clears cached source map data

> **Note:** The extension auto-detects the cache directory from `phel-config.php`. If your project uses a custom temp directory, it will be discovered automatically.

<details>
<summary><strong>Alternative: Using PHP Debug Extension</strong></summary>

If you prefer debugging at the PHP level (or don't have the Phel extension installed), you can use the [PHP Debug extension](https://marketplace.visualstudio.com/items?itemName=xdebug.php-debug):

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

With this approach, you'll need to set breakpoints in the compiled PHP files (found in the temp directory). Use `setKeepGeneratedTempFiles(true)` in `phel-config.php` to preserve the compiled files for inspection.

</details>

### PHPStorm

PHPStorm has built-in XDebug support:

- **Configure PHP Interpreter:**
  - Go to: `Settings` → `PHP` → `CLI Interpreter`
  - Add or select your PHP interpreter
  - Verify XDebug shows as "Installed ✓"

- **Configure Debug Settings:**
  - Go to: `Settings` → `PHP` → `Debug`
  - Set XDebug port to `9003`
  - Check "Can accept external connections"

- **Path Mappings (for Docker/VM):**
  - Go to: `Settings` → `PHP` → `Servers`
  - Add a new server configuration
  - Map your project root to the container path:
    - Local: `/Users/you/phel-project`
    - Remote: `/var/www/html`

- **Start Listening:**
  - Click the phone icon in the toolbar: "Start Listening for PHP Debug Connections"
  - Or use menu: `Run` → `Start Listening for PHP Debug Connections`

- **Set breakpoints** and run your code

Detailed guide: [VVV PHPStorm XDebug Setup](https://varyingvagrantvagrants.org/docs/en-US/references/xdebug-and-phpstorm/)

### Emacs

XDebug uses the Debug Adapter Protocol (DAP). Set up [dap-mode](https://emacs-lsp.github.io/dap-mode/):

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

## Debugging Phel Code

### With VS Code Phel Extension

The Phel VS Code extension provides a seamless debugging experience:

1. **Set breakpoints in `.phel` files**: Click in the gutter next to any line. The extension automatically maps these to the corresponding PHP lines.

2. **Source-level debugging**: Stack traces show Phel file names and line numbers, not the compiled PHP.

3. **Phel-native variable display**: Variables are shown with Phel formatting:
   - Vectors: `[3 items]`
   - Maps: `{2 entries}`
   - Keywords: `:status`
   - Lists: `(5 items)`

4. **Skip internals**: By default, stepping skips Phel runtime code. Disable with `"skipPhelInternals": false` if debugging the Phel runtime itself.

5. **Hover for mapping info**: Hover over a breakpoint to see which PHP file/line it maps to.

### With Other Editors

When debugging Phel code with XDebug in editors without native Phel support:

1. **Breakpoints in compiled PHP**: Since Phel compiles to PHP, you'll be stepping through the generated PHP code. Use `setKeepGeneratedTempFiles(true)` in `phel-config.php` to inspect the compiled output.

2. **Path mapping is critical**: Ensure your editor knows how to map container/VM paths to local paths.

3. **Debugging the compiler**: For Phel core development, set breakpoints in the compiler code (`vendor/phel-lang/phel-lang/src/`).

4. **REPL debugging**: You can debug REPL sessions by running the REPL with XDebug enabled.

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

**Enable XDebug logging** to diagnose connection problems:

```ini
xdebug.log=/tmp/xdebug.log
xdebug.log_level=7
```

**Common issues:**

- **Port conflicts**: XDebug 3.x uses port 9003 (not 9000). Update your editor configuration.
- **Firewall**: Ensure port 9003 is not blocked by firewall rules.
- **Path mappings**: Double-check that container paths match your actual paths. Use `pwd` inside the container to verify.
- **Docker**: Use `host.docker.internal` instead of `localhost` for `xdebug.client_host`.
- **WSL2/VM**: May need to use the host machine's network IP address.

**Testing XDebug connection:**

Create a simple test script:

```php
<?php
xdebug_break(); // Hard breakpoint
echo "XDebug is working!\n";
```

Run it and verify your debugger connects.
