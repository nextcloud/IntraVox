<template>
	<div v-if="chips.length > 0" class="filter-chips" :class="{ 'filter-chips--on-dark': dark }">
		<button
			v-for="chip in chips"
			:key="chip.key"
			type="button"
			class="filter-chips__chip"
			:title="t('intravox', 'Remove filter {label}', { label: chip.text })"
			@click="remove(chip)">
			<span class="filter-chips__text">{{ chip.text }}</span>
			<Close :size="14" />
		</button>

		<button
			v-if="chips.length > 1"
			type="button"
			class="filter-chips__clear"
			@click="$emit('clear-all')">
			{{ t('intravox', 'Clear all') }}
		</button>
	</div>
</template>

<script>
import { translate } from '@nextcloud/l10n'
import Close from 'vue-material-design-icons/Close.vue'

export default {
	name: 'FilterChips',

	components: {
		Close,
	},

	props: {
		/**
		 * Whether the chips sit on a dark widget background. The theme's text
		 * colours are computed against the page background, so on a coloured
		 * widget "Clear all" renders near-invisible; this switches to the
		 * panel's own tokens. Same switch as FilterPanel.
		 */
		dark: {
			type: Boolean,
			default: false,
		},
		/** field => array of selected values */
		refinements: {
			type: Object,
			default: () => ({}),
		},
		/** field => human label */
		facetLabels: {
			type: Object,
			default: () => ({}),
		},
		searchTerm: {
			type: String,
			default: '',
		},
	},

	emits: ['remove', 'remove-search', 'clear-all'],

	computed: {
		chips() {
			const chips = []

			if (this.searchTerm) {
				chips.push({
					key: '$q',
					isSearch: true,
					text: `"${this.searchTerm}"`,
				})
			}

			for (const [field, values] of Object.entries(this.refinements)) {
				if (!Array.isArray(values)) {
					continue
				}
				const label = this.facetLabels[field] || field
				for (const value of values) {
					chips.push({
						key: `${field}::${value}`,
						field,
						value,
						// "Rol: Manager" reads better than a bare value once
						// several facets are active at once.
						text: `${label}: ${value}`,
					})
				}
			}

			return chips
		},
	},

	methods: {
		t: translate,

		remove(chip) {
			if (chip.isSearch) {
				this.$emit('remove-search')
				return
			}
			this.$emit('remove', { field: chip.field, value: chip.value })
		},
	},
}
</script>

<style scoped>
.filter-chips {
	--iv-chip-clear-text: var(--color-text-maxcontrast);
	--iv-chip-clear-hover-text: var(--color-main-text);
	--iv-chip-clear-hover-bg: var(--color-background-hover);

	display: flex;
	flex-wrap: wrap;
	align-items: center;
	gap: 6px;
	margin-bottom: 12px;
}

/* On a dark widget background --color-text-maxcontrast is a mid grey computed
   against the page, which all but disappears. Mirrors .filter-panel--on-dark. */
.filter-chips--on-dark {
	--iv-chip-clear-text: rgba(255, 255, 255, 0.85);
	--iv-chip-clear-hover-text: #fff;
	--iv-chip-clear-hover-bg: rgba(255, 255, 255, 0.15);
}

.filter-chips__chip {
	display: inline-flex;
	align-items: center;
	gap: 4px;
	max-width: 100%;
	padding: 3px 6px 3px 10px;
	border: none;
	border-radius: var(--border-radius-pill);
	background-color: var(--color-primary-element);
	color: var(--color-primary-element-text);
	font-size: 0.9em;
	cursor: pointer;
}

.filter-chips__chip:hover,
.filter-chips__chip:focus-visible {
	background-color: var(--color-primary-element-hover);
}

.filter-chips__text {
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.filter-chips__clear {
	padding: 3px 8px;
	background: transparent;
	border: none;
	border-radius: var(--border-radius-pill);
	color: var(--iv-chip-clear-text);
	font-size: 0.9em;
	cursor: pointer;
	text-decoration: underline;
}

.filter-chips__clear:hover,
.filter-chips__clear:focus-visible {
	color: var(--iv-chip-clear-hover-text);
	background-color: var(--iv-chip-clear-hover-bg);
}
</style>
