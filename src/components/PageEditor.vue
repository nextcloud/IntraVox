<template>
  <div class="page-editor">
    <!-- Page Grid -->
    <template v-for="(row, rowIndex) in localPage.layout.rows" :key="rowIndex">
    <div
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
            handle=".drag-handle"
            @end="onDragEnd(rowIndex, column)"
            class="widget-drop-zone"
          >
            <template #item="{ element: widget }">
              <div class="widget-wrapper" :class="{ 'editing': focusedWidgetId === widget.id }">
                <!-- Floating toolbar - appears on hover -->
                <div class="floating-toolbar">
                  <div class="drag-handle" :aria-label="t('Drag to reorder')">
                    <DragVertical :size="16" />
                  </div>
                  <NcButton v-if="needsEditButton(widget.type)"
                            @click="editWidget(widget, rowIndex)"
                            type="secondary"
                            :aria-label="t('Edit widget')">
                    <template #icon>
                      <Pencil :size="16" />
                    </template>
                  </NcButton>
                  <NcButton @click="duplicateWidget(rowIndex, widget)"
                            type="secondary"
                            :aria-label="t('Duplicate widget')">
                    <template #icon>
                      <ContentDuplicate :size="16" />
                    </template>
                  </NcButton>
                  <NcButton @click="deleteWidget(rowIndex, widget.id)"
                            type="error"
                            :aria-label="t('Delete widget')">
                    <template #icon>
                      <Delete :size="16" />
                    </template>
                  </NcButton>
                </div>
                <Widget :widget="widget" :page-id="page.uniqueId" :editable="true" :row-background-color="row.backgroundColor || ''" @update="updateWidget($event, rowIndex)" @focus="focusedWidgetId = widget.id" @blur="focusedWidgetId = null" />
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

    <!-- Insert Row Button -->
    <div class="insert-row-container">
      <NcButton @click="insertRow(rowIndex + 1)" type="secondary" class="insert-row-btn">
        <template #icon>
          <TableRowPlusAfter :size="20" />
        </template>
        {{ t('Insert Row') }}
      </NcButton>
    </div>
    </template>

    <!-- Widget Picker Modal -->
    <WidgetPicker
      v-if="showWidgetPicker"
      @close="showWidgetPicker = false"
      @select="addWidget"
    />

    <!-- Widget Editor Modal -->
    <WidgetEditor
      v-if="editingWidget && editingWidget.type !== 'links'"
      :widget="editingWidget"
      :page-id="page.uniqueId"
      @close="editingWidget = null"
      @save="saveWidget"
    />

    <!-- Links Editor Modal -->
    <LinksEditor
      v-if="editingWidget && editingWidget.type === 'links'"
      :widget="editingWidget"
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
import ContentDuplicate from 'vue-material-design-icons/ContentDuplicate.vue';
import Palette from 'vue-material-design-icons/Palette.vue';
import DragVertical from 'vue-material-design-icons/DragVertical.vue';
import Widget from './Widget.vue';
import WidgetPicker from './WidgetPicker.vue';
import WidgetEditor from './WidgetEditor.vue';
import LinksEditor from './LinksEditor.vue';

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
    ContentDuplicate,
    Palette,
    DragVertical,
    Widget,
    WidgetPicker,
    WidgetEditor,
    LinksEditor
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
      return ['image', 'link', 'links', 'file', 'heading'].includes(widgetType);
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
    insertRow(index) {
      // Insert a new row at the specified index
      this.localPage.layout.rows.splice(index, 0, {
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
        // Reinitialize column arrays to reflect the deleted row
        this.initializeColumnArrays();
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
      if (widgetType === 'image' || widgetType === 'links' || widgetType === 'file' || widgetType === 'heading') {
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
          widget.content = '';
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
        case 'links':
          widget.items = [];
          widget.columns = 2;
          widget.backgroundColor = null;
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
      console.log('[PageEditor saveWidget] Received updated widget:', updatedWidget);

      const row = this.localPage.layout.rows[this.editingRowIndex];
      const index = row.widgets.findIndex(w => w.id === this.editingWidget.id);

      if (index !== -1) {
        // Preserve the id, column, and order from the original widget
        updatedWidget.id = this.editingWidget.id;
        updatedWidget.column = this.editingWidget.column;
        updatedWidget.order = this.editingWidget.order;

        console.log('[PageEditor saveWidget] Updating widget at row', this.editingRowIndex, 'index', index);
        console.log('[PageEditor saveWidget] Updated widget:', updatedWidget);

        // Use splice to ensure Vue reactivity detects the change
        row.widgets.splice(index, 1, updatedWidget);

        // Reinitialize column arrays to reflect the updated widget
        this.initializeColumnArrays();

        // Force trigger the watcher by creating a deep clone
        this.localPage = JSON.parse(JSON.stringify(this.localPage));

        console.log('[PageEditor saveWidget] Updated localPage:', this.localPage);

        // Manually emit update to ensure parent receives the change
        this.$emit('update', this.localPage);
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
        // Manually emit update to ensure parent receives the change
        this.$emit('update', this.localPage);
      }
    },
    duplicateWidget(rowIndex, widget) {
      const row = this.localPage.layout.rows[rowIndex];

      // Create a deep copy of the widget
      const duplicatedWidget = JSON.parse(JSON.stringify(widget));

      // Assign a new unique ID
      duplicatedWidget.id = `widget-${this.nextWidgetId++}`;

      // Insert the duplicated widget right after the original
      const index = row.widgets.findIndex(w => w.id === widget.id);
      if (index !== -1) {
        // Update order for the duplicated widget and all widgets after it
        duplicatedWidget.order = widget.order + 1;

        // Insert after the original widget
        row.widgets.splice(index + 1, 0, duplicatedWidget);

        // Update order for all subsequent widgets in the same column
        row.widgets
          .filter(w => w.column === widget.column && w.order > widget.order)
          .forEach(w => {
            if (w.id !== duplicatedWidget.id) {
              w.order++;
            }
          });

        // Reinitialize column arrays to reflect the new widget
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

.insert-row-container {
  display: flex;
  justify-content: center;
  align-items: center;
  margin: 8px 0;
  opacity: 0.6;
  transition: opacity 0.2s ease;
}

.insert-row-container:hover {
  opacity: 1;
}

.insert-row-btn {
  font-size: 13px;
}

.page-row {
  margin-bottom: 12px;
  position: relative;
  padding: 16px;
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
  gap: 12px;
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
  border: 1px solid transparent;
  border-radius: var(--border-radius-large);
  background: transparent;
  transition: border-color 0.2s ease;
}

.widget-wrapper:hover {
  border-color: var(--color-border);
}

/* Divider widget in edit mode - no padding or border */
.widget-wrapper:has(.widget-divider) {
  padding: 0;
  border: none;
  background: transparent;
  margin-bottom: 15px;
}

/* Floating toolbar - Medium/Notion style */
.floating-toolbar {
  position: absolute;
  top: -36px; /* Float above widget */
  right: 0;
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 4px;
  background: var(--color-main-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius-large);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  z-index: 1001;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.2s ease;
}

.widget-wrapper:hover .floating-toolbar {
  opacity: 1;
  pointer-events: auto;
}

/* Hide toolbar when editing */
.widget-wrapper.editing .floating-toolbar {
  opacity: 0 !important;
  pointer-events: none !important;
}

/* Divider widgets - show toolbar below instead of above */
.widget-wrapper:has(.widget-divider) .floating-toolbar {
  top: auto;
  bottom: -36px;
}

/* Drag handle in floating toolbar */
.floating-toolbar .drag-handle {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 6px 8px;
  background: transparent;
  border: none;
  border-radius: var(--border-radius);
  cursor: grab;
  color: var(--color-text-maxcontrast);
  transition: all 0.15s ease;
}

.floating-toolbar .drag-handle:hover {
  background: var(--color-background-hover);
  color: var(--color-main-text);
}

.floating-toolbar .drag-handle:active {
  cursor: grabbing;
  background: var(--color-primary-element-light);
}

/* Compact buttons in toolbar */
.floating-toolbar :deep(.button-vue) {
  min-height: 28px !important;
  min-width: 28px !important;
}

.floating-toolbar :deep(.button-vue__icon) {
  height: 16px !important;
  width: 16px !important;
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
