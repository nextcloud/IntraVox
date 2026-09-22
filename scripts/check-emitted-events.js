#!/usr/bin/env node
/**
 * Guards against a listener bound to an event that nobody emits.
 *
 * This is a silent failure by construction. Vue attaches `@feed-name="..."`
 * to whatever component sits there; if that component never calls
 * `$emit('feed-name')`, nothing throws, nothing warns, and no test that
 * renders the parent notices. The feature simply does not happen.
 *
 * It has cost this repo the same feature twice:
 *
 *   3c9e1c3  added FeedWidget's `emits: ['feed-name']` + the $emit, so the
 *            editor could offer the feed's own <channel><title> as a widget
 *            title. Verified working on dev at the time.
 *   06e8aeb  merged PR #38, whose branch had also edited FeedWidget.vue. The
 *            conflict was resolved in favour of #38 and took BOTH lines with
 *            it. FeedWidgetEditor kept `@feed-name="onFeedName"` — bound to an
 *            event that no longer existed. Titles stopped prefilling.
 *
 * Nothing caught it: the build passed, `npm run ci` passed, the ESLint guard
 * added later passes too (`vue/require-explicit-emits` only looks downward,
 * at the child's own template). It surfaced when a user asked why a feed
 * produced no title.
 *
 * So this checks the pairing across files, in the same spirit as
 * check-sidebar-load-guards.js: for every listener a template binds to a
 * LOCAL component (one this repo defines and imports), that component must
 * declare or emit the event.
 *
 * Deliberately narrow, to stay silent rather than noisy:
 *  - Only local components. `@update` on NcTextField is the library's problem.
 *  - Only custom events. Native DOM events (`@click` on a component that does
 *    not declare it still work via fallthrough attributes).
 *  - `v-on="$listeners"`-style forwarding and dynamic `$emit(variable)` make a
 *    component un-analysable; such a component is skipped and reported.
 *
 * Run standalone or via `npm run lint:emitted-events`.
 */

const path = require('path')
const fs = require('fs')

const SRC = path.join(__dirname, '..', 'src')

/**
 * Events that are native DOM events first. A component that does not declare
 * them still receives them on its root element, so a missing $emit is not a
 * bug. Everything else is custom and must be emitted to mean anything.
 */
const NATIVE = new Set([
	'click', 'dblclick', 'mousedown', 'mouseup', 'mouseenter', 'mouseleave',
	'mousemove', 'mouseover', 'mouseout', 'contextmenu',
	'keydown', 'keyup', 'keypress',
	'focus', 'blur', 'focusin', 'focusout',
	'input', 'change', 'submit', 'reset',
	'scroll', 'wheel', 'load', 'error', 'abort',
	'drag', 'dragstart', 'dragend', 'dragover', 'dragenter', 'dragleave', 'drop',
	'touchstart', 'touchend', 'touchmove', 'touchcancel',
	'pointerdown', 'pointerup', 'pointermove', 'pointerenter', 'pointerleave',
	'transitionend', 'animationend', 'play', 'pause', 'ended',
])

function vueFiles(dir, out = []) {
	for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
		const full = path.join(dir, entry.name)
		if (entry.isDirectory()) vueFiles(full, out)
		else if (entry.name.endsWith('.vue')) out.push(full)
	}
	return out
}

const files = vueFiles(SRC)

/** PascalCase component name -> absolute path, for every .vue this repo owns. */
const byName = new Map()
for (const file of files) {
	byName.set(path.basename(file, '.vue'), file)
}

const sources = new Map(files.map(f => [f, fs.readFileSync(f, 'utf8')]))

const templateOf = (source) => {
	const open = source.indexOf('<template>')
	if (open === -1) return ''
	const close = source.lastIndexOf('</template>')
	return close > open ? source.slice(open, close) : source.slice(open)
}

const scriptOf = (source) => {
	const open = source.search(/<script\b[^>]*>/)
	if (open === -1) return ''
	const close = source.lastIndexOf('</script>')
	return close > open ? source.slice(open, close) : source.slice(open)
}

const kebab = (name) => name.replace(/([a-z0-9])([A-Z])/g, '$1-$2').toLowerCase()

/**
 * Which events can this component produce?
 *
 * Returns null when the component cannot be analysed statically, so the caller
 * can skip it rather than report a false positive.
 */
