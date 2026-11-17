const assert = require('node:assert/strict');
const {verifyGoldMasterDetails} = require('./sovereign-gm-checks.cjs');

function contrast(foreground, background) {
  const luminance = css => {
    const rgb = css.match(/[\d.]+/g).slice(0, 3).map(Number).map(n => {
      const s = n / 255;
      return s <= .04045 ? s / 12.92 : ((s + .055) / 1.055) ** 2.4;
    });
    return rgb[0] * .2126 + rgb[1] * .7152 + rgb[2] * .0722;
  };
  const values = [luminance(foreground), luminance(background)].sort((a, b) => b - a);
  return (values[0] + .05) / (values[1] + .05);
}

async function verifyFinalTheme(ctx, mode) {
  const {evaluate, wait, click, cdp, screenshot, reload, base, record, mouseClick} = ctx;
  const home = async () => {
    await cdp.send('Page.navigate', {url:base+'/index.php/apps/intravox/#page-home-en'});
    await wait("!!document.querySelector('.sovereign-hero') && document.querySelectorAll('.news-item').length===3", 'final UX homepage');
  };
  const readable = async selector => {
    const colors = await evaluate(`(()=>{const e=document.querySelector(${JSON.stringify(selector)});if(!e||!e.getBoundingClientRect().height)return null;let p=e,bg;while(p){const s=getComputedStyle(p),c=s.backgroundColor;const v=c.match(/[\\d.]+/g)?.map(Number);if(v&&(v.length===3||v[3]>.98)){bg=c;break}if(s.backgroundImage.startsWith('linear-gradient')){bg=s.backgroundImage.match(/rgba?\\([^)]+\\)/)?.[0];if(bg)break}p=p.parentElement}return {fg:getComputedStyle(e).color,bg:bg||getComputedStyle(document.querySelector('#intravox-app')).backgroundColor}})()`);
    assert(colors, `Visible ${selector}`);
    assert(contrast(colors.fg, colors.bg) >= 4.5, `AA text contrast for ${selector}: ${JSON.stringify(colors)}`);
    return colors;
  };
  await evaluate("(()=>{let e=document.querySelector('.sovereign-hero');while(e){e.scrollTop=0;e=e.parentElement}window.scrollTo(0,0)})()");
  await verifyGoldMasterDetails(ctx, mode, 'desktop');
  await screenshot(`admin-top-${mode}`);
  await readable('.sovereign-hero__search');
  await evaluate("document.querySelector('.sovereign-hero__search').click()");
  await wait("[...document.querySelectorAll('.unified-search-modal input,#unified-search input,.unified-search input,input[type=\"search\"],[role=\"dialog\"] input')].some(e=>e.getBoundingClientRect().height>0)", 'hero opened native Unified Search');
  await reload();
  await wait("!!document.querySelector('.sovereign-hero')", 'hero search restored');

  const grids = await evaluate(`[...document.querySelectorAll('.links-widget')].map(g=>{const r=[...g.querySelectorAll('.link-item')].map(e=>e.getBoundingClientRect());return{count:r.length,rows:new Set(r.map(x=>Math.round(x.top))).size,min:Math.min(...r.map(x=>x.height)),max:Math.max(...r.map(x=>x.height))}})`);
  assert.equal(grids[0].count, 8); assert.equal(grids[0].rows, 4);
  assert.equal(grids[1].count, 8); assert.equal(grids[1].rows, 4);
  assert(grids.every(g=>g.max-g.min<2), `Each launchpad has uniform card heights: ${JSON.stringify(grids)}`);
  await evaluate("document.querySelector('[data-sovereign-row=\"sovereign-workspaces\"]').scrollIntoView({behavior:'instant',block:'start'})");
  await screenshot(`admin-middle-${mode}`);
  const hashBefore = await evaluate('location.hash');
  await evaluate("document.querySelector('.link-item[href=\"#organogram-matrix\"]').click()");
  await wait("document.activeElement?.id==='organogram-matrix'", 'matrix jump focused the organogram');
  await wait("(()=>{const r=document.querySelector('.agl-organogram').getBoundingClientRect();return r.top>=document.querySelector('.intravox-topbar').getBoundingClientRect().bottom&&r.top<innerHeight-200})()", 'matrix heading visible below the sticky header');
  assert.equal(await evaluate('location.hash'), hashBefore, 'Matrix jump does not invoke page routing');
  assert(await evaluate("document.querySelector('.agl-organogram__companies').innerText.includes('MIRRORED EXCOM GOVERNANCE')"));
  assert(await evaluate("document.querySelector('.agl-organogram__columns').innerText.includes('Inside & Outside Sales')"));
  await screenshot(`admin-bottom-${mode}`);
  await evaluate("document.querySelector('.intravox-footer').scrollIntoView({behavior:'instant',block:'end'})");
  await screenshot(`admin-footer-${mode}`);

  if (mode === 'light') {
    const destinations = {
      'page-sovereign-commercial':'Commercial & Ops',
      'page-sovereign-engineering':'Engineering & C3',
      'page-sovereign-finance':'Finance & Treasury',
      'page-sovereign-technology':'Technology & Product',
      'page-sovereign-governance':'Governance & Legal',
      'page-a1000002-0002-4000-8000-000000000021':'Nassau C3',
      'page-a1000002-0002-4000-8000-000000000022':'Athens C3',
    };
    for (const [id, title] of Object.entries(destinations)) {
      await evaluate(`document.querySelector('.agl-organogram a[href="#${id}"]').click()`);
      await wait(`[...document.querySelectorAll('#intravox-main-content h2')].some(e=>e.innerText.includes(${JSON.stringify(title)}))`, `organogram destination ${title}`);
      await home();
    }
  }
  await evaluate("document.querySelector('.intravox-topbar').scrollIntoView({behavior:'instant',block:'start'})");
  const point = await evaluate("(()=>{const e=[...document.querySelectorAll('.megamenu-trigger,.dropdown-trigger')].find(e=>e.textContent.trim()==='Engineering & C3');const r=e.getBoundingClientRect();return{x:r.x+r.width/2,y:r.y+r.height/2}})()");
  await cdp.send('Input.dispatchMouseEvent', {type:'mouseMoved',...point});
  await wait("[...document.querySelectorAll('.megamenu-dropdown,.dropdown-menu')].some(e=>e.getBoundingClientRect().height>0)", 'Engineering & C3 dropdown');
  const menuColors = await readable('.megamenu-dropdown a,.dropdown-menu a');
  await screenshot(`admin-dropdown-${mode}`);
  const appsPoint = await evaluate("(()=>{const e=[...document.querySelectorAll('.megamenu-trigger,.dropdown-trigger')].find(e=>e.textContent.trim()==='Sovereign 365 Apps');const r=e.getBoundingClientRect();return{x:r.x+r.width/2,y:r.y+r.height/2}})()");
  await cdp.send('Input.dispatchMouseEvent', {type:'mouseMoved',...appsPoint});
  await wait("!!document.querySelector('.megamenu-dropdown [aria-disabled=\"true\"][title],.dropdown-menu [aria-disabled=\"true\"][title]')", 'application availability tooltips');
  assert.equal(await evaluate("[...document.querySelectorAll('.megamenu-dropdown,.dropdown-menu')].some(e=>/Not enabled|Availability unconfirmed/.test(e.innerText))"),false,'No visible availability suffixes in app navigation');
  await cdp.send('Input.dispatchMouseEvent', {type:'mouseMoved',x:700,y:400});
  await evaluate("[...document.querySelectorAll('.link-tile-title')].find(e=>e.textContent.trim()==='COO — Commercial & Ops').closest('a').click()");
  await wait("[...document.querySelectorAll('#intravox-main-content h2')].some(e=>e.innerText.includes('Commercial & Ops'))", 'COO sub-page');
  await screenshot(`admin-subpage-${mode}`);
  await click('Edit page');
  await wait("!!document.querySelector('.page-title-input')", `${mode} native editor`);
  const editorColors = await readable('.page-title-input');
  await screenshot(`admin-editor-${mode}`);
  await click('Cancel');
  await wait("!document.querySelector('.page-title-input')", 'sub-page editor cancelled');
  await evaluate("document.querySelector('.header-right .action-item__menutoggle').click()");
  await click('Page settings');
  await wait("!!document.querySelector('.page-settings')", `${mode} page settings`);
  const modalColors = await readable('.page-settings');
  await screenshot(`admin-settings-${mode}`);
  await mouseClick('.page-settings .tri-state-select .vs__dropdown-toggle');
  await wait("!!document.querySelector('[role=\"option\"]')", 'settings select options');
  await readable('[role="option"]');
  await screenshot(`admin-settings-options-${mode}`);
  await click('Cancel');
  await wait("!document.querySelector('.page-settings')", 'settings modal closed');
  await home();
  record(`admin: ${mode} final grids, hero search, matrix links, dropdown, editor and modal contrast`, {grids,menuColors,editorColors,modalColors});
}

module.exports = {verifyFinalTheme};
