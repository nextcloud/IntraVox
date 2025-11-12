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
            <span v-else class="metadata-text" @click="startTitleEdit">
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

        <!-- Size -->
        <div class="metadata-row">
          <label class="metadata-label">{{ t('Size') }}</label>
          <div class="metadata-value">{{ metadata.sizeFormatted }}</div>
        </div>

        <!-- Modified -->
        <div class="metadata-row">
          <label class="metadata-label">{{ t('Modified') }}</label>
          <div class="metadata-value">
            {{ metadata.modifiedRelative }}
            <span class="metadata-hint">{{ metadata.modifiedFormatted }}</span>
          </div>
        </div>

        <!-- Created -->
        <div class="metadata-row">
          <label class="metadata-label">{{ t('Created') }}</label>
          <div class="metadata-value">{{ metadata.createdFormatted }}</div>
        </div>

        <!-- Owner -->
        <div class="metadata-row">
          <label class="metadata-label">{{ t('Owner') }}</label>
          <div class="metadata-value">{{ metadata.owner }}</div>
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

    <!-- Versions Tab -->
    <NcAppSidebarTab
      id="versions-tab"
      :name="t('Versions')"
      :order="3"
    >
      <template #icon>
        <History :size="20" />
      </template>

        <div v-if="loadingVersions" class="loading">
          {{ t('Loading versions...') }}
        </div>

        <div v-else-if="versionError" class="error-message">
          {{ versionError }}
        </div>

        <div v-else-if="versions.length === 0" class="empty-state">
          <p>{{ t('No versions available yet.') }}</p>
          <p class="hint">{{ t('Versions are created automatically when you save changes to this page.') }}</p>
        </div>

        <div v-else class="version-list">
          <div
            v-for="version in versions"
            :key="version.timestamp"
            class="version-item"
          >
            <div class="version-info">
              <div class="version-date">{{ version.relativeDate }}</div>
              <div class="version-details">{{ version.date }}</div>
            </div>
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
    }
  },
  emits: ['close', 'version-restored'],
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
      loadingMetaVox: false
    };
  },
  computed: {
    pageSubtitle() {
      return this.t('Page details and history');
    }
  },
  watch: {
    isOpen(newValue) {
      if (newValue) {
        this.loadVersions();
        this.loadMetadata();
        this.checkMetaVoxInstalled();
      }
    },
    pageId() {
      if (this.isOpen) {
        this.loadVersions();
        this.loadMetadata();
        this.dispatchMetaVoxUpdate();
      }
    },
    activeTab(newTab) {
      console.log('[PageDetailsSidebar] Active tab changed to:', newTab);
      if (newTab === 'metavox-tab' && this.metaVoxInstalled) {
        console.log('[PageDetailsSidebar] MetaVox tab activated, dispatching update...');
        // Use nextTick to ensure the container ref is available
        this.$nextTick(() => {
          this.dispatchMetaVoxUpdate();
        });
      }
    },
    metaVoxInstalled(newValue) {
      console.log('[PageDetailsSidebar] MetaVox installed status changed to:', newValue);
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
      this.loadVersions();
      this.loadMetadata();
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

      // Convert internal path to display format
      // Path format: /__groupfolders/4/files/en/mission
      // Display format: IntraVox/en/mission
      if (folderPath.startsWith('/__groupfolders/')) {
        // Extract the part after /__groupfolders/X/
        const pathAfterGroupfolder = folderPath.replace(/^\/__groupfolders\/\d+\//, '');

        // Remove 'files/' from the beginning if present (internal path structure)
        const cleanPath = pathAfterGroupfolder.replace(/^files\//, '');

        // Get the groupfolder name from metadata
        const groupfolderName = this.metadata?.mountPoint || 'IntraVox';

        // Prepend groupfolder name
        return cleanPath ? `${groupfolderName}/${cleanPath}` : groupfolderName;
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

      // Convert groupfolder internal path to Files app URL
      // Path format: /__groupfolders/4/files/en/mission/page.json
      // Target format: /apps/files/files/{parentFolderId}?dir=/IntraVox/en/mission
      if (folderPath.startsWith('/__groupfolders/')) {
        // Extract the part after /__groupfolders/X/
        const pathAfterGroupfolder = folderPath.replace(/^\/__groupfolders\/\d+\//, '');

        // Remove 'files/' from the beginning if present (internal path structure)
        const cleanPath = pathAfterGroupfolder.replace(/^files\//, '');

        // Use the groupfolder name from metadata if available, otherwise default to 'IntraVox'
        const groupfolderName = this.metadata?.mountPoint || 'IntraVox';

        // Get the parent folder fileId from metadata
        const fileId = this.metadata?.parentFolderId;

        if (!fileId) {
          // Fallback if no fileId available
          return '#';
        }

        // Generate Files app URL with fileId and dir parameters
        // Format: /apps/files/files/{parentFolderId}?dir=/IntraVox/en/mission
        const filesPath = `/${groupfolderName}/${cleanPath}`;
        return generateUrl('/apps/files/files/{fileId}?dir={dir}', {
          fileId: fileId,
          dir: filesPath
        });
      }

      // Fallback for non-groupfolder paths
      return generateUrl('/apps/files/?dir={dir}', { dir: folderPath });
    },
    onTabChange(newTabId) {
      console.log('[PageDetailsSidebar] onTabChange called with:', newTabId);
      // This is a more reliable way to detect tab changes
      if (newTabId === 'metavox-tab' && this.metaVoxInstalled) {
        console.log('[PageDetailsSidebar] MetaVox tab activated via event, dispatching update...');
        this.$nextTick(() => {
          this.dispatchMetaVoxUpdate();
        });
      }
    },
    async loadVersions() {
      console.log('[PageDetailsSidebar] loadVersions called', {
        pageId: this.pageId,
        isOpen: this.isOpen
      });

      this.loadingVersions = true;
      this.versionError = null;

      try {
        const url = generateUrl(`/apps/intravox/api/pages/${this.pageId}/versions`);
        console.log('[PageDetailsSidebar] Making API call to:', url);

        const response = await axios.get(url);
        console.log('[PageDetailsSidebar] API response:', response.data);

        this.versions = response.data;
        console.log('[PageDetailsSidebar] Versions set:', this.versions);
      } catch (error) {
        console.error('[PageDetailsSidebar] Failed to load versions:', error);
        console.error('[PageDetailsSidebar] Error details:', {
          message: error.message,
          response: error.response,
          status: error.response?.status
        });
        this.versionError = error.response?.data?.error || this.t('Failed to load version history');
      } finally {
        this.loadingVersions = false;
        console.log('[PageDetailsSidebar] loadVersions finished');
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
      this.showRestoreDialog = false;

      try {
        const url = generateUrl(`/apps/intravox/api/pages/${this.pageId}/versions/${this.versionToRestore}`);
        const response = await axios.post(url);

        // Emit event to parent to reload the page
        this.$emit('version-restored', response.data);

        // Reload versions list
        await this.loadVersions();

        this.versionToRestore = null;
      } catch (error) {
        console.error('Failed to restore version:', error);
        showError(this.t('Failed to restore version: {error}', {
          error: error.response?.data?.error || error.message
        }));
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
      if (!this.metadata?.canEdit) {
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
        console.log('MetaVox check failed, assuming not installed:', error);
        this.metaVoxInstalled = false;
      }
    },
    dispatchMetaVoxUpdate() {
      console.log('[PageDetailsSidebar] dispatchMetaVoxUpdate called', {
        metaVoxInstalled: this.metaVoxInstalled,
        hasContainer: !!this.$refs.metavoxContainer,
        pageId: this.pageId
      });

      if (!this.metaVoxInstalled) {
        console.log('[PageDetailsSidebar] MetaVox not installed, skipping dispatch');
        return;
      }

      if (!this.$refs.metavoxContainer) {
        console.log('[PageDetailsSidebar] MetaVox container ref not available yet');
        return;
      }

      // Dispatch custom event for MetaVox to listen to
      // Similar to how Files app works
      const event = new CustomEvent('intravox:metavox:update', {
        detail: {
          pageId: this.pageId,
          pageName: this.pageName,
          container: this.$refs.metavoxContainer,
          metadata: this.metadata
        }
      });
      window.dispatchEvent(event);

      console.log('[PageDetailsSidebar] Dispatched MetaVox update event for page:', this.pageId);
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
  transition: background 0.2s;
}

.version-item:hover {
  background: var(--color-background-dark);
}

.version-info {
  flex: 1;
}

.version-date {
  font-weight: 600;
  font-size: 14px;
  margin-bottom: 4px;
}

.version-details {
  font-size: 12px;
  color: var(--color-text-maxcontrast);
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
  text-transform: uppercase;
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

.metadata-editable {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.metadata-text {
  cursor: pointer;
  padding: 4px;
  border-radius: var(--border-radius);
  transition: background 0.2s;
}

.metadata-text:hover {
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
