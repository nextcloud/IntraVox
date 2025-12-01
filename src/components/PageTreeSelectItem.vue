<template>
  <li class="tree-select-item">
    <div
      class="item-row"
      :class="{
        'is-selected': isSelected,
        'is-focused': isFocused,
        'matches-search': item.matchesSearch
      }"
      @mouseenter="$emit('focus', item.uniqueId)"
    >
      <!-- Expand/collapse toggle -->
      <button
        v-if="hasChildren"
        class="expand-toggle"
        @click.stop="$emit('toggle', item.uniqueId)"
        :aria-label="isExpanded ? 'Collapse' : 'Expand'"
      >
        <ChevronRight v-if="!isExpanded" :size="16" />
        <ChevronDown v-else :size="16" />
      </button>
      <span v-else class="toggle-spacer"></span>

      <!-- Page item -->
      <button class="item-content" @click="$emit('select', item)">
        <FolderOutline v-if="hasChildren" :size="16" class="item-icon folder-icon" />
        <FileDocument v-else :size="16" class="item-icon" />
        <span class="item-title">{{ item.title }}</span>
        <Check v-if="isSelected" :size="16" class="check-icon" />
      </button>
    </div>

    <!-- Children -->
    <ul v-if="hasChildren && isExpanded" class="children-list">
      <PageTreeSelectItem
        v-for="child in item.children"
        :key="child.uniqueId"
        :item="child"
        :expanded-nodes="expandedNodes"
        :selected-id="selectedId"
        :focused-id="focusedId"
        :search-query="searchQuery"
        @toggle="(id) => $emit('toggle', id)"
        @select="(page) => $emit('select', page)"
        @focus="(id) => $emit('focus', id)"
      />
    </ul>
  </li>
</template>

<script>
import ChevronRight from 'vue-material-design-icons/ChevronRight.vue';
import ChevronDown from 'vue-material-design-icons/ChevronDown.vue';
import FileDocument from 'vue-material-design-icons/FileDocument.vue';
import FolderOutline from 'vue-material-design-icons/FolderOutline.vue';
import Check from 'vue-material-design-icons/Check.vue';

export default {
  name: 'PageTreeSelectItem',
  components: {
    ChevronRight,
    ChevronDown,
    FileDocument,
    FolderOutline,
    Check
  },
  props: {
    item: {
      type: Object,
      required: true
    },
    expandedNodes: {
      type: Set,
      required: true
    },
    selectedId: {
      type: String,
      default: null
    },
    focusedId: {
      type: String,
      default: null
    },
    searchQuery: {
      type: String,
      default: ''
    }
  },
  emits: ['toggle', 'select', 'focus'],
  computed: {
    hasChildren() {
      return this.item.children && this.item.children.length > 0;
    },
    isExpanded() {
      return this.expandedNodes.has(this.item.uniqueId);
    },
    isSelected() {
      return this.item.uniqueId === this.selectedId;
    },
    isFocused() {
      return this.item.uniqueId === this.focusedId;
    }
  }
};
</script>

<style scoped>
.tree-select-item {
  list-style: none;
}

.item-row {
  display: flex;
  align-items: center;
  border-radius: 4px;
  margin: 1px 0;
}

.item-row:hover {
  background: var(--color-background-hover);
}

.item-row.is-focused {
  background: var(--color-background-hover);
  outline: 2px solid var(--color-primary-element);
  outline-offset: -2px;
}

.item-row.is-selected {
  background: var(--color-primary-element-light);
}

.item-row.matches-search .item-title {
  font-weight: 600;
}

.expand-toggle {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 22px;
  height: 22px;
  padding: 0;
  margin: 0;
  background: none;
  border: none;
  cursor: pointer;
  color: var(--color-text-maxcontrast);
  border-radius: 4px;
  flex-shrink: 0;
}

.expand-toggle:hover {
  background: var(--color-background-dark);
  color: var(--color-main-text);
}

.toggle-spacer {
  width: 22px;
  flex-shrink: 0;
}

.item-content {
  display: flex;
  align-items: center;
  gap: 6px;
  flex: 1;
  padding: 5px 8px 5px 4px;
  background: none;
  border: none;
  cursor: pointer;
  text-align: left;
  color: var(--color-main-text);
  font-size: 13px;
  min-width: 0;
}

.item-icon {
  flex-shrink: 0;
  color: var(--color-text-maxcontrast);
}

.item-icon.folder-icon {
  color: var(--color-primary-element);
}

.item-title {
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.check-icon {
  flex-shrink: 0;
  color: var(--color-primary-element);
}

.children-list {
  list-style: none;
  margin: 0;
  padding: 0 0 0 22px;
}
</style>
