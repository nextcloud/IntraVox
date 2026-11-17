const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync(`${__dirname}/preview-storage.js`, 'utf8');

async function browser({ embedded = true, supported = true, allowed = false, denied = false } = {}) {
    let onClick, requests = 0;
    const destinations = [];
    const button = { disabled: false, addEventListener: (_, callback) => { onClick = callback; } };
    const status = { textContent: '' };
    const document = {
        getElementById: id => id === 'connect-session' ? button : status,
        ...(supported ? {
            hasStorageAccess: async () => allowed,
            requestStorageAccess: async () => {
                requests++;
                if (denied) throw new Error('blocked');
            },
        } : {}),
    };
    const window = { self: {}, top: {}, location: { replace: url => destinations.push(url) } };
    if (!embedded) window.top = window.self;
    vm.runInNewContext(source, { document, window });
    await Promise.resolve();
    await Promise.resolve();
    return { button, status, destinations, requests: () => requests, click: () => onClick?.() };
}

(async () => {
    let page = await browser();
    assert.equal(page.destinations.length, 0, 'must not navigate before consent');
    assert.equal(page.requests(), 0, 'must not request permission without a gesture');
    await page.click();
    assert.equal(page.requests(), 1);
    assert.match(page.destinations[0], /^\/index\.php\/login\?preview_storage=1/);
    page = await browser({ allowed: true });
    assert.equal(page.destinations.length, 1, 'existing permission resumes automatically');
    page = await browser({ denied: true });
    await page.click();
    assert.equal(page.destinations.length, 0, 'denial cannot claim authentication');
    assert.equal(page.button.disabled, false, 'denial remains retryable');
    assert.match(page.status.textContent, /blocked/);
    page = await browser({ supported: false });
    assert.equal(page.button.disabled, true);
    assert.equal(page.destinations.length, 0);
    page = await browser({ embedded: false });
    await page.click();
    assert.equal(page.requests(), 0);
    assert.equal(page.destinations.length, 1);
    assert.doesNotMatch(source, /document\.cookie|localStorage|sessionStorage|postMessage/);
    console.log('Preview storage consent: 15 checks passed');
})().catch(error => { console.error(error); process.exitCode = 1; });
