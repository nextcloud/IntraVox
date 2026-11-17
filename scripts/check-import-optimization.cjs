const assert = require('node:assert/strict');
const vm = require('node:vm');
const components = require('./nextcloud-component-imports-loader.cjs');
const locales = require('./dialog-locale-chunks-loader.cjs');
const optimized = components("import { NcButton, NcDialog as Dialog } from '@nextcloud/vue';");
assert.match(optimized, /import NcButton from '@nextcloud\/vue\/components\/NcButton'/);
assert.match(optimized, /import Dialog from '@nextcloud\/vue\/components\/NcDialog'/);
assert.throws(() => components("import { unknownHelper } from '@nextcloud/vue'"));
const original = ['en', 'fr', 'de', 'nl'].map(language =>
    ({ language, translations: [{ msgid: 'Test', msgstr: ['x'.repeat(70000)] }] }));
const source = `const registered = []; for (const data of ${JSON.stringify(original)}) { registered.push(data); }`;
const transformed = locales.call({ resourcePath: '/fixture.mjs', resourceQuery: '' }, source);
assert.match(transformed, /aglDialogLocales=0/);
const recovered = [];
for (let index = 0; index < original.length; index++) {
    const part = locales.call({ resourceQuery: `?aglDialogLocales=${index}` }, source);
    recovered.push(...vm.runInNewContext(part.replace('export default', '')));
}
assert.equal(JSON.stringify(recovered), JSON.stringify(original), 'all locale data and ordering must survive');
assert.throws(() => locales.call({ resourceQuery: '?aglDialogLocales=99' }, source));
assert.throws(() => locales.call({}, 'export default {}'));
console.log('Nextcloud component and locale chunk optimizations preserve their contracts');
