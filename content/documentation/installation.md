+++
title = "Installation"
weight = 1
+++

Phel requires PHP 8.3+. Choose the installation method that works best for you:

## Composer

**Recommended for most projects.** Create a new project:

```bash
composer create-project --stability dev phel-lang/cli-skeleton example-app
cd example-app
composer repl
```

Or add Phel to an existing project:

```bash
composer require phel-lang/phel-lang
```

Commands are available through `vendor/bin/phel`.

## PHAR

**No project setup needed.** Download the pre-built PHAR from the
[latest GitHub release](https://github.com/phel-lang/phel-lang/releases):

```bash
curl -L https://phel-lang.org/phar -o phel.phar
php phel.phar --version
```

Use it exactly like the Composer-installed binary:

```bash
php phel.phar repl
php phel.phar run src/main.phel
php phel.phar test --filter foo
```

## Nix

**For Nix users.** Quickly try Phel without installing it globally:

```bash
nix shell nixpkgs#phel
phel repl
```

For a repeatable development environment, create a `shell.nix` in your project:

```nix
{ pkgs ? import <nixpkgs> { } }:

pkgs.mkShell {
  packages = with pkgs; [
    php83
    php83Packages.composer
  ];
}
```

Then run `nix-shell` and use Composer as normal.
