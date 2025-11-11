<template>
  <div v-if="isOpen" class="page-details-sidebar">
    <div class="sidebar-header">
      <h3>{{ t('Page Details') }}</h3>
      <NcButton
        type="tertiary"
        @click="$emit('close')"
        :aria-label="t('Close sidebar')"
      >
        <template #icon>
          <Close :size="20" />
        </template>
      </NcButton>
    </div>

    <div class="sidebar-content">
      <!-- Version History Tab -->
      <div class="sidebar-section">
        <h4>{{ t('Version History') }}</h4>

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
              @click="restoreVersion(version.timestamp)"
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

      <!-- Placeholder for future MetaVox integration -->
      <div class="sidebar-section">
        <h4>{{ t('Metadata') }}</h4>
        <p class="coming-soon">{{ t('MetaVox integration coming soon') }}</p>
      </div>
    </div>
  </div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n';
import { generateUrl } from '@nextcloud/router';
import axios from '@nextcloud/axios';
import { NcButton } from '@nextcloud/vue';
import Close from 'vue-material-design-icons/Close.vue';
import Restore from 'vue-material-design-icons/Restore.vue';

export default {
  name: 'PageDetailsSidebar',
  components: {
    NcButton,
    Close,
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
    }
  },
  emits: ['close', 'version-restored'],
  data() {
    return {
      versions: [],
      loadingVersions: false,
      versionError: null,
      restoringVersion: false
    };
  },
  watch: {
    isOpen(newValue) {
      if (newValue) {
        this.loadVersions();
      }
    },
    pageId() {
      if (this.isOpen) {
        this.loadVersions();
      }
    }
  },
  mounted() {
    if (this.isOpen) {
      this.loadVersions();
    }
  },
  methods: {
    t(key, vars = {}) {
      return t('intravox', key, vars);
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
    async restoreVersion(timestamp) {
      if (!confirm(this.t('Are you sure you want to restore this version? This will create a new version of the current state.'))) {
        return;
      }

      this.restoringVersion = true;

      try {
        const url = generateUrl(`/apps/intravox/api/pages/${this.pageId}/versions/${timestamp}`);
        const response = await axios.post(url);

        // Emit event to parent to reload the page
        this.$emit('version-restored', response.data);

        // Reload versions list
        await this.loadVersions();
      } catch (error) {
        console.error('Failed to restore version:', error);
        alert(this.t('Failed to restore version: {error}', {
          error: error.response?.data?.error || error.message
        }));
      } finally {
        this.restoringVersion = false;
      }
    }
  }
};
</script>

<style scoped>
.page-details-sidebar {
  position: fixed;
  top: 50px;
  right: 0;
  width: 400px;
  height: calc(100vh - 50px);
  background: var(--color-main-background);
  border-left: 1px solid var(--color-border);
  box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
  z-index: 1000;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.sidebar-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  border-bottom: 1px solid var(--color-border);
}

.sidebar-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
}

.sidebar-content {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
}

.sidebar-section {
  margin-bottom: 32px;
}

.sidebar-section h4 {
  margin: 0 0 12px 0;
  font-size: 14px;
  font-weight: 600;
  color: var(--color-text-maxcontrast);
  text-transform: uppercase;
}

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
</style>
