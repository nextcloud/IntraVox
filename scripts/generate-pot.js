#!/usr/bin/env node
/**
 * Generate translationfiles/templates/intravox.pot using Nextcloud's official
 * translationtool.phar (the same tool the NC Transifex sync-bot runs).
 *
 * Using the official tool guarantees that what we ship in the POT exactly
 * matches what the sync-bot will extract — no drift between local and remote.
 *
 * The tool:
 *   - reads .l10nignore for excluded paths,
 *   - concatenates all Vue/JS into a fake dummy file,
 *   - runs xgettext on PHP + JS sources,
 *   - writes translationfiles/templates/<appid>.pot with source-file refs.
 *
 * Usage:
 *   node scripts/generate-pot.js
 *
 * Replaces our older en.json-based extraction (which kept stale strings
 * that no longer exist in source). See RELEASE_CHECKLIST.md §2 for details.
 */

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');
const https = require('https');

const ROOT = path.resolve(__dirname, '..');
const PHAR_URL = 'https://raw.githubusercontent.com/nextcloud/docker-ci/master/translations/translationtool/translationtool.phar';
const PHAR_LOCAL = path.join(ROOT, 'scripts', '.cache', 'translationtool.phar');
const POT_PATH = path.join(ROOT, 'translationfiles', 'templates', 'intravox.pot');

async function downloadPhar() {
    return new Promise((resolve, reject) => {
        fs.mkdirSync(path.dirname(PHAR_LOCAL), { recursive: true });
        console.log(`Downloading ${PHAR_URL} …`);
        const file = fs.createWriteStream(PHAR_LOCAL);
        https.get(PHAR_URL, (res) => {
            // Follow one level of redirect.
            if (res.statusCode === 301 || res.statusCode === 302) {
                https.get(res.headers.location, (res2) => {
                    res2.pipe(file).on('finish', () => file.close(resolve));
                }).on('error', reject);
                return;
            }
            if (res.statusCode !== 200) {
                return reject(new Error(`HTTP ${res.statusCode} fetching translationtool.phar`));
            }
            res.pipe(file).on('finish', () => file.close(resolve));
        }).on('error', reject);
    });
}

async function main() {
    // Cache the phar locally — refresh if older than 7 days.
    let needDownload = !fs.existsSync(PHAR_LOCAL);
    if (!needDownload) {
        const ageMs = Date.now() - fs.statSync(PHAR_LOCAL).mtimeMs;
        needDownload = ageMs > 7 * 24 * 3600 * 1000;
    }
    if (needDownload) {
        await downloadPhar();
        console.log(`Saved to ${PHAR_LOCAL}`);
    } else {
        console.log(`Using cached ${PHAR_LOCAL}`);
    }

    // Run the NC tool. It writes its output to translationfiles/templates/<appid>.pot
    // relative to the current working directory.
    console.log('Running NC translationtool.phar create-pot-files …');
    try {
        const stdout = execSync(`php ${PHAR_LOCAL} create-pot-files`, {
            cwd: ROOT,
            stdio: ['ignore', 'pipe', 'inherit'],
        }).toString();
        // The tool also prints warnings (embedded URLs, regex literals in vue
        // templates xgettext doesn't grok) — useful but not fatal.
        if (stdout) process.stdout.write(stdout);
    } catch (e) {
        console.error('translationtool.phar failed:', e.message);
        process.exit(1);
    }

    // Report what we got.
    if (!fs.existsSync(POT_PATH)) {
        console.error(`Expected ${POT_PATH} to exist after run`);
        process.exit(1);
    }
    const pot = fs.readFileSync(POT_PATH, 'utf8');
    const msgidCount = [...pot.matchAll(/^msgid "/gm)].length - 1; // -1 for the empty header msgid
    console.log(`\nWrote ${POT_PATH}`);
    console.log(`Total msgids: ${msgidCount}`);
}

main().catch((e) => {
    console.error(e);
    process.exit(1);
});
