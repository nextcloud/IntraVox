<template>
	<div class="support-settings">
		<!-- Section 1: About IntraVox -->
		<div class="settings-section">
			<h2>{{ t('intravox', 'Support IntraVox') }}</h2>
			<p class="settings-section-desc">
				{{ t('intravox', 'IntraVox is free and open source (AGPL-3.0). You can use all features without a subscription — no limits on functionality, no restrictions, no catch.') }}
			</p>
			<p class="settings-section-desc">
				{{ t('intravox', 'If IntraVox is valuable to your organization, consider subscribing. Your subscription funds active development, guaranteed Nextcloud compatibility, and email support.') }}
			</p>
		</div>

		<!-- Section 2: What's included -->
		<div class="settings-section">
			<h2>{{ t('intravox', 'What a subscription includes') }}</h2>

			<div class="includes-list">
				<div class="includes-item">
					<span class="includes-check">&#x2705;</span>
					<div class="includes-text">
						<span class="includes-label">{{ t('intravox', 'Guaranteed compatibility') }}</span>
						<span class="includes-desc">{{ t('intravox', 'Tested with every new Nextcloud release') }}</span>
					</div>
				</div>
				<div class="includes-item">
					<span class="includes-check">&#x2705;</span>
					<div class="includes-text">
						<span class="includes-label">{{ t('intravox', 'Email support') }}</span>
						<span class="includes-desc">{{ t('intravox', 'Direct support from the developers') }}</span>
					</div>
				</div>
				<div class="includes-item">
					<span class="includes-check">&#x2705;</span>
					<div class="includes-text">
						<span class="includes-label">{{ t('intravox', 'Priority bug fixes') }}</span>
						<span class="includes-desc">{{ t('intravox', 'Your issues get priority attention') }}</span>
					</div>
				</div>
				<div class="includes-item">
					<span class="includes-check">&#x2705;</span>
					<div class="includes-text">
						<span class="includes-label">{{ t('intravox', 'Active development') }}</span>
						<span class="includes-desc">{{ t('intravox', 'New features and improvements') }}</span>
					</div>
				</div>
			</div>
		</div>

		<!-- Section 3: Pricing -->
		<div class="settings-section">
			<h2>{{ t('intravox', 'Pricing') }}</h2>

			<div class="pricing-table">
				<div class="pricing-row pricing-row--free">
					<span class="pricing-tier">{{ t('intravox', 'Free') }}</span>
					<span class="pricing-price pricing-price--free">{{ t('intravox', 'Free — 50 pages per language') }}</span>
				</div>
				<div class="pricing-row">
					<span class="pricing-tier">{{ t('intravox', '1–50 users') }}</span>
					<span class="pricing-price">{{ t('intravox', '€39/year') }}</span>
				</div>
				<div class="pricing-row">
					<span class="pricing-tier">{{ t('intravox', '51–250 users') }}</span>
					<span class="pricing-price">{{ t('intravox', '€119/year') }}</span>
				</div>
				<div class="pricing-row">
					<span class="pricing-tier">{{ t('intravox', '251–1000 users') }}</span>
					<span class="pricing-price">{{ t('intravox', '€279/year') }}</span>
				</div>
				<div class="pricing-row">
					<span class="pricing-tier">{{ t('intravox', '1000+ users') }}</span>
					<span class="pricing-price">{{ t('intravox', 'Contact us') }}</span>
				</div>
			</div>

			<p class="pricing-note">
				{{ t('intravox', 'Excl. VAT, per instance, per year. All paid tiers include unlimited pages.') }}
			</p>

			<NcButton type="primary"
				:href="pricingUrl"
				target="_blank"
				rel="noopener noreferrer">
				{{ t('intravox', 'View pricing & subscribe') }}
			</NcButton>
		</div>

		<!-- Section 4: Your installation -->
		<div class="settings-section">
			<h2>{{ t('intravox', 'Your installation') }}</h2>

			<div v-if="licenseStats" class="stats-overview">
				<div
					v-for="lang in licenseStats.supportedLanguages"
					:key="lang"
					class="language-stat-row">
					<div class="language-info">
						<span class="language-flag">{{ getLanguageFlag(lang) }}</span>
						<span class="language-name">{{ getLanguageName(lang) }}</span>
						<span class="language-code">({{ lang }})</span>
					</div>
					<div class="progress-container">
						<div class="progress-bar">
							<div
								class="progress-fill"
								:class="{ exceeded: isLangExceeded(lang) }"
								:style="{ width: getProgressWidth(lang) + '%' }">
							</div>
						</div>
						<span class="page-count">
							{{ licenseStats.pageCounts[lang] || 0 }} {{ t('intravox', 'pages') }}
						</span>
					</div>
				</div>

				<div class="total-pages">
					<strong>{{ t('intravox', 'Total') }}:</strong>
					{{ licenseStats.totalPages }} {{ t('intravox', 'pages') }}
				</div>
			</div>

			<NcNoteCard v-if="licenseStats && licenseStats.hasLicense && licenseStats.licenseValid" type="success">
				{{ t('intravox', 'Subscription active — thank you for supporting IntraVox!') }}
			</NcNoteCard>

			<NcNoteCard v-if="licenseStats && licenseStats.hasLicense && !licenseStats.licenseValid" type="warning">
				{{ t('intravox', 'Subscription key is invalid or expired.') }}
			</NcNoteCard>
		</div>

		<!-- Section 5: Your organization -->
		<div class="settings-section">
			<div class="contact-fields">
				<h2>{{ t('intravox', 'Your organization (optional)') }}</h2>
				<p class="field-desc">{{ t('intravox', 'These details help us reach you if needed. They are never shared.') }}</p>

				<div class="field-row">
					<label for="organization-name">{{ t('intravox', 'Organization name') }}</label>
					<input id="organization-name"
						v-model="organizationName"
						type="text"
						:placeholder="t('intravox', 'e.g. Acme Corporation')"
						class="contact-input">
				</div>

				<div class="field-row">
					<label for="contact-email">{{ t('intravox', 'Contact email') }}</label>
					<input id="contact-email"
						v-model="contactEmail"
						type="email"
						:placeholder="t('intravox', 'e.g. admin@example.com')"
						class="contact-input">
				</div>

				<NcButton type="primary"
					:disabled="savingContact"
					@click="saveContactInfo">
					{{ savingContact ? t('intravox', 'Saving...') : t('intravox', 'Save') }}
				</NcButton>
			</div>
		</div>

		<!-- Section 6: Subscription key -->
		<div class="settings-section">
			<h2>{{ t('intravox', 'Subscription key') }}</h2>

			<div class="field-row">
				<input id="license-key"
					v-model="licenseKey"
					type="text"
					:placeholder="t('intravox', 'e.g. IVOX-XXXX-XXXX-XXXX-XXXX')"
					class="contact-input"
					@input="_userEditedLicenseKey = true">
			</div>
			<div class="license-key-actions">
				<NcButton type="primary"
					:disabled="savingLicense"
					@click="saveLicenseKey">
					{{ savingLicense ? t('intravox', 'Saving...') : t('intravox', 'Save & activate') }}
				</NcButton>
				<NcButton v-if="licenseStats && licenseStats.hasLicense"
					type="tertiary"
					:disabled="savingLicense"
					@click="removeLicenseKey">
					{{ t('intravox', 'Remove subscription key') }}
				</NcButton>
			</div>
		</div>

		<!-- Section 7: Contact -->
		<div class="settings-section">
			<div class="contact-info-block">
				<p>
					{{ t('intravox', 'Learn more about IntraVox') }}:
					<a href="https://voxcloud.nl" target="_blank" rel="noopener noreferrer">voxcloud.nl</a>
				</p>
				<p>
					{{ t('intravox', 'Questions or feedback?') }}
					<a href="mailto:info@voxcloud.nl">info@voxcloud.nl</a>
				</p>
			</div>
		</div>

		<!-- Section 8: Anonymous Usage Statistics -->
		<div class="settings-section">
			<h2>{{ t('intravox', 'Anonymous Usage Statistics') }}</h2>
			<p class="settings-section-desc">
				{{ t('intravox', 'Help improve IntraVox by sharing anonymous usage statistics.') }}
			</p>

			<div class="telemetry-settings">
				<div class="engagement-option">
					<NcCheckboxRadioSwitch
						type="switch"
						:model-value="telemetryEnabled"
						@update:model-value="toggleTelemetry($event)">
						<div class="option-info">
							<span class="option-label">{{ t('intravox', 'Share anonymous usage statistics') }}</span>
							<span class="option-desc">{{ t('intravox', 'We collect: page counts per language, user counts, version info (IntraVox, Nextcloud, PHP), and basic server configuration. No personal data or page content is shared.') }}</span>
						</div>
					</NcCheckboxRadioSwitch>
				</div>

				<div v-if="telemetryEnabled" class="telemetry-info">
					<NcNoteCard type="success">
						<p>{{ t('intravox', 'Thank you for helping improve IntraVox!') }}</p>
						<p v-if="telemetryLastReport">
							{{ t('intravox', 'Last report sent') }}: {{ formatDate(telemetryLastReport) }}
						</p>
						<NcButton type="secondary"
							:disabled="sendingTelemetry"
							@click="sendTelemetryNow">
							{{ sendingTelemetry ? t('intravox', 'Sending...') : t('intravox', 'Send report now') }}
						</NcButton>
					</NcNoteCard>
				</div>

				<div class="telemetry-details">
					<h4>{{ t('intravox', 'What we collect') }}:</h4>
					<ul>
						<li>{{ t('intravox', 'Page counts per language (e.g., EN: 45, NL: 32)') }}</li>
						<li>{{ t('intravox', 'Total user count and active users') }}</li>
						<li>{{ t('intravox', 'IntraVox, Nextcloud, and PHP version numbers') }}</li>
						<li>{{ t('intravox', 'A unique hash of your instance URL (privacy-friendly identifier)') }}</li>
						<li>{{ t('intravox', 'Basic server configuration (database, OS, web server, language, timezone, country)') }}</li>
					</ul>
					<h4>{{ t('intravox', 'What we never collect') }}:</h4>
					<ul class="not-collected">
						<li>{{ t('intravox', 'Page content or titles') }}</li>
						<li>{{ t('intravox', 'User names or email addresses') }}</li>
						<li>{{ t('intravox', 'Your actual server URL') }}</li>
						<li>{{ t('intravox', 'Any personal or sensitive data') }}</li>
					</ul>
				</div>
			</div>
		</div>

		<div v-if="message" :class="['message', messageType]">
			{{ message }}
		</div>
	</div>
