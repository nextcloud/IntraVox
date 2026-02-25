<template>
  <div v-if="shareInfo !== null" class="share-button-container">
    <!-- Active share: primary colored icon -->
    <NcButton v-if="hasShare"
              type="tertiary"
              :aria-label="t('Public link')"
              :title="t('This page is shared via a public link')"
              class="share-button-active"
              @click="showDialog = true">
      <template #icon>
        <LinkVariant :size="20" />
      </template>
    </NcButton>

    <!-- No share: muted icon -->
    <NcButton v-else
              type="tertiary"
              :aria-label="t('Share this page')"
              :title="t('Share this page')"
              class="share-button-inactive"
              @click="showNoShareDialog = true">
      <template #icon>
        <LinkVariantOff :size="20" />
      </template>
    </NcButton>

    <ShareDialog
      v-if="showDialog"
      :share-info="shareInfo"
      :page-title="pageTitle"
      @close="showDialog = false"
    />

    <NoShareDialog
      v-if="showNoShareDialog"
      :share-info="shareInfo"
      :page-title="pageTitle"
      @close="showNoShareDialog = false"
    />
  </div>
</template>

<script>
import axios from '@nextcloud/axios';
import { generateUrl } from '@nextcloud/router';
import { translate as t } from '@nextcloud/l10n';
import { NcButton } from '@nextcloud/vue';
import LinkVariant from 'vue-material-design-icons/LinkVariant.vue';
import LinkVariantOff from 'vue-material-design-icons/LinkVariantOff.vue';
import ShareDialog from './ShareDialog.vue';
import NoShareDialog from './NoShareDialog.vue';

export default {
  name: 'ShareButton',
  components: {
    NcButton,
    LinkVariant,
    LinkVariantOff,
    ShareDialog,
    NoShareDialog
  },
  props: {
    pageUniqueId: {
      type: String,
      required: true
    },
    pageTitle: {
      type: String,
      default: ''
    },
    language: {
      type: String,
      default: 'en'
    }
  },
  data() {
    return {
      shareInfo: null,
      loading: false,
      showDialog: false,
      showNoShareDialog: false,
      error: null
    };
  },
  computed: {
    hasShare() {
      return this.shareInfo?.hasShare === true;
    }
  },
  watch: {
    pageUniqueId: {
      immediate: true,
      handler(newId) {
        if (newId) {
          this.loadShareInfo();
        }
      }
    }
  },
  methods: {
    t(key, vars = {}) {
      return t('intravox', key, vars);
    },
    async loadShareInfo() {
      if (!this.pageUniqueId) {
        return;
      }

      this.loading = true;
      this.error = null;

      try {
        const response = await axios.get(
          generateUrl('/apps/intravox/api/pages/{uniqueId}/share-info', {
            uniqueId: this.pageUniqueId
          })
        );
        this.shareInfo = response.data;
      } catch (err) {
        this.shareInfo = null;
        this.error = err.message;
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>

<style scoped>
.share-button-container {
  display: inline-flex;
  align-items: center;
}

.share-button-active {
  color: var(--color-primary-element) !important;
}

.share-button-inactive {
  color: var(--color-text-maxcontrast) !important;
}
</style>
