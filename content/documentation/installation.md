+++
title = "Installation"
weight = 3
description = "Install Phel with Composer, the PHAR, a one-line script, Docker, or Nix, then check your setup with phel doctor."
+++

All methods need **PHP 8.5+**, except [Quick launch](#quick-launch-no-php-no-docker) and Docker, which bring their own PHP.

## Which method?

| Goal                               | Use                                               |
|------------------------------------|---------------------------------------------------|
| New project with tests + scripts   | [Getting Started](/documentation/getting-started/) |
| Add to existing Composer project   | [Composer require](#composer-recommended)          |
| Run a single file, no setup        | [PHAR](#phar-no-project-setup)                    |
| **No PHP**, no Docker              | [Quick launch](#quick-launch-no-php-no-docker)    |
| **No PHP**, Docker available       | [Docker](#docker-no-php-required)                 |
| Reproducible dev shells            | [Nix](#nix)                                       |

## Composer (recommended)

To start a new project from the skeleton, follow [Getting Started](/documentation/getting-started/). To add Phel to an existing project:

```bash
composer require phel-lang/phel-lang
vendor/bin/phel init my-app    # scaffold phel-config.php + src/
```

Then run every command as `vendor/bin/phel <cmd>`, for example `vendor/bin/phel repl`. `phel init` has more layouts and templates: see [CLI Commands](/documentation/reference/cli-commands/).

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

One command, no PHP and no container. Linux and macOS, x86_64 and arm64.

```bash
bash <(curl -sL https://phel-lang.org/get) repl
```

It downloads a [static PHP build](https://github.com/crazywhalecc/static-php-cli) and the Phel PHAR into a private temporary folder, checks the PHP download against a pinned SHA-256, and runs your command. Nothing needs root. Later runs reuse the download.

Do not pipe it into bash (`curl ... | bash`): bash takes over stdin and the REPL exits at once. On other shells, download the script first with `curl -sL https://phel-lang.org/get -o /tmp/phel-get`, then run `bash /tmp/phel-get repl`.

To keep `phel` on `PATH` for the shell session:

```bash
source <(curl -sL https://phel-lang.org/get)
phel run src/main.phel
```

| Variable | Default | Effect |
|---|---|---|
| `PHEL_VERSION` | `latest` | Pin a Phel release, e.g. `0.50.0` |
| `PHP_VERSION` | `8.5.8` | Pin a [static PHP build](https://dl.static-php.dev/static-php-cli/common/). Only the default has a pinned checksum |
| `PREFIX` | `${TMPDIR}/phel-quick`, or `/tmp/phel-quick-$UID` | Where everything is downloaded |
| `PHEL_FORCE` | `0` | `1` downloads again instead of reusing |

Differences from a normal install:

- **No readline**, so the REPL has no history or arrow keys. Install `rlwrap` and the script uses it.
- **No OPcache**, so each run compiles again.
- **Fixed extensions:** `json`, `mbstring`, `curl`, `openssl`, `gd`, `gmp`, `bcmath`, `pdo_mysql`, `pdo_pgsql`, `pdo_sqlite`, `redis`, `soap`, `sockets` and `zip`. No PECL, no `php.ini`.
- **No Windows.** Use WSL or [Docker](#docker-no-php-required).

The script runs on your machine, so [read it first](https://github.com/phel-lang/phel-lang.org/blob/master/static/get). Remove everything with `rm -rf "${TMPDIR:-/tmp}/phel-quick"*`.

## Docker (no PHP required)

Open a live Phel REPL with no files and no install:

```bash
docker run --rm -it php:8.5-cli sh -c \
  "curl -sL https://phel-lang.org/phar -o /tmp/phel.phar && php /tmp/phel.phar repl"
```

The container downloads the PHAR again on every run, so this suits a first try, not daily use.

For a Composer project, use the official `composer` image, which ships PHP and Composer:

```bash
docker run --rm -it -v "$PWD":/app -w /app composer \
  create-project --stability dev phel-lang/cli-skeleton example-app

cd example-app

# Start the REPL
docker run --rm -it -v "$PWD":/app -w /app composer composer repl
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
