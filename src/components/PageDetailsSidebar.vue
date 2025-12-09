<template>
  <NcAppSidebar
    v-if="isOpen"
    :name="pageName"
    :subtitle="pageSubtitle"
    :active.sync="activeTab"
    @update:active="onTabChange"
    @close="handleClose"
  >
    <!-- Details Tab (Page Properties/File Info) -->
    <NcAppSidebarTab
      id="details-tab"
      :name="t('Details')"
      :order="1"
    >
      <template #icon>
        <InformationOutline :size="20" />
      </template>

      <div v-if="loadingMetadata" class="loading">
        {{ t('Loading properties...') }}
      </div>

      <div v-else-if="metadataError" class="error-message">
        {{ metadataError }}
      </div>

      <div v-else-if="metadata" class="metadata-container">
        <!-- Editable Title -->
        <div class="metadata-row">
          <label class="metadata-label">{{ t('Name') }}</label>
          <div class="metadata-value metadata-editable">
            <input
              v-if="editingTitle"
              v-model="editableTitle"
              type="text"
              class="title-input"
              @keydown.enter="saveTitle"
              @keydown.esc="cancelTitleEdit"
            />
            <span v-else class="metadata-text" :class="{ 'metadata-text--editable': canEditTitle }" @click="startTitleEdit">
              {{ metadata.title || t('Untitled') }}
            </span>
            <NcButton
              v-if="editingTitle"
              type="primary"
              @click="saveTitle"
              :disabled="savingTitle"
              size="small"
            >
              {{ t('Save') }}
            </NcButton>
            <NcButton
              v-if="editingTitle"
              type="secondary"
              @click="cancelTitleEdit"
              size="small"
            >
              {{ t('Cancel') }}
            </NcButton>
          </div>
        </div>

        <!-- Modified -->
        <div class="metadata-row">
          <label class="metadata-label">{{ t('Modified') }}</label>
          <div class="metadata-value">
            {{ metadata.modifiedRelative }}
            <span class="metadata-hint">{{ metadata.modifiedFormatted }}</span>
          </div>
        </div>

        <!-- Path -->
        <div class="metadata-row">
          <label class="metadata-label">{{ t('Location') }}</label>
          <div class="metadata-value metadata-path">
            <a :href="getFolderUrl(metadata.path)" target="_blank" rel="noopener noreferrer" class="folder-link">
              {{ getDisplayPath(metadata.path) }}
            </a>
          </div>
        </div>

        <!-- Page Type -->
        <div v-if="metadata.type" class="metadata-row">
          <label class="metadata-label">{{ t('Type') }}</label>
          <div class="metadata-value">
            <span class="page-type-badge" :class="`page-type-${metadata.type}`">
              {{ getPageTypeLabel(metadata.type) }}
            </span>
            <span v-if="metadata.hasChildren" class="metadata-hint">
              {{ t('(has child pages)') }}
            </span>
          </div>
        </div>

        <!-- Unique ID (for sharing) -->
        <div v-if="metadata.uniqueId" class="metadata-row">
          <label class="metadata-label">{{ t('UniqueId') }}</label>
          <div class="metadata-value metadata-monospace">{{ metadata.uniqueId }}</div>
        </div>
      </div>
    </NcAppSidebarTab>

    <!-- MetaVox Tab (only if MetaVox app is installed) -->
    <NcAppSidebarTab
      v-if="metaVoxInstalled"
      id="metavox-tab"
      :name="t('MetaVox')"
      :order="2"
    >
      <template #icon>
        <MetaVoxIcon :size="20" />
      </template>
      <div ref="metavoxContainer" class="metavox-container">
        <div v-if="loadingMetaVox" class="loading">
          {{ t('Loading MetaVox...') }}
        </div>
        <div v-else-if="!metaVoxInstalled" class="empty-state">
          <p>{{ t('MetaVox app is not installed') }}</p>
        </div>
      </div>
    </NcAppSidebarTab>

    <!-- Versions Tab - Hidden for v1.0 -->
    <NcAppSidebarTab
      v-if="false"
      id="versions-tab"
      :name="t('Versions')"
      :order="3"
    >
      <template #icon>
        <History :size="20" />
      </template>

      <!-- Loading State -->
      <NcEmptyContent v-if="loadingVersions" :name="t('Loading versions...')">
        <template #icon>
          <NcLoadingIcon />
        </template>
      </NcEmptyContent>

      <!-- Error State -->
      <NcEmptyContent v-else-if="versionError" :name="t('Failed to load versions')">
        <template #icon>
          <AlertCircle :size="64" />
        </template>
        <template #description>
          {{ versionError }}
        </template>
      </NcEmptyContent>

      <!-- Empty State -->
      <NcEmptyContent v-else-if="versions.length === 0" :name="t('No versions yet')">
        <template #icon>
          <History :size="64" />
        </template>
        <template #description>
          {{ t('Versions are created automatically when you save changes to this page.') }}
        </template>
      </NcEmptyContent>

      <!-- Version List -->
      <div v-else class="version-list">
        <div
          v-for="version in versions"
          :key="version.timestamp"
          :class="['version-item', { 'version-item--selected': selectedVersion?.timestamp === version.timestamp }]"
          @click="selectVersion(version)"
        >
          <div class="version-info">
            <!-- Version header with date and author -->
            <div class="version-header">
              <span class="version-date">{{ version.relativeDate }}</span>
              <span v-if="version.author" class="version-author">
                {{ t('by {author}', { author: version.author }) }}
              </span>
            </div>

            <!-- Version details (absolute date and size) -->
            <div class="version-details">
              {{ version.date }} · {{ formatBytes(version.size) }}
            </div>

            <!-- Version label (editable) -->
            <div v-if="editingLabel === version.timestamp" class="version-label-edit" @click.stop>
              <NcTextField
                v-model="editableLabel"
                :label="t('Version label')"
                :placeholder="t('Add a label to this version')"
                @keydown.enter="saveVersionLabel(version.timestamp)"
                @keydown.esc="cancelLabelEdit"
              />
              <div class="label-actions">
                <NcButton
                  type="primary"
                  size="small"
                  @click="saveVersionLabel(version.timestamp)"
                >
                  {{ t('Save') }}
                </NcButton>
                <NcButton
                  type="secondary"
                  size="small"
                  @click="cancelLabelEdit"
                >
                  {{ t('Cancel') }}
                </NcButton>
              </div>
            </div>
            <div v-else-if="version.label" class="version-label">
              <Label :size="16" />
              <span>{{ version.label }}</span>
            </div>
          </div>

          <!-- Version actions -->
          <div class="version-actions" @click.stop>
            <!-- Add/Edit Label -->
            <NcButton
              type="tertiary"
              @click="startLabelEdit(version)"
              :aria-label="version.label ? t('Edit label') : t('Add label')"
            >
              <template #icon>
                <Label :size="20" />
              </template>
            </NcButton>

            <!-- Restore -->
            <NcButton
              type="secondary"
              @click="confirmRestoreVersion(version.timestamp)"
              :disabled="restoringVersion"
              :aria-label="t('Restore this version')"
            >
              <template #icon>
                <Restore :size="20" />
              </template>
              {{ t('Restore') }}
            </NcButton>
          </div>
        </div>
      </div>
    </NcAppSidebarTab>
  </NcAppSidebar>

  <!-- Restore Version Confirmation Dialog -->
  <NcDialog
    v-if="showRestoreDialog"
    :name="t('Restore Version')"
    :message="t('A backup of the current version will be created before restoring. Do you want to continue?')"
    :buttons="[
      {
        label: t('Cancel'),
        callback: cancelRestore
      },
      {
        label: t('Restore'),
        type: 'primary',
        callback: restoreVersion
      }
    ]"
    @close="cancelRestore"
  />

