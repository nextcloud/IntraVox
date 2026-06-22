<template>
	<div class="language-fallback">
		<div class="language-fallback-content">
			<div class="language-fallback-icon">
				<Translate :size="48" />
			</div>

			<h2 class="language-fallback-title">{{ t('intravox', 'No content in your language yet') }}</h2>

			<p class="language-fallback-description">
				{{ t('intravox', 'This intranet has no pages in {language} yet.', { language: ownLanguageName }) }}
			</p>

			<!-- Which languages DO have content -->
			<p v-if="contentLanguageNames.length" class="language-fallback-available">
				{{ t('intravox', 'Content is available in:') }}
				<strong>{{ contentLanguageNames.join(', ') }}</strong>
			</p>
			<p v-else class="language-fallback-available">
				{{ t('intravox', 'No published content is available in any language yet.') }}
			</p>

			<!-- Primary action: let the user change their own language in NC settings -->
			<div class="language-fallback-actions">
				<NcButton type="primary" :href="personalSettingsUrl">
					<template #icon>
						<Cog :size="20" />
					</template>
					{{ t('intravox', 'Change your language') }}
				</NcButton>
			</div>

			<p class="language-fallback-hint">
				{{ t('intravox', 'Set your language under Personal settings to see the intranet in a language that has content.') }}
			</p>

			<div v-if="isAdmin" class="language-fallback-admin">
				<NcButton type="tertiary" :href="adminSettingsUrl">
					{{ t('intravox', 'Manage intranet languages') }}
				</NcButton>
			</div>
		</div>
	</div>
</template>

<script>
import { translate, translatePlural } from '@nextcloud/l10n';
import { generateUrl } from '@nextcloud/router';
import { NcButton } from '@nextcloud/vue';
import Cog from 'vue-material-design-icons/Cog.vue';
import Translate from 'vue-material-design-icons/Translate.vue';

export default {
	name: 'LanguageFallbackNotice',
	components: {
		NcButton,
		Cog,
		Translate,
	},
	props: {
		/** Base code of the user's own (content-less) language, e.g. "en". */
		ownLanguage: {
			type: String,
			default: 'en',
		},
		/** Base codes of languages that DO have content, e.g. ["nl", "de"]. */
		languagesWithContent: {
			type: Array,
			default: () => [],
		},
		/** Map of base code -> display name for all known languages. */
		languageNames: {
			type: Object,
			default: () => ({}),
		},
		/** Whether the current user is an IntraVox admin. */
		isAdmin: {
			type: Boolean,
			default: false,
		},
	},
	computed: {
		ownLanguageName() {
			return this.nameOf(this.ownLanguage);
		},
		/** Display names of the content languages, sorted, excluding the user's own. */
		contentLanguageNames() {
			return this.languagesWithContent
				.filter(code => code !== this.ownLanguage)
				.map(code => this.nameOf(code))
				.sort((a, b) => a.localeCompare(b));
		},
		personalSettingsUrl() {
			// NC personal info page hosts the Language picker.
			return generateUrl('/settings/user');
		},
		adminSettingsUrl() {
			// Deep-link straight to the Demo Data tab, where the language
			// management UI lives (tabs are hash-addressable in AdminSettings).
			return generateUrl('/settings/admin/intravox') + '#demo';
		},
	},
	methods: {
		t(app, text, vars) {
      return translate(app, text, vars);
    },
		nameOf(code) {
			return this.languageNames[code] || code.toUpperCase();
		},
	},
};
</script>

<style scoped>
.language-fallback {
	display: flex;
	justify-content: center;
	align-items: flex-start;
	width: 100%;
	min-height: calc(100vh - 50px);
	padding: 60px 20px 40px 20px;
	background: var(--color-main-background);
	box-sizing: border-box;
}

.language-fallback-content {
	max-width: 540px;
	width: 100%;
	text-align: center;
}

.language-fallback-icon {
	color: var(--color-primary-element);
	margin-bottom: 16px;
}

.language-fallback-title {
	font-size: 24px;
	font-weight: 600;
	color: var(--color-main-text);
	margin: 0 0 12px 0;
}

.language-fallback-description {
	font-size: 16px;
	color: var(--color-text-maxcontrast);
	margin: 0 0 16px 0;
	line-height: 1.5;
}

.language-fallback-available {
	font-size: 15px;
	color: var(--color-main-text);
	margin: 0 0 24px 0;
}

.language-fallback-actions {
	display: flex;
	justify-content: center;
	margin-bottom: 16px;
}

.language-fallback-hint {
	font-size: 13px;
	color: var(--color-text-maxcontrast);
	margin: 0 0 24px 0;
	font-style: italic;
}

.language-fallback-admin {
	display: flex;
	justify-content: center;
}

@media (max-width: 768px) {
	.language-fallback {
		padding: 30px 16px;
	}

	.language-fallback-title {
		font-size: 20px;
	}

	.language-fallback-description {
		font-size: 14px;
	}
}
</style>
