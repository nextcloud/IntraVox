#!/usr/bin/env node
const fs = require('node:fs');
const path = require('node:path');
function validateBundle(root) {
  const directory = path.join(root, 'demo-data/sovereign-365/en');
  const errors = [];
  if (!fs.existsSync(directory)) return ['Missing Sovereign Gold Master bundle'];
  let count = 0;
  function visit(relative) {
    const absolute = path.join(directory, relative);
    const stat = fs.lstatSync(absolute);
    if (stat.isSymbolicLink()) { errors.push(`Bundle symlink: ${relative}`); return; }
    if (stat.isDirectory()) {
      for (const name of fs.readdirSync(absolute)) visit(path.join(relative, name));
      return;
    }
    count++;
    if (!/\.(json|svg)$/.test(relative)) errors.push(`Unexpected bundle file: ${relative}`);
    if (/(?:^|\/)(?:about|departments|documentation|_media)(?:\/|$)/.test(relative)) errors.push(`Upstream demo path: ${relative}`);
    const text = fs.readFileSync(absolute, 'utf8');
    if (/\.replit\.dev|localhost|__groupfolders\/1/i.test(text)) errors.push(`Sandbox reference in bundle: ${relative}`);
    if (relative.endsWith('.json')) {
      try { JSON.parse(text); } catch { errors.push(`Invalid JSON: ${relative}`); }
    }
  }
  visit('');
  for (const name of ['home.json', 'navigation.json', 'footer.json',
    '_resources', 'sovereign-excom', 'sovereign-commercial',
    'sovereign-cloud-carib-commercial', 'sovereign-athena-cloud-commercial',
    'sovereign-engineering', 'sovereign-finance', 'sovereign-technology',
    'sovereign-governance', 'sovereign-nassau', 'sovereign-athens',
    'sovereign-news/group/group.json', 'sovereign-news/caribbean/caribbean.json', 'sovereign-news/emea/emea.json']) {
    if (!fs.existsSync(path.join(directory, name))) errors.push(`Missing bundle entry: ${name}`);
  }
  try {
    const home = JSON.parse(fs.readFileSync(path.join(directory, 'home.json'), 'utf8'));
    if (home.uniqueId !== 'page-3429f149-dd7c-492a-8a4a-dab3ff83d511') errors.push('Incorrect Gold Master home ID');
    for (const id of ['sovereign-overview', 'sovereign-workspaces']) {
      if (!home.layout.rows.some(row => row.id === id)) errors.push(`Missing Gold Master layout row: ${id}`);
    }
  } catch { errors.push('Cannot validate Gold Master homepage'); }
  if (count > 100) errors.push('Gold Master bundle exceeds the 100-file limit');
  return errors;
}
if (require.main === module) {
  const errors = validateBundle(path.resolve(__dirname, '..'));
  if (errors.length) { console.error(errors.join('\n')); process.exitCode = 1; }
  else console.log('Gold Master bundle: portable, complete, demo-free.');
}
module.exports = { validateBundle };
