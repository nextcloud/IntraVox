<template>
  <li class="tree-item">
    <div class="tree-item-row" :class="{ 'is-current': item.isCurrent }">
      <!-- Expand/collapse toggle -->
      <button
        v-if="hasChildren"
        class="tree-toggle"
        @click="$emit('toggle', item.uniqueId)"
        :aria-label="isExpanded ? t('intravox', 'Collapse') : t('intravox', 'Expand')"
      >
        <ChevronRight v-if="!isExpanded" :size="18" />
        <ChevronDown v-else :size="18" />
      </button>
      <span v-else class="tree-toggle-spacer"></span>

      <!-- Page icon and title -->
      <button class="tree-item-content" @click="$emit('navigate', item.uniqueId)">
        <FileDocument :size="18" class="tree-icon" />
        <span class="tree-item-title">{{ item.title }}</span>
        <span v-if="isThisHomepage" class="home-badge">{{ t('intravox', 'Home') }}</span>
        <span v-if="item.isCurrent" class="current-badge">{{ t('intravox', 'Current') }}</span>
      </button>

      <!-- Manage actions (issue #69) -->
      <div v-if="manageMode" class="tree-item-actions">
        <button
          class="tree-action"
          :disabled="isFirst"
          :aria-label="t('intravox', 'Move up')"
          :title="t('intravox', 'Move up')"
          @click="$emit('move-up', item.uniqueId)"
        >
          <ArrowUp :size="18" />
        </button>
        <button
          class="tree-action"
          :disabled="isLast"
          :aria-label="t('intravox', 'Move down')"
          :title="t('intravox', 'Move down')"
          @click="$emit('move-down', item.uniqueId)"
        >
          <ArrowDown :size="18" />
        </button>
        <button
          v-if="!isThisHomepage"
          class="tree-action"
          :aria-label="t('intravox', 'Move to another page')"
          :title="t('intravox', 'Move to another page')"
          @click="$emit('move-to', item)"
        >
          <FolderMove :size="18" />
        </button>
        <button
          v-if="canSetHomepage"
          class="tree-action"
          :aria-label="t('intravox', 'Set as homepage')"
          :title="t('intravox', 'Set as homepage')"
          @click="$emit('set-homepage', item)"
        >
          <HomeOutline :size="18" />
        </button>
        <button
          class="tree-action"
          :aria-label="t('intravox', 'Copy page')"
          :title="t('intravox', 'Copy page')"
          @click="$emit('copy', item)"
        >
          <ContentCopy :size="18" />
        </button>
        <button
          v-if="!isThisHomepage && (item.permissions && item.permissions.canDelete)"
          class="tree-action tree-action--danger"
          :aria-label="t('intravox', 'Delete')"
          :title="t('intravox', 'Delete')"
          @click="$emit('delete', item)"
        >
          <Delete :size="18" />
        </button>
      </div>
    </div>

    <!-- Children (progressive rendering: show up to visibleChildCount, expand on demand) -->
    <ul v-if="hasChildren && isExpanded" class="tree-children">
      <PageTreeItem
        v-for="(child, idx) in visibleChildren"
        :key="child.uniqueId"
        :item="child"
        :expanded-nodes="expandedNodes"
        :manage-mode="manageMode"
        :parent-id="item.uniqueId"
        :homepage-unique-id="homepageUniqueId"
        :is-first="idx === 0"
        :is-last="idx === visibleChildren.length - 1"
        @toggle="(id) => $emit('toggle', id)"
        @navigate="(id) => $emit('navigate', id)"
        @move-up="(id) => $emit('move-up', id)"
        @move-down="(id) => $emit('move-down', id)"
        @move-to="(node) => $emit('move-to', node)"
        @delete="(node) => $emit('delete', node)"
        @copy="(node) => $emit('copy', node)"
        @set-homepage="(node) => $emit('set-homepage', node)"
      />
      <li v-if="hasMoreChildren" class="tree-show-more">
        <button class="show-more-button" @click="showMoreChildren">
          {{ t('intravox', 'Show {count} more...', { count: item.children.length - visibleChildCount }) }}
        </button>
      </li>
    </ul>
  </li>
</template>

<script>
import { translate, translatePlural } from '@nextcloud/l10n';
import ChevronRight from 'vue-material-design-icons/ChevronRight.vue';
import ChevronDown from 'vue-material-design-icons/ChevronDown.vue';
import FileDocument from 'vue-material-design-icons/FileDocument.vue';
import ArrowUp from 'vue-material-design-icons/ArrowUp.vue';
import ArrowDown from 'vue-material-design-icons/ArrowDown.vue';
import FolderMove from 'vue-material-design-icons/FolderMove.vue';
import Delete from 'vue-material-design-icons/Delete.vue';
import ContentCopy from 'vue-material-design-icons/ContentCopy.vue';
import HomeOutline from 'vue-material-design-icons/HomeOutline.vue';

export default {
  name: 'PageTreeItem',
  components: {
    ChevronRight,
    ChevronDown,
    FileDocument,
    ArrowUp,
    ArrowDown,
    FolderMove,
    Delete,
    ContentCopy,
    HomeOutline
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
    manageMode: {
      type: Boolean,
      default: false
    },
    isFirst: {
      type: Boolean,
      default: false
    },
    isLast: {
      type: Boolean,
      default: false
    },
    parentId: {
      type: String,
      default: null
    },
    homepageUniqueId: {
      type: String,
      default: null
    }
  },
  emits: ['toggle', 'navigate', 'move-up', 'move-down', 'move-to', 'delete', 'copy', 'set-homepage'],
  data() {
    return {
      visibleChildCount: 50,
    };
  },
  computed: {
    hasChildren() {
      return this.item.children && this.item.children.length > 0;
    },
    isExpanded() {
      return this.expandedNodes.has(this.item.uniqueId);
    },
    visibleChildren() {
      if (!this.hasChildren) return [];
      return this.item.children.slice(0, this.visibleChildCount);
    },
    hasMoreChildren() {
      return this.hasChildren && this.item.children.length > this.visibleChildCount;
    },
    isThisHomepage() {
      if (this.homepageUniqueId) {
        return this.item.uniqueId === this.homepageUniqueId;
      }
      return this.item.uniqueId === 'home';
    },
    isRootLevel() {
      // Root rows are rendered by the modal with parentId === null.
      return this.parentId === null || this.parentId === undefined || this.parentId === '';
    },
    canSetHomepage() {
      // Only root-level pages can become the homepage, and not the current one.
      return this.isRootLevel && !this.isThisHomepage;
    },
  },
  methods: {
    t(app, text, vars) {
      return translate(app, text, vars);
    },
    showMoreChildren() {
      this.visibleChildCount += 50;
    },
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

.home-badge {
  flex-shrink: 0;
  font-size: 11px;
  padding: 2px 6px;
  background: var(--color-primary-element-light);
  color: var(--color-primary-element-light-text, var(--color-main-text));
  border-radius: 10px;
  font-weight: 500;
  margin-right: 4px;
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

.tree-item-actions {
  display: flex;
  align-items: center;
  gap: 2px;
  flex-shrink: 0;
  margin-left: 4px;
}

.tree-action {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  padding: 0;
  background: none;
  border: none;
  cursor: pointer;
  color: var(--color-text-maxcontrast);
  border-radius: var(--border-radius);
}

.tree-action:hover:not(:disabled) {
  background: var(--color-background-dark);
  color: var(--color-main-text);
}

.tree-action:disabled {
  opacity: 0.35;
  cursor: default;
}

.tree-action--danger:hover:not(:disabled) {
  color: var(--color-error);
}

.tree-children {
  list-style: none;
  margin: 0;
  padding: 0 0 0 24px;
}

.tree-show-more {
  list-style: none;
}

.show-more-button {
  display: block;
  width: 100%;
  padding: 6px 8px 6px 32px;
  background: none;
  border: none;
  cursor: pointer;
  text-align: left;
  color: var(--color-primary-element);
  font-size: 13px;
  border-radius: 4px;
}

.show-more-button:hover {
  background: var(--color-background-hover);
}
</style>
