<template>
	<NcDialog
		:open="true"
		:name="t('intravox', 'Page Settings')"
		size="normal"
		@close="$emit('close')">
		<!-- Hidden element to capture initial focus and blur it -->
		<div ref="focusTrap" tabindex="-1" class="focus-trap" />
		<div class="page-settings">
			<div class="settings-section">
				<h3 class="settings-section-header">
					<span class="settings-icon">💬</span>
					{{ t('intravox', 'Engagement') }}
				</h3>
				<p class="settings-section-desc">
					{{ t('intravox', 'Override global engagement settings for this page.') }}
				</p>

				<!-- Allow Reactions -->
				<div class="setting-item" :class="{ 'setting-item--disabled': !globalSettings.allowPageReactions }">
					<div class="setting-header">
						<span class="setting-label">{{ t('intravox', 'Allow reactions on this page') }}</span>
						<NcSelect
							v-if="globalSettings.allowPageReactions"
							v-model="localSettings.allowReactions"
							:options="disableOnlyOptions"
							:clearable="true"
							:searchable="false"
							:autofocus="false"
							:placeholder="t('intravox', 'Use global setting')"
							class="tri-state-select"
							@clear="localSettings.allowReactions = null" />
						<span v-else class="setting-locked">
							{{ t('intravox', 'Disabled globally') }}
						</span>
					</div>
					<span class="setting-status">{{ getStatusText(localSettings.allowReactions, 'reactions') }}</span>
				</div>

				<!-- Allow Comments -->
				<div class="setting-item" :class="{ 'setting-item--disabled': !globalSettings.allowComments }">
					<div class="setting-header">
						<span class="setting-label">{{ t('intravox', 'Allow comments on this page') }}</span>
						<NcSelect
							v-if="globalSettings.allowComments"
							v-model="localSettings.allowComments"
							:options="disableOnlyOptions"
							:clearable="true"
							:searchable="false"
							:autofocus="false"
							:placeholder="t('intravox', 'Use global setting')"
							class="tri-state-select"
							@clear="localSettings.allowComments = null" />
						<span v-else class="setting-locked">
							{{ t('intravox', 'Disabled globally') }}
						</span>
					</div>
					<span class="setting-status">{{ getStatusText(localSettings.allowComments, 'comments') }}</span>
				</div>

				<!-- Allow Comment Reactions (only show if comments are enabled) -->
				<div v-if="showCommentReactionsOption" class="setting-item" :class="{ 'setting-item--disabled': !globalSettings.allowCommentReactions }">
					<div class="setting-header">
						<span class="setting-label">{{ t('intravox', 'Allow reactions on comments') }}</span>
						<NcSelect
							v-if="globalSettings.allowCommentReactions"
							v-model="localSettings.allowCommentReactions"
							:options="disableOnlyOptions"
							:clearable="true"
							:searchable="false"
							:autofocus="false"
							:placeholder="t('intravox', 'Use global setting')"
							class="tri-state-select"
							@clear="localSettings.allowCommentReactions = null" />
						<span v-else class="setting-locked">
							{{ t('intravox', 'Disabled globally') }}
						</span>
					</div>
					<span class="setting-status">{{ getStatusText(localSettings.allowCommentReactions, 'commentReactions') }}</span>
				</div>
			</div>
		</div>

		<template #actions>
			<NcButton type="tertiary" @click="$emit('close')">
				{{ t('intravox', 'Cancel') }}
			</NcButton>
			<NcButton type="primary" @click="save">
				{{ t('intravox', 'Save') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script>
import { NcDialog, NcButton, NcSelect } from '@nextcloud/vue'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'PageSettingsModal',
	components: {
		NcDialog,
		NcButton,
		NcSelect,
	},
	props: {
		settings: {
			type: Object,
			default: () => ({
				allowReactions: null,
				allowComments: null,
				allowCommentReactions: null,
			}),
		},
		globalSettings: {
			type: Object,
			default: () => ({
				allowPageReactions: true,
				allowComments: true,
				allowCommentReactions: true,
			}),
		},
	},
	emits: ['close', 'save'],
	data() {
		return {
			localSettings: {
				allowReactions: null,
				allowComments: null,
				allowCommentReactions: null,
			},
		}
	},
	computed: {
		// Only allow disabling per page when globally enabled
		// Cannot enable something that is globally disabled
		disableOnlyOptions() {
			return [
				{ value: false, label: t('intravox', 'Disabled') },
			]
		},
		// Check if comments are effectively enabled for this page
		// (globally enabled AND not disabled at page level)
		commentsEffectivelyEnabled() {
			// If comments are globally disabled, they're off
			if (!this.globalSettings.allowComments) {
				return false
			}
			// If comments are explicitly disabled for this page, they're off
			// Check both the option object format and direct value
			const commentValue = this.localSettings.allowComments?.value ?? this.localSettings.allowComments
			if (commentValue === false) {
				return false
			}
			return true
		},
		// Only show comment reactions option if comments are enabled
		showCommentReactionsOption() {
			return this.commentsEffectivelyEnabled
		},
	},
	created() {
		// Initialize localSettings - null means use global setting (shown as placeholder)
		// Only set a value if page has an explicit override
		this.localSettings.allowReactions = this.mapToOption(this.settings?.allowReactions)
		this.localSettings.allowComments = this.mapToOption(this.settings?.allowComments)
		this.localSettings.allowCommentReactions = this.mapToOption(this.settings?.allowCommentReactions)
	},
	mounted() {
		// Remove focus from any element after modal opens
		this.$nextTick(() => {
			if (document.activeElement && document.activeElement !== document.body) {
				document.activeElement.blur()
			}
		})
	},
	methods: {
		t(app, key, vars = {}) {
			return t(app, key, vars)
		},
		mapToOption(value) {
			// null = no override, return null (will show placeholder)
			if (value === null || value === undefined) {
				return null
			}
			// Only false (disabled) is a valid page-level override
			// true is not valid because you can't enable something globally disabled
			if (value === false) {
				return this.disableOnlyOptions.find(opt => opt.value === false)
			}
			return null
		},
		getStatusText(option, type) {
			let globalEnabled
			if (type === 'reactions') {
				globalEnabled = this.globalSettings.allowPageReactions
			} else if (type === 'comments') {
				globalEnabled = this.globalSettings.allowComments
			} else if (type === 'commentReactions') {
				globalEnabled = this.globalSettings.allowCommentReactions
			}

			// If globally disabled, always show that
			if (!globalEnabled) {
				return this.t('intravox', 'Disabled by administrator')
			}

			const value = option?.value
			if (value === false) {
				return this.t('intravox', 'Disabled for this page')
			}
			// null = inherit from global (which is enabled if we got here)
			return this.t('intravox', 'Using global setting (enabled)')
		},
		save() {
			this.$emit('save', {
				allowReactions: this.localSettings.allowReactions?.value ?? null,
				allowComments: this.localSettings.allowComments?.value ?? null,
				allowCommentReactions: this.localSettings.allowCommentReactions?.value ?? null,
			})
		},
	},
}
</script>

<style scoped>
.focus-trap {
	position: absolute;
	width: 0;
	height: 0;
	overflow: hidden;
}

.page-settings {
	padding: 16px 0;
}

.settings-section {
	padding: 0 16px;
}

.settings-section-header {
	display: flex;
	align-items: center;
	gap: 8px;
	margin: 0 0 8px 0;
	font-size: 16px;
	font-weight: 600;
}

.settings-icon {
	font-size: 20px;
}

.settings-section-desc {
	margin: 0 0 16px 0;
	font-size: 13px;
	color: var(--color-text-maxcontrast);
}

.setting-item {
	margin-bottom: 16px;
	padding: 12px;
	background: var(--color-background-hover);
	border-radius: var(--border-radius-large);
}

.setting-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 16px;
	margin-bottom: 4px;
}

.setting-label {
	font-weight: 500;
	color: var(--color-main-text);
}

.setting-status {
	font-size: 12px;
	color: var(--color-text-maxcontrast);
	font-style: italic;
}

.tri-state-select {
	min-width: 180px;
}

.setting-item--disabled {
	opacity: 0.6;
}

.setting-locked {
	padding: 6px 12px;
	font-size: 13px;
	color: var(--color-text-maxcontrast);
	background: var(--color-background-dark);
	border-radius: var(--border-radius);
	font-style: italic;
}
</style>
