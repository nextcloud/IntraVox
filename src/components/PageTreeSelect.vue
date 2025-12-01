<template>
  <div class="page-tree-select" ref="selectContainer">
    <!-- Selected value / trigger -->
    <button
      type="button"
      class="select-trigger"
      :class="{ 'is-open': isOpen, 'has-value': selectedPage, 'is-disabled': disabled }"
      :disabled="disabled"
      @click="toggleDropdown"
      ref="trigger"
    >
      <span v-if="selectedPage" class="selected-value">
        <FileDocument :size="16" class="selected-icon" />
        <span class="selected-path" v-if="selectedPath">{{ selectedPath }} /</span>
        <span class="selected-title">{{ selectedPage.title }}</span>
      </span>
      <span v-else class="placeholder">{{ placeholder }}</span>
      <ChevronDown :size="18" class="trigger-icon" :class="{ 'rotated': isOpen }" />
    </button>

    <!-- Dropdown -->
    <div v-if="isOpen" class="select-dropdown" ref="dropdown">
      <!-- Search input -->
      <div class="search-wrapper">
        <Magnify :size="16" class="search-icon" />
        <input
          ref="searchInput"
          v-model="searchQuery"
          type="text"
          class="search-input"
          :placeholder="t('Search pages...')"
          @keydown.escape="closeDropdown"
          @keydown.down.prevent="focusNext"
          @keydown.up.prevent="focusPrev"
          @keydown.enter.prevent="selectFocused"
        />
        <button v-if="searchQuery" class="clear-search" @click="searchQuery = ''">
          <Close :size="14" />
        </button>
      </div>

      <!-- Tree list -->
      <div class="tree-wrapper" ref="treeWrapper">
        <div v-if="loading" class="loading-state">
          <NcLoadingIcon :size="24" />
        </div>

        <div v-else-if="filteredTree.length === 0" class="empty-state">
          {{ searchQuery ? t('No pages found') : t('No pages available') }}
        </div>

        <ul v-else class="tree-list">
          <PageTreeSelectItem
            v-for="item in filteredTree"
            :key="item.uniqueId"
            :item="item"
            :expanded-nodes="expandedNodes"
            :selected-id="modelValue"
            :focused-id="focusedId"
            :search-query="searchQuery"
            @toggle="toggleNode"
            @select="selectPage"
            @focus="focusedId = $event"
          />
        </ul>
      </div>

      <!-- Clear selection -->
      <button v-if="selectedPage && clearable" class="clear-selection" @click="clearSelection">
        <Close :size="16" />
        {{ t('Clear selection') }}
      </button>
    </div>
  </div>
</template>

<script>
import { translate } from '@nextcloud/l10n';
import { NcLoadingIcon } from '@nextcloud/vue';
import axios from '@nextcloud/axios';
import { generateUrl } from '@nextcloud/router';
import ChevronDown from 'vue-material-design-icons/ChevronDown.vue';
import FileDocument from 'vue-material-design-icons/FileDocument.vue';
import Magnify from 'vue-material-design-icons/Magnify.vue';
import Close from 'vue-material-design-icons/Close.vue';
import PageTreeSelectItem from './PageTreeSelectItem.vue';

