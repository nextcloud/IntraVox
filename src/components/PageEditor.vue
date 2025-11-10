<template>
  <div class="page-editor">
    <!-- Layout Controls -->
    <div class="editor-toolbar">
      <NcButton @click="addRow" type="secondary">
        <template #icon>
          <TableRowPlusAfter :size="20" />
        </template>
        {{ t('Add Row') }}
      </NcButton>
    </div>

    <!-- Page Grid -->
    <div
      v-for="(row, rowIndex) in localPage.layout.rows"
      :key="rowIndex"
      class="page-row editable"
      :style="getRowStyle(row)"
    >
      <div class="row-controls">
        <label style="margin-right: 8px;">{{ t('Row columns:') }}</label>
        <button
          v-for="n in 5"
          :key="n"
          :class="{ active: (row.columns || localPage.layout.columns) === n }"
          @click="setRowColumns(rowIndex, n)"
          class="column-button small"
          style="margin-right: 4px;"
        >
          {{ n }}
        </button>

        <!-- Background Color Picker -->
        <NcActions style="margin-left: 8px;">
          <template #icon>
            <Palette :size="20" />
          </template>
          <NcActionButton v-for="color in backgroundColors"
                          :key="color.value"
                          @click="setRowBackgroundColor(rowIndex, color.value)">
            <template #icon>
              <div class="color-preview" :style="{ background: color.cssVar }"></div>
            </template>
            {{ color.label }}
          </NcActionButton>
        </NcActions>

        <NcButton @click="deleteRow(rowIndex)"
                  type="error"
                  :aria-label="t('Delete row')"
                  style="margin-left: 8px;">
          <template #icon>
            <Delete :size="20" />
          </template>
        </NcButton>
      </div>

      <div
        class="page-grid"
        :style="{ gridTemplateColumns: `repeat(${(row.columns || localPage.layout.columns) ?? 1}, 1fr)` }"
      >
        <div
          v-for="column in ((row.columns || localPage.layout.columns) ?? 1)"
          :key="`${rowIndex}-${column}`"
          class="page-column droppable"
        >
          <div class="column-label">{{ t('Column {column}', { column }) }}</div>
          <draggable
            :list="getColumnWidgets(rowIndex, column)"
            :group="{ name: 'widgets', pull: true, put: true }"
            :animation="200"
            item-key="id"
            @end="onDragEnd(rowIndex, column)"
            class="widget-drop-zone"
          >
            <template #item="{ element: widget }">
              <div class="widget-wrapper" :class="{ 'editing': focusedWidgetId === widget.id }">
                <div class="widget-controls">
                  <NcButton v-if="needsEditButton(widget.type)"
                            @click="editWidget(widget, rowIndex)"
                            type="secondary"
                            :aria-label="t('Edit widget')">
                    <template #icon>
                      <Pencil :size="20" />
                    </template>
                  </NcButton>
                  <NcButton @click="deleteWidget(rowIndex, widget.id)"
                            type="error"
                            :aria-label="t('Delete widget')">
                    <template #icon>
                      <Delete :size="20" />
                    </template>
                  </NcButton>
                </div>
                <Widget :widget="widget" :page-id="page.id" :editable="true" @update="updateWidget($event, rowIndex)" @focus="focusedWidgetId = widget.id" @blur="focusedWidgetId = null" />
              </div>
            </template>
          </draggable>
          <div class="column-add-widget">
            <NcButton @click="showWidgetPickerForColumn(rowIndex, column)" type="primary" class="add-widget-btn">
              <template #icon>
                <Plus :size="20" />
              </template>
              {{ t('Add Widget') }}
            </NcButton>
          </div>
        </div>
      </div>
    </div>

    <!-- Widget Picker Modal -->
    <WidgetPicker
      v-if="showWidgetPicker"
      @close="showWidgetPicker = false"
      @select="addWidget"
    />

    <!-- Widget Editor Modal -->
    <WidgetEditor
      v-if="editingWidget"
      :widget="editingWidget"
      :page-id="page.id"
      @close="editingWidget = null"
      @save="saveWidget"
    />

    <!-- Delete Confirmation Dialog -->
    <NcDialog
      v-if="showDeleteDialog"
      :name="deleteDialogTitle"
      @closing="cancelDelete"
    >
      <p>{{ deleteDialogMessage }}</p>
      <template #actions>
        <NcButton @click="cancelDelete" type="secondary">
          {{ t('Cancel') }}
        </NcButton>
        <NcButton @click="confirmDelete" type="error">
          {{ t('Delete') }}
        </NcButton>
      </template>
    </NcDialog>
  </div>
