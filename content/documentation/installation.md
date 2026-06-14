+++
title = "Installation"
weight = 2
description = "Install Phel via Composer, PHAR, Docker, or Nix, then verify with phel doctor."
+++

Requires **PHP 8.4+**. Pick the method matching your workflow.

## Which method?

| Goal                               | Use                                               |
|------------------------------------|---------------------------------------------------|
| New project with tests + scripts   | [Composer skeleton](#new-project-from-skeleton)   |
| Add to existing Composer project   | [Composer require](#add-to-an-existing-project)   |
| Run a single file, no setup        | [PHAR](#phar-no-project-setup)                    |
| **No PHP installed** (Docker only) | [Docker](#docker-no-php-required)                 |
| Reproducible dev shells            | [Nix](#nix)                                       |
| Fastest path                       | [Getting Started](/documentation/getting-started) |

## Composer (recommended)

### New project from skeleton

Ships with tests, build config, ready-to-use `composer` scripts (`repl`, `dev`, `test`, `build`, `format`).

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

All commands then via `vendor/bin/phel <cmd>` (e.g. `vendor/bin/phel repl`).

<details class="dev-note dev-note--php">
<summary>
  <span class="dev-note__label">PHP</span>
  <span class="dev-note__title">Does this replace my PHP app?</span>
  <span class="dev-note__chevron">›</span>
</summary>
<div class="dev-note__content">

No. Phel lives alongside PHP. `require 'vendor/autoload.php'` and call compiled Phel namespaces from PHP, or call PHP from Phel. Drop into any Composer project (Laravel, Symfony, WordPress plugin) and use where Lisp fits better.

</div>
</details>

## PHAR (no project setup)

Run without Composer. Good for quick experiments, CI one-shots, trying the language.

```bash
curl -L https://phel-lang.org/phar -o phel.phar
php phel.phar --version
```

Every command works the same:

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

## Docker (no PHP required)

No PHP installed? With Docker, run Phel in one command.

### Zero-setup REPL

Paste and you're in a live Phel REPL. No files, no install:

```bash
docker run --rm -it php:8.4-cli sh -c \
  "curl -sL https://phel-lang.org/phar -o /tmp/phel.phar && php /tmp/phel.phar repl"
```

Container downloads PHAR fresh each run. Fine for experimenting, wasteful for daily use. See [Persistent `phel` alias](#persistent-phel-alias-backed-by-docker) for a cached setup.

### Run a Phel file from your host

Mount cwd, run any Phel script:

```bash
docker run --rm -it -v "$PWD":/app -w /app php:8.4-cli sh -c \
  "curl -sL https://phel-lang.org/phar -o /tmp/phel.phar && php /tmp/phel.phar run src/main.phel"
```

### Persistent `phel` alias backed by Docker

Download PHAR once, make `phel` feel native:

```bash
curl -L https://phel-lang.org/phar -o phel.phar

# Add to ~/.zshrc, ~/.bashrc, or run in your shell:
alias phel='docker run --rm -it -v "$PWD":/app -w /app php:8.4-cli php /app/phel.phar'

phel repl
phel run src/main.phel
phel test
```

### Composer project with no local PHP

Use official `composer` image (ships PHP + Composer):

```bash
docker run --rm -it -v "$PWD":/app -w /app composer \
  create-project --stability dev phel-lang/cli-skeleton example-app

cd example-app

# Start the REPL
docker run --rm -it -v "$PWD":/app -w /app -p 2345:2345 composer composer repl
```

Alias for daily use:

```bash
alias dcomposer='docker run --rm -it -v "$PWD":/app -w /app composer'
dcomposer composer repl
dcomposer composer test
dcomposer composer dev
```

> `-p 2345:2345` exposes default nREPL port for host editor integration. Omit if not needed.

## Nix

Reproducible dev environments. Phel is in nixpkgs: see [phel on search.nixos.org](https://search.nixos.org/packages?channel=unstable&show=phel) or the [package source](https://github.com/NixOS/nixpkgs/blob/master/pkgs/by-name/ph/phel/package.nix).

No Nix yet? Install via [Determinate Systems installer](https://determinate.systems/nix-installer/) or [official installer](https://nixos.org/download).

### Ad-hoc shell

```bash
nix shell nixpkgs#phel
phel repl
```

> Nixpkgs may lag latest. Check `nix eval nixpkgs#phel.version`. For newest, use Composer or PHAR.

### Project `shell.nix`

Pin PHP + Composer for the team:

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

## Verify install

Run the doctor:

```bash
vendor/bin/phel doctor    ; Composer
php phel.phar doctor      ; PHAR
phel doctor               ; Nix / global
```

Checks PHP extensions (`json`, `mbstring`, `readline`), writable cache dir, source layout. Tells you exactly what's missing.

<details class="dev-note dev-note--clojure">
<summary>
  <span class="dev-note__label">Clojure</span>
  <span class="dev-note__title">Mental model for the toolchain</span>
  <span class="dev-note__chevron">›</span>
</summary>
<div class="dev-note__content">

Mapping from `lein`/`deps.edn`:

| Clojure                    | Phel                                         |
|----------------------------|----------------------------------------------|
| `deps.edn` / `project.clj` | `composer.json` + `phel-config.php`          |
| `lein new app foo`         | `composer create-project … cli-skeleton foo` |
| `clj` / `lein repl`        | `composer repl` or `phel repl`               |
| `lein test`                | `composer test` or `phel test`               |
| `uberjar`                  | `phel build` (compiles to PHP)               |
| nREPL                      | `phel nrepl` (bencode over TCP)              |

Editor integration: nREPL + LSP. See [Editor Support](/documentation/tooling/editor-support).

</div>
</details>

## Upgrading to 0.44

```bash
composer require phel-lang/phel-lang:^0.44
./vendor/bin/phel cache:clear        # or: rm -rf .phel/cache
```

Always clear the cache after upgrading: compiled PHP from earlier installs references renamed core types and fails to load otherwise. Rebuild downstream projects too.

Behaviour changes in 0.44:

- Requires `gacela-project/gacela: ^1.15`. Editing `phel-config.php` takes effect immediately again (the stale merged-config cache is cleared on change).
- `phel test` exit codes are stricter: it no longer exits `0` when nothing ran, bad paths/selectors fail loudly, and `--list` no longer appends a false `No tests matched`. CI relying on the old lenient codes may start failing as intended.
- `await-all` (and `pmap`, built on it) now return results in input order instead of completion order. Code that tolerated shuffled concurrent results sees deterministic ordering now.
- The docs doctest harness (`composer test-docs`, `tests/doctest/`) was removed; user-facing guides now live on [phel-lang.org](https://phel-lang.org/documentation/).

New in 0.44 (config tooling + sharper test runner): `phel config` prints the merged config with provenance, `phel test --coverage` and `--watch`, `phel build --report`, `phel init --template=<name>`, optimization levels (`phel build -O <level>`), LSP PHP interop, and a REPL reload workflow (`(reload!)`, `(run-tests ...)`). See the [0.44 release notes](/releases/0-44-feedback-loop/).

Behaviour changes in 0.43:

- A `never` / `void` / `null` `:tag` return on a value-returning function is now a compile error instead of a load-time fatal (`mixed`, `?T`, and union/intersection tags still pass).

New in 0.43 (typed PHP interop): `php/callable` first-class callables, `defstruct ^:php/readonly` fields, `defenum` methods + interfaces, `^:php/override` (`#[\Override]`), and `definterface` typed class constants. See the [0.43 release notes](/releases/0-43-first-class-callable/).

Behaviour changes in 0.42:

- Structs print with a `.` separator instead of `\` (e.g. `(my.ns.point 1 2)`). Snapshot tests or code that parses struct output must match the new form.
- `str/index-of` returns `nil` for an empty search string instead of throwing a PHP `ValueError`.
- Lexer columns are counted in code points, so error locations in multibyte source point at the right column.
- `if-let`, `when-let`, `if-some`, `when-first` are now hygienic: a user binding named like the macros' internal temporary no longer collides.

New in 0.42 (richer typed PHP interop, all opt-in):

- `phel.reflect`: read PHP 8 attributes (`class-attributes` / `method-attributes` / ...) and bridge native enums (`enum->keyword` / `keyword->enum` / `enum-values`).
- `defenum` native backed enums and `defexception` with an optional parent class.
- `php/ref` passes a local by reference into `php/->` / `php/::` and plain PHP calls like `preg_match` / `sort`.
- `hydrate` / `bean` bridge a Phel map and a typed PHP object both ways.
- PHP 8 named arguments in `php/new` / `php/->` / `php/::` via the `:&` marker, e.g. `(php/new \App\Mailer :& :host "smtp")`.
- `iterator-seq` builds a lazy seq over any PHP `Traversable`.
- `defstruct` `:php` blocks declare inline PHP magic methods; `phel format` and `phel.http` JSON bodies / response builders round it out.

See the [0.42 release notes](/releases/0-42-life-everything/) for the full list.

Breaking changes in 0.41:

- Stricter argument errors: `take` with a non-int count, `remove` / `select-keys` on a non-seqable, `int` / `long` / `float` / `double` on non-numeric values, and `get` / `assoc` / `update` with non-int keys now raise clean Phel errors instead of leaking a PHP `TypeError`. Code that leaned on silent coercion must pass real values.
- Clojure-aligned laziness: `map`, `filter`, `remove`, `concat`, `distinct`, and `repeatedly` no longer realize their head eagerly, `map` over `nil` returns a lazy seq, and `LazySeq` no longer drops `nil` values. Force with `doall` or `vec` where you relied on eager evaluation.

Breaking changes in 0.40:

- `phel agent-install`: the `.agents/` docs tree is now copied by default. The `--with-docs` flag is gone; use `--no-docs` to opt out.
- Map destructuring with `:keys` / `:strs` / `:syms` and a non-vector value now reports a shape error instead of silently dropping the binding.

Breaking changes in 0.39 (Clojure-aligned core type renames):

- `Variable` → `Atom`
- `Uuid` → `UUID`
- `BigInteger` → `BigInt`
- `Rational` → `Ratio`
- `PhelFuture` → `Future`
- `ExInfoException` → `ExceptionInfo`
- `LazyCons` → `Cons`
- Auto-refer: common `Phel\Lang\*` types resolve without `(:use ...)`. `Interface` suffix dropped (e.g. `(php/instanceof x LazySeq)`). User `(:use ...)` still overrides.

Earlier upgrades (0.37):

- `PhelConfig` setters replaced by immutable `withX()` chain; old `setX()` shims emit deprecation notices. See [Configuration](/documentation/configuration/).
- `PhelConfig::forProject(ProjectLayout $layout = Flat, string $mainNamespace = '')`: layout argument is first, `Flat` is the default.
- `Phel\Printer` moved to `Phel\Shared\Printer`. Phel sources should `(:use Phel.Shared.Printer.Printer)`; the old path no longer resolves.
- Cross-module exceptions + `CodeSnippet` moved to `Phel\Shared\Exceptions` / `Phel\Shared\Parser\ReadModel`.
- Runtime state (cache, REPL history, error log) now lives under `.phel/`. Override via `withPhelDir('...')` or the `PHEL_DIR` env var.

## Next steps

- [Getting Started](/documentation/getting-started): first REPL session, project tour.
- [Editor Support](/documentation/tooling/editor-support): Emacs, VS Code, IntelliJ, Vim.
- [CLI Commands](/documentation/tooling/cli-commands): every subcommand.
- [Configuration](/documentation/configuration): `phel-config.php` options.
