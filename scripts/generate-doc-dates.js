#!/usr/bin/env node
/**
 * Generate data/doc-dates.json: the last commit date of every documentation
 * page, for the "Last updated" line and the TechArticle dateModified.
 *
 * Reads:  content/documentation/**.md (git history)
 * Writes: data/doc-dates.json, keyed by path relative to content/
 *         (the same shape as Zola's `page.relative_path`)
 *
 * The generated API reference is skipped: it is rebuilt on every deploy, so
 * its commit date says nothing. Files with no commit yet are left out, and
 * templates load the map with `required=false`, so a missing entry or file
 * only hides the date. CI must check out full history (fetch-depth: 0), or
 * every page reports the checkout commit's date.
 */

const fs = require('node:fs');
const path = require('node:path');
const { execFileSync } = require('node:child_process');

const ROOT = path.resolve(__dirname, '..');
const CONTENT = path.join(ROOT, 'content');
const DOCS = path.join(CONTENT, 'documentation');
const SKIP = path.join(DOCS, 'reference', 'api');
const OUT = path.join(ROOT, 'data/doc-dates.json');

function markdownFiles(dir) {
  if (dir === SKIP) return [];
  return fs.readdirSync(dir, { withFileTypes: true }).flatMap(entry => {
    const abs = path.join(dir, entry.name);
    if (entry.isDirectory()) return markdownFiles(abs);
    return entry.name.endsWith('.md') ? [abs] : [];
  });
}

// Outside a git checkout this yields no dates rather than failing the build.
function lastCommitDate(abs) {
  try {
    return execFileSync('git', ['log', '-1', '--format=%cs', '--', abs], {
      cwd: ROOT,
      encoding: 'utf8',
      stdio: ['ignore', 'pipe', 'ignore'],
    }).trim();
  } catch {
    return '';
  }
}

function main() {
  const dates = {};
  for (const abs of markdownFiles(DOCS).sort()) {
    const date = lastCommitDate(abs);
    if (date) dates[path.relative(CONTENT, abs).split(path.sep).join('/')] = date;
  }

  fs.mkdirSync(path.dirname(OUT), { recursive: true });
  fs.writeFileSync(OUT, JSON.stringify(dates, null, 2) + '\n');
  console.log(`Wrote ${OUT} (${Object.keys(dates).length} pages)`);
}

main();
