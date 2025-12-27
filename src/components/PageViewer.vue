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
        :editable="false"
        :row-background-color="page.layout.headerRow.backgroundColor || ''"
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
        :editable="false"
        :row-background-color="page.layout.sideColumns.left.backgroundColor || ''"
        @navigate="$emit('navigate', $event)"
      />
    </div>

    <!-- Main Content -->
    <div class="page-viewer">
      <div
        v-for="(row, rowIndex) in page.layout.rows"
        :key="rowIndex"
        class="page-row"
        :style="getRowStyle(row)"
      >
        <div
          class="page-grid"
          :style="{ gridTemplateColumns: `repeat(${(row.columns || page.layout.columns) ?? 1}, 1fr)` }"
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
              :editable="false"
              :row-background-color="row.backgroundColor || ''"
              @navigate="$emit('navigate', $event)"
            />
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
        :editable="false"
        :row-background-color="page.layout.sideColumns.right.backgroundColor || ''"
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

export default {
  name: 'PageViewer',
  components: {
    Widget,
    ReactionBar,
    CommentSection
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
    }
  },
  emits: ['navigate'],
  data() {
    return {
      pageReactions: {},
      userReactions: []
    };
  },
  computed: {
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
     * Check if reactions are allowed on this page
     * Takes into account both global settings and page-level overrides
     */
    showReactions() {
      // Global setting off = no reactions anywhere
      if (!this.engagementSettings.allowPageReactions) {
        return false;
      }
      // Check page-level override
      const pageSetting = this.page?.settings?.allowReactions;
      if (pageSetting === false) {
        return false;
      }
      // Default: show reactions
      return true;
    },
    /**
     * Check if comments are allowed on this page
     * Takes into account both global settings and page-level overrides
     */
    showComments() {
      // Global setting off = no comments anywhere
      if (!this.engagementSettings.allowComments) {
        return false;
      }
      // Check page-level override
      const pageSetting = this.page?.settings?.allowComments;
      if (pageSetting === false) {
        return false;
      }
      // Default: show comments
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
    getWidgetsForColumn(row, column) {
      if (!row.widgets) return [];

      // Filter widgets by column and sort by order
      return row.widgets
        .filter(w => w.column === column)
        .sort((a, b) => a.order - b.order);
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
    }
  },
  watch: {
    'page.uniqueId': {
      immediate: true,
      handler() {
        this.loadReactions();
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
  background-color: var(--color-background-dark);
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
  background-color: var(--color-background-dark);
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  gap: 12px;
  align-self: flex-start;
}

.page-row {
  margin-bottom: 12px;
  padding: 16px;
  border-radius: var(--border-radius-container-large);
  box-sizing: border-box;
}

.page-grid {
  display: grid;
  gap: 12px;
  width: 100%;
  box-sizing: border-box;
}

.page-column {
  min-height: 50px;
  box-sizing: border-box;
  min-width: 0;
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
  .page-viewer-container {
    flex-direction: column;
  }

  .side-column {
    width: 100%;
    min-width: 100%;
    max-width: 100%;
    order: 1; /* Side columns go below main content on mobile */
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
    padding: 16px 8px;
    margin-left: 0;
    margin-right: 0;
    border-radius: 0;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
  }

  .page-grid {
    gap: 12px;
    grid-template-columns: 1fr !important; /* Single column on mobile */
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
