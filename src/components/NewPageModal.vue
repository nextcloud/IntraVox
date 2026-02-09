<template>
  <NcModal @close="$emit('close')"
           :name="t('Create new page')"
           size="large">
    <div class="new-page-modal-content">
      <!-- Mode Tabs -->
      <div class="mode-tabs">
        <button
          class="mode-tab"
          :class="{ active: mode === 'blank' }"
          @click="mode = 'blank'"
        >
          <FileOutline :size="18" />
          {{ t('Blank Page') }}
        </button>
        <button
          class="mode-tab"
          :class="{ active: mode === 'template' }"
          @click="mode = 'template'; loadTemplates()"
        >
          <FileDocumentMultipleOutline :size="18" />
          {{ t('From Template') }}
        </button>
      </div>

      <!-- Blank Page Mode -->
      <div v-if="mode === 'blank'" class="mode-content">
        <p class="modal-description">{{ t('Enter a title for the new page') }}</p>
        <input
          ref="titleInput"
          v-model="newPageTitle"
          type="text"
          class="page-title-input"
          :placeholder="t('Page title')"
          @keyup.enter="createPage"
        />
      </div>

      <!-- Template Mode -->
      <div v-else class="mode-content">
        <!-- Loading state -->
        <div v-if="loadingTemplates" class="templates-loading">
          <NcLoadingIcon :size="32" />
          <span>{{ t('Loading templates...') }}</span>
        </div>

        <!-- No templates -->
        <div v-else-if="templates.length === 0" class="templates-empty">
          <FileDocumentMultipleOutline :size="48" />
          <p>{{ t('No templates available') }}</p>
          <small>{{ t('Create templates by saving existing pages as templates') }}</small>
        </div>

        <!-- Template Gallery -->
        <div v-else class="template-gallery">
          <TemplatePreviewCard
            v-for="template in templates"
            :key="template.id"
            :template="template"
            :selected="selectedTemplate === template.id"
            @select="selectTemplate(template)"
          />
        </div>

        <!-- Page title input (when template selected) -->
        <div v-if="selectedTemplate && templates.length > 0" class="template-title-section">
          <label>{{ t('Page title') }}</label>
          <input
            ref="templateTitleInput"
            v-model="newPageTitle"
            type="text"
            class="page-title-input"
            :placeholder="t('Enter page title')"
            @keyup.enter="createPage"
          />
        </div>
      </div>

      <div class="modal-buttons">
        <NcButton @click="$emit('close')" type="secondary">
          {{ t('Cancel') }}
        </NcButton>
        <NcButton
          @click="createPage"
          type="primary"
          :disabled="!canCreate"
        >
          {{ t('Create') }}
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
import FileOutline from 'vue-material-design-icons/FileOutline.vue';
import FileDocumentMultipleOutline from 'vue-material-design-icons/FileDocumentMultipleOutline.vue';
import TemplatePreviewCard from './TemplatePreviewCard.vue';

export default {
  name: 'NewPageModal',
  components: {
    NcModal,
    NcButton,
    NcLoadingIcon,
    FileOutline,
    FileDocumentMultipleOutline,
    TemplatePreviewCard
  },
  props: {
    currentPagePath: {
      type: String,
      default: null
    }
  },
  emits: ['close', 'create', 'create-from-template'],
  data() {
    return {
      mode: 'blank',
      newPageTitle: '',
      templates: [],
      selectedTemplate: null,
      loadingTemplates: false
    };
  },
  computed: {
    canCreate() {
      if (this.mode === 'blank') {
        return this.newPageTitle.trim().length > 0;
      }
      return this.selectedTemplate && this.newPageTitle.trim().length > 0;
    }
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
    async loadTemplates() {
      if (this.templates.length > 0) return; // Already loaded

      this.loadingTemplates = true;
      try {
        const url = generateUrl('/apps/intravox/api/templates');
        const response = await axios.get(url);
        this.templates = response.data.templates || [];
      } catch (err) {
        console.error('Failed to load templates:', err);
        this.templates = [];
      } finally {
        this.loadingTemplates = false;
      }
    },
    selectTemplate(template) {
      this.selectedTemplate = template.id;
      // Pre-fill title based on template title (without "Template" suffix)
      if (!this.newPageTitle) {
        const templateTitle = template.title || '';
        this.newPageTitle = templateTitle.replace(/\s*Template\s*$/i, '');
      }
      // Focus title input
      this.$nextTick(() => {
        this.$refs.templateTitleInput?.focus();
      });
    },
    createPage() {
      const title = this.newPageTitle.trim();
      if (!title) return;

      if (this.mode === 'blank') {
        this.$emit('create', { title });
      } else if (this.selectedTemplate) {
        this.$emit('create-from-template', {
          templateId: this.selectedTemplate,
          title
        });
      }
      this.$emit('close');
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

.mode-tabs {
  display: flex;
  gap: 8px;
  border-bottom: 1px solid var(--color-border);
  padding-bottom: 12px;
}

.mode-tab {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  border: none;
  background: transparent;
  color: var(--color-text-maxcontrast);
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  border-radius: var(--border-radius);
  transition: background 0.2s, color 0.2s;
}

.mode-tab:hover {
  background: var(--color-background-hover);
  color: var(--color-main-text);
}

.mode-tab.active {
  background: var(--color-primary-element-light);
  color: var(--color-primary-element);
}

.mode-content {
  min-height: 150px;
}

.modal-description {
  margin: 0 0 12px 0;
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

.templates-loading,
.templates-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 40px 20px;
  color: var(--color-text-maxcontrast);
  text-align: center;
}

.templates-empty p {
  margin: 0;
  font-size: 16px;
  color: var(--color-main-text);
}

.templates-empty small {
  font-size: 13px;
}

.template-gallery {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 16px;
  max-height: 450px;
  overflow-y: auto;
  padding: 4px;
}

.template-title-section {
  margin-top: 16px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.template-title-section label {
  font-size: 14px;
  font-weight: 500;
  color: var(--color-main-text);
}

.modal-buttons {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}
</style>
