<template>
  <li class="tree-item">
    <div class="tree-item-row" :class="{ 'is-current': item.isCurrent }">
      <!-- Expand/collapse toggle -->
      <button
        v-if="hasChildren"
        class="tree-toggle"
        @click="$emit('toggle', item.uniqueId)"
        :aria-label="isExpanded ? t('Collapse') : t('Expand')"
      >
        <ChevronRight v-if="!isExpanded" :size="18" />
        <ChevronDown v-else :size="18" />
      </button>
      <span v-else class="tree-toggle-spacer"></span>

      <!-- Page icon and title -->
      <button class="tree-item-content" @click="$emit('navigate', item.uniqueId)">
        <FileDocument :size="18" class="tree-icon" />
        <span class="tree-item-title">{{ item.title }}</span>
        <span v-if="item.isCurrent" class="current-badge">{{ t('Current') }}</span>
      </button>
    </div>

    <!-- Children -->
    <ul v-if="hasChildren && isExpanded" class="tree-children">
      <PageTreeItem
        v-for="child in item.children"
        :key="child.uniqueId"
        :item="child"
        :expanded-nodes="expandedNodes"
        @toggle="(id) => $emit('toggle', id)"
        @navigate="(id) => $emit('navigate', id)"
      />
    </ul>
  </li>
</template>

<script>
import { translate } from '@nextcloud/l10n';
import ChevronRight from 'vue-material-design-icons/ChevronRight.vue';
import ChevronDown from 'vue-material-design-icons/ChevronDown.vue';
import FileDocument from 'vue-material-design-icons/FileDocument.vue';

export default {
  name: 'PageTreeItem',
  components: {
    ChevronRight,
    ChevronDown,
    FileDocument
  },
  props: {
    item: {
      type: Object,
      required: true
    },
    expandedNodes: {
      type: Set,
      required: true
    }
  },
  emits: ['toggle', 'navigate'],
  computed: {
    hasChildren() {
      return this.item.children && this.item.children.length > 0;
    },
    isExpanded() {
      return this.expandedNodes.has(this.item.uniqueId);
    }
  },
  methods: {
    t(key, vars = {}) {
      return translate('intravox', key, vars);
    }
  }
};
</script>

<style scoped>
.tree-item {
  list-style: none;
}

.tree-item-row {
  display: flex;
  align-items: center;
  padding: 4px 0;
  border-radius: 4px;
}

.tree-item-row:hover {
  background: var(--color-background-hover);
}

.tree-item-row.is-current {
  background: var(--color-primary-element-light);
}

.tree-toggle {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  padding: 0;
  margin: 0;
  background: none;
  border: none;
  cursor: pointer;
  color: var(--color-text-maxcontrast);
  border-radius: 4px;
  flex-shrink: 0;
}

.tree-toggle:hover {
  background: var(--color-background-dark);
  color: var(--color-main-text);
}

.tree-toggle-spacer {
  width: 24px;
  flex-shrink: 0;
}

.tree-item-content {
  display: flex;
  align-items: center;
  gap: 8px;
  flex: 1;
  padding: 4px 8px;
  background: none;
  border: none;
  cursor: pointer;
  text-align: left;
  color: var(--color-main-text);
  border-radius: 4px;
  min-width: 0;
}

.tree-item-content:hover {
  background: var(--color-background-dark);
}

.tree-icon {
  color: var(--color-text-maxcontrast);
  flex-shrink: 0;
}

.tree-item-title {
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.current-badge {
  flex-shrink: 0;
  font-size: 11px;
  padding: 2px 6px;
  background: var(--color-primary-element);
  color: var(--color-primary-element-text);
  border-radius: 10px;
  font-weight: 500;
}

.tree-children {
  list-style: none;
  margin: 0;
  padding: 0 0 0 24px;
}
</style>
