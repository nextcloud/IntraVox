<template>
  <div class="navigation-item" :class="`level-${level}`">
    <div class="item-content">
      <!-- Drag Handle -->
      <div class="drag-handle">
        <DragVertical :size="20" />
      </div>

      <!-- Item Details -->
      <div class="item-details">
        <input v-model="localItem.title"
               type="text"
               class="item-title-input"
               :placeholder="t('Item title')"
               @input="emitUpdate" />

        <div class="item-link-options">
          <!-- Link to Page (disabled when URL is set) -->
          <PageTreeSelect
            :model-value="localItem.uniqueId"
            :placeholder="t('Link to page')"
            :disabled="!!localItem.url"
            @select="updatePageLink"
            class="link-selector"
            :class="{ 'is-disabled': !!localItem.url }" />

          <!-- OR Custom URL -->
          <span class="or-separator">{{ t('or') }}</span>

          <div class="url-section">
            <input v-model="localItem.url"
                   type="url"
                   class="url-input"
                   :class="{ 'has-value': !!localItem.url }"
                   :placeholder="t('Custom URL')"
                   @input="onUrlInput" />

            <!-- Clear URL button -->
            <button v-if="localItem.url"
                    type="button"
                    class="clear-url-btn"
                    @click="clearUrl"
                    :aria-label="t('Clear URL')">
              <Close :size="16" />
            </button>

            <!-- Target selector (for URLs) -->
            <NcSelect v-if="localItem.url"
                      :model-value="selectedTarget"
                      :options="targetOptions"
                      @update:modelValue="updateTarget"
                      class="target-selector" />
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="item-actions">
        <!-- Promote: move item up one level (to parent's level) -->
        <NcButton v-if="canPromote"
                  @click="$emit('promote', itemPath)"
                  type="tertiary"
                  :aria-label="t('Promote (move up one level)')">
          <template #icon>
            <ChevronLeft :size="20" />
          </template>
        </NcButton>

        <!-- Demote: make item a child of the item above -->
        <NcButton v-if="canDemote"
                  @click="$emit('demote', itemPath)"
                  type="tertiary"
                  :aria-label="t('Demote (make child of item above)')">
          <template #icon>
            <ChevronRight :size="20" />
          </template>
        </NcButton>

        <NcButton v-if="level < 3"
                  @click="$emit('add-child', itemPath)"
                  type="tertiary"
                  :aria-label="t('Add sub-item')">
          <template #icon>
            <Plus :size="20" />
          </template>
        </NcButton>

        <NcButton @click="$emit('delete', itemPath)"
                  type="error"
                  :aria-label="t('Delete item')">
          <template #icon>
            <Delete :size="20" />
          </template>
        </NcButton>
      </div>
    </div>

    <!-- Children (recursive) -->
    <draggable v-if="localItem.children && localItem.children.length > 0"
               v-model="localItem.children"
               item-key="id"
               handle=".drag-handle"
               group="navigation-items"
               @change="onChildrenChange"
               class="children-list">
      <template #item="{element, index}">
        <NavigationItem :item="element"
                       :index="index"
                       :level="level + 1"
                       :parent-path="itemPath"
                       :is-first="index === 0"
                       :sibling-count="localItem.children.length"
                       @update="(path, updates) => $emit('update', path, updates)"
                       @delete="(path) => $emit('delete', path)"
                       @add-child="(path) => $emit('add-child', path)"
                       @promote="(path) => $emit('promote', path)"
                       @demote="(path) => $emit('demote', path)" />
      </template>
    </draggable>
  </div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n';
import { NcButton, NcSelect } from '@nextcloud/vue';
import draggable from 'vuedraggable';
import DragVertical from 'vue-material-design-icons/DragVertical.vue';
import Plus from 'vue-material-design-icons/Plus.vue';
import Delete from 'vue-material-design-icons/Delete.vue';
import Close from 'vue-material-design-icons/Close.vue';
import ChevronLeft from 'vue-material-design-icons/ChevronLeft.vue';
import ChevronRight from 'vue-material-design-icons/ChevronRight.vue';
import PageTreeSelect from './PageTreeSelect.vue';

export default {
  name: 'NavigationItem',
  components: {
    NcButton,
    NcSelect,
    draggable,
    DragVertical,
    Plus,
    Delete,
    Close,
    ChevronLeft,
    ChevronRight,
    PageTreeSelect
  },
  props: {
    item: {
      type: Object,
      required: true
    },
    index: {
      type: Number,
      required: true
    },
    level: {
      type: Number,
      default: 1
    },
    parentPath: {
      type: String,
      default: ''
    },
    isFirst: {
      type: Boolean,
      default: false
    },
    siblingCount: {
      type: Number,
      default: 1
    }
  },
  emits: ['update', 'delete', 'add-child', 'promote', 'demote'],
  data() {
    return {
      localItem: JSON.parse(JSON.stringify(this.item)),
      targetOptions: [
        { value: '_self', label: t('intravox', 'Same tab') },
        { value: '_blank', label: t('intravox', 'New tab') }
      ]
    };
  },
  computed: {
    itemPath() {
      return this.parentPath
        ? `${this.parentPath}.${this.index}`
        : `${this.index}`;
    },
    selectedTarget() {
      const target = this.localItem.target || '_self';
      return this.targetOptions.find(opt => opt.value === target);
    },
    canPromote() {
      // Can promote if not at top level (level > 1)
      return this.level > 1;
    },
    canDemote() {
      // Can demote if:
      // - Not the first item in the list (needs a sibling above to become parent)
      // - Current level < 3 (max depth)
      return !this.isFirst && this.level < 3;
    }
  },
  watch: {
    item: {
      deep: true,
      handler(newItem) {
        this.localItem = JSON.parse(JSON.stringify(newItem));
      }
    }
  },
  methods: {
    t(key, vars = {}) {
      return t('intravox', key, vars);
    },
    emitUpdate() {
      this.$emit('update', this.itemPath, this.localItem);
    },
    updatePageLink(page) {
      if (page) {
        this.localItem.uniqueId = page.uniqueId;
        this.localItem.url = null;

        // Auto-fill title with page name if current title is empty or "New Item"
        const currentTitle = this.localItem.title?.trim() || '';
        const isNewItem = currentTitle === '' || currentTitle === this.t('New Item');
        if (isNewItem) {
          this.localItem.title = page.title;
        }
      } else {
        this.localItem.uniqueId = null;
      }
      this.emitUpdate();
    },
    updateTarget(option) {
      if (option) {
        this.localItem.target = option.value;
      } else {
        this.localItem.target = '_self';
      }
      this.emitUpdate();
    },
    onUrlInput() {
      // When URL is entered, clear the page selection
      if (this.localItem.url && this.localItem.url.trim()) {
        this.localItem.uniqueId = null;
      }
      this.emitUpdate();
    },
    clearUrl() {
      this.localItem.url = null;
      this.localItem.target = '_self';
      this.emitUpdate();
    },
    onChildrenChange() {
      // When children are reordered, emit update for this item
      this.emitUpdate();
    }
  }
};
</script>

<style scoped>
.navigation-item {
  background: var(--color-main-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  padding: 8px;
}

.navigation-item.level-2,
.navigation-item.level-3 {
  margin-left: 24px;
  margin-top: 6px;
}

.item-content {
  display: flex;
  align-items: flex-start;
  gap: 8px;
}

.drag-handle {
  cursor: grab;
  color: var(--color-text-maxcontrast);
  padding: 2px;
  flex-shrink: 0;
  margin-top: 2px;
}

.drag-handle:active {
  cursor: grabbing;
}

.item-details {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.item-title-input {
  width: 100%;
  padding: 6px 10px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  font-size: 13px;
  font-weight: 500;
}

.item-link-options {
  display: flex;
  align-items: center;
  gap: 6px;
}

.link-selector {
  flex: 1;
  min-width: 180px;
}

.link-selector.is-disabled {
  opacity: 0.5;
  pointer-events: none;
}

.or-separator {
  color: var(--color-text-maxcontrast);
  font-size: 11px;
  text-transform: uppercase;
}

.url-section {
  display: flex;
  align-items: center;
  gap: 6px;
  flex: 1;
}

.url-input {
  flex: 1;
  padding: 6px 10px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  font-size: 13px;
}

.url-input.has-value {
  border-color: var(--color-primary-element);
  background: var(--color-primary-element-light);
}

.clear-url-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  padding: 0;
  border: none;
  background: var(--color-background-dark);
  border-radius: var(--border-radius);
  cursor: pointer;
  color: var(--color-text-maxcontrast);
  flex-shrink: 0;
}

.clear-url-btn:hover {
  background: var(--color-error);
  color: white;
}

.target-selector {
  min-width: 120px;
  flex-shrink: 0;
}

.item-actions {
  display: flex;
  gap: 2px;
  flex-shrink: 0;
  margin-top: 2px;
}

.children-list {
  margin-top: 6px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
</style>
