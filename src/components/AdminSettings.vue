<template>
	<div class="intravox-admin-settings">
		<div class="settings-section">
			<h2>{{ t('intravox', 'Demo Data') }}</h2>
			<p class="settings-section-desc">
				{{ t('intravox', 'Install demo content to quickly set up your intranet with example pages, navigation, and images.') }}
			</p>

			<div class="demo-data-info">
				<p v-if="!setupComplete" class="info-note info-setup">
					{{ t('intravox', 'The IntraVox GroupFolder will be created automatically when you install demo data.') }}
				</p>
			</div>

			<table class="demo-data-table">
				<thead>
					<tr>
						<th>{{ t('intravox', 'Language') }}</th>
						<th>{{ t('intravox', 'Content') }}</th>
						<th>{{ t('intravox', 'Status') }}</th>
						<th>{{ t('intravox', 'Action') }}</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="lang in languages" :key="lang.code">
						<td class="language-cell">
							<span class="flag">{{ lang.flag }}</span>
							<span class="name">{{ lang.name }}</span>
						</td>
						<td class="content-cell">
							<span v-if="lang.fullContent" class="content-badge full">
								{{ t('intravox', 'Full intranet') }}
							</span>
							<span v-else class="content-badge basic">
								{{ t('intravox', 'Homepage only') }}
							</span>
						</td>
						<td class="status-cell">
							<span :class="['status-badge', lang.status]">
								{{ getStatusLabel(lang.status) }}
							</span>
						</td>
						<td class="action-cell">
							<!-- Not installed: single Install button -->
							<template v-if="lang.canInstall && lang.status !== 'installed'">
								<NcButton
									:disabled="installing !== null"
									type="primary"
									@click="installDemoData(lang.code)">
									<template #icon>
										<span v-if="installing === lang.code" class="icon-loading-small"></span>
									</template>
									{{ installing === lang.code ? t('intravox', 'Installing...') : t('intravox', 'Install') }}
								</NcButton>
							</template>
							<!-- Already installed: reinstall button -->
							<template v-else-if="lang.canInstall && lang.status === 'installed'">
								<NcButton
									:disabled="installing !== null"
									type="secondary"
									@click="showReinstallDialog(lang.code)">
									<template #icon>
										<span v-if="installing === lang.code" class="icon-loading-small"></span>
									</template>
									{{ installing === lang.code ? t('intravox', 'Reinstalling...') : t('intravox', 'Reinstall') }}
								</NcButton>
							</template>
							<span v-else class="unavailable">
								{{ t('intravox', 'Not available') }}
							</span>
						</td>
					</tr>
				</tbody>
			</table>

			<div v-if="message" :class="['message', messageType]">
				{{ message }}
			</div>
		</div>

		<!-- Reinstall confirmation dialog -->
		<NcDialog
			v-if="reinstallDialogVisible"
			:name="t('intravox', 'Reinstall demo data')"
			@closing="reinstallDialogVisible = false">
			<template #default>
				<div class="reinstall-dialog-content">
					<NcNoteCard type="warning">
						{{ t('intravox', 'This will delete all existing demo content for') }} <strong>{{ reinstallLanguageName }}</strong> {{ t('intravox', 'and replace it with fresh demo data.') }}
					</NcNoteCard>
					<p class="reinstall-warning">
						{{ t('intravox', 'Any changes you made to the demo pages will be lost. This action cannot be undone.') }}
					</p>
				</div>
			</template>
			<template #actions>
				<NcButton type="tertiary" @click="reinstallDialogVisible = false">
					{{ t('intravox', 'Cancel') }}
				</NcButton>
				<NcButton type="error" @click="confirmReinstall">
					{{ t('intravox', 'Reinstall') }}
				</NcButton>
			</template>
		</NcDialog>
	</div>
</template>

