#!/usr/bin/env node
/**
 * Detect mixed sync/async imports of the same .vue component across the
 * codebase. Webpack splits such imports into overlapping chunks that can
 * produce runtime `TypeError: Cannot read properties of undefined (reading
 * 'call')` when one chunk loads before its sibling.
 *
 * Failure mode that motivated this check (2026-05-27):
 *   - Widget.vue: defineAsyncComponent(() => import('./PhotoStoryWidget.vue'))
 *   - PhotoStoryWidgetEditor.vue: import PhotoStoryWidget from './PhotoStoryWidget.vue'
 *   → runtime crash on page that uses both.
 *
 * Run: node scripts/check-import-consistency.js
 * Exit code: 0 if clean, 1 if mixed imports found.
 */

const fs = require('fs');
const path = require('path');

const srcDir = path.join(__dirname, '..', 'src');
const ignoredDirs = new Set(['node_modules', 'dist']);

function walk(dir, out = []) {
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    if (entry.isDirectory()) {
      if (!ignoredDirs.has(entry.name)) walk(path.join(dir, entry.name), out);
    } else if (entry.isFile() && (entry.name.endsWith('.vue') || entry.name.endsWith('.js'))) {
      out.push(path.join(dir, entry.name));
    }
  }
  return out;
}

// Map: target .vue path → { sync: [importer paths], async: [importer paths] }
const importMap = new Map();

const SYNC_IMPORT = /import\s+\w+\s+from\s+['"]([^'"]+\.vue)['"]/g;
const ASYNC_IMPORT = /defineAsyncComponent\s*\(\s*\(\)\s*=>\s*import\s*\(\s*['"]([^'"]+\.vue)['"]\s*\)\s*\)/g;

function resolveTarget(importer, spec) {
  if (spec.startsWith('./') || spec.startsWith('../')) {
    return path.resolve(path.dirname(importer), spec);
  }
  return spec; // bare import — leave as-is
}

for (const file of walk(srcDir)) {
  const text = fs.readFileSync(file, 'utf8');
  let m;
  SYNC_IMPORT.lastIndex = 0;
  while ((m = SYNC_IMPORT.exec(text))) {
    const target = resolveTarget(file, m[1]);
    if (!importMap.has(target)) importMap.set(target, { sync: [], async: [] });
    importMap.get(target).sync.push(file);
  }
  ASYNC_IMPORT.lastIndex = 0;
  while ((m = ASYNC_IMPORT.exec(text))) {
    const target = resolveTarget(file, m[1]);
    if (!importMap.has(target)) importMap.set(target, { sync: [], async: [] });
    importMap.get(target).async.push(file);
  }
}

const conflicts = [];
for (const [target, { sync, async }] of importMap.entries()) {
  if (sync.length > 0 && async.length > 0) {
    conflicts.push({ target, sync, async });
  }
}

if (conflicts.length === 0) {
  console.log('✓ Import consistency: all .vue components use a single import strategy');
  process.exit(0);
}

console.error('✗ Mixed sync/async imports detected for the following components:');
console.error('');
const relSrc = (p) => path.relative(srcDir, p);
for (const c of conflicts) {
  console.error(`  ${relSrc(c.target)}`);
  for (const f of c.sync)  console.error(`    sync  ← ${relSrc(f)}`);
  for (const f of c.async) console.error(`    async ← ${relSrc(f)}`);
  console.error('');
}
console.error('Webpack can produce runtime TypeError when the same component is loaded');
console.error('via both strategies. Pick one — usually async + defineAsyncComponent — and');
console.error('use it consistently across every importer of that component.');
process.exit(1);
