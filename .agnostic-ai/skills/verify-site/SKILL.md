---
name: verify-site
description: Run the full phel-lang.org gate before a push, then confirm the deploy is live. Trigger on "verify", "check the site", "run the gate", or before pushing content, template or build changes.
x-claude:
  model: sonnet
---

# Verify the site

Run once per change set, in this order. Stop at the first failure, fix it, and rerun from that step.

## Gate

1. Generated inputs: `composer build` (API, search, releases, error reference) and `npm run build-css-prod`.
2. `zola build`, then `zola check --skip-external-links`. Orphan warnings for the two legal pages are expected.
3. `npm run check:links`: every absolute internal link and `#anchor`. Must report 0 broken.
4. `php build/run-doc-snippets.php`: every ```phel block against the runtime, about 5 minutes. Must report no regressions. Pass paths to run only the pages you changed while iterating.
5. When `build/` changed: `vendor/bin/phpunit` and `composer phpstan`.
6. When templates or CSS changed: look at the affected pages in light and dark, desktop and 390px wide, and check there is no horizontal scroll. Local screenshots need `base_url` pointed at the local server, because asset URLs are absolute; restore `config.toml` before committing.

Also scan the diff for em or en dashes and for deprecated Phel syntax (`{:name n}` destructuring, `phel\string`, `php/new`).

## After the push

- Deploys queue and take about 40 minutes. Watch the `main.yml` run whose `headSha` is the pushed commit: the newest run in the list may belong to an earlier push.
- Then fetch a changed page from https://phel-lang.org with a cache-busting query and check for the new markup. A green run alone does not prove the page is live.
