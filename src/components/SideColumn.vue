<template>
	<div class="side-column"
	     :class="[`side-column-${side}`, { 'side-column-edit-mode': editable }]"
	     :style="getColumnStyle()">
		<!-- Edit mode controls -->
		<div v-if="editable" class="side-column-header">
			<span class="side-column-title">{{ side === 'left' ? t('Left column') : t('Right column') }}</span>
			<div class="side-column-actions">
				<!-- Background color picker -->
				<NcColorPicker v-model="localBackgroundColor"
				               @update:modelValue="updateBackgroundColor">
					<NcButton type="tertiary"
					          :aria-label="t('Background color')"
					          :title="t('Background color')">
						<template #icon>
							<FormatColorFill :size="20" />
						</template>
					</NcButton>
				</NcColorPicker>

				<!-- Delete button -->
				<NcButton type="tertiary"
				          @click="$emit('remove')"
				          :aria-label="t('Remove column')"
				          :title="t('Remove column')">
					<template #icon>
						<Delete :size="20" />
					</template>
				</NcButton>
			</div>
		</div>

		<!-- Widgets container -->
		<div class="side-column-widgets">
			<Widget
				v-for="(widget, index) in sortedWidgets"
				:key="widget.id || index"
				:widget="widget"
				:page-id="pageId"
				:editable="editable"
				:row-background-color="backgroundColor || ''"
				@edit="$emit('edit-widget', widget)"
				@delete="$emit('delete-widget', widget)"
				@navigate="$emit('navigate', $event)"
			/>

			<!-- Add widget button in edit mode -->
			<div v-if="editable" class="side-column-add-widget">
				<NcButton type="secondary"
				          @click="$emit('add-widget')"
				          :aria-label="t('Add widget')">
					<template #icon>
						<Plus :size="20" />
					</template>
					{{ t('Add Widget') }}
				</NcButton>
			</div>

			<!-- Empty state -->
			<div v-if="!editable && sortedWidgets.length === 0" class="side-column-empty">
				<!-- Empty side column in view mode, nothing to show -->
			</div>
		</div>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcColorPicker } from '@nextcloud/vue'
import Widget from './Widget.vue'
import Plus from 'vue-material-design-icons/Plus.vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import FormatColorFill from 'vue-material-design-icons/FormatColorFill.vue'

export default {
	name: 'SideColumn',
	components: {
		NcButton,
		NcColorPicker,
		Widget,
		Plus,
		Delete,
		FormatColorFill
	},
	props: {
		side: {
			type: String,
			required: true,
			validator: (value) => ['left', 'right'].includes(value)
		},
		widgets: {
			type: Array,
			default: () => []
		},
		backgroundColor: {
			type: String,
			default: ''
		},
		pageId: {
			type: String,
			default: ''
		},
		editable: {
			type: Boolean,
			default: false
		}
	},
	emits: ['update:backgroundColor', 'add-widget', 'edit-widget', 'delete-widget', 'remove', 'navigate'],
	data() {
		return {
			localBackgroundColor: this.backgroundColor || ''
		}
	},
	computed: {
		sortedWidgets() {
			if (!this.widgets) return []
			return [...this.widgets].sort((a, b) => (a.order || 0) - (b.order || 0))
		}
	},
	watch: {
		backgroundColor(newVal) {
			this.localBackgroundColor = newVal || ''
		}
	},
	methods: {
		t(key, vars = {}) {
			return t('intravox', key, vars)
		},
		getColumnStyle() {
			const style = {}

			if (this.backgroundColor) {
				style.backgroundColor = this.backgroundColor

				// Set text color based on background
				if (this.backgroundColor === 'var(--color-primary-element)') {
					style.color = 'var(--color-primary-element-text)'
				} else {
					style.color = 'var(--color-main-text)'
				}
			}

			return style
		},
		updateBackgroundColor(color) {
			this.localBackgroundColor = color
			this.$emit('update:backgroundColor', color)
		}
	}
}
</script>

<style scoped>
.side-column {
	display: flex;
	flex-direction: column;
	min-width: 200px;
	max-width: 300px;
	padding: 16px;
	border-radius: var(--border-radius-container-large);
	background-color: var(--color-background-dark);
	box-sizing: border-box;
}

.side-column-edit-mode {
	border: 2px dashed var(--color-border-dark);
}

.side-column-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 12px;
	padding-bottom: 8px;
	border-bottom: 1px solid var(--color-border);
}

.side-column-title {
	font-weight: 600;
	font-size: 14px;
}

.side-column-actions {
	display: flex;
	gap: 4px;
}

.side-column-widgets {
	display: flex;
	flex-direction: column;
	gap: 12px;
	flex: 1;
}

.side-column-add-widget {
	margin-top: 8px;
}

.side-column-empty {
	min-height: 50px;
}

/* Mobile responsive */
@media (max-width: 768px) {
	.side-column {
		min-width: 100%;
		max-width: 100%;
		margin-bottom: 16px;
	}
}
</style>