</template>

<script>
import { NcButton, NcCheckboxRadioSwitch, NcNoteCard } from '@nextcloud/vue'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { showError } from '@nextcloud/dialogs'

export default {
	name: 'SupportSettings',

	components: {
		NcButton,
		NcCheckboxRadioSwitch,
		NcNoteCard,
	},

	props: {
		initialTelemetryEnabled: {
			type: Boolean,
			default: false,
		},
		initialTelemetryLastReport: {
			type: [Number, null],
			default: null,
		},
	},

	data() {
		return {
			licenseStats: null,
			licenseKey: '',
			savingLicense: false,
			_userEditedLicenseKey: false,
			organizationName: '',
			contactEmail: '',
			savingContact: false,
			telemetryEnabled: this.initialTelemetryEnabled,
			telemetryLastReport: this.initialTelemetryLastReport,
			sendingTelemetry: false,
			message: '',
			messageType: 'success',
		}
	},

	computed: {
		pricingUrl() {
			const lang = (window.document?.documentElement?.lang || '').split('-')[0]
			return lang === 'nl' ? 'https://voxcloud.nl/pricing/#intravox' : 'https://voxcloud.nl/en/pricing/#intravox'
		},
	},

	mounted() {
		this.loadSettings()
		this.loadLicenseStats()
	},

	methods: {
		async loadSettings() {
			try {
				const res = await axios.get(generateUrl('/apps/intravox/api/settings'))
				if (res.data.success) {
					this.organizationName = res.data.settings.organization_name || ''
					this.contactEmail = res.data.settings.contact_email || ''
				}
			} catch (error) {
				console.error('Failed to load settings:', error)
			}
		},

		async loadLicenseStats() {
			try {
				const response = await axios.get(generateUrl('/apps/intravox/api/license/stats'))
				if (response.data.success) {
					this.licenseStats = response.data
					if (this.licenseStats.hasLicense && !this._userEditedLicenseKey) {
						this.licenseKey = this.licenseStats.licenseKeyMasked || ''
					}
				}
			} catch (error) {
				console.error('Failed to load license stats:', error)
			}
		},

		async saveLicenseKey() {
			const key = this.licenseKey.trim()
			if (!key) {
				this.showMessage(this.t('intravox', 'Please enter a subscription key'), 'error')
				return
			}
			this.savingLicense = true
			try {
				const saveRes = await axios.post(generateUrl('/apps/intravox/api/settings/license'), {
					licenseKey: key,
				})
				if (!saveRes.data.success) {
					this.showMessage(this.t('intravox', 'Failed to save subscription key'), 'error')
					return
				}

				const valRes = await axios.post(generateUrl('/apps/intravox/api/license/validate'))
				if (valRes.data.success && valRes.data.validation?.valid) {
					await axios.post(generateUrl('/apps/intravox/api/license/update-usage'))
					this.showMessage(this.t('intravox', 'Subscription activated!'), 'success')
				} else {
					this.showMessage(this.t('intravox', 'Subscription key saved but validation failed: {reason}', { reason: valRes.data.validation?.reason || 'unknown' }), 'error')
				}

				await this.loadLicenseStats()
				this.$emit('license-changed')
			} catch (error) {
				console.error('Failed to save/validate license key:', error)
				this.showMessage(this.t('intravox', 'Failed to save subscription key'), 'error')
			} finally {
				this.savingLicense = false
			}
		},

		async removeLicenseKey() {
			this.savingLicense = true
			try {
				await axios.post(generateUrl('/apps/intravox/api/settings/license'), {
					licenseKey: '',
				})
				this.licenseKey = ''
				this._userEditedLicenseKey = false
				await this.loadLicenseStats()
				this.$emit('license-changed')
				this.showMessage(this.t('intravox', 'Subscription key removed.'), 'success')
			} catch (error) {
				this.showMessage(this.t('intravox', 'Failed to remove subscription key'), 'error')
			} finally {
				this.savingLicense = false
			}
		},

		async saveContactInfo() {
			this.savingContact = true
			try {
				await axios.post(generateUrl('/apps/intravox/api/settings'), {
					organization_name: this.organizationName,
					contact_email: this.contactEmail,
				})
				this.showMessage(this.t('intravox', 'Contact information saved.'), 'success')
			} catch (error) {
				console.error('Failed to save contact info:', error)
				this.showMessage(this.t('intravox', 'Failed to save contact information'), 'error')
			} finally {
				this.savingContact = false
			}
		},

		async sendTelemetryNow() {
			this.sendingTelemetry = true
			try {
				await axios.post(generateUrl('/apps/intravox/api/license/telemetry'))
				this.telemetryLastReport = Math.floor(Date.now() / 1000)
			} catch (error) {
				console.error('Failed to send telemetry:', error)
			} finally {
				this.sendingTelemetry = false
			}
		},

		async toggleTelemetry(enabled) {
			try {
				await axios.post(
					generateUrl('/apps/intravox/api/settings/telemetry'),
					{ enabled },
				)
				this.telemetryEnabled = enabled
			} catch (error) {
				console.error('Failed to toggle telemetry:', error)
				showError(this.t('intravox', 'Failed to update telemetry settings'))
			}
		},

		getLanguageFlag(lang) {
			const flags = { nl: '🇳🇱', en: '🇬🇧', de: '🇩🇪', fr: '🇫🇷' }
			return flags[lang] || '🌐'
		},

		getLanguageName(lang) {
			const names = {
				nl: this.t('intravox', 'Dutch'),
				en: this.t('intravox', 'English'),
				de: this.t('intravox', 'German'),
				fr: this.t('intravox', 'French'),
			}
			return names[lang] || lang
		},

		getProgressWidth(lang) {
			if (!this.licenseStats) return 0
			const count = this.licenseStats.pageCounts[lang] || 0
			const limit = this.licenseStats.freeLimit
			return Math.min((count / limit) * 100, 100)
		},

		isLangExceeded(lang) {
			if (!this.licenseStats) return false
			return (this.licenseStats.pageCounts[lang] || 0) >= this.licenseStats.freeLimit
		},

		showMessage(text, type) {
			this.message = text
			this.messageType = type
			setTimeout(() => {
				this.message = ''
			}, 5000)
		},

		formatDate(timestamp) {
			if (!timestamp) return this.t('intravox', 'Never')
			const date = new Date(timestamp * 1000)
			return date.toLocaleString(undefined, {
				year: 'numeric',
				month: 'long',
				day: 'numeric',
				hour: '2-digit',
				minute: '2-digit',
			})
		},

		t(app, text, vars) {
			if (typeof OC !== 'undefined' && OC.L10N) {
				return OC.L10N.translate(app, text, vars)
			}
			if (vars) {
				return Object.keys(vars).reduce((result, key) => {
					return result.replace(`{${key}}`, vars[key])
				}, text)
			}
			return text
		},
	},
}
</script>