</template>

<script>
import draggable from 'vuedraggable';
import { translate as t } from '@nextcloud/l10n';
import { NcButton, NcDialog, NcActions, NcActionButton } from '@nextcloud/vue';
import Plus from 'vue-material-design-icons/Plus.vue';
import TableRowPlusAfter from 'vue-material-design-icons/TableRowPlusAfter.vue';
import Pencil from 'vue-material-design-icons/Pencil.vue';
import Delete from 'vue-material-design-icons/Delete.vue';
import Palette from 'vue-material-design-icons/Palette.vue';
import Widget from './Widget.vue';
import WidgetPicker from './WidgetPicker.vue';
import WidgetEditor from './WidgetEditor.vue';

export default {
  name: 'PageEditor',
  components: {
    draggable,
    NcButton,
    NcDialog,
    NcActions,
    NcActionButton,
    Plus,
    TableRowPlusAfter,
    Pencil,
    Delete,
    Palette,
    Widget,
    WidgetPicker,
    WidgetEditor
  },
  props: {
    page: {
      type: Object,
      required: true
    }
  },
  emits: ['update'],
  data() {
    return {
      localPage: JSON.parse(JSON.stringify(this.page)),
      showWidgetPicker: false,
      editingWidget: null,
      editingRowIndex: null,
      targetRowIndex: null,
      targetColumn: null,
      nextWidgetId: 1,
      columnArrays: {}, // Store separate arrays per row/column
      showDeleteDialog: false,
      deleteDialogTitle: '',
      deleteDialogMessage: '',
      deleteCallback: null,
      focusedWidgetId: null
    };
  },
  computed: {
    backgroundColors() {
      return [
        {
          label: this.t('Default (transparent)'),
          value: '',
          cssVar: 'transparent'
        },
        {
          label: this.t('Primary'),
          value: 'var(--color-primary-element)',
          cssVar: 'var(--color-primary-element)'
        },
        {
          label: this.t('Primary light'),
          value: 'var(--color-primary-element-light)',
          cssVar: 'var(--color-primary-element-light)'
        },
        {
          label: this.t('Light background'),
          value: 'var(--color-background-hover)',
          cssVar: 'var(--color-background-hover)'
        }
      ];
    }
  },
  mounted() {
    // Ensure all widgets have unique IDs
    this.initializeWidgetIds();
    this.initializeColumnArrays();
  },
  watch: {
    localPage: {
      handler() {
        this.$emit('update', this.localPage);
      },
      deep: true
    }
  },
  methods: {
    t(key, vars = {}) {
      return t('intravox', key, vars);
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
    needsEditButton(widgetType) {
      // Show edit button for widgets that aren't inline-editable
      return ['image', 'link', 'file', 'heading'].includes(widgetType);
    },
    initializeWidgetIds() {
      // Assign unique IDs to all widgets if they don't have one
      this.localPage.layout.rows.forEach(row => {
        row.widgets.forEach(widget => {
          if (!widget.id) {
            widget.id = `widget-${this.nextWidgetId++}`;
          }
        });
      });
    },
    initializeColumnArrays() {
      // Create reactive arrays for each column in each row
      const newArrays = {};
      this.localPage.layout.rows.forEach((row, rowIndex) => {
        const rowColumns = (row.columns || this.localPage.layout.columns) ?? 1;
        for (let col = 1; col <= rowColumns; col++) {
          const key = `${rowIndex}-${col}`;
          newArrays[key] = row.widgets.filter(w => w.column === col);
        }
      });
      this.columnArrays = newArrays;
    },
    getColumnWidgets(rowIndex, column) {
      const key = `${rowIndex}-${column}`;
      if (!this.columnArrays[key]) {
        this.columnArrays[key] = [];
      }
      return this.columnArrays[key];
    },
    onDragEnd(rowIndex, column) {
      // Use nextTick to batch updates and avoid flickering
      this.$nextTick(() => {
        // Rebuild ALL rows' widgets from column arrays to handle cross-row drags
        this.localPage.layout.rows.forEach((row, rIndex) => {
          const allWidgets = [];
          const rowColumns = (row.columns || this.localPage.layout.columns) ?? 1;

          for (let col = 1; col <= rowColumns; col++) {
            const key = `${rIndex}-${col}`;
            const columnWidgets = this.columnArrays[key] || [];

            columnWidgets.forEach((widget, index) => {
              widget.column = col;
              widget.order = index + 1;
              allWidgets.push(widget);
            });
          }

          row.widgets = allWidgets;
        });

        // Reinitialize column arrays to ensure they stay in sync
        this.initializeColumnArrays();
      });
    },
    setColumns(n) {
      const oldColumns = this.localPage.layout.columns;
      this.localPage.layout.columns = n;

      // If reducing columns, move widgets from removed columns to last available column
      if (n < oldColumns) {
        this.localPage.layout.rows.forEach(row => {
          row.widgets.forEach(widget => {
            if (widget.column > n) {
              widget.column = n; // Move to last column
            }
          });
        });
      }

      this.initializeColumnArrays();
    },
    setRowColumns(rowIndex, n) {
      const row = this.localPage.layout.rows[rowIndex];
      const oldColumns = (row.columns || this.localPage.layout.columns) ?? 1;

      // Set row-specific column count
      row.columns = n;

      // If reducing columns, move widgets from removed columns to last available column
      if (n < oldColumns) {
        row.widgets.forEach(widget => {
          if (widget.column > n) {
            widget.column = n; // Move to last column
          }
        });
      }

      this.initializeColumnArrays();
    },
    setRowBackgroundColor(rowIndex, color) {
      // Set the backgroundColor property
      const row = this.localPage.layout.rows[rowIndex];
      row.backgroundColor = color;

      // Force Vue to detect the change by creating a new array reference
      this.localPage.layout.rows = [...this.localPage.layout.rows];
    },
    addRow() {
      this.localPage.layout.rows.push({
        columns: 1,
        widgets: [],
        backgroundColor: ''
      });
      this.initializeColumnArrays();
    },
    deleteRow(rowIndex) {
      this.showDeleteDialog = true;
      this.deleteDialogTitle = this.t('Delete row');
      this.deleteDialogMessage = this.t('Are you sure you want to delete this row?');
      this.deleteCallback = () => {
        this.localPage.layout.rows.splice(rowIndex, 1);
      };
    },
    showWidgetPickerForColumn(rowIndex, column) {
      this.targetRowIndex = rowIndex;
      this.targetColumn = column;
      this.showWidgetPicker = true;
    },
    addWidget(widgetType) {
      const newWidget = this.createWidget(widgetType);

      // Use target row and column if specified
      const rowIndex = this.targetRowIndex !== null ? this.targetRowIndex : 0;
      const column = this.targetColumn !== null ? this.targetColumn : 1;

      // Ensure row exists
      if (this.localPage.layout.rows.length === 0) {
        this.addRow();
      }

      const row = this.localPage.layout.rows[rowIndex];
      newWidget.id = `widget-${this.nextWidgetId++}`;
      newWidget.column = column;
      newWidget.order = row.widgets.filter(w => w.column === column).length + 1;
      row.widgets.push(newWidget);

      // Update column arrays
      this.initializeColumnArrays();

      this.showWidgetPicker = false;

      // Open editor modal for widgets that need configuration
      if (widgetType === 'image' || widgetType === 'link' || widgetType === 'file' || widgetType === 'heading') {
        this.editWidget(newWidget, rowIndex);
      }

      this.targetRowIndex = null;
      this.targetColumn = null;
    },
    createWidget(type) {
      const widget = {
        type: type,
        column: 1,
        order: 1
      };

      switch (type) {
        case 'text':
          widget.content = this.t('New text widget');
          break;
        case 'heading':
          widget.content = this.t('New heading');
          widget.level = 2;
          break;
        case 'image':
          widget.src = '';
          widget.alt = '';
          break;
        case 'link':
          widget.url = '';
          widget.text = this.t('Link text');
          break;
        case 'file':
          widget.path = '';
          widget.name = this.t('File');
          break;
        case 'divider':
        case 'spacer':
          // No additional properties needed for divider
          break;
      }

      return widget;
    },
    editWidget(widget, rowIndex) {
      this.editingWidget = JSON.parse(JSON.stringify(widget));
      this.editingRowIndex = rowIndex;
    },
    saveWidget(updatedWidget) {
      const row = this.localPage.layout.rows[this.editingRowIndex];
      const index = row.widgets.findIndex(w => w.id === this.editingWidget.id);

      if (index !== -1) {
        row.widgets[index] = updatedWidget;
        // Reinitialize column arrays to reflect the updated widget
        this.initializeColumnArrays();
      }

      this.editingWidget = null;
      this.editingRowIndex = null;
    },
    updateWidget(updatedWidget, rowIndex) {
      // For inline editing - update widget directly without closing modal
      const row = this.localPage.layout.rows[rowIndex];
      const index = row.widgets.findIndex(w => w.id === updatedWidget.id);

      if (index !== -1) {
        row.widgets[index] = updatedWidget;
        // Reinitialize column arrays to reflect the updated widget
        this.initializeColumnArrays();
      }
    },
    deleteWidget(rowIndex, widgetId) {
      this.showDeleteDialog = true;
      this.deleteDialogTitle = this.t('Delete widget');
      this.deleteDialogMessage = this.t('Are you sure you want to delete this widget?');
      this.deleteCallback = () => {
        const row = this.localPage.layout.rows[rowIndex];
        const index = row.widgets.findIndex(w => w.id === widgetId);
        if (index !== -1) {
          row.widgets.splice(index, 1);
          // Reinitialize column arrays to reflect the deleted widget
          this.initializeColumnArrays();
        }
      };
    },
    confirmDelete() {
      if (this.deleteCallback) {
        this.deleteCallback();
      }
      this.cancelDelete();
    },
    cancelDelete() {
      this.showDeleteDialog = false;
      this.deleteDialogTitle = '';
      this.deleteDialogMessage = '';
      this.deleteCallback = null;
    }
  }
};
</script>

<style scoped>
.page-editor {
  width: 100%;
}

.editor-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px;
  background: var(--color-background-dark);
  border-radius: var(--border-radius-large);
  margin-bottom: 20px;
}

