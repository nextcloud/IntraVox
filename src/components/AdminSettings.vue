<template>
	<div class="intravox-admin-settings">
		<!-- Tab Navigation - MetaVox style -->
		<div class="tab-navigation">
			<button
				:class="['tab-button', { active: activeTab === 'video' }]"
				@click="activeTab = 'video'">
				<Video :size="16" />
				{{ t('intravox', 'Video Services') }}
			</button>
			<button
				:class="['tab-button', { active: activeTab === 'engagement' }]"
				@click="activeTab = 'engagement'">
				<CommentTextOutline :size="16" />
				{{ t('intravox', 'Engagement') }}
			</button>
			<button
				:class="['tab-button', { active: activeTab === 'publication' }]"
				@click="activeTab = 'publication'">
				<CalendarClock :size="16" />
				{{ t('intravox', 'Publication') }}
			</button>
			<button
				:class="['tab-button', { active: activeTab === 'demo' }]"
				@click="activeTab = 'demo'">
				<PackageVariant :size="16" />
				{{ t('intravox', 'Demo Data') }}
			</button>
			<button
				:class="['tab-button', { active: activeTab === 'export' }]"
				@click="activeTab = 'export'">
				<Download :size="16" />
				{{ t('intravox', 'Export/Import') }}
			</button>
			<button
				:class="['tab-button', { active: activeTab === 'license' }]"
				@click="activeTab = 'license'">
				<License :size="16" />
				{{ t('intravox', 'License') }}
			</button>
		</div>

		<!-- Demo Data Tab -->
		<div v-if="activeTab === 'demo'" class="tab-content">
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
									:disabled="installing !== null || cleaningStart !== null"
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
									:disabled="installing !== null || cleaningStart !== null"
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
							<!-- Clean Start button - always available when setup is complete -->
							<NcButton
								v-if="setupComplete"
								:disabled="installing !== null || cleaningStart !== null"
								type="tertiary"
								@click="showCleanStartDialog(lang.code)">
								<template #icon>
									<span v-if="cleaningStart === lang.code" class="icon-loading-small"></span>
									<Broom v-else :size="16" />
								</template>
								{{ cleaningStart === lang.code ? t('intravox', 'Resetting...') : t('intravox', 'Clean Start') }}
							</NcButton>
						</td>
					</tr>
				</tbody>
			</table>

				<div v-if="message" :class="['message', messageType]">
				{{ message }}
			</div>
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

		<!-- Clean Start confirmation dialog -->
		<NcDialog
			v-if="cleanStartDialogVisible"
			:name="t('intravox', 'Clean Start')"
			@closing="cleanStartDialogVisible = false">
			<template #default>
				<div class="clean-start-dialog-content">
					<NcNoteCard type="error">
						<p><strong>{{ t('intravox', 'Warning: This action will permanently delete all content!') }}</strong></p>
					</NcNoteCard>
					<p class="clean-start-info">
						{{ t('intravox', 'This will delete for') }} <strong>{{ cleanStartLanguageName }}</strong>:
					</p>
					<ul class="deletion-list">
						<li>{{ t('intravox', 'All pages and subpages') }}</li>
						<li>{{ t('intravox', 'Navigation structure') }}</li>
						<li>{{ t('intravox', 'Footer content') }}</li>
						<li>{{ t('intravox', 'All comments and reactions') }}</li>
						<li>{{ t('intravox', 'All uploaded media files') }}</li>
					</ul>
					<p class="clean-start-result">
						{{ t('intravox', 'You will get a fresh empty homepage and empty navigation.') }}
					</p>
					<NcNoteCard type="warning">
						{{ t('intravox', 'This action cannot be undone!') }}
					</NcNoteCard>
				</div>
			</template>
			<template #actions>
				<NcButton type="tertiary" @click="cleanStartDialogVisible = false">
					{{ t('intravox', 'Cancel') }}
				</NcButton>
				<NcButton type="error" @click="confirmCleanStart">
					{{ t('intravox', 'Delete All & Start Fresh') }}
				</NcButton>
			</template>
		</NcDialog>

		<!-- Video Services Tab -->
		<div v-if="activeTab === 'video'" class="tab-content">
			<div class="settings-section">
				<h2>{{ t('intravox', 'Video Embed Domains') }}</h2>
				<p class="settings-section-desc">
					{{ t('intravox', 'Configure which video platforms can be embedded in pages. Toggle services on or off.') }}
				</p>

				<!-- Services Grid (2 columns) -->
				<div class="services-grid">
					<div v-for="category in categories" :key="category.id" class="service-category" :class="category.id">
						<h3 class="category-header" :class="category.id">
							<span class="category-icon">{{ category.icon }}</span>
							{{ t('intravox', category.name) }}
						</h3>

						<div class="service-list">
							<div
								v-for="service in getServicesByCategory(category.id)"
								:key="service.id"
								class="service-item"
								:class="{ enabled: isServiceEnabled(service), 'tracking-warning': category.id === 'tracking' }">
								<NcCheckboxRadioSwitch
									type="switch"
									:model-value="isServiceEnabled(service)"
									@update:model-value="toggleService(service, $event)">
									<div class="service-info">
										<span class="service-name">
											{{ service.name }}
											<a
												:href="getServiceHomepage(service)"
												target="_blank"
												rel="noopener noreferrer"
												class="service-link"
												:title="t('intravox', 'Visit website')"
												@click.stop>
												<OpenInNew :size="14" />
											</a>
										</span>
										<span class="service-domain">{{ getHostFromUrl(service.domain) }}</span>
									</div>
								</NcCheckboxRadioSwitch>
							</div>
						</div>
					</div>

					<!-- Custom Servers Category (full width) -->
					<div class="service-category custom">
						<h3 class="category-header custom">
							<span class="category-icon">🏢</span>
							{{ t('intravox', 'Custom servers') }}
						</h3>

						<div class="service-list">
							<!-- Existing custom domains with risk assessment -->
							<div
								v-for="item in customDomainsWithRisk"
								:key="item.domain"
								class="service-item custom-item enabled">
								<div class="custom-domain-info">
									<span class="service-name">
										<span class="protocol-badge">HTTPS</span>
										<a
											:href="item.domain"
											target="_blank"
											rel="noopener noreferrer"
											class="custom-domain-link"
											:title="t('intravox', 'Visit website')">
											{{ getHostFromUrl(item.domain) }}
											<OpenInNew :size="12" />
										</a>
									</span>
									<span :class="['risk-badge', item.risk.risk]">
										{{ item.risk.icon }} {{ t('intravox', item.risk.label) }}
									</span>
								</div>
								<NcButton
									type="tertiary"
									@click="removeCustomDomain(item.domain)">
									{{ t('intravox', 'Remove') }}
								</NcButton>
							</div>

							<!-- Add new domain row -->
							<div class="add-domain-row">
								<input
									v-model="newDomain"
									type="text"
									class="domain-input"
									:placeholder="t('intravox', 'video.example.org (HTTPS required)')"
									@keyup.enter="addCustomDomain"
								/>
								<NcButton type="secondary" @click="addCustomDomain" :disabled="!newDomain.trim()">
									{{ t('intravox', 'Add') }}
								</NcButton>
							</div>
							<p class="custom-server-hint">
								{{ t('intravox', 'Only secure HTTPS servers can be added. The URL will be validated before adding.') }}
							</p>
						</div>
					</div>
				</div>

				<!-- Warnings display -->
				<NcNoteCard v-if="domainWarnings.length > 0" type="warning" class="domain-warnings">
					<p><strong>{{ t('intravox', 'Privacy warnings:') }}</strong></p>
					<ul>
						<li v-for="warning in domainWarnings" :key="warning">{{ warning }}</li>
					</ul>
				</NcNoteCard>

				<div class="save-section">
					<NcButton
						type="primary"
						:disabled="savingDomains"
						@click="saveVideoDomains">
						{{ savingDomains ? t('intravox', 'Saving...') : t('intravox', 'Save video settings') }}
					</NcButton>
				</div>
			</div>
		</div>

		<!-- Export/Import Tab -->
		<div v-if="activeTab === 'export'" class="tab-content">
			<!-- Sub-tab Navigation -->
			<div class="sub-tab-navigation">
				<button
					:class="['sub-tab-button', { active: exportSubTab === 'export' }]"
					@click="exportSubTab = 'export'">
					<Download :size="16" />
					{{ t('intravox', 'Export') }}
				</button>
				<button
					:class="['sub-tab-button', { active: exportSubTab === 'confluence' }]"
					@click="exportSubTab = 'confluence'">
					<CloudDownload :size="16" />
					{{ t('intravox', 'Confluence') }}
				</button>
				<button
					:class="['sub-tab-button', { active: exportSubTab === 'import' }]"
					@click="exportSubTab = 'import'">
					<Upload :size="16" />
					{{ t('intravox', 'Import') }}
				</button>
			</div>

			<!-- Export Section -->
			<div v-if="exportSubTab === 'export'" class="settings-section">
				<h2>{{ t('intravox', 'Export Pages') }}</h2>
				<p class="settings-section-desc">
					{{ t('intravox', 'Export your IntraVox pages for backup or migration.') }}
				</p>

				<div class="export-options">
					<div class="export-row">
						<label class="export-label">{{ t('intravox', 'Language') }}</label>
						<select v-model="exportLanguage" class="export-select">
							<option :value="null" disabled>{{ t('intravox', 'Select language') }}</option>
							<option
								v-for="lang in exportLanguages"
								:key="lang.code"
								:value="lang.code">
								{{ lang.flag }} {{ lang.name }}
								<template v-if="lang.hasContent">({{ lang.pageCount }} {{ t('intravox', 'pages') }})</template>
								<template v-else>({{ t('intravox', 'empty') }})</template>
							</option>
						</select>
					</div>

					<div class="export-row">
						<label class="export-label">{{ t('intravox', 'Format') }}</label>
						<div class="export-format-options">
							<NcCheckboxRadioSwitch
								v-model="exportFormat"
								value="zip"
								type="radio"
								name="exportFormat">
								ZIP ({{ t('intravox', 'with media files') }})
							</NcCheckboxRadioSwitch>
							<NcCheckboxRadioSwitch
								v-model="exportFormat"
								value="json"
								type="radio"
								name="exportFormat">
								JSON ({{ t('intravox', 'pages only') }})
							</NcCheckboxRadioSwitch>
						</div>
					</div>

					<div class="export-row">
						<NcCheckboxRadioSwitch
							v-model="exportIncludeComments"
							type="checkbox">
							{{ t('intravox', 'Include comments and reactions') }}
						</NcCheckboxRadioSwitch>
					</div>

					<NcButton
						type="primary"
						:disabled="!exportLanguage || exporting"
						@click="downloadExport">
						<template #icon>
							<Download :size="20" />
						</template>
						{{ exporting ? t('intravox', 'Exporting...') : t('intravox', 'Download Export') }}
					</NcButton>

					<!-- Export Progress -->
					<div v-if="exporting" class="export-progress">
						<NcNoteCard type="warning">
							<p><strong>{{ t('intravox', 'Export in progress') }}</strong></p>
							<p>{{ t('intravox', 'Please keep this page open until the export completes.') }}</p>
						</NcNoteCard>
						<NcProgressBar :value="exportProgress" />
						<p class="progress-text">{{ exportStatusText }}</p>
					</div>

					<!-- Export Success Message -->
					<div v-if="exportComplete" class="export-result">
						<NcNoteCard type="success">
							{{ t('intravox', 'Export completed successfully. Download should start automatically.') }}
						</NcNoteCard>
					</div>
				</div>
			</div>

			<!-- Confluence Import Section -->
			<div v-if="exportSubTab === 'confluence'" class="settings-section confluence-import-section">
				<ConfluenceImport />
			</div>

			<!-- Import Section -->
			<div v-if="exportSubTab === 'import'" class="settings-section import-section">
				<h2>{{ t('intravox', 'Import from ZIP') }}</h2>
				<p class="settings-section-desc">
					{{ t('intravox', 'Import pages and media from an IntraVox ZIP export file.') }}
				</p>

				<div class="import-options">
					<div class="import-row">
						<label class="import-label">{{ t('intravox', 'Select ZIP file') }}</label>
						<input
							ref="importFileInput"
							type="file"
							accept=".zip"
							class="import-file-input"
							@change="onImportFileSelect" />
						<span v-if="importFileName" class="import-filename">
							{{ importFileName }}
						</span>
					</div>

					<div class="import-row">
						<NcCheckboxRadioSwitch
							v-model="importIncludeComments"
							type="checkbox">
							{{ t('intravox', 'Import comments and reactions') }}
						</NcCheckboxRadioSwitch>
					</div>

					<div class="import-row">
						<NcCheckboxRadioSwitch
							v-model="importOverwrite"
							type="checkbox">
							{{ t('intravox', 'Overwrite existing pages') }}
						</NcCheckboxRadioSwitch>
					</div>

					<NcButton
						type="primary"
						:disabled="!importFile || importing"
						@click="startImport">
						<template #icon>
							<Upload :size="20" />
						</template>
						{{ importing ? t('intravox', 'Importing...') : t('intravox', 'Start Import') }}
					</NcButton>

					<!-- Import Progress -->
					<div v-if="importing" class="import-progress">
						<NcNoteCard type="warning">
							<p><strong>{{ t('intravox', 'Import in progress') }}</strong></p>
							<p>{{ t('intravox', 'Please keep this page open until the import completes.') }}</p>
						</NcNoteCard>
						<NcProgressBar :value="importProgress" />
						<p class="progress-text">{{ importStatusText }}</p>
					</div>

					<!-- Import Result -->
					<div v-if="importResult" class="import-result">
						<NcNoteCard type="success">
							{{ t('intravox', 'Import completed successfully') }}:
							{{ importResult.stats.pagesImported }} {{ t('intravox', 'pages') }},
							{{ importResult.stats.mediaFilesImported }} {{ t('intravox', 'media files') }},
							{{ importResult.stats.commentsImported }} {{ t('intravox', 'comments') }}
						</NcNoteCard>
					</div>
				</div>
			</div>
		</div>

		<!-- Engagement Tab -->
		<div v-if="activeTab === 'engagement'" class="tab-content">
			<div class="settings-section">
				<h2>{{ t('intravox', 'Reactions & Comments') }}</h2>
				<p class="settings-section-desc">
					{{ t('intravox', 'Configure how users can interact with pages through reactions and comments.') }}
				</p>

				<!-- Page Reactions -->
				<div class="engagement-group">
					<h3 class="engagement-group-header">
						<span class="engagement-icon">👍</span>
						{{ t('intravox', 'Page Reactions') }}
					</h3>
					<div class="engagement-option">
						<NcCheckboxRadioSwitch
							type="switch"
							:model-value="engagementSettings.allowPageReactions"
							@update:model-value="engagementSettings.allowPageReactions = $event">
							<div class="option-info">
								<span class="option-label">{{ t('intravox', 'Allow reactions on pages') }}</span>
								<span class="option-desc">{{ t('intravox', 'Users can add emoji reactions to pages') }}</span>
							</div>
						</NcCheckboxRadioSwitch>
					</div>
				</div>

				<!-- Comments -->
				<div class="engagement-group">
					<h3 class="engagement-group-header">
						<span class="engagement-icon">💬</span>
						{{ t('intravox', 'Comments') }}
					</h3>
					<div class="engagement-option">
						<NcCheckboxRadioSwitch
							type="switch"
							:model-value="engagementSettings.allowComments"
							@update:model-value="engagementSettings.allowComments = $event">
							<div class="option-info">
								<span class="option-label">{{ t('intravox', 'Allow comments on pages') }}</span>
								<span class="option-desc">{{ t('intravox', 'Users can post comments on pages') }}</span>
							</div>
						</NcCheckboxRadioSwitch>
					</div>
					<div v-if="engagementSettings.allowComments" class="engagement-option sub-option">
						<NcCheckboxRadioSwitch
							type="switch"
							:model-value="engagementSettings.allowCommentReactions"
							@update:model-value="engagementSettings.allowCommentReactions = $event">
							<div class="option-info">
								<span class="option-label">{{ t('intravox', 'Allow reactions on comments') }}</span>
								<span class="option-desc">{{ t('intravox', 'Users can add emoji reactions to comments') }}</span>
							</div>
						</NcCheckboxRadioSwitch>
					</div>
				</div>

				<div class="save-section">
					<NcButton
						type="primary"
						:disabled="savingEngagement"
						@click="saveEngagementSettings">
						{{ savingEngagement ? t('intravox', 'Saving...') : t('intravox', 'Save engagement settings') }}
					</NcButton>
				</div>
			</div>
		</div>

		<!-- Publication Tab -->
		<div v-if="activeTab === 'publication'" class="tab-content">
			<div class="settings-section">
				<h2>{{ t('intravox', 'Publication Date Fields') }}</h2>
				<p class="settings-section-desc">
					{{ t('intravox', 'Configure MetaVox fields used for publication date filtering in the News widget.') }}
				</p>

				<NcNoteCard v-if="!metavoxAvailable" type="warning">
					{{ t('intravox', 'MetaVox is not available. Install and enable MetaVox to use publication date filtering.') }}
				</NcNoteCard>

				<div v-else class="publication-settings">
					<p class="settings-info">
						{{ t('intravox', 'Select the MetaVox date fields used for publication filtering. The News widget will use these fields to filter pages based on their publication period.') }}
					</p>

					<!-- Loading state -->
					<div v-if="loadingMetavoxFields" class="loading-fields">
						<span class="icon-loading-small"></span>
						{{ t('intravox', 'Loading MetaVox fields...') }}
					</div>

					<!-- No date fields available -->
					<NcNoteCard v-else-if="dateFields.length === 0" type="warning">
						{{ t('intravox', 'No date fields found in MetaVox. Create date fields in MetaVox first and assign them to the IntraVox groupfolder.') }}
					</NcNoteCard>

					<template v-else>
						<div class="setting-row">
							<label class="setting-label" for="publish-date-field">
								{{ t('intravox', 'Publish date field') }}
							</label>
							<select
								id="publish-date-field"
								v-model="publicationSettings.publishDateField"
								class="setting-select">
								<option value="">{{ t('intravox', '— Not configured —') }}</option>
								<option
									v-for="field in dateFields"
									:key="field.field_name"
									:value="field.field_name">
									{{ field.field_label }}
									<template v-if="field.field_label !== field.field_name">
										({{ field.field_name }})
									</template>
								</option>
							</select>
							<p class="setting-hint">
								{{ t('intravox', 'Pages will only be shown on or after this date.') }}
							</p>
						</div>

						<div class="setting-row">
							<label class="setting-label" for="expiration-date-field">
								{{ t('intravox', 'Expiration date field') }}
							</label>
							<select
								id="expiration-date-field"
								v-model="publicationSettings.expirationDateField"
								class="setting-select">
								<option value="">{{ t('intravox', '— Not configured —') }}</option>
								<option
									v-for="field in dateFields"
									:key="field.field_name"
									:value="field.field_name">
									{{ field.field_label }}
									<template v-if="field.field_label !== field.field_name">
										({{ field.field_name }})
									</template>
								</option>
							</select>
							<p class="setting-hint">
								{{ t('intravox', 'Pages will be hidden after this date.') }}
							</p>
						</div>

						<div class="save-section">
							<NcButton
								type="primary"
								:disabled="savingPublication"
								@click="savePublicationSettings">
								{{ savingPublication ? t('intravox', 'Saving...') : t('intravox', 'Save publication settings') }}
							</NcButton>
						</div>
					</template>
				</div>
			</div>
		</div>

		<!-- License Tab -->
		<div v-if="activeTab === 'license'" class="tab-content">
			<!-- License Configuration Section -->
			<div class="settings-section">
				<h2>{{ t('intravox', 'License Configuration') }}</h2>
				<p class="settings-section-desc">
					{{ t('intravox', 'Configure your license server connection and license key.') }}
				</p>

				<div class="license-config">
					<div class="setting-row">
						<label class="setting-label" for="license-server-url">
							{{ t('intravox', 'License Server URL') }}
						</label>
						<input
							id="license-server-url"
							v-model="licenseServerUrl"
							type="url"
							class="setting-input"
							placeholder="https://licenses.voxcloud.nl" />
						<p class="setting-hint">
							{{ t('intravox', 'The URL of the license server. Leave empty to use the default.') }}
						</p>
					</div>

					<div class="setting-row">
						<label class="setting-label" for="license-key">
							{{ t('intravox', 'License Key') }}
						</label>
						<input
							id="license-key"
							v-model="licenseKey"
							type="text"
							class="setting-input license-key-input"
							:placeholder="t('intravox', 'Enter your license key')" />
						<p class="setting-hint">
							{{ t('intravox', 'Your license key from VoxCloud. Leave empty for the free version.') }}
						</p>
					</div>

					<!-- License Status -->
					<div v-if="licenseKey" class="license-status">
						<div v-if="validatingLicense" class="status-checking">
							<span class="icon-loading-small"></span>
							{{ t('intravox', 'Validating license...') }}
						</div>
						<div v-else-if="licenseValidation" class="status-result">
							<NcNoteCard :type="licenseValidation.valid ? 'success' : 'error'">
								<template v-if="licenseValidation.valid">
									<p><strong>{{ t('intravox', 'License valid') }}</strong></p>
									<p v-if="licenseValidation.license">
										{{ t('intravox', 'Type') }}: {{ licenseValidation.license.licenseType || 'Standard' }}
										<span v-if="licenseValidation.license.maxPages">
											| {{ t('intravox', 'Max pages') }}: {{ licenseValidation.license.maxPages }}
										</span>
									</p>
								</template>
								<template v-else>
									<p><strong>{{ t('intravox', 'License invalid') }}</strong></p>
									<p>{{ licenseValidation.reason }}</p>
								</template>
							</NcNoteCard>
						</div>
					</div>

					<div class="save-section license-save">
						<NcButton
							type="primary"
							:disabled="savingLicense"
							@click="saveLicenseSettings">
							{{ savingLicense ? t('intravox', 'Saving...') : t('intravox', 'Save license settings') }}
						</NcButton>
						<NcButton
							v-if="licenseKey"
							type="secondary"
							:disabled="validatingLicense"
							@click="validateLicenseNow">
							{{ validatingLicense ? t('intravox', 'Validating...') : t('intravox', 'Validate now') }}
						</NcButton>
					</div>
				</div>
			</div>

			<!-- Page Statistics Section -->
			<div class="settings-section">
				<h2>{{ t('intravox', 'Page Statistics') }}</h2>
				<p class="settings-section-desc">
					{{ t('intravox', 'Overview of pages per language in your IntraVox installation.') }}
				</p>

				<div class="license-stats">
					<!-- Language stats with progress bars -->
					<div class="language-stats">
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
										:class="{ exceeded: (licenseStats.pageCounts[lang] || 0) > licenseStats.freeLimit }"
										:style="{ width: getProgressWidth(lang) + '%' }">
									</div>
								</div>
								<span class="page-count">
									{{ licenseStats.pageCounts[lang] || 0 }} {{ t('intravox', 'pages') }}
								</span>
							</div>
						</div>
					</div>

					<!-- Total pages -->
					<div class="total-pages">
						<strong>{{ t('intravox', 'Total') }}:</strong>
						{{ licenseStats.totalPages }} {{ t('intravox', 'pages') }}
					</div>

					<!-- Free tier info - show if no license, invalid license, or expired license -->
					<NcNoteCard v-if="showFreeTierInfo" type="info" class="license-info-card">
						<p>
							{{ t('intravox', 'In the free version, {limit} pages per language are included.', { limit: licenseStats.freeLimit }) }}
						</p>
						<p>
							{{ t('intravox', 'Contact us for a license if you need more pages.') }}
						</p>
					</NcNoteCard>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import { NcButton, NcDialog, NcNoteCard, NcCheckboxRadioSwitch, NcProgressBar } from '@nextcloud/vue'