<style scoped>
.support-settings {
	max-width: 800px;
}

/* Settings sections */
.settings-section {
	margin-bottom: 32px;
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

/* What's included list */
.includes-list {
	display: flex;
	flex-direction: column;
	gap: 12px;
	margin-bottom: 24px;
}

.includes-item {
	display: flex;
	align-items: flex-start;
	gap: 12px;
	padding: 12px 20px;
	background: var(--color-background-hover);
	border-radius: var(--border-radius-large);
}

.includes-check {
	font-size: 1.2em;
	flex-shrink: 0;
}

.includes-text {
	display: flex;
	flex-direction: column;
	gap: 2px;
}

.includes-label {
	font-weight: 600;
	color: var(--color-main-text);
}

.includes-desc {
	font-size: 13px;
	color: var(--color-text-maxcontrast);
}

/* Pricing table */
.pricing-table {
	display: flex;
	flex-direction: column;
	gap: 8px;
	margin-bottom: 16px;
}

.pricing-row {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 12px 20px;
	background: var(--color-background-hover);
	border-radius: var(--border-radius-large);
}

.pricing-row--free {
	border: 2px solid var(--color-primary-element-light);
}

.pricing-tier {
	font-weight: 500;
	color: var(--color-main-text);
}

.pricing-price {
	font-size: 16px;
	font-weight: 700;
	color: var(--color-primary);
}

.pricing-price--free {
	font-weight: 500;
	font-size: 14px;
}

.pricing-note {
	color: var(--color-text-maxcontrast);
	margin-bottom: 16px;
	font-size: 14px;
}

/* Language stats */
.language-stat-row {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 12px 20px;
	background: var(--color-background-hover);
	border-radius: var(--border-radius-large);
	margin-bottom: 8px;
}

.language-info {
	display: flex;
	align-items: center;
	gap: 8px;
	min-width: 160px;
}

.language-flag {
	font-size: 1.3em;
}

.language-name {
	font-weight: 500;
}

.language-code {
	color: var(--color-text-maxcontrast);
	font-size: 13px;
}

.progress-container {
	display: flex;
	align-items: center;
	gap: 12px;
	flex: 1;
	max-width: 400px;
}

.progress-bar {
	flex: 1;
	height: 8px;
	background: var(--color-border);
	border-radius: 4px;
	overflow: hidden;
}

.progress-fill {
	height: 100%;
	background: var(--color-primary);
	border-radius: 4px;
	transition: width 0.3s ease;
}

.progress-fill.exceeded {
	background: var(--color-warning);
}

.page-count {
	font-size: 14px;
	color: var(--color-text-maxcontrast);
	white-space: nowrap;
}

.total-pages {
	margin-top: 8px;
	font-size: 15px;
}

/* Stats overview */
.stats-overview {
	margin-bottom: 24px;
}

/* Contact info block */
.contact-info-block {
	margin-bottom: 20px;
	padding: 16px 20px;
	background: var(--color-background-hover);
	border-radius: var(--border-radius-large);
}

.contact-info-block p {
	margin: 0 0 8px 0;
	line-height: 1.5;
}

.contact-info-block p:last-child {
	margin-bottom: 0;
}

.contact-info-block a {
	color: var(--color-primary-element);
	font-weight: 500;
	text-decoration: none;
}

.contact-info-block a:hover {
	text-decoration: underline;
}

.contact-fields h2 {
	margin: 0 0 8px 0;
	font-size: 20px;
	font-weight: bold;
}

.contact-fields .field-desc {
	font-size: 13px;
	color: var(--color-text-maxcontrast);
	margin-bottom: 16px;
}

.field-row {
	display: flex;
	flex-direction: column;
	gap: 4px;
	margin-bottom: 12px;
}

.field-row label {
	font-weight: 500;
	font-size: 14px;
}

.contact-input {
	width: 100%;
	max-width: 400px;
	padding: 8px 12px;
	border: 2px solid var(--color-border-dark);
	border-radius: var(--border-radius-large);
	background: var(--color-main-background);
	color: var(--color-main-text);
	font-size: 14px;
}

.contact-input:focus {
	border-color: var(--color-primary-element);
	outline: none;
}

/* License key section */
.license-key-actions {
	display: flex;
	gap: 8px;
	margin-top: 8px;
}

/* Telemetry */
.telemetry-settings {
	margin-top: 16px;
}

.telemetry-info {
	margin-top: 16px;
}

.telemetry-details {
	margin-top: 16px;
	font-size: 14px;
	color: var(--color-text-maxcontrast);
}

.telemetry-details h4 {
	color: var(--color-main-text);
	font-size: 14px;
	margin-bottom: 8px;
}

.telemetry-details h4:not(:first-child) {
	margin-top: 16px;
}

.telemetry-details ul {
	list-style: none;
	padding: 0;
	margin: 0 0 8px 0;
}

.telemetry-details ul li {
	padding: 4px 0;
	padding-left: 20px;
	position: relative;
}

.telemetry-details ul li::before {
	content: '•';
	position: absolute;
	left: 6px;
	color: var(--color-primary);
}

.telemetry-details ul.not-collected li::before {
	content: '✕';
	color: var(--color-error);
}

.engagement-option {
	margin-bottom: 12px;
}

.option-info {
	display: flex;
	flex-direction: column;
}

.option-label {
	font-weight: 500;
}

.option-desc {
	font-size: 13px;
	color: var(--color-text-maxcontrast);
}

.message {
	margin-top: 15px;
	padding: 10px 15px;
	border-radius: var(--border-radius);
	font-size: 14px;
}

.message.success {
	background: #d4edda;
	color: #155724;
	border: 1px solid #c3e6cb;
}

.message.error {
	background: #f8d7da;
	color: #721c24;
	border: 1px solid var(--color-error, #f5c6cb);
}
</style>
