<template>
  <footer class="intravox-footer">
    <div class="footer-wrapper">
      <!-- Edit Footer Button -->
      <div v-if="canEdit && isHomePage && !isEditingFooter" class="footer-actions">
        <button @click="startEditFooter" class="edit-footer-btn" :title="t('Edit footer')">
          <Pencil :size="16" />
          {{ t('Edit Footer') }}
        </button>
      </div>

      <!-- Footer Editor or Content -->
      <div class="footer-content-wrapper">
        <InlineTextEditor
          v-if="isEditingFooter"
          v-model="editableContent"
          :editable="true"
          :placeholder="t('Enter footer content...')"
          class="footer-editor"
        />
        <div v-else class="footer-content" v-html="footerContent"></div>
      </div>

      <!-- Save/Cancel Buttons when editing -->
      <div v-if="isEditingFooter" class="footer-edit-actions">
        <button @click="cancelEditFooter" class="footer-action-btn secondary">
          <Close :size="16" />
          {{ t('Cancel') }}
        </button>
        <button @click="saveFooter" class="footer-action-btn primary">
          <ContentSave :size="16" />
          {{ t('Save') }}
        </button>
      </div>
    </div>
  </footer>
</template>

<script>
import { translate as t } from '@nextcloud/l10n';
import { showSuccess, showError } from '@nextcloud/dialogs';
import axios from '@nextcloud/axios';
import { generateUrl } from '@nextcloud/router';
import Pencil from 'vue-material-design-icons/Pencil.vue';
import Close from 'vue-material-design-icons/Close.vue';
import ContentSave from 'vue-material-design-icons/ContentSave.vue';
import InlineTextEditor from './InlineTextEditor.vue';

export default {
  name: 'Footer',
  components: {
    Pencil,
    Close,
    ContentSave,
    InlineTextEditor
  },
  props: {
    footerContent: {
      type: String,
      default: ''
    },
    canEdit: {
      type: Boolean,
      default: false
    },
    isHomePage: {
      type: Boolean,
      default: false
    }
  },
  emits: ['save'],
  data() {
    return {
      isEditingFooter: false,
      editableContent: '',
      originalContent: ''
    };
  },
  methods: {
    t(key, vars = {}) {
      return t('intravox', key, vars);
    },
    startEditFooter() {
      this.originalContent = this.footerContent;
      this.editableContent = this.footerContent;
      this.isEditingFooter = true;
    },
    cancelEditFooter() {
      this.isEditingFooter = false;
      this.editableContent = '';
      this.originalContent = '';
      showSuccess(this.t('Changes cancelled'));
    },
    async saveFooter() {
      try {
        await axios.post(generateUrl('/apps/intravox/api/footer'), {
          content: this.editableContent
        });
        this.$emit('save', this.editableContent);
        this.isEditingFooter = false;
        this.originalContent = '';
        showSuccess(this.t('Footer saved'));
      } catch (err) {
        showError(this.t('Could not save footer: {error}', { error: err.message }));
      }
    }
  }
};
</script>

<style scoped>
.intravox-footer {
  width: 100%;
  background: var(--color-main-background);
  border-top: 1px solid var(--color-border);
  margin-top: 40px;
}

.footer-wrapper {
  max-width: min(1600px, 95vw);
  margin: 0 auto;
  padding: 20px;
}

.footer-actions {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 12px;
}

.edit-footer-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  background: var(--color-primary-element);
  color: var(--color-primary-element-text);
  border: none;
  border-radius: var(--border-radius);
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: background-color 0.2s;
}

.edit-footer-btn:hover {
  background: var(--color-primary-element-hover);
}

.footer-content-wrapper {
  width: 100%;
}

.footer-content {
  padding: 16px;
  color: var(--color-text-maxcontrast);
  font-size: 14px;
  line-height: 1.6;
}

.footer-content :deep(p) {
  margin: 0.5em 0;
}

.footer-content :deep(a) {
  color: var(--color-primary);
  text-decoration: none;
}

.footer-content :deep(a:hover) {
  text-decoration: underline;
}

.footer-editor {
  min-height: 100px;
  background: var(--color-main-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  padding: 12px;
}

.footer-edit-actions {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
  margin-top: 12px;
}

.footer-action-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border: none;
  border-radius: var(--border-radius);
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: background-color 0.2s;
}

.footer-action-btn.primary {
  background: var(--color-primary-element);
  color: var(--color-primary-element-text);
}

.footer-action-btn.primary:hover {
  background: var(--color-primary-element-hover);
}

.footer-action-btn.secondary {
  background: var(--color-background-dark);
  color: var(--color-main-text);
}

.footer-action-btn.secondary:hover {
  background: var(--color-background-darker);
}
</style>