export default {
  name: 'PageTreeSelect',
  components: {
    NcLoadingIcon,
    ChevronDown,
    FileDocument,
    Magnify,
    Close,
    PageTreeSelectItem
  },
  props: {
    modelValue: {
      type: String,
      default: null
    },
    placeholder: {
      type: String,
      default: 'Select a page'
    },
    clearable: {
      type: Boolean,
      default: true
    },
    disabled: {
      type: Boolean,
      default: false
    }
  },
  emits: ['update:modelValue', 'select'],
  data() {
    return {
      isOpen: false,
      loading: false,
      tree: [],
      expandedNodes: new Set(),
      searchQuery: '',
      focusedId: null,
      flatPages: []
    };
  },
  computed: {
    selectedPage() {
      if (!this.modelValue) return null;
      return this.flatPages.find(p => p.uniqueId === this.modelValue);
    },
    selectedPath() {
      if (!this.selectedPage || !this.selectedPage.path) return null;
      return this.selectedPage.path;
    },
    filteredTree() {
      if (!this.searchQuery.trim()) {
        return this.tree;
      }
      const query = this.searchQuery.toLowerCase();
      return this.filterTree(this.tree, query);
    },
    focusableItems() {
      // Get flat list of visible items for keyboard navigation
      return this.getFlatVisibleItems(this.filteredTree);
    }
  },
  watch: {
    isOpen(open) {
      if (open) {
        this.$nextTick(() => {
          this.$refs.searchInput?.focus();
          // Expand path to selected item
          if (this.modelValue) {
            this.expandPathToItem(this.tree, this.modelValue);
          }
        });
      } else {
        this.searchQuery = '';
        this.focusedId = null;
      }
    }
  },
  mounted() {
    document.addEventListener('click', this.handleClickOutside);
    this.loadTree();
  },
  beforeUnmount() {
    document.removeEventListener('click', this.handleClickOutside);
  },
  methods: {
    t(key, vars = {}) {
      return translate('intravox', key, vars);
    },
    async loadTree() {
      this.loading = true;
      try {
        const response = await axios.get(generateUrl('/apps/intravox/api/pages/tree'));
        this.tree = response.data.tree || [];
        this.flatPages = this.flattenTree(this.tree, '');

        // Auto-expand first level
        this.tree.forEach(item => {
          if (item.children && item.children.length > 0) {
            this.expandedNodes.add(item.uniqueId);
          }
        });
      } catch (err) {
        console.error('PageTreeSelect: Error loading tree:', err);
      } finally {
        this.loading = false;
      }
    },
    flattenTree(nodes, parentPath) {
      const result = [];
      for (const node of nodes) {
        const path = parentPath;
        result.push({
          uniqueId: node.uniqueId,
          title: node.title,
          path: path,
          hasChildren: node.children && node.children.length > 0
        });
        if (node.children && node.children.length > 0) {
          const childPath = parentPath ? `${parentPath} / ${node.title}` : node.title;
          result.push(...this.flattenTree(node.children, childPath));
        }
      }
      return result;
    },
    filterTree(nodes, query) {
      const result = [];
      for (const node of nodes) {
        const matches = node.title.toLowerCase().includes(query);
        const filteredChildren = node.children ? this.filterTree(node.children, query) : [];

        if (matches || filteredChildren.length > 0) {
          result.push({
            ...node,
            children: filteredChildren,
            matchesSearch: matches
          });
          // Auto-expand nodes that have matching children
          if (filteredChildren.length > 0) {
            this.expandedNodes.add(node.uniqueId);
          }
        }
      }
      return result;
    },
    getFlatVisibleItems(nodes) {
      const result = [];
      for (const node of nodes) {
        result.push(node);
        if (node.children && node.children.length > 0 && this.expandedNodes.has(node.uniqueId)) {
          result.push(...this.getFlatVisibleItems(node.children));
        }
      }
      return result;
    },
    expandPathToItem(nodes, targetId) {
      for (const node of nodes) {
        if (node.uniqueId === targetId) {
          return true;
        }
        if (node.children && node.children.length > 0) {
          if (this.expandPathToItem(node.children, targetId)) {
            this.expandedNodes.add(node.uniqueId);
            return true;
          }
        }
      }
      return false;
    },
    toggleDropdown() {
      this.isOpen = !this.isOpen;
    },
    closeDropdown() {
      this.isOpen = false;
    },
    toggleNode(uniqueId) {
      if (this.expandedNodes.has(uniqueId)) {
        this.expandedNodes.delete(uniqueId);
      } else {
        this.expandedNodes.add(uniqueId);
      }
      this.expandedNodes = new Set(this.expandedNodes);
    },
    selectPage(page) {
      this.$emit('update:modelValue', page.uniqueId);
      this.$emit('select', page);
      this.closeDropdown();
    },
    clearSelection() {
      this.$emit('update:modelValue', null);
      this.$emit('select', null);
      this.closeDropdown();
    },
    handleClickOutside(event) {
      if (this.$refs.selectContainer && !this.$refs.selectContainer.contains(event.target)) {
        this.closeDropdown();
      }
    },
    focusNext() {
      const items = this.focusableItems;
      if (items.length === 0) return;

      const currentIndex = items.findIndex(i => i.uniqueId === this.focusedId);
      const nextIndex = currentIndex < items.length - 1 ? currentIndex + 1 : 0;
      this.focusedId = items[nextIndex].uniqueId;
      this.scrollToFocused();
    },
    focusPrev() {
      const items = this.focusableItems;
      if (items.length === 0) return;

      const currentIndex = items.findIndex(i => i.uniqueId === this.focusedId);
      const prevIndex = currentIndex > 0 ? currentIndex - 1 : items.length - 1;
      this.focusedId = items[prevIndex].uniqueId;
      this.scrollToFocused();
    },
    selectFocused() {
      if (this.focusedId) {
        const page = this.flatPages.find(p => p.uniqueId === this.focusedId);
        if (page) {
          this.selectPage(page);
        }
      }
    },
    scrollToFocused() {
      this.$nextTick(() => {
        const focusedEl = this.$refs.treeWrapper?.querySelector('.is-focused');
        if (focusedEl) {
          focusedEl.scrollIntoView({ block: 'nearest' });
        }
      });
    }
  }
};
</script>