.toolbar-group {
  display: flex;
  align-items: center;
  gap: 10px;
}

.toolbar-group label {
  font-weight: bold;
  margin-right: 5px;
}

.column-button {
  width: 35px;
  height: 35px;
  border: 2px solid var(--color-border);
  border-radius: var(--border-radius-large);
  background: var(--color-main-background);
  cursor: pointer;
  font-weight: bold;
}

.column-button:hover {
  background: var(--color-background-hover);
}

.column-button.active {
  background: var(--color-primary);
  color: white;
  border-color: var(--color-primary);
}

.column-button.small {
  width: 28px;
  height: 28px;
  font-size: 12px;
}

.page-row {
  margin-bottom: 20px;
  position: relative;
  padding: 10px;
  border: 2px dashed transparent;
  border-radius: var(--border-radius-large);
}

.page-row.editable {
  border-color: var(--color-border);
}

.row-controls {
  display: flex;
  align-items: center;
  margin-bottom: 10px;
  padding: 8px;
  background: transparent;
  border-radius: var(--border-radius-large);
  border: 1px solid var(--color-border);
}

.page-grid {
  display: grid;
  gap: 20px;
  width: 100%;
}

.page-column {
  min-height: 100px;
  padding: 10px;
  border: 2px dashed var(--color-border);
  border-radius: var(--border-radius-large);
  background: transparent;
  position: relative;
}

