#!/usr/bin/env node
/**
 * Lightweight lint of the source strings against the mechanical parts of the
 * Nextcloud translation guidelines. The NC Transifex coordinator (rakekniven)
 * locked the IntraVox resource in #63 over exactly these categories, so catching
 * them before pushing keeps the resource from being re-locked.
 *
 * Scope is deliberately small — only the mechanically-checkable rules. Full
 * sentence-case cannot be enforced without false positives on proper nouns
 * (IntraVox, MetaVox, API, URL, ZIP, OAuth2, …), so that stays a human review
 * item; obvious Title Case is merely flagged, never failed.
 *
 * By default this prints warnings and exits 0 (so `npm run l10n:push` is never
 * blocked). Pass --strict to exit 1 on any finding (for a #63-style audit).
 *
 * Usage: node scripts/lint-l10n-strings.js [--strict]
 */

const { computeSourceStrings } = require('./extract-en-json.js');

const strict = process.argv.includes('--strict');
const src = computeSourceStrings();
const msgids = [...src.singulars, ...src.plurals.flat()];

const findings = [];
const add = (rule, msgid, note) => findings.push({ rule, msgid, note });

// Words that legitimately keep capitals — don't flag Title-Case on these.
const PROPER = /\b(IntraVox|MetaVox|SearchVox|FormVox|CalVox|VoxCloud|Nextcloud|Photo Story|File Story|API|URL|URLs|ZIP|OAuth2?|RSS|PDF|ID|IDs|HTML|CSS|JSON|CSV|SSO|OIDC|SAML|iCal|CalDAV|WebDAV|GroupFolder|FormVox|OnlyOffice|Collabora)\b/g;

for (const s of msgids) {
	// 1. Ellipsis must be preceded by a non-breaking space (U+00A0). Applies to
	//    both the "…" char and a literal "..." run.
	//    Allowed: "Loading …"  Flagged: "Loading …" / "Loading…" / "Loading..."
	const ellipsisMatch = s.match(/(.)(?:…|\.\.\.)/);
	if (ellipsisMatch && ellipsisMatch[1] !== ' ') {
		add('ellipsis', s, 'ellipsis must be preceded by U+00A0 (non-breaking space)');
	}

	// 2. No raw URL / path / placeholder-only msgid — those don't belong in t().
	if (/^https?:\/\//i.test(s)) add('url', s, 'msgid is a raw URL — move it out of t()');
	else if (/^\//.test(s) && !/\s/.test(s)) add('path', s, 'msgid looks like a raw path — move it out of t()');
	else if (/^\{[^}]+\}$/.test(s)) add('placeholder', s, 'msgid is only a {placeholder}');

	// 3. Heuristic Title Case (multiple capitalised words), excluding proper nouns
	//    and acronyms. Warning only — for human review.
	const stripped = s.replace(PROPER, '').replace(/\{[^}]+\}/g, '');
	const words = stripped.split(/\s+/).filter(w => /^[A-Za-z]/.test(w));
	const capWords = words.filter(w => /^[A-Z][a-z]+$/.test(w));
	if (words.length >= 2 && capWords.length >= 2 && capWords.length === words.length) {
		add('titlecase', s, 'looks Title Case — NC guideline is sentence case (verify; proper nouns exempt)');
	}
}

if (findings.length === 0) {
	console.log(`✓ l10n string lint clean (${msgids.length} strings)`);
	process.exit(0);
}

// Group by rule for a compact report.
const byRule = {};
for (const f of findings) (byRule[f.rule] ||= []).push(f);

const colour = strict ? '\x1b[31m' : '\x1b[33m';
console.log(`${colour}${strict ? '✗' : '⚠'} l10n string lint: ${findings.length} finding(s)\x1b[0m`);
for (const [rule, list] of Object.entries(byRule)) {
	console.log(`\n  [${rule}] ${list[0].note}`);
	for (const f of list.slice(0, 25)) console.log(`      ${JSON.stringify(f.msgid)}`);
	if (list.length > 25) console.log(`      … and ${list.length - 25} more`);
}
console.log('');

process.exit(strict ? 1 : 0);
