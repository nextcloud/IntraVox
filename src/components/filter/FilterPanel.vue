<template>
	<div class="filter-panel" :class="{ 'filter-panel--on-dark': dark }">
		<div class="filter-panel__head">
			<h4 class="filter-panel__title">{{ t('intravox', 'Filters') }}</h4>
			<button
				v-if="activeCount > 0"
				type="button"
				class="filter-panel__clear"
				@click="$emit('clear-all')">
				{{ t('intravox', 'Clear') }}
			</button>
		</div>

		<div v-if="searchEnabled" class="filter-panel__search">
			<Magnify :size="18" class="filter-panel__search-icon" />
			<input
				:value="searchTerm"
				type="search"
				class="filter-panel__search-input"
				:placeholder="searchPlaceholder || t('intravox', 'Search by name …')"
				:aria-label="searchPlaceholder || t('intravox', 'Search by name')"
				@input="onSearchInput">
		</div>

		<NcNoteCard
			v-if="approximate"
			type="warning"
			class="filter-panel__note">
			{{ t('intravox', 'Showing results from the first {cap} accounts. Counts may be incomplete — add a group filter in the widget settings for exact numbers.', { cap: capCount }) }}
		</NcNoteCard>

		<div v-if="loading && facets.length === 0" class="filter-panel__loading">
			<NcLoadingIcon :size="20" />
		</div>

		<FacetGroup
			v-for="facet in facets"
			:key="facet.field"
			:field="facet.field"
			:label="labelFor(facet)"
			:values="facet.values"
			:limit="limitFor(facet)"
			:counts-available="facet.countsAvailable !== false"
			:approximate="approximate"
			:start-collapsed="collapsedFor(facet)"
			:dark="dark"
			@toggle="$emit('toggle-value', $event)" />

		<p v-if="!loading && facets.length === 0" class="filter-panel__empty">
			{{ t('intravox', 'No filters available') }}
		</p>
	</div>
</template>

<script>
import { NcLoadingIcon, NcNoteCard } from '@nextcloud/vue'
import { translate } from '@nextcloud/l10n'
import Magnify from 'vue-material-design-icons/Magnify.vue'
import FacetGroup from './FacetGroup.vue'

export default {
	name: 'FilterPanel',

	components: {
		FacetGroup,
		Magnify,
		NcLoadingIcon,
		NcNoteCard,
	},

	props: {
		facets: {
			type: Array,
			default: () => [],
		},
		/** field => label, from the widget config. */
		facetLabels: {
			type: Object,
			default: () => ({}),
		},
		/**
		 * Whether the panel sits on a dark widget background. The theme text
		 * colours assume the page background, so on a coloured widget they
		 * render near-invisible; this switches the panel to its own tokens.
		 */
		dark: {
			type: Boolean,
			default: false,
		},
		/** Raw viewerFilters.facets entries, for per-facet limit/collapsed. */
		facetConfig: {
			type: Array,
			default: () => [],
		},
		searchTerm: {
			type: String,
			default: '',
		},
		searchEnabled: {
			type: Boolean,
			default: true,
		},
		searchPlaceholder: {
			type: String,
			default: '',
		},
		loading: {
			type: Boolean,
			default: false,
		},
		approximate: {
			type: Boolean,
			default: false,
		},
		capCount: {
			type: Number,
			default: 0,
		},
		activeCount: {
			type: Number,
			default: 0,
		},
	},

	emits: ['toggle-value', 'update:search-term', 'clear-all'],

	methods: {
		t: translate,

		labelFor(facet) {
			return this.facetLabels[facet.field] || facet.label || facet.field
		},

		configFor(field) {
			return this.facetConfig.find(f => f && typeof f === 'object' && f.field === field) ?? {}
		},

		limitFor(facet) {
			const limit = Number(this.configFor(facet.field).limit)
			return Number.isFinite(limit) && limit > 0 ? limit : 8
		},

		collapsedFor(facet) {
			return this.configFor(facet.field).collapsed === true
		},

		onSearchInput(event) {
			// Debouncing lives in the mixin, so every consumer gets the same
			// 300 ms rather than each input reinventing it.
			this.$emit('update:search-term', event.target.value)
		},
	},
}
</script>

<style scoped>
.filter-panel {
	display: flex;
	flex-direction: column;
	gap: 2px;

	/* One place decides the panel's colours; FacetGroup inherits these, so
	   the two cannot drift apart on a coloured background. */
	--iv-filter-text: var(--color-main-text);
	--iv-filter-muted: var(--color-text-maxcontrast);
	--iv-filter-border: var(--color-border);
	--iv-filter-hover: var(--color-background-hover);
	--iv-filter-field-bg: var(--color-main-background);
	--iv-filter-field-text: var(--color-main-text);
	--iv-filter-field-border: var(--color-border-maxcontrast);
	--iv-filter-count-bg: var(--color-background-dark);
	--iv-filter-count-text: var(--color-text-maxcontrast);
	--iv-filter-accent: var(--color-primary-element);
}

/* On a dark widget background the theme's text colours are unreadable, so
   the panel derives everything from the background itself: white text at
   full strength, and translucent whites for the supporting surfaces. Those
   keep their contrast whatever colour the widget is set to. */
.filter-panel--on-dark {
	--iv-filter-text: #fff;
	--iv-filter-muted: rgba(255, 255, 255, 0.85);
	--iv-filter-border: rgba(255, 255, 255, 0.25);
	--iv-filter-hover: rgba(255, 255, 255, 0.15);
	--iv-filter-field-bg: rgba(0, 0, 0, 0.25);
	--iv-filter-field-text: #fff;
	--iv-filter-field-border: rgba(255, 255, 255, 0.4);
	--iv-filter-count-bg: rgba(255, 255, 255, 0.2);
	--iv-filter-count-text: #fff;
	/* A checkbox tinted with the widget colour would vanish into it. */
	--iv-filter-accent: #fff;
}

.filter-panel__head {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 8px;
	margin-bottom: 4px;
}

.filter-panel__title {
	margin: 0;
	font-size: 1.05em;
	font-weight: 600;
	color: var(--iv-filter-text);
}

.filter-panel__clear {
	padding: 2px 8px;
	background: transparent;
	border: none;
	border-radius: var(--border-radius-pill);
	color: var(--iv-filter-muted);
	font-size: 0.9em;
	cursor: pointer;
	text-decoration: underline;
}

.filter-panel__clear:hover {
	color: var(--iv-filter-text);
}

.filter-panel__search {
	position: relative;
	margin-bottom: 8px;
}

.filter-panel__search-icon {
	position: absolute;
	top: 50%;
	inset-inline-start: 8px;
	transform: translateY(-50%);
	color: var(--iv-filter-muted);
	pointer-events: none;
	z-index: 1;
}

.filter-panel__search-input {
	width: 100%;
	padding-inline-start: 32px;
	background-color: var(--iv-filter-field-bg);
	color: var(--iv-filter-field-text);
	border-color: var(--iv-filter-field-border);
}

.filter-panel__search-input::placeholder {
	color: var(--iv-filter-muted);
	opacity: 1;
}

.filter-panel__note {
	margin: 4px 0 8px;
}

.filter-panel__loading {
	display: flex;
	justify-content: center;
	padding: 12px 0;
}

.filter-panel__empty {
	margin: 8px 0 0;
	color: var(--iv-filter-muted);
	font-size: 0.9em;
}
</style>
