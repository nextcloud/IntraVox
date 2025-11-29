<template>
  <NcModal @close="$emit('close')"
           :name="t('Create new page')"
           size="small">
    <div class="new-page-modal-content">
      <p class="modal-description">{{ t('Enter a title for the new page') }}</p>
      <input
        ref="titleInput"
        v-model="newPageTitle"
        type="text"
        class="page-title-input"
        :placeholder="t('Page title')"
        @keyup.enter="createPage"
      />

      <div class="modal-buttons">
        <NcButton @click="$emit('close')" type="secondary">
          {{ t('Cancel') }}
        </NcButton>
        <NcButton @click="createPage" type="primary" :disabled="!newPageTitle.trim()">
          {{ t('Create') }}
        </NcButton>
      </div>
    </div>
  </NcModal>
</template>

<script>
import { translate } from '@nextcloud/l10n';
import { NcModal, NcButton } from '@nextcloud/vue';

export default {
  name: 'NewPageModal',
  components: {
    NcModal,
    NcButton
  },
  props: {
    currentPagePath: {
      type: String,
      default: null
    }
  },
  emits: ['close', 'create'],
  data() {
    return {
      newPageTitle: ''
    };
  },
  mounted() {
    // Auto-focus the input field when modal opens
    this.$nextTick(() => {
      this.$refs.titleInput?.focus();
    });
  },
  methods: {
    t(key, vars = {}) {
      return translate('intravox', key, vars);
    },
    createPage() {
      const title = this.newPageTitle.trim();
      if (title) {
        this.$emit('create', { title });
        this.$emit('close');
      }
    }
  }
};
</script>

<style scoped>
.new-page-modal-content {
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.modal-description {
  margin: 0;
  color: var(--color-text-maxcontrast);
  font-size: 14px;
}

.page-title-input {
  width: 100%;
  padding: 10px;
  font-size: 16px;
  color: var(--color-main-text);
  background: var(--color-main-background);
  border: 2px solid var(--color-border-dark);
  border-radius: var(--border-radius-large);
  box-sizing: border-box;
}

.page-title-input:focus {
  outline: none;
  border-color: var(--color-primary);
}

.modal-buttons {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}
</style>
