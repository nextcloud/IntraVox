#!/usr/bin/env node
/* Real Chromium + native Nextcloud logins. Temporary QA users are always removed.
 * Credentials stay in this process and are never logged or saved in reports.
 */
const fs = require('node:fs');
const path = require('node:path');
const { spawn, execFileSync } = require('node:child_process');
const { randomBytes } = require('node:crypto');
const assert = require('node:assert/strict');
const { verifyFinalTheme } = require('./sovereign-ui-checks.cjs');

const root = path.resolve(__dirname, '../..');
const out = path.join(root, 'generated-artifacts/sovereign-qa');
const base = `https://${process.env.REPLIT_DEV_DOMAIN}`;
const occ = (...args) => execFileSync('php', ['-d', 'memory_limit=512M', '.runtime/nextcloud/occ', ...args],
  { cwd: root, encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] });
const pause = ms => new Promise(r => setTimeout(r, ms));
const results = [];
const record = (name, data = {}) => { results.push({ name, ...data }); console.log(`PASS ${name}`, JSON.stringify(data)); };
fs.mkdirSync(out, { recursive: true });

async function connect(url) {
  const ws = new WebSocket(url);
  await new Promise((resolve, reject) => { ws.onopen = resolve; ws.onerror = reject; });
  let id = 0;
  const pending = new Map();
  const diagnostics = [];
  ws.onmessage = event => {
    const value = JSON.parse(event.data);
    if (value.method === 'Runtime.consoleAPICalled' && value.params.type === 'error') {
      diagnostics.push(value.params.args.map(a => a.description || a.value || a.type).join(' ').slice(0,1200));
    }
    if (value.method === 'Runtime.exceptionThrown') diagnostics.push(value.params.exceptionDetails.exception?.description || value.params.exceptionDetails.text);
    if (value.method === 'Network.responseReceived' && value.params.response.url.includes('/apps/intravox/api/')) {
      diagnostics.push({ path: new URL(value.params.response.url).pathname, status: value.params.response.status });
    }
    if (value.id && pending.has(value.id)) {
      const p = pending.get(value.id); pending.delete(value.id);
      value.error ? p.reject(Error(value.error.message)) : p.resolve(value.result);
    }
  };
  return {
    diagnostics,
    send(method, params = {}) {
      return new Promise((resolve, reject) => {
        const n = ++id; pending.set(n, { resolve, reject });
        ws.send(JSON.stringify({ id: n, method, params }));
      });
    },
    close() { ws.close(); },
  };
}

