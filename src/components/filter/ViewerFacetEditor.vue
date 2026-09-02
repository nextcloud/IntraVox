<template>
	<div class="vfe">
		<label class="vfe__toggle">
			<input
				type="checkbox"
				:checked="config.enabled"
				@change="setEnabled($event.target.checked)">
			<span>{{ t('intravox', 'Let visitors filter these results') }}</span>
		</label>

		<p class="vfe__hint">
			{{ t('intravox', 'Visitors pick from these fields themselves. They can only narrow the selection above further — never widen it.') }}
		</p>

		<template v-if="config.enabled">
			<NcNoteCard v-if="preflight.approximate" type="warning" class="vfe__note">
				<p>
					{{ t('intravox', 'This instance has {total} accounts, more than the {cap} this widget scans. Filter counts will be approximate and shown as "~12".', { total: preflight.userCount, cap: preflight.cap }) }}
				</p>
				<p v-if="!hasGroupFilter">
					<strong>{{ t('intravox', 'Add a group filter above') }}</strong>
					{{ t('intravox', 'to scope this widget to one or more groups. Counts then become exact, and the widget loads faster.') }}
				</p>
				<p v-else>
					{{ t('intravox', 'This widget already has a group filter, so its own results are scoped — but the group is larger than the scan limit.') }}
				</p>
			</NcNoteCard>
			<div class="vfe__block">
				<label class="vfe__label">{{ t('intravox', 'Filterable fields') }}</label>

				<p v-if="selectableFields.length === 0" class="vfe__empty">
					{{ t('intravox', 'No fields available to filter on.') }}
				</p>

				<div v-else class="vfe__facet-head" aria-hidden="true">
					<span class="vfe__grip vfe__grip--ghost">
						<DragHorizontalVariant :size="18" />
					</span>
					<span class="vfe__facet-name">{{ t('intravox', 'Field') }}</span>
					<span class="vfe__facet-label vfe__facet-head-label">{{ t('intravox', 'Name shown to visitors') }}</span>
					<span class="vfe__remove vfe__remove--ghost">
						<Close :size="18" />
					</span>
				</div>

				<draggable
					v-model="facetList"
					item-key="field"
					handle=".vfe__grip"
					@end="emit">
					<template #item="{ element }">
						<div class="vfe__facet">
							<span class="vfe__grip" :title="t('intravox', 'Drag to reorder')">
								<DragHorizontalVariant :size="18" />
							</span>
							<span class="vfe__facet-name">{{ labelForField(element.field) }}</span>
							<input
								v-model="element.label"
								type="text"
								class="vfe__facet-label"
								:placeholder="t('intravox', 'Default: {label}', { label: labelForField(element.field) })"
								:aria-label="t('intravox', 'Label shown to visitors')"
								@input="emit">
							<button
								type="button"
								class="vfe__remove"
								:title="t('intravox', 'Remove')"
								@click="removeFacet(element.field)">
								<Close :size="18" />
							</button>
						</div>
					</template>
				</draggable>

				<select
					v-if="addableFields.length > 0"
					class="vfe__add"
					:value="''"
					@change="addFacet($event.target.value)">
					<option value="">{{ t('intravox', '+ Add filter field') }}</option>
					<option v-for="f in addableFields" :key="f.fieldName" :value="f.fieldName">
						{{ f.label }}
					</option>
				</select>

				<p v-if="conflictingFields.length > 0" class="vfe__conflict">
					{{ t('intravox', 'This widget already filters on {fields}. Visitors cannot widen that, so those fields would only show empty options and are not offered here.', { fields: conflictingFields.join(', ') }) }}
				</p>
			</div>

			<div class="vfe__block">
				<label class="vfe__toggle">
					<input
						type="checkbox"
						:checked="config.searchEnabled"
						@change="setKey('searchEnabled', $event.target.checked)">
					<span>{{ t('intravox', 'Show a search box') }}</span>
				</label>
			</div>

			<div class="vfe__block">
				<label class="vfe__label">{{ t('intravox', 'Panel position') }}</label>
				<div class="vfe__layouts">
					<button
						type="button"
						class="vfe__layout"
						:class="{ 'vfe__layout--active': config.layout !== 'top' }"
						@click="setKey('layout', 'sidebar')">
						{{ t('intravox', 'Beside the results') }}
					</button>
					<button
						type="button"
						class="vfe__layout"
						:class="{ 'vfe__layout--active': config.layout === 'top' }"
						@click="setKey('layout', 'top')">
						{{ t('intravox', 'Above the results') }}
					</button>
				</div>
				<p class="vfe__hint">
					{{ t('intravox', 'A side panel suits a full-width page; place it above the results in a narrow column.') }}
				</p>
			</div>

			<NcNoteCard type="info" class="vfe__note">
				{{ t('intravox', 'Filters are not shown on public share links, because the filter values would list your organisation structure to anyone with the link.') }}
			</NcNoteCard>
		</template>
	</div>
