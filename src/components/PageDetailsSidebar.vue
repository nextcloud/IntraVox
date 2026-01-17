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

    <!-- Versions Tab - Uses IntraVox versions API (leverages Nextcloud/GroupFolders versioning) -->
    <NcAppSidebarTab
      id="versions-tab"
      :name="t('Versions')"
      :order="3"
    >
      <template #icon>
        <History :size="20" />
      </template>

      <div class="versions-container">
        <div v-if="loadingVersions" class="loading">
          {{ t('Loading versions...') }}
        </div>

        <div v-else-if="versionError" class="error-message">
          {{ versionError }}
        </div>

        <div v-else-if="versions.length === 0" class="empty-state">
          <p>{{ t('No versions available') }}</p>
          <p class="hint">{{ t('Versions are created automatically when you save changes') }}</p>
        </div>

        <div v-else class="version-list">
          <!-- Current version (the actual file, not from history) - like Nextcloud Files app -->
          <div
            class="version-item version-item--current"
            :class="{ 'version-item--selected': selectedVersion === null }"
            @click="selectCurrentVersion"
          >
            <div class="version-info">
              <div class="version-header">
                <span class="version-name">{{ t('Current version') }}</span>
                <span v-if="currentVersionAuthor" class="version-author">{{ currentVersionAuthor }}</span>
              </div>
              <div class="version-details">
                {{ currentVersionDate }}<span v-if="currentVersionSize"> · {{ currentVersionSize }}</span>
              </div>
            </div>
          </div>

          <!-- Version history -->
          <div class="version-history-label">
            {{ t('Version history') }}
          </div>

          <div
            v-for="version in versions"
            :key="version.timestamp"
            class="version-item"
            :class="{ 'version-item--selected': selectedVersion?.timestamp === version.timestamp }"
            @click="selectVersion(version)"
          >
            <div class="version-info">
              <div class="version-header">
                <span class="version-name">{{ version.label || t('Version') }}</span>
                <span v-if="version.author" class="version-author">{{ version.author }}</span>
              </div>
              <div class="version-details">
                {{ version.relativeTime || version.formattedDate }} · {{ formatBytes(version.size) }}
              </div>
            </div>
            <div class="version-actions">
              <NcButton
                type="tertiary"
                :aria-label="t('Restore this version')"
                :title="t('Restore this version')"
                @click.stop="confirmRestoreVersion(version.timestamp)"
              >
                <template #icon>
                  <Restore :size="20" />
                </template>
              </NcButton>
            </div>
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
import { NcAppSidebar, NcAppSidebarTab, NcButton, NcDialog } from '@nextcloud/vue';
import History from 'vue-material-design-icons/History.vue';
import InformationOutline from 'vue-material-design-icons/InformationOutline.vue';
import Restore from 'vue-material-design-icons/Restore.vue';
import MetaVoxIcon from './icons/MetaVoxIcon.vue';

