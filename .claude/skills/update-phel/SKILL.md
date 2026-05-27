---
name: update-phel
description: Bump phel-lang/phel-lang dependency to the latest release in this website repo. Triggers on "update phel", "bump phel", "upgrade phel-lang", "new phel release". Handles composer constraint bump, lock refresh, and verifies the post-update hook regenerated config.toml.
model: sonnet
---

# Update phel-lang to latest

This repo pins `phel-lang/phel-lang` in `composer.json` and mirrors the active version in `config.toml` (used by the Zola site to render the version badge / install snippets). A `post-update-cmd` runs `build/update-phel-version.php` which rewrites `config.toml` from the installed package version - do not edit `config.toml` by hand.

## Procedure

1. **Find current + latest version.**
   ```bash
   composer show phel-lang/phel-lang | grep -E "^versions"
   composer show phel-lang/phel-lang --all | grep -E "^versions" | head -1
   ```
   First command shows installed (marked `*`). Second lists all tags - latest stable is first non-`dev-*` entry.

2. **Bump constraint in `composer.json`.** Edit the `phel-lang/phel-lang` line under `require`. Use caret on the minor (`^0.40`), matching existing style. Phel is pre-1.0 so minor bumps may break - read the changelog if anything fails later.

3. **Update lock + run post-update hook.**
   ```bash
   composer update phel-lang/phel-lang --with-dependencies
   ```
   This pulls the new package, refreshes `composer.lock`, then auto-runs `php build/update-phel-version.php` which rewrites `config.toml` `phel_version = "vX.Y.Z"`. If `config.toml` did NOT change, the hook failed - investigate before committing.

4. **Verify.**
   ```bash
   git diff config.toml          # should show phel_version bump
   composer test                 # 37+ tests, must pass
   ```

5. **Commit.** Use this exact subject (matches prior `chore: bump phel-lang to 0.39.0` convention):
   ```
   chore: bump phel-lang to X.Y.Z
   ```
   Stage exactly: `composer.json`, `composer.lock`, `config.toml`. Do not stage anything else - the bump should be a clean three-file diff. Conventional commits, no Claude trailers (per global instructions).

## Files touched (expected)

- `composer.json` - constraint bump only (1 line)
- `composer.lock` - phel-lang + transitive deps (often symfony/*)
- `config.toml` - `phel_version` rewritten by post-update hook

If any other file changes, something is off - investigate.

## When tests fail after bump

Phel < 1.0: minor releases can rename core fns or change emit output. Check:
- `https://github.com/phel-lang/phel-lang/releases/tag/vX.Y.Z` for breaking changes
- Failing tests in `build/tests/` - usually `VersionUpdater` or API page generation
- Regenerate API artifacts: `composer build` (runs `api-page.php`, `api-search.php`, `api-json.php`, `generate-releases.php`)

If breakage is in Phel itself, do not patch the website to compensate - open an issue upstream and pin to the prior version.
