#!/usr/bin/env node
// Export only the approved CMS tree. No credentials, database or generic demo media.
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '../..');
const source = path.join(root, '.runtime/data/__groupfolders/1/files/en');
const target = path.join(root, 'demo-data/sovereign-365/en');
const entries = ['home.json', 'navigation.json', 'footer.json', '_resources',
  'sovereign-excom', 'sovereign-commercial', 'sovereign-cloud-carib-commercial',
  'sovereign-athena-cloud-commercial', 'sovereign-engineering', 'sovereign-finance',
  'sovereign-technology', 'sovereign-governance', 'sovereign-nassau', 'sovereign-athens', 'sovereign-news'];
const files = [];
function collect(relative) {
  const file = path.join(source, relative);
  if (fs.lstatSync(file).isSymbolicLink()) throw new Error(`Symlink rejected: ${relative}`);
  if (fs.statSync(file).isDirectory()) {
    for (const name of fs.readdirSync(file).sort()) collect(path.join(relative, name));
    return;
  }
  if (!/\.(json|svg)$/.test(relative)) throw new Error(`Unexpected bundle file: ${relative}`);
  const content = fs.readFileSync(file, 'utf8');
  if (/\.replit\.dev|localhost|__groupfolders\/1/i.test(content)) throw new Error(`Sandbox reference: ${relative}`);
  if (relative.endsWith('.json')) JSON.parse(content);
  files.push([relative, content]);
}
entries.forEach(collect);
if (JSON.parse(files.find(([name]) => name === 'home.json')[1]).uniqueId !== 'page-3429f149-dd7c-492a-8a4a-dab3ff83d511') {
  throw new Error('Unexpected Gold Master homepage identity');
}
for (const [relative, content] of files) {
  const destination = path.join(target, relative);
  fs.mkdirSync(path.dirname(destination), { recursive: true });
  fs.writeFileSync(destination, content);
}
console.log(`Exported ${files.length} approved Gold Master files.`);
