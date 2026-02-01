<template>
  <NcModal @close="$emit('close')"
           :name="modalTitle"
           size="normal">
    <div class="page-tree-content">
      <h2 class="page-tree-title">{{ modalTitle }}</h2>

      <div v-if="loading" class="loading-state">
        <NcLoadingIcon :size="32" />
        <p>{{ t('Loading page structure...') }}</p>
      </div>

      <div v-else-if="error" class="error-state">
        <p>{{ error }}</p>
      </div>

      <div v-else-if="tree.length === 0" class="empty-state">
        <p>{{ t('No pages found.') }}</p>
      </div>

      <div v-else class="page-tree">
        <ul class="tree-list">
          <PageTreeItem
            v-for="item in tree"
            :key="item.uniqueId"
            :item="item"
            :expanded-nodes="expandedNodes"
            @toggle="toggleNode"
            @navigate="handleNavigate"
          />
        </ul>
      </div>
    </div>
  </NcModal>
</template>

<script>
import { translate } from '@nextcloud/l10n';
import { NcModal, NcLoadingIcon } from '@nextcloud/vue';
import axios from '@nextcloud/axios';
import { generateUrl } from '@nextcloud/router';
import PageTreeItem from './PageTreeItem.vue';

export default {
  name: 'PageTreeModal',
  components: {
    NcModal,
    NcLoadingIcon,
    PageTreeItem
  },
  props: {
    currentPageId: {
      type: String,
      default: null
    },
    shareToken: {
      type: String,
      default: null
    }
  },
  emits: ['close', 'navigate'],
  data() {
    return {
      tree: [],
      loading: true,
      error: null,
      expandedNodes: new Set()
    };
  },
  computed: {
    modalTitle() {
      return this.t('Page structure');
    }
  },
  async mounted() {
    await this.loadTree();
  },
  methods: {
    t(key, vars = {}) {
      return translate('intravox', key, vars);
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

        // Auto-expand nodes: path to current page + all children below current
        this.expandPathToCurrent(this.tree);
      } catch (err) {
        console.error('PageTreeModal: Error loading tree:', err);
        this.error = this.t('Could not load page structure: {error}', { error: err.message });
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
    }
  }
};
</script>

<style scoped>
.page-tree-content {
  padding: 20px;
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
</style>
