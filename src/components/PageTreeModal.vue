<template>
  <NcModal @close="$emit('close')"
           :name="modalTitle"
           size="normal">
    <div class="page-tree-content">
      <div class="page-tree-header">
        <h2 class="page-tree-title">{{ modalTitle }}</h2>
        <NcButton v-if="canManage && !loading && !error && tree.length > 0"
                  type="tertiary"
                  @click="manageMode = !manageMode">
          <template #icon>
            <Cog :size="20" />
          </template>
          {{ manageMode ? t('intravox', 'Done') : t('intravox', 'Manage structure') }}
        </NcButton>
      </div>

      <p v-if="!loading && !error && tree.length > 0" class="page-tree-intro">
        {{ t('intravox', 'Browse all pages. Use "Manage structure" to move, reorder, copy or delete the actual pages.') }}
      </p>

      <NcNoteCard v-if="manageMode" type="warning">
        {{ t('intravox', 'Changes here move the actual pages and their folders, not just the menu.') }}
      </NcNoteCard>

      <div v-if="loading" class="loading-state">
        <NcLoadingIcon :size="32" />
        <p>{{ t('intravox', 'Loading page structure …') }}</p>
      </div>

      <div v-else-if="error" class="error-state">
        <p>{{ error }}</p>
      </div>

      <div v-else-if="tree.length === 0" class="empty-state">
        <p>{{ t('intravox', 'No pages found.') }}</p>
      </div>

      <div v-else class="page-tree">
        <ul class="tree-list">
          <PageTreeItem
            v-for="(item, idx) in tree"
            :key="item.uniqueId"
            :item="item"
            :expanded-nodes="expandedNodes"
            :manage-mode="manageMode"
            :parent-id="null"
            :homepage-unique-id="homepageUniqueId"
            :is-first="idx === 0"
            :is-last="idx === tree.length - 1"
            @toggle="toggleNode"
            @navigate="handleNavigate"
            @move-up="handleMoveUp"
            @move-down="handleMoveDown"
            @move-to="handleMoveTo"
            @delete="handleDelete"
            @copy="handleCopy"
            @set-homepage="handleSetHomepage"
          />
        </ul>
      </div>
    </div>
  </NcModal>
</template>

<script>
import { translate, translatePlural } from '@nextcloud/l10n';
import { NcModal, NcLoadingIcon, NcButton, NcNoteCard } from '@nextcloud/vue';
import Cog from 'vue-material-design-icons/Cog.vue';
import axios from '@nextcloud/axios';
import { generateUrl } from '@nextcloud/router';
import PageTreeItem from './PageTreeItem.vue';

