#!/usr/bin/env node
// Generate a self-contained private review from existing authenticated evidence.
// Does not run tests, sign packages, publish files, or modify the app/runtime.
const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
const {execFileSync} = require('node:child_process');

const root = path.resolve(__dirname, '../..');
const qa = path.join(root, 'generated-artifacts/sovereign-qa');
const evidence = JSON.parse(fs.readFileSync(path.join(qa, 'verification.json'), 'utf8'));
if (!evidence.passed || !evidence.results.length) throw new Error('Review requires passing authenticated evidence');
const version = require('../../package.json').version;
const archive = path.join(root, `build/intravox-${version}.tar.gz`);
const releaseLogs = path.join(root, 'generated-artifacts/release');
const buildLog = fs.readFileSync(path.join(releaseLogs, 'portal-v1-gm-build.log'), 'utf8');
const unitLog = fs.readFileSync(path.join(releaseLogs, 'portal-v1-gm-unit-static.log'), 'utf8');
const packageLog = fs.readFileSync(path.join(releaseLogs, 'portal-v1-gm-package.log'), 'utf8');
if (!buildLog.includes('compiled successfully') || /\bWARNING\b/i.test(buildLog)) throw new Error('Review requires a successful warning-free build log');
if (!unitLog.includes('unit gate green') || unitLog.includes('FAILED')) throw new Error('Review requires a successful unit/static log');
if (!packageLog.includes('Package check passed')) throw new Error('Review requires a successful package log');
const sha256 = file => crypto.createHash('sha256').update(fs.readFileSync(file)).digest('hex');
const escape = value => String(value).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const source = execFileSync('git', ['rev-parse', 'HEAD'], {cwd:root, encoding:'utf8'}).trim();
const dirty = execFileSync('git', ['status', '--porcelain'], {cwd:root, encoding:'utf8'}).trim();
const figures = [
  ['admin-top-light', 'Light: compact badges, search grouping and Athena plum header'],
  ['admin-middle-light', 'Light: centered executive badges and amber ExCom treatment'],
  ['admin-footer-light', 'Light: governance matrix and compliance footer clearance'],
  ['admin-top-dark', 'Dark: compact badges, search grouping and Athena plum header'],
  ['admin-middle-dark', 'Dark: centered executive badges and amber ExCom treatment'],
  ['admin-footer-dark', 'Dark: governance matrix and compliance footer clearance'],
  ['admin-mobile-light', 'Mobile light: both clocks and the shortcut remain inside the hero; native Cancel toast may be visible'],
  ['admin-mobile-dark', 'Mobile dark: responsive hero after native editor/settings tests'],
].map(([name, caption]) => `<figure><img loading="lazy" src="data:image/png;base64,${fs.readFileSync(path.join(qa, `${name}.png`)).toString('base64')}" alt="${escape(caption)}"><figcaption>${escape(caption)}</figcaption></figure>`).join('\n');
const html = `<!doctype html><html lang="en"><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sovereign 365 Portal — final-polish candidate review</title>
<style>body{font:16px/1.6 system-ui,sans-serif;max-width:1440px;margin:32px auto;padding:0 20px;background:#f8fafc;color:#23132b}h1,h2{color:#4b164c;line-height:1.2}code{overflow-wrap:anywhere}figure{margin:32px 0;padding:16px;background:#fff;border:1px solid #ddd;border-radius:12px}img{max-width:100%;height:auto;display:block;margin:auto}figcaption{margin-top:12px}.notice{padding:18px;background:#fff2cf;border-left:4px solid #f59e0b}</style>
<h1>Sovereign 365 Portal v1.0 — final-polish candidate</h1>
<p class="notice">For owner/downstream review. Not Gold Master sign-off. No release tag, signing, deployment or mirror change is implied. Nextcloud app version remains <strong>${escape(version)}</strong>.</p>
<h2>Verification record</h2>
<ul>
<li>Authenticated browser evidence: <strong>${evidence.results.length} passing records</strong>, ${escape(evidence.checkedAt)}, admin/reader/ExCom accounts. The suite includes desktop/mobile light/dark badge sizing/alignment, actual icon/label/shortcut bounds, native header colour and footer clearance.</li>
<li>Source at generation: <code>${escape(source)}</code>${dirty ? ' — working tree has changes; this preview is not a clean-source attestation.' : ' — clean checkout at generation.'} This records provenance at generation, not an automatic same-SHA attestation of earlier test logs.</li>
<li>Unit/static gate passed with integration explicitly skipped; full integration and Portal-specific downstream Jenkins/Sonar qualification are separate and not claimed.</li>
<li>Production webpack build passed with zero warnings. Unsigned package and package-leak regression checks passed. Original approved 52-check evidence and HTML remain separately preserved.</li>
<li>Supported local theming persisted primary_color #4B164C. CSS fallback is Portal-scoped. No production settings were changed.</li>
<li>Jenkins has no rsync/signing step: positive staging allowlist, explicit archive exclusion and content guard reject generated QA files.</li>
</ul>
<h2>Candidate fingerprints</h2>
<p>Unsigned <code>build/intravox-${escape(version)}.tar.gz</code>: <code>${sha256(archive)}</code></p>
<p>Current <code>generated-artifacts/sovereign-qa/verification.json</code>: <code>${sha256(path.join(qa, 'verification.json'))}</code></p>
<p>Original approved evidence: <code>generated-artifacts/sovereign-qa-approved/verification.json</code> and <code>portal-v1-ux-review.html</code>. Review files and raw evidence stay outside Git.</p>
<h2>Current authenticated captures</h2>${figures}</html>`;
const output = path.join(qa, 'portal-v1-gm-review.html');
fs.writeFileSync(output, html);
console.log(`Wrote ${path.relative(root, output)} (${Buffer.byteLength(html)} bytes)`);