</template>

<script>
import { translate as t } from '@nextcloud/l10n';
import { generateUrl } from '@nextcloud/router';
import axios from '@nextcloud/axios';
import { showError, showSuccess } from '@nextcloud/dialogs';
import { NcAppSidebar, NcAppSidebarTab, NcButton, NcDialog, NcEmptyContent, NcLoadingIcon, NcTextField } from '@nextcloud/vue';
import History from 'vue-material-design-icons/History.vue';
import InformationOutline from 'vue-material-design-icons/InformationOutline.vue';
import Restore from 'vue-material-design-icons/Restore.vue';
import Label from 'vue-material-design-icons/Label.vue';
import AlertCircle from 'vue-material-design-icons/AlertCircle.vue';
import MetaVoxIcon from './icons/MetaVoxIcon.vue';

export default {
  name: 'PageDetailsSidebar',
  components: {
    NcAppSidebar,
    NcAppSidebarTab,
    NcButton,
    NcDialog,
    NcEmptyContent,
    NcLoadingIcon,
    NcTextField,
    History,
    InformationOutline,
    MetaVoxIcon,
    Restore,
    Label,
    AlertCircle
  },
  props: {
    isOpen: {
      type: Boolean,
      default: false
    },
    pageId: {
      type: String,
      required: true
    },
    pageName: {
      type: String,
      default: ''
    },
    initialTab: {
      type: String,
      default: 'details-tab'
    }
  },
  emits: ['close', 'version-restored', 'version-selected'],
  data() {
    return {
      activeTab: 'details-tab',
      versions: [],
      loadingVersions: false,
      versionError: null,
      restoringVersion: false,
      showRestoreDialog: false,
      versionToRestore: null,
      metadata: null,
      loadingMetadata: false,
      metadataError: null,
      editingTitle: false,
      editableTitle: '',
      savingTitle: false,
      metaVoxInstalled: false,
      loadingMetaVox: false,
      selectedVersion: null,
      editingLabel: null,
      editableLabel: '',
      isRestoring: false // Flag to prevent auto-select during restore
    };
  },
  computed: {
    pageSubtitle() {
      return this.t('Page details and history');
    },
    canEditTitle() {
      // Check write permission using Nextcloud's permissions
      return this.metadata?.permissions?.canWrite ?? this.metadata?.canEdit ?? false;
    }
  },
  watch: {
    isOpen(newValue) {
      if (newValue) {
        // Load versions silently - don't block sidebar if it fails
        this.loadVersions().catch(() => {
          // Silently fail - versions tab is hidden anyway
        });
        this.loadMetadata().catch(() => {
          // Metadata errors are already handled in the component
        });
        this.checkMetaVoxInstalled();
      }
    },
    pageId() {
      if (this.isOpen) {
        this.loadVersions().catch(() => {
          // Silently fail - versions tab is hidden anyway
        });
        this.loadMetadata().catch(() => {
          // Metadata errors are already handled in the component
        });
        this.dispatchMetaVoxUpdate();
      }
    },
    initialTab(newTab) {
      if (newTab && newTab !== this.activeTab) {
        this.activeTab = newTab;
      }
    },
    activeTab(newTab) {
      if (newTab === 'metavox-tab' && this.metaVoxInstalled) {
        // Use nextTick to ensure the container ref is available
        this.$nextTick(() => {
          this.dispatchMetaVoxUpdate();
        });
      }
      // Auto-select first version when versions tab is activated
      // Only if versions are already loaded and we don't have a selection
      if (newTab === 'versions-tab' && this.versions.length > 0 && !this.selectedVersion) {
        this.autoSelectFirstVersion();
      }
    },
    versions(newVersions) {
      // Auto-select first version if versions tab is active and versions just loaded
      // BUT skip if we're in the middle of a restore operation OR already have a selection
      if (this.activeTab === 'versions-tab' && newVersions.length > 0 && !this.isRestoring && !this.selectedVersion) {
        this.autoSelectFirstVersion();
      }
    },
    metaVoxInstalled(newValue) {
      // If MetaVox just became available and the tab is already active, dispatch update
      if (newValue && this.activeTab === 'metavox-tab') {
        this.$nextTick(() => {
          this.dispatchMetaVoxUpdate();
        });
      }
    }
  },
  mounted() {
    if (this.isOpen) {
      // Load versions and metadata silently - don't block sidebar if they fail
      this.loadVersions().catch(() => {
        // Silently fail - versions tab is hidden anyway
      });
      this.loadMetadata().catch(() => {
        // Metadata errors are already handled in the component
      });
      this.checkMetaVoxInstalled();
    }
  },
  methods: {
    t(key, vars = {}) {
      return t('intravox', key, vars);
    },
    handleClose() {
      this.$emit('close');
    },
    getDisplayPath(path) {
      if (!path) {
        return '';
      }

      // Extract folder path (remove filename)
      const lastSlash = path.lastIndexOf('/');
      const folderPath = lastSlash > 0 ? path.substring(0, lastSlash) : path;

      // Get the groupfolder name from metadata
      const groupfolderName = this.metadata?.mountPoint || 'IntraVox';

      // Format 1: Direct groupfolder access (admin/internal)
      // Path format: /__groupfolders/4/files/en/mission
      // Display format: IntraVox/en/mission
      if (folderPath.startsWith('/__groupfolders/')) {
        // Extract the part after /__groupfolders/X/
        const pathAfterGroupfolder = folderPath.replace(/^\/__groupfolders\/\d+\//, '');

        // Remove 'files/' from the beginning if present (internal path structure)
        const cleanPath = pathAfterGroupfolder.replace(/^files\//, '');

        // Prepend groupfolder name
        return cleanPath ? `${groupfolderName}/${cleanPath}` : groupfolderName;
      }

      // Format 2: User-mounted groupfolder view (normal users)
      // Path format: /user@email.com/files/IntraVox/en/mission
      // Display format: IntraVox/en/mission
      const userMountPattern = new RegExp(`^/[^/]+/files/${groupfolderName}/(.*)$`);
      const userMatch = folderPath.match(userMountPattern);
      if (userMatch) {
        const relativePath = userMatch[1];
        return relativePath ? `${groupfolderName}/${relativePath}` : groupfolderName;
      }

      return folderPath;
    },
    getFolderUrl(path) {
      if (!path) {
        return '#';
      }

      // Extract folder path (remove filename)
      const lastSlash = path.lastIndexOf('/');
      const folderPath = lastSlash > 0 ? path.substring(0, lastSlash) : path;

      // Use the groupfolder name from metadata if available, otherwise default to 'IntraVox'
      const groupfolderName = this.metadata?.mountPoint || 'IntraVox';

      // Get the parent folder fileId from metadata
      const fileId = this.metadata?.parentFolderId;

      // Format 1: Direct groupfolder access (admin/internal)
      // Path format: /__groupfolders/4/files/en/mission/page.json
      // Target format: /apps/files/files/{parentFolderId}?dir=/IntraVox/en/mission
      if (folderPath.startsWith('/__groupfolders/')) {
        // Extract the part after /__groupfolders/X/
        const pathAfterGroupfolder = folderPath.replace(/^\/__groupfolders\/\d+\//, '');

        // Remove 'files/' from the beginning if present (internal path structure)
        const cleanPath = pathAfterGroupfolder.replace(/^files\//, '');

        if (!fileId) {
          return '#';
        }

        // Generate Files app URL with fileId and dir parameters
        const filesPath = `/${groupfolderName}/${cleanPath}`;
        return generateUrl('/apps/files/files/{fileId}?dir={dir}', {
          fileId: fileId,
          dir: filesPath
        });
      }

      // Format 2: User-mounted groupfolder view (normal users)
      // Path format: /user@email.com/files/IntraVox/en/mission
      // Target format: /apps/files/files/{parentFolderId}?dir=/IntraVox/en/mission
      const userMountPattern = new RegExp(`^/[^/]+/files/${groupfolderName}/(.*)$`);
      const userMatch = folderPath.match(userMountPattern);
      if (userMatch) {
        const relativePath = userMatch[1];

        if (!fileId) {
          return '#';
        }

        const filesPath = relativePath ? `/${groupfolderName}/${relativePath}` : `/${groupfolderName}`;
        return generateUrl('/apps/files/files/{fileId}?dir={dir}', {
          fileId: fileId,
          dir: filesPath
        });
      }

      // Fallback for non-groupfolder paths
      return generateUrl('/apps/files/?dir={dir}', { dir: folderPath });
    },
    onTabChange(newTabId) {
      // This is a more reliable way to detect tab changes
      if (newTabId === 'metavox-tab' && this.metaVoxInstalled) {
        this.$nextTick(() => {
          this.dispatchMetaVoxUpdate();
        });
      }
    },
    async loadVersions() {
      this.loadingVersions = true;
      this.versionError = null;

      try {
        const url = generateUrl(`/apps/intravox/api/pages/${this.pageId}/versions`);
        const response = await axios.get(url);
        this.versions = response.data;
      } catch (error) {
        this.versionError = error.response?.data?.error || this.t('Failed to load version history');
      } finally {
        this.loadingVersions = false;
      }
    },
    confirmRestoreVersion(timestamp) {
      this.versionToRestore = timestamp;
      this.showRestoreDialog = true;
    },
    cancelRestore() {
      this.showRestoreDialog = false;
      this.versionToRestore = null;
    },
    async restoreVersion() {
      if (!this.versionToRestore) {
        return;
      }

      this.restoringVersion = true;
      this.isRestoring = true; // Prevent auto-select during restore
      this.showRestoreDialog = false;

      // Remember which version we're restoring
      const restoredTimestamp = this.versionToRestore;

      try {
        const url = generateUrl(`/apps/intravox/api/pages/${this.pageId}/versions/${this.versionToRestore}`);
        const response = await axios.post(url);

        // Emit event to parent to reload the page
        this.$emit('version-restored', response.data);

        // Reload versions list
        await this.loadVersions();

        // After restore, the restored version should be selected
        // It will be the second item (index 1) because a new backup was created (index 0)
        this.$nextTick(() => {
          const restoredVersion = this.versions.find(v => v.timestamp === restoredTimestamp);
          if (restoredVersion) {
            this.selectVersion(restoredVersion);
          } else if (this.versions.length > 1) {
            // Fallback: select second version (the one that was just restored)
            this.selectVersion(this.versions[1]);
          }
          // Reset flag after selection
          this.isRestoring = false;
        });

        this.versionToRestore = null;
      } catch (error) {
        console.error('Failed to restore version:', error);
        showError(this.t('Failed to restore version: {error}', {
          error: error.response?.data?.error || error.message
        }));
        this.isRestoring = false; // Reset flag on error too
      } finally {
        this.restoringVersion = false;
      }
    },
    async loadMetadata() {
      this.loadingMetadata = true;
      this.metadataError = null;

      try {
        const url = generateUrl(`/apps/intravox/api/pages/${this.pageId}/metadata`);
        const response = await axios.get(url);
        this.metadata = response.data;
      } catch (error) {
        console.error('Failed to load metadata:', error);
        this.metadataError = error.response?.data?.error || this.t('Failed to load properties');
      } finally {
        this.loadingMetadata = false;
      }
    },
    startTitleEdit() {
      // Check write permission using Nextcloud's permissions
      const canWrite = this.metadata?.permissions?.canWrite ?? this.metadata?.canEdit ?? false;
      if (!canWrite) {
        return;
      }
      this.editableTitle = this.metadata.title;
      this.editingTitle = true;
      // Focus input after Vue updates the DOM
      this.$nextTick(() => {
        const input = this.$el.querySelector('.title-input');
        if (input) {
          input.focus();
          input.select();
        }
      });
    },
    cancelTitleEdit() {
      this.editingTitle = false;
      this.editableTitle = '';
    },
    async saveTitle() {
      if (!this.editableTitle.trim()) {
        showError(this.t('Title cannot be empty'));
        return;
      }

      this.savingTitle = true;

      try {
        const url = generateUrl(`/apps/intravox/api/pages/${this.pageId}/metadata`);
        const response = await axios.put(url, {
          title: this.editableTitle.trim()
        });
        this.metadata = response.data;
        this.editingTitle = false;
        showSuccess(this.t('Title updated'));
      } catch (error) {
        console.error('Failed to update title:', error);
        showError(this.t('Failed to update title: {error}', {
          error: error.response?.data?.error || error.message
        }));
      } finally {
        this.savingTitle = false;
      }
    },
    async checkMetaVoxInstalled() {
      try {
        const url = generateUrl('/apps/intravox/api/metavox/status');
        const response = await axios.get(url);
        this.metaVoxInstalled = response.data.installed === true;
      } catch (error) {
        this.metaVoxInstalled = false;
      }
    },
    dispatchMetaVoxUpdate() {
      if (!this.metaVoxInstalled) {
        return;
      }

      if (!this.$refs.metavoxContainer) {
        return;
      }

      // Dispatch custom event for MetaVox to listen to
      const event = new CustomEvent('intravox:metavox:update', {
        detail: {
          pageId: this.pageId,
          pageName: this.pageName,
          container: this.$refs.metavoxContainer,
          metadata: this.metadata
        }
      });
      window.dispatchEvent(event);
    },
    formatBytes(bytes) {
      if (bytes === 0) return '0 Bytes';
      const k = 1024;
      const sizes = ['Bytes', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    },
    startLabelEdit(version) {
      this.editingLabel = version.timestamp;
      this.editableLabel = version.label || '';
    },
    cancelLabelEdit() {
      this.editingLabel = null;
      this.editableLabel = '';
    },
    async saveVersionLabel(timestamp) {
      if (!this.editableLabel.trim() && !this.versions.find(v => v.timestamp === timestamp)?.label) {
        // No label to save and no existing label to remove
        this.cancelLabelEdit();
        return;
      }

      try {
        const url = generateUrl(`/apps/intravox/api/pages/${this.pageId}/versions/${timestamp}/label`);
        await axios.put(url, {
          label: this.editableLabel.trim()
        });

        // Update local version
        const version = this.versions.find(v => v.timestamp === timestamp);
        if (version) {
          version.label = this.editableLabel.trim() || null;
        }

        this.cancelLabelEdit();
        showSuccess(this.t('Version label updated'));
      } catch (error) {
        showError(this.t('Failed to update version label: {error}', {
          error: error.response?.data?.error || error.message
        }));
      }
    },
    async selectVersion(version) {
      this.selectedVersion = version;

      // Emit event to parent component to show the version preview
      this.$emit('version-selected', {
        version,
        pageId: this.pageId
      });
    },
    getDepartmentMaxDepth() {
      // Department pages have max depth 2
      return 2;
    },
    getPageTypeLabel(type) {
      const labels = {
        'department': this.t('Department'),
        'container': this.t('Container'),
        'page': this.t('Page')
      };
      return labels[type] || type;
    },
    autoSelectFirstVersion() {
      if (this.versions.length > 0) {
        this.$nextTick(() => {
          this.selectVersion(this.versions[0]);
        });
      }
    },
  }
};
</script>

<style scoped>
/* NcAppSidebar handles its own styling */

.loading,
.empty-state {
  text-align: center;
  padding: 20px;
  color: var(--color-text-maxcontrast);
}

.empty-state .hint {
  font-size: 12px;
  margin-top: 8px;
}

.error-message {
  padding: 12px;
  background: var(--color-error);
  color: white;
  border-radius: var(--border-radius);
  margin-bottom: 12px;
}

.version-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.version-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  background: var(--color-background-hover);
  border-radius: var(--border-radius);
  transition: background 0.2s, border-color 0.2s;
  border: 2px solid transparent;
  cursor: pointer;
}

.version-item:hover {
  background: var(--color-background-dark);
}

.version-item--selected {
  background: var(--color-primary-element-light);
  border-color: var(--color-primary-element);
}

.version-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.version-header {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.version-date {
  font-weight: 600;
  font-size: 14px;
}

.version-author {
  font-size: 12px;
  color: var(--color-text-maxcontrast);
  font-style: italic;
}

.version-details {
  font-size: 12px;
  color: var(--color-text-maxcontrast);
}

.version-label {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 4px 8px;
  background: var(--color-background-dark);
  color: var(--color-main-text);
  border-radius: var(--border-radius);
  font-size: 12px;
  width: fit-content;
  margin-top: 4px;
}

.version-label-edit {
  margin-top: 8px;
}

.label-actions {
  display: flex;
  gap: 8px;
  margin-top: 8px;
}

.version-actions {
  display: flex;
  gap: 4px;
  align-items: center;
}

.coming-soon {
  font-style: italic;
  color: var(--color-text-maxcontrast);
}

/* Metadata container */
.metadata-container {
  padding: 12px;
}

.metadata-row {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 12px 0;
  border-bottom: 1px solid var(--color-border);
}

.metadata-row:last-child {
  border-bottom: none;
}

.metadata-label {
  font-size: 12px;
  font-weight: 600;
  color: var(--color-text-maxcontrast);
}

.metadata-value {
  font-size: 14px;
  color: var(--color-main-text);
  word-break: break-word;
}

.metadata-hint {
  display: block;
  font-size: 12px;
  color: var(--color-text-maxcontrast);
  margin-top: 2px;
}

.metadata-path {
  font-size: 12px;
  color: var(--color-text-maxcontrast);
}

.folder-link {
  color: var(--color-primary-element);
  text-decoration: none;
  border-bottom: 1px dotted var(--color-primary-element);
  transition: border-bottom 0.2s;
}

.folder-link:hover {
  border-bottom: 1px solid var(--color-primary-element);
}

.metadata-monospace {
  font-family: monospace;
  font-size: 12px;
  background: var(--color-background-hover);
  padding: 4px 8px;
  border-radius: var(--border-radius);
}

.page-type-badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: var(--border-radius-large);
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.page-type-department {
  background: #10b981;
  color: white;
}

.page-type-container {
  background: #3b82f6;
  color: white;
}

.page-type-page {
  background: var(--color-background-dark);
  color: var(--color-main-text);
}

.metadata-editable {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.metadata-text {
  padding: 4px;
  border-radius: var(--border-radius);
  transition: background 0.2s;
}

.metadata-text--editable {
  cursor: pointer;
}

.metadata-text--editable:hover {
  background: var(--color-background-hover);
}

.title-input {
  width: 100%;
  padding: 8px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  font-size: 14px;
  background: var(--color-main-background);
  color: var(--color-main-text);
}

.title-input:focus {
  outline: none;
  border-color: var(--color-primary-element);
}

.metadata-editable :deep(.button-vue) {
  margin-right: 8px;
}

/* MetaVox container */
.metavox-container {
  padding: 12px;
  min-height: 200px;
}

/* Preview dialog */
.preview-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px;
  gap: 16px;
  color: var(--color-text-maxcontrast);
}

.preview-container {
  display: flex;
  flex-direction: column;
  height: 70vh;
  max-height: 800px;
}

.preview-header {
  padding: 20px 20px 16px 20px;
  border-bottom: 1px solid var(--color-border);
  flex-shrink: 0;
}

.preview-header h3 {
  margin: 0 0 8px 0;
  font-size: 20px;
  font-weight: 600;
}

.preview-meta {
  margin: 0;
  font-size: 12px;
  color: var(--color-text-maxcontrast);
}

.preview-tabs {
  display: flex;
  gap: 0;
  border-bottom: 1px solid var(--color-border);
  flex-shrink: 0;
  padding: 0 20px;
}

.preview-tabs button {
  padding: 12px 20px;
  background: none;
  border: none;
  border-bottom: 2px solid transparent;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  color: var(--color-text-maxcontrast);
  transition: all 0.2s;
}

.preview-tabs button:hover {
  color: var(--color-main-text);
  background: var(--color-background-hover);
}

.preview-tabs button.active {
  color: var(--color-primary-element);
  border-bottom-color: var(--color-primary-element);
}

.preview-content {
  padding: 20px;
  overflow-y: auto;
  flex: 1;
  min-height: 0;
}

.preview-body {
  line-height: 1.6;
}

.preview-body :deep(h1),
.preview-body :deep(h2),
.preview-body :deep(h3),
.preview-body :deep(h4),
.preview-body :deep(h5),
.preview-body :deep(h6) {
  margin-top: 1.5em;
  margin-bottom: 0.5em;
}

.preview-body :deep(p) {
  margin-bottom: 1em;
}

.preview-body :deep(ul),
.preview-body :deep(ol) {
  margin-left: 1.5em;
  margin-bottom: 1em;
}

.preview-source pre {
  background: var(--color-background-dark);
  padding: 16px;
  border-radius: var(--border-radius);
  overflow-x: auto;
  font-family: 'Courier New', monospace;
  font-size: 13px;
  line-height: 1.5;
  white-space: pre-wrap;
  word-wrap: break-word;
}

/* Diff viewer */
.preview-diff {
  height: 100%;
}

.diff-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px;
  gap: 16px;
  color: var(--color-text-maxcontrast);
}

.diff-error {
  padding: 20px;
  text-align: center;
  color: var(--color-text-maxcontrast);
}

.diff-viewer {
  font-family: 'Courier New', monospace;
  font-size: 13px;
  line-height: 1.5;
  background: var(--color-background-dark);
  border-radius: var(--border-radius);
  overflow: hidden;
}

.diff-viewer > div {
  display: flex;
  padding: 2px 8px;
  border-left: 3px solid transparent;
}

.diff-prefix {
  display: inline-block;
  width: 20px;
  flex-shrink: 0;
  font-weight: bold;
  user-select: none;
}

.diff-line {
  flex: 1;
  white-space: pre-wrap;
  word-wrap: break-word;
}

.diff-added {
  background: rgba(16, 185, 129, 0.15);
  border-left-color: #10b981;
}

.diff-added .diff-prefix {
  color: #10b981;
}

.diff-removed {
  background: rgba(239, 68, 68, 0.15);
  border-left-color: #ef4444;
}

.diff-removed .diff-prefix {
  color: #ef4444;
}

.diff-unchanged {
  background: transparent;
}

.diff-unchanged .diff-prefix {
  color: var(--color-text-maxcontrast);
}
</style>