async function browser(role, group, port) {
  const uid = `sovereign-qa-${role}-${Date.now()}`;
  const password = `Qa1!${randomBytes(24).toString('base64url')}`;
  const profile = path.join(root, '.runtime', uid);
  let chrome, cdp, qaPageId;
  try {
    process.env.OC_PASS = password;
    try {
      execFileSync('php', ['-d', 'memory_limit=512M', '.runtime/nextcloud/occ', 'user:add', '--password-from-env',
        '--display-name', `Sovereign QA ${role}`, '--group', group, uid],
      { cwd: root, env: process.env, stdio: ['ignore', 'pipe', 'pipe'] });
    } finally { delete process.env.OC_PASS; }
    if (role === 'excom') occ('group:adduser', 'ExCom', uid);
    occ('user:setting', uid, 'firstrunwizard', 'show', '32.0.15');
    chrome = spawn('/repl/tools/bin/chromium', ['--headless', '--no-sandbox', '--disable-dev-shm-usage',
      '--no-first-run', `--user-data-dir=${profile}`, `--remote-debugging-port=${port}`, 'about:blank'],
    { stdio: 'ignore' });
    let tabs;
    for (let i = 0; i < 40; i++) {
      try { tabs = await (await fetch(`http://127.0.0.1:${port}/json`)).json(); if (tabs.length) break; } catch {}
      await pause(250);
    }
    assert(tabs?.length, 'Chromium started');
    cdp = await connect(tabs.find(t => t.type === 'page').webSocketDebuggerUrl);
    await cdp.send('Page.enable');
    await cdp.send('Runtime.enable');
    await cdp.send('Network.enable');
    const evaluate = async expression => {
      const r = await cdp.send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true });
      if (r.exceptionDetails) throw Error(r.exceptionDetails.text + ': ' + r.exceptionDetails.exception?.description);
      return r.result.value;
    };
    const wait = async (expression, label, limit = 120) => {
      for (let i = 0; i < limit; i++) {
        try { if (await evaluate(expression)) return; } catch (e) {
          if (!/Execution context|Cannot find context/.test(e.message)) throw e;
        }
        await pause(250);
      }
      const state = await evaluate('({url:location.pathname,text:document.body.innerText.slice(0,700),search:[...document.querySelectorAll("#unified-search,#unified-search *")].slice(0,12).map(e=>({tag:e.tagName,cls:e.className?.baseVal??e.className,role:e.getAttribute("role"),h:e.getBoundingClientRect().height}))})');
      const shot = await cdp.send('Page.captureScreenshot', { format: 'png' });
      fs.writeFileSync(path.join(out, `${role}-failed.png`), Buffer.from(shot.data, 'base64'));
      throw Error(`Timed out: ${label}; ${JSON.stringify(state)}; ${JSON.stringify(cdp.diagnostics)}`);
    };
    const reload = async () => {
      await evaluate('window.__sovereignQaReload = true');
      await cdp.send('Page.reload');
      await wait("window.__sovereignQaReload!==true && document.readyState==='complete'", 'new document loaded');
    };
    const setTheme = async mode => {
      const status = await evaluate(`(async()=>{const r=await fetch((OC.webroot||'')+'/ocs/v2.php/apps/theming/api/v1/theme/${mode}/enable?format=json',{method:'PUT',headers:{requesttoken:OC.requestToken,'OCS-APIRequest':'true','Content-Type':'application/json'},body:'{}'});return r.status})()`);
      assert.equal(status, 200, 'Native Nextcloud theme preference accepted');
    };
    const click = async text => {
      const success = await evaluate(`(() => {const el=[...document.querySelectorAll('button,a')].find(e=>e.textContent.trim()===${JSON.stringify(text)} && e.getBoundingClientRect().width); if(!el)return false;el.click();return true})()`);
      assert(success, `Found control ${text}`);
      await pause(350);
    };
    const mouseClick = async selector => {
      const point = await evaluate(`(()=>{const e=[...document.querySelectorAll(${JSON.stringify(selector)})].at(-1);if(!e)return null;e.scrollIntoView({behavior:'instant',block:'center'});const r=e.getBoundingClientRect();return {x:r.x+r.width/2,y:r.y+r.height/2}})()`);
      assert(point, `Found pointer control ${selector}`);
      await cdp.send('Input.dispatchMouseEvent', {type:'mousePressed',...point,button:'left',clickCount:1});
      await cdp.send('Input.dispatchMouseEvent', {type:'mouseReleased',...point,button:'left',clickCount:1});
    };
    const screenshot = async name => {
      const shot = await cdp.send('Page.captureScreenshot', { format: 'png' });
      fs.writeFileSync(path.join(out, `${name}.png`), Buffer.from(shot.data, 'base64'));
    };
    await cdp.send('Emulation.setDeviceMetricsOverride', { width: 1440, height: 1100, deviceScaleFactor: 1, mobile: false });
    await cdp.send('Page.navigate', { url: `${base}/index.php/login` });
    await wait("!!document.querySelector('#user') && !!document.querySelector('#password')", 'native login form');
    // Native form, native CSRF token, no cookie manipulation or auth bypass.
    await evaluate(`(() => {for(const [selector,value] of [['#user',${JSON.stringify(uid)}],['#password',${JSON.stringify(password)}]]){const input=document.querySelector(selector);input.value=value;input.dispatchEvent(new Event('input',{bubbles:true}));input.dispatchEvent(new Event('change',{bubbles:true}));} document.querySelector('form').requestSubmit();return true})()`);
    await wait("!location.pathname.includes('/login') && !!document.querySelector('#header')", 'valid native login', 100);
    await cdp.send('Page.navigate', { url: `${base}/index.php/apps/intravox/` });
    await wait("!!document.querySelector('.agl-organogram') && document.querySelectorAll('.news-item').length > 0", 'Sovereign homepage');
    await pause(600);
    const state = await evaluate(`(() => {
      const app=document.querySelector('#intravox-app'), top=app.querySelector('.intravox-topbar');
      const rect=e=>{const r=e.getBoundingClientRect();return{x:r.x,y:r.y,width:r.width,height:r.height}};
      return {title:document.title,theme:document.documentElement.dataset.themeLight!==undefined?'light':document.documentElement.dataset.themeDark!==undefined?'dark':'default',
        heading:document.body.innerText.includes('Sovereign 365 App Launchpad'),comments:!!document.querySelector('.reactions-comments-section'),
        header:rect(top),navigation:rect(app.querySelector('.intravox-nav-bar')),context:rect(app.querySelector('.agl-context-bar')),
        separateUtility:app.querySelector('.intravox-header').getBoundingClientRect().height,
        pills:[...app.querySelectorAll('.agl-context-bar__lens')].map(e=>e.textContent.trim()),
        companies:app.querySelectorAll('.agl-organogram__company').length,
        executives:app.querySelectorAll('.agl-organogram__executive').length,
        columns:app.querySelectorAll('.agl-organogram__column').length,
        hubLinks:app.querySelectorAll('a.agl-organogram__hub-link').length,
        news:[...app.querySelectorAll('.news-item')].map(e=>e.dataset.aglEntity),
         newsBadges:app.querySelectorAll('.news-item .agl-entity-badge').length,
        appCards:app.querySelectorAll('.links-widget')[0].querySelectorAll('.link-item').length,
        officeCards:app.querySelectorAll('.links-widget')[1].querySelectorAll('.link-item').length,
        emptySubtitles:[...app.querySelectorAll('.link-item')].filter(e=>!e.querySelector('.link-tile-subtitle')?.textContent.trim()).length,
        hero:!!app.querySelector('.sovereign-hero'),
        clocks:app.querySelectorAll('.sovereign-hero time').length,
        duplicateLogo:app.querySelectorAll('.agl-context-bar img').length,
        unavailablePills:app.querySelectorAll('.link-unavailable').length,
        unavailable:app.querySelectorAll('.link-item[aria-disabled=\"true\"]').length,
        horizontalOverflow:document.documentElement.scrollWidth>innerWidth,
        errors:[...app.querySelectorAll('[role=\"alert\"]')].map(e=>e.textContent.trim())};
    })()`);
    assert(state.heading && !state.comments, 'Branded homepage without social section');
    assert.equal(state.separateUtility, 0, 'No separate empty utility row');
     assert(state.context.height < 80, 'Context and controls remain one compact desktop tier');
    assert(Math.abs(state.header.height - state.navigation.height - state.context.height) < 4, 'Only two header tiers');
    assert.equal(state.executives, 4); assert.equal(state.companies, 3); assert.equal(state.columns, 5);
    assert.deepEqual(state.news.sort(), ['all', 'athena-cloud', 'cloud-carib']);
    assert.equal(state.newsBadges, 3);
    assert.equal(state.appCards, 8);
    assert.equal(state.hubLinks, 2);
    assert.equal(state.officeCards, role === 'reader' ? 6 : 8);
    assert.equal(state.emptySubtitles, 0);
    assert(state.hero && state.clocks === 2);
    assert.equal(state.duplicateLogo, 0);
    assert.equal(state.unavailablePills, 0);
    assert.equal(state.horizontalOverflow, false);
    assert.equal(state.errors.length, 0);
    assert.equal(state.pills.includes('ExCom HoldCo'), role !== 'reader');
    record(`${role}: homepage, two-tier header, social suppression, organogram and role lens`, state);
    await screenshot(`${role}-desktop-default`);
    await cdp.send('Emulation.setDeviceMetricsOverride', { width:1024,height:900,deviceScaleFactor:1,mobile:false });
    await pause(350);
    const preview = await evaluate(`(()=>{const box=s=>{const e=document.querySelector(s),r=e.getBoundingClientRect(),c=getComputedStyle(e);return {width:r.width,height:r.height,display:c.display,flexBasis:c.flexBasis,gap:c.gap}};
      return {context:box('.agl-context-bar'),workspace:box('.agl-context-bar__workspace'),lenses:box('.agl-context-bar__lenses'),launches:box('.agl-context-bar__launches'),tools:box('.agl-context-bar__tools'),actions:box('.agl-context-bar__tools .header-right'),overflow:document.documentElement.scrollWidth>innerWidth}})()`);
    record(`${role}: embedded Preview geometry`, preview);
    if (role === 'admin') await screenshot('admin-preview-width');
    await cdp.send('Emulation.setDeviceMetricsOverride', { width:1440,height:1100,deviceScaleFactor:1,mobile:false });

    for (const [label, entity] of [['Cloud Carib', 'cloud-carib'], ['Athena Cloud', 'athena-cloud'], ['All Authorized', 'all']]) {
      await click(label);
      const shown = await evaluate("[...document.querySelectorAll('.news-item')].map(e=>e.dataset.aglEntity)");
      assert.deepEqual(shown.sort(), (entity === 'all' ? ['all', 'athena-cloud', 'cloud-carib'] : ['all', entity]).sort());
      record(`${role}: ${label} news filtering`, { entities: shown });
    }
    if (role !== 'reader') {
      await click('ExCom HoldCo');
      assert(await evaluate("!![...document.querySelectorAll('.link-item')].find(e=>e.textContent.includes('ExCom HoldCo Vault'))"));
      await click('All Authorized');
      const privateStatus = await evaluate("(async()=>{const r=await fetch(OC.generateUrl('/apps/intravox/api/pages/page-sovereign-excom'));return r.status})()");
      assert.equal(privateStatus, 200, 'Authorized executive page accessible');
      record(`${role}: executive content authorized by server`, { status: privateStatus });
    } else {
      assert.equal(await evaluate("!![...document.querySelectorAll('.link-item')].find(e=>e.textContent.includes('ExCom HoldCo Vault'))"), false);
      const privateStatus = await evaluate("(async()=>{const r=await fetch(OC.generateUrl('/apps/intravox/api/pages/page-sovereign-excom'));return r.status})()");
      assert([403, 404].includes(privateStatus), 'Restricted page denied by server');
      record('reader: ExCom page rejected by server', { status: privateStatus });
    }
    // Exercise real Nextcloud user themes through the supported theme endpoint.
    for (const mode of ['light', 'dark']) {
      await setTheme(mode);
      await reload();
      await wait("!!document.querySelector('.agl-organogram') && document.querySelectorAll('.news-item').length===3", `${mode} homepage`);
      const colors = await evaluate(`({canvas:getComputedStyle(document.querySelector('#intravox-app')).backgroundColor,card:getComputedStyle(document.querySelector('.news-item')).backgroundColor,pill:getComputedStyle(document.querySelector('[aria-pressed=\"true\"].agl-context-bar__lens')).backgroundColor,pillText:getComputedStyle(document.querySelector('[aria-pressed=\"true\"].agl-context-bar__lens')).color,theme:[...document.body.attributes].filter(a=>/^data-(theme|color-scheme)/.test(a.name)).map(a=>a.name+'='+a.value)})`);
      record(`${role}: native ${mode} colors`, colors);
      assert(await evaluate("getComputedStyle(document.querySelector('.link-item--unavailable .link-tile-title')).color===getComputedStyle(document.querySelector('#intravox-app')).color"), `${mode} unavailable app labels remain readable`);
      assert.equal(colors.pill, 'rgb(255, 255, 255)'); assert.equal(colors.pillText, 'rgb(46, 13, 54)');
      assert.equal(colors.canvas, mode === 'light' ? 'rgb(248, 250, 252)' : 'rgb(19, 13, 24)');
      assert.equal(colors.card, mode === 'light' ? 'rgb(255, 255, 255)' : 'rgb(30, 21, 38)');
      record(`${role}: native ${mode} theme and exact active-pill contrast`, colors);
      if (role === 'admin') {
        await verifyFinalTheme({evaluate,wait,click,cdp,screenshot,reload,base,record,mouseClick},mode);
        await screenshot(`admin-desktop-${mode}`);
        if (mode === 'light') {
          await cdp.send('Emulation.setEmulatedMedia', {features:[{name:'prefers-color-scheme',value:'dark'}]});
          assert.equal(await evaluate("getComputedStyle(document.querySelector('#intravox-app')).backgroundColor"), 'rgb(248, 250, 252)', 'Explicit native light overrides dark OS preference');
          await cdp.send('Emulation.setEmulatedMedia', {features:[]});
          record('admin: explicit light theme respected with a dark OS preference');
        }
        await cdp.send('Emulation.setDeviceMetricsOverride', {width:390,height:844,deviceScaleFactor:1,mobile:true});
        await pause(350);
        assert.equal(await evaluate("document.documentElement.scrollWidth>innerWidth"), false, `${mode} mobile overflow`);
        await screenshot(`admin-mobile-${mode}`);
        await evaluate("document.querySelector('.agl-organogram').scrollIntoView({behavior:'instant',block:'start'})");
        await wait("(()=>{const r=document.querySelector('.agl-organogram').getBoundingClientRect();return r.height>100&&r.top<innerHeight-50&&r.bottom>300})()", 'mobile organogram scrolled into view');
        await screenshot(`admin-mobile-organogram-${mode}`);
        await cdp.send('Emulation.setDeviceMetricsOverride', {width:1440,height:1100,deviceScaleFactor:1,mobile:false});
      }
    }
    if (role === 'admin') {
      await setTheme('default');
      await cdp.send('Emulation.setEmulatedMedia', {features:[{name:'prefers-color-scheme',value:'dark'}]});
      await reload();
      await wait("!!document.querySelector('.agl-organogram') && getComputedStyle(document.querySelector('#intravox-app')).backgroundColor==='rgb(19, 13, 24)'", 'native automatic dark theme');
      await cdp.send('Emulation.setEmulatedMedia', {features:[{name:'prefers-color-scheme',value:'light'}]});
      await wait("getComputedStyle(document.querySelector('#intravox-app')).backgroundColor==='rgb(248, 250, 252)'", 'native automatic light theme');
      record('admin: native automatic theme followed OS changes without reload');
      await cdp.send('Emulation.setEmulatedMedia', {features:[]});
    }
    // Both C3 native page destinations must load, then Home restores the baseline.
    for (const [name, id] of [['Nassau', 'page-a1000002-0002-4000-8000-000000000021'], ['Athens', 'page-a1000002-0002-4000-8000-000000000022']]) {
      await cdp.send('Page.navigate', { url: `${base}/index.php/apps/intravox/#${id}` });
      await wait(`[...document.querySelectorAll('#intravox-main-content h1,#intravox-main-content h2')].some(e=>e.innerText.includes(${JSON.stringify(name)}) && e.innerText.includes('C3'))`, `${name} C3 destination`);
      record(`${role}: ${name} C3 destination loaded`);
    }
    await cdp.send('Page.navigate', { url: `${base}/index.php/apps/intravox/#page-home-en` });
    await wait("!!document.querySelector('.agl-organogram')", 'Home alias');
    const expanded = await evaluate("document.querySelector('.agl-organogram__toggle').getAttribute('aria-expanded')");
    await evaluate("document.querySelector('.agl-organogram__toggle').click()");
    await pause(200);
    assert.notEqual(await evaluate("document.querySelector('.agl-organogram__toggle').getAttribute('aria-expanded')"), expanded);
    await evaluate("document.querySelector('.agl-organogram__toggle').click();document.querySelector('.agl-organogram__executive').click()");
    assert.equal(await evaluate("document.querySelector('.agl-organogram__executive').getAttribute('aria-pressed')"), 'true');
    record(`${role}: organogram expansion and executive selection`);
    await evaluate("document.querySelector('.agl-organogram').scrollIntoView({behavior:'instant',block:'start'})");
    await wait("(()=>{const r=document.querySelector('.agl-organogram').getBoundingClientRect();return r.height>100&&r.top<innerHeight-50&&r.bottom>157})()", 'organogram scrolled into view');
    record(`${role}: visible organogram geometry`, await evaluate("(()=>{const r=document.querySelector('.agl-organogram').getBoundingClientRect();return {top:r.top,height:r.height,bottom:r.bottom}})()"));
    await pause(200);
    await screenshot(`${role}-organogram`);
    if (role === 'admin') {
      await evaluate("[...document.querySelectorAll('.link-item')].find(e=>e.textContent.includes('Global Search')).click()");
      const searchVisible = "[...document.querySelectorAll('#searchbox,.unified-search__input,#unified-search input,.unified-search input,input[type=\"search\"],[role=\"dialog\"] input')].some(e=>e.getBoundingClientRect().height>0&&getComputedStyle(e).visibility==='visible')";
      await wait(searchVisible, 'Global Search launchpad action');
      await reload();
      await wait("!!document.querySelector('.agl-organogram') && document.querySelectorAll('.news-item').length===3", 'search restored homepage');
      await cdp.send('Input.dispatchKeyEvent', {type:'keyDown',key:'k',code:'KeyK',modifiers:2});
      await cdp.send('Input.dispatchKeyEvent', {type:'keyUp',key:'k',code:'KeyK',modifiers:2});
      await wait(searchVisible, 'Ctrl K shortcut');
      record('admin: Global Search launchpad and keyboard shortcut opened native search');
      await reload();
      await wait("!!document.querySelector('.agl-organogram')", 'search shortcut restored homepage');
      await click('Edit page');
      await wait("!!document.querySelector('.page-title-input')", 'native editor');
      assert(await evaluate("!![...document.querySelectorAll('button')].find(e=>e.textContent.trim()==='Save')"));
      await click('Cancel');
      await wait("!!document.querySelector('.agl-organogram') && !document.querySelector('.page-title-input')", 'reader restored');
      record('admin: native editor and Cancel preserved');
      await evaluate("document.querySelector('[aria-label=\"Page structure\"]').click()");
      await wait("!!document.querySelector('.page-tree-panel')", 'structure panel');
      await evaluate("document.querySelector('[aria-label=\"Page structure\"]').click()");
      // Native create/edit/settings/save/reload/delete on a temporary page only.
      const cleanupId = process.argv.find(arg=>arg.startsWith('--cleanup-page='))?.split('=')[1];
      if (cleanupId) {
        const cleaned = await evaluate(`(async()=>{const url=OC.generateUrl('/apps/intravox/api/pages/'+${JSON.stringify(cleanupId)});const p=await(await fetch(url)).json();if(p.uniqueId!==${JSON.stringify(cleanupId)}||p.title!=='Sovereign QA persistence'||p.layout?.rows?.[2]?.widgets?.[0]?.title!=='QA Executive structure')return false;const r=await fetch(url,{method:'DELETE',headers:{requesttoken:OC.requestToken}});return r.ok})()`);
        assert(cleaned, 'Only a verified earlier QA fixture was removed');
      }
      qaPageId = `page-qa-${Date.now()}`;
      const created = await evaluate(`(async()=>{
        const home=await (await fetch(OC.generateUrl('/apps/intravox/api/pages/page-home-en'))).json();
        const layout=structuredClone(home.layout);
        Object.assign(layout.rows[2].widgets[0],{title:'QA Executive structure',defaultExpanded:true,showCompanies:true});
        const r=await fetch(OC.generateUrl('/apps/intravox/api/pages'),{method:'POST',
          headers:{requesttoken:OC.requestToken,'Content-Type':'application/json'},body:JSON.stringify({
            id:${JSON.stringify(qaPageId)},uniqueId:${JSON.stringify(qaPageId)},title:'Sovereign QA persistence',
            language:'en',status:'published',layout,hidePageTitleBar:true,disableSocial:true,
            settings:{allowReactions:null,allowComments:null,allowCommentReactions:null}})});
        return {status:r.status,body:await r.json()};
      })()`);
      assert(created.status >= 200 && created.status < 300, `Temporary page created: ${created.status}`);
      assert(created.body.uniqueId && created.body.uniqueId !== qaPageId, 'Server minted the page identity');
      qaPageId = created.body.uniqueId;
      await cdp.send('Page.navigate', { url: `${base}/index.php/apps/intravox/#${qaPageId}` });
      await wait("document.querySelector('.agl-organogram')?.innerText.includes('QA Executive structure')", 'temporary organogram config');
      await evaluate("document.querySelector('.header-right .action-item__menutoggle').click()");
      await click('Page settings');
      await wait("!!document.querySelector('.page-settings')", 'page settings');
      await evaluate("document.querySelector('.page-settings input[type=\"checkbox\"]').click()");
      await mouseClick('.page-settings .tri-state-select .vs__dropdown-toggle');
      await wait("[...document.querySelectorAll('[role=\"option\"]')].some(e=>e.textContent.trim()==='Enabled')", 'social policy options');
      await evaluate("[...document.querySelectorAll('[role=\"option\"]')].find(e=>e.textContent.trim()==='Enabled').click()");
      await evaluate("[...document.querySelectorAll('[role=\"dialog\"] button')].find(e=>e.textContent.trim()==='Save').click()");
      await wait("!document.querySelector('.page-settings')", 'settings saved');
      const tempUrl = `/apps/intravox/api/pages/${qaPageId}`;
      await wait(`(async()=>{const p=await(await fetch(OC.generateUrl(${JSON.stringify(tempUrl)}))).json();return p.hidePageTitleBar===false&&p.disableSocial===false})()`, 'social and title policy persisted');
      await reload();
      await wait("!!document.querySelector('.agl-organogram') && document.querySelector('.intravox-header').getBoundingClientRect().height>0", 'expanded title policy after reload');
      await click('Edit page');
      await wait("!!document.querySelector('.page-title-input')", 'temporary page editor');
      await evaluate("const i=document.querySelector('.page-title-input');i.value='Sovereign QA saved';i.dispatchEvent(new Event('input',{bubbles:true}))");
      await click('Save');
      await wait("!!document.querySelector('.agl-organogram') && !document.querySelector('.page-title-input')", 'native Save completed');
      const saved = await evaluate(`(async()=>await(await fetch(OC.generateUrl(${JSON.stringify(tempUrl)}))).json())()`);
      assert.equal(saved.title, 'Sovereign QA saved');
      assert.equal(saved.disableSocial, false); assert.equal(saved.hidePageTitleBar, false);
      assert.equal(saved.settings.allowReactions, null); assert.equal(saved.settings.allowComments, null);
      assert.equal(saved.layout.rows[2].widgets[0].title, 'QA Executive structure');
      assert.equal(saved.layout.rows[2].widgets[0].defaultExpanded, true);
      assert.equal(saved.layout.rows[1].widgets[1].items[6].fallbackAppId, 'forms');
      assert.equal(saved.layout.rows[1].widgets[3].items[7].action, 'organogram-matrix');
      record('admin: page settings and organogram configuration survived native Save and reload');
      const deleted = await evaluate(`(async()=>{const r=await fetch(OC.generateUrl(${JSON.stringify(tempUrl)}),{method:'DELETE',headers:{requesttoken:OC.requestToken}});return r.status})()`);
      assert(deleted >= 200 && deleted < 300, 'Temporary page deleted');
      qaPageId = undefined;
      await cdp.send('Page.navigate', { url: `${base}/index.php/apps/intravox/#page-home-en` });
      await wait("!!document.querySelector('.agl-organogram')", 'unchanged homepage restored');
    } else {
      assert.equal(await evaluate("!!document.querySelector('[aria-label=\"Edit this page\"]')"), false);
    }
    assert(!cdp.diagnostics.some(x => typeof x === 'string' && /TypeError|ReferenceError/.test(x)), 'No JavaScript runtime failures');
    assert(preview.context.height<80 && !preview.overflow, 'Compact embedded Preview width');
    await evaluate("document.querySelector('.intravox-topbar').scrollIntoView({block:'start'})");
    await cdp.send('Emulation.setDeviceMetricsOverride', { width: 390, height: 844, deviceScaleFactor: 1, mobile: true });
    await pause(500);
    assert.equal(await evaluate("document.documentElement.scrollWidth>innerWidth"), false);
    const mobile = await cdp.send('Page.captureScreenshot', { format: 'png' });
    fs.writeFileSync(path.join(out, `${role}-mobile.png`), Buffer.from(mobile.data, 'base64'));
    record(`${role}: mobile layout without horizontal overflow`);
  } finally {
    if (qaPageId && cdp) {
      await cdp.send('Runtime.evaluate', { expression: `fetch(OC.generateUrl('/apps/intravox/api/pages/${qaPageId}'),{method:'DELETE',headers:{requesttoken:OC.requestToken}}).then(r=>r.status)`, awaitPromise:true }).catch(()=>{});
    }
    cdp?.close(); chrome?.kill('SIGTERM');
    await pause(300);
    occ('user:delete', uid);
    fs.rmSync(profile, { recursive: true, force: true });
  }
}

(async () => {
  let passed = false;
  try {
    const only = process.argv.find(arg=>arg.startsWith('--role='))?.split('=')[1];
    assert(!only || ['admin','reader','excom'].includes(only), 'Known QA role');
    if (!only || only === 'admin') await browser('admin', 'admin', 9333);
    if (!only || only === 'reader') await browser('reader', 'IntraVox Users', 9334);
    if (!only || only === 'excom') await browser('excom', 'IntraVox Users', 9335);
    passed = true;
    console.log('PASS all authenticated Sovereign checks');
  } finally {
    fs.writeFileSync(path.join(out, 'verification.json'), JSON.stringify({ checkedAt: new Date().toISOString(), passed, results }, null, 2));
  }
})().catch(e => { console.error(e.message); process.exitCode = 1; });
