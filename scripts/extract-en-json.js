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
 * This module also exports computeSourceStrings() so scripts/check-l10n-sync.js
 * can compute the exact same source-string set without a second copy of the
 * extraction regexes.
 *
 * Usage: node scripts/extract-en-json.js
 */

const fs = require('fs');
const path = require('path');
const crypto = require('crypto');

const ROOT = path.resolve(__dirname, '..');
const OUT = path.join(ROOT, 'l10n', 'en.json');
const COUNT = path.join(ROOT, 'l10n', '.source-count.json');
const MANIFEST = path.join(ROOT, 'l10n', '.source-strings.json');

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

// Factory functions so every caller gets fresh, non-shared RegExp instances
// (a shared /g regex carries lastIndex state between files).
function makeRegexes() {
	return {
		T_RE: new RegExp(`(?<![\\w$])${T_PREFIX}t\\(\\s*(?:'intravox'\\s*,\\s*)?${STR}`, 'g'),
		N_RE: new RegExp(`(?<![\\w$])${T_PREFIX}n\\(\\s*(?:'intravox'\\s*,\\s*)?${STR}\\s*,\\s*${STR}`, 'g'),
		DT_RE: new RegExp(`(?:this\\.)?\\$t\\(\\s*${STR}`, 'g'),
		DN_RE: new RegExp(`(?:this\\.)?\\$n\\(\\s*${STR}\\s*,\\s*${STR}`, 'g'),
	};
}

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

/**
 * Scan src/ + lib/ for translation calls and return the deterministic source
 * string set. Single source of truth for both the POT extractor (this file's
 * CLI) and the prebuild guard (check-l10n-sync.js).
 *
 * @returns {{ singulars: string[], plurals: [string,string][], files: string[] }}
 *   singulars and plurals are sorted (localeCompare); a string used in n() is a
 *   plural pair and never also appears in singulars.
 */
function computeSourceStrings() {
	const singulars = new Set();
	const plurals = new Map(); // singular -> plural

	const files = [
		...walk(path.join(ROOT, 'src'), ['.vue', '.js']),
		...walk(path.join(ROOT, 'lib'), ['.php']),
	];

	for (const file of files) {
		const content = fs.readFileSync(file, 'utf8');
		const { T_RE, N_RE, DT_RE, DN_RE } = makeRegexes();

		// Plurals first so we can record the pairing; their singular also counts.
		let m;
		for (const re of [N_RE, DN_RE]) {
			while ((m = re.exec(content)) !== null) {
				const sing = unescapeLiteral(m[1]);
				const plur = unescapeLiteral(m[2]);
				if (sing) plurals.set(sing, plur);
			}
		}

		for (const re of [T_RE, DT_RE]) {
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

	const sortedSingulars = [...singulars].sort((a, b) => a.localeCompare(b));
	const sortedPlurals = [...plurals.keys()]
		.sort((a, b) => a.localeCompare(b))
		.map(s => [s, plurals.get(s)]);

	return { singulars: sortedSingulars, plurals: sortedPlurals, files };
}

/**
 * The canonical, order-stable list of every source msgid (singulars + the
 * singular form of each plural), and its sha256. This is what the manifest and
 * the guard compare on — durable across POT deletes by the bot.
 *
 * @param {{singulars:string[], plurals:[string,string][]}} src
 * @returns {{ msgids: string[], sha256: string, count: number }}
 */
function sourceStringManifest(src) {
	const msgids = [...src.singulars, ...src.plurals.map(([s]) => s)]
		.sort((a, b) => a.localeCompare(b));
	const sha256 = crypto.createHash('sha256')
		.update(JSON.stringify(msgids))
		.digest('hex');
	return { msgids, sha256, count: msgids.length };
}

module.exports = { computeSourceStrings, sourceStringManifest };

// ---- CLI ----
if (require.main === module) {
	const src = computeSourceStrings();

	// Build the translations object, sorted for deterministic output.
	const translations = {};
	for (const s of src.singulars) {
		translations[s] = s;
	}
	for (const [s, p] of src.plurals) {
		translations[s] = [s, p];
	}

	const out = { translations, pluralForm: 'nplurals=2; plural=(n != 1);' };
	fs.mkdirSync(path.dirname(OUT), { recursive: true });
	fs.writeFileSync(OUT, JSON.stringify(out, null, 4) + '\n');

	// Committed count file — the runtime denominator for the admin "translation
	// coverage" percentage (l10n/en.json is a gitignored build artifact absent on
	// the server, so the total lives here instead).
	const sourceStrings = src.singulars.length + src.plurals.length;
	fs.writeFileSync(COUNT, JSON.stringify({ sourceStrings }, null, 4) + '\n');

	// Committed source-string manifest — the durable record of exactly which
	// msgids the Transifex bot has been given. scripts/check-l10n-sync.js fails
	// the build if the code's set no longer matches this, i.e. strings were added
	// without pushing them to Transifex (npm run l10n:push). The full sorted list
	// is stored (not just the hash) so every git diff shows what entered/left.
	const manifest = sourceStringManifest(src);
	fs.writeFileSync(MANIFEST, JSON.stringify({
		count: manifest.count,
		sha256: manifest.sha256,
		strings: manifest.msgids,
	}, null, 4) + '\n');

	console.log(`Extracted ${src.singulars.length} singular + ${src.plurals.length} plural strings`);
	console.log(`Scanned ${src.files.length} files`);
	console.log(`Wrote ${path.relative(ROOT, OUT)}`);
	console.log(`Wrote ${path.relative(ROOT, COUNT)} (sourceStrings=${sourceStrings})`);
	console.log(`Wrote ${path.relative(ROOT, MANIFEST)} (count=${manifest.count}, sha256=${manifest.sha256.slice(0, 12)}…)`);
}
