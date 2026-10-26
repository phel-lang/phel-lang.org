---
name: website
description: Build, check and deploy the Zola site, and which files are generated.
---

# Website

## Build and check

- `composer install` and `npm ci` first.
- `composer build`: generates the API reference, search index, release pages and error reference.
- `npm run build-css-prod`: builds `static/tailwind.css` from `css/`.
- Build with Zola 0.23.6, the version CI and deploy pin.
- Content is a Tera template. Components live in `templates/components/`: `{{ <name arg="x" /> }}` inline, `{% <name arg="x"> %}body{% </name> %}` with a body. Wrap a literal `{{`, `{%` or `{#` in content in `{% raw %}...{% endraw %}`, around the whole fence for a code block. Write heading ids as `{ #id }`: `{#id}` opens a Tera comment. Generated pages skip templating (`skip_content_templating` in `config.toml`).
- Phel highlighting is `syntaxes/phel.tmLanguage.json`, the only grammar Zola loads for ```phel blocks. When Phel ships new reader syntax or special forms, update it from `docs/spec/language-surface.md` in phel-lang/phel-lang. Keep the scopes on colors the themes already have, so the class numbers below stay the same.
- Syntax colors come from `syntaxes/phel-light.theme.json` and `syntaxes/phel-dark.theme.json`. After changing a theme, copy the rules of `public/giallo-light.css` and `public/giallo-dark.css` into `static/syntax-theme-light.css` and `static/syntax-theme-dark.css` (dark ones under `.dark`).
- `zola build`, then `zola check --skip-external-links`, then `npm run check:links`. Zola only checks `@/` links; `check:links` checks every absolute internal link and `#anchor` in `public/`.
- `php build/run-doc-snippets.php [path ...]`: runs every ```phel block in `content/` against the real runtime. It must report no regressions. Mark a block that cannot run standalone with `<!-- phel-test: skip -->` on the line above the fence.
- `vendor/bin/phpunit` and `composer phpstan` for the PHP in `build/`.
- The `verify-site` skill runs the whole gate in order. Run it once per change set, not after each edit.

## Generated, never edit by hand

- `content/documentation/reference/api/`, `static/api.json`, `static/api_search.json`: from Phel docstrings (`build/api-*.php`).
- `content/documentation/reference/errors.md`: from `build/src/ErrorReference/`.
- `content/releases/*` except `_index.md`: from GitHub releases.
- `static/tailwind.css`, `static/llms-full.txt`, `static/agentic-coding.md`, `data/doc-dates.json`: from `npm run build-css-prod`.

## Links

- Internal links are absolute: `/documentation/<section>/<page>/`.
- When a page moves or merges, add its old URL to the `aliases` list in the front matter of the page that now holds the content. One `aliases` key per file.
- Update `static/llms.txt` when the docs structure changes.

## Deploy

- A push to `master` runs `.github/workflows/main.yml`, which uploads `public/` over FTP. Runs queue one at a time and take about 40 minutes.
- After a push, confirm the new content is live on https://phel-lang.org, not only that the workflow went green.
- Stylesheets and scripts are linked with `get_url(..., cachebust=true)`. Keep that for any new asset.
