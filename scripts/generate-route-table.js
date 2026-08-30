#!/usr/bin/env node
/**
 * Generate the route posture table. (AUTHZ-1)
 *
 * Reviewer question 2 of the plan: "what does each endpoint require?" — and the
 * honest answer used to be "read 175 lines of routes array plus 189 annotations
 * spread over 14 controllers". This produces one table: route -> controller
 * method -> what it demands of the caller.
 *
 * The table is a FIXTURE, not documentation. It is committed and checked, so a
 * change in authorization shows up as a diff in this file — the seam work in F4
 * and F5 moves a lot of code, and "the route posture is unchanged" becomes a
 * reviewable claim instead of a promise.
 *
 * Run with --check to fail when the committed table is stale.
 */

const fs = require('fs')
const path = require('path')

const REPO_ROOT = path.resolve(__dirname, '..')
const TABLE = path.join(REPO_ROOT, 'docs/route-table.md')
const TABLE_NL = path.join(REPO_ROOT, 'docs/route-table.nl.md')

/** Parse appinfo/routes.php into {name, url, verb}. */
function routes() {
	const source = fs.readFileSync(path.join(REPO_ROOT, 'appinfo/routes.php'), 'utf8')
	const found = []
	const re = /\[\s*'name'\s*=>\s*'([^']+)'\s*,\s*'url'\s*=>\s*'([^']+)'\s*,\s*'verb'\s*=>\s*'([^']+)'/g
	let m
	while ((m = re.exec(source)) !== null) {
		found.push({ name: m[1], url: m[2], verb: m[3] })
	}
	return found
}

