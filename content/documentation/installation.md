+++
title = "Installation"
weight = 1
+++

Phel targets PHP 8.3+, so make sure your environment ships with a compatible PHP
runtime and [Composer](https://getcomposer.org/). Choose the setup that fits
your workflow:

## Composer

For a ready-to-run starter project:

```bash
composer create-project --stability dev phel-lang/cli-skeleton example-app
cd example-app
composer repl
```

Prefer to wire Phel into an existing codebase? Pull it in as a dependency:

```bash
composer require phel-lang/phel-lang
```

Then add a source directory and start writing `*.phel` files. Commands are
available through `vendor/bin/phel`.

## Standalone PHAR

Try Phel without touching `composer.json`. Download the pre-built PHAR from the
[latest GitHub release](https://github.com/phel-lang/phel-lang/releases):

```bash
curl -L https://github.com/phel-lang/phel-lang/releases/latest/download/phel.phar -o phel.phar
php phel.phar --version
```

Use it exactly like the Composer-installed binary:

```bash
php phel.phar repl
php phel.phar run src/main.phel
php phel.phar test --filter foo
```

## NixOS

Phel needs PHP 8.3+ and Composer. On NixOS you can spin up an environment that
provides both tools without polluting your system profile.

### Install from nixpkgs

`phel` is available in [nixpkgs](https://github.com/NixOS/nixpkgs/blob/master/pkgs/by-name/ph/phel/package.nix),
so you can make it part of your system or user environment:

```nix
{ pkgs, ... }: {
  environment.systemPackages = with pkgs; [ phel ];
}
```

With [Home Manager](https://nix-community.github.io/home-manager/):

```nix
{ pkgs, ... }: {
  home.packages = [
    pkgs.phel
  ];
}
```

To try Phel without persisting it, start an ad-hoc shell:

```bash
nix shell nixpkgs#phel
```

### Ephemeral shell

Run Composer-based workflows inside an ad-hoc shell:

```bash
nix-shell -p php83 php83Packages.composer --run \
  "composer create-project --stability dev phel-lang/cli-skeleton example-app"
```

Inside the spawned shell, `cd example-app` and use commands such as
`composer repl` or `vendor/bin/phel`.

### Project shell.nix

For a repeatable development setup, drop this `shell.nix` into your project and
run `nix-shell`:

```nix
{ pkgs ? import <nixpkgs> { } }:

pkgs.mkShell {
  packages = with pkgs; [
    php83
    php83Packages.composer
  ];
}
```

> Using flakes? Replace the last snippet with a `devShell` definition that pulls
> in the same `php83` and `php83Packages.composer` packages.
