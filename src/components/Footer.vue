<template>
  <footer class="intravox-footer">
    <div class="footer-wrapper">
      <div class="footer-content-and-actions">
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

        <!-- 3-dot Action Menu (shown when not editing) -->
        <div v-if="canEdit && isHomePage && !isEditingFooter" class="footer-actions">
          <NcActions>
            <NcActionButton @click="startEditFooter">
              <template #icon>
                <Pencil :size="20" />
              </template>
              {{ t('Edit footer') }}
            </NcActionButton>
          </NcActions>
        </div>

        <!-- Save/Cancel Buttons (shown when editing) -->
        <div v-if="isEditingFooter" class="footer-edit-actions">
          <NcButton
            @click="cancelEditFooter"
            :aria-label="t('Cancel')"
          >
            <template #icon>
              <Close :size="20" />
            </template>
            {{ t('Cancel') }}
          </NcButton>
          <NcButton
            type="primary"
            @click="saveFooter"
            :aria-label="t('Save')"
          >
            <template #icon>
              <ContentSave :size="20" />
            </template>
            {{ t('Save') }}
          </NcButton>
        </div>
      </div>
    </div>
  </footer>
</template>

<script>
import { translate as t } from '@nextcloud/l10n';
import { showSuccess, showError } from '@nextcloud/dialogs';
import axios from '@nextcloud/axios';
import { generateUrl } from '@nextcloud/router';
import { NcButton, NcActions, NcActionButton } from '@nextcloud/vue';
import Pencil from 'vue-material-design-icons/Pencil.vue';
import Close from 'vue-material-design-icons/Close.vue';
import ContentSave from 'vue-material-design-icons/ContentSave.vue';
import InlineTextEditor from './InlineTextEditor.vue';

export default {
  name: 'Footer',
  components: {
    NcButton,
    NcActions,
    NcActionButton,
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
}

.footer-wrapper {
  max-width: min(1600px, 95vw);
  margin: 0 auto;
  padding: 0 20px 60px 20px;
}

.footer-content-and-actions {
  display: flex;
  align-items: flex-start;
  gap: 20px;
}

.footer-content-wrapper {
  flex: 1;
}

.footer-content {
  padding: 16px;
  color: var(--color-text-maxcontrast);
  font-size: 14px;
  line-height: 1.6;
  text-align: center;
}

.footer-actions {
  flex-shrink: 0;
  align-self: flex-start;
}

.footer-edit-actions {
  display: flex;
  gap: 12px;
  flex-shrink: 0;
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
</style>
