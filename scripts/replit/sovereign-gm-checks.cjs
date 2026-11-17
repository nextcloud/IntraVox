const assert = require('node:assert/strict');

// Computed geometry catches both flex stretching and icon-wrapper regressions.
async function verifyGoldMasterDetails({evaluate, record}, mode, viewport) {
  const details = await evaluate(`(() => {
    const rect = element => {
      const r = element.getBoundingClientRect();
      return {left:r.left,right:r.right,width:r.width,top:r.top,bottom:r.bottom};
    };
    const badges = [...document.querySelectorAll('.news-item .agl-entity-badge,.link-item--tile .agl-entity-badge')].map(e => {
      const s = getComputedStyle(e);
      return {
        entity:e.dataset.entity, news:!!e.closest('.news-item'),
        box:rect(e), parent:rect(e.parentElement),
        align:s.alignSelf, display:s.display, border:s.borderTopColor,
        tint:s.backgroundColor,
      };
    });
    const search = document.querySelector('.sovereign-hero__search');
    const icon = search.querySelector('.sovereign-hero__search-icon');
    const label = search.querySelector('.sovereign-hero__search-label');
    const kbd = search.querySelector('kbd');
    const footer = document.querySelector('.intravox-footer');
    const content = footer.querySelector('.footer-content');
    const line = document.createRange();
    line.selectNodeContents(content);
    return {
      badges, search:rect(search), hero:rect(search.closest('.sovereign-hero')),
      clocks:rect(document.querySelector('.sovereign-hero__clocks')),
      icon:rect(icon), label:rect(label), kbd:rect(kbd),
      header:getComputedStyle(document.querySelector('#header')).backgroundColor,
      footerPadding:parseFloat(getComputedStyle(footer).paddingBottom),
      footerClearance:rect(footer).bottom-line.getBoundingClientRect().bottom,
      footerText:content.textContent,
    };
  })()`);
  assert(details.badges.filter(b => b.news).length >= 3, 'Announcement badges exist');
  assert(details.badges.some(b => !b.news), 'Executive card badges exist');
  for (const badge of details.badges) {
    assert(badge.box.width < badge.parent.width - 12, `Content-sized ${badge.entity} badge`);
    // A flex parent's item is blockified: specified inline-flex computes to flex.
    assert(['inline-flex', 'flex'].includes(badge.display), 'Badge retains a flex formatting context');
    assert.equal(badge.align, badge.news ? 'flex-start' : 'center');
    if (badge.news) {
      assert(Math.abs(badge.box.left - badge.parent.left) < 3, 'Announcement badge left aligned');
    } else {
      assert(Math.abs((badge.box.left + badge.box.right - badge.parent.left - badge.parent.right) / 2) < 3, 'Executive badge centered');
    }
    if (badge.entity === 'excom') assert.equal(badge.border, 'rgb(245, 158, 11)', 'Amber ExCom border');
    assert.notEqual(badge.tint, 'rgba(0, 0, 0, 0)', 'Entity tint is visible');
  }
  assert.equal(details.header, 'rgb(75, 22, 76)', 'Native top bar stays Athena plum');
  assert(details.icon.width <= 21, 'Search icon wrapper cannot stretch');
  assert(details.icon.left - details.search.left < 18, 'Search icon starts on the left');
  assert(Math.abs(details.label.left - details.icon.right - 10) < 3, 'Icon and label have a 10px gap');
  assert(details.kbd.left >= details.label.right - 1, 'Shortcut follows label without overlap');
  assert(details.search.right - details.kbd.right < 18, 'Only shortcut stays at the far right');
  assert(details.search.right <= details.hero.right - 10, 'Search fits inside the hero, not an overflow-clipping ancestor');
  assert(details.clocks.right <= details.hero.right - 10, 'Both clocks fit inside the hero');
  assert(details.footerPadding >= 20, 'Portal footer has 20px bottom padding');
  assert(details.footerClearance >= 20, 'Compliance text clears the footer bottom');
  assert(details.footerText.includes('Compliance baseline:'), 'Compliance line retained');
  record(`admin: ${mode} ${viewport} compact badges, search alignment, plum header and footer clearance`, details);
}

module.exports = {verifyGoldMasterDetails};
