+++
title = "Installation"
weight = 2
+++

Phel requires **PHP 8.4+**. Pick the install method that matches how you want to work.

## Which Method Should I Use?

| Goal                                        | Use                                                  |
| ------------------------------------------- | ---------------------------------------------------- |
| Start a new project with tests + scripts    | [**Composer skeleton**](#new-project-from-skeleton)  |
| Add Phel to an existing Composer project    | [**Composer require**](#add-to-an-existing-project)  |
| Run a single file or play around (no setup) | [**PHAR**](#phar-no-project-setup)                   |
| **No PHP installed** (just Docker)          | [**Docker**](#docker-no-php-required)                |
| Reproducible dev shells across machines     | [**Nix**](#nix)                                      |
| Just looking for the fastest path           | [Getting Started](/documentation/getting-started) →  |

## Composer (Recommended)

### New project from skeleton

The skeleton comes with tests, build config, and ready-to-use `composer` scripts (`repl`, `dev`, `test`, `build`, `format`).

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

All commands are then available via `vendor/bin/phel <cmd>` (for example `vendor/bin/phel repl`).

<details class="dev-note dev-note--php">
<summary>
  <span class="dev-note__label">PHP</span>
  <span class="dev-note__title">Does this replace my PHP app?</span>
  <span class="dev-note__chevron">›</span>
</summary>
<div class="dev-note__content">

No. Phel lives alongside your PHP code. You can `require 'vendor/autoload.php'` and call compiled Phel namespaces from PHP, or call PHP classes from Phel. Add it to any Composer project (Laravel, Symfony, WordPress plugin, framework-less) and use it where Lisp is a better fit.

</div>
</details>

## PHAR (No Project Setup)

Run Phel without Composer. Good for quick experiments, CI one-shots, or when you just want to try the language.

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

Make it globally available:

```bash
chmod +x phel.phar
sudo mv phel.phar /usr/local/bin/phel
phel repl
```

## Docker (No PHP Required)

Don't have PHP installed (or don't want to)? If you have Docker, you can run Phel in one command.

### Zero-setup REPL

Paste this and you are in a live Phel REPL. No files, no install. Just Docker.

```bash
docker run --rm -it php:8.4-cli sh -c \
  "curl -sL https://phel-lang.org/phar -o /tmp/phel.phar && php /tmp/phel.phar repl"
```

The container downloads the PHAR fresh each run. Fine for experimenting, wasteful for daily use (see below).

### Run a Phel file from your host

Mount the current directory and run any Phel script:

```bash
docker run --rm -it -v "$PWD":/app -w /app php:8.4-cli sh -c \
  "curl -sL https://phel-lang.org/phar -o /tmp/phel.phar && php /tmp/phel.phar run src/main.phel"
```

### Persistent: a `phel` alias backed by Docker

Download the PHAR once, then make `phel` feel native:

```bash
curl -L https://phel-lang.org/phar -o phel.phar

# Add to ~/.zshrc, ~/.bashrc, or run in your shell:
alias phel='docker run --rm -it -v "$PWD":/app -w /app php:8.4-cli php /app/phel.phar'

phel repl
phel run src/main.phel
phel test
```

### Create a Composer project with no local PHP

Use the official `composer` image (ships with PHP + Composer):

```bash
docker run --rm -it -v "$PWD":/app -w /app composer \
  create-project --stability dev phel-lang/cli-skeleton example-app

cd example-app

# Start the REPL
docker run --rm -it -v "$PWD":/app -w /app -p 2345:2345 composer composer repl
```

Make it an alias for daily use:

```bash
alias dcomposer='docker run --rm -it -v "$PWD":/app -w /app composer'
dcomposer composer repl
dcomposer composer test
dcomposer composer dev
```

> The `-p 2345:2345` exposes the default nREPL port if you want editor integration from your host. Omit it if you don't need it.

## Nix

For reproducible dev environments. Phel is packaged in nixpkgs: see [phel on search.nixos.org](https://search.nixos.org/packages?channel=unstable&show=phel) or the [package source](https://github.com/NixOS/nixpkgs/blob/master/pkgs/by-name/ph/phel/package.nix).

If you don't have Nix yet, install via the [Determinate Systems installer](https://determinate.systems/nix-installer/) or the [official installer](https://nixos.org/download).

### Ad-hoc shell

```bash
nix shell nixpkgs#phel
phel repl
```

> Nixpkgs can lag behind the latest release. Check with `nix eval nixpkgs#phel.version`. If you need the newest version, use Composer or the PHAR.

### Project `shell.nix`

Pin PHP + Composer for the whole team:

```nix
{ pkgs ? import <nixpkgs> { } }:

pkgs.mkShell {
  packages = with pkgs; [
    php84
    php84Packages.composer
  ];
}
```

Then `nix-shell` and use Composer as normal.

## Verify Your Install

Whichever method you picked, run the doctor:

```bash
vendor/bin/phel doctor    # Composer install
php phel.phar doctor      # PHAR
phel doctor               # Nix / global
```

It checks PHP extensions (`json`, `mbstring`, `readline`), writable cache directory, and source layout. If anything is missing, it tells you exactly what to install.

<details class="dev-note dev-note--clojure">
<summary>
  <span class="dev-note__label">Clojure</span>
  <span class="dev-note__title">Mental model for the toolchain</span>
  <span class="dev-note__chevron">›</span>
</summary>
<div class="dev-note__content">

Rough mapping for folks coming from `lein`/`deps.edn`:

| Clojure                       | Phel                                |
| ----------------------------- | ----------------------------------- |
| `deps.edn` / `project.clj`    | `composer.json` + `phel-config.php` |
| `lein new app foo`            | `composer create-project … cli-skeleton foo` |
| `clj` / `lein repl`           | `composer repl` or `phel repl`     |
| `lein test`                   | `composer test` or `phel test`     |
| `uberjar`                     | `phel build` (compiles to PHP)     |
| nREPL                         | `phel nrepl` (bencode over TCP)    |

Editor integration works the same way you're used to: nREPL + LSP. See [Editor Support](/documentation/tooling/editor-support).

</div>
</details>

## Next Steps

- [Getting Started](/documentation/getting-started): first REPL session and project tour.
- [Editor Support](/documentation/tooling/editor-support): Emacs, VS Code, IntelliJ, Vim.
- [CLI Commands](/documentation/tooling/cli-commands): every subcommand.
- [Configuration](/documentation/configuration): `phel-config.php` options.
