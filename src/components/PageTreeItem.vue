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

      <!-- Pass-through level: a folder whose page does not exist in this
           language (a deep page was translated before its ancestors). Same
           rule as the breadcrumb — a missing ancestor is a label, not a
           wall — so it is not clickable and offers no actions, but its
           children stay reachable. -->
      <span v-if="isPlaceholder"
            class="tree-item-content tree-item-content--placeholder"
            :title="`${item.title} — ${t('intravox', 'This level has no page in this language yet')}`">
        <FolderOutline :size="18" class="tree-icon" />
        <span class="tree-item-title">{{ item.title }}</span>
      </span>

      <!-- Page icon and title. De titel wordt in het smalle paneel afgekapt met
           ellipsis, dus de volledige naam moet via de tooltip leesbaar blijven. -->
      <button v-else class="tree-item-content" :title="item.title" @click="$emit('navigate', item.uniqueId)">
        <FileDocument :size="18" class="tree-icon" />
        <span class="tree-item-title">{{ item.title }}</span>
        <span v-if="isThisHomepage" class="home-badge">{{ t('intravox', 'Home') }}</span>
        <span v-if="item.isCurrent" class="current-badge">{{ t('intravox', 'Current') }}</span>
      </button>

      <!-- Manage actions (issue #69). Each action is gated on the permission the
           backend actually enforces, so the UI never offers something that 403s
           (issue #86): reorder/move need write on the page, copy needs create. -->
      <div v-if="manageMode && !isPlaceholder" class="tree-item-actions">
        <button
          v-if="canWrite"
          class="tree-action"
          :disabled="isFirst"
          :aria-label="t('intravox', 'Move up')"
          :title="t('intravox', 'Move up')"
          @click="$emit('move-up', item.uniqueId)"
        >
          <ArrowUp :size="18" />
        </button>
        <button
          v-if="canWrite"
          class="tree-action"
          :disabled="isLast"
          :aria-label="t('intravox', 'Move down')"
          :title="t('intravox', 'Move down')"
          @click="$emit('move-down', item.uniqueId)"
        >
          <ArrowDown :size="18" />
        </button>
        <button
          v-if="canWrite && !isThisHomepage"
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
          v-if="canWrite"
          class="tree-action"
          :aria-label="t('intravox', 'Rename')"
          :title="t('intravox', 'Rename')"
          @click="$emit('rename', item)"
        >
          <RenameBox :size="18" />
        </button>
        <button
          v-if="parentCanCreate"
          class="tree-action"
          :aria-label="t('intravox', 'Copy page')"
          :title="t('intravox', 'Copy page')"
          @click="$emit('copy', { item, parentId })"
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
        :parent-can-create="canCreate"
        :homepage-unique-id="homepageUniqueId"
        :is-first="idx === 0"
        :is-last="idx === visibleChildren.length - 1"
        @toggle="(id) => $emit('toggle', id)"
        @navigate="(id) => $emit('navigate', id)"
        @move-up="(id) => $emit('move-up', id)"
        @move-down="(id) => $emit('move-down', id)"
        @move-to="(node) => $emit('move-to', node)"
        @rename="(node) => $emit('rename', node)"
        @delete="(node) => $emit('delete', node)"
        @copy="(payload) => $emit('copy', payload)"
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
import { translate } from '@nextcloud/l10n';
import ChevronRight from 'vue-material-design-icons/ChevronRight.vue';
import ChevronDown from 'vue-material-design-icons/ChevronDown.vue';
import FileDocument from 'vue-material-design-icons/FileDocument.vue';
import FolderOutline from 'vue-material-design-icons/FolderOutline.vue';
import ArrowUp from 'vue-material-design-icons/ArrowUp.vue';
import ArrowDown from 'vue-material-design-icons/ArrowDown.vue';
import FolderMove from 'vue-material-design-icons/FolderMove.vue';
import RenameBox from 'vue-material-design-icons/RenameBox.vue';
import Delete from 'vue-material-design-icons/Delete.vue';
import ContentCopy from 'vue-material-design-icons/ContentCopy.vue';
import HomeOutline from 'vue-material-design-icons/HomeOutline.vue';

export default {
  name: 'PageTreeItem',
  components: {
    ChevronRight,
    ChevronDown,
    FileDocument,
    FolderOutline,
    ArrowUp,
    ArrowDown,
    FolderMove,
    RenameBox,
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
    // Create-permission on THIS item's parent folder — i.e. where a sibling copy
    // of this page would land. Gates the Copy button so it only shows when the
    // backend copy (into the parent) will actually succeed (issue #86 follow-up).
    parentCanCreate: {
      type: Boolean,
      default: false
    },
    homepageUniqueId: {
      type: String,
      default: null
    }
  },
  emits: ['toggle', 'navigate', 'move-up', 'move-down', 'move-to', 'rename', 'delete', 'copy', 'set-homepage'],
  data() {
    return {
      visibleChildCount: 50,
    };
  },
  computed: {
    isPlaceholder() {
      return this.item.isPlaceholder === true;
    },
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
      // Setting the homepage writes the language root, so also require write
      // here (issue #86) — the backend enforces the root check as the safety net.
      return this.isRootLevel && !this.isThisHomepage && this.canWrite;
    },
    // Per-item permission gates (issue #86) — match what each backend endpoint
    // enforces so a manage action is only shown when it will actually succeed.
    canWrite() {
      return !!(this.item.permissions && this.item.permissions.canWrite);
    },
    canCreate() {
      return !!(this.item.permissions && this.item.permissions.canCreate);
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
  /* Een flex-item weigert standaard te krimpen onder de breedte van zijn
     inhoud (min-width:auto). Zonder deze regel kan de rij dus breder worden
     dan het paneel en heeft de ellipsis op .tree-item-title niets om tegen af
     te kappen: de titel wijkt uit naar een tweede regel, die door de
     inspringing van .tree-children links buiten de tekstkolom begint. */
  min-width: 0;
}

.tree-item-row:hover {
  background: var(--color-background-hover);
}

/* Zelfde actieve markering als de inhoudsopgave: vlak + balk + accentkleur,
   zodat beide weergaven van het paneel één taal spreken. */
.tree-item-row.is-current {
  background: var(--color-primary-element-light);
  box-shadow: inset 3px 0 0 var(--color-primary-element);
}

.tree-item-row.is-current .tree-item-title,
.tree-item-row.is-current .tree-icon {
  color: var(--color-primary-element);
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
  /* Nextcloud maakt élke button bold; in een navigatieboom leest dat als één
     grote nadruk. Vet is voorbehouden aan de huidige pagina. */
  font-weight: normal;
}

.tree-item-row.is-current .tree-item-title {
  font-weight: 600;
}

.tree-item-content:hover {
  background: var(--color-background-dark);
}

/* Pass-through level: readable but visibly not a page — muted, no pointer,
   no hover-invite. The span already inherits the flex layout above. */
.tree-item-content--placeholder {
  cursor: default;
  color: var(--color-text-maxcontrast);
  font-style: italic;
}

.tree-item-content--placeholder:hover {
  background: none;
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

/* 16px i.p.v. 24px: in het 320px-paneel eet inspringing de titelbreedte op.
   Op niveau 3+ scheelt dit 4 à 5 tekens, terwijl de hiërarchie afleesbaar
   blijft — de chevrons markeren de niveaus al. */
.tree-children {
  list-style: none;
  margin: 0;
  padding: 0 0 0 16px;
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
