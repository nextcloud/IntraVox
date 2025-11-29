<template>
  <NcModal @close="$emit('close')"
           :name="t('Edit Navigation')"
           size="large"
           class="navigation-editor-modal">
    <div class="navigation-editor">
      <!-- Hidden element to capture autofocus -->
      <input type="text" style="position: absolute; opacity: 0; pointer-events: none;" autofocus />

      <!-- Actions at top with type selector on left -->
      <div class="modal-actions-top">
        <div class="type-selector-left">
          <label>{{ t('Navigation Type:') }}</label>
          <NcSelect v-model="selectedType"
                    :options="typeOptions"
                    :placeholder="t('Select navigation type')"
                    :clearable="false"
                    @update:modelValue="updateType" />
        </div>
        <div class="action-buttons">
          <NcButton @click="$emit('close')" type="secondary">
            {{ t('Cancel') }}
          </NcButton>
          <NcButton @click="save" type="primary">
            <template #icon>
              <ContentSave :size="20" />
            </template>
            {{ t('Save Navigation') }}
          </NcButton>
        </div>
      </div>

      <!-- Navigation Items -->
      <div class="editor-section">
        <div class="section-header">
          <h3>{{ t('Navigation Items') }}</h3>
          <NcButton @click="addTopLevelItem" type="primary">
            <template #icon>
              <Plus :size="20" />
            </template>
            {{ t('Add Item') }}
          </NcButton>
        </div>

        <div v-if="localNavigation.items.length === 0" class="empty-state">
          {{ t('No navigation items yet. Click "Add Item" to create one.') }}
        </div>

        <!-- Drag and drop list -->
        <draggable v-model="localNavigation.items"
                   item-key="id"
                   handle=".drag-handle"
                   group="navigation-items"
                   class="navigation-list">
          <template #item="{element, index}">
            <NavigationItem :item="element"
                           :index="index"
                           :level="1"
                           :pages="pages"
                           @update="updateItem"
                           @delete="deleteItem"
                           @add-child="addChildItem" />
          </template>
        </draggable>
      </div>
    </div>
  </NcModal>
</template>

<script>
import { translate as t } from '@nextcloud/l10n';
import { NcModal, NcButton, NcSelect } from '@nextcloud/vue';
import draggable from 'vuedraggable';
import Plus from 'vue-material-design-icons/Plus.vue';
import ContentSave from 'vue-material-design-icons/ContentSave.vue';
import NavigationItem from './NavigationItem.vue';

export default {
  name: 'NavigationEditor',
  components: {
    NcModal,
    NcButton,
    NcSelect,
    draggable,
    Plus,
    ContentSave,
    NavigationItem
  },
  props: {
    navigation: {
      type: Object,
      required: true
    },
    pages: {
      type: Array,
      default: () => []
    }
  },
  emits: ['close', 'save'],
  data() {
    return {
      localNavigation: JSON.parse(JSON.stringify(this.navigation)),
      typeOptions: [
        { value: 'dropdown', label: t('intravox', 'Dropdown (Cascading)') },
        { value: 'megamenu', label: t('intravox', 'Mega Menu') }
      ]
    };
  },
  computed: {
    selectedType() {
      const type = this.localNavigation.type || 'dropdown';
      return this.typeOptions.find(opt => opt.value === type);
    }
  },
  methods: {
    t(key, vars = {}) {
      return t('intravox', key, vars);
    },
    updateType(option) {
      if (option) {
        this.localNavigation.type = option.value;
      }
    },
    addTopLevelItem() {
      this.localNavigation.items.push({
        id: `nav_${Date.now()}`,
        title: t('intravox', 'New Item'),
        uniqueId: null,
        url: null,
        children: []
      });
    },
    addChildItem(parentPath) {
      const parent = this.getItemByPath(parentPath);
      if (parent && parent.children.length < 10) {
        parent.children.push({
          id: `nav_${Date.now()}`,
          title: t('intravox', 'New Item'),
          uniqueId: null,
          url: null,
          children: []
        });
      }
    },
    updateItem(path, updates) {
      const item = this.getItemByPath(path);
      if (item) {
        Object.assign(item, updates);
      }
    },
    deleteItem(path) {
      const pathParts = path.split('.');
      if (pathParts.length === 1) {
        // Top level
        const index = parseInt(pathParts[0]);
        this.localNavigation.items.splice(index, 1);
      } else {
        // Nested
        const parentPath = pathParts.slice(0, -1).join('.');
        const parent = this.getItemByPath(parentPath);
        const childIndex = parseInt(pathParts[pathParts.length - 1]);
        if (parent && parent.children) {
          parent.children.splice(childIndex, 1);
        }
      }
    },
    getItemByPath(path) {
      const parts = path.split('.').map(p => parseInt(p));
      let current = this.localNavigation.items[parts[0]];

      for (let i = 1; i < parts.length; i++) {
        if (!current || !current.children) return null;
        current = current.children[parts[i]];
      }

      return current;
    },
    save() {
      this.$emit('save', this.localNavigation);
    }
  }
};
</script>

<style scoped>
.navigation-editor {
  padding: 16px;
  max-height: 70vh;
  overflow-y: auto;
}

.editor-section {
  margin-bottom: 12px;
}

.editor-section label {
  display: block;
  font-weight: 600;
  margin-bottom: 6px;
  color: var(--color-main-text);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.section-header h3 {
  margin: 0;
  font-size: 15px;
  font-weight: 600;
}

.empty-state {
  padding: 40px;
  text-align: center;
  color: var(--color-text-maxcontrast);
  background: var(--color-background-dark);
  border-radius: var(--border-radius);
  border: 2px dashed var(--color-border);
}

.navigation-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.modal-actions-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 16px;
  border-bottom: 1px solid var(--color-border);
  margin-bottom: 16px;
}

.type-selector-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.type-selector-left label {
  font-weight: 600;
  color: var(--color-main-text);
  white-space: nowrap;
}

.action-buttons {
  display: flex;
  gap: 8px;
}
</style>
