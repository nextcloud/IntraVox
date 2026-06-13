#!/usr/bin/env node
/**
 * Generate l10n .js files from .json files
 *
 * Converts the Nextcloud l10n JSON format to the JavaScript format
 * that Nextcloud loads at runtime (`OC.L10N.register("intravox", {...}, "<plural-form>")`).
 *
 * Plural-form selection:
 *   1. If the source JSON has a `pluralForm` field, use it verbatim (this is what the
 *      NC Transifex bot writes).
 *   2. Otherwise fall back to PLURAL_OVERRIDES[lang] for languages whose plural rule
 *      diverges from the default — JA/KO/ZH have only 1 form, FR/PT treat 0 as singular,
 *      AR has six forms, PL/RU/CS each have their own complex rule. Without these
 *      overrides those languages render bizarrely (e.g. always-singular for ZH,
 *      "1 minuut, 2 minuten" patterns for Polish, etc.).
 *   3. Otherwise the safe binary default (English-like languages).
 *
 * Lesson ported from IntroVox `regenerate_js_translations.py` (PLURAL_OVERRIDES).
 *
 * Usage: node scripts/generate-l10n.js
 */

const fs = require('fs');
const path = require('path');

const l10nDir = path.join(__dirname, '..', 'l10n');
const appId = 'intravox';

const DEFAULT_PLURAL = 'nplurals=2; plural=(n != 1);';

// Plural rules per language (base code). Used only when the source JSON
// has no explicit `pluralForm` field. Ported from IntroVox/regenerate_js_translations.py.
const PLURAL_OVERRIDES = {
    // 1 form: every count uses the same word.
    'ja':    'nplurals=1; plural=0;',
    'ko':    'nplurals=1; plural=0;',
    'zh':    'nplurals=1; plural=0;',
    'zh_CN': 'nplurals=1; plural=0;',
    'zh_HK': 'nplurals=1; plural=0;',
    'zh_TW': 'nplurals=1; plural=0;',
    'th':    'nplurals=1; plural=0;',
    'vi':    'nplurals=1; plural=0;',
    'id':    'nplurals=1; plural=0;',
    // 2 forms, 0 is singular (vs English's 0 is plural).
    'fr':    'nplurals=2; plural=(n > 1);',
    'pt':    'nplurals=2; plural=(n > 1);',
    'pt_BR': 'nplurals=2; plural=(n > 1);',
    // 3 forms (Slavic).
    'pl':    'nplurals=3; plural=(n==1 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2);',
    'ru':    'nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2);',
    'uk':    'nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2);',
    'cs':    'nplurals=3; plural=(n==1) ? 0 : (n>=2 && n<=4) ? 1 : 2;',
    'sk':    'nplurals=3; plural=(n==1) ? 0 : (n>=2 && n<=4) ? 1 : 2;',
    // 4 forms (Slovenian).
    'sl':    'nplurals=4; plural=(n%100==1 ? 0 : n%100==2 ? 1 : n%100==3 || n%100==4 ? 2 : 3);',
    // 6 forms (Arabic).
    'ar':    'nplurals=6; plural=n==0 ? 0 : n==1 ? 1 : n==2 ? 2 : n%100>=3 && n%100<=10 ? 3 : n%100>=11 ? 4 : 5;',
};

function pluralFormFor(lang, dataPluralForm) {
    if (typeof dataPluralForm === 'string' && dataPluralForm.length > 0) {
        return dataPluralForm;
    }
    // Try exact match first, then base-language code (en_GB -> en).
    if (PLURAL_OVERRIDES[lang]) return PLURAL_OVERRIDES[lang];
    const base = lang.split('_')[0];
    if (PLURAL_OVERRIDES[base]) return PLURAL_OVERRIDES[base];
    return DEFAULT_PLURAL;
}

const jsonFiles = fs.readdirSync(l10nDir).filter(f => f.endsWith('.json'));

console.log('Generating l10n JavaScript files...');

for (const jsonFile of jsonFiles) {
    const lang = path.basename(jsonFile, '.json');
    const jsonPath = path.join(l10nDir, jsonFile);
    const jsPath = path.join(l10nDir, `${lang}.js`);

    try {
        const jsonContent = fs.readFileSync(jsonPath, 'utf8');
        const data = JSON.parse(jsonContent);

        const translations = data.translations || {};
        const pluralForm = pluralFormFor(lang, data.pluralForm);

        const jsContent = `OC.L10N.register("${appId}",${JSON.stringify(translations)}, "${pluralForm}")\n`;
        fs.writeFileSync(jsPath, jsContent);

        const keyCount = Object.keys(translations).length;
        const overrideTag = (PLURAL_OVERRIDES[lang] || PLURAL_OVERRIDES[lang.split('_')[0]]) && !data.pluralForm ? ' [override]' : '';
        console.log(`  ✓ ${lang}.js (${keyCount} translations)${overrideTag}`);
    } catch (error) {
        console.error(`  ✗ ${lang}: ${error.message}`);
    }
}

console.log('Done!');
