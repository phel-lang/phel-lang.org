#!/usr/bin/env node
/**
 * Check the house rules of every documentation page.
 *
 * Reads:  content/documentation/**.md (except the generated API reference),
 *         phel_version from config.toml
 * Exits:  1 when a page breaks a rule
 *
 * Rules: a description of 50 to 160 characters, a difficulty on every language
 * page, `; =>` and `; prints` comments, the callout component instead of a
 * blockquote, no Phel release older than the current one outside upgrading.md,
 * a lead paragraph before the first H2, and no H1 in the body.
 */

const fs = require('node:fs');
const path = require('node:path');

const ROOT = path.resolve(__dirname, '..');
const DOCS = path.join(ROOT, 'content', 'documentation');
const GENERATED = path.join(DOCS, 'reference', 'api');

function currentMinor() {
  const match = fs.readFileSync(path.join(ROOT, 'config.toml'), 'utf8').match(/^phel_version\s*=\s*"v?0\.(\d+)\.\d+"/m);
  if (!match) throw new Error('config.toml has no phel_version');
  return Number(match[1]);
}

function markdownFiles(dir) {
  return fs.readdirSync(dir, { withFileTypes: true }).flatMap((entry) => {
    const abs = path.join(dir, entry.name);
    if (abs === GENERATED) return [];
    if (entry.isDirectory()) return markdownFiles(abs);
    return entry.name.endsWith('.md') ? [abs] : [];
  });
}

function split(source) {
  const match = source.match(/^\+\+\+\r?\n([\s\S]*?)\r?\n\+\+\+(?:\r?\n|$)/);
  if (!match) return { frontMatter: '', body: source, bodyStart: 1 };
  return { frontMatter: match[1], body: source.slice(match[0].length), bodyStart: match[0].split('\n').length };
}

/** Body lines with their line number and whether they sit inside a code fence. */
function lines(body, bodyStart) {
  let fence = null;
  return body.split('\n').map((text, i) => {
    const marker = text.match(/^\s*(`{3,}|~{3,})(.*)$/);
    const closes =
      marker && fence !== null && marker[1][0] === fence[0] && marker[1].length >= fence.length && marker[2].trim() === '';
    const inCode = fence !== null || Boolean(marker);
    if (closes) fence = null;
    else if (marker && fence === null) fence = marker[1];
    return { text, line: bodyStart + i, inCode };
  });
}

function check(file, minor) {
  const rel = path.relative(ROOT, file);
  const { frontMatter, body, bodyStart } = split(fs.readFileSync(file, 'utf8'));
  const problems = [];
  const report = (line, message) => problems.push(`${rel}:${line}  ${message}`);

  const description = frontMatter.match(/^description\s*=\s*"((?:[^"\\]|\\.)*)"/m);
  if (!description) report(1, 'no description');
  else if (description[1].length < 50 || description[1].length > 160) {
    report(1, `description is ${description[1].length} characters, keep it between 50 and 160`);
  }

  const isLanguagePage = path.dirname(file) === path.join(DOCS, 'language') && path.basename(file) !== '_index.md';
  if (isLanguagePage && !/^difficulty\s*=/m.test(frontMatter)) report(1, 'no difficulty in [extra]');

  const bodyLines = lines(body, bodyStart);
  let seenLead = false;
  let firstH2Checked = false;
  for (const { text, line, inCode } of bodyLines) {
    if (/;;\s*(=>|prints)/.test(text)) report(line, 'write `; =>` and `; prints`, with one semicolon');
    if (/phel/i.test(text) && rel !== path.join('content', 'documentation', 'reference', 'upgrading.md')) {
      for (const [version, minorText] of text.matchAll(/(?<![\d.])v?0\.(\d+)\.\d+(?![\d.])/g)) {
        if (Number(minorText) < minor) report(line, `${version} is older than the current Phel release`);
      }
    }
    if (inCode) continue;
    if (/^>/.test(text)) report(line, 'use {% <callout> %} instead of a blockquote');
    if (/^# /.test(text)) report(line, 'no H1 in the body: the template renders the title');
    if (/^## /.test(text) && !firstH2Checked) {
      firstH2Checked = true;
      if (!seenLead) report(line, 'add a lead paragraph before the first H2');
    }
    if (text.trim() !== '' && !/^(#{1,6} |<!--|\{%)/.test(text.trim())) seenLead = true;
  }

  return problems;
}

function main() {
  const minor = currentMinor();
  const files = markdownFiles(DOCS).sort();
  const problems = files.flatMap((file) => check(file, minor));

  for (const problem of problems) console.log(problem);
  console.log(`Checked ${files.length} documentation pages, ${problems.length} problems.`);
  process.exit(problems.length > 0 ? 1 : 0);
}

main();
