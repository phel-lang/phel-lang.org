#!/usr/bin/env node
/**
 * Check every internal link and #anchor in the built site.
 *
 * Reads:  public/**.html (run `zola build` first), base_url from config.toml
 * Exits:  1 when a link points at a missing page, file or anchor
 *
 * `zola check` only follows `@/` links, and this site writes absolute
 * `/documentation/...` URLs, so broken paths and anchors slip past it. Alias
 * redirect pages are skipped as sources: their only link is the target.
 */

const fs = require('node:fs');
const path = require('node:path');

const ROOT = path.resolve(__dirname, '..');
const PUBLIC = path.join(ROOT, 'public');

function baseUrl() {
  const match = fs.readFileSync(path.join(ROOT, 'config.toml'), 'utf8').match(/^base_url\s*=\s*"([^"]+)"/m);
  return match ? match[1].replace(/\/$/, '') : '';
}

function htmlFiles(dir) {
  return fs.readdirSync(dir, { withFileTypes: true }).flatMap((entry) => {
    const abs = path.join(dir, entry.name);
    if (entry.isDirectory()) return htmlFiles(abs);
    return entry.name.endsWith('.html') ? [abs] : [];
  });
}

function decode(href) {
  return href.replace(/&amp;/g, '&').replace(/&#x2F;/g, '/').replace(/&quot;/g, '"');
}

function main() {
  if (!fs.existsSync(PUBLIC)) {
    console.error('public/ is missing: run `zola build` first.');
    process.exit(1);
  }

  const base = baseUrl();
  const idsByUrl = new Map();
  const links = [];

  for (const file of htmlFiles(PUBLIC)) {
    const html = fs.readFileSync(file, 'utf8');
    const url = '/' + path.relative(PUBLIC, file).split(path.sep).join('/').replace(/index\.html$/, '');
    idsByUrl.set(url, new Set([...html.matchAll(/\sid="([^"]+)"/g)].map((m) => m[1])));
    if (html.includes('http-equiv="refresh"')) continue;
    for (const [, raw] of html.matchAll(/href="([^"]+)"/g)) {
      const href = decode(raw).replace(base, '');
      if (href.startsWith('/') && !href.startsWith('//')) links.push([url, href]);
    }
  }

  const broken = new Map();
  for (const [source, href] of links) {
    const [rawPath, fragment = ''] = href.split('#');
    let target = rawPath.split('?')[0] || source;
    if (!target.endsWith('/') && !path.posix.basename(target).includes('.')) target += '/';

    let problem = null;
    if (target.endsWith('/')) {
      if (!idsByUrl.has(target)) problem = 'missing page';
      else if (fragment && !idsByUrl.get(target).has(decodeURIComponent(fragment))) problem = 'missing anchor';
    } else if (!fs.existsSync(path.join(PUBLIC, target))) {
      problem = 'missing file';
    }

    if (problem) {
      const key = `${href}  (${problem})`;
      if (!broken.has(key)) broken.set(key, source);
    }
  }

  for (const [key, source] of broken) console.log(`${key}  first seen on ${source}`);
  console.log(`Checked ${links.length} internal links, ${broken.size} broken.`);
  process.exit(broken.size > 0 ? 1 : 0);
}

main();
