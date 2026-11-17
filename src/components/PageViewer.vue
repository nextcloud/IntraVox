<template>
  <div class="page-viewer-wrapper">
    <!-- Header Row (spans full width above everything) -->
    <div v-if="hasHeaderRow"
         class="header-row"
         :style="getHeaderRowStyle()">
      <Widget
        v-for="widget in getHeaderRowWidgets()"
        :key="widget.id || widget.order"
        :widget="widget"
        :page-id="page.uniqueId"
        :anchor-id="anchorIdForWidget(widget)"
        :editable="false"
        :row-background-color="page.layout.headerRow.backgroundColor || ''"
        :share-token="shareToken"
        @navigate="$emit('navigate', $event)"
      />
    </div>

  <div class="page-viewer-container"
       :class="{ 'has-left-column': hasLeftColumn, 'has-right-column': hasRightColumn }">
    <!-- Left Side Column -->
    <div v-if="hasLeftColumn"
         class="side-column side-column-left"
         :style="getSideColumnStyle('left')">
      <Widget
        v-for="widget in getSideColumnWidgets('left')"
        :key="widget.id || widget.order"
        :widget="widget"
        :page-id="page.uniqueId"
        :anchor-id="anchorIdForWidget(widget)"
        :editable="false"
        :row-background-color="page.layout.sideColumns.left.backgroundColor || ''"
        :share-token="shareToken"
        @navigate="$emit('navigate', $event)"
      />
    </div>

    <!-- Main Content -->
    <div class="page-viewer">
      <div
        v-for="(row, rowIndex) in page.layout.rows"
        :key="rowIndex"
        class="page-row"
        :class="{ 'collapsible-row': row.collapsible, 'has-background': !!row.backgroundColor }"
        :style="getRowStyle(row)"
      >
        <!-- Section Header (only for collapsible rows) -->
        <div
          v-if="row.collapsible"
          class="row-section-header"
          :class="{ collapsed: isRowCollapsed(row) }"
          @click="toggleRowCollapsed(row)"
        >
          <ChevronDown v-if="!isRowCollapsed(row)" :size="20" class="section-chevron" />
          <ChevronRight v-else :size="20" class="section-chevron" />
          <!-- TRANSLATORS: Noun: fallback title of a collapsible section -->
          <span class="section-title">{{ row.sectionTitle || t('intravox', 'Section') }}</span>
        </div>

        <!-- Row Content (collapsible) -->
        <div
          class="row-content"
          :class="{ collapsed: row.collapsible && isRowCollapsed(row) }"
          v-show="!row.collapsible || !isRowCollapsed(row)"
        >
          <div
            class="page-grid"
            :style="{ gridTemplateColumns: `repeat(${(row.columns || page.layout.columns) ?? 1}, minmax(0, 1fr))` }"
          >
            <div
              v-for="column in ((row.columns || page.layout.columns) ?? 1)"
              :key="column"
              class="page-column"
            >
              <Widget
                v-for="widget in getWidgetsForColumn(row, column)"
                :key="widget.order"
                :widget="widget"
                :page-id="page.uniqueId"
                :anchor-id="anchorIdForWidget(widget)"
                :editable="false"
                :row-background-color="row.backgroundColor || ''"
                :share-token="shareToken"
                @navigate="$emit('navigate', $event)"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Side Column -->
    <div v-if="hasRightColumn"
         class="side-column side-column-right"
         :style="getSideColumnStyle('right')">
      <Widget
        v-for="widget in getSideColumnWidgets('right')"
        :key="widget.id || widget.order"
        :widget="widget"
        :page-id="page.uniqueId"
        :anchor-id="anchorIdForWidget(widget)"
        :editable="false"
        :row-background-color="page.layout.sideColumns.right.backgroundColor || ''"
        :share-token="shareToken"
        @navigate="$emit('navigate', $event)"
      />
    </div>
  </div>

    <!-- Reactions & Comments Section (full width, below all columns) -->
    <div v-if="showReactions || showComments" class="reactions-comments-section">
      <ReactionBar
        v-if="showReactions"
        :page-id="page.uniqueId"
        :reactions="pageReactions"
        :user-reactions="userReactions"
        @update="handleReactionsUpdate"
      />
      <CommentSection
        v-if="showComments"
        :page-id="page.uniqueId"
        :allow-comment-reactions="allowCommentReactions"
      />
    </div>
  </div>
</template>

<script>
import Widget from './Widget.vue';
import ReactionBar from './reactions/ReactionBar.vue';
import CommentSection from './reactions/CommentSection.vue';
import { getPageReactions } from '../services/CommentService.js';
import { makeUniqueAnchorId, scrollToHashAnchor } from '../utils/headingAnchors.js';
import ChevronDown from 'vue-material-design-icons/ChevronDown.vue';
import ChevronRight from 'vue-material-design-icons/ChevronRight.vue';
import { translate } from '@nextcloud/l10n';