<script>
import { NcButton, NcDialog, NcNoteCard } from '@nextcloud/vue'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export default {
	name: 'AdminSettings',
	components: {
		NcButton,
		NcDialog,
		NcNoteCard,
	},
	props: {
		initialState: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			languages: this.initialState.languages || [],
			setupComplete: this.initialState.setupComplete !== false,
			installing: null,
			message: '',
			messageType: 'success',
			reinstallDialogVisible: false,
			reinstallLanguageCode: null,
		}
	},
	computed: {
		reinstallLanguageName() {
			if (!this.reinstallLanguageCode) return ''
			const langData = this.languages.find(l => l.code === this.reinstallLanguageCode)
			return langData?.name || this.reinstallLanguageCode
		},
	},
	methods: {
		getStatusLabel(status) {
			const labels = {
				not_installed: this.t('intravox', 'Not installed'),
				installed: this.t('intravox', 'Installed'),
				empty: this.t('intravox', 'Empty folder'),
				unavailable: this.t('intravox', 'Unavailable'),
				setup_required: this.t('intravox', 'Not installed'),
			}
			return labels[status] || status
		},
		showReinstallDialog(language) {
			this.reinstallLanguageCode = language
			this.reinstallDialogVisible = true
		},
		confirmReinstall() {
			this.reinstallDialogVisible = false
			if (this.reinstallLanguageCode) {
				this.installDemoData(this.reinstallLanguageCode)
			}
		},
		async installDemoData(language) {
			this.installing = language
			this.message = ''

			try {
				const response = await axios.post(
					generateUrl('/apps/intravox/api/demo-data/import'),
					{ language, mode: 'overwrite' }
				)

				if (response.data.success) {
					this.message = response.data.message || this.t('intravox', 'Demo data installed successfully')
					this.messageType = 'success'
					// Refresh status
					await this.refreshStatus()
				} else {
					this.message = response.data.message || this.t('intravox', 'Failed to install demo data')
					this.messageType = 'error'
				}
			} catch (error) {
				console.error('Failed to install demo data:', error)
				this.message = error.response?.data?.message || this.t('intravox', 'Failed to install demo data')
				this.messageType = 'error'
			} finally {
				this.installing = null
			}
		},
		async refreshStatus() {
			try {
				const response = await axios.get(
					generateUrl('/apps/intravox/api/demo-data/status')
				)
				if (response.data.languages) {
					this.languages = response.data.languages
				}
				if (response.data.setupComplete !== undefined) {
					this.setupComplete = response.data.setupComplete
				}
			} catch (error) {
				console.error('Failed to refresh status:', error)
			}
		},
		t(app, text) {
			return window.t ? window.t(app, text) : text
		},
	},
}
</script>

<style scoped>
.intravox-admin-settings {
	max-width: 800px;
	padding: 20px;
}

.settings-section h2 {
	font-size: 20px;
	font-weight: bold;
	margin-bottom: 8px;
}

.settings-section-desc {
	color: var(--color-text-maxcontrast);
	margin-bottom: 20px;
}

.demo-data-info {
	margin-bottom: 20px;
}

.info-note.info-setup {
	background-color: var(--color-primary-element-light);
	border-left-color: var(--color-primary-element);
	margin-bottom: 12px;
}

.info-note {
	background-color: var(--color-background-hover);
	padding: 12px;
	border-radius: 4px;
	border-left: 3px solid var(--color-primary);
}

.demo-data-table {
	width: 100%;
	border-collapse: collapse;
	margin-bottom: 20px;
}

.demo-data-table th,
.demo-data-table td {
	padding: 12px;
	text-align: left;
	border-bottom: 1px solid var(--color-border);
}

.demo-data-table th {
	font-weight: bold;
	color: var(--color-text-maxcontrast);
}

.language-cell .flag {
	font-size: 1.5em;
	margin-right: 8px;
}

.language-cell .name {
	font-weight: 500;
}

.content-badge {
	display: inline-block;
	padding: 4px 8px;
	border-radius: 4px;
	font-size: 0.85em;
}

.content-badge.full {
	background-color: var(--color-success-hover);
	color: var(--color-success-text);
}

.content-badge.basic {
	background-color: var(--color-background-hover);
	color: var(--color-text-maxcontrast);
}

.status-badge {
	display: inline-block;
	padding: 4px 8px;
	border-radius: 4px;
	font-size: 0.85em;
}

.status-badge.not_installed {
	background-color: var(--color-background-hover);
	color: var(--color-text-maxcontrast);
}

.status-badge.installed {
	background-color: var(--color-success-hover);
	color: var(--color-success-text);
}

.status-badge.empty {
	background-color: var(--color-warning-hover);
	color: var(--color-warning-text);
}

.status-badge.unavailable {
	background-color: var(--color-error-hover);
	color: var(--color-error-text);
}

.status-badge.setup_required {
	background-color: var(--color-warning-hover);
	color: var(--color-warning-text);
}

.action-cell .unavailable {
	color: var(--color-text-maxcontrast);
	font-style: italic;
}

.message {
	padding: 12px;
	border-radius: 4px;
	margin-top: 16px;
}

.message.success {
	background-color: var(--color-success-hover);
	color: var(--color-success-text);
}

.message.error {
	background-color: var(--color-error-hover);
	color: var(--color-error-text);
}

.icon-loading-small {
	display: inline-block;
	width: 16px;
	height: 16px;
	border: 2px solid transparent;
	border-top-color: currentColor;
	border-radius: 50%;
	animation: spin 0.8s linear infinite;
}

@keyframes spin {
	to { transform: rotate(360deg); }
}

.icon-download {
	display: inline-block;
	width: 16px;
	height: 16px;
}

.reinstall-dialog-content {
	padding: 8px 0;
}

.reinstall-warning {
	margin-top: 12px;
	color: var(--color-text-maxcontrast);
}
</style>
