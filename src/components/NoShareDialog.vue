<template>
  <NcDialog :name="t('Share this page')"
            :can-close="true"
            @close="$emit('close')">
    <div class="share-dialog">
      <!-- Status indicator -->
      <div class="share-scope">
        <LinkVariantOff :size="20" />
        <span class="scope-label">
          {{ t('This page is not shared publicly') }}
        </span>
      </div>

      <!-- NC link sharing disabled by admin -->
      <template v-if="isDisabledByAdmin">
        <NcNoteCard type="warning">
          {{ t('Public link sharing is disabled by the administrator.') }}
        </NcNoteCard>
        <p class="share-explanation">
          {{ t('To enable anonymous access to IntraVox pages, an administrator must first enable "Allow users to share via link and emails" in the Nextcloud Sharing settings.') }}
        </p>
        <a :href="adminSharingUrl" target="_blank" class="share-manage-link">
          <CogOutline :size="16" />
          <span class="manage-text">
            {{ t('Open Sharing settings') }}
          </span>
          <OpenInNew :size="14" class="manage-arrow" />
        </a>
      </template>

      <!-- No share exists, but sharing is possible -->
      <template v-else>
        <p class="share-explanation">
          {{ t('To make this page accessible without login, create a public share link in the Files app.') }}
        </p>
        <a v-if="filesUrl" :href="filesUrl" target="_blank" class="share-manage-link">
          <FolderOutline :size="16" />
          <span class="manage-text">
            {{ t('Open in Files') }}
          </span>
          <OpenInNew :size="14" class="manage-arrow" />
        </a>
      </template>
    </div>
  </NcDialog>
</template>

<script>
import { translate as t } from '@nextcloud/l10n';
import { generateUrl } from '@nextcloud/router';
import { NcDialog, NcNoteCard } from '@nextcloud/vue';
import LinkVariantOff from 'vue-material-design-icons/LinkVariantOff.vue';
import FolderOutline from 'vue-material-design-icons/FolderOutline.vue';
import CogOutline from 'vue-material-design-icons/CogOutline.vue';
import OpenInNew from 'vue-material-design-icons/OpenInNew.vue';

export default {
  name: 'NoShareDialog',
  components: {
    NcDialog,
    NcNoteCard,
    LinkVariantOff,
    FolderOutline,
    CogOutline,
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
    isDisabledByAdmin() {
      return this.shareInfo?.reason === 'nc_disabled';
    },
    filesUrl() {
      const url = this.shareInfo?.filesUrl || '';
      if (!url) return null;
      const [path, query] = url.split('?');
      const base = generateUrl(path);
      return query ? base + '?' + query : base;
    },
    adminSharingUrl() {
      return generateUrl('/settings/admin/sharing');
    }
  },
  methods: {
    t(key, vars = {}) {
      return t('intravox', key, vars);
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

.share-explanation {
  font-size: 14px;
  color: var(--color-main-text);
  margin-bottom: 16px;
  line-height: 1.5;
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