export default {
  name: 'PageViewer',
  components: {
    Widget,
    ReactionBar,
    CommentSection,
    ChevronDown,
    ChevronRight
  },
  props: {
    page: {
      type: Object,
      required: true
    },
    engagementSettings: {
      type: Object,
      default: () => ({
        allowPageReactions: true,
        allowComments: true,
        allowCommentReactions: true,
        singleReactionPerUser: true
      })
    },
    shareToken: {
      type: String,
      default: ''
    },
    isPublic: {
      type: Boolean,
      default: false
    },
    hubPage: {
      type: Boolean,
      default: false
    }
  },
  emits: ['navigate', 'rows-changed'],
  data() {
    return {
      pageReactions: {},
      userReactions: [],
      collapsedRows: {} // Track collapsed state for collapsible rows in view mode
    };
  },
  computed: {
    isHubPage() {
      return this.hubPage || this.page?.hidePageTitleBar === true;
    },
    socialExplicitlyDisabled() {
      const value = Object.prototype.hasOwnProperty.call(this.page || {}, 'disableSocial')
        ? this.page.disableSocial
        : this.page?.settings?.disableSocial;
      return value === true;
    },
    socialExplicitlyEnabled() {
      const value = Object.prototype.hasOwnProperty.call(this.page || {}, 'disableSocial')
        ? this.page.disableSocial
        : this.page?.settings?.disableSocial;
      return value === false;
    },
    hasHeaderRow() {
      return this.page?.layout?.headerRow?.enabled &&
             this.page.layout.headerRow.widgets?.length > 0;
    },
    hasLeftColumn() {
      return this.page?.layout?.sideColumns?.left?.enabled &&
             this.page.layout.sideColumns.left.widgets?.length > 0;
    },
    hasRightColumn() {
      return this.page?.layout?.sideColumns?.right?.enabled &&
             this.page.layout.sideColumns.right.widgets?.length > 0;
    },
    /**
     * Map every stand-alone heading widget on the page to a unique `h-<slug>`
     * anchor id, in a stable render order (header → main rows → side columns).
     * Keyed by widget identity so lookups are cheap and stable across re-renders.
     * Headings inside text blocks get their ids from the markdown serializer.
     */
    headingAnchorIds() {
      const nextId = makeUniqueAnchorId();
      const map = new Map();
      const layout = this.page?.layout;
      if (!layout) return map;
      const assign = (widgets) => {
        (widgets || []).forEach((w) => {
          if (w && w.type === 'heading') {
            map.set(w, nextId(w.content || ''));
          }
        });
      };
      // Header row first, then main-content rows in visual order, then side columns.
      assign(layout.headerRow?.widgets);
      (layout.rows || []).forEach((row) => assign(row.widgets));
      assign(layout.sideColumns?.left?.widgets);
      assign(layout.sideColumns?.right?.widgets);
      return map;
    },
    /**
     * Check if reactions are allowed on this page
     * Takes into account both global settings and page-level overrides
     */
    showReactions() {
      // Global setting off = no reactions anywhere
      if (!this.engagementSettings.allowPageReactions || this.socialExplicitlyDisabled) {
        return false;
      }
      // Check page-level override
      const pageSetting = this.page?.settings?.allowReactions;
      if (pageSetting === false) {
        return false;
      }
      if ((this.isHubPage || this.page?.path?.toLowerCase().endsWith('/home.json')) && pageSetting !== true && !this.socialExplicitlyEnabled) return false;
      return true;
    },
    /**
     * Check if comments are allowed on this page
     * Takes into account both global settings and page-level overrides
     */
    showComments() {
      // Global setting off = no comments anywhere
      if (!this.engagementSettings.allowComments || this.socialExplicitlyDisabled) {
        return false;
      }
      // Check page-level override
      const pageSetting = this.page?.settings?.allowComments;
      if (pageSetting === false) {
        return false;
      }
      if ((this.isHubPage || this.page?.path?.toLowerCase().endsWith('/home.json')) && pageSetting !== true && !this.socialExplicitlyEnabled) return false;
      return true;
    },
    /**
     * Check if reactions on comments are allowed
     * Takes into account both global settings and page-level overrides
     */
    allowCommentReactions() {
      // Global setting off = no comment reactions anywhere
      if (!this.engagementSettings.allowCommentReactions) {
        return false;
      }
      // Check page-level override
      const pageSetting = this.page?.settings?.allowCommentReactions;
      if (pageSetting === false) {
        return false;
      }
      // Default: allow comment reactions
      return true;
    }
  },
  methods: {
    t(app, text, vars) {
      return translate(app, text, vars);
    },
    getWidgetsForColumn(row, column) {
      if (!row.widgets) return [];

      // Filter widgets by column and sort by order
      return row.widgets
        .filter(w => w.column === column)
        .sort((a, b) => a.order - b.order);
    },
    isRowCollapsed(row) {
      // Check if this collapsible row is currently collapsed in view mode
      if (!row.collapsible) return false;
      // Use row.id as key, fallback to row's index position
      const rowKey = row.id || `row-${this.page.layout.rows.indexOf(row)}`;
      // Use stored state if available, otherwise use defaultCollapsed setting
      return this.collapsedRows[rowKey] ?? row.defaultCollapsed ?? false;
    },
    toggleRowCollapsed(row) {
      if (!row.collapsible) return;
      const rowKey = row.id || `row-${this.page.layout.rows.indexOf(row)}`;
      // Get current state (from stored state, or defaultCollapsed, or false)
      const currentState = this.collapsedRows[rowKey] ?? row.defaultCollapsed ?? false;
      this.collapsedRows = {
        ...this.collapsedRows,
        [rowKey]: !currentState
      };
      // De inhoudsopgave toont alleen zichtbare koppen; open/dichtklappen
      // verandert die verzameling.
      this.$emit('rows-changed');
    },
    getSideColumnWidgets(side) {
      const sideColumn = this.page?.layout?.sideColumns?.[side];
      if (!sideColumn || !sideColumn.widgets) return [];
      return [...sideColumn.widgets].sort((a, b) => (a.order || 0) - (b.order || 0));
    },
    getRowStyle(row) {
      const style = {};

      if (row.backgroundColor) {
        style.backgroundColor = row.backgroundColor;

        // Set text color based on background color
        // Only Primary (dark green) needs white text
        if (row.backgroundColor === 'var(--color-primary-element)') {
          style.color = 'var(--color-primary-element-text)';
        } else {
          // Light backgrounds: use default dark text
          style.color = 'var(--color-main-text)';
        }
      }

      return style;
    },
    getSideColumnStyle(side) {
      const sideColumn = this.page?.layout?.sideColumns?.[side];
      if (!sideColumn) return {};

      const style = {};

      if (sideColumn.backgroundColor) {
        style.backgroundColor = sideColumn.backgroundColor;

        // Set text color based on background color
        if (sideColumn.backgroundColor === 'var(--color-primary-element)') {
          style.color = 'var(--color-primary-element-text)';
        } else {
          style.color = 'var(--color-main-text)';
        }
      } else {
        // Explicitly set transparent to override CSS default
        style.backgroundColor = 'transparent';
      }

      return style;
    },
    getHeaderRowWidgets() {
      const headerRow = this.page?.layout?.headerRow;
      if (!headerRow || !headerRow.widgets) return [];
      return [...headerRow.widgets].sort((a, b) => (a.order || 0) - (b.order || 0));
    },
    getHeaderRowStyle() {
      const headerRow = this.page?.layout?.headerRow;
      if (!headerRow) return {};

      const style = {};

      if (headerRow.backgroundColor) {
        style.backgroundColor = headerRow.backgroundColor;

        if (headerRow.backgroundColor === 'var(--color-primary-element)') {
          style.color = 'var(--color-primary-element-text)';
        } else {
          style.color = 'var(--color-main-text)';
        }
      } else {
        // Explicitly set transparent to override CSS default
        style.backgroundColor = 'transparent';
      }

      return style;
    },
    async loadReactions() {
      if (!this.page?.uniqueId) return;
      try {
        const result = await getPageReactions(this.page.uniqueId);
        this.pageReactions = result.reactions || {};
        this.userReactions = result.userReactions || [];
      } catch (error) {
        console.error('Failed to load reactions:', error);
      }
    },
    handleReactionsUpdate(result) {
      this.pageReactions = result.reactions || {};
      this.userReactions = result.userReactions || [];
    },
    /** Anchor id for a stand-alone heading widget, or '' if none. */
    anchorIdForWidget(widget) {
      return this.headingAnchorIds.get(widget) || '';
    }
  },
  mounted() {
    // Honour a `#h-…` section anchor on the initial (deep-linked) load.
    this.$nextTick(() => scrollToHashAnchor());
  },
  watch: {
    'page.uniqueId': {
      immediate: true,
      handler() {
        this.loadReactions();
      }
    },
    // After navigating to another page, re-check the fragment so
    // `?page=…#h-section` deep links scroll once the new content is in the DOM.
    'page': {
      handler() {
        this.$nextTick(() => scrollToHashAnchor());
      }
    }
  }
};
</script>

