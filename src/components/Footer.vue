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
          <div v-else class="footer-content" v-html="footerHtml"></div>
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
import { showSuccess } from '@nextcloud/dialogs';
import { NcButton, NcActions, NcActionButton } from '@nextcloud/vue';
import Pencil from 'vue-material-design-icons/Pencil.vue';
import Close from 'vue-material-design-icons/Close.vue';
import ContentSave from 'vue-material-design-icons/ContentSave.vue';
import InlineTextEditor from './InlineTextEditor.vue';
import { markdownToHtml } from '../utils/markdownSerializer.js';

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
  emits: ['save', 'navigate'],
  data() {
    return {
      isEditingFooter: false,
      editableContent: '',
      originalContent: ''
    };
  },
  mounted() {
    this.attachLinkHandlers();
  },
  updated() {
    // Reattach handlers when content changes
    if (!this.isEditingFooter) {
      this.$nextTick(() => {
        this.attachLinkHandlers();
      });
    }
  },
  computed: {
    footerHtml() {
      // Convert markdown to HTML for display
      return markdownToHtml(this.footerContent || '');
    }
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
    saveFooter() {
      // Emit save event to parent - parent will handle API call and notifications
      this.$emit('save', this.editableContent);
      this.isEditingFooter = false;
      this.originalContent = '';
    },
    attachLinkHandlers() {
      // Get all links in the footer content
      const footerEl = this.$el?.querySelector('.footer-content');
      if (!footerEl) return;

      const links = footerEl.querySelectorAll('a');
      links.forEach(link => {
        const href = link.getAttribute('href');

        // Only handle internal navigation links (those starting with #)
        if (href && href.startsWith('#') && href.length > 1) {
          // Remove any existing click handler
          const oldHandler = link._clickHandler;
          if (oldHandler) {
            link.removeEventListener('click', oldHandler);
          }

          // Create and store new handler
          const clickHandler = (e) => {
            e.preventDefault();
            // Remove the # and emit the pageId
            const pageId = href.substring(1);
            this.$emit('navigate', pageId);
          };

          link._clickHandler = clickHandler;
          link.addEventListener('click', clickHandler);
        }
      });
    }
  }
};
</script>

<style scoped>
.intravox-footer {
  width: 100%;
  max-width: 100vw;
  background: var(--color-main-background);
  box-sizing: border-box;
  overflow-x: hidden;
}

.footer-wrapper {
  max-width: min(1600px, 95vw);
  margin: 0 auto;
  padding: 0 20px 60px 20px;
  box-sizing: border-box;
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
  color: inherit !important;
}

.footer-content :deep(a) {
  color: var(--color-primary);
  text-decoration: none;
}

.footer-content :deep(a:hover) {
  text-decoration: underline;
}

.footer-content :deep(strong),
.footer-content :deep(em),
.footer-content :deep(u),
.footer-content :deep(s),
.footer-content :deep(ul),
.footer-content :deep(ol),
.footer-content :deep(li) {
  color: inherit !important;
}

.footer-editor {
  min-height: 100px;
  background: var(--color-main-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  padding: 12px;
}

/* Mobile styles */
@media (max-width: 768px) {
  .footer-wrapper {
    padding: 0 8px 40px 8px;
    max-width: 100%;
  }

  .footer-content-and-actions {
    flex-direction: column;
    gap: 12px;
  }

  .footer-actions,
  .footer-edit-actions {
    width: 100%;
    justify-content: flex-start;
  }

  .footer-content {
    padding: 12px 8px;
    font-size: 13px;
  }
}
</style>
