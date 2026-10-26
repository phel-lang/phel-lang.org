+++
title = "Installation"
weight = 3
description = "Install Phel with Composer, the PHAR, Docker, or Nix, then check your setup with phel doctor."
+++

All methods need **PHP 8.5+**, except Docker, which brings its own PHP.

## Which method?

| Goal                               | Use                                               |
|------------------------------------|---------------------------------------------------|
| New project with tests + scripts   | [Getting Started](/documentation/getting-started/) |
| Add to existing Composer project   | [Composer require](#composer-recommended)          |
| Run a single file, no setup        | [PHAR](#phar-no-project-setup)                    |
| **No PHP installed** (Docker only) | [Docker](#docker-no-php-required)                 |
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
