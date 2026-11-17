#!/usr/bin/env node
// Scoped local-reference and screenshot guard; not a general Markdown parser.
const fs = require('node:fs');
const path = require('node:path');

const documents = [
  'README.md',
  'docs/index.md',
  'docs/engineering/portal-code-map.md',
  'docs/engineering/sovereign-development.md',
  'docs/engineering/release-quality.md',
  'docs/engineering/sovereign-gold-master.md',
  'docs/releases/portal-v1.0.md',
  'screenshots/sovereign-portal/README.md',
];

function validateDocuments(root, files = documents) {
  const errors = [];
  for (const file of files) {
    const absolute = path.resolve(root, file);
    if (!fs.existsSync(absolute)) {
      errors.push(`${file}: missing document`);
      continue;
    }
    const text = fs.readFileSync(absolute, 'utf8');
    const prose = text.replace(/^```[^\n]*\n[\s\S]*?^```/gm, '');
    const targets = [
      ...Array.from(prose.matchAll(/!?\[[^\]]*\]\(([^)\s]+)\)/g), match => match[1]),
      ...Array.from(prose.matchAll(/<img\b[^>]*\bsrc=["']([^"']+)["']/gi), match => match[1]),
    ];
    for (const target of targets) {
      if (/^(?:https?:|mailto:)/i.test(target)) continue;
      let local;
      try { local = decodeURIComponent(target.split(/[?#]/)[0]); }
      catch { errors.push(`${file}: invalid encoded link ${target}`); continue; }
      if (!local) continue; // This guard checks paths, not heading anchors.
      const resolved = path.resolve(path.dirname(absolute), local);
      const relative = path.relative(root, resolved);
      if (relative === '..' || relative.startsWith(`..${path.sep}`) || path.isAbsolute(relative)) {
        errors.push(`${file}: reference escapes repository: ${target}`);
      } else if (!fs.existsSync(resolved)) {
        errors.push(`${file}: missing local reference ${target}`);
      }
    }
    if (file === 'README.md' && /raw\.githubusercontent\.com\/nextcloud\/intravox\/.*screenshots/i.test(text)) {
      errors.push('README.md: stale original IntraVox screenshot URL');
    }
  }
  return errors;
}

function validateReleaseIdentity(root) {
  const version = JSON.parse(fs.readFileSync(path.join(root, 'package.json'), 'utf8')).version;
  const metadata = fs.readFileSync(path.join(root, 'appinfo/info.xml'), 'utf8');
  const readme = fs.readFileSync(path.join(root, 'README.md'), 'utf8');
  const errors = [];
  if (!metadata.includes(`<version>${version}</version>`)) errors.push('App/package versions differ');
  if (!readme.includes(`**${version}**`)) errors.push('README app version differs from package');
  return errors;
}

function validateScreenshots(root) {
  const directory = path.join(root, 'screenshots/sovereign-portal');
  const names = ['home-light', 'home-dark', 'launchpad-light', 'editor-light', 'settings-light', 'mobile-dark'];
  const errors = [];
  let bytes = 0;
  for (const name of names) {
    const file = path.join(directory, `${name}.webp`);
    if (!fs.existsSync(file)) { errors.push(`Missing screenshot ${name}`); continue; }
    const buffer = fs.readFileSync(file);
    bytes += buffer.length;
    if (buffer.toString('ascii', 0, 4) !== 'RIFF' || buffer.toString('ascii', 8, 12) !== 'WEBP') {
      errors.push(`Invalid WebP header: ${name}`);
    }
  }
  if (bytes > 512 * 1024) errors.push('Curated screenshots exceed the documented 512 KiB budget');
  return errors;
}

if (require.main === module) {
  const root = path.resolve(__dirname, '..');
  const errors = [...validateDocuments(root), ...validateReleaseIdentity(root), ...validateScreenshots(root),
    ...require('./check-sovereign-bundle.cjs').validateBundle(root)];
  if (errors.length) {
    console.error(errors.join('\n'));
    process.exitCode = 1;
  } else {
    console.log(`PASS ${documents.length} Sovereign documents: local paths, app version and six screenshot headers/budget.`);
    console.log('Manual review still required: anchors, external URLs, visual content, commands and gate evidence.');
  }
}

module.exports = { validateDocuments, validateReleaseIdentity, validateScreenshots };
