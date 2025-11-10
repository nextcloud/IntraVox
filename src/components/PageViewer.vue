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
}

.page-row {
  margin-bottom: 30px;
  padding: 10px;
  border-radius: var(--border-radius-large);
}

.page-grid {
  display: grid;
  gap: 15px;
  width: 100%;
}

.page-column {
  min-height: 50px;
}
</style>