<style scoped>
.page-tree-select {
  position: relative;
  width: 100%;
}

.select-trigger {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 6px 10px;
  background: var(--color-main-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  cursor: pointer;
  text-align: left;
  font-size: 13px;
  min-height: 34px;
  gap: 8px;
}

.select-trigger:hover:not(:disabled) {
  border-color: var(--color-primary-element);
}

.select-trigger.is-disabled {
  opacity: 0.5;
  cursor: not-allowed;
  background: var(--color-background-dark);
}

.select-trigger.is-open {
  border-color: var(--color-primary-element);
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 0;
}

.selected-value {
  display: flex;
  align-items: center;
  gap: 6px;
  flex: 1;
  min-width: 0;
  overflow: hidden;
}

.selected-icon {
  flex-shrink: 0;
  color: var(--color-primary-element);
}

.selected-path {
  color: var(--color-text-maxcontrast);
  font-size: 12px;
  white-space: nowrap;
}

.selected-title {
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.placeholder {
  color: var(--color-text-maxcontrast);
}

.trigger-icon {
  flex-shrink: 0;
  color: var(--color-text-maxcontrast);
  transition: transform 0.2s;
}

.trigger-icon.rotated {
  transform: rotate(180deg);
}

.select-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: var(--color-main-background);
  border: 1px solid var(--color-primary-element);
  border-top: none;
  border-radius: 0 0 var(--border-radius) var(--border-radius);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 1000;
  max-height: 350px;
  display: flex;
  flex-direction: column;
}

.search-wrapper {
  display: flex;
  align-items: center;
  padding: 8px;
  border-bottom: 1px solid var(--color-border);
  gap: 8px;
}

.search-icon {
  flex-shrink: 0;
  color: var(--color-text-maxcontrast);
}

.search-input {
  flex: 1;
  border: none;
  background: none;
  font-size: 13px;
  outline: none;
  padding: 4px 0;
}

.search-input::placeholder {
  color: var(--color-text-maxcontrast);
}

.clear-search {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  padding: 0;
  border: none;
  background: var(--color-background-dark);
  border-radius: 50%;
  cursor: pointer;
  color: var(--color-text-maxcontrast);
}

.clear-search:hover {
  background: var(--color-error);
  color: white;
}

.tree-wrapper {
  flex: 1;
  overflow-y: auto;
  padding: 8px;
  max-height: 250px;
}

.loading-state,
.empty-state {
  padding: 20px;
  text-align: center;
  color: var(--color-text-maxcontrast);
}

.tree-list {
  list-style: none;
  margin: 0;
  padding: 0;
}

.clear-selection {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px;
  border: none;
  border-top: 1px solid var(--color-border);
  background: var(--color-background-dark);
  cursor: pointer;
  font-size: 12px;
  color: var(--color-text-maxcontrast);
}

.clear-selection:hover {
  background: var(--color-error-hover);
  color: var(--color-error);
}
</style>
