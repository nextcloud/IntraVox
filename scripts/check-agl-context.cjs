const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.resolve(__dirname, '..');
const read = file => fs.readFileSync(path.join(root, file), 'utf8');
assert.equal((read('src/App.vue').match(/<Teleport\b[^>]*\bdefer\b/g) || []).length, 2,
  'Toolbar Teleports must wait until their in-app targets are attached');
const moduleFrom = file => import(`data:text/javascript;base64,${Buffer.from(read(file)).toString('base64')}`);

(async () => {
    const helpers = await moduleFrom('src/utils/aglBrandContext.js');
    const { isExcomEligible, filterAglTree, isVisibleInAglContext } = helpers;
    const executiveUids = read('lib/Service/SovereignBaseline.php').match(/EXECUTIVE_UIDS = \[([^\]]+)\]/)[1];
    assert.deepEqual([...executiveUids.matchAll(/'([^']+)'/g)].map(match => match[1]), [...helpers.AGL_EXCOM_UIDS],
      'Native executive ACLs and client eligibility must use the same approved identities');
    for (const uid of ['scott.mackenzie', 'victor.kovacs', 'alfredo.carrasco', 'stelios.xeroudakis']) {
        assert.equal(isExcomEligible({ uid, groups: [] }), true, uid);
    }
    for (const groups of [['ExCom'], ['admin']]) {
        assert.equal(isExcomEligible({ uid: 'member', groups }), true);
    }
    for (const invalid of [null, {}, { uid: '', groups: ['admin'] }, { uid: 'reader', groups: ['excom'] },
        { uid: 'reader', groups: [] }, { uid: 'victor.kovaks', groups: [] }, { uid: 'reader', groups: 'admin' }]) {
        assert.equal(isExcomEligible(invalid), false);
    }
    const shared = { title: 'Shared', url: '/shared' };
    const carib = { title: 'Carib', url: '/carib', entity: 'cloud-carib' };
    const athens = { title: 'Athens', url: '/athens', entity: 'athena-cloud' };
    const excom = { title: 'ExCom', url: '/excom', entity: 'excom' };
    const privateCarib = { ...carib, title: 'Private Carib', excomOnly: true };
    const tree = [shared, carib, athens, excom, privateCarib,
        { title: 'Container', children: [{ title: 'Level two', children: [excom] }] }];
    const snapshot = JSON.stringify(tree);
    assert.deepEqual(filterAglTree(tree, 'all', false).map(i => i.title), ['Shared', 'Carib', 'Athens']);
    assert.deepEqual(filterAglTree(tree, 'cloud-carib', true).map(i => i.title), ['Shared', 'Carib', 'Private Carib']);
    assert.deepEqual(filterAglTree(tree, 'athena-cloud', false).map(i => i.title), ['Shared', 'Athens']);
    assert.deepEqual(filterAglTree(tree, 'excom', true).map(i => i.title), ['Shared', 'ExCom', 'Private Carib', 'Container']);
    assert.equal(filterAglTree(tree, 'all', true)[5].children[0].children[0].title, 'ExCom');
    assert.equal(isVisibleInAglContext(excom, 'all', false), false);
    assert.equal(isVisibleInAglContext(privateCarib, 'cloud-carib', true), true);
    assert.deepEqual(filterAglTree(null), []);
    assert.equal(JSON.stringify(tree), snapshot, 'filtering must never edit persisted navigation');

    // Exercise the actual Options API storage/identity lifecycle without a login
    // bypass, an external server, or a fabricated browser session.
    const script = read('src/components/AglBrandContextBar.vue').split('<script>')[1].split('</script>')[0]
        .replace(/^import .*;\r?\n/gm, '').replace('export default', 'module.exports =');
    async function mount({ initial, stored, storageFails = false, networkFails = false } = {}) {
        const dataset = { aglContext: 'excom', aglExcom: 'true' };
        const events = [];
        const sandbox = {
            module: { exports: {} }, ...helpers,
            lightLogo: '/original-white-athena-logo.png',
            translate: (app, text) => text,
            loadState: () => initial ?? null,
            axios: { get: async () => {
                if (networkFails) throw new Error('unavailable');
                return { data: { ocs: { data: { id: 'reader', groups: [] } } } };
            } },
            generateOcsUrl: () => '/ocs/v2.php/cloud/user',
            window: { localStorage: {
                getItem: () => { if (storageFails) throw new Error('blocked'); return stored; },
                setItem: () => { if (storageFails) throw new Error('blocked'); },
            } },
            document: { documentElement: { dataset }, dispatchEvent: () => {} },
            CustomEvent: class { constructor(name, options) { this.detail = options.detail; } },
        };
        vm.runInNewContext(script, sandbox);
        const component = sandbox.module.exports;
        const instance = { ...component.data(), $emit: (...args) => events.push(args) };
        for (const [name, method] of Object.entries(component.methods)) instance[name] = method.bind(instance);
        for (const [name, getter] of Object.entries(component.computed)) {
            Object.defineProperty(instance, name, { get: getter.bind(instance) });
        }
        assert.equal(instance.eligible, false, 'identity loading must fail closed');
        await component.mounted.call(instance);
        return { instance, dataset, unmount: () => component.beforeUnmount.call(instance), events };
    }
    let bar = await mount({ initial: { uid: 'leader', groups: ['admin'] }, stored: 'excom' });
    assert.equal(bar.instance.context, 'excom');
    assert.equal(bar.dataset.aglExcom, 'true');
    assert.equal(bar.instance.visibleLenses.length, 4);
    bar.instance.selectContext('athena-cloud');
    assert.equal(bar.dataset.aglContext, 'athena-cloud');
    bar.unmount();
    assert.equal(bar.dataset.aglExcom, undefined);
    assert.equal(bar.dataset.aglContext, undefined);
    for (const options of [
        { initial: { uid: 'reader', groups: [] }, stored: 'excom' },
        { initial: {}, stored: 'malformed' },
        { networkFails: true, stored: 'excom' },
        { storageFails: true, initial: { uid: 'reader', groups: [] } },
    ]) {
        bar = await mount(options);
        assert.equal(bar.instance.context, 'all');
        assert.equal(bar.instance.eligible, false);
        assert.equal(bar.instance.visibleLenses.length, 3);
        assert.equal(bar.dataset.aglExcom, 'false');
        bar.instance.selectContext('excom');
        assert.equal(bar.instance.context, 'all');
    }
    const { AGL_ORGANOGRAM, createOrganogramWidget } = await moduleFrom('src/data/aglOrganogram.js');
    assert.equal(AGL_ORGANOGRAM.columns.length, 5);
    assert.equal(AGL_ORGANOGRAM.executives.length, 4);
    assert.equal(AGL_ORGANOGRAM.companies.length, 3);
    assert.equal(createOrganogramWidget().type, 'organogram');
    assert.equal(createOrganogramWidget().defaultExpanded, true);
    const acorn = require('acorn');
    const viewerScript = read('src/components/PageViewer.vue').split('<script>')[1].split('</script>')[0];
    const exported = acorn.parse(viewerScript, { ecmaVersion: 'latest', sourceType: 'module' })
        .body.find(n => n.type === 'ExportDefaultDeclaration').declaration;
    const computedNode = exported.properties.find(n => n.key.name === 'computed').value;
    const socialComputed = vm.runInNewContext(`(${viewerScript.slice(computedNode.start, computedNode.end)})`);
    function viewer(page, hubPage = false, globalEnabled = true) {
        const view = { page, hubPage, engagementSettings: { allowPageReactions: globalEnabled, allowComments: globalEnabled } };
        for (const [name, getter] of Object.entries(socialComputed)) {
            Object.defineProperty(view, name, { get: getter.bind(view) });
        }
        return view;
    }
    for (const key of ['showReactions', 'showComments']) {
        assert.equal(viewer({})[key], true, 'ordinary page retains global default');
        assert.equal(viewer({}, true)[key], false, 'home defaults to no social');
        assert.equal(viewer({ hidePageTitleBar: true })[key], false, 'hub defaults to no social');
        assert.equal(viewer({ hidePageTitleBar: true, settings: { allowComments: true, allowReactions: true } })[key], true);
        assert.equal(viewer({ disableSocial: true, settings: { allowComments: true, allowReactions: true } })[key], false);
        assert.equal(viewer({ disableSocial: false }, true)[key], true);
        assert.equal(viewer({ disableSocial: false }, true, false)[key], false, 'cannot override global prohibition');
        assert.equal(viewer({ settings: { allowComments: null, allowReactions: null } }, true)[key], false);
    }
    const { markUnavailableApps } = await moduleFrom('src/utils/aglAppAvailability.js');
    const apps = [{ title: 'Apps', children: [{ title: 'Files', appId: 'files' }, { title: 'Mail', appId: 'mail' }] }];
    const before = JSON.stringify(apps);
    const marked = markUnavailableApps(apps, { files: true, mail: false });
    assert.equal(marked[0].children[0].unavailable, undefined);
    assert.equal(marked[0].children[1].unavailable, true);
    assert.match(marked[0].children[1].title, /Not enabled/);
    assert.equal(JSON.stringify(apps), before, 'availability does not mutate saved navigation');
    console.log('Workspace identity, lens, storage, nesting and organogram checks passed');
})().catch(error => { console.error(error); process.exitCode = 1; });
