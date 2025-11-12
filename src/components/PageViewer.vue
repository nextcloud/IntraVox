<template>
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
            :page-id="page.id"
            :editable="false"
            :row-background-color="row.backgroundColor || ''"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Widget from './Widget.vue';

export default {
  name: 'PageViewer',
  components: {
    Widget
  },
  props: {
    page: {
      type: Object,
      required: true
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
    }
  }
};
</script>

<style scoped>
.page-viewer {
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
}

.page-row {
  margin-bottom: 30px;
  padding: 24px;
  border-radius: var(--border-radius-large);
  box-sizing: border-box;
}

.page-grid {
  display: grid;
  gap: 15px;
  width: 100%;
  box-sizing: border-box;
}

.page-column {
  min-height: 50px;
  box-sizing: border-box;
  min-width: 0;
}

/* Mobile styles */
@media (max-width: 768px) {
  .page-viewer {
    overflow-x: hidden; /* Prevent horizontal scroll */
    width: 100%;
    max-width: 100%;
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