import { showSuccess, showError, showWarning } from '@nextcloud/dialogs'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import PackageVariant from 'vue-material-design-icons/PackageVariant.vue'
import Video from 'vue-material-design-icons/Video.vue'
import OpenInNew from 'vue-material-design-icons/OpenInNew.vue'
import CommentTextOutline from 'vue-material-design-icons/CommentTextOutline.vue'
import CalendarClock from 'vue-material-design-icons/CalendarClock.vue'
import Download from 'vue-material-design-icons/Download.vue'
import Upload from 'vue-material-design-icons/Upload.vue'
import CloudDownload from 'vue-material-design-icons/CloudDownload.vue'
import Broom from 'vue-material-design-icons/Broom.vue'
import License from 'vue-material-design-icons/License.vue'
import ConfluenceImport from '../admin/components/ConfluenceImport.vue'

export default {
	name: 'AdminSettings',
	components: {
		NcButton,
		NcDialog,
		NcNoteCard,
		NcCheckboxRadioSwitch,
		NcProgressBar,
		PackageVariant,
		Video,
		OpenInNew,
		CommentTextOutline,
		CalendarClock,
		Download,
		Upload,
		CloudDownload,
		Broom,
		License,
		ConfluenceImport,
	},
	props: {
		initialState: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			activeTab: 'video', // Default to video tab
			exportSubTab: 'export', // Default export sub-tab
			languages: this.initialState.languages || [],
			setupComplete: this.initialState.setupComplete !== false,
			installing: null,
			message: '',
			messageType: 'success',
			reinstallDialogVisible: false,
			reinstallLanguageCode: null,
			// Clean start state
			cleaningStart: null,
			cleanStartDialogVisible: false,
			cleanStartLanguageCode: null,
			// Video domains from server
			videoDomains: Array.isArray(this.initialState.videoDomains)
				? [...this.initialState.videoDomains]
				: [],
			newDomain: '',
			savingDomains: false,
			domainWarnings: [],
			// Engagement settings
			engagementSettings: {
				allowPageReactions: this.initialState.engagementSettings?.allowPageReactions ?? true,
				allowComments: this.initialState.engagementSettings?.allowComments ?? true,
				allowCommentReactions: this.initialState.engagementSettings?.allowCommentReactions ?? true,
			},
			savingEngagement: false,
			// Publication settings
			publicationSettings: {
				publishDateField: this.initialState.publicationSettings?.publishDateField ?? '',
				expirationDateField: this.initialState.publicationSettings?.expirationDateField ?? '',
			},
			savingPublication: false,
			metavoxAvailable: this.initialState.metavoxAvailable ?? false,
			// MetaVox fields for publication settings
			metavoxFields: [],
			loadingMetavoxFields: false,
			// Export settings
			exportLanguage: null,
			exportLanguages: [],
			exportIncludeComments: true,
			exportFormat: 'zip',
			exporting: false,
			exportProgress: 0,
			exportStatusText: '',
			exportComplete: false,
			// Import settings
			importFile: null,
			importFileName: '',
			importIncludeComments: true,
			importOverwrite: false,
			importing: false,
			importProgress: 0,
			importStatusText: '',
			importResult: null,
			// Timeout IDs for cleanup
			exportTimeoutId: null,
			importTimeoutId: null,
			// Bound event handler for proper cleanup
			boundBeforeUnloadHandler: null,
			// License stats
			licenseStats: this.initialState.licenseStats || {
				pageCounts: {},
				totalPages: 0,
				freeLimit: 50,
				supportedLanguages: ['nl', 'en', 'de', 'fr'],
			},
			// License configuration
			licenseServerUrl: this.initialState.licenseServerUrl || '',
			licenseKey: this.initialState.licenseKey || '',
			savingLicense: false,
			validatingLicense: false,
			licenseValidation: null,
			// Known video services with metadata
			knownServices: [
				// Privacy-friendly (no/minimal tracking)
				{ id: 'youtube-privacy', name: 'YouTube (privacy mode)', domain: 'https://www.youtube-nocookie.com', category: 'privacy', description: 'No tracking cookies' },
				{ id: 'vimeo', name: 'Vimeo', domain: 'https://player.vimeo.com', category: 'privacy', description: 'Professional video hosting' },
				{ id: 'surfnet', name: 'SURF Video', domain: 'https://video.edu.nl', category: 'privacy', description: 'Dutch education network' },
				{ id: 'ted', name: 'TED Talks', domain: 'https://embed.ted.com', category: 'privacy', description: 'Ideas worth spreading' },
				{ id: 'framatube', name: 'FramaTube', domain: 'https://framatube.org', category: 'privacy', description: 'PeerTube by Framasoft' },
				{ id: 'tilvids', name: 'TilVids', domain: 'https://tilvids.com', category: 'privacy', description: 'Educational PeerTube' },
				{ id: 'diode', name: 'Diode Zone', domain: 'https://diode.zone', category: 'privacy', description: 'PeerTube instance' },
				{ id: 'blender', name: 'Blender Video', domain: 'https://video.blender.org', category: 'privacy', description: 'Blender Foundation' },
				// Tracking concerns (known trackers)
				{ id: 'youtube', name: 'YouTube (standard)', domain: 'https://www.youtube.com', category: 'tracking', description: 'Contains Google tracking' },
				{ id: 'dailymotion', name: 'Dailymotion', domain: 'https://www.dailymotion.com', category: 'tracking', description: 'Contains tracking' },
			],
			// Categories for display (2 columns: privacy + tracking)
			categories: [
				{ id: 'privacy', name: 'Privacy-friendly', icon: '✅', description: 'No or minimal tracking' },
				{ id: 'tracking', name: 'Tracking concerns', icon: '⚠️', description: 'Platforms with known trackers' },
			],
		}
	},
	computed: {
		reinstallLanguageName() {
			if (!this.reinstallLanguageCode) return ''
			const langData = this.languages.find(l => l.code === this.reinstallLanguageCode)
			return langData?.name || this.reinstallLanguageCode
		},
		cleanStartLanguageName() {
			if (!this.cleanStartLanguageCode) return ''
			const langData = this.languages.find(l => l.code === this.cleanStartLanguageCode)
			return langData?.name || this.cleanStartLanguageCode
		},
		// Get custom domains (domains not in knownServices)
		customDomains() {
			const knownDomains = this.knownServices.map(s => s.domain)
			return this.videoDomains.filter(d => !knownDomains.includes(d))
		},
		// Computed domain risk assessments (prevents re-calculation on every render)
		customDomainsWithRisk() {
			return this.customDomains.map(domain => ({
				domain,
				risk: this.assessDomainRisk(domain)
			}))
		},
		// Filter MetaVox fields to only show date type fields
		dateFields() {
			return this.metavoxFields.filter(field => field.field_type === 'date')
		},
		// Show free tier info if no license, invalid license, or expired license
		showFreeTierInfo() {
			// No license key entered
			if (!this.licenseKey) return true
			// License validation failed (invalid or expired)
			if (this.licenseValidation && !this.licenseValidation.valid) return true
			return false
		},
	},
	watch: {
		// Clear success messages when switching tabs
		activeTab(newTab) {
			this.exportComplete = false
			this.importResult = null
			// Load MetaVox fields when switching to publication tab
			if (newTab === 'publication' && this.metavoxAvailable && this.metavoxFields.length === 0) {
				this.loadMetavoxFields()
			}
		},
		// Clear success messages when switching export sub-tabs
		exportSubTab() {
			this.exportComplete = false
			this.importResult = null
		},
	},
	methods: {
		// Service toggle methods
		getServicesByCategory(categoryId) {
			return this.knownServices.filter(s => s.category === categoryId)
		},
		isServiceEnabled(service) {
			return this.videoDomains.includes(service.domain)
		},
		toggleService(service, enabled) {
			if (enabled) {
				if (!this.videoDomains.includes(service.domain)) {
					this.videoDomains.push(service.domain)
				}
				if (service.category === 'tracking') {
					showWarning(this.t('intravox', '{service} has tracking concerns. Consider privacy-friendly alternatives.', { service: service.name }))
				}
			} else {
				const index = this.videoDomains.indexOf(service.domain)
				if (index > -1) {
					this.videoDomains.splice(index, 1)
				}
			}
		},
		// Custom domain methods
		addCustomDomain() {
			if (!this.newDomain) return

			let domain = this.newDomain.trim()

			// Remove trailing slashes and clean up
			domain = domain.replace(/\/+$/, '')

			// Check if it starts with http:// (insecure)
			if (domain.startsWith('http://')) {
				showError(this.t('intravox', 'Only HTTPS URLs are allowed for security reasons. Please use https://'))
				return
			}

			// Add https:// if no protocol specified
			if (!domain.startsWith('https://')) {
				domain = 'https://' + domain
			}

			// Validate URL format
			try {
				const url = new URL(domain)

				// Must be https
				if (url.protocol !== 'https:') {
					showError(this.t('intravox', 'Only HTTPS URLs are allowed for security reasons'))
					return
				}

				// Must have a valid hostname
				if (!url.hostname) {
					showError(this.t('intravox', 'Please enter a valid domain name (e.g., video.example.org)'))
					return
				}

				// Validate domain structure: must have at least one dot and valid TLD (2+ chars)
				const parts = url.hostname.split('.')
				if (parts.length < 2) {
					showError(this.t('intravox', 'Please enter a valid domain name (e.g., video.example.org)'))
					return
				}

				// TLD must be at least 2 characters
				const tld = parts[parts.length - 1]
				if (tld.length < 2) {
					showError(this.t('intravox', 'Invalid domain: TLD must be at least 2 characters (e.g., .com, .org, .nl)'))
					return
				}

				// Domain name part must be at least 1 character
				const domainName = parts[parts.length - 2]
				if (domainName.length < 1) {
					showError(this.t('intravox', 'Please enter a valid domain name (e.g., video.example.org)'))
					return
				}

				// Check for valid characters in hostname (letters, numbers, hyphens, dots)
				if (!/^[a-zA-Z0-9.-]+$/.test(url.hostname)) {
					showError(this.t('intravox', 'Domain contains invalid characters. Only letters, numbers, dots and hyphens are allowed.'))
					return
				}

				// Use the cleaned URL (hostname only with https)
				domain = `https://${url.hostname}`

			} catch (e) {
				showError(this.t('intravox', 'Invalid URL format. Please enter a valid URL (e.g., https://video.example.org)'))
				return
			}

			// Check for duplicates
			if (this.videoDomains.includes(domain)) {
				showWarning(this.t('intravox', 'This domain is already in the list'))
				this.newDomain = ''
				return
			}

			// Check if it matches a known service
			const knownService = this.knownServices.find(s => s.domain === domain)
			if (knownService) {
				showWarning(this.t('intravox', '{service} is already available in the predefined services above', { service: knownService.name }))
				this.newDomain = ''
				return
			}

			this.videoDomains.push(domain)
			this.newDomain = ''
			showSuccess(this.t('intravox', 'Domain added. Don\'t forget to save your settings.'))
		},
		removeCustomDomain(domain) {
			const index = this.videoDomains.indexOf(domain)
			if (index > -1) {
				this.videoDomains.splice(index, 1)
			}
		},
		getHostFromUrl(url) {
			try {
				return new URL(url).hostname
			} catch {
				return url
			}
		},
		getServiceHomepage(service) {
			// Map embed domains to their homepage URLs
			const homepageMap = {
				'www.youtube-nocookie.com': 'https://www.youtube.com',
				'player.vimeo.com': 'https://vimeo.com',
				'video.edu.nl': 'https://video.edu.nl',
				'embed.ted.com': 'https://www.ted.com',
				'framatube.org': 'https://framatube.org',
				'tilvids.com': 'https://tilvids.com',
				'diode.zone': 'https://diode.zone',
				'video.blender.org': 'https://video.blender.org',
				'www.youtube.com': 'https://www.youtube.com',
				'www.dailymotion.com': 'https://www.dailymotion.com',
			}

			const host = this.getHostFromUrl(service.domain)
			return homepageMap[host] || service.domain
		},
		// Risk assessment for custom domains
		assessDomainRisk(domain) {
			try {
				const url = new URL(domain)
				const host = url.hostname.toLowerCase()

				// 1. HTTPS check - critical
				if (url.protocol !== 'https:') {
					return { risk: 'danger', label: 'No HTTPS', icon: '🔴' }
				}

				// 2. Known trackers - warning
				if (/google\.|youtube\.|facebook\.|meta\.|doubleclick/i.test(host)) {
					return { risk: 'warning', label: 'Tracking concerns', icon: '⚠️' }
				}

				// 3. PeerTube patterns - good
				if (/peertube|tube\.|video\./i.test(host)) {
					return { risk: 'good', label: 'Video platform', icon: '✅' }
				}

				// 4. Educational - good
				if (/\.edu$|\.ac\.|university|college/i.test(host)) {
					return { risk: 'good', label: 'Educational', icon: '🎓' }
				}

				// 5. Government - good
				if (/\.gov$|\.overheid\.|\.govt\./i.test(host)) {
					return { risk: 'good', label: 'Government', icon: '🏛️' }
				}

				// 6. Unknown - neutral
				return { risk: 'unknown', label: 'Unknown platform', icon: '❓' }
			} catch {
				return { risk: 'danger', label: 'Invalid URL', icon: '🔴' }
			}
		},
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
		showCleanStartDialog(language) {
			this.cleanStartLanguageCode = language
			this.cleanStartDialogVisible = true
		},
		async confirmCleanStart() {
			this.cleanStartDialogVisible = false
			if (!this.cleanStartLanguageCode) return

			this.cleaningStart = this.cleanStartLanguageCode
			this.message = ''

			try {
				const response = await axios.post(
					generateUrl('/apps/intravox/api/demo-data/clean-start'),
					{ language: this.cleanStartLanguageCode }
				)

				if (response.data.success) {
					this.message = this.t('intravox', 'Clean start completed successfully')
					this.messageType = 'success'
					await this.refreshStatus()
				} else {
					this.message = response.data.message || this.t('intravox', 'Clean start failed')
					this.messageType = 'error'
				}
			} catch (error) {
				console.error('Clean start failed:', error)
				this.message = error.response?.data?.message || this.t('intravox', 'Clean start failed')
				this.messageType = 'error'
			} finally {
				this.cleaningStart = null
				this.cleanStartLanguageCode = null
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
		t(app, text, vars) {
			if (window.t) {
				return vars ? window.t(app, text, vars) : window.t(app, text)
			}
			return text
		},
		async saveVideoDomains() {
			this.savingDomains = true
			this.domainWarnings = []

			try {
				const response = await axios.post(
					generateUrl('/apps/intravox/api/settings/video-domains'),
					{ domains: this.videoDomains.filter(d => d) }
				)
				showSuccess(this.t('intravox', 'Video domains saved'))

				// Show warnings if any
				if (response.data.warnings && response.data.warnings.length > 0) {
					this.domainWarnings = response.data.warnings
				}
			} catch (error) {
				console.error('Failed to save video domains:', error)
				showError(this.t('intravox', 'Failed to save video domains'))
			} finally {
				this.savingDomains = false
			}
		},
		async saveEngagementSettings() {
			this.savingEngagement = true

			try {
				await axios.post(
					generateUrl('/apps/intravox/api/settings/engagement'),
					this.engagementSettings
				)
				showSuccess(this.t('intravox', 'Engagement settings saved'))
			} catch (error) {
				console.error('Failed to save engagement settings:', error)
				showError(this.t('intravox', 'Failed to save engagement settings'))
			} finally {
				this.savingEngagement = false
			}
		},
		async savePublicationSettings() {
			this.savingPublication = true

			try {
				await axios.post(
					generateUrl('/apps/intravox/api/settings/publication'),
					this.publicationSettings
				)
				showSuccess(this.t('intravox', 'Publication settings saved'))
			} catch (error) {
				console.error('Failed to save publication settings:', error)
				showError(this.t('intravox', 'Failed to save publication settings'))
			} finally {
				this.savingPublication = false
			}
		},
		async loadMetavoxFields() {
			if (!this.metavoxAvailable) return

			this.loadingMetavoxFields = true
			try {
				const response = await axios.get(generateUrl('/apps/intravox/api/metavox/fields'))
				this.metavoxFields = response.data.fields || []
			} catch (error) {
				console.error('Failed to load MetaVox fields:', error)
				this.metavoxFields = []
			} finally {
				this.loadingMetavoxFields = false
			}
		},
		async loadExportLanguages() {
			try {
				const response = await axios.get(generateUrl('/apps/intravox/api/export/languages'))
				this.exportLanguages = response.data
			} catch (error) {
				console.error('Failed to load export languages:', error)
			}
		},
		async downloadExport() {
			if (!this.exportLanguage) return

			this.exporting = true
			this.exportProgress = 0
			this.exportComplete = false
			this.exportStatusText = this.t('intravox', 'Preparing export...')

			try {
				// Choose endpoint based on format
				const endpoint = this.exportFormat === 'zip'
					? '/apps/intravox/api/export/language/{language}/zip'
					: '/apps/intravox/api/export/language/{language}'

				const url = generateUrl(endpoint, {
					language: this.exportLanguage,
				})

				// Use axios to download with progress tracking
				const response = await axios.get(url, {
					params: {
						includeComments: this.exportIncludeComments ? '1' : '0',
					},
					responseType: 'blob',
					onDownloadProgress: (progressEvent) => {
						if (progressEvent.lengthComputable) {
							const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total)
							this.exportProgress = percentCompleted
							this.exportStatusText = this.t('intravox', 'Downloading... {percent}%', { percent: percentCompleted })
						} else {
							// If total size is unknown, show indeterminate progress
							this.exportStatusText = this.t('intravox', 'Downloading... {size} MB', {
								size: (progressEvent.loaded / 1024 / 1024).toFixed(2)
							})
						}
					}
				})

				// Create blob download link
				const blob = new Blob([response.data], {
					type: response.headers['content-type'] || 'application/octet-stream'
				})
				const downloadUrl = window.URL.createObjectURL(blob)
				const link = document.createElement('a')
				link.href = downloadUrl

				// Get filename from Content-Disposition header or generate one
				const contentDisposition = response.headers['content-disposition']
				let filename = `intravox-export-${this.exportLanguage}.${this.exportFormat}`
				if (contentDisposition) {
					const filenameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/)
					if (filenameMatch && filenameMatch[1]) {
						filename = filenameMatch[1].replace(/['"]/g, '')
					}
				}
				link.download = filename

				// Trigger download
				document.body.appendChild(link)
				link.click()
				document.body.removeChild(link)
				window.URL.revokeObjectURL(downloadUrl)

				// Show success
				this.exportProgress = 100
				this.exportStatusText = this.t('intravox', 'Export completed')
				this.exportComplete = true
				showSuccess(this.t('intravox', 'Export downloaded successfully'))
			} catch (error) {
				console.error('Failed to export:', error)
				showError(this.t('intravox', 'Failed to export: ') + (error.response?.data?.error || error.message))
			} finally {
				// Reset after a short delay (with proper cleanup)
				if (this.exportTimeoutId) {
					clearTimeout(this.exportTimeoutId)
				}
				this.exportTimeoutId = setTimeout(() => {
					this.exporting = false
					this.exportProgress = 0
					this.exportTimeoutId = null
				}, 1000)
			}
		},
		onImportFileSelect(event) {
			const file = event.target.files[0]
			if (file) {
				this.importFile = file
				this.importFileName = file.name
				this.importResult = null
			}
		},
		async startImport() {
			if (!this.importFile) return

			this.importing = true
			this.importProgress = 0
			this.importResult = null
			this.importStatusText = this.t('intravox', 'Uploading ZIP file...')

			try {
				const formData = new FormData()
				formData.append('file', this.importFile)
				formData.append('importComments', this.importIncludeComments ? '1' : '0')
				formData.append('overwrite', this.importOverwrite ? '1' : '0')

				const response = await axios.post(
					generateUrl('/apps/intravox/api/import/zip'),
					formData,
					{
						headers: { 'Content-Type': 'multipart/form-data' },
						onUploadProgress: (progressEvent) => {
							// Upload progress (0-50%)
							if (progressEvent.lengthComputable) {
								const uploadPercent = Math.round((progressEvent.loaded * 50) / progressEvent.total)
								this.importProgress = uploadPercent
								this.importStatusText = this.t('intravox', 'Uploading... {percent}%', { percent: Math.round((progressEvent.loaded * 100) / progressEvent.total) })
							} else {
								this.importStatusText = this.t('intravox', 'Uploading... {size} MB', {
									size: (progressEvent.loaded / 1024 / 1024).toFixed(2)
								})
							}
						},
						onDownloadProgress: (progressEvent) => {
							// Processing on server (50-100%)
							// Since we don't know the exact processing time, show indeterminate progress
							if (this.importProgress < 50) {
								this.importProgress = 50
							}
							// Simulate processing progress
							const processingProgress = 50 + Math.min(45, Math.floor(Math.random() * 30))
							this.importProgress = processingProgress
							this.importStatusText = this.t('intravox', 'Processing import on server...')
						}
					}
				)

				// Complete
				this.importProgress = 100
				this.importStatusText = this.t('intravox', 'Import completed')
				this.importResult = response.data
				showSuccess(this.t('intravox', 'Import completed successfully'))

				// Refresh export languages to show new content
				this.loadExportLanguages()
			} catch (error) {
				console.error('Import failed:', error)
				showError(this.t('intravox', 'Import failed: ') + (error.response?.data?.error || error.message))
			} finally {
				// Reset after a short delay (with proper cleanup)
				if (this.importTimeoutId) {
					clearTimeout(this.importTimeoutId)
				}
				this.importTimeoutId = setTimeout(() => {
					this.importing = false
					this.importProgress = 0
					this.importTimeoutId = null
				}, 1000)
			}
		},
		handleBeforeUnload(event) {
			// Warn user if export or import is in progress
			if (this.exporting || this.importing) {
				event.preventDefault()
				event.returnValue = '' // Required for Chrome
				return '' // Required for some browsers
			}
		},
		// License tab helper methods
		getLanguageFlag(lang) {
			const flags = {
				nl: '🇳🇱',
				en: '🇬🇧',
				de: '🇩🇪',
				fr: '🇫🇷',
			}
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
			const count = this.licenseStats.pageCounts[lang] || 0
			const limit = this.licenseStats.freeLimit
			// Cap at 100% for display, but allow exceeded styling
			return Math.min((count / limit) * 100, 100)
		},
		async saveLicenseSettings() {
			this.savingLicense = true

			try {
				await axios.post(
					generateUrl('/apps/intravox/api/settings/license'),
					{
						licenseServerUrl: this.licenseServerUrl,
						licenseKey: this.licenseKey,
					}
				)
				showSuccess(this.t('intravox', 'License settings saved'))

				// Validate after saving if we have a key
				if (this.licenseKey) {
					await this.validateLicenseNow()
				} else {
					this.licenseValidation = null
				}
			} catch (error) {
				console.error('Failed to save license settings:', error)
				showError(this.t('intravox', 'Failed to save license settings'))
			} finally {
				this.savingLicense = false
			}
		},
		async validateLicenseNow() {
			if (!this.licenseKey) return

			this.validatingLicense = true
			this.licenseValidation = null

			try {
				const response = await axios.post(
					generateUrl('/apps/intravox/api/license/validate')
				)
				this.licenseValidation = response.data
			} catch (error) {
				console.error('Failed to validate license:', error)
				this.licenseValidation = {
					valid: false,
					reason: error.response?.data?.error || this.t('intravox', 'Failed to validate license'),
				}
			} finally {
				this.validatingLicense = false
			}
		},
	},
	mounted() {
		this.loadExportLanguages()
		// Prevent accidental navigation during export (use bound handler for proper cleanup)
		this.boundBeforeUnloadHandler = this.handleBeforeUnload.bind(this)
		window.addEventListener('beforeunload', this.boundBeforeUnloadHandler)
	},
	beforeUnmount() {
		// Clean up event listener with the same bound function reference
		if (this.boundBeforeUnloadHandler) {
			window.removeEventListener('beforeunload', this.boundBeforeUnloadHandler)
			this.boundBeforeUnloadHandler = null
		}
		// Clean up any pending timeouts
		if (this.exportTimeoutId) {
			clearTimeout(this.exportTimeoutId)
			this.exportTimeoutId = null
		}
		if (this.importTimeoutId) {
			clearTimeout(this.importTimeoutId)
			this.importTimeoutId = null
		}
	},
}
</script>

<style scoped>
.intravox-admin-settings {
	max-width: 900px;
	padding: 20px;
}

/* Tab Navigation - MetaVox style */
.tab-navigation {
	border-bottom: 1px solid var(--color-border);
	margin-bottom: 20px;
	display: flex;
	gap: 10px;
}

.tab-button {
	display: flex;
	align-items: center;
	gap: 8px;
	padding: 12px 20px;
	border: none;
	background: none;
	cursor: pointer;
	border-bottom: 2px solid transparent;
	color: var(--color-text-lighter);
	font-size: 14px;
	transition: all 0.2s ease;
}

.tab-button:hover:not(.active) {
	background: var(--color-background-hover);
}

.tab-button.active {
	border-bottom-color: var(--color-primary);
	color: var(--color-primary);
	background: var(--color-primary-element-light);
}

.tab-content {
	animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
	from { opacity: 0; transform: translateY(-4px); }
	to { opacity: 1; transform: translateY(0); }
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

/* Clean Start dialog */
.clean-start-dialog-content {
	padding: 8px 0;
}

.clean-start-info {
	margin: 12px 0 8px 0;
	color: var(--color-main-text);
}

.deletion-list {
	margin: 8px 0 16px 20px;
	padding: 0;
	color: var(--color-text-maxcontrast);
}

.deletion-list li {
	margin-bottom: 4px;
}

.clean-start-result {
	margin: 16px 0 12px 0;
	color: var(--color-main-text);
	font-weight: 500;
}

/* Action cell with multiple buttons */
.action-cell {
	display: flex;
	gap: 8px;
	align-items: center;
}

/* Video domains section */
.settings-section + .settings-section {
	margin-top: 40px;
	padding-top: 20px;
	border-top: 1px solid var(--color-border);
}

/* Services Grid - 2 columns */
.services-grid {
	display: grid;
	grid-template-columns: repeat(2, 1fr);
	gap: 20px;
	margin-bottom: 24px;
}

@media (max-width: 768px) {
	.services-grid {
		grid-template-columns: 1fr;
	}
}

/* Custom servers category spans full width */
.service-category.custom {
	grid-column: 1 / -1;
}

.service-category {
	/* No margin needed - grid handles spacing */
}

.category-header {
	display: flex;
	align-items: center;
	gap: 8px;
	margin: 0 0 12px 0;
	padding: 8px 12px;
	font-size: 14px;
	font-weight: 600;
	border-radius: var(--border-radius);
}

.category-header.privacy {
	background-color: var(--color-success-hover);
	color: var(--color-success-text);
}

.category-header.tracking {
	background-color: var(--color-warning-hover);
	color: var(--color-warning-text);
}

.category-header.custom {
	background-color: var(--color-background-dark);
	color: var(--color-main-text);
}

.category-icon {
	font-size: 16px;
}

.service-list {
	display: flex;
	flex-direction: column;
	gap: 4px;
}

.service-item {
	padding: 8px 12px;
	background: var(--color-background-hover);
	border-radius: var(--border-radius);
	border-left: 3px solid transparent;
	transition: border-color 0.15s ease;
}

.service-item.enabled {
	border-left-color: var(--color-success);
	background: var(--color-background-dark);
}

.service-item.tracking-warning.enabled {
	border-left-color: var(--color-warning);
}

/* Custom domain items */
.service-item.custom-item {
	display: flex;
	align-items: center;
	justify-content: space-between;
}

.custom-domain-info {
	display: flex;
	flex-direction: column;
	gap: 4px;
	flex: 1;
}

/* Risk badges */
.risk-badge {
	display: inline-flex;
	align-items: center;
	gap: 4px;
	font-size: 12px;
	font-weight: 500;
	padding: 2px 8px;
	border-radius: 10px;
	width: fit-content;
}

.risk-badge.good {
	background-color: var(--color-success-hover);
	color: var(--color-success-text);
}

.risk-badge.warning {
	background-color: var(--color-warning-hover);
	color: var(--color-warning-text);
}

.risk-badge.danger {
	background-color: var(--color-error-hover);
	color: var(--color-error-text);
}

.risk-badge.unknown {
	background-color: var(--color-background-dark);
	color: var(--color-text-maxcontrast);
}

.service-item :deep(.checkbox-radio-switch) {
	width: 100%;
}

.service-item :deep(.checkbox-radio-switch__content) {
	flex: 1;
}

.service-info {
	display: flex;
	flex-direction: column;
	gap: 2px;
}

.service-name {
	display: flex;
	align-items: center;
	gap: 6px;
	font-weight: 500;
	color: var(--color-main-text);
}

/* External link styling for services */
.service-link {
	color: var(--color-text-maxcontrast);
	opacity: 0.6;
	transition: opacity 0.2s, color 0.2s;
	vertical-align: middle;
	text-decoration: none;
}

.service-link:hover {
	opacity: 1;
	color: var(--color-primary);
}

.service-item:hover .service-link {
	opacity: 0.8;
}

.custom-domain-link {
	display: inline-flex;
	align-items: center;
	gap: 4px;
	color: var(--color-main-text);
	text-decoration: none;
	transition: color 0.2s;
}

.custom-domain-link:hover {
	color: var(--color-primary);
}

.custom-domain-link :deep(svg) {
	opacity: 0.5;
	transition: opacity 0.2s;
}

.custom-domain-link:hover :deep(svg) {
	opacity: 1;
}

.protocol-badge {
	display: inline-block;
	font-size: 10px;
	font-weight: 600;
	padding: 2px 6px;
	border-radius: 4px;
	background-color: var(--color-success);
	color: white;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

.service-domain {
	font-size: 12px;
	color: var(--color-text-maxcontrast);
	font-family: var(--font-monospace, monospace);
}

/* Add domain row */
.add-domain-row {
	display: flex;
	align-items: center;
	gap: 8px;
}

.domain-input {
	flex: 1;
	padding: 8px 12px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	background: var(--color-main-background);
	color: var(--color-main-text);
	font-size: 14px;
}

.domain-input:focus {
	border-color: var(--color-primary-element);
	outline: none;
}

.custom-server-hint {
	margin: 8px 0 0 0;
	font-size: 12px;
	color: var(--color-text-maxcontrast);
	font-style: italic;
}

/* Warnings */
.domain-warnings {
	margin: 16px 0;
}

.domain-warnings ul {
	margin: 8px 0 0 0;
	padding-left: 20px;
}

.domain-warnings li {
	margin-bottom: 4px;
}

/* Save section */
.save-section {
	margin-top: 24px;
	padding-top: 16px;
	border-top: 1px solid var(--color-border);
}

/* Section headers */
.settings-section h3 {
	font-size: 14px;
	font-weight: 600;
	margin: 0 0 12px 0;
}

/* Engagement tab styles */
.engagement-group {
	margin-bottom: 24px;
	padding: 16px;
	background: var(--color-background-hover);
	border-radius: var(--border-radius-large);
}

.engagement-group-header {
	display: flex;
	align-items: center;
	gap: 8px;
	margin: 0 0 16px 0;
	padding-bottom: 8px;
	border-bottom: 1px solid var(--color-border);
	font-size: 15px;
	font-weight: 600;
}

.engagement-icon {
	font-size: 18px;
}

.engagement-option {
	padding: 8px 0;
}

.engagement-option.sub-option {
	margin-left: 24px;
	padding-left: 16px;
	border-left: 2px solid var(--color-border);
}

.option-info {
	display: flex;
	flex-direction: column;
	gap: 2px;
}

.option-label {
	font-weight: 500;
	color: var(--color-main-text);
}

.option-desc {
	font-size: 12px;
	color: var(--color-text-maxcontrast);
}

/* Export tab styles */
.export-options {
	display: flex;
	flex-direction: column;
	gap: 16px;
	max-width: 400px;
}

.export-row {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.export-progress {
	margin-top: 16px;
}

.progress-text {
	margin-top: 8px;
	color: var(--color-text-lighter);
	font-size: 14px;
	text-align: center;
}

.export-result {
	margin-top: 16px;
}

.export-label {
	font-weight: 500;
	color: var(--color-main-text);
}

.export-select {
	padding: 8px 12px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	background: var(--color-main-background);
	color: var(--color-main-text);
	font-size: 14px;
	cursor: pointer;
}

.export-select:focus {
	border-color: var(--color-primary-element);
	outline: none;
}

.export-format-options {
	display: flex;
	flex-direction: column;
	gap: 4px;
}

/* Import section styles */
.import-section {
	margin-top: 32px;
	padding-top: 24px;
	border-top: 1px solid var(--color-border);
}

.import-options {
	display: flex;
	flex-direction: column;
	gap: 16px;
	max-width: 500px;
}

.import-row {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.import-label {
	font-weight: 500;
	color: var(--color-main-text);
}

.import-file-input {
	padding: 8px 12px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	background: var(--color-main-background);
	color: var(--color-main-text);
	font-size: 14px;
	cursor: pointer;
}

.import-file-input:focus {
	border-color: var(--color-primary-element);
	outline: none;
}

.import-filename {
	font-size: 13px;
	color: var(--color-text-maxcontrast);
	font-style: italic;
}

.import-progress {
	margin-top: 16px;
}

.import-progress .note-card,
.export-progress .note-card {
	margin-bottom: 16px;
}

.import-progress p,
.export-progress p {
	margin: 4px 0;
}

.import-result {
	margin-top: 16px;
}

/* Sub-tab navigation */
.sub-tab-navigation {
	display: flex;
	gap: 8px;
	margin-bottom: 30px;
	border-bottom: 2px solid var(--color-border);
	padding-bottom: 0;
}

.sub-tab-button {
	display: flex;
	align-items: center;
	gap: 8px;
	padding: 12px 24px;
	border: none;
	background: none;
	color: var(--color-text-lighter);
	cursor: pointer;
	transition: all 0.2s ease;
	border-bottom: 3px solid transparent;
	margin-bottom: -2px; /* Overlap parent border */
	font-size: 14px;
	font-weight: 500;
}

.sub-tab-button:hover {
	color: var(--color-main-text);
	background: var(--color-background-hover);
}

.sub-tab-button.active {
	color: var(--color-primary);
	border-bottom-color: var(--color-primary);
	background: var(--color-primary-element-light);
}

.sub-tab-button .material-design-icon {
	display: flex;
	align-items: center;
}

/* Publication Settings */
.publication-settings {
	margin-top: 20px;
}

.settings-info {
	color: var(--color-text-maxcontrast);
	margin-bottom: 24px;
	line-height: 1.5;
}

.setting-row {
	margin-bottom: 24px;
}

.setting-label {
	display: block;
	font-weight: 500;
	margin-bottom: 8px;
	color: var(--color-main-text);
}

.setting-input {
	width: 100%;
	max-width: 400px;
	padding: 8px 12px;
	border: 1px solid var(--color-border);
	border-radius: 4px;
	font-size: 14px;
	background: var(--color-main-background);
	color: var(--color-main-text);
}

.setting-input:focus {
	border-color: var(--color-primary-element);
	outline: none;
	box-shadow: 0 0 0 2px var(--color-primary-element-light);
}

.setting-input::placeholder {
	color: var(--color-text-maxcontrast);
}

.setting-hint {
	margin-top: 6px;
	font-size: 13px;
	color: var(--color-text-maxcontrast);
}

.setting-select {
	width: 100%;
	max-width: 400px;
	padding: 8px 12px;
	border: 1px solid var(--color-border);
	border-radius: 4px;
	font-size: 14px;
	background: var(--color-main-background);
	color: var(--color-main-text);
	cursor: pointer;
}

.setting-select:focus {
	border-color: var(--color-primary-element);
	outline: none;
	box-shadow: 0 0 0 2px var(--color-primary-element-light);
}

.loading-fields {
	display: flex;
	align-items: center;
	gap: 8px;
	padding: 16px;
	color: var(--color-text-maxcontrast);
}

/* License tab styles */
.license-stats {
	margin-top: 20px;
}

.language-stats {
	display: flex;
	flex-direction: column;
	gap: 16px;
	margin-bottom: 24px;
}

.language-stat-row {
	display: flex;
	align-items: center;
	gap: 16px;
	padding: 12px 16px;
	background: var(--color-background-hover);
	border-radius: var(--border-radius-large);
}

.language-info {
	display: flex;
	align-items: center;
	gap: 8px;
	min-width: 160px;
}

.language-flag {
	font-size: 1.5em;
}

.language-name {
	font-weight: 500;
	color: var(--color-main-text);
}

.language-code {
	color: var(--color-text-maxcontrast);
	font-size: 13px;
}

.progress-container {
	flex: 1;
	display: flex;
	align-items: center;
	gap: 12px;
}

.progress-bar {
	flex: 1;
	height: 8px;
	background: var(--color-background-dark);
	border-radius: 4px;
	overflow: hidden;
}

.progress-fill {
	height: 100%;
	background: var(--color-success);
	border-radius: 4px;
	transition: width 0.3s ease;
}

.progress-fill.exceeded {
	background: var(--color-warning);
}

.page-count {
	min-width: 100px;
	text-align: right;
	font-weight: 500;
	color: var(--color-main-text);
}

.total-pages {
	padding: 16px;
	background: var(--color-background-dark);
	border-radius: var(--border-radius-large);
	margin-bottom: 20px;
	font-size: 16px;
}

.license-info-card {
	margin-top: 16px;
}

.license-info-card p {
	margin: 4px 0;
}

.license-info-card p:last-child {
	margin-bottom: 0;
}

/* License configuration */
.license-config {
	margin-top: 20px;
	max-width: 500px;
}

.license-key-input {
	font-family: var(--font-monospace, monospace);
	letter-spacing: 0.5px;
}

.license-status {
	margin-top: 16px;
}

.status-checking {
	display: flex;
	align-items: center;
	gap: 8px;
	padding: 12px;
	background: var(--color-background-hover);
	border-radius: var(--border-radius);
	color: var(--color-text-maxcontrast);
}

.status-result {
	margin-top: 8px;
}

.license-save {
	display: flex;
	gap: 12px;
	align-items: center;
}
</style>
