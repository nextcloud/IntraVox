<template>
  <!-- Twee varianten: 'modal' (publieke share-view) en 'panel' (vast linker-
       paneel als inhoudsopgave in de app). Zelfde boom en beheer-logica. -->
  <component
    :is="isPanel ? 'aside' : 'NcModal'"
    :class="isPanel ? 'page-tree-panel' : null"
    v-bind="isPanel ? { 'aria-label': modalTitle } : { name: modalTitle, size: 'normal' }"
    @close="$emit('close')">
    <div class="page-tree-content" :class="{ 'is-panel': isPanel }">
      <div v-if="isPanel" class="panel-header">
        <h2 class="panel-title">{{ modalTitle }}</h2>
        <button class="panel-close"
                @click="$emit('close')"
                :aria-label="t('intravox', 'Close')"
                :title="t('intravox', 'Close')">
          <Close :size="20" />
        </button>
      </div>
      <!-- Twee weergaven in het paneel: de paginaboom en de inhoudsopgave van
           de huidige pagina. Alleen in paneelmodus — de modal toont altijd de boom. -->
      <!-- Volledig ARIA-tabspatroon: role=tab hoort aria-controls te hebben en
           te wijzen op een role=tabpanel, en de pijltjestoetsen horen tussen de
           tabs te bewegen terwijl Tab naar de inhoud springt (roving tabindex).
           Zonder dat leest een schermlezer wel "tab", maar klopt de belofte niet. -->
      <div v-if="isPanel"
           class="panel-tabs"
           role="tablist"
           :aria-label="t('intravox', 'Page structure views')"
           @keydown="onTabKeydown">
        <button id="intravox-tab-pages"
                ref="tabPages"
                class="panel-tab"
                :class="{ 'is-active': activeTab === 'pages' }"
                role="tab"
                aria-controls="intravox-tabpanel-pages"
                :aria-selected="activeTab === 'pages' ? 'true' : 'false'"
                :tabindex="activeTab === 'pages' ? 0 : -1"
                @click="setActiveTab('pages')">
          {{ t('intravox', 'Pages') }}
        </button>
        <button id="intravox-tab-toc"
                ref="tabToc"
                class="panel-tab"
                :class="{ 'is-active': activeTab === 'toc' }"
                role="tab"
                aria-controls="intravox-tabpanel-toc"
                :aria-selected="activeTab === 'toc' ? 'true' : 'false'"
                :tabindex="activeTab === 'toc' ? 0 : -1"
                @click="setActiveTab('toc')">
          {{ t('intravox', 'On this page') }}
        </button>
      </div>

      <div v-if="isPanel"
           v-show="activeTab === 'toc'"
           id="intravox-tabpanel-toc"
           class="panel-scroll-area"
           role="tabpanel"
           aria-labelledby="intravox-tab-toc">
        <PageToc :page-key="tocPageKey"
                 :current-page-id="currentPageId" />
      </div>

      <!-- Modal: hint en beheerknop bovenaan. In de paneelvariant staan ze in
           een stille voettekst onderaan, zodat de boom zelf bovenaan begint. -->
      <div v-show="!isPanel || activeTab === 'pages'"
           class="tree-view"
           :id="isPanel ? 'intravox-tabpanel-pages' : null"
           :role="isPanel ? 'tabpanel' : null"
           :aria-labelledby="isPanel ? 'intravox-tab-pages' : null">
      <!-- In beheermodus vertelt de waarschuwing hieronder het verhaal al;
           de algemene uitleg is dan dubbelop. -->
      <CollapsibleHint v-if="!isPanel && !manageMode && !loading && !error && tree.length > 0"
                       :summary="t('intravox', 'About the page structure')">
        {{ t('intravox', 'This shows all your actual pages. Use "Manage structure" to move, reorder, copy or delete them. Only top-level pages can be set as the homepage; to make a subpage the homepage, move it to the top level first. To change the links in the navigation bar and their order, use "Edit navigation".') }}
      </CollapsibleHint>

      <NcNoteCard v-if="manageMode" type="warning">
        {{ t('intravox', 'Changes here move and rename the actual pages and their folders. This is different from "Edit navigation", which only changes the links in the navigation bar.') }}
      </NcNoteCard>

      <!-- Opent de modal al ín beheermodus (vanuit het paneel), dan sluit Done
           de modal; anders schakelt hij de beheermodus uit. -->
      <div v-if="!isPanel && canManageAny && !loading && !error && tree.length > 0"
           class="page-tree-toolbar">
        <NcButton :type="manageMode ? 'primary' : 'tertiary'"
                  @click="toggleManageMode">
          <template #icon>
            <Check v-if="manageMode" :size="20" />
            <Cog v-else :size="20" />
          </template>
          {{ manageMode ? t('intravox', 'Done') : t('intravox', 'Manage structure') }}
        </NcButton>
      </div>

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
            :parent-can-create="rootCanCreate"
            :homepage-unique-id="homepageUniqueId"
            :is-first="idx === 0"
            :is-last="idx === tree.length - 1"
            @toggle="toggleNode"
            @navigate="handleNavigate"
            @move-up="handleMoveUp"
            @move-down="handleMoveDown"
            @move-to="handleMoveTo"
            @rename="handleRename"
            @delete="handleDelete"
            @copy="handleCopy"
            @set-homepage="handleSetHomepage"
          />
        </ul>
      </div>

      <!-- Stille voettekst: alleen de ingang naar beheren. De uitleg staat in
           de beheermodal zelf, waar hij hoort bij de acties die hij beschrijft. -->
      <div v-if="isPanel && canManageAny && !loading && !error && tree.length > 0"
           class="panel-footer">
        <!-- Beheren opent een modal: de actieknoppen per pagina passen niet
             in een 320px-paneel zonder de titels te verdringen -->
        <button class="panel-footer__action" @click="$emit('manage')">
          <Cog :size="16" />
          <span>{{ t('intravox', 'Manage structure') }}</span>
        </button>
      </div>
      </div><!-- /.tree-view -->
    </div>
  </component>
