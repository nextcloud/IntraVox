<template>
  <NcModal @close="$emit('close')"
           :name="t('Save as Template')"
           size="small">
    <div class="save-template-modal-content">
      <p class="modal-description">{{ t('Create a reusable template from this page') }}</p>

      <div class="form-group">
        <label for="template-title">{{ t('Template name') }}</label>
        <input
          id="template-title"
          ref="titleInput"
          v-model="templateTitle"
          type="text"
          class="template-input"
          :placeholder="t('Enter template name')"
          @keyup.enter="saveTemplate"
        />
      </div>

      <div class="form-group">
        <label for="template-description">{{ t('Description') }} <span class="optional">{{ t('(optional)') }}</span></label>
        <textarea
          id="template-description"
          v-model="templateDescription"
          class="template-input template-textarea"
          :placeholder="t('Describe what this template is for')"
          rows="3"
        ></textarea>
      </div>

      <div v-if="error" class="error-message">
        {{ error }}
      </div>

      <div class="modal-buttons">
        <NcButton @click="$emit('close')" type="secondary" :disabled="saving">
          {{ t('Cancel') }}
        </NcButton>
        <NcButton @click="saveTemplate" type="primary" :disabled="!canSave || saving">
          <template #icon>
            <NcLoadingIcon v-if="saving" :size="20" />
          </template>
          {{ saving ? t('Saving...') : t('Save Template') }}
        </NcButton>
      </div>
    </div>
  </NcModal>
</template>

<script>
import { translate } from '@nextcloud/l10n';
import { generateUrl } from '@nextcloud/router';
import axios from '@nextcloud/axios';
import { NcModal, NcButton, NcLoadingIcon } from '@nextcloud/vue';

export default {
  name: 'SaveAsTemplateModal',
  components: {
    NcModal,
    NcButton,
    NcLoadingIcon
  },
  props: {
    pageUniqueId: {
      type: String,
      required: true
    },
    pageTitle: {
      type: String,
      default: ''
    }
  },
  emits: ['close', 'saved'],
  data() {
    return {
      templateTitle: '',
      templateDescription: '',
      saving: false,
      error: null
    };
  },
  computed: {
    canSave() {
      return this.templateTitle.trim().length > 0;
    }
  },
  mounted() {
    // Pre-fill with page title + " Template"
    this.templateTitle = this.pageTitle ? `${this.pageTitle} Template` : '';

    // Auto-focus the input field when modal opens
    this.$nextTick(() => {
      this.$refs.titleInput?.focus();
      this.$refs.titleInput?.select();
    });
  },
  methods: {
    t(key, vars = {}) {
      return translate('intravox', key, vars);
    },
    async saveTemplate() {
      if (!this.canSave || this.saving) return;

      this.saving = true;
      this.error = null;

      try {
        const url = generateUrl('/apps/intravox/api/templates');
        const response = await axios.post(url, {
          pageUniqueId: this.pageUniqueId,
          templateTitle: this.templateTitle.trim(),
          templateDescription: this.templateDescription.trim() || null
        });

        if (response.data.success) {
          this.$emit('saved', response.data.template);
          this.$emit('close');
        } else {
          this.error = response.data.error || this.t('Failed to save template');
        }
      } catch (err) {
        console.error('Failed to save template:', err);
        this.error = err.response?.data?.error || this.t('Failed to save template');
      } finally {
        this.saving = false;
      }
    }
  }
};
</script>

<style scoped>
.save-template-modal-content {
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.modal-description {
  margin: 0;
  color: var(--color-text-maxcontrast);
  font-size: 14px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group label {
  font-weight: 500;
  font-size: 14px;
  color: var(--color-main-text);
}

.form-group .optional {
  font-weight: normal;
  color: var(--color-text-maxcontrast);
}

.template-input {
  width: 100%;
  padding: 10px;
  font-size: 14px;
  color: var(--color-main-text);
  background: var(--color-main-background);
  border: 2px solid var(--color-border-dark);
  border-radius: var(--border-radius-large);
  box-sizing: border-box;
}

.template-input:focus {
  outline: none;
  border-color: var(--color-primary);
}

.template-textarea {
  resize: vertical;
  min-height: 60px;
  font-family: inherit;
}

.error-message {
  padding: 10px;
  background: var(--color-error);
  color: white;
  border-radius: var(--border-radius);
  font-size: 14px;
}

.modal-buttons {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 8px;
}
</style>
