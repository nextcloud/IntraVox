import {
	countActiveRefinements,
	parseRefinements,
	serializeRefinements,
} from '../services/FacetQueryService.js'
import { aliasField } from '../services/filterSpec.js'
import { translate } from '@nextcloud/l10n'

/**
 * Readable headings for the fields a viewer can filter on.
 *
 * The editor shows these names when picking fields, but stores only the field
 * itself, so the viewer side needs its own copy. Keyed by the aliased field
 * name, which is what the facet results use.
 */
const FACET_LABELS = {
	group: 'Group',
	displayName: 'Name',
	pronouns: 'Pronouns',
	role: 'Role',
	headline: 'Headline',
	organisation: 'Organisation',
	email: 'Email',
	phone: 'Phone',
	address: 'Address',
	website: 'Website',
	birthdate: 'Date of birth',
	biography: 'Biography',
	twitter: 'X (Twitter)',
	bluesky: 'Bluesky',
	fediverse: 'Fediverse',
}

/**
 * Heading for a field the editor did not rename.
 *
 * An unknown field — a custom one, say — keeps its own name rather than being
 * forced into a guessed label.
 */
function defaultFacetLabel(field) {
	const source = FACET_LABELS[field]
	return source ? translate('intravox', source) : field
}

/**
 * Reactive state for a widget that lets viewers refine its own results.
 *
 * Options API rather than a composable: every widget in this app is Options
 * API, and mixing `setup()` into a component that also has `data()`/`methods`
 * is the pattern that makes a Vue codebase hard to follow. Transport and
 * serialisation live in FacetQueryService; only state lives here.
 *
 * A consuming component provides:
 *   - `widget` prop (the widget config)
 *   - `widgetKey` prop (URL namespace, see below)
 *   - a `runFacetedFetch({ refinements, q, facets })` method doing the request
 */
