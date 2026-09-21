<template>
  <NcAppSidebar
    v-if="isOpen"
    :name="pageName"
    :subtitle="pageSubtitle"
    :active="activeTab"
    @update:active="onTabChange"
    @close="handleClose"
  >
    <!-- Details Tab (Page Properties/File Info) -->
    <NcAppSidebarTab
      id="details-tab"
      :name="t('intravox', 'Details')"
      :order="1"
    >
      <template #icon>
        <InformationOutline :size="20" />
      </template>

      <div v-if="loadingMetadata" class="loading">
        {{ t('intravox', 'Loading properties …') }}
      </div>

      <div v-else-if="metadataError" class="error-message">
        {{ metadataError }}
      </div>

      <div v-else-if="metadata" class="metadata-container">
        <!-- Name is READ-ONLY here. Renaming lives in the actions menu and the
             page tree; a third path from this panel used a different endpoint
             (PUT /metadata instead of the rename call), so two code paths could
             drift apart for one action. The sidebar shows, the menu does. -->
        <div class="metadata-row">
          <label class="metadata-label">{{ t('intravox', 'Name') }}</label>
          <div class="metadata-value">
            {{ metadata.title || t('intravox', 'Untitled') }}
          </div>
        </div>

        <!-- Modified -->
        <div class="metadata-row">
          <label class="metadata-label">{{ t('intravox', 'Modified') }}</label>
          <div class="metadata-value">
            {{ metadata.modifiedRelative }}
            <span class="metadata-hint">{{ metadata.modifiedFormatted }}</span>
          </div>
        </div>

        <!-- Path -->
        <div class="metadata-row">
          <label class="metadata-label">{{ t('intravox', 'Location') }}</label>
          <div class="metadata-value metadata-path">
            <a :href="getFolderUrl(metadata.path)" target="_blank" rel="noopener noreferrer" class="folder-link">
              {{ getDisplayPath(metadata.path) }}
            </a>
          </div>
        </div>

        <!-- Page Type -->
        <div v-if="metadata.type" class="metadata-row">
          <label class="metadata-label">{{ t('intravox', 'Type') }}</label>
          <div class="metadata-value">
            <span class="page-type-badge" :class="`page-type-${metadata.type}`">
              {{ getPageTypeLabel(metadata.type) }}
            </span>
            <span v-if="metadata.hasChildren" class="metadata-hint">
              {{ t('intravox', '(has child pages)') }}
            </span>
          </div>
        </div>

        <!-- Unique ID (for sharing) -->
        <div v-if="metadata.uniqueId" class="metadata-row">
          <label class="metadata-label">{{ t('intravox', 'UID') }}</label>
          <div class="metadata-value metadata-monospace">{{ metadata.uniqueId }}</div>
        </div>
      </div>
    </NcAppSidebarTab>

    <!-- MetaVox Tab. Its own tab rather than a section under Details because
         it is EDITABLE — Details is read-only facts, this is a form you fill
         in and save. Rendered from MetaVox's OCS API; MetaVox itself contains
         no IntraVox code and needs none. -->
    <NcAppSidebarTab
      v-if="metaVoxAvailable"
      id="metavox-tab"
      :name="t('intravox', 'MetaVox')"
      :order="2"
    >
      <template #icon>
        <MetaVoxIcon :size="20" />
      </template>
      <MetaVoxPanel
        :file-id="fileId"
        :groupfolder-id="groupfolderId"
        @saved="$emit('metadata-saved')" />
    </NcAppSidebarTab>

    <!-- Translations Tab.
         Only rendered when the intranet actually has more than one content
         language: on a single-language install this whole concept is noise,
         and the majority of installs are single-language. Multilingual UI that
         everyone pays for is the documented failure of the WordPress plugins. -->
    <NcAppSidebarTab
      v-if="isMultilingual"
      id="translations-tab"
      :name="t('intravox', 'Translations')"
      :order="3"
    >
      <template #icon>
        <Translate :size="20" />
      </template>
      <TranslationsPanel
        :page-id="pageId"
        :initial-translations="translations"
        :language-names="languageNames"
        @navigate="$emit('navigate', $event)"
        @changed="$emit('translations-changed', $event)" />
    </NcAppSidebarTab>

    <!-- Versions Tab - Uses IntraVox versions API (leverages Nextcloud/GroupFolders versioning) -->
    <NcAppSidebarTab
      id="versions-tab"
      :name="t('intravox', 'Versions')"
      :order="4"
    >
      <template #icon>
        <History :size="20" />
      </template>

      <div class="versions-container">
        <div v-if="loadingVersions" class="loading">
          {{ t('intravox', 'Loading versions …') }}
        </div>

        <div v-else-if="versionError" class="error-message">
          {{ versionError }}
        </div>

        <div v-else-if="versions.length === 0" class="empty-state">
          <p>{{ t('intravox', 'No versions available') }}</p>
          <p class="hint">{{ t('intravox', 'Versions are created automatically when you save changes') }}</p>
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
                <span class="version-name">{{ t('intravox', 'Current version') }}</span>
                <span v-if="currentVersionAuthor" class="version-author">{{ currentVersionAuthor }}</span>
              </div>
              <div class="version-details">
                {{ currentVersionDate }}<span v-if="currentVersionSize"> · {{ currentVersionSize }}</span>
              </div>
            </div>
          </div>

          <!-- Version history -->
          <div class="version-history-label">
            {{ t('intravox', 'Version history') }}
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
                <span class="version-name">{{ version.label || t('intravox', 'Version') }}</span>
                <span v-if="version.author" class="version-author">{{ version.author }}</span>
              </div>
              <div class="version-details">
                {{ version.relativeTime || version.formattedDate }} · {{ formatBytes(version.size) }}
              </div>
            </div>
            <div class="version-actions">
              <NcButton
                type="tertiary"
                :aria-label="t('intravox', 'Restore this version')"
                :title="t('intravox', 'Restore this version')"
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
    :name="t('intravox', 'Restore version')"
    :message="t('intravox', 'A backup of the current version will be created before restoring. Do you want to continue?')"
    :buttons="[
      {
        label: t('intravox', 'Cancel'),
        callback: cancelRestore
      },
      {
        label: t('intravox', 'Restore'),
        type: 'primary',
        callback: restoreVersion
      }
    ]"
    @close="cancelRestore"
  />