</template>

<script>
import { NcNoteCard } from '@nextcloud/vue'
import { translate } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import draggable from 'vuedraggable'
import Close from 'vue-material-design-icons/Close.vue'
import DragHorizontalVariant from 'vue-material-design-icons/DragHorizontalVariant.vue'

const DEFAULT_CONFIG = {
	enabled: false,
	facets: [],
	searchFields: ['displayName', 'role'],
	searchEnabled: true,
	layout: 'sidebar',
}

export default {
	name: 'ViewerFacetEditor',

	components: {
		Close,
		DragHorizontalVariant,
		draggable,
		NcNoteCard,
	},

	props: {
		/** The widget's viewerFilters object. */
		modelValue: {
			type: Object,
			default: null,
		},
		/** Field definitions from /api/users/fields. */
		availableFields: {
			type: Array,
			default: () => [],
		},
		/** The widget's editor-side filters, used to detect conflicts. */
		editorFilters: {
			type: Array,
			default: () => [],
		},
	},

	emits: ['update:modelValue'],

	data() {
		return {
			config: { ...DEFAULT_CONFIG, ...(this.modelValue ?? {}) },
			preflight: { userCount: 0, cap: 0, approximate: false },
		}
	},

	computed: {
		facetList: {
			get() {
				return this.config.facets ?? []
			},
			set(list) {
				this.config.facets = list
			},
		},

		/**
		 * Fields the editor already filters on.
		 *
		 * A facet on one of those could only ever offer options that yield
		 * nothing, because a viewer cannot widen an editor filter. The server
		 * drops such a facet anyway; hiding it here explains why instead of
		 * silently ignoring the choice.
		 */
		conflictingFields() {
			return this.editorFilters
				.map(f => f?.fieldName || f?.field)
				.filter(Boolean)
				.map(f => this.labelForField(f))
		},

		/**
		 * Whether the widget is already scoped to one or more groups.
		 *
		 * A group-scoped cohort skips callForAllUsers() entirely, which is
		 * what makes exact counts affordable past the scan cap — so the
		 * advice differs depending on whether one is already set.
		 */
		hasGroupFilter() {
			return this.editorFilters.some(f => (f?.fieldName || f?.field) === 'group')
		},

		conflictingFieldNames() {
			return this.editorFilters.map(f => f?.fieldName || f?.field).filter(Boolean)
		},

		selectableFields() {
			return this.availableFields.filter(f => !this.conflictingFieldNames.includes(f.fieldName))
		},

		addableFields() {
			const chosen = this.facetList.map(f => f.field)
			return this.selectableFields.filter(f => !chosen.includes(f.fieldName))
		},
	},

	watch: {
		modelValue: {
			deep: true,
			handler(value) {
				this.config = { ...DEFAULT_CONFIG, ...(value ?? {}) }
			},
		},
	},

	mounted() {
		this.loadPreflight()
	},

	methods: {
		t: translate,

		labelForField(fieldName) {
			const match = this.availableFields.find(f => f.fieldName === fieldName)
			return match?.label || fieldName
		},

		setEnabled(enabled) {
			this.config.enabled = enabled
			// Give a first-time user something that works immediately rather
			// than an enabled-but-empty panel.
			if (enabled && this.facetList.length === 0 && this.addableFields.length > 0) {
				this.config.facets = this.addableFields
					.slice(0, 3)
					.map(f => ({ field: f.fieldName, label: '', limit: 8, collapsed: false }))
			}
			this.emit()
		},

		setKey(key, value) {
			this.config[key] = value
			this.emit()
		},

		addFacet(fieldName) {
			if (!fieldName || this.facetList.some(f => f.field === fieldName)) {
				return
			}
			this.config.facets = [
				...this.facetList,
				{ field: fieldName, label: '', limit: 8, collapsed: false },
			]
			this.emit()
		},

		removeFacet(fieldName) {
			this.config.facets = this.facetList.filter(f => f.field !== fieldName)
			this.emit()
		},

		emit() {
			this.$emit('update:modelValue', JSON.parse(JSON.stringify(this.config)))
		},

		/**
		 * Ask the server whether counts will be exact for this instance size.
		 *
		 * Better to say so while the editor is configuring the widget than to
		 * let them discover approximate counts in production.
		 */
		async loadPreflight() {
			try {
				const { data } = await axios.get(generateUrl('/apps/intravox/api/people/facet-preflight'))
				this.preflight = {
					userCount: Number(data.userCount ?? 0),
					cap: Number(data.cap ?? 0),
					approximate: Boolean(data.approximate),
				}
			} catch (error) {
				// A missing preflight is not worth blocking the editor over.
			}
		},
	},
}
</script>

