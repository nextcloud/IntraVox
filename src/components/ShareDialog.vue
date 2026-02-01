<template>
  <NcDialog :name="t('Public Link')"
            :can-close="true"
            @close="$emit('close')">
    <div class="share-dialog">
      <!-- Scope indicator -->
      <div class="share-scope">
        <FolderOutline v-if="shareInfo.scope === 'folder'" :size="20" />
        <FileDocumentOutline v-else :size="20" />
        <span class="scope-label">
          <template v-if="shareInfo.scope === 'page'">
            {{ t('This link shares only this page') }}
          </template>
          <template v-else-if="shareInfo.isLanguageRoot">
            {{ t('This link shares all {language} pages', { language: shareInfo.scopeLabel }) }}
          </template>
          <template v-else>
            {{ t('This link shares the "{section}" section', { section: shareInfo.scopeLabel || shareInfo.scopeName }) }}
          </template>
        </span>
      </div>

      <!-- Password protected indicator -->
      <div v-if="shareInfo.hasPassword" class="share-password-badge">
        <LockOutline :size="16" />
        <span class="password-label">{{ t('Password protected') }}</span>
        <span class="password-hint">{{ t('Visitors must enter a password to access this link. Manage in Files.') }}</span>
      </div>

      <!-- Copy link button -->
      <div class="share-copy-row">
        <NcButton type="secondary"
                  wide
                  @click="copyUrl">
          <template #icon>
            <ContentCopy :size="20" />
          </template>
          {{ t('Copy public link') }}
        </NcButton>
      </div>

      <!-- Navigation tree (for folder shares) -->
      <div v-if="shareInfo.scope === 'folder' && shareInfo.navigation?.length > 0" class="share-navigation">
        <h4>{{ t('Includes these pages:') }}</h4>
        <ul class="nav-tree">
          <li v-for="item in shareInfo.navigation" :key="item.title" class="nav-tree-item">
            {{ item.title }}
            <ul v-if="item.children?.length > 0" class="nav-tree-children">
              <li v-for="child in item.children" :key="child.title" class="nav-tree-child">
                {{ child.title }}
              </li>
            </ul>
          </li>
        </ul>
      </div>

      <!-- Read-only warning -->
      <NcNoteCard type="info" class="share-info-note">
        <p>{{ t('Visitors can only VIEW this content. Editing via this link is not possible, even if the Files share allows it.') }}</p>
      </NcNoteCard>

      <!-- Manage link - clickable -->
      <a :href="filesUrl" target="_blank" class="share-manage-link">
        <FolderOutline :size="16" />
        <span class="manage-text">
          {{ t('Manage share in Files') }}
        </span>
        <OpenInNew :size="14" class="manage-arrow" />
      </a>
    </div>
  </NcDialog>
</template>

<script>
import { translate as t } from '@nextcloud/l10n';
import { generateUrl } from '@nextcloud/router';
import { showSuccess, showError } from '@nextcloud/dialogs';
import { NcDialog, NcButton, NcNoteCard } from '@nextcloud/vue';
import ContentCopy from 'vue-material-design-icons/ContentCopy.vue';
import FolderOutline from 'vue-material-design-icons/FolderOutline.vue';
import FileDocumentOutline from 'vue-material-design-icons/FileDocumentOutline.vue';
import LockOutline from 'vue-material-design-icons/LockOutline.vue';
import OpenInNew from 'vue-material-design-icons/OpenInNew.vue';

export default {
  name: 'ShareDialog',
  components: {
    NcDialog,
    NcButton,
    NcNoteCard,
    ContentCopy,
    FolderOutline,
    FileDocumentOutline,
    LockOutline,
    OpenInNew
  },
  props: {
    shareInfo: {
      type: Object,
      required: true
    },
    pageTitle: {
      type: String,
      default: ''
    }
  },
  emits: ['close'],
  computed: {
    publicUrl() {
      const baseUrl = window.location.origin;
      return baseUrl + this.shareInfo.publicUrl;
    },
    filesUrl() {
      // Split path and query, use generateUrl for the path part only
      const url = this.shareInfo.filesUrl || '';
      const [path, query] = url.split('?');
      const base = generateUrl(path);
      return query ? base + '?' + query : base;
    }
  },
  methods: {
    t(key, vars = {}) {
      return t('intravox', key, vars);
    },
    async copyUrl() {
      try {
        await navigator.clipboard.writeText(this.publicUrl);
        showSuccess(this.t('Link copied to clipboard'));
      } catch (err) {
        const textArea = document.createElement('textarea');
        textArea.value = this.publicUrl;
        document.body.appendChild(textArea);
        textArea.select();
        try {
          document.execCommand('copy');
          showSuccess(this.t('Link copied to clipboard'));
        } catch (e) {
          showError(this.t('Failed to copy link'));
        }
        document.body.removeChild(textArea);
      }
    }
  }
};
</script>

<style scoped>
.share-dialog {
  padding: 16px;
}

.share-scope {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px;
  background: var(--color-background-hover);
  border-radius: var(--border-radius);
  margin-bottom: 16px;
}

.scope-label {
  color: var(--color-text-maxcontrast);
}

.share-password-badge {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  padding: 10px 12px;
  background: var(--color-warning-hover, #fff3cd);
  border-radius: var(--border-radius);
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.share-password-badge .password-label {
  font-weight: 600;
  font-size: 13px;
  color: var(--color-main-text);
}

.share-password-badge .password-hint {
  font-size: 12px;
  color: var(--color-text-maxcontrast);
  width: 100%;
  padding-left: 24px;
}

.share-copy-row {
  margin-bottom: 16px;
}

.share-navigation {
  margin-bottom: 16px;
}

.share-navigation h4 {
  font-size: 14px;
  font-weight: 600;
  margin-bottom: 8px;
  color: var(--color-text-maxcontrast);
}

.nav-tree {
  margin: 0;
  padding-left: 0;
  list-style: none;
  font-size: 13px;
}

.nav-tree-item {
  margin: 2px 0;
  color: var(--color-main-text);
}

.nav-tree-children {
  margin: 0;
  padding-left: 20px;
  list-style: none;
}

.nav-tree-child {
  margin: 2px 0;
  color: var(--color-text-maxcontrast);
  position: relative;
  padding-left: 14px;
}

.nav-tree-child::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 50%;
  width: 8px;
  border-left: 1px solid var(--color-border-dark);
  border-bottom: 1px solid var(--color-border-dark);
}

.nav-tree-child:last-child::before {
  border-left-style: solid;
}

.share-info-note {
  margin-bottom: 16px;
}

.share-info-note p {
  margin: 0;
}

.share-manage-link {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: var(--color-primary-element);
  text-decoration: none;
  padding: 8px 12px;
  border-radius: var(--border-radius);
  transition: background-color 0.1s ease;
}

.share-manage-link:hover {
  background-color: var(--color-background-hover);
}

.manage-text {
  flex: 1;
}

.manage-arrow {
  opacity: 0.5;
}
</style>
