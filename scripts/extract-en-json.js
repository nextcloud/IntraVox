#!/usr/bin/env node
/**
 * Extract translatable strings into l10n/en.json — the canonical English source
 * that scripts/generate-pot.js turns into translationfiles/templates/intravox.pot.
 *
 * Why this exists: en.json is the source-of-truth for frontend (Vue/JS) strings,
 * but the Nextcloud Transifex sync-bot deletes it from the repo after each sync
 * (it is a build artifact, gitignored). It therefore has to be regenerated from
 * the code before every POT round. This script does that deterministically by
 * scanning the source for translation calls, so en.json is never hand-edited or
 * restored from git.
 *
 * What it scans:
 *   - src/**\/*.{vue,js}   t('intravox', '…')   this.t('…')   $t('…')   this.$t('…')
 *                          n('intravox', 's', 'p', …)   this.n(…)   $n('s', 'p', …)
 *   - lib/**\/*.php        ->t('…')             ->n('…', '…', …)            $l->t('…')
 *
 * Note: $t/$n are the Vue global aliases bound to the intravox appId in
 * src/main.js (translate('intravox', …)), so they carry NO 'intravox' first arg.
 *
 * Output structure (matches the NC bot's en.json exactly):
 *   { "translations": { "<msgid>": "<msgid>", "<singular>": ["<singular>", "<plural>"] } }
 *
 * Singular values map to themselves; plural entries map to a [singular, plural]
 * array — generate-pot.js already expects exactly this shape.
 *
 * Usage: node scripts/extract-en-json.js
 */

const fs = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '..');
const OUT = path.join(ROOT, 'l10n', 'en.json');

// A single-quoted JS/PHP string literal, allowing backslash escapes
// (e.g. \' and \\). Captured group 1 is the raw, still-escaped contents.
const STR = "'((?:[^'\\\\]|\\\\.)*)'";

// Translation-call patterns. Each yields singular (and plural where present).
//
// Two families:
//   1. t()/n()  — optional 'intravox' first arg. Forms: t('intravox','…'),
//      this.t('…'), $l->t('…'), ->t('…'), ->n('…','…').
//   2. $t()/$n() — Vue global aliases, string is the FIRST arg (no 'intravox').
//      Forms: $t('…'), this.$t('…'), $n('s','p',…).
//
// The (?<![\w$]) lookbehind before family 1 stops us matching the trailing `t`
// of an identifier (impor`t(`) or method (spli`t(`). Family 2 anchors on the
// literal `$t(`/`$n(` token, which can't be a false positive.
const T_PREFIX = `(?:this\\.|\\$\\w+->|->)?`;
const T_RE = new RegExp(`(?<![\\w$])${T_PREFIX}t\\(\\s*(?:'intravox'\\s*,\\s*)?${STR}`, 'g');
const N_RE = new RegExp(`(?<![\\w$])${T_PREFIX}n\\(\\s*(?:'intravox'\\s*,\\s*)?${STR}\\s*,\\s*${STR}`, 'g');
const DT_RE = new RegExp(`(?:this\\.)?\\$t\\(\\s*${STR}`, 'g');
const DN_RE = new RegExp(`(?:this\\.)?\\$n\\(\\s*${STR}\\s*,\\s*${STR}`, 'g');

// Un-escape a captured single-quoted literal into its real string value.
function unescapeLiteral(raw) {
	return raw.replace(/\\(['\\nt])/g, (_, ch) => {
		if (ch === 'n') return '\n';
		if (ch === 't') return '\t';
		return ch; // \' -> '   \\ -> \
	});
}

// Recursively collect files under dir matching one of the extensions.
function walk(dir, exts, acc = []) {
	let entries;
	try {
		entries = fs.readdirSync(dir, { withFileTypes: true });
	} catch (e) {
		return acc;
	}
	for (const entry of entries) {
		const full = path.join(dir, entry.name);
		if (entry.isDirectory()) {
			if (entry.name === 'node_modules' || entry.name === '.git') continue;
			walk(full, exts, acc);
		} else if (exts.some(ext => entry.name.endsWith(ext))) {
			acc.push(full);
		}
	}
	return acc;
}

const singulars = new Set();
const plurals = new Map(); // singular -> plural

const files = [
	...walk(path.join(ROOT, 'src'), ['.vue', '.js']),
	...walk(path.join(ROOT, 'lib'), ['.php']),
];

for (const file of files) {
	const content = fs.readFileSync(file, 'utf8');

	// Plurals first so we can record the pairing; their singular also counts.
	let m;
	for (const re of [N_RE, DN_RE]) {
		re.lastIndex = 0;
		while ((m = re.exec(content)) !== null) {
			const sing = unescapeLiteral(m[1]);
			const plur = unescapeLiteral(m[2]);
			if (sing) plurals.set(sing, plur);
		}
	}

	for (const re of [T_RE, DT_RE]) {
		re.lastIndex = 0;
		while ((m = re.exec(content)) !== null) {
			const s = unescapeLiteral(m[1]);
			if (s) singulars.add(s);
		}
	}
}

// Plurals take precedence: a string used in n() is an array entry, not a bare
// singular, so drop it from the singular set to avoid a duplicate key clash.
for (const sing of plurals.keys()) {
	singulars.delete(sing);
}

// Build the translations object, sorted for deterministic output.
const translations = {};
const sortedSingulars = [...singulars].sort((a, b) => a.localeCompare(b));
const sortedPlurals = [...plurals.keys()].sort((a, b) => a.localeCompare(b));

for (const s of sortedSingulars) {
	translations[s] = s;
}
for (const s of sortedPlurals) {
	translations[s] = [s, plurals.get(s)];
}

const out = { translations, pluralForm: 'nplurals=2; plural=(n != 1);' };
fs.mkdirSync(path.dirname(OUT), { recursive: true });
fs.writeFileSync(OUT, JSON.stringify(out, null, 4) + '\n');

console.log(`Extracted ${sortedSingulars.length} singular + ${sortedPlurals.length} plural strings`);
console.log(`Scanned ${files.length} files`);
console.log(`Wrote ${path.relative(ROOT, OUT)}`);