<style scoped>
.page-viewer-wrapper {
  display: flex;
  flex-direction: column;
  width: 100%;
}

.page-viewer-container {
  display: flex;
  gap: 16px;
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
}

.page-viewer {
  flex: 1;
  min-width: 0;
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
}

/* Header Row Styles */
.header-row {
  width: 100%;
  padding: 16px;
  margin-bottom: 16px;
  border-radius: var(--border-radius-container-large);
  /* No default background - controlled by inline style via getHeaderRowStyle() */
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.side-column {
  flex-shrink: 0;
  width: 250px;
  min-width: 200px;
  max-width: 300px;
  padding: 16px;
  border-radius: var(--border-radius-container-large);
  /* No default background - controlled by inline style via getSideColumnStyle() */
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  gap: 12px;
  align-self: flex-start;
}

.page-row {
  margin-bottom: 12px;
  /* Binnenmarge zit op .row-content; hier padding geeft dubbele witruimte */
  padding: 0;
  border-radius: var(--border-radius-container-large);
  box-sizing: border-box;
  /* Without these, a wide child (table with min-content larger than its
     column track) can blow the row past the viewport. min-width: 0 lets
     the row shrink below its min-content, max-width: 100% keeps it inside
     its parent. */
  min-width: 0;
  max-width: 100%;
}

/* Collapsible row styling */
.page-row.collapsible-row {
  overflow: hidden;
}

.row-section-header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  background: rgba(0, 0, 0, 0.1);
  cursor: pointer;
  user-select: none;
  transition: background-color 0.2s ease;
  color: inherit;
}

