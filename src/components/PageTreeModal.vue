<template>
  <NcModal @close="$emit('close')"
           :name="modalTitle"
           size="normal">
    <div class="page-tree-content">
      <NcNoteCard v-if="!loading && !error && tree.length > 0" type="info">
        {{ t('intravox', 'This shows all your actual pages. Use "Manage structure" to move, reorder, copy or delete them. To change the links in the navigation bar and their order, use "Edit navigation".') }}
      </NcNoteCard>

      <div v-if="canManage && !loading && !error && tree.length > 0" class="page-tree-toolbar">
        <NcButton type="tertiary"
                  @click="manageMode = !manageMode">
          <template #icon>
            <Cog :size="20" />
          </template>
          {{ manageMode ? t('intravox', 'Done') : t('intravox', 'Manage structure') }}
        </NcButton>
      </div>

      <NcNoteCard v-if="manageMode" type="warning">
        {{ t('intravox', 'Changes here move and rename the actual pages and their folders. This is different from "Edit navigation", which only changes the links in the navigation bar.') }}
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

      <!-- Inline "move to" step (kept inside this modal to avoid stacked modals) -->
      <div v-else-if="movingNode" class="move-panel">
        <p class="move-panel__label">
          {{ t('intravox', 'Move this page to:') }}
        </p>
        <p class="move-panel__title">{{ movingNode.title }}</p>
        <NcCheckboxRadioSwitch
          type="switch"
          :model-value="moveToRoot"
          @update:model-value="setMoveToRoot">
          {{ t('intravox', 'Move to the top level') }}
        </NcCheckboxRadioSwitch>
        <PageTreeSelect
          v-if="!moveToRoot"
          v-model="moveTargetId"
          :placeholder="t('intravox', 'Select a destination page')" />
        <div class="move-panel__actions">
          <NcButton type="tertiary" @click="cancelMove">
            {{ t('intravox', 'Cancel') }}
          </NcButton>
          <NcButton type="primary" :disabled="moveInProgress || (!moveToRoot && !moveTargetId)" @click="confirmMove">
            {{ t('intravox', 'Move page') }}
          </NcButton>
        </div>
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
import { NcModal, NcLoadingIcon, NcButton, NcNoteCard, NcCheckboxRadioSwitch } from '@nextcloud/vue';
import Cog from 'vue-material-design-icons/Cog.vue';
import axios from '@nextcloud/axios';
import { generateUrl } from '@nextcloud/router';
import PageTreeItem from './PageTreeItem.vue';
import PageTreeSelect from './PageTreeSelect.vue';

export default {
  name: 'PageTreeModal',
  components: {
    NcModal,
    NcLoadingIcon,
    NcButton,
    NcNoteCard,
    NcCheckboxRadioSwitch,
    PageTreeItem,
    PageTreeSelect,
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
      movingNode: null,
      moveTargetId: null,
      moveToRoot: false,
      moveInProgress: false,
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
      // Swap in place so the modal reflects the new order instantly (no full
      // reload / flash). The server persists in the background.
      [siblings[i], siblings[j]] = [siblings[j], siblings[i]];
      const orderedIds = siblings.map(n => n.uniqueId);
      this.$emit('reorder', { parentId: parentId || null, orderedIds });
    },
    handleMoveUp(uniqueId) {
      this.emitReorderAfterSwap(uniqueId, -1);
    },
    handleMoveDown(uniqueId) {
      this.emitReorderAfterSwap(uniqueId, 1);
    },
    handleMoveTo(node) {
      this.movingNode = node;
      this.moveTargetId = null;
      this.moveToRoot = false;
    },
    setMoveToRoot(val) {
      this.moveToRoot = val;
      if (val) this.moveTargetId = null;
    },
    cancelMove() {
      this.movingNode = null;
      this.moveTargetId = null;
      this.moveToRoot = false;
    },
    async confirmMove() {
      if (!this.movingNode) return;
      this.moveInProgress = true;
      const payload = {
        pageId: this.movingNode.uniqueId,
        targetParentId: this.moveToRoot ? '' : (this.moveTargetId || ''),
      };
      // Delegate the API call to the parent, then refresh the tree in place.
      this.$emit('move', payload, async (ok) => {
        this.moveInProgress = false;
        if (ok) {
          this.movingNode = null;
          this.moveTargetId = null;
          this.moveToRoot = false;
          await this.loadTree();
        }
      });
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

.page-tree-toolbar {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 12px;
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
.move-panel {
  padding: 8px 4px;
}

.move-panel__label {
  margin: 0 0 4px;
  color: var(--color-text-maxcontrast);
}

.move-panel__title {
  margin: 0 0 12px;
  font-weight: 600;
}

.move-panel__actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 16px;
}

</style>
