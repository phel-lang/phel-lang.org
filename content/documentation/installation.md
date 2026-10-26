+++
title = "Installation"
weight = 3
description = "Install Phel with Composer, the PHAR, a one-line script, Docker, or Nix, then check your setup with phel doctor."
+++

This page shows every way to install Phel and how to check that it works. All methods need **PHP 8.5+**, except [Quick launch](#quick-launch-no-php-no-docker) and Docker, which bring their own PHP.

## Which method?

| Goal                               | Use                                               |
|------------------------------------|---------------------------------------------------|
| New project with tests + scripts   | [Composer skeleton](#new-project-from-skeleton)   |
| Add to existing Composer project   | [Composer require](#add-to-an-existing-project)   |
| Run a single file, no setup        | [PHAR](#phar-no-project-setup)                    |
| **No PHP**, no Docker              | [Quick launch](#quick-launch-no-php-no-docker)    |
| **No PHP**, Docker available       | [Docker](#docker-no-php-required)                 |
| Reproducible dev shells            | [Nix](#nix)                                       |
| Guided first project               | [Getting Started](/documentation/getting-started/) |

## Composer (recommended)

### New project from skeleton

The skeleton ships with tests, build config, and ready-to-use Composer scripts (`repl`, `dev`, `test`, `build`, `format`).

```bash
composer create-project --stability dev phel-lang/cli-skeleton example-app
cd example-app
composer repl
```

### Add to an existing project

```bash
composer require phel-lang/phel-lang
vendor/bin/phel init my-app    # scaffold phel-config.php + src/
```

Then run every command as `vendor/bin/phel <cmd>`, for example `vendor/bin/phel repl`. `phel init` has more layouts and templates: see [CLI Commands](/documentation/reference/cli-commands/).

{% <php_note> %}
**Does this replace my PHP app?** No. Phel lives next to your PHP code. Call compiled Phel namespaces from PHP after `require 'vendor/autoload.php'`, or call PHP from Phel. Add it to any Composer project (Laravel, Symfony, a WordPress plugin) and use it where a Lisp fits better. See [Framework Integration](/documentation/web/framework-integration/).
{% </php_note> %}

## PHAR (no project setup)

The PHAR is a single file that runs without Composer. Use it for quick experiments, one-off CI jobs, or to try the language.

```bash
curl -L https://phel-lang.org/phar -o phel.phar
php phel.phar --version
```

Every command works the same way:

```bash
php phel.phar repl
php phel.phar run src/main.phel
php phel.phar test --filter foo
```

To make it available everywhere:

```bash
chmod +x phel.phar
sudo mv phel.phar /usr/local/bin/phel
phel repl
```

## Quick launch (no PHP, no Docker)

One command, no PHP and no container runtime. Linux and macOS, x86_64 and arm64.

```bash
bash <(curl -sL https://phel-lang.org/get) repl
```

What it does:

- Downloads a [static PHP build](https://github.com/crazywhalecc/static-php-cli) and the Phel PHAR into a private temporary cache
- Checks the default PHP tarball against a SHA-256 pinned in the script, using `shasum` or `sha256sum`
- Writes a small `phel` wrapper next to them
- Runs your command

Nothing lands outside that folder, nothing needs root. The first run takes a few seconds, later runs start instantly.

Not on bash or zsh? Download first, then run it:

```bash
curl -sL https://phel-lang.org/get -o /tmp/phel-get
bash /tmp/phel-get repl
```

> Do not use `curl ... | bash`. Bash takes over stdin and the REPL exits on the spot. Use `bash <(curl ...)` or download first.

### Keep `phel` for the shell session

```bash
source <(curl -sL https://phel-lang.org/get)
phel repl
phel run src/main.phel
```

Only `phel` goes on `PATH`. Your system `php`, if you have one, is untouched.

### Options

| Variable       | Default                      | Effect                                |
|----------------|------------------------------|---------------------------------------|
| `PHEL_VERSION` | `latest`                     | Pin a Phel release, e.g. `0.50.0`     |
| `PHP_VERSION`  | `8.5.8`                      | Pin a static PHP build                |
| `PREFIX`       | See below                    | Where everything is downloaded        |
| `PHEL_FORCE`   | `0`                          | `1` re-downloads instead of reusing   |

```bash
PHEL_VERSION=0.50.0 PHP_VERSION=8.4.18 bash <(curl -sL https://phel-lang.org/get) repl
```

> `PHP_VERSION` must name a build static-php-cli has actually published, and they lag upstream PHP. Check the [listing](https://dl.static-php.dev/static-php-cli/common/). Overriding it skips checksum verification, since only the default build has a pinned sum.

When `TMPDIR` is set, `PREFIX` defaults to `${TMPDIR%/}/phel-quick`. Otherwise it defaults to `/tmp/phel-quick-$UID`, keeping caches belonging to different users separate. The cache directory is restricted to its owner. `latest` is resolved on each launch and cached under its concrete Phel version, so a newly released version is picked up without `PHEL_FORCE=1`. If the release cannot be resolved, the newest concrete version already in the cache is used.

Connections time out after 10 seconds. Artifact downloads are retried if they remain below 1 KiB/s for 30 seconds, and partial downloads are removed if the transfer ultimately fails.

### Read it before you run it

The command runs a remote script on your machine. Read it first:

```bash
curl -sL https://phel-lang.org/get | less
```

The source is in the [website repo](https://github.com/phel-lang/phel-lang.org/blob/master/static/get). Everything is fetched over HTTPS, and the PHP tarball is checked against a pinned hash. The Phel PHAR has no published checksum yet, so the script verifies it by running it rather than by hash.

### Differences from a normal install

- **No readline.** The static build ships without it, so `phel doctor` reports `readline extension: FAIL` and the REPL has no history or arrow keys. Install `rlwrap` (`brew install rlwrap`, `apt install rlwrap`) and the wrapper uses it automatically for `repl`.
- **No OPcache.** `phel doctor` says so too. Repeat runs recompile every time.
- **Temporary by design.** The folder goes away on reboot. Re-run the command to get it back.
- **Fixed extension set.** No PECL, no `php.ini` to edit. `json`, `mbstring`, `curl`, `openssl`, `gd`, `gmp`, `bcmath`, `pdo_mysql`, `pdo_pgsql`, `pdo_sqlite`, `redis`, `soap`, `sockets` and `zip` are compiled in. If your code needs anything else, use a normal install.
- **No Windows.** static-php-cli publishes no Windows builds. Use WSL or [Docker](#docker-no-php-required).

Everything Phel itself does works the same: `repl`, `run`, `test`, `eval`, `build`, `fmt`.

Remove it when you are done:

```bash
rm -rf "${TMPDIR:-/tmp}/phel-quick"*
```

### Community alternative

[clojure.cc](https://clojure.cc/try/#quick-dialect-usage) by [@ingydotnet](https://github.com/ingydotnet) launches Phel the same way, alongside a dozen other Lisp dialects:

```bash
source <(curl -sL clojure.cc/get) phel && phel
```

It also needs `make` and `git`, and it is maintained outside the Phel project.

## Docker (no PHP required)

No PHP on your machine? With Docker you run Phel in one command.

### Zero-setup REPL

Paste this to open a live Phel REPL. No files, no install:

```bash
docker run --rm -it php:8.5-cli sh -c \
  "curl -sL https://phel-lang.org/phar -o /tmp/phel.phar && php /tmp/phel.phar repl"
```

The container downloads the PHAR again on every run. That is fine for a first try but slow for daily use. For a cached setup, see [Persistent `phel` alias](#persistent-phel-alias-backed-by-docker).

### Run a Phel file from your host

Mount the current directory and run any Phel script:

```bash
docker run --rm -it -v "$PWD":/app -w /app php:8.5-cli sh -c \
  "curl -sL https://phel-lang.org/phar -o /tmp/phel.phar && php /tmp/phel.phar run src/main.phel"
```

### Persistent `phel` alias backed by Docker

Download the PHAR once, then alias `phel` so it works like a local install:

```bash
curl -L https://phel-lang.org/phar -o phel.phar

# Add to ~/.zshrc, ~/.bashrc, or run in your shell:
alias phel='docker run --rm -it -v "$PWD":/app -w /app php:8.5-cli php /app/phel.phar'

phel repl
phel run src/main.phel
phel test
```

### Composer project with no local PHP

Use the official `composer` image, which ships PHP and Composer:

```bash
docker run --rm -it -v "$PWD":/app -w /app composer \
  create-project --stability dev phel-lang/cli-skeleton example-app

cd example-app

# Start the REPL
docker run --rm -it -v "$PWD":/app -w /app composer composer repl
```

An alias for daily use:

```bash
alias dcomposer='docker run --rm -it -v "$PWD":/app -w /app composer'
dcomposer composer repl
dcomposer composer test
dcomposer composer dev
```

## Nix

Use Nix for reproducible dev environments. Phel is in nixpkgs: see [phel on search.nixos.org](https://search.nixos.org/packages?channel=unstable&show=phel) or the [package source](https://github.com/NixOS/nixpkgs/blob/master/pkgs/by-name/ph/phel/package.nix).

No Nix yet? Install it with the [Determinate Systems installer](https://determinate.systems/nix-installer/) or the [official installer](https://nixos.org/download).

### Ad-hoc shell

```bash
nix shell nixpkgs#phel
phel repl
```

{% <callout kind="note"> %}
The nixpkgs version can be behind the latest release. Check it with `nix eval nixpkgs#phel.version`. For the newest release, use Composer or the PHAR.
{% </callout> %}

### Project `shell.nix`

Pin PHP and Composer for the whole team:

```nix
{ pkgs ? import <nixpkgs> { } }:

pkgs.mkShell {
  packages = with pkgs; [
    php85
    php85Packages.composer
  ];
}
```

Then run `nix-shell` and use Composer as normal.

## Verify install

Run `doctor` with the method you installed:

```bash
vendor/bin/phel doctor    # Composer
php phel.phar doctor      # PHAR
phel doctor               # Nix / global
```

It checks the PHP extensions Phel needs (`json`, `mbstring`, `readline`), your source and test directories, OPcache, and the cache size, and names anything that is missing.

{% <clojure_note> %}
How the toolchain maps from `lein` and `deps.edn`:

| Clojure                    | Phel                                         |
|----------------------------|----------------------------------------------|
| `deps.edn` / `project.clj` | `composer.json` + `phel-config.php`          |
| `lein new app foo`         | `composer create-project … cli-skeleton foo` |
| `clj` / `lein repl`        | `composer repl` or `phel repl`               |
| `lein test`                | `composer test` or `phel test`               |
| `uberjar`                  | `phel build` (compiles to PHP)               |
| nREPL                      | `phel nrepl` (bencode over TCP)              |

Editors connect through nREPL and LSP. See [Editor Support](/documentation/tooling/editor-support/).
{% </clojure_note> %}

## Upgrading

Moving to a newer Phel? [Upgrading](/documentation/reference/upgrading/) lists what changed in each release, back to 0.37.

## Next steps

- [Getting Started](/documentation/getting-started/): first REPL session, first file, project tour.
- [Editor Support](/documentation/tooling/editor-support/): set up Emacs, VS Code, PhpStorm, or Vim.
- [Configuration](/documentation/reference/configuration/): tune `phel-config.php`.