.row-section-header:hover {
  background: rgba(0, 0, 0, 0.15);
}

.row-section-header .section-chevron {
  flex-shrink: 0;
  color: inherit;
  opacity: 0.7;
  transition: transform 0.2s ease;
}

.row-section-header .section-title {
  font-weight: 600;
  font-size: 1.1em;
  color: inherit;
}

/* Collapsed state header styling */
.row-section-header.collapsed {
  border-radius: var(--border-radius-container-large);
}

/* Row content container */
.row-content {
  /* Kale rijen lopen als documentflow: alleen horizontale padding, zodat
     tekst uitlijnt met tekst in rijen mét achtergrondkleur */
  padding: 0 16px;
  min-width: 0;
  max-width: 100%;
  box-sizing: border-box;
}

/* Rijen met achtergrond of sectieheader zijn visueel een blok en houden
   hun binnenmarge */
.page-row.has-background .row-content,
.page-row.collapsible-row .row-content {
  padding: 16px;
}

.page-row.collapsible-row .row-content {
  padding-top: 12px;
}

.row-content.collapsed {
  display: none;
}

.page-grid {
  display: grid;
  gap: 12px;
  width: 100%;
  box-sizing: border-box;
  align-items: start;
}

.page-column {
  box-sizing: border-box;
  min-width: 0;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

/* Reactions & Comments Section */
.reactions-comments-section {
  margin-top: 24px;
  padding: 0 16px;
  width: 100%;
  max-width: 100%;
}

/* Mobile styles */
@media (max-width: 768px) {
  .page-viewer-wrapper {
    padding: 0 8px;
  }

  .page-viewer-container {
    flex-direction: column;
  }

  .header-row {
    border-radius: var(--border-radius-container-large);
  }

  .side-column {
    width: 100%;
    min-width: 100%;
    max-width: 100%;
    order: 1; /* Side columns go below main content on mobile */
    border-radius: var(--border-radius-container-large);
  }

  .side-column-left {
    order: -1; /* Left column stays above on mobile */
  }

  .page-viewer {
    overflow-x: hidden; /* Prevent horizontal scroll */
    width: 100%;
    max-width: 100%;
    order: 0;
  }

  .page-row {
    margin-bottom: 16px;
    padding: 0;
    border-radius: var(--border-radius-container-large) !important;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    overflow: hidden; /* Ensures border-radius is visible */
  }

  .row-content {
    padding: 0 12px;
  }

  .page-row.has-background .row-content,
  .page-row.collapsible-row .row-content {
    padding: 12px;
  }

  .page-row.collapsible-row {
    border-radius: var(--border-radius-container-large) !important;
    overflow: hidden;
  }

  .row-section-header {
    border-radius: 0; /* Header takes full width inside row */
  }

  .row-section-header.collapsed {
    border-radius: 0; /* Parent row handles border-radius */
  }

  .page-grid {
    gap: 12px;
    grid-template-columns: 1fr !important;
    width: 100%;
    max-width: 100%;
  }

  .page-column {
    overflow-x: hidden; /* Prevent images from overflowing */
    width: 100%;
    max-width: 100%;
    min-width: 0;
  }
}
</style>
