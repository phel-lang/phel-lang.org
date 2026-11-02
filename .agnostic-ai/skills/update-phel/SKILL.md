---
name: update-phel
description: Use to bump phel-lang/phel-lang to a new release in this site repo. Triggers on "update phel", "bump phel", "upgrade phel-lang", "new phel release".
x-claude:
  model: sonnet
---

# Update phel-lang

`composer.json` pins `phel-lang/phel-lang`. A `post-update-cmd` runs `build/update-phel-version.php`, which rewrites `phel_version` in `config.toml`. Never edit `config.toml` by hand.

1. Find the versions: `composer show phel-lang/phel-lang | grep -E "^versions"` (installed is marked `*`) and `composer show phel-lang/phel-lang --all | grep -E "^versions" | head -1` (latest stable is the first non-`dev-*` entry).
2. Set the `require` constraint in `composer.json` to a caret on the minor (`^0.40`). Phel is pre-1.0, so a minor can break; read the release notes at `https://github.com/phel-lang/phel-lang/releases/tag/vX.Y.Z` if anything fails.
3. `composer update phel-lang/phel-lang --with-dependencies`. If `config.toml` did not change, the hook failed: find out why before committing.
4. Verify: `git diff config.toml` shows the bump; `composer test`; `composer test:snippets`; `composer build && zola build && npm run check:links`.
5. Fix stale version strings in prose (snippet runs skip them):
   `grep -rnE 'phel-lang/phel-lang[":]+ *\^?0\.[0-9]+|var-dumper' content/`
   - `content/documentation/installation.md`: the `## Upgrading` section and any `composer require phel-lang/phel-lang:^X.Y`.
   - `content/documentation/reference/upgrading.md`: add a section for the new minor with its breaking changes, newest first.
   - `content/documentation/guides/debugging.md` (`### PHP dump functions`): the `symfony/var-dumper` constraint matches this repo's `composer.json`.
6. Quick-launch script `static/get` (served at `https://phel-lang.org/get`): `PHEL_VERSION` defaults to `latest`, so a normal bump needs no edit. Edit it only when the release raises the minimum PHP:
   - Check the static build exists for all four platforms (`{linux,macos}-{x86_64,aarch64}`) at `https://dl.static-php.dev/static-php-cli/common/php-<new>-cli-<platform>.tar.gz`; it lags upstream PHP.
   - Update `_PHEL_GET_PHP_DEFAULT` and the four SHA-256s in `_phel_get_sha256` (`curl -sL <url> | shasum -a 256`).
   - Update the `PHP_VERSION` default in the Options table of `content/documentation/installation.md`.
7. Commit `chore: bump phel-lang to X.Y.Z`. A clean bump stages `composer.json`, `composer.lock`, `config.toml`. Real fallout goes in the same commit or a follow-up `docs:`/`test:` commit: `build/tests/php/ApiGenerator/Integration/ApiJsonFileTest.php` (`$expectedNamespaces` and its count, when namespaces are added), regenerated `content/documentation/reference/api/*.md` (`composer build`), `content/**` and `build/doc-snippets-baseline.json`.

## When tests fail

- Look at `build/tests/`: usually `VersionUpdater` or API page generation. `composer build` regenerates the API artifacts.
- A bug in Phel itself: do not patch the site. Open an issue upstream and pin the prior version.

## When snippets break

`composer test:snippets` runs each ```phel block of `content/` in its own `phel run` process. The baseline `build/doc-snippets-baseline.json` is empty, so any broken block fails. For each reported `file:line  [error]`:

- Phel changed a name or output: update the snippet and its `; =>` comment to the real output (vectors print `[1 2]`, strings keep quotes).
- The block cannot run standalone (placeholder template, REPL transcript, intentional error, server context): put `<!-- phel-test: skip -->` on its own line above the fence. Never use a ```phel skip fence string: it breaks highlighting.

Repeat until `Failed: 0`. One page: `php build/run-doc-snippets.php content/path/to/file.md --verbose`. Keep the baseline empty; `--update-baseline` only to accept a known failure on purpose.