function emittedBy(file) {
	const script = scriptOf(sources.get(file))
	const events = new Set()

	// `emits: ['a', 'b']` or `emits: { a: null }` — the declaration Vue 3 wants.
	const emitsArray = script.match(/\bemits:\s*\[([^\]]*)\]/)
	if (emitsArray) {
		for (const m of emitsArray[1].matchAll(/['"]([^'"]+)['"]/g)) events.add(m[1])
	}
	const emitsObject = script.match(/\bemits:\s*\{([\s\S]*?)\n\t*\}/)
	if (emitsObject) {
		for (const m of emitsObject[1].matchAll(/^\s*['"]?([\w:-]+)['"]?\s*:/gm)) events.add(m[1])
	}

	// The calls themselves, including a template-only `$emit('x')`.
	const whole = sources.get(file)
	for (const m of whole.matchAll(/\$emit\(\s*['"]([^'"]+)['"]/g)) events.add(m[1])
	for (const m of whole.matchAll(/\bemit\(\s*['"]([^'"]+)['"]/g)) events.add(m[1])

	// Un-analysable: the event name is computed, or listeners are forwarded
	// wholesale from a parent. Either way we cannot prove absence.
	if (/\$emit\(\s*[^'")\s]/.test(whole)) return null
	if (/v-on="\$attrs"|v-bind="\$attrs"|v-on="\$listeners"/.test(whole)) return null

	// `defineEmits` for any script-setup component.
	const defineEmits = script.match(/defineEmits[<(]([\s\S]*?)[>)]/)
	if (defineEmits) {
		for (const m of defineEmits[1].matchAll(/['"]([^'"]+)['"]/g)) events.add(m[1])
		if (!/['"]/.test(defineEmits[1])) return null // typed form, not parsed
	}

	return events
}

/**
 * Local components a file registers, mapped from every tag spelling to the file.
 *
 * Both registration styles count. The lazy one is not an edge case here: the
 * editor loads its own preview with
 * `FeedWidget: defineAsyncComponent(() => import('./FeedWidget.vue'))`, and
 * that is precisely the pair this check exists for. A resolver that only
 * understood static imports would have walked past the bug it was written for
 * — which it did, on the first run.
 */
function localComponentsOf(file) {
	const script = scriptOf(sources.get(file))
	const map = new Map()

	const register = (name, spec) => {
		const target = byName.get(path.basename(spec, '.vue'))
		if (!target) return
		map.set(name, target)
		map.set(kebab(name), target)
	}

	// Static: `import FeedWidget from './FeedWidget.vue'`
	for (const m of script.matchAll(/import\s+(\w+)\s+from\s+['"]([^'"]+\.vue)['"]/g)) {
		register(m[1], m[2])
	}

	// Lazy: `Name: defineAsyncComponent(() => import('./Name.vue'))`, and the
	// bare `Name: () => import('./Name.vue')` form.
	for (const m of script.matchAll(
		/(\w+)\s*:\s*(?:defineAsyncComponent\s*\(\s*)?\(\s*\)\s*=>\s*import\(\s*['"]([^'"]+\.vue)['"]/g
	)) {
		register(m[1], m[2])
	}

	// Lazy const: `const Foo = defineAsyncComponent(() => import('./Foo.vue'))`
	for (const m of script.matchAll(
		/(?:const|let)\s+(\w+)\s*=\s*defineAsyncComponent\s*\(\s*\(\s*\)\s*=>\s*import\(\s*['"]([^'"]+\.vue)['"]/g
	)) {
		register(m[1], m[2])
	}

	return map
}

let failures = 0
let checked = 0
const skipped = []

const fail = (msg, detail) => {
	failures++
	console.error(`✗ ${msg}`)
	if (detail) console.error(`    ${detail}`)
}

for (const file of files) {
	const locals = localComponentsOf(file)
	if (locals.size === 0) continue

	const template = templateOf(sources.get(file))
	const rel = path.relative(path.join(__dirname, '..'), file)

	// Each opening tag of a local component, with its attributes.
	for (const m of template.matchAll(/<([A-Za-z][\w.-]*)((?:\s[^<>]*?)?)\/?>/g)) {
		const [, tag, attrs] = m
		const target = locals.get(tag)
		if (!target) continue

		const emitted = emittedBy(target)
		if (emitted === null) {
			const name = path.basename(target, '.vue')
			if (!skipped.includes(name)) skipped.push(name)
			continue
		}

		for (const a of attrs.matchAll(/(?:@|v-on:)([\w-]+)(?:\.[\w.]+)?\s*=/g)) {
			const event = a[1]
			if (NATIVE.has(event)) continue
			checked++

			const declared = emitted.has(event)
				|| emitted.has(kebab(event))
				|| [...emitted].some(e => kebab(e) === event)
			if (!declared) {
				const line = template.slice(0, m.index).split('\n').length
				const open = sources.get(file).indexOf('<template>')
				const offset = sources.get(file).slice(0, open).split('\n').length - 1
				fail(
					`${rel}:${line + offset} binds @${event} on <${tag}>, which never emits it`,
					`${path.relative(path.join(__dirname, '..'), target)} emits: `
					+ (emitted.size ? [...emitted].sort().join(', ') : '(nothing)')
				)
			}
		}
	}
}

if (skipped.length > 0) {
	console.warn(
		`! skipped ${skipped.length} component(s) that forward or compute their events: `
		+ skipped.sort().join(', ')
	)
}

if (failures > 0) {
	console.error(
		`\n${failures} unemitted event listener(s). A listener bound to an event `
		+ 'nobody emits fails silently: no warning, no error, the feature just '
		+ 'does not happen. Either emit it in the child, or drop the listener.'
	)
	process.exit(1)
}

console.log(`✓ event listeners matched (${checked} custom listeners on local components)`)