.column-label {
  position: absolute;
  top: -10px;
  left: 10px;
  background: transparent;
  padding: 2px 8px;
  font-size: 12px;
  color: var(--color-text-maxcontrast);
  border-radius: var(--border-radius-large);
}

.widget-drop-zone {
  min-height: 80px;
  transition: background-color 0.2s;
}

.page-column.droppable {
  transition: all 0.2s;
}

.page-column.droppable:hover {
  border-color: var(--color-primary);
  background: transparent;
}

.widget-wrapper {
  position: relative;
  margin-bottom: 15px;
  padding: 10px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius-large);
  background: transparent;
  cursor: move;
}

.widget-wrapper:hover {
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

/* Divider widget in edit mode - reduce padding to prevent double-line effect */
.widget-wrapper:has(.widget-divider) {
  padding: 0;
  border: none;
  background: transparent;
  margin-bottom: 5px;
}

.widget-controls {
  position: absolute;
  top: 5px;
  right: 5px;
  display: flex;
  gap: 5px;
  z-index: 1001; /* Higher than text editor toolbar (z-index: 100) */
  opacity: 0;
  transition: opacity 0.2s ease;
  pointer-events: none;
}

.widget-wrapper:hover .widget-controls {
  opacity: 1;
  pointer-events: auto;
}

/* Hide controls when editing (text editor is focused) */
.widget-wrapper.editing .widget-controls {
  opacity: 0 !important;
  pointer-events: none !important;
}

.column-add-widget {
  margin-top: 10px;
  text-align: center;
  padding-top: 10px;
  border-top: 1px dashed var(--color-border);
}

.column-add-widget .add-widget-btn {
  width: 100%;
}

.color-preview {
  width: 20px;
  height: 20px;
  border-radius: var(--border-radius-large);
  border: 1px solid var(--color-border);
}
</style>