</template>

<script>
import { translate } from '@nextcloud/l10n';
import { generateUrl } from '@nextcloud/router';
import axios from '@nextcloud/axios';
import { showError, showSuccess } from '@nextcloud/dialogs';
import { NcAppSidebar, NcAppSidebarTab, NcButton, NcDialog } from '@nextcloud/vue';
import History from 'vue-material-design-icons/History.vue';
import InformationOutline from 'vue-material-design-icons/InformationOutline.vue';
import Restore from 'vue-material-design-icons/Restore.vue';
import Translate from 'vue-material-design-icons/Translate.vue';
import MetaVoxIcon from './icons/MetaVoxIcon.vue';
import TranslationsPanel from './TranslationsPanel.vue';
import MetaVoxPanel from './MetaVoxPanel.vue';

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
    Restore,
    Translate,
    TranslationsPanel,
    MetaVoxPanel
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
    /** Other language versions of this page, as returned with the page. */
    translations: {
      type: Array,
      default: () => []
    },
    /** language code => display name, for the translations tab. */
    languageNames: {
      type: Object,
      default: () => ({})
    },
    /**
     * Whether this intranet has more than one content language. Gates the
     * Translations tab: a single-language install must not pay for a
     * multilingual concept it never uses.
     */
    isMultilingual: {
      type: Boolean,
      default: false
    },
    /**
     * Whether MetaVox is installed. Gates its tab exactly as the action menu
     * gates its entry, so the two can never disagree about what exists.
     */
    metaVoxAvailable: {
      type: Boolean,
      default: false
    },
    /** Nextcloud file id of the page JSON, for MetaVox lookups. */
    fileId: {
      type: Number,
      default: null
    },
    /** Groupfolder holding the page; MetaVox assigns its fields per folder. */
    groupfolderId: {
      type: Number,
      default: null
    },
    initialTab: {
      type: String,
      default: 'details-tab'
    }
  },
  emits: [
    'close',
    'version-restored',
    'version-selected',
    'metadata-saved',
    'navigate',
    'translations-changed'
  ],
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
      selectedVersion: null,
      editingLabel: null,
      editableLabel: '',
      isRestoring: false, // Flag to prevent auto-select during restore
      versionsLoaded: false // Flag for lazy loading
    };
  },
  computed: {
    pageSubtitle() {
      return this.t('intravox', 'Page details and history');
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
      return this.t('intravox', 'Now');
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
      }
    },
    pageId(newId) {
      // De sidebar blijft sinds 2.0.2.17 open tijdens navigeren, dus hij moet
      // zijn eigen inhoud verversen: alleen wissen liet een leeg paneel achter
      // en een verouderde versielijst.
      if (!this.isOpen || !newId) return;

      this.selectedVersion = null;
      this.versions = [];
      this.currentVersion = null;
      this.metadata = null;
      this.versionsLoaded = false;

      // Herlaad wat de zichtbare tab nodig heeft; de rest blijft lui laden
      // zodra de gebruiker die tab opent.
      if (this.activeTab === 'versions-tab') {
        this.loadVersions();
        this.versionsLoaded = true;
      } else {
        this.loadMetadata().catch(() => {});
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
      } else if (newTab === 'details-tab') {
        if (!this.metadata) {
          this.loadMetadata().catch(() => {});
        }
      }
    },
    versions(newVersions) {
      // Auto-select first version if versions tab is active and versions just loaded
      // BUT skip if we're in the middle of a restore operation OR already have a selection
      if (this.activeTab === 'versions-tab' && newVersions.length > 0 && !this.isRestoring && !this.selectedVersion) {
        this.autoSelectFirstVersion();
      }
    }
  },
  created() {
    // Not in data(): bookkeeping for overlapping requests, not render state.
    this._metadataToken = 0;
    this._versionsToken = 0;
  },
  mounted() {
    if (this.isOpen) {
      // Only load metadata at mount (details tab is default)
      this.loadMetadata().catch(() => {});
    }

    // Listen for page save events to refresh versions
    window.addEventListener('intravox:page:saved', this.handlePageSaved);
    // Listen for edit mode started to reset to current version
    window.addEventListener('intravox:edit:started', this.handleEditStarted);
    // MetaVox saves the publish/expiration dates itself, outside IntraVox's
    // save flow. Listen for it so the page's publication state (and its
    // Draft/Scheduled/Expired badge) is recomputed straight away.
    window.addEventListener('metavox:metadata:saved', this.handleMetaVoxSaved);
  },
  beforeUnmount() {
    // Clean up event listeners
    window.removeEventListener('intravox:page:saved', this.handlePageSaved);
    window.removeEventListener('intravox:edit:started', this.handleEditStarted);
    window.removeEventListener('metavox:metadata:saved', this.handleMetaVoxSaved);
  },
  methods: {
    t(app, text, vars) {
      return translate(app, text, vars);
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
      // NcAppSidebar beheert de actieve tab zelf; `:active.sync` is Vue 2-syntax
      // en doet in Vue 3 niets, dus zonder deze toewijzing bleef activeTab op
      // 'details-tab' hangen. Daardoor herlaadde de sidebar bij navigatie de
      // metadata terwijl de gebruiker naar Versions keek.
      // Het laden zelf gebeurt in de activeTab-watcher — één plek.
      this.activeTab = newTabId;
    },
    async loadVersions() {
      // Same two hazards as loadMetadata(), reachable when the versions tab is
      // the remembered initialTab.
      if (!this.pageId) {
        return;
      }

      const token = ++this._versionsToken;
      this.loadingVersions = true;
      this.versionError = null;

      try {
        const url = generateUrl(`/apps/intravox/api/pages/${this.pageId}/versions`);
        const response = await axios.get(url);
        if (token !== this._versionsToken) return;

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
        if (token !== this._versionsToken) return;
        console.error('Failed to load versions:', error);
        this.versionError = error.response?.data?.error || this.t('intravox', 'Failed to load version history');
      } finally {
        if (token === this._versionsToken) {
          this.loadingVersions = false;
        }
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

        // Show the current page (which now contains the restored content)
        // Don't select a historical version - that would show a preview
        // instead of the actual restored page
        this.$nextTick(() => {
          this.selectCurrentVersion();
          this.isRestoring = false;
        });

        this.versionToRestore = null;
      } catch (error) {
        console.error('Failed to restore version:', error);
        showError(this.t('intravox', 'Failed to restore version: {error}', {
          error: error.response?.data?.error || error.message
        }));
        this.isRestoring = false; // Reset flag on error too
      } finally {
        this.restoringVersion = false;
      }
    },
    async loadMetadata() {
      // Mounted behind v-show, so we exist before currentPage resolves: without
      // this, a remembered-open sidebar fetched /api/pages/undefined/metadata
      // and showed its 404 body ("Page not found") as the user's page.
      if (!this.pageId) {
        return;
      }

      // mounted() and the pageId watcher can overlap and finish out of order,
      // so only the newest load may write. Measured timings and the regression
      // itself live in scripts/check-sidebar-load-guards.js.
      const token = ++this._metadataToken;
      this.loadingMetadata = true;
      this.metadataError = null;

      try {
        const url = generateUrl(`/apps/intravox/api/pages/${this.pageId}/metadata`);
        const response = await axios.get(url);
        if (token !== this._metadataToken) return;
        this.metadata = response.data;
      } catch (error) {
        if (token !== this._metadataToken) return;
        console.error('Failed to load metadata:', error);
        this.metadataError = error.response?.data?.error || this.t('intravox', 'Failed to load properties');
      } finally {
        if (token === this._metadataToken) {
          this.loadingMetadata = false;
        }
      }
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
        showSuccess(this.t('intravox', 'Version label updated'));
      } catch (error) {
        showError(this.t('intravox', 'Failed to update version label: {error}', {
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
      // Department pages have max depth 5
      return 5;
    },
    getPageTypeLabel(type) {
      const labels = {
        'department': this.t('intravox', 'Department'),
        'container': this.t('intravox', 'Container'),
        'page': this.t('intravox', 'Page')
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
    },
    handleMetaVoxSaved() {
      // MetaVox stores the publish/expiration dates on the page file itself, so
      // IntraVox has no idea the page's publication state just changed. Ask the
      // app to re-read it, otherwise a page that was just scheduled would keep
      // showing its old Draft/Published badge until a reload.
      this.$emit('metadata-saved');
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

/* Note: the NcAppSidebarTabs double-underline workaround lives in
 * css/main.css (loaded globally via Util::addStyle) — scoped CSS in a Vue
 * SFC can't reach the third-party data-v attribute selector. */
</style>