export default {
  name: 'PageTreeModal',
  components: {
    NcModal,
    NcLoadingIcon,
    NcButton,
    NcNoteCard,
    PageTreeItem,
    Cog
  },
  props: {
    currentPageId: {
      type: String,
      default: null
    },
    shareToken: {
      type: String,
      default: null
    },
    canManage: {
      type: Boolean,
      default: false
    }
  },
  emits: ['close', 'navigate', 'reorder', 'move', 'delete', 'copy', 'set-homepage', 'homepage'],
  data() {
    return {
      tree: [],
      loading: true,
      error: null,
      expandedNodes: new Set(),
      homepageUniqueId: null,
      manageMode: false
    };
  },
  computed: {
    modalTitle() {
      return this.t('intravox', 'Page structure');
    }
  },
  async mounted() {
    await this.loadTree();
  },
  methods: {
    t(app, text, vars) {
      return translate(app, text, vars);
    },
    async loadTree() {
      this.loading = true;
      this.error = null;

      try {
        const params = this.currentPageId ? { currentPageId: this.currentPageId } : {};
        let url;
        if (this.shareToken) {
          url = generateUrl('/apps/intravox/api/share/{token}/tree', { token: this.shareToken });
        } else {
          url = generateUrl('/apps/intravox/api/pages/tree');
        }
        const response = await axios.get(url, { params });
        this.tree = response.data.tree || [];
        this.homepageUniqueId = response.data.homepageUniqueId || null;
        this.$emit('homepage', this.homepageUniqueId);

        // Auto-expand nodes: path to current page + all children below current
        this.expandPathToCurrent(this.tree);
      } catch (err) {
        console.error('PageTreeModal: Error loading tree:', err);
        this.error = this.t('intravox', 'Could not load page structure: {error}', { error: err.message });
      } finally {
        this.loading = false;
      }
    },
    expandPathToCurrent(nodes) {
      // Recursively find and expand the path to the current page
      // Also expand all children below the current page
      for (const node of nodes) {
        if (node.isCurrent) {
          // Found current page - expand all its descendants
          this.expandAllDescendants(node);
          return true;
        }
        if (node.children && node.children.length > 0) {
          const hasCurrentChild = this.expandPathToCurrent(node.children);
          if (hasCurrentChild) {
            this.expandedNodes.add(node.uniqueId);
            return true;
          }
        }
      }
      return false;
    },
    expandAllDescendants(node) {
      // Recursively expand this node and all its children
      if (node.children && node.children.length > 0) {
        this.expandedNodes.add(node.uniqueId);
        for (const child of node.children) {
          this.expandAllDescendants(child);
        }
      }
    },
    toggleNode(uniqueId) {
      if (this.expandedNodes.has(uniqueId)) {
        this.expandedNodes.delete(uniqueId);
      } else {
        this.expandedNodes.add(uniqueId);
      }
      // Force reactivity update
      this.expandedNodes = new Set(this.expandedNodes);
    },
    handleNavigate(uniqueId) {
      this.$emit('navigate', uniqueId);
      this.$emit('close');
    },
    // ---- Manage: reorder / move / delete (issue #69) ----
    findSiblingList(nodes, parentId) {
      if (parentId === null || parentId === undefined || parentId === '') {
        return nodes;
      }
      for (const node of nodes) {
        if (node.uniqueId === parentId) {
          return node.children || [];
        }
        if (node.children && node.children.length) {
          const found = this.findSiblingList(node.children, parentId);
          if (found) return found;
        }
      }
      return null;
    },
    parentIdOf(targetId, nodes = this.tree, parent = null) {
      for (const node of nodes) {
        if (node.uniqueId === targetId) return parent;
        if (node.children && node.children.length) {
          const found = this.parentIdOf(targetId, node.children, node.uniqueId);
          if (found !== undefined) return found;
        }
      }
      return undefined;
    },
    emitReorderAfterSwap(uniqueId, delta) {
      const parentId = this.parentIdOf(uniqueId);
      if (parentId === undefined) return;
      const siblings = this.findSiblingList(this.tree, parentId);
      if (!siblings) return;
      const i = siblings.findIndex(n => n.uniqueId === uniqueId);
      const j = i + delta;
      if (i < 0 || j < 0 || j >= siblings.length) return;
      const reordered = siblings.slice();
      [reordered[i], reordered[j]] = [reordered[j], reordered[i]];
      const orderedIds = reordered.map(n => n.uniqueId);
      this.$emit('reorder', { parentId: parentId || null, orderedIds });
    },
    handleMoveUp(uniqueId) {
      this.emitReorderAfterSwap(uniqueId, -1);
    },
    handleMoveDown(uniqueId) {
      this.emitReorderAfterSwap(uniqueId, 1);
    },
    handleMoveTo(node) {
      this.$emit('move', node);
    },
    handleDelete(node) {
      this.$emit('delete', node);
    },
    handleCopy(node) {
      this.$emit('copy', node);
    },
    handleSetHomepage(node) {
      this.$emit('set-homepage', node);
    }
  }
};
</script>

<style scoped>
.page-tree-content {
  padding: 20px;
}

.page-tree-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 16px;
}

.page-tree-header .page-tree-title {
  margin: 0;
}

.page-tree-title {
  margin: 0 0 16px 0;
  font-size: 20px;
  font-weight: 600;
  color: var(--color-main-text);
}

.loading-state,
.error-state,
.empty-state {
  text-align: center;
  padding: 40px;
  color: var(--color-text-maxcontrast);
}

.error-state {
  color: var(--color-error);
}

.page-tree {
  max-height: 60vh;
  overflow-y: auto;
}

.tree-list {
  list-style: none;
  margin: 0;
  padding: 0;
}
.page-tree-intro {
  margin: 0 0 12px;
  color: var(--color-text-maxcontrast);
  font-size: 13px;
}
</style>