<style scoped>
.vfe {
	display: flex;
	flex-direction: column;
	gap: 10px;
}

.vfe__toggle {
	display: flex;
	align-items: center;
	gap: 8px;
	cursor: pointer;
}

.vfe__hint,
.vfe__empty,
.vfe__conflict {
	margin: 0;
	color: var(--color-text-maxcontrast);
	font-size: 0.85em;
}

.vfe__conflict {
	padding: 6px 8px;
	border-radius: var(--border-radius);
	background-color: var(--color-background-dark);
}

.vfe__block {
	display: flex;
	flex-direction: column;
	gap: 6px;
}

/* Column headings for the rows below.
   This carries the same children and the same box model as .vfe__facet — a
   transparent border standing in for the row's 1px — so flex resolves the
   identical column widths. Deriving the offsets by hand drifts the moment the
   row changes; letting the layout do it cannot. */
.vfe__facet-head {
	display: flex;
	align-items: center;
	gap: 6px;
	padding: 4px;
	border: 1px solid transparent;
	margin-bottom: 2px;
	font-size: 0.85em;
	color: var(--color-text-maxcontrast);
}

/* Reserve the grip and remove columns without showing an icon. */
.vfe__grip--ghost,
.vfe__remove--ghost {
	visibility: hidden;
}

.vfe__facet-head-label {
	/* The heading is a plain span where the row has an input, so it has to
	   reproduce that input's own insets to line up with the placeholder:
	   2px border + 12px padding, plus the 3px margin inputs carry here. */
	padding-inline-start: 17px;
}

.vfe__label {
	font-weight: 600;
	color: var(--color-main-text);
}

.vfe__facet {
	display: flex;
	align-items: center;
	gap: 6px;
	padding: 4px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	background-color: var(--color-main-background);
	margin-bottom: 4px;
}

.vfe__grip {
	display: flex;
	color: var(--color-text-maxcontrast);
	cursor: grab;
}

.vfe__facet-name {
	flex: 0 0 auto;
	min-width: 90px;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.vfe__facet-label {
	flex: 1 1 auto;
	min-width: 0;
}

.vfe__remove {
	flex: 0 0 auto;
	display: flex;
	padding: 2px;
	background: transparent;
	border: none;
	border-radius: var(--border-radius);
	color: var(--color-text-maxcontrast);
	cursor: pointer;
}

.vfe__remove:hover {
	background-color: var(--color-background-hover);
	color: var(--color-error);
}

.vfe__add {
	width: 100%;
}

.vfe__layouts {
	display: flex;
	gap: 6px;
}

.vfe__layout {
	flex: 1 1 0;
	padding: 6px 8px;
	border: 2px solid var(--color-border);
	border-radius: var(--border-radius);
	background-color: var(--color-main-background);
	color: var(--color-main-text);
	cursor: pointer;
	font-size: 0.9em;
}

.vfe__layout--active {
	border-color: var(--color-primary-element);
	background-color: var(--color-primary-element-light);
}

.vfe__note {
	margin: 4px 0;
}
</style>
