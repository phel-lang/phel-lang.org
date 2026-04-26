#!/usr/bin/env node
/**
 * One-shot migration: split the legacy single-page
 * content/documentation/reference/api.md into a Zola section:
 *
 *   content/documentation/reference/api/
 *   ├── _index.md
 *   └── <namespace-slug>.md
 *
 * Idempotent: cleans the api/ directory if it already exists.
 *
 * Run: `node scripts/split-api-md.js`
 *
 * After this runs once, the PHP generator (build/api-page.php)
 * is the source of truth and produces the same layout.
 */

const fs = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '..');
const LEGACY_FILE = path.join(ROOT, 'content/documentation/reference/api.md');
const OUT_DIR = path.join(ROOT, 'content/documentation/reference/api');

function slug(namespace) {
  return namespace
    .toLowerCase()
    .replace(/[\\/_]/g, '-')
    .replace(/[^a-z0-9-]/g, '-')
    .replace(/-+/g, '-')
    .replace(/^-|-$/g, '');
}

function rmrf(dir) {
  if (!fs.existsSync(dir)) return;
  for (const entry of fs.readdirSync(dir)) {
    const p = path.join(dir, entry);
    const stat = fs.lstatSync(p);
    if (stat.isDirectory()) rmrf(p);
    else fs.unlinkSync(p);
  }
  fs.rmdirSync(dir);
}

function rewriteCrossLinks(body, namespacesByAnchor) {
  // Convert href="#anchor" inside this namespace's body. If anchor belongs
  // to another namespace, rewrite to absolute path.
  return body.replace(
    /href="#([^"]+)"/g,
    (match, anchor) => {
      const targetNs = namespacesByAnchor.get(anchor);
      if (!targetNs || targetNs === currentNamespace) return match;
      return `href="/documentation/reference/api/${slug(targetNs)}/#${anchor}"`;
    },
  );
}

let currentNamespace = '';

function main() {
  if (!fs.existsSync(LEGACY_FILE)) {
    console.error('Legacy api.md not found at', LEGACY_FILE);
    console.error('Either it has already been migrated, or you need to generate it first via the PHP build.');
    process.exit(1);
  }

  const text = fs.readFileSync(LEGACY_FILE, 'utf8');

  // Strip frontmatter and the leading tip block; find first namespace marker.
  const firstNs = text.search(/^##\s+`([^`]+)`/m);
  if (firstNs === -1) {
    console.error('No namespace headings (## `<ns>`) found in', LEGACY_FILE);
    process.exit(1);
  }

  const body = text.slice(firstNs);
  const segments = body.split(/^##\s+`([^`]+)`\s*$/m);
  // segments[0] = "" (before first match)
  // segments[1] = first namespace name
  // segments[2] = first namespace body
  // segments[3] = second namespace name
  // ... etc.

  const groups = [];
  for (let i = 1; i < segments.length; i += 2) {
    const namespace = segments[i].trim();
    const sectionBody = segments[i + 1] || '';
    groups.push({ namespace, body: sectionBody });
  }

  if (groups.length === 0) {
    console.error('No namespace sections parsed from', LEGACY_FILE);
    process.exit(1);
  }

  // Build anchor-to-namespace map for cross-link rewrite.
  const namespacesByAnchor = new Map();
  for (const { namespace, body } of groups) {
    const re = /^###\s+`([^`]+)`/gm;
    let m;
    while ((m = re.exec(body)) !== null) {
      const fnName = m[1];
      const anchor = fnName.toLowerCase().replace(/[^a-z0-9-]/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
      if (!namespacesByAnchor.has(anchor)) namespacesByAnchor.set(anchor, namespace);
    }
  }

  // Prepare output directory.
  rmrf(OUT_DIR);
  fs.mkdirSync(OUT_DIR, { recursive: true });

  // Write _index.md
  const namespacesSorted = [...groups].sort((a, b) => a.namespace.localeCompare(b.namespace));
  const counts = new Map(
    namespacesSorted.map(({ namespace, body }) => [
      namespace,
      (body.match(/^###\s+`/gm) || []).length,
    ]),
  );

  const indexLines = [
    '+++',
    'title = "API"',
    'weight = 110',
    'template = "page-api-index.html"',
    'sort_by = "title"',
    'aliases = ["/api", "/documentation/api"]',
    '+++',
    '',
    '> **Tip:** This documentation is also available in JSON format at [`/api.json`](/api.json).',
    '',
    'Browse the API by namespace:',
    '',
    '<ul class="api-namespace-grid">',
    ...namespacesSorted.map(({ namespace }) => {
      const count = counts.get(namespace) || 0;
      const escaped = namespace.replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
      }[c]));
      return `<li><a href="/documentation/reference/api/${slug(namespace)}/"><span class="api-namespace-grid__name"><code>${escaped}</code></span><span class="api-namespace-grid__count">${count}</span></a></li>`;
    }),
    '</ul>',
    '',
  ];
  fs.writeFileSync(path.join(OUT_DIR, '_index.md'), indexLines.join('\n'));

  // Write each namespace file.
  for (const { namespace, body } of groups) {
    currentNamespace = namespace;
    const cleaned = rewriteCrossLinks(body, namespacesByAnchor).replace(/^\s*---\s*$/m, '');
    const count = counts.get(namespace) || 0;
    const frontmatter = [
      '+++',
      `title = "${namespace.replace(/\\/g, '\\\\').replace(/"/g, '\\"')}"`,
      'template = "page-api-namespace.html"',
      '',
      '[extra]',
      `fn_count = ${count}`,
      `namespace = "${namespace.replace(/\\/g, '\\\\').replace(/"/g, '\\"')}"`,
      '+++',
      '',
    ].join('\n');

    const filename = path.join(OUT_DIR, `${slug(namespace)}.md`);
    fs.writeFileSync(filename, frontmatter + cleaned.trimStart());
  }

  // Remove legacy file.
  fs.unlinkSync(LEGACY_FILE);

  console.log(`Wrote ${groups.length + 1} files into ${OUT_DIR}`);
}

main();