export default {
  name: 'PageDetailsSidebar',
  components: {
    NcAppSidebar,
    NcAppSidebarTab,
    NcButton,
    NcDialog,
    History,
    InformationOutline,
    MetaVoxIcon,
    Restore
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
      currentVersion: null, // Current file metadata from API
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
      isRestoring: false, // Flag to prevent auto-select during restore
      versionsLoaded: false // Flag for lazy loading
    };
  },
  computed: {
    pageSubtitle() {
      return this.t('Page details and history');
    },
    canEditTitle() {
      // Check write permission using Nextcloud's permissions
      return this.metadata?.permissions?.canWrite ?? this.metadata?.canEdit ?? false;
    },
    currentVersionDate() {
      // Use the relativeTime from currentVersion API response (like Files app)
      if (this.currentVersion?.relativeTime) {
        return this.currentVersion.relativeTime;
      }
      // Fallback to metadata if versions not loaded yet
      if (this.metadata?.modifiedRelative) {
        return this.metadata.modifiedRelative;
      }
      return this.t('Now');
    },
    currentVersionSize() {
      // Size from currentVersion API response
      if (this.currentVersion?.size) {
        return this.formatBytes(this.currentVersion.size);
      }
      return null;
    },
    currentVersionAuthor() {
      // Author from currentVersion API response
      return this.currentVersion?.author || null;
    }
  },
  watch: {
    isOpen(newValue, oldValue) {
      if (newValue && !oldValue) {
        // Sidebar just opened - reset state and apply initialTab
        this.versionsLoaded = false;
        this.activeTab = this.initialTab || 'details-tab';
        // Only load metadata (for details tab - default)
        this.loadMetadata().catch(() => {});
        this.checkMetaVoxInstalled();
      }
    },
    pageId() {
      // Note: Sidebar is closed on navigation (in App.vue), so this is a fallback
      if (this.isOpen) {
        this.versionsLoaded = false;
        this.selectedVersion = null;
        this.versions = [];
        this.currentVersion = null;
        this.metadata = null;
      }
    },
    initialTab(newTab) {
      // Only apply initialTab if sidebar is currently closed
      // When sidebar is open, user controls the active tab
      if (!this.isOpen && newTab) {
        this.activeTab = newTab;
      }
    },
    activeTab(newTab) {
      // Load data when tab is activated (lazy loading)
      if (newTab === 'versions-tab' && !this.versionsLoaded) {
        this.loadVersions();
        this.versionsLoaded = true;
      } else if (newTab === 'details-tab' && !this.metadata) {
        this.loadMetadata().catch(() => {});
      } else if (newTab === 'metavox-tab' && this.metaVoxInstalled) {
        this.$nextTick(() => {
          this.dispatchMetaVoxUpdate();
        });
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
      // Only load metadata at mount (details tab is default)
      this.loadMetadata().catch(() => {});
      this.checkMetaVoxInstalled();
    }

    // Listen for page save events to refresh versions
    window.addEventListener('intravox:page:saved', this.handlePageSaved);
    // Listen for edit mode started to reset to current version
    window.addEventListener('intravox:edit:started', this.handleEditStarted);
  },
  beforeUnmount() {
    // Clean up event listeners
    window.removeEventListener('intravox:page:saved', this.handlePageSaved);
    window.removeEventListener('intravox:edit:started', this.handleEditStarted);
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
      // Load versions when tab is activated (lazy loading)
      if (newTabId === 'versions-tab' && !this.versionsLoaded) {
        this.loadVersions();
        this.versionsLoaded = true;
      } else if (newTabId === 'metavox-tab' && this.metaVoxInstalled) {
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

        // API response structure: { currentVersion: {...}, versions: [...] }
        if (response.data && typeof response.data === 'object' && 'versions' in response.data) {
          this.currentVersion = response.data.currentVersion;
          this.versions = response.data.versions;
        } else {
          // Backwards compatibility: old API returned array directly
          this.versions = response.data;
          this.currentVersion = null;
        }
      } catch (error) {
        console.error('Failed to load versions:', error);
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
    selectCurrentVersion() {
      // Clear version selection to show current page
      this.selectedVersion = null;
      // Emit event to clear version preview in parent
      this.$emit('version-selected', {
        version: null,
        pageId: this.pageId
      });
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
      // Auto-select current version (not a history version)
      this.$nextTick(() => {
        this.selectCurrentVersion();
      });
    },
    handlePageSaved(event) {
      // Refresh versions when this page is saved (if versions were already loaded)
      if (event.detail?.pageId === this.pageId && this.versionsLoaded) {
        this.loadVersions();
      }
    },
    handleEditStarted(event) {
      // When edit mode starts, reset to current version selection
      // User should always edit the current version, not a history version
      if (event.detail?.pageId === this.pageId) {
        this.selectCurrentVersion();
      }
    }
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

.version-name {
  font-weight: 600;
  font-size: 14px;
}

.version-author {
  font-size: 12px;
  color: var(--color-text-maxcontrast);
}

.version-badge {
  font-size: 11px;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: var(--border-radius-pill, 20px);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.version-badge--current {
  background-color: var(--color-primary-element);
  color: var(--color-primary-element-text, white);
}

/* Current version item styling */
.version-item--current {
  background: var(--color-primary-element-light);
  border-color: var(--color-primary-element);
}

.version-item--current:hover {
  background: var(--color-primary-element-light);
}

/* Version history label */
.version-history-label {
  font-size: 12px;
  font-weight: 600;
  color: var(--color-text-maxcontrast);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  padding: 12px 0 8px 0;
  margin-top: 4px;
  border-top: 1px solid var(--color-border);
}

.version-details {
  font-size: 12px;
  color: var(--color-text-maxcontrast);
}

.version-actions {
  display: flex;
  gap: 4px;
  align-items: center;
}

/* Versions container */
.versions-container {
  min-height: 200px;
  padding: 8px;
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
</style>
