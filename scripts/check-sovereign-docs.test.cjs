const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const { validateDocuments, validateReleaseIdentity, validateScreenshots } = require('./check-sovereign-docs.cjs');

function fixture(t) {
  const root = fs.mkdtempSync(path.join(os.tmpdir(), 'sovereign-docs-'));
  t.after(() => fs.rmSync(root, { recursive: true, force: true }));
  const write = (file, content) => {
    fs.mkdirSync(path.dirname(path.join(root, file)), { recursive: true });
    fs.writeFileSync(path.join(root, file), content);
  };
  return { root, write };
}

test('accepts relative Markdown and HTML images, encoded names and external links', t => {
  const { root, write } = fixture(t);
  write('README.md', '[guide](docs/guide.md#heading)\n![image](image%20one.webp)\n<img src="image%20one.webp">\n[external](https://example.com)\n[anchor](#heading)');
  write('docs/guide.md', '# Heading');
  write('image one.webp', 'fixture');
  assert.deepEqual(validateDocuments(root, ['README.md']), []);
});

test('rejects missing documents and broken relative references', t => {
  const { root, write } = fixture(t);
  write('README.md', '[missing](missing.md)\n<img src="missing.webp">');
  assert.equal(validateDocuments(root, ['README.md', 'absent.md']).length, 3);
});

test('rejects stale upstream README images and paths escaping the repository', t => {
  const { root, write } = fixture(t);
  write('README.md', '![old](https://raw.githubusercontent.com/nextcloud/intravox/main/screenshots/demo.png)\n[escape](../outside.md)');
  assert.equal(validateDocuments(root, ['README.md']).length, 2);
});

test('ignores example code and reports malformed encoded paths', t => {
  const { root, write } = fixture(t);
  write('README.md', '```md\n[example](not-a-link.md)\n```\n[broken](bad%ZZ.md)');
  assert.equal(validateDocuments(root, ['README.md']).length, 1);
});

test('catches app metadata and README version drift', t => {
  const { root, write } = fixture(t);
  write('package.json', '{"version":"3.1.2"}');
  write('appinfo/info.xml', '<info><version>3.1.2</version></info>');
  write('README.md', '**3.1.2**');
  assert.deepEqual(validateReleaseIdentity(root), []);
  write('appinfo/info.xml', '<info><version>1.0.0</version></info>');
  write('README.md', '**1.0.0**');
  assert.equal(validateReleaseIdentity(root).length, 2);
});

test('rejects missing, invalid or oversized screenshot assets', t => {
  const { root, write } = fixture(t);
  assert.equal(validateScreenshots(root).length, 6);
  const names = ['home-light', 'home-dark', 'launchpad-light', 'editor-light', 'settings-light', 'mobile-dark'];
  for (const name of names) write(`screenshots/sovereign-portal/${name}.webp`, 'RIFF0000WEBP');
  assert.deepEqual(validateScreenshots(root), []);
  write('screenshots/sovereign-portal/home-light.webp', 'not an image');
  assert.equal(validateScreenshots(root).length, 1);
  write('screenshots/sovereign-portal/home-light.webp', Buffer.concat([Buffer.from('RIFF0000WEBP'), Buffer.alloc(512 * 1024)]));
  assert.equal(validateScreenshots(root).length, 1);
});
