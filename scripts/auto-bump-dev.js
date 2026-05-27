#!/usr/bin/env node
/**
 * Auto-bump the patch-level version for dev deploys.
 *
 * Why: NC's cache-buster is `substr(md5(appVersion), 0, 8)` — the only way
 * to force browsers to re-download our bundle after a deploy is to change
 * the version string. Every dev deploy therefore needs a fresh version.
 *
 * Strategy: if version is `X.Y.Z`, bump to `X.Y.Z.1`.
 *           if version is `X.Y.Z.N`, bump to `X.Y.Z.N+1`.
 *
 * Only runs on dev deploys — production releases should set the version
 * explicitly via release-flow, not via this script.
 *
 * Usage: node scripts/auto-bump-dev.js
 */

const fs = require('fs');
const path = require('path');

const root = path.join(__dirname, '..');
const pkgPath = path.join(root, 'package.json');
const infoXmlPath = path.join(root, 'appinfo', 'info.xml');

const pkg = JSON.parse(fs.readFileSync(pkgPath, 'utf8'));
const current = pkg.version;

const parts = current.split('.');
let next;
if (parts.length === 3) {
  // X.Y.Z -> X.Y.Z.1
  next = `${current}.1`;
} else if (parts.length === 4) {
  // X.Y.Z.N -> X.Y.Z.(N+1)
  parts[3] = String(parseInt(parts[3], 10) + 1);
  next = parts.join('.');
} else {
  console.error(`✗ Unexpected version format: ${current}`);
  process.exit(1);
}

// Update package.json
pkg.version = next;
fs.writeFileSync(pkgPath, JSON.stringify(pkg, null, 2) + '\n');

// Update appinfo/info.xml
const infoXml = fs.readFileSync(infoXmlPath, 'utf8');
const updated = infoXml.replace(
  /<version>[^<]+<\/version>/,
  `<version>${next}</version>`,
);
fs.writeFileSync(infoXmlPath, updated);

console.log(`✓ Auto-bumped: ${current} → ${next}`);
