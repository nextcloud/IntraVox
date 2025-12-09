<template>
	<div class="welcome-screen">
		<div class="welcome-content">
			<div class="welcome-icon">
				<img :src="logoUrl" alt="IntraVox" class="welcome-logo">
			</div>

			<h1 class="welcome-title">{{ t('Welcome to IntraVox') }}</h1>

			<p class="welcome-description">
				{{ t('IntraVox is not yet configured. To get started, you can install demo data or create your own pages.') }}
			</p>

			<div class="welcome-steps">
				<div class="step">
					<span class="step-number">1</span>
					<div class="step-content">
						<h3>{{ t('Install Demo Data') }}</h3>
						<p>{{ t('Get started quickly with example pages in multiple languages (Dutch, English, German, French).') }}</p>
					</div>
				</div>
				<div class="step">
					<span class="step-number">2</span>
					<div class="step-content">
						<h3>{{ t('Configure Settings') }}</h3>
						<p>{{ t('Customize video domains, upload limits, and other settings for your organization.') }}</p>
					</div>
				</div>
				<div class="step">
					<span class="step-number">3</span>
					<div class="step-content">
						<h3>{{ t('Create Content') }}</h3>
						<p>{{ t('Start building your intranet with pages, navigation, and multimedia content.') }}</p>
					</div>
				</div>
			</div>

			<div class="welcome-actions">
				<NcButton
					type="primary"
					:href="adminSettingsUrl">
					<template #icon>
						<Cog :size="20" />
					</template>
					{{ t('Go to Admin Settings') }}
				</NcButton>
				<a
					href="https://github.com/nextcloud/IntraVox/blob/main/README.md"
					target="_blank"
					rel="noopener noreferrer"
					class="github-link">
					<Github :size="18" />
					{{ t('Documentation on GitHub') }}
				</a>
			</div>

			<p class="welcome-hint">
				{{ t('After installing demo data, refresh this page to see your new intranet.') }}
			</p>
		</div>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n';
import { generateUrl, imagePath } from '@nextcloud/router';
import { NcButton } from '@nextcloud/vue';
import Cog from 'vue-material-design-icons/Cog.vue';
import Github from 'vue-material-design-icons/Github.vue';

export default {
	name: 'WelcomeScreen',
	components: {
		NcButton,
		Cog,
		Github,
	},
	computed: {
		adminSettingsUrl() {
			return generateUrl('/settings/admin/intravox');
		},
		logoUrl() {
			return imagePath('intravox', 'app-dark.svg');
		},
	},
	methods: {
		t(key, vars = {}) {
			return t('intravox', key, vars);
		},
	},
};
</script>

<style scoped>
.welcome-screen {
	display: flex;
	justify-content: center;
	align-items: center;
	width: 100%;
	height: calc(100vh - 50px);
	padding: 40px 20px;
	background: var(--color-main-background);
	box-sizing: border-box;
}

.welcome-content {
	max-width: 700px;
	width: 100%;
	text-align: center;
}

.welcome-icon {
	margin-bottom: 24px;
}

.welcome-logo {
	width: 80px;
	height: 80px;
	opacity: 0.9;
}

.welcome-title {
	font-size: 28px;
	font-weight: 600;
	color: var(--color-main-text);
	margin: 0 0 16px 0;
}

.welcome-description {
	font-size: 16px;
	color: var(--color-text-maxcontrast);
	margin: 0 0 32px 0;
	line-height: 1.5;
}

.welcome-steps {
	display: flex;
	flex-direction: column;
	gap: 16px;
	margin-bottom: 32px;
	text-align: left;
}

.step {
	display: flex;
	align-items: flex-start;
	gap: 16px;
	padding: 16px;
	background: var(--color-background-hover);
	border-radius: var(--border-radius-large);
}

.step-number {
	display: flex;
	align-items: center;
	justify-content: center;
	width: 32px;
	height: 32px;
	background: var(--color-primary-element);
	color: var(--color-primary-element-text);
	border-radius: 50%;
	font-weight: 600;
	font-size: 14px;
	flex-shrink: 0;
}

.step-content h3 {
	margin: 0 0 4px 0;
	font-size: 15px;
	font-weight: 600;
	color: var(--color-main-text);
}

.step-content p {
	margin: 0;
	font-size: 14px;
	color: var(--color-text-maxcontrast);
	line-height: 1.4;
}

.welcome-actions {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 16px;
	margin-bottom: 24px;
}

.github-link {
	display: inline-flex;
	align-items: center;
	gap: 8px;
	color: var(--color-text-maxcontrast);
	text-decoration: none;
	font-size: 14px;
	transition: color 0.2s;
}

.github-link:hover {
	color: var(--color-main-text);
}

.welcome-hint {
	font-size: 13px;
	color: var(--color-text-maxcontrast);
	margin: 0;
	font-style: italic;
}

/* Mobile styles */
@media (max-width: 768px) {
	.welcome-screen {
		padding: 20px 16px;
	}

	.welcome-title {
		font-size: 22px;
	}

	.welcome-description {
		font-size: 14px;
	}

	.step {
		padding: 12px;
	}

	.step-number {
		width: 28px;
		height: 28px;
		font-size: 13px;
	}
}
</style>
