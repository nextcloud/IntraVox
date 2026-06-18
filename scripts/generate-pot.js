#!/usr/bin/env node
/**
 * Generate translationfiles/templates/intravox.pot from l10n/en.json + PHP scan.
 *
 * Why this exists: `xgettext` chokes on Vue templates (HTML/directive syntax
 * looks like broken JS to it), so we can't run it against `src/`. But the
 * webpack build pipeline already extracts every `t('intravox', '...')` call
 * into l10n/en.json — that's the canonical set of frontend-translatable
 * strings. We use that as source-of-truth for the POT.
 *
 * PHP `->t('...')` calls live in lib/ and aren't captured by webpack. For
 * those we run xgettext on lib/**\/*.php and merge the result in.
 *
 * Usage:
 *   node scripts/generate-pot.js
 *
 * Output: translationfiles/templates/intravox.pot (overwrites previous)
 */

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const ROOT = path.resolve(__dirname, '..');
const EN_JSON = path.join(ROOT, 'l10n', 'en.json');
const POT = path.join(ROOT, 'translationfiles', 'templates', 'intravox.pot');

const VERSION = JSON.parse(fs.readFileSync(path.join(ROOT, 'package.json'), 'utf8')).version;

// Escape a string for inclusion in a PO `msgid "..."` line.
function escapePo(s) {
    return s
        .replace(/\\/g, '\\\\')
        .replace(/"/g, '\\"')
        .replace(/\n/g, '\\n')
        .replace(/\t/g, '\\t');
}

// Format one msgid/msgstr entry. PO format wraps long strings — for simplicity
// we emit single-line msgids; Transifex and the NC l10n bot both accept this.
// Plural entries (en.json array value [singular, plural]) emit a msgid_plural
// block with msgstr[0]/msgstr[1] — required so the `%n` placeholder is a real
// gettext plural and not an "undefined plural" (see NC translation dos-and-don'ts).
function poEntry(msgid) {
    return `msgid "${escapePo(msgid)}"\nmsgstr ""\n`;
}

function poPluralEntry(singular, plural) {
    return `msgid "${escapePo(singular)}"\n` +
        `msgid_plural "${escapePo(plural)}"\n` +
        `msgstr[0] ""\nmsgstr[1] ""\n`;
}

console.log('Generating POT from l10n/en.json + lib/**.php …');

// 1. Frontend strings from en.json (the canonical webpack-extracted set).
// Array values are plurals: { "%n result found": ["%n result found", "%n results found"] }.
const en = JSON.parse(fs.readFileSync(EN_JSON, 'utf8'));
const enTranslations = en.translations || {};
const frontendSingular = [];
const frontendPlural = []; // [singular, plural]
for (const [key, val] of Object.entries(enTranslations)) {
    if (Array.isArray(val)) {
        frontendPlural.push([val[0] || key, val[1] || val[0] || key]);
    } else {
        frontendSingular.push(key);
    }
}
const frontendStrings = frontendSingular;
console.log(`  Frontend strings (from en.json): ${frontendStrings.length} singular + ${frontendPlural.length} plural`);

// 2. PHP strings via xgettext on lib/.
let phpStrings = [];
try {
    const phpFilesList = '/tmp/intravox-pot-php-files.txt';
    execSync(`find lib/ -name "*.php" -type f > ${phpFilesList}`, { cwd: ROOT });
    const phpPotPath = '/tmp/intravox-pot-php.pot';
    execSync(
        `xgettext --language=PHP --from-code=UTF-8 --keyword=t:1 --keyword=n:1,2 -o ${phpPotPath} --files-from=${phpFilesList}`,
        { cwd: ROOT }
    );
    const phpPot = fs.readFileSync(phpPotPath, 'utf8');
    // Match every `msgid "..."` after the header, skipping the empty leading msgid.
    const matches = [...phpPot.matchAll(/^msgid "(.*)"$/gm)];
    phpStrings = matches.map(m => m[1]).filter(s => s.length > 0);
    console.log(`  PHP strings (from xgettext on lib/): ${phpStrings.length}`);
} catch (e) {
    console.warn('  xgettext failed — PHP strings will be missing from POT:', e.message);
}

// 3. Merge, dedupe, sort for deterministic output.
const all = new Set([...frontendStrings, ...phpStrings]);
const sorted = [...all].sort((a, b) => a.localeCompare(b));
// Dedupe plurals by singular, sort deterministically.
const pluralBySingular = new Map(frontendPlural.map(([s, p]) => [s, p]));
const sortedPlurals = [...pluralBySingular.keys()].sort((a, b) => a.localeCompare(b));
console.log(`  Total unique strings: ${sorted.length} singular + ${sortedPlurals.length} plural`);

// 4. Build the POT.
const now = new Date().toISOString().replace('T', ' ').replace(/:\d+\.\d+Z$/, '+0000');
const header = `# IntraVox translation template.
# Copyright (C) ${new Date().getFullYear()} VoxCloud
# This file is distributed under the AGPL-3.0 license.
#
msgid ""
msgstr ""
"Project-Id-Version: IntraVox ${VERSION}\\n"
"Report-Msgid-Bugs-To: translations@nextcloud.com\\n"
"POT-Creation-Date: ${now}\\n"
"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\\n"
"Last-Translator: FULL NAME <EMAIL@ADDRESS>\\n"
"Language-Team: LANGUAGE <LL@li.org>\\n"
"Language: \\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"

`;

const singularBody = sorted.map(poEntry).join('\n');
const pluralBody = sortedPlurals
    .map(s => poPluralEntry(s, pluralBySingular.get(s)))
    .join('\n');
const body = [singularBody, pluralBody].filter(Boolean).join('\n');
fs.writeFileSync(POT, header + body);
console.log(`Wrote ${POT}`);
console.log(`Total msgids: ${sorted.length + sortedPlurals.length + 1} (incl. header)`);
