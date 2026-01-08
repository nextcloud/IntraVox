<template>
  <NcModal @close="$emit('close')"
           :name="t('Edit links')"
           size="large"
           class="links-editor-modal">
    <div class="links-editor-content">
      <!-- Container Settings -->
      <div class="form-section">
        <h3>{{ t('Container settings') }}</h3>

        <div class="form-group">
          <label>{{ t('Grid columns:') }}</label>
          <select v-model.number="localWidget.columns" class="widget-input">
            <option :value="1">1 {{ t('column') }}</option>
            <option :value="2">2 {{ t('columns') }}</option>
            <option :value="3">3 {{ t('columns') }}</option>
            <option :value="4">4 {{ t('columns') }}</option>
          </select>
        </div>

        <div class="form-group">
          <label>{{ t('Background color:') }}</label>
          <div class="color-presets">
            <button
              type="button"
              :class="{ active: !localWidget.backgroundColor }"
              @click="setBackgroundColor(null)"
              class="color-preset-btn"
            >
              {{ t('None') }}
            </button>
            <button
              type="button"
              :class="{ active: localWidget.backgroundColor === 'var(--color-background-hover)' }"
              @click="setBackgroundColor('var(--color-background-hover)')"
              class="color-preset-btn"
            >
              {{ t('Light') }}
            </button>
            <button
              type="button"
              :class="{ active: localWidget.backgroundColor === 'var(--color-primary-element)' }"
              @click="setBackgroundColor('var(--color-primary-element)')"
              class="color-preset-btn"
            >
              {{ t('Primary') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Links List -->
      <div class="form-section">
        <div class="section-header">
          <h3>{{ t('Links') }}</h3>
          <NcButton @click="addLink" type="primary">
            <template #icon>
              <Plus :size="20" />
            </template>
            {{ t('Add link') }}
          </NcButton>
        </div>

        <draggable
          v-if="localWidget.items && localWidget.items.length > 0"
          v-model="localWidget.items"
          item-key="id"
          handle=".drag-handle"
          class="links-list"
          :animation="200"
        >
          <template #item="{ element: link, index }">
            <div class="link-editor-item">
              <div class="drag-handle" :title="t('Drag to reorder')">
                <DragVertical :size="20" />
              </div>

              <div class="link-fields">
                <div class="form-row">
                  <div class="form-group flex-1">
                    <label>{{ t('Link text:') }}</label>
                    <input
                      v-model="link.text"
                      type="text"
                      :placeholder="t('Click here')"
                      class="widget-input"
                    />
                  </div>

                  <div class="form-group flex-1">
                    <label>{{ t('Link to:') }}</label>
                    <div class="link-target-options">
                      <PageTreeSelect
                        :model-value="link.uniqueId"
                        :placeholder="t('Select page')"
                        :disabled="!!link.url"
                        :clearable="true"
                        @select="updatePageLink(index, $event)"
                        class="page-selector"
                      />
                      <span class="or-separator">{{ t('or') }}</span>
                      <input
                        v-model="link.url"
                        type="url"
                        placeholder="https://example.com"
                        class="widget-input url-input"
                        :disabled="!!link.uniqueId"
                        @input="clearPageLink(index)"
                      />
                    </div>
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group flex-1">
                    <label>{{ t('Icon:') }}</label>
                    <NcSelect
                      v-model="link.iconObject"
                      :options="iconOptions"
                      :placeholder="t('Select an icon')"
                      :clearable="true"
                      @update:modelValue="updateLinkIcon(index, $event)"
                    >
                      <template #selected-option="option">
                        <div class="icon-option">
                          <component :is="getIconComponent(option.value)" :size="20" />
                          <span>{{ option.label }}</span>
                        </div>
                      </template>
                      <template #option="option">
                        <div class="icon-option">
                          <component :is="getIconComponent(option.value)" :size="20" />
                          <span>{{ option.label }}</span>
                        </div>
                      </template>
                    </NcSelect>
                  </div>

                  <div class="form-group flex-1">
                    <label>{{ t('Link background:') }}</label>
                    <div class="color-presets">
                      <button
                        type="button"
                        :class="{ active: !link.backgroundColor }"
                        @click="setLinkBackground(index, null)"
                        class="color-preset-btn small"
                      >
                        {{ t('Default') }}
                      </button>
                      <button
                        type="button"
                        :class="{ active: link.backgroundColor === 'var(--color-primary-element-light)' }"
                        @click="setLinkBackground(index, 'var(--color-primary-element-light)')"
                        class="color-preset-btn small"
                      >
                        {{ t('Primary') }}
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <button
                type="button"
                @click="removeLink(index)"
                class="remove-link-btn"
                :title="t('Remove link')"
              >
                <Delete :size="20" />
              </button>
            </div>
          </template>
        </draggable>

        <div v-else class="empty-state">
          <ViewGrid :size="48" />
          <p>{{ t('No links yet. Click "Add link" to get started.') }}</p>
        </div>
      </div>

      <div class="modal-footer">
        <NcButton @click="$emit('close')" type="secondary">
          {{ t('Cancel') }}
        </NcButton>
        <NcButton @click="save" type="primary">
          {{ t('Save') }}
        </NcButton>
      </div>
    </div>
  </NcModal>
</template>

<script>
import { translate as t } from '@nextcloud/l10n';
import { NcButton, NcModal, NcSelect } from '@nextcloud/vue';
import Plus from 'vue-material-design-icons/Plus.vue';
import Delete from 'vue-material-design-icons/Delete.vue';
import ViewGrid from 'vue-material-design-icons/ViewGrid.vue';
import DragVertical from 'vue-material-design-icons/DragVertical.vue';
import PageTreeSelect from './PageTreeSelect.vue';
import draggable from 'vuedraggable';

// Import all available icons
import Home from 'vue-material-design-icons/Home.vue';
import Information from 'vue-material-design-icons/Information.vue';
import Email from 'vue-material-design-icons/Email.vue';
import Phone from 'vue-material-design-icons/Phone.vue';
import Web from 'vue-material-design-icons/Web.vue';
import FileDocument from 'vue-material-design-icons/FileDocument.vue';
import Download from 'vue-material-design-icons/Download.vue';
import Help from 'vue-material-design-icons/Help.vue';
import Cog from 'vue-material-design-icons/Cog.vue';
import Account from 'vue-material-design-icons/Account.vue';
import AccountGroup from 'vue-material-design-icons/AccountGroup.vue';
import Calendar from 'vue-material-design-icons/Calendar.vue';
import ChartLine from 'vue-material-design-icons/ChartLine.vue';
import Briefcase from 'vue-material-design-icons/Briefcase.vue';
import School from 'vue-material-design-icons/School.vue';
import Star from 'vue-material-design-icons/Star.vue';
import Heart from 'vue-material-design-icons/Heart.vue';
import LightbulbOn from 'vue-material-design-icons/LightbulbOn.vue';
import Rocket from 'vue-material-design-icons/Rocket.vue';
import Archive from 'vue-material-design-icons/Archive.vue';
import OpenInNew from 'vue-material-design-icons/OpenInNew.vue';

export default {
  name: 'LinksEditor',
  components: {
    NcButton,
    NcModal,
    NcSelect,
    Plus,
    Delete,
    ViewGrid,
    DragVertical,
    PageTreeSelect,
    draggable,
    Home,
    Information,
    Email,
    Phone,
    Web,
    FileDocument,
    Download,
    Help,
    Cog,
    Account,
    AccountGroup,
    Calendar,
    ChartLine,
    Briefcase,
    School,
    Star,
    Heart,
    LightbulbOn,
    Rocket,
    Archive,
    OpenInNew
  },
  props: {
    widget: {
      type: Object,
      required: true
    }
  },
  emits: ['close', 'save'],
  data() {
    return {
      localWidget: JSON.parse(JSON.stringify(this.widget))
    };
  },
  computed: {
    iconOptions() {
      return [
        { value: 'home', label: this.t('Home') },
        { value: 'information', label: this.t('Information') },
        { value: 'email', label: this.t('Email') },
        { value: 'phone', label: this.t('Phone') },
        { value: 'web', label: this.t('Website') },
        { value: 'file-document', label: this.t('Document') },
        { value: 'download', label: this.t('Download') },
        { value: 'help', label: this.t('Help') },
        { value: 'cog', label: this.t('Settings') },
        { value: 'account', label: this.t('Account') },
        { value: 'account-group', label: this.t('Team') },
        { value: 'calendar', label: this.t('Calendar') },
        { value: 'chart-line', label: this.t('Analytics') },
        { value: 'briefcase', label: this.t('Business') },
        { value: 'school', label: this.t('Education') },
        { value: 'star', label: this.t('Favorite') },
        { value: 'heart', label: this.t('Like') },
        { value: 'lightbulb-on', label: this.t('Idea') },
        { value: 'rocket', label: this.t('Launch') },
        { value: 'archive', label: this.t('Archive') },
        { value: 'open-in-new', label: this.t('External link') }
      ];
    }
  },
  mounted() {
    // Ensure items array exists
    if (!this.localWidget.items) {
      this.localWidget.items = [];
    }
    // Set default columns if not set
    if (!this.localWidget.columns) {
      this.localWidget.columns = 2;
    }
    // Initialize iconObject for each link from the icon string value
    // Also ensure text field exists (fallback from title for backwards compatibility)
    if (this.localWidget.items) {
      this.localWidget.items.forEach((link, index) => {
        // Ensure each item has an id for drag-and-drop
        if (!link.id) {
          link.id = Date.now() + index + Math.random().toString(36).substr(2, 9);
        }
        if (link.icon && !link.iconObject) {
          link.iconObject = this.iconOptions.find(opt => opt.value === link.icon);
        }
        // Ensure text field exists (use title as fallback for backwards compatibility)
        if (!link.text && link.title) {
          link.text = link.title;
        }
        // Ensure uniqueId field exists (for backwards compatibility with older links)
        if (link.uniqueId === undefined) {
          link.uniqueId = null;
        }
        // Convert legacy #uniqueId URLs to uniqueId field
        if (link.url && link.url.startsWith('#') && !link.uniqueId) {
          link.uniqueId = link.url.substring(1);
          link.url = '';
        }
      });
    }
  },
  methods: {
    t(key, vars = {}) {
      return t('intravox', key, vars);
    },
    getIconComponent(iconName) {
      const iconMap = {
        'home': 'Home',
        'information': 'Information',
        'email': 'Email',
        'phone': 'Phone',
        'web': 'Web',
        'file-document': 'FileDocument',
        'download': 'Download',
        'help': 'Help',
        'cog': 'Cog',
        'account': 'Account',
        'account-group': 'AccountGroup',
        'calendar': 'Calendar',
        'chart-line': 'ChartLine',
        'briefcase': 'Briefcase',
        'school': 'School',
        'star': 'Star',
        'heart': 'Heart',
        'lightbulb-on': 'LightbulbOn',
        'rocket': 'Rocket',
        'archive': 'Archive',
        'open-in-new': 'OpenInNew'
      };
      return iconMap[iconName] || 'OpenInNew';
    },
    addLink() {
      if (!this.localWidget.items) {
        this.localWidget.items = [];
      }
      this.localWidget.items.push({
        id: Date.now() + Math.random().toString(36).substr(2, 9),
        text: '',
        url: '',
        uniqueId: null,
        icon: 'open-in-new',
        iconObject: this.iconOptions.find(opt => opt.value === 'open-in-new'),
        backgroundColor: null
      });
    },
    updatePageLink(index, page) {
      if (page) {
        this.localWidget.items[index].uniqueId = page.uniqueId;
        this.localWidget.items[index].url = '';
        // Auto-fill text if empty
        if (!this.localWidget.items[index].text) {
          this.localWidget.items[index].text = page.title;
        }
      } else {
        this.localWidget.items[index].uniqueId = null;
      }
    },
    clearPageLink(index) {
      // Clear uniqueId when URL is entered
      if (this.localWidget.items[index].url) {
        this.localWidget.items[index].uniqueId = null;
      }
    },
    updateLinkIcon(index, iconObject) {
      // Sync the selected icon object back to the string value
      this.localWidget.items[index].icon = iconObject ? iconObject.value : null;
      this.localWidget.items[index].iconObject = iconObject;
    },
    removeLink(index) {
      this.localWidget.items.splice(index, 1);
    },
    setBackgroundColor(color) {
      this.localWidget.backgroundColor = color;
    },
    setLinkBackground(index, color) {
      this.localWidget.items[index].backgroundColor = color;
    },
    save() {
      // Clean up iconObject property before saving (it's only for NcSelect display)
      const cleanWidget = JSON.parse(JSON.stringify(this.localWidget));
      if (cleanWidget.items) {
        cleanWidget.items.forEach(link => {
          delete link.iconObject;
        });
      }
      this.$emit('save', cleanWidget);
      this.$emit('close');
    }
  }
};
</script>

<style scoped>
.links-editor-modal :deep(.modal-container) {
  max-width: 900px !important;
}

.links-editor-content {
  padding: 20px;
  max-height: 80vh;
  overflow-y: auto;
}

.form-section {
  margin-bottom: 30px;
  padding-bottom: 20px;
  border-bottom: 1px solid var(--color-border);
}

.form-section:last-of-type {
  border-bottom: none;
}

.form-section h3 {
  margin: 0 0 15px 0;
  font-size: 16px;
  font-weight: 600;
  color: var(--color-main-text);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.section-header h3 {
  margin: 0;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
  font-weight: 500;
  font-size: 13px;
  color: var(--color-main-text);
}

.widget-input {
  width: 100%;
  padding: 8px 12px;
  border: 2px solid var(--color-border-dark);
  border-radius: var(--border-radius-large);
  background: var(--color-main-background);
  color: var(--color-main-text);
  font-size: 14px;
}

.widget-input:focus {
  outline: none;
  border-color: var(--color-primary-element);
}

.color-presets {
  display: flex;
  gap: 8px;
}

.color-preset-btn {
  flex: 1;
  padding: 8px 12px;
  border: 2px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-main-background);
  color: var(--color-main-text);
  cursor: pointer;
  font-size: 13px;
  transition: all 0.2s;
}

.color-preset-btn.small {
  padding: 6px 12px;
  font-size: 12px;
}

.color-preset-btn:hover {
  border-color: var(--color-primary-element-light);
}

.color-preset-btn.active {
  border-color: var(--color-primary);
  background: var(--color-primary-element-light);
}

.links-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.link-editor-item {
  display: flex;
  gap: 15px;
  align-items: flex-start;
  padding: 15px;
  background: var(--color-background-hover);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius-large);
}

.drag-handle {
  flex-shrink: 0;
  cursor: grab;
  padding: 4px;
  color: var(--color-text-maxcontrast);
  border-radius: var(--border-radius);
  transition: all 0.2s;
}

.drag-handle:hover {
  background: var(--color-background-dark);
  color: var(--color-main-text);
}

.drag-handle:active {
  cursor: grabbing;
}

/* Sortable ghost styling (during drag) */
.sortable-ghost {
  opacity: 0.5;
  background: var(--color-primary-element-light);
}

.sortable-drag {
  opacity: 1;
}

.link-fields {
  flex: 1;
}

.form-row {
  display: flex;
  gap: 15px;
  margin-bottom: 10px;
}

.form-row:last-child {
  margin-bottom: 0;
}

.flex-1 {
  flex: 1;
}

.remove-link-btn {
  flex-shrink: 0;
  padding: 8px;
  background: var(--color-error-hover, rgba(229, 57, 53, 0.1));
  border: 1px solid var(--color-error);
  color: var(--color-error);
  cursor: pointer;
  border-radius: var(--border-radius);
  transition: all 0.2s;
}

.remove-link-btn:hover {
  background: var(--color-error);
  color: white;
}

.icon-option {
  display: flex;
  align-items: center;
  gap: 8px;
}

.link-target-options {
  display: flex;
  align-items: center;
  gap: 8px;
}

.or-separator {
  color: var(--color-text-lighter);
  font-size: 12px;
  text-transform: uppercase;
  flex-shrink: 0;
}

.page-selector {
  flex: 2;
  min-width: 250px;
}

.url-input {
  flex: 1;
  min-width: 180px;
}

.url-input:disabled,
.page-selector.is-disabled {
  opacity: 0.5;
  pointer-events: none;
}

.empty-state {
  text-align: center;
  padding: 40px 20px;
  color: var(--color-text-maxcontrast);
}

.empty-state svg {
  margin-bottom: 10px;
  color: var(--color-text-maxcontrast);
}

.empty-state p {
  margin: 0;
  font-size: 14px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding-top: 20px;
  border-top: 1px solid var(--color-border);
  margin-top: 20px;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
  .form-row {
    flex-direction: column;
    gap: 10px;
  }

  .link-editor-item {
    flex-direction: column;
  }

  .remove-link-btn {
    align-self: flex-end;
  }
}
</style>
