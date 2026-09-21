#!/usr/bin/env node
/**
 * Guards the Details sidebar against two request hazards it shipped with.
 *
 * Both were measured on dev.rikdekker.nl, on a cold load with the sidebar
 * remembered as open (localStorage 'intravox:details-open' === '1'):
 *
 *   /apps/intravox/api/pages/undefined/metadata   start 342ms -> end 1911ms  404
 *   /apps/intravox/api/pages/page-7fe1d5d9-…      start 942ms -> end 1306ms  200
 *
 * 1. UNDEFINED ID. App.vue renders the sidebar behind `v-show`, not `v-if`, so
 *    the component mounts before `currentPage` resolves and `pageId` is
 *    undefined. The request became `/api/pages/undefined/metadata`, whose 404
 *    body is literally {"error":"Page not found"} — rendered to the user as if
 *    their page had gone missing.
 *
 * 2. OUT-OF-ORDER RESPONSES. mounted() and the pageId watcher can have two
 *    loads in flight. The bad one started first but finished 605ms LAST, so its
 *    catch overwrote metadata that had already loaded correctly: an error
 *    banner on top of good data.
 *
 * A unit test would need a DOM, a bundler and a Vue runtime that this repo
 * deliberately does not carry, so this checks the source itself, in the same
 * spirit as check-facet-serialization.js. It also runs the real guard logic
 * against a simulated out-of-order race, so the ordering rule is executed and
 * not merely pattern-matched.
 *
 * Run standalone or via `npm run lint:sidebar-guards`.
 */

const path = require('path')
const fs = require('fs')

const COMPONENT = path.join(__dirname, '..', 'src', 'components', 'PageDetailsSidebar.vue')
const APP = path.join(__dirname, '..', 'src', 'App.vue')

const source = fs.readFileSync(COMPONENT, 'utf8')
const appSource = fs.readFileSync(APP, 'utf8')

let failures = 0
const fail = (msg, detail) => {
	failures++
	console.error(`✗ ${msg}`)
	if (detail) console.error(`    ${detail}`)
}

/** Extract one method body by brace-matching from its declaration. */
function methodBody(name) {
	const start = source.indexOf(`async ${name}()`)
	if (start === -1) return null
	const open = source.indexOf('{', start)
	if (open === -1) return null
	let depth = 0
	for (let i = open; i < source.length; i++) {
		const c = source[i]
		if (c === '{') depth++
		else if (c === '}') {
			depth--
			if (depth === 0) return source.slice(open, i + 1)
		}
	}
	return null
}

// ---------------------------------------------------------------- structure

const LOADERS = ['loadMetadata', 'loadVersions']

for (const name of LOADERS) {
	const body = methodBody(name)
	if (!body) {
		fail(`${name}() not found — did it get renamed?`)
		continue
	}

	// Hazard 1: the request must not be built from a falsy pageId.
	const guardsPageId = /if\s*\(\s*!this\.pageId\s*\)\s*\{\s*return/.test(body)
	if (!guardsPageId) {
		fail(
			`${name}() does not bail out on a missing pageId`,
			'a cold load mounts the sidebar before currentPage exists, producing /api/pages/undefined/…'
		)
	}

	// The guard is only meaningful if it precedes the request.
	const guardIndex = body.search(/if\s*\(\s*!this\.pageId\s*\)/)
	const requestIndex = body.indexOf('generateUrl')
	if (guardsPageId && requestIndex !== -1 && guardIndex > requestIndex) {
		fail(`${name}() guards pageId only AFTER building the request URL`)
	}

	// Hazard 2: a stale response must not be allowed to write.
	const takesToken = /const token = \+\+this\._\w+Token/.test(body)
	const checksToken = (body.match(/token !== this\._\w+Token/g) || []).length
	if (!takesToken) {
		fail(`${name}() does not take a request token`, 'overlapping loads can finish out of order')
	}
	// One check after the await, one in catch, one in finally.
	if (checksToken < 2) {
		fail(
			`${name}() checks its request token ${checksToken}×, expected at least 2`,
			'both the success path and the catch must refuse to write when superseded'
		)
	}
	if (!/catch \(error\) \{\s*if \(token !== this\._\w+Token\) return/.test(body)) {
		fail(
			`${name}()'s catch does not check the token first`,
			'this is the exact path that painted "Page not found" over loaded data'
		)
	}
}

// Both tokens must be initialised, and not as reactive state.
if (!/this\._metadataToken = 0/.test(source) || !/this\._versionsToken = 0/.test(source)) {
	fail('request tokens are not initialised in created()')
}

// ------------------------------------------------- the precondition upstream

// If App.vue ever switches to v-if, the mount race disappears; if it stays
// v-show, these guards are load-bearing. Either is fine — but the comment in
// the component explains the v-show behaviour, so flag a silent change.
if (!/v-show="currentPage && !loading && !error"/.test(appSource)) {
	console.warn(
		'! App.vue no longer renders PageDetailsSidebar with the documented v-show; '
		+ 'the guards stay correct, but PageDetailsSidebar.vue comments may now be stale'
	)
}

// The bug only surfaced because the open/closed state is restored from
// localStorage before any page is loaded. Keep that link discoverable.
if (!/intravox:details-open/.test(appSource)) {
	console.warn('! App.vue no longer reads intravox:details-open; the cold-open path may have moved')
}

// ------------------------------------------------------ behavioural check

/**
 * Run the ordering rule itself against the measured race: a slow rejection
 * that started first must not overwrite a fast success that started later.
 */
function simulateRace({ guarded }) {
	const state = { metadata: null, metadataError: null, loading: false }
	const host = { _metadataToken: 0 }

	const load = async (id, { delay, fails }) => {
		if (guarded && !id) return
		const token = ++host._metadataToken
		state.loading = true
		state.metadataError = null
		try {
			await new Promise(r => setTimeout(r, delay))
			if (fails) throw { response: { data: { error: 'Page not found' } } }
			if (guarded && token !== host._metadataToken) return
			state.metadata = { id }
		} catch (error) {
			if (guarded && token !== host._metadataToken) return
			state.metadataError = error.response.data.error
		} finally {
			if (!guarded || token === host._metadataToken) state.loading = false
		}
	}

	// Exactly the measured timing: undefined starts first, finishes last.
	return Promise.all([
		load(undefined, { delay: 60, fails: true }),
		load('page-7fe1d5d9', { delay: 10, fails: false }),
	]).then(() => state)
}

async function behavioural() {
	const broken = await simulateRace({ guarded: false })
	if (broken.metadataError !== 'Page not found') {
		fail(
			'the simulation no longer reproduces the original bug',
			'if this stops failing without the guards, the scenario has drifted and proves nothing'
		)
	}

	const fixed = await simulateRace({ guarded: true })
	if (fixed.metadataError !== null) {
		fail('with the guards applied, a stale 404 still reached the UI', `got ${JSON.stringify(fixed.metadataError)}`)
	}
	if (!fixed.metadata || fixed.metadata.id !== 'page-7fe1d5d9') {
		fail('with the guards applied, the good response did not survive', JSON.stringify(fixed.metadata))
	}
	if (fixed.loading !== false) {
		fail('loading flag left stuck after the race', String(fixed.loading))
	}
}

behavioural().then(() => {
	if (failures > 0) {
		console.error(`\n${failures} sidebar load-guard check(s) failed`)
		process.exit(1)
	}
	console.log(
		`✓ sidebar load guards intact (${LOADERS.length} loaders: undefined-id guard + stale-response token, `
		+ 'plus an executed out-of-order race)'
	)
})
