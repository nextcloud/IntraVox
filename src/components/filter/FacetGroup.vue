<template>
	<div class="facet-group">
		<button
			class="facet-group__header"
			type="button"
			:aria-expanded="String(expanded)"
			@click="expanded = !expanded">
			<ChevronDown v-if="expanded" :size="18" />
			<ChevronRight v-else :size="18" />
			<span class="facet-group__label">{{ label }}</span>
			<span v-if="selectedCount > 0" class="facet-group__badge">{{ selectedCount }}</span>
		</button>

		<div v-show="expanded" class="facet-group__body">
			<input
				v-if="values.length > TYPEAHEAD_THRESHOLD"
				v-model="needle"
				type="search"
				class="facet-group__search"
				:placeholder="t('intravox', 'Filter options …')"
				:aria-label="t('intravox', 'Filter options in {facet}', { facet: label })">

			<ul class="facet-group__list">
				<li v-for="entry in visibleValues" :key="entry.value">
					<label class="facet-group__option" :class="{ 'facet-group__option--empty': entry.count === 0 }">
						<input
							type="checkbox"
							class="facet-group__checkbox"
							:checked="entry.selected"
							@change="$emit('toggle', { field, value: entry.value })">
						<span class="facet-group__value" :title="entry.label">{{ entry.label }}</span>
						<span v-if="countsAvailable" class="facet-group__count">{{ formatCount(entry.count) }}</span>
					</label>
				</li>
			</ul>

			<p v-if="visibleValues.length === 0" class="facet-group__none">
				{{ t('intravox', 'No matching options') }}
			</p>

			<button
				v-if="canShowMore"
				type="button"
				class="facet-group__more"
				@click="showAll = true">
				{{ t('intravox', 'Show all ({count})', { count: filteredValues.length }) }}
			</button>
		</div>
	</div>
</template>

<script>
import { translate } from '@nextcloud/l10n'
import ChevronDown from 'vue-material-design-icons/ChevronDown.vue'
import ChevronRight from 'vue-material-design-icons/ChevronRight.vue'

export default {
	name: 'FacetGroup',

	components: {
		ChevronDown,
		ChevronRight,
	},

	props: {
		field: {
			type: String,
			required: true,
		},
		label: {
			type: String,
			default: '',
		},
		values: {
			type: Array,
			default: () => [],
		},
		/** How many values to show before "Show all". */
		limit: {
			type: Number,
			default: 8,
		},
		/**
		 * False when the server could not count reliably (a candidate set
		 * over the cap). The panel still offers the choices; it just does not
		 * put a number next to them, because a wrong number beside a checkbox
		 * discredits the whole panel.
		 */
		countsAvailable: {
			type: Boolean,
			default: true,
		},
		/** Marks counts as partial, rendered as "~12". */
		approximate: {
			type: Boolean,
			default: false,
		},
		startCollapsed: {
			type: Boolean,
			default: false,
		},
	},

	emits: ['toggle'],

	data() {
		return {
			expanded: !this.startCollapsed,
			showAll: false,
			needle: '',
			TYPEAHEAD_THRESHOLD: 10,
		}
	},

	computed: {
		filteredValues() {
			const needle = this.needle.trim().toLowerCase()
			if (!needle) {
				return this.values
			}
			return this.values.filter(v => String(v.label ?? v.value).toLowerCase().includes(needle))
		},

		visibleValues() {
			if (this.showAll || this.needle) {
				return this.filteredValues
			}
			return this.filteredValues.slice(0, this.limit)
		},

		canShowMore() {
			return !this.showAll && !this.needle && this.filteredValues.length > this.limit
		},

		selectedCount() {
			return this.values.filter(v => v.selected).length
		},
	},

	methods: {
		t: translate,

		formatCount(count) {
			// The tilde is the honest signal that the scan was capped, so a
			// partial number never reads as an exact one.
			return this.approximate ? `~${count}` : String(count)
		},
	},
}
</script>

<style scoped>
.facet-group {
	border-bottom: 1px solid var(--iv-filter-border, var(--color-border));
	padding-bottom: 4px;
}

.facet-group:last-child {
	border-bottom: none;
}

.facet-group__header {
	display: flex;
	align-items: center;
	gap: 4px;
	width: 100%;
	padding: 10px 4px;
	background: transparent;
	border: none;
	border-radius: var(--border-radius);
	cursor: pointer;
	font-weight: 600;
	color: var(--iv-filter-text, var(--color-main-text));
	text-align: left;
}

.facet-group__header:hover,
.facet-group__header:focus-visible {
	background-color: var(--iv-filter-hover, var(--color-background-hover));
}

.facet-group__label {
	flex: 1 1 auto;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.facet-group__badge {
	flex: 0 0 auto;
	min-width: 20px;
	padding: 1px 7px;
	border-radius: var(--border-radius-pill);
	background-color: var(--color-primary-element);
	color: var(--color-primary-element-text);
	font-size: 0.8em;
	text-align: center;
}

.facet-group__body {
	padding: 0 4px 4px;
}

.facet-group__search {
	width: 100%;
	margin-bottom: 6px;
	background-color: var(--iv-filter-field-bg, var(--color-main-background));
	color: var(--iv-filter-field-text, var(--color-main-text));
	border-color: var(--iv-filter-field-border, var(--color-border-maxcontrast));
}

.facet-group__search::placeholder {
	color: var(--iv-filter-muted, var(--color-text-maxcontrast));
	opacity: 1;
}

.facet-group__list {
	list-style: none;
	margin: 0;
	padding: 0;
}

.facet-group__option {
	display: flex;
	align-items: center;
	gap: 8px;
	padding: 5px 6px;
	border-radius: var(--border-radius);
	cursor: pointer;
}

.facet-group__option:hover {
	background-color: var(--iv-filter-hover, var(--color-background-hover));
}

/* A value at zero stays clickable — it may be the one the viewer selected —
   but should not compete visually with values that still yield results. */
.facet-group__option--empty .facet-group__value,
.facet-group__option--empty .facet-group__count {
	opacity: 0.5;
}

.facet-group__checkbox {
	flex: 0 0 auto;
	margin: 0;
	cursor: pointer;
	accent-color: var(--iv-filter-accent, var(--color-primary-element));
}

.facet-group__value {
	flex: 1 1 auto;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
	color: var(--iv-filter-text, var(--color-main-text));
}

.facet-group__count {
	flex: 0 0 auto;
	padding: 0 6px;
	border-radius: var(--border-radius-pill);
	background-color: var(--iv-filter-count-bg, var(--color-background-dark));
	color: var(--iv-filter-count-text, var(--color-text-maxcontrast));
	font-size: 0.85em;
	font-variant-numeric: tabular-nums;
}

.facet-group__none,
.facet-group__more {
	margin: 4px 0 0;
	padding: 4px 6px;
	background: transparent;
	border: none;
	color: var(--iv-filter-muted, var(--color-text-maxcontrast));
	font-size: 0.9em;
}

.facet-group__more {
	cursor: pointer;
	text-decoration: underline;
}

.facet-group__more:hover {
	color: var(--iv-filter-text, var(--color-main-text));
}
</style>
