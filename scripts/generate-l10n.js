#!/usr/bin/env node
/**
 * Generate l10n .js files from .json files
 *
 * This script converts the Nextcloud l10n JSON format to the JavaScript format
 * that Nextcloud uses at runtime.
 *
 * Usage: node scripts/generate-l10n.js
 */

const fs = require('fs');
const path = require('path');

const l10nDir = path.join(__dirname, '..', 'l10n');
const appId = 'intravox';

// Get all JSON files in l10n directory
const jsonFiles = fs.readdirSync(l10nDir).filter(f => f.endsWith('.json'));

console.log('Generating l10n JavaScript files...');

for (const jsonFile of jsonFiles) {
    const lang = path.basename(jsonFile, '.json');
    const jsonPath = path.join(l10nDir, jsonFile);
    const jsPath = path.join(l10nDir, `${lang}.js`);

    try {
        // Read and parse JSON
        const jsonContent = fs.readFileSync(jsonPath, 'utf8');
        const data = JSON.parse(jsonContent);

        // Extract translations and plural form
        const translations = data.translations || {};
        const pluralForm = data.pluralForm || 'nplurals=2; plural=(n != 1);';

        // Generate JS content
        const jsContent = `OC.L10N.register("${appId}",${JSON.stringify(translations)}, "${pluralForm}")\n`;

        // Write JS file
        fs.writeFileSync(jsPath, jsContent);

        const keyCount = Object.keys(translations).length;
        console.log(`  ✓ ${lang}.js (${keyCount} translations)`);
    } catch (error) {
        console.error(`  ✗ ${lang}: ${error.message}`);
    }
}

console.log('Done!');
