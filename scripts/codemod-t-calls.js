#!/usr/bin/env node
/**
 * One-off codemod: make every translation call literally `t('intravox', '…')`
 * and `n('intravox', '…', '…', …)` so the Nextcloud Transifex extractor (which
 * only matches that exact form) sees ALL strings.
 *
 * Before: IntraVox mostly used `this.t('str')` / template `t('str')` (resolved
 * via a local `t(key, vars)` wrapper) and a few `$t('str')` globals — forms the
 * NC bot misses (~799 strings invisible).
 *
 * After: each component exposes the app-first wrapper
 *   t(app, text, vars) { return translate(app, text, vars) }
 *   n(app, s, p, c, v) { return translatePlural(app, s, p, c, v) }
 * and every call-site is `t('intravox', 'str')` — correct at runtime AND
 * bot-visible.
 *
 * The set of msgids must NOT change (verified against /tmp/golden-msgids.txt):
 * only the call FORM changes, never the translated text.
 *
 * Usage: node scripts/codemod-t-calls.js [--dry]
 */

const fs = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '..');
const SRC = path.join(ROOT, 'src');
const DRY = process.argv.includes('--dry');

// Single-quoted JS string literal, allowing escapes — same shape as extract-en-json.js.
const STR = "'((?:[^'\\\\]|\\\\.)*)'";

function walk(dir, acc = []) {
	for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
		const full = path.join(dir, e.name);
		if (e.isDirectory()) { if (e.name !== 'node_modules') walk(full, acc); }
		else if (/\.(vue|js)$/.test(e.name)) acc.push(full);
	}
	return acc;
}

// Ensure `import { translate, translatePlural } from '@nextcloud/l10n'` exists.
function ensureImport(src) {
	if (/from ['"]@nextcloud\/l10n['"]/.test(src)) {
		// Already imports something from l10n — normalise to include both.
		return src.replace(
			/import\s*\{([^}]*)\}\s*from\s*['"]@nextcloud\/l10n['"]/,
			(m, inner) => {
				const need = ['translate', 'translatePlural'];
				const have = inner.split(',').map(s => s.trim().split(/\s+as\s+/)[0]);
				const add = need.filter(n => !have.includes(n));
				if (!add.length) return m;
				return `import { ${inner.trim().replace(/,\s*$/, '')}, ${add.join(', ')} } from '@nextcloud/l10n'`;
			},
		);
	}
	// No l10n import yet — add one. For .vue, insert after <script>; for .js at top.
	const importLine = "import { translate, translatePlural } from '@nextcloud/l10n'\n";
	if (/<script[^>]*>/.test(src)) {
		return src.replace(/(<script[^>]*>\s*\n)/, `$1${importLine}`);
	}
	return importLine + src;
}

// Rewrite the local t()/n() wrapper definitions to the app-first form.
function rewriteWrappers(src) {
	let out = src;
	// t(key, vars = {}) { return t('intravox', key, vars); }  (Familie A)
	out = out.replace(
		/t\(key, vars = \{\}\) \{\s*return t\('intravox', key, vars\);?\s*\}/g,
		"t(app, text, vars) {\n      return translate(app, text, vars);\n    }",
	);
	// t(key) { return t('intravox', key); }  (SearchBar)
	out = out.replace(
		/t\(key\) \{\s*return t\('intravox', key\);?\s*\}/g,
		"t(app, text, vars) {\n      return translate(app, text, vars);\n    }",
	);
	// n(singular, plural, count, vars = {}) { return n('intravox', …); }  (Familie A plural)
	out = out.replace(
		/n\(singular, plural, count, vars = \{\}\) \{\s*return n\('intravox', singular, plural, count, vars\);?\s*\}/g,
		"n(app, singular, plural, count, vars) {\n      return translatePlural(app, singular, plural, count, vars);\n    }",
	);
	return out;
}

// Prefix call-sites: t('X' -> t('intravox', 'X'  and  this.t('X' -> this.t('intravox', 'X'
// Idempotent: skip when the first arg is already 'intravox'. Never touch the
// wrapper definition `t(app, …)` (its first token is the identifier `app`, not a
// string literal, so the STR regex won't match it).
function prefixCalls(src) {
	let n = 0;
	// t( '...'  not preceded by word/$/.  EXCEPT when already t('intravox'
	const tRe = new RegExp(`(?<![\\w$.])(this\\.)?t\\(\\s*${STR}`, 'g');
	let out = src.replace(tRe, (m, thisPrefix, lit) => {
		if (lit === 'intravox') return m; // already prefixed
		n++;
		return `${thisPrefix || ''}t('intravox', '${lit}'`;
	});
	// $t('...') and this.$t('...') -> t('intravox', '...')
	const dtRe = new RegExp(`(?<![\\w])(this\\.)?\\$t\\(\\s*${STR}`, 'g');
	out = out.replace(dtRe, (m, thisPrefix, lit) => {
		n++;
		return `${thisPrefix || ''}t('intravox', '${lit}'`;
	});
	// n('...', '...' plural calls (skip if already 'intravox')
	const nRe = new RegExp(`(?<![\\w$.])(this\\.)?n\\(\\s*${STR}\\s*,\\s*${STR}`, 'g');
	out = out.replace(nRe, (m, thisPrefix, s1, s2) => {
		if (s1 === 'intravox') return m;
		n++;
		return `${thisPrefix || ''}n('intravox', '${s1}', '${s2}'`;
	});
	return { out, n };
}

let filesChanged = 0, callsPrefixed = 0;
for (const file of walk(SRC)) {
	const orig = fs.readFileSync(file, 'utf8');
	let src = orig;

	// Only touch files that actually use a bare/this/$ translation call or a wrapper.
	const usesWrapper = /t\(key(,| )|t\(text\)|t\(app,|this\.\$t\(|\$t\(/.test(src);
	const hasBareCalls = new RegExp(`(?<![\\w$.])(this\\.)?\\$?t\\(\\s*'`).test(src);
	if (!usesWrapper && !hasBareCalls) continue;

	src = rewriteWrappers(src);
	const r = prefixCalls(src);
	src = r.out;

	if (src !== orig) {
		// Only add the import if the file now references translate/translatePlural.
		if (/\btranslate(Plural)?\(/.test(src)) src = ensureImport(src);
		if (!DRY) fs.writeFileSync(file, src);
		filesChanged++;
		callsPrefixed += r.n;
		console.log(`${DRY ? '[dry] ' : ''}${path.relative(ROOT, file)}  (+${r.n} calls)`);
	}
}
console.log(`\n${filesChanged} files, ${callsPrefixed} call-sites prefixed${DRY ? ' (dry run)' : ''}`);