</template>

<script>
import { translate } from '@nextcloud/l10n';
import { NcModal, NcLoadingIcon, NcButton, NcNoteCard, NcCheckboxRadioSwitch } from '@nextcloud/vue';
import CollapsibleHint from './CollapsibleHint.vue';
import Cog from 'vue-material-design-icons/Cog.vue';
import Close from 'vue-material-design-icons/Close.vue';
import Check from 'vue-material-design-icons/Check.vue';
import axios from '@nextcloud/axios';
import { generateUrl } from '@nextcloud/router';
import PageTreeItem from './PageTreeItem.vue';
import PageTreeSelect from './PageTreeSelect.vue';
import PageToc from './PageToc.vue';

// Onthoudt welke weergave het paneel toonde, in de stijl van
// 'intravox:page-tree-open' in App.vue
const TAB_STORAGE_KEY = 'intravox:page-panel-tab';

export default {
  name: 'PageTreeModal',
  components: {
    NcModal,
    NcLoadingIcon,
    NcButton,
    NcNoteCard,
    NcCheckboxRadioSwitch,
    CollapsibleHint,
    PageTreeItem,
    PageTreeSelect,
    PageToc,
    Cog,
    Close,
    Check
  },
  props: {
    /**
     * 'modal' (default) toont de boom in een NcModal; 'panel' rendert hem als
     * vast linkerpaneel dat open blijft tijdens navigeren (inhoudsopgave).
     */
    variant: {
      type: String,
      default: 'modal',
      validator: (value) => ['modal', 'panel'].includes(value)
    },
    // Opent de modal direct in beheermodus (vanuit "Manage structure" in het paneel)
    startInManageMode: {
      type: Boolean,
      default: false
    },
    // Verandert wanneer de gerenderde pagina wijzigt; laat de inhoudsopgave
    // opnieuw scannen
    tocPageKey: {
      type: String,
      default: ''
    },
    // Bewerkmodus: koppen krijgen dan geen anker-id, dus de inhoudsopgave zou
    // leeg zijn. Het paneel valt in dat geval terug op de paginaboom.
    forcePagesTab: {
      type: Boolean,
      default: false
    },
    currentPageId: {
      type: String,
      default: null
    },
    /**
     * Language tree to show. Follows the language of the page being VIEWED,
     * not the viewer's profile language — the 1.9.6 rule ('the structure you
     * are looking at decides') applied to reading. Without this, opening the
     * structure while on a French page showed the English tree, and a freshly
     * created French translation was nowhere to be found in it.
     */
    language: {
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
  emits: ['close', 'navigate', 'reorder', 'move', 'rename', 'delete', 'copy', 'set-homepage', 'homepage', 'manage'],
  data() {
    return {
      tree: [],
      loading: true,
      error: null,
      expandedNodes: new Set(),
      homepageUniqueId: null,
      rootPermissions: {},
      movingNode: null,
      moveTargetId: null,
      moveToRoot: false,
      moveInProgress: false,
      manageMode: this.startInManageMode,
      activeTab: this.readStoredTab()
    };
  },
  computed: {
    isPanel() {
      return this.variant === 'panel';
    },
    modalTitle() {
      return this.t('intravox', 'Page structure');
    },
    // Show "Manage structure" when the user can act on ANY page in the tree —
    // not only when they have write on the root. A department editor with write
    // on just their own section must still get the manage toolbar so they can
    // reorder/rename/delete within that section (issue #86). Falls back to the
    // root-level canManage prop when the tree carries no permission data.
    canManageAny() {
      if (this.canManage) {
        return true;
      }
      const anyManageable = (nodes) => (nodes || []).some((n) => {
        const p = n.permissions || {};
        if (p.canWrite || p.canCreate || p.canDelete) {
          return true;
        }
        return anyManageable(n.children);
      });
      return anyManageable(this.tree);
    },
    // Create-permission on the language root — where a sibling copy of a
    // top-level page lands. Gates the Copy button on root-level tree items (#86).
    rootCanCreate() {
      return !!this.rootPermissions.canCreate;
    }
  },
  watch: {
    // Navigating from inside the tree can land on a page in another language;
    // the tree must swap along with it rather than keep showing the old one.
    language() {
      this.loadTree();
    },
    // In paneelmodus blijft de component gemonteerd tijdens navigeren; de
    // markering van de huidige pagina moet dan client-side meebewegen.
    currentPageId() {
      this.markCurrentPage();
    },
    // Bewerken maakt de inhoudsopgave onbruikbaar (geen anker-ids), dus terug
    // naar de boom. De opgeslagen voorkeur blijft staan voor na het bewerken.
    forcePagesTab: {
      immediate: true,
      handler(force) {
        if (force) {
          this.activeTab = 'pages';
        } else {
          this.activeTab = this.readStoredTab();
        }
      }
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
        const params = {};
        if (this.currentPageId) {
          params.currentPageId = this.currentPageId;
        }
        if (this.language) {
          params.language = this.language;
        }
        let url;
        if (this.shareToken) {
          url = generateUrl('/apps/intravox/api/share/{token}/tree', { token: this.shareToken });
        } else {
          url = generateUrl('/apps/intravox/api/pages/tree');
        }
        const response = await axios.get(url, { params });
        this.tree = response.data.tree || [];
        this.homepageUniqueId = response.data.homepageUniqueId || null;
        this.rootPermissions = response.data.rootPermissions || {};
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
      // Het paneel is een inhoudsopgave: het blijft open tot de gebruiker het
      // zelf sluit. Alleen de modal sluit na navigeren.
      if (!this.isPanel) {
        this.$emit('close');
      }
    },
    readStoredTab() {
      try {
        return window.localStorage.getItem(TAB_STORAGE_KEY) === 'toc' ? 'toc' : 'pages';
      } catch (e) {
        return 'pages';
      }
    },
    onTabKeydown(event) {
      // WAI-ARIA tabs: pijltjes wisselen van tab, Home/End springen naar de
      // uiterste. Tab zelf verlaat de balk richting de inhoud.
      const volgorde = ['pages', 'toc'];
      const huidig = volgorde.indexOf(this.activeTab);
      let doel = null;
      if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
        doel = volgorde[(huidig + 1) % volgorde.length];
      } else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
        doel = volgorde[(huidig - 1 + volgorde.length) % volgorde.length];
      } else if (event.key === 'Home') {
        doel = volgorde[0];
      } else if (event.key === 'End') {
        doel = volgorde[volgorde.length - 1];
      }
      if (!doel) return;
      event.preventDefault();
      this.setActiveTab(doel);
      this.$nextTick(() => {
        const ref = doel === 'pages' ? this.$refs.tabPages : this.$refs.tabToc;
        ref?.focus();
      });
    },
    setActiveTab(tab) {
      this.activeTab = tab;
      try {
        window.localStorage.setItem(TAB_STORAGE_KEY, tab);
      } catch (e) {
        // Zonder storage (private mode) geldt de keuze alleen deze sessie
      }
    },
    toggleManageMode() {
      // Opende de modal speciaal om te beheren, dan is beheren klaar = modal
      // dicht. Anders valt hij terug op de gewone boomweergave.
      if (this.manageMode && this.startInManageMode) {
        this.$emit('close');
        return;
      }
      this.manageMode = !this.manageMode;
    },
    markCurrentPage() {
      const mark = (nodes) => {
        for (const node of nodes || []) {
          node.isCurrent = node.uniqueId === this.currentPageId;
          mark(node.children);
        }
      };
      mark(this.tree);
      this.expandPathToCurrent(this.tree);
      this.expandedNodes = new Set(this.expandedNodes);
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
    handleRename(node) {
      this.$emit('rename', node);
    },
    handleDelete(node) {
      this.$emit('delete', node);
    },
    handleCopy(payload) {
      // payload = { item, parentId } — forward so the parent copies the page as
      // a sibling (into parentId), where create-permission was already checked.
      this.$emit('copy', payload);
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

/* --- Paneel-variant: vaste linkerkolom als inhoudsopgave --- */
.page-tree-panel {
  width: 320px;
  flex-shrink: 0;
  box-sizing: border-box;
  background: var(--color-main-background);
  border-right: 1px solid var(--color-border);
  /* Plakt onder de sticky topbar; --intravox-topbar-height wordt door App.vue
     gezet omdat de topbar-hoogte varieert (editmodus, smalle schermen) */
  position: sticky;
  top: var(--intravox-topbar-height, 0px);
  align-self: flex-start;
  /* dvh volgt de in- en uitschuivende browserbalk op mobiel; de vh-regel
     erboven is de fallback voor browsers zonder dvh. Deze kolom is weliswaar
     de brede variant, maar een tablet in liggende stand haalt 1025px ook. */
  max-height: calc(100vh - var(--header-height, 50px) - var(--intravox-topbar-height, 0px));
  max-height: calc(100dvh - var(--header-height, 50px) - var(--intravox-topbar-height, 0px));
  overflow-y: auto;
  /* Het paneel is hier zelf de scrollende laag, dus ook hier geen doorslaande
     swipe naar de pagina eronder. */
  overscroll-behavior: contain;
}

.page-tree-panel .page-tree-content {
  padding: 12px 16px 16px;
}

/* Op desktop scrollt het PANEEL zelf, dus de binnenste lijst mag geen tweede
   scrollbalk krijgen. Bewust begrensd tot >=1025px: onder die breedte haalt
   de media query hieronder de max-height van het paneel weg, en dan is dit
   de enige laag die nog kan scrollen. */
@media (min-width: 1025px) {
  .page-tree-content.is-panel .page-tree {
    max-height: none;
    overflow-y: visible;
  }
}

/* Voettekst: beheer + uitleg, visueel ondergeschikt aan de boom */
.panel-footer {
  margin-top: 16px;
  padding-top: 12px;
  border-top: 1px solid var(--color-border);
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 4px;
  font-size: 13px;
}

.panel-footer__action {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 4px 0;
  background: none;
  border: none;
  color: var(--color-text-maxcontrast);
  font-size: 13px;
  cursor: pointer;
}

.panel-footer__action:hover {
  color: var(--color-main-text);
}

.panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 8px;
  /* Blijft in beeld terwijl een lange boom scrollt */
  position: sticky;
  top: 0;
  z-index: 1;
  background: var(--color-main-background);
  padding-bottom: 4px;
}

/* Uitgelijnd op de kop van NcAppSidebar (de Details-sidebar rechts), zodat de
   twee zijpanelen naast elkaar dezelfde typografie hebben: 20px, heading-gewicht,
   normale tekstkleur. */
.panel-title {
  margin: 0;
  padding: 7px 0;
  font-size: 20px;
  font-weight: var(--font-weight-heading, bold);
  color: var(--color-main-text);
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.panel-close {
  display: flex;
  align-items: center;
  justify-content: center;
  /* Zelfde klikoppervlak en ronding als de sluitknop van NcAppSidebar */
  width: var(--default-clickable-area, 44px);
  height: var(--default-clickable-area, 44px);
  padding: 0;
  background: none;
  border: none;
  border-radius: var(--border-radius-element, var(--border-radius));
  color: var(--color-main-text);
  cursor: pointer;
  flex-shrink: 0;
  transition: background-color 0.1s ease;
}

.panel-close:hover {
  background-color: var(--color-background-hover);
}

/* Tabbalk: paginaboom vs inhoudsopgave van de huidige pagina.
   Bewust zonder iconen, anders dan de tabs van de Details-sidebar: dit zijn
   twee wéérgaven van hetzelfde paneel (een schakelaar), geen vier losse
   inhoudssecties. De vórm volgt wel de sidebar-tabs — knopvlak met lichte
   achtergrond als actieve staat — zodat beide panelen dezelfde taal spreken. */
.panel-tabs {
  display: flex;
  gap: 4px;
  margin-bottom: 8px;
}

.panel-tab {
  flex: 1;
  padding: 8px;
  background: none;
  border: none;
  border-radius: var(--border-radius-element, var(--border-radius));
  color: var(--color-text-maxcontrast);
  font-size: 14px;
  cursor: pointer;
}

.panel-tab:hover {
  background-color: var(--color-background-hover);
  color: var(--color-main-text);
}

.panel-tab.is-active {
  background-color: var(--color-primary-element-light);
  color: var(--color-main-text);
  font-weight: 600;
}

/* Smal scherm: paneel als overlay over de content i.p.v. induwen */
@media (max-width: 1024px) {
  .page-tree-panel {
    position: fixed;
    left: 0;
    top: calc(var(--header-height, 50px) + var(--intravox-topbar-height, 0px));
    bottom: 0;
    width: min(320px, 85vw);
    z-index: 2000;
    box-shadow: 4px 0 16px var(--color-box-shadow);

    /* Een fixed box met top EN bottom legt zijn hoogte NIET vast zodra de
       inhoud groter is: die wint, en de box groeit door tot voorbij het
       scherm. Gemeten op 390x844: paneel 2004px hoog, scrollHeight ook
       2004px, dus geen overflow en niets te scrollen.

       HEIGHT en niet max-height, want met alleen een maximum krimpt het
       paneel juist mee met korte inhoud: op de tab "Op deze pagina" bleef
       er dan een lege witte strook van 178px over. Een vaste hoogte houdt
       het paneel op beide tabs even groot.

       dvh volgt de in- en uitschuivende adresbalk van Safari/iOS; de
       vh-regel erboven is de fallback voor browsers zonder dvh. */
    height: calc(100vh - var(--header-height, 50px) - var(--intravox-topbar-height, 0px));
    height: calc(100dvh - var(--header-height, 50px) - var(--intravox-topbar-height, 0px));

    /* Niet het hele paneel laten scrollen maar de lijst erin, zodat de
       titel, de tabs en de sluitknop in beeld blijven. */
    display: flex;
    flex-direction: column;
    overflow: hidden;
  }

  .page-tree-panel .page-tree-content.is-panel {
    display: flex;
    flex-direction: column;
    flex: 1;
    /* Zonder min-height:0 weigert een flex-item te krimpen onder zijn
       inhoud en scrollt er alsnog niets -- het klassieke struikelblok. */
    min-height: 0;
  }

  /* De twee tabpanels zijn .tree-view (paginaboom) en .panel-scroll-area
     (inhoudsopgave). Die moeten meekrimpen, niet alleen de lijst erin:
     .tree-view zit als display:block-wrapper tussen de content en de
     .page-tree, en groeide zonder deze regel gewoon door tot 1872px --
     dan heeft de lijst eronder niets om tegen af te kappen. Gemeten. */
  .page-tree-content.is-panel .tree-view,
  .page-tree-content.is-panel .panel-scroll-area {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 0;
  }

  /* En dit is wat er daadwerkelijk scrollt. */
  .page-tree-content.is-panel .page-tree,
  .page-tree-content.is-panel .panel-scroll-area {
    max-height: none;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    /* Zonder dit slaat een swipe aan het einde van de lijst door naar de
       pagina ACHTER de overlay: die scrollt dan weg terwijl de boom stil
       blijft staan. contain houdt de swipe binnen dit element. */
    overscroll-behavior: contain;
  }

  .page-tree-content.is-panel .page-tree {
    flex: 1;
    min-height: 0;
  }
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