export default {
	props: {
		/**
		 * Namespace for this widget's URL parameter.
		 *
		 * Falls back to a positional key because widget ids are only assigned
		 * when a page is opened in the editor — a page that has never been
		 * edited since ids were introduced has none.
		 */
		widgetKey: {
			type: String,
			default: '',
		},
	},

	data() {
		return {
			refinements: {},
			facets: [],
			searchTerm: '',
			facetLoading: false,
			approximate: false,
			scannedCount: 0,
			capCount: 0,
		}
	},

	computed: {
		/** Whether the editor enabled viewer filtering for this widget. */
		viewerFiltersEnabled() {
			const config = this.widget?.viewerFilters
			if (!config?.enabled) {
				return false
			}
			// A public share never gets a filter panel: the facet values would
			// amount to a browsable directory of the organisation for anyone
			// holding the link. The server refuses the parameters too; this
			// just avoids rendering a panel that could not work.
			if (this.shareToken) {
				return false
			}
			return Array.isArray(config.facets) && config.facets.length > 0
		},

		/** Field names the editor exposed as facets. */
		facetFieldNames() {
			return (this.widget?.viewerFilters?.facets ?? [])
				.map(f => (typeof f === 'string' ? f : f?.field))
				.filter(Boolean)
		},

		/**
		 * Per-facet headings for the panel, keyed the way the server names the
		 * field.
		 *
		 * Two things this has to get right. The stored config keeps the field
		 * as the editor wrote it (`displayname`) while facet results come back
		 * aliased (`displayName`), so the key is aliased here or the lookup
		 * silently misses and the panel falls back to the raw field name. And a
		 * facet the editor never renamed carries an empty label, which is not a
		 * reason to show `displayName` to a visitor — it falls back to the
		 * readable default for that field.
		 */
		facetLabels() {
			const labels = {}
			for (const facet of this.widget?.viewerFilters?.facets ?? []) {
				const field = typeof facet === 'string' ? facet : facet?.field
				if (!field) {
					continue
				}
				const custom = (typeof facet === 'object' && facet.label) ? String(facet.label).trim() : ''
				const key = aliasField(field)
				labels[key] = custom || defaultFacetLabel(key)
			}
			return labels
		},

		searchFieldNames() {
			return this.widget?.viewerFilters?.searchFields ?? []
		},

		searchEnabled() {
			return this.widget?.viewerFilters?.searchEnabled !== false
		},

		filterLayout() {
			return this.widget?.viewerFilters?.layout === 'top' ? 'top' : 'sidebar'
		},

		activeRefinementCount() {
			return countActiveRefinements(this.refinements) + (this.searchTerm ? 1 : 0)
		},

		hasActiveRefinements() {
			return this.activeRefinementCount > 0
		},

		/** Query-string parameter this widget owns. */
		urlParamName() {
			return `fw.${this.widgetKey || 'w0'}`
		},
	},

	methods: {
		/**
		 * Toggle one value within a facet.
		 *
		 * @param {object} payload the toggle payload
		 * @param {string} payload.field facet field
		 * @param {string} payload.value facet value
		 */
		toggleRefinement({ field, value }) {
			const current = Array.isArray(this.refinements[field])
				? [...this.refinements[field]]
				: []
			const index = current.indexOf(value)

			if (index === -1) {
				current.push(value)
			} else {
				current.splice(index, 1)
			}

			const next = { ...this.refinements }
			if (current.length > 0) {
				next[field] = current
			} else {
				delete next[field]
			}

			this.refinements = next
			this.onRefinementsChanged()
		},

		removeRefinement({ field, value }) {
			if (!Array.isArray(this.refinements[field])) {
				return
			}
			this.toggleRefinement({ field, value })
		},

		clearAllRefinements() {
			this.refinements = {}
			this.searchTerm = ''
			this.onRefinementsChanged()
		},

		setSearchTerm(term) {
			this.searchTerm = term ?? ''
			this.onRefinementsChanged()
		},

		/**
		 * Debounced refetch plus URL sync.
		 *
		 * replaceState rather than pushState, so Back still means "previous
		 * page" instead of walking back through every checkbox the user
		 * ticked.
		 */
		onRefinementsChanged() {
			this.syncRefinementsToUrl()

			clearTimeout(this._facetDebounce)
			this._facetDebounce = setTimeout(() => {
				this.runFacetedFetch({
					refinements: this.refinements,
					q: this.searchTerm,
					facets: this.facetFieldNames,
				})
			}, 300)
		},

		syncRefinementsToUrl() {
			if (typeof window === 'undefined' || !window.history?.replaceState) {
				return
			}

			try {
				const url = new URL(window.location.href)
				const token = serializeRefinements(this.refinements, this.searchTerm)

				if (token) {
					url.searchParams.set(this.urlParamName, token)
				} else {
					url.searchParams.delete(this.urlParamName)
				}

				window.history.replaceState(window.history.state, '', url.toString())
			} catch (error) {
				// A URL we cannot write is not worth breaking the widget over.
			}
		},

		/**
		 * Restore refinements from the address bar.
		 *
		 * Read in the widget rather than centrally: the widget owns its own
		 * parameter, App.vue owns `page`.
		 */
		restoreRefinementsFromUrl() {
			if (typeof window === 'undefined') {
				return
			}

			try {
				const token = new URL(window.location.href).searchParams.get(this.urlParamName)
				if (!token) {
					return
				}
				const { refinements, q } = parseRefinements(token)
				this.refinements = refinements
				this.searchTerm = q
			} catch (error) {
				// Malformed URL: start unfiltered rather than fail to render.
			}
		},

		/** Store the facet block from a response. */
		applyFacetResponse(payload) {
			this.facets = Array.isArray(payload?.facets) ? payload.facets : []
			this.approximate = Boolean(payload?.meta?.approximate)
			this.scannedCount = Number(payload?.meta?.scanned ?? 0)
			this.capCount = Number(payload?.meta?.cap ?? 0)
		},
	},

	beforeUnmount() {
		clearTimeout(this._facetDebounce)
	},
}