/** controller#method -> the markers on that method. */
function postures() {
	const dir = path.join(REPO_ROOT, 'lib/Controller')
	const markers = ['NoAdminRequired', 'NoCSRFRequired', 'PublicPage']
	const map = new Map()

	for (const file of fs.readdirSync(dir).filter((f) => f.endsWith('.php'))) {
		const source = fs.readFileSync(path.join(dir, file), 'utf8')
		const lines = source.split(/\r?\n/)
		const controller = file.replace(/Controller\.php$/, '')
		const key = controller.charAt(0).toLowerCase() + controller.slice(1)

		let prevEnd = -1
		for (let i = 0; i < lines.length; i++) {
			if (!/^\s*(?:public|private|protected) function \w+/.test(lines[i])) continue

			const isPublic = /^\s*public function (\w+)/.exec(lines[i])
			if (isPublic) {
				const head = lines.slice(prevEnd + 1, i).join('\n')
				const on = markers.filter((name) => head.includes(`#[${name}]`))
				// An admin check in the body is part of the posture too.
				map.set(`${key}#${isPublic[1]}`, { markers: on, file })
			}

			let depth = 0
			let started = false
			for (let q = i; q < lines.length; q++) {
				depth += (lines[q].match(/\{/g) || []).length - (lines[q].match(/\}/g) || []).length
				if (lines[q].includes('{')) started = true
				if (started && depth === 0) { prevEnd = q; break }
			}
		}

		// Which methods guard on isAdmin() in their body? Bounded by the NEXT
		// method, otherwise a guard in the following method is attributed to this
		// one — which briefly labelled the anonymous /api/health as admin-checked.
		const bodyGuards = new Set()
		const methodRe = /(?:public|private|protected) function (\w+)\s*\([^)]*\)[^{]*\{/g
		const starts = []
		let mm
		while ((mm = methodRe.exec(source)) !== null) starts.push({ name: mm[1], at: mm.index })
		for (let s = 0; s < starts.length; s++) {
			const end = s + 1 < starts.length ? starts[s + 1].at : source.length
			const body = source.slice(starts[s].at, end)
			if (/!\$this->isAdmin\(\)|isAdmin\(\)\s*\)\s*\{/.test(body)) bodyGuards.add(starts[s].name)
		}
		for (const name of bodyGuards) {
			const entry = map.get(`${key}#${name}`)
			if (entry) entry.adminGuard = true
		}
	}

	return map
}

/**
 * What a caller must satisfy, in words.
 *
 * De sleutel is taalonafhankelijk; alleen de weergave verschilt. Zo blijven de
 * telling en de sortering in beide talen identiek en kan de NL-tabel niet stil
 * afwijken van de Engelse.
 */
function requirementKey(entry) {
	if (!entry) return 'unknown'
	const { markers, adminGuard } = entry
	if (markers.includes('PublicPage')) return adminGuard ? 'anonymousAdminCheck' : 'anonymous'
	if (adminGuard) return 'adminBody'
	if (markers.includes('NoAdminRequired')) return 'user'
	return 'admin'
}

/** Weergave per taal. De sleutels komen uit requirementKey(). */
const REQUIREMENTS = {
	en: {
		unknown: '?',
		anonymousAdminCheck: 'anonymous + admin check (!)',
		anonymous: 'anonymous',
		adminBody: 'admin (checked in body)',
		user: 'any logged-in user',
		admin: 'admin',
	},
	nl: {
		unknown: '?',
		anonymousAdminCheck: 'anoniem + admin-controle (!)',
		anonymous: 'anoniem',
		adminBody: 'admin (gecontroleerd in de body)',
		user: 'elke ingelogde gebruiker',
		admin: 'admin',
	},
}

/** Kop- en looptekst per taal. De tabelinhoud zelf blijft Engels: dat zijn
 *  route-paden en handlernamen uit de code, geen proza. */
const STRINGS = {
	en: {
		title: 'Route posture',
		intro: [
			'What each endpoint demands of its caller. Regenerate with `npm run route-table`;',
			'CI fails when this file is stale, so a change in authorization shows up here as a',
			'diff rather than staying buried in 14 controllers.',
		],
		count: (n) => `${n} routes.`,
		header: '| Verb | URL | Handler | Requires | CSRF |',
		csrf: { exempt: 'exempt', required: 'required' },
	},
	nl: {
		title: 'Route-posture',
		intro: [
			'Wat elk endpoint van zijn aanroeper verlangt. Opnieuw genereren met',
			'`npm run route-table`; CI faalt als dit bestand verouderd is, zodat een wijziging',
			'in de autorisatie hier als diff zichtbaar wordt in plaats van verstopt te raken',
			'in 14 controllers.',
		],
		count: (n) => `${n} routes.`,
		header: '| Verb | URL | Handler | Vereist | CSRF |',
		csrf: { exempt: 'vrijgesteld', required: 'vereist' },
	},
}

function render(lang) {
	const t = STRINGS[lang]
	const words = REQUIREMENTS[lang]
	const posture = postures()
	const rows = routes().map((r) => {
		// routes.php uses snake_case for a few handlers; NC resolves those to the
		// camelCase method, so look both up rather than reporting "?".
		const camel = r.name.replace(/_([a-z])/g, (_, c) => c.toUpperCase())
		const entry = posture.get(r.name) ?? posture.get(camel)
		return {
			...r,
			requires: requirementKey(entry),
			csrf: entry && entry.markers.includes('NoCSRFRequired') ? 'exempt' : 'required',
		}
	})

	const counts = rows.reduce((acc, r) => {
		acc[r.requires] = (acc[r.requires] || 0) + 1
		return acc
	}, {})

	const lines = [
		`# ${t.title}`,
		'',
		'<!-- Generated by scripts/generate-route-table.js. Do not edit by hand. -->',
		'',
		...t.intro,
		'',
		t.count(rows.length),
		'',
		// Sorteer op de Engelse sleutel, zodat beide tabellen dezelfde volgorde
		// houden en een diff tussen de talen betekenisvol blijft.
		...Object.entries(counts).sort().map(([k, v]) => `- **${words[k]}**: ${v}`),
		'',
		t.header,
		'|---|---|---|---|---|',
		...rows.map((r) => `| ${r.verb} | \`${r.url}\` | \`${r.name}\` | ${words[r.requires]} | ${t.csrf[r.csrf]} |`),
		'',
	]

	return lines.join('\n')
}

const outputs = [
	{ file: TABLE, contents: render('en') },
	{ file: TABLE_NL, contents: render('nl') },
]

if (process.argv.includes('--check')) {
	// Beide talen worden gecontroleerd. Zou alleen de Engelse tabel bewaakt
	// worden, dan zou de Nederlandse stil verouderen zodra er een route bij komt.
	const stale = outputs.filter(({ file, contents }) => {
		const current = fs.existsSync(file) ? fs.readFileSync(file, 'utf8') : ''
		return current !== contents
	})
	if (stale.length > 0) {
		for (const { file } of stale) {
			console.error(`✗ ${path.relative(REPO_ROOT, file)} is stale. Run: npm run route-table`)
		}
		process.exit(1)
	}
	console.log('✓ Route tables (en + nl) match the controllers')
} else {
	for (const { file, contents } of outputs) {
		fs.mkdirSync(path.dirname(file), { recursive: true })
		fs.writeFileSync(file, contents)
		console.log(`✓ Wrote ${path.relative(REPO_ROOT, file)}`)
	}
}
