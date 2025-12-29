<template>
  <div class="page-editor-wrapper">
    <!-- Header Row (spans full width above everything) -->
    <div v-if="hasHeaderRow" class="header-row-editor" :style="getHeaderRowStyle()">
      <div class="header-row-header">
        <span class="header-row-title">{{ t('Header row') }}</span>
        <div class="header-row-actions">
          <NcActions>
            <template #icon>
              <Palette :size="20" />
            </template>
            <NcActionButton v-for="color in backgroundColors"
                            :key="color.value"
                            @click="setHeaderRowBackgroundColor(color.value)">
              <template #icon>
                <div class="color-preview" :style="{ background: color.cssVar }"></div>
              </template>
              {{ color.label }}
            </NcActionButton>
          </NcActions>
          <NcButton type="error"
                    @click="removeHeaderRow()"
                    :aria-label="t('Remove header row')">
            <template #icon>
              <Delete :size="20" />
            </template>
          </NcButton>
        </div>
      </div>
      <div class="header-row-widgets">
        <draggable
          :list="headerRowWidgets"
          :group="{ name: 'allWidgets', pull: true, put: true }"
          :animation="200"
          item-key="id"
          handle=".drag-handle"
          @end="onHeaderRowDragEnd"
          class="header-widget-drop-zone"
        >
          <template #item="{ element: widget }">
            <div class="widget-wrapper" :class="{ 'editing': focusedWidgetId === widget.id }">
              <div class="floating-toolbar">
                <div class="drag-handle" :aria-label="t('Drag to reorder')">
                  <DragVertical :size="16" />
                </div>
                <NcButton v-if="needsEditButton(widget.type)"
                          @click="editHeaderRowWidget(widget)"
                          type="secondary"
                          :aria-label="t('Edit widget')">
                  <template #icon>
                    <Pencil :size="16" />
                  </template>
                </NcButton>
                <NcButton @click="duplicateHeaderRowWidget(widget)"
                          type="secondary"
                          :aria-label="t('Duplicate widget')">
                  <template #icon>
                    <ContentDuplicate :size="16" />
                  </template>
                </NcButton>
                <NcButton @click="deleteHeaderRowWidget(widget.id)"
                          type="error"
                          :aria-label="t('Delete widget')">
                  <template #icon>
                    <Delete :size="16" />
                  </template>
                </NcButton>
              </div>
              <Widget :widget="widget" :page-id="page.uniqueId" :editable="true" :row-background-color="getHeaderRowBgColor()" @update="updateHeaderRowWidget($event)" @focus="focusedWidgetId = widget.id" @blur="focusedWidgetId = null" />
            </div>
          </template>
        </draggable>
        <NcButton @click="showHeaderRowWidgetPicker()" type="primary" class="add-widget-btn">
          <template #icon>
            <Plus :size="20" />
          </template>
          {{ t('Add Widget') }}
        </NcButton>
      </div>
    </div>

    <!-- Add Header Row Button (when no header row exists) -->
    <div v-if="!hasHeaderRow" class="add-header-row-container">
      <NcButton @click="addHeaderRow()" type="secondary" class="add-header-row-btn">
        <template #icon>
          <TableRowPlusBefore :size="20" />
        </template>
        {{ t('Header row') }}
      </NcButton>
    </div>

  <div class="page-editor-container">
    <!-- Left Side Column -->
    <div v-if="hasSideColumn('left')" class="side-column-editor side-column-left" :style="getSideColumnStyle('left')">
      <div class="side-column-header">
        <span class="side-column-title">{{ t('Left column') }}</span>
        <div class="side-column-actions">
          <NcActions>
            <template #icon>
              <Palette :size="20" />
            </template>
            <NcActionButton v-for="color in backgroundColors"
                            :key="color.value"
                            @click="setSideColumnBackgroundColor('left', color.value)">
              <template #icon>
                <div class="color-preview" :style="{ background: color.cssVar }"></div>
              </template>
              {{ color.label }}
            </NcActionButton>
          </NcActions>
          <NcButton type="error"
                    @click="removeSideColumn('left')"
                    :aria-label="t('Remove column')">
            <template #icon>
              <Delete :size="20" />
            </template>
          </NcButton>
        </div>
      </div>
      <div class="side-column-widgets">
        <draggable
          :list="getSideColumnWidgets('left')"
          :group="{ name: 'allWidgets', pull: true, put: true }"
          :animation="200"
          item-key="id"
          handle=".drag-handle"
          @end="onSideColumnDragEnd('left')"
          class="side-widget-drop-zone"
        >
          <template #item="{ element: widget }">
            <div class="widget-wrapper" :class="{ 'editing': focusedWidgetId === widget.id }">
              <div class="floating-toolbar">
                <div class="drag-handle" :aria-label="t('Drag to reorder')">
                  <DragVertical :size="16" />
                </div>
                <NcButton v-if="needsEditButton(widget.type)"
                          @click="editSideColumnWidget(widget, 'left')"
                          type="secondary"
                          :aria-label="t('Edit widget')">
                  <template #icon>
                    <Pencil :size="16" />
                  </template>
                </NcButton>
                <NcButton @click="duplicateSideColumnWidget(widget, 'left')"
                          type="secondary"
                          :aria-label="t('Duplicate widget')">
                  <template #icon>
                    <ContentDuplicate :size="16" />
                  </template>
                </NcButton>
                <NcButton @click="deleteSideColumnWidget('left', widget.id)"
                          type="error"
                          :aria-label="t('Delete widget')">
                  <template #icon>
                    <Delete :size="16" />
                  </template>
                </NcButton>
              </div>
              <Widget :widget="widget" :page-id="page.uniqueId" :editable="true" :row-background-color="getSideColumnBgColor('left')" @update="updateSideColumnWidget($event, 'left')" @focus="focusedWidgetId = widget.id" @blur="focusedWidgetId = null" />
            </div>
          </template>
        </draggable>
        <NcButton @click="showSideColumnWidgetPicker('left')" type="primary" class="add-widget-btn">
          <template #icon>
            <Plus :size="20" />
          </template>
          {{ t('Add Widget') }}
        </NcButton>
      </div>
    </div>

    <!-- Main Content Area -->
    <div class="page-editor">
      <!-- Add Side Column Buttons -->
      <div class="side-column-buttons">
        <NcButton v-if="!hasSideColumn('left')"
                  @click="addSideColumn('left')"
                  type="secondary"
                  :aria-label="t('Add left column')">
          <template #icon>
            <TableColumnPlusBefore :size="20" />
          </template>
          {{ t('Left column') }}
        </NcButton>
        <NcButton v-if="!hasSideColumn('right')"
                  @click="addSideColumn('right')"
                  type="secondary"
                  :aria-label="t('Add right column')">
          <template #icon>
            <TableColumnPlusAfter :size="20" />
          </template>
          {{ t('Right column') }}
        </NcButton>
      </div>

      <!-- Page Grid -->
      <draggable
        :list="localPage.layout.rows"
        :animation="200"
        item-key="id"
        handle=".row-drag-handle"
        @end="onRowDragEnd"
        class="rows-container"
      >
        <template #item="{ element: row, index: rowIndex }">
    <div class="row-wrapper">
    <div
      class="page-row editable"
      :style="getRowStyle(row)"
    >
      <div class="row-controls">
        <div class="row-drag-handle" :aria-label="t('Drag to reorder row')">
          <DragVertical :size="20" />
        </div>

        <!-- Collapsible Section Controls (grouped) -->
        <div class="collapsible-controls">
          <label class="collapsible-toggle-label" @click.stop>
            <input
              type="checkbox"
              :checked="row.collapsible"
              @change="toggleRowCollapsible(row)"
            />
            {{ t('Collapsible section') }}
          </label>

          <!-- Section Title (only shown when collapsible) -->
          <input
            v-if="row.collapsible"
            v-model="row.sectionTitle"
            class="row-section-title"
            :placeholder="t('Section title...')"
            @input="onSectionTitleChange(row)"
            @click.stop
          />

          <!-- Default Collapsed checkbox (only shown when collapsible) -->
          <label v-if="row.collapsible" class="default-collapsed-label" @click.stop>
            <input
              type="checkbox"
              v-model="row.defaultCollapsed"
              @change="onDefaultCollapsedChange(row)"
            />
            {{ t('Collapsed by default') }}
          </label>
        </div>

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

      <!-- Row Content (collapsible) -->
      <div
        class="row-content"
        :class="{ collapsed: row.collapsible && row.collapsed }"
        v-show="!row.collapsible || !row.collapsed"
      >
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
              :group="{ name: 'allWidgets', pull: true, put: true }"
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
    </div>
        </template>
      </draggable>

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

    <!-- Right Side Column -->
    <div v-if="hasSideColumn('right')" class="side-column-editor side-column-right" :style="getSideColumnStyle('right')">
      <div class="side-column-header">
        <span class="side-column-title">{{ t('Right column') }}</span>
        <div class="side-column-actions">
          <NcActions>
            <template #icon>
              <Palette :size="20" />
            </template>
            <NcActionButton v-for="color in backgroundColors"
                            :key="color.value"
                            @click="setSideColumnBackgroundColor('right', color.value)">
              <template #icon>
                <div class="color-preview" :style="{ background: color.cssVar }"></div>
              </template>
              {{ color.label }}
            </NcActionButton>
          </NcActions>
          <NcButton type="error"
                    @click="removeSideColumn('right')"
                    :aria-label="t('Remove column')">
            <template #icon>
              <Delete :size="20" />
            </template>
          </NcButton>
        </div>
      </div>
      <div class="side-column-widgets">
        <draggable
          :list="getSideColumnWidgets('right')"
          :group="{ name: 'allWidgets', pull: true, put: true }"
          :animation="200"
          item-key="id"
          handle=".drag-handle"
          @end="onSideColumnDragEnd('right')"
          class="side-widget-drop-zone"
        >
          <template #item="{ element: widget }">
            <div class="widget-wrapper" :class="{ 'editing': focusedWidgetId === widget.id }">
              <div class="floating-toolbar">
                <div class="drag-handle" :aria-label="t('Drag to reorder')">
                  <DragVertical :size="16" />
                </div>
                <NcButton v-if="needsEditButton(widget.type)"
                          @click="editSideColumnWidget(widget, 'right')"
                          type="secondary"
                          :aria-label="t('Edit widget')">
                  <template #icon>
                    <Pencil :size="16" />
                  </template>
                </NcButton>
                <NcButton @click="duplicateSideColumnWidget(widget, 'right')"
                          type="secondary"
                          :aria-label="t('Duplicate widget')">
                  <template #icon>
                    <ContentDuplicate :size="16" />
                  </template>
                </NcButton>
                <NcButton @click="deleteSideColumnWidget('right', widget.id)"
                          type="error"
                          :aria-label="t('Delete widget')">
                  <template #icon>
                    <Delete :size="16" />
                  </template>
                </NcButton>
              </div>
              <Widget :widget="widget" :page-id="page.uniqueId" :editable="true" :row-background-color="getSideColumnBgColor('right')" @update="updateSideColumnWidget($event, 'right')" @focus="focusedWidgetId = widget.id" @blur="focusedWidgetId = null" />
            </div>
          </template>
        </draggable>
        <NcButton @click="showSideColumnWidgetPicker('right')" type="primary" class="add-widget-btn">
          <template #icon>
            <Plus :size="20" />
          </template>
          {{ t('Add Widget') }}
        </NcButton>
      </div>
    </div>
  </div>
  </div>
</template>

<script>
import draggable from 'vuedraggable';
import { translate as t } from '@nextcloud/l10n';
import { NcButton, NcDialog, NcActions, NcActionButton } from '@nextcloud/vue';
import Plus from 'vue-material-design-icons/Plus.vue';
import TableRowPlusAfter from 'vue-material-design-icons/TableRowPlusAfter.vue';
import TableRowPlusBefore from 'vue-material-design-icons/TableRowPlusBefore.vue';
import TableColumnPlusBefore from 'vue-material-design-icons/TableColumnPlusBefore.vue';
import TableColumnPlusAfter from 'vue-material-design-icons/TableColumnPlusAfter.vue';
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
    TableRowPlusBefore,
    TableColumnPlusBefore,
    TableColumnPlusAfter,
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
      localPage: structuredClone(this.page),
      showWidgetPicker: false,
      editingWidget: null,
      editingRowIndex: null,
      editingSideColumn: null, // 'left' or 'right' when editing side column widget
      targetRowIndex: null,
      targetColumn: null,
      targetSideColumn: null, // 'left' or 'right' when adding to side column
      nextWidgetId: 1,
      nextRowId: 1,
      columnArrays: {}, // Store separate arrays per row/column
      sideColumnWidgets: { left: [], right: [] }, // Separate arrays for side columns
      headerRowWidgets: [], // Array for header row widgets
      editingHeaderRow: false, // Flag when editing header row widget
      targetHeaderRow: false, // Flag when adding widget to header row
      showDeleteDialog: false,
      deleteDialogTitle: '',
      deleteDialogMessage: '',
      deleteCallback: null,
      focusedWidgetId: null
    };
  },
  computed: {
    hasHeaderRow() {
      return this.localPage.layout.headerRow?.enabled === true;
    },
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
    // Ensure all rows have unique IDs for drag-and-drop
    this.initializeRowIds();
    // Ensure all widgets have unique IDs
    this.initializeWidgetIds();
    this.initializeColumnArrays();
    this.initializeSideColumns();
    this.initializeHeaderRow();
  },
  // Note: Deep watcher removed for performance - updates are emitted explicitly in syncAllZones()
  methods: {
    t(key, vars = {}) {
      return t('intravox', key, vars);
    },
    initializeRowIds() {
      // Ensure all rows have unique IDs for drag-and-drop
      let maxRowId = 0;
      this.localPage.layout.rows.forEach(row => {
        if (row.id) {
          const match = row.id.match(/^row-(\d+)$/);
          if (match) {
            maxRowId = Math.max(maxRowId, parseInt(match[1], 10));
          }
        }
      });

      this.nextRowId = maxRowId + 1;

      this.localPage.layout.rows.forEach(row => {
        if (!row.id) {
          row.id = `row-${this.nextRowId++}`;
        }
      });
    },
    generateRowId() {
      if (!this.nextRowId) {
        this.nextRowId = 1;
      }
      return `row-${this.nextRowId++}`;
    },
    onRowDragEnd() {
      // After drag ends, reinitialize column arrays and emit update
      this.$nextTick(() => {
        this.initializeColumnArrays();
        this.$emit('update', this.localPage);
      });
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
      return ['image', 'link', 'links', 'file', 'heading', 'video', 'news'].includes(widgetType);
    },
    initializeWidgetIds() {
      // Collect ALL widgets from all zones for duplicate detection
      const allWidgets = [];

      // Collect main row widgets
      this.localPage.layout.rows.forEach((row, rowIndex) => {
        row.widgets.forEach((widget, widgetIndex) => {
          allWidgets.push({ widget, zone: 'row', rowIndex, widgetIndex });
        });
      });

      // Collect header row widgets
      if (this.localPage.layout.headerRow?.widgets) {
        this.localPage.layout.headerRow.widgets.forEach((widget, widgetIndex) => {
          allWidgets.push({ widget, zone: 'header', widgetIndex });
        });
      }

      // Collect side column widgets
      if (this.localPage.layout.sideColumns) {
        ['left', 'right'].forEach(side => {
          if (this.localPage.layout.sideColumns[side]?.widgets) {
            this.localPage.layout.sideColumns[side].widgets.forEach((widget, widgetIndex) => {
              allWidgets.push({ widget, zone: `side-${side}`, widgetIndex });
            });
          }
        });
      }

      // First pass: find highest ID and detect duplicates
      let maxId = 0;
      const seenIds = new Set();
      const duplicateWidgets = [];

      allWidgets.forEach(entry => {
        const widget = entry.widget;
        if (widget.id) {
          // Extract numeric ID
          const match = widget.id.match(/^widget-(\d+)$/);
          if (match) {
            maxId = Math.max(maxId, parseInt(match[1], 10));
          }

          // Check for duplicates
          if (seenIds.has(widget.id)) {
            duplicateWidgets.push(entry);
          } else {
            seenIds.add(widget.id);
          }
        }
      });

      // Set nextWidgetId to one more than the highest found
      this.nextWidgetId = maxId + 1;

      // Second pass: fix duplicates and assign IDs to widgets without one
      allWidgets.forEach(entry => {
        const widget = entry.widget;
        if (!widget.id || duplicateWidgets.includes(entry)) {
          // Assign new unique ID
          widget.id = `widget-${this.nextWidgetId++}`;
        }
      });
    },
    initializeColumnArrays() {
      // Create reactive arrays for each column in each row
      const newArrays = {};
      this.localPage.layout.rows.forEach((row, rowIndex) => {
        const rowColumns = (row.columns || this.localPage.layout.columns) ?? 1;
        for (let col = 1; col <= rowColumns; col++) {
          const key = `${rowIndex}-${col}`;
          // Filter by column AND sort by order to match view mode
          newArrays[key] = row.widgets
            .filter(w => w.column === col)
            .sort((a, b) => (a.order || 0) - (b.order || 0));
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
        // Sync all zones to handle cross-zone drags
        this.syncAllZones();
      });
    },
    /**
     * Centralized sync for all widget zones (header, side columns, main rows)
     * This handles cross-zone drags correctly
     */
    syncAllZones() {
      // Sync header row widgets
      if (this.localPage.layout.headerRow) {
        const updatedHeaderWidgets = this.headerRowWidgets.map((widget, index) => ({
          ...widget,
          order: index + 1
        }));
        this.headerRowWidgets = updatedHeaderWidgets;
        this.localPage.layout.headerRow.widgets = [...updatedHeaderWidgets];
      }

      // Sync side column widgets
      ['left', 'right'].forEach(side => {
        if (this.localPage.layout.sideColumns?.[side]) {
          const updatedSideWidgets = this.sideColumnWidgets[side].map((widget, index) => ({
            ...widget,
            order: index + 1
          }));
          this.sideColumnWidgets[side] = updatedSideWidgets;
          this.localPage.layout.sideColumns[side].widgets = [...updatedSideWidgets];
        }
      });

      // Rebuild ALL rows' widgets from column arrays
      const updatedRows = this.localPage.layout.rows.map((row, rIndex) => {
        const allWidgets = [];
        const rowColumns = (row.columns || this.localPage.layout.columns) ?? 1;

        for (let col = 1; col <= rowColumns; col++) {
          const key = `${rIndex}-${col}`;
          const columnWidgets = this.columnArrays[key] || [];

          columnWidgets.forEach((widget, index) => {
            // Create a new widget object to ensure reactivity
            const updatedWidget = { ...widget, column: col, order: index + 1 };
            allWidgets.push(updatedWidget);
          });
        }

        // Return a new row object with updated widgets
        return { ...row, widgets: allWidgets };
      });

      // Replace the entire rows array to trigger Vue reactivity
      this.localPage.layout.rows = updatedRows;

      // Force Vue to detect the change by creating a new localPage reference
      this.localPage = structuredClone(this.localPage);

      // Reinitialize all arrays to ensure they stay in sync
      this.initializeColumnArrays();
      this.initializeSideColumns();
      this.initializeHeaderRow();

      // Explicitly emit the update to parent
      this.$emit('update', this.localPage);
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
      this.$emit('update', this.localPage);
    },
    setRowBackgroundColor(rowIndex, color) {
      // Set the backgroundColor property
      const row = this.localPage.layout.rows[rowIndex];
      row.backgroundColor = color;

      // Force Vue to detect the change by creating a new array reference
      this.localPage.layout.rows = [...this.localPage.layout.rows];
      this.$emit('update', this.localPage);
    },
    toggleRowCollapsible(row) {
      // Toggle whether this row is a collapsible section
      row.collapsible = !row.collapsible;
      if (!row.collapsible) {
        // Reset collapsed state and title when disabling collapsible
        row.collapsed = false;
        row.sectionTitle = '';
      }
      // Force Vue to detect the change
      this.localPage.layout.rows = [...this.localPage.layout.rows];
      this.$emit('update', this.localPage);
    },
    toggleRowCollapsed(row) {
      // Toggle collapsed state (only if collapsible is enabled)
      if (row.collapsible) {
        row.collapsed = !row.collapsed;
        // Force Vue to detect the change
        this.localPage.layout.rows = [...this.localPage.layout.rows];
        this.$emit('update', this.localPage);
      }
    },
    onSectionTitleChange(row) {
      // Debounced update when section title changes
      // Force Vue to detect the change
      this.localPage.layout.rows = [...this.localPage.layout.rows];
      this.$emit('update', this.localPage);
    },
    onDefaultCollapsedChange(row) {
      // Update when default collapsed state changes
      this.localPage.layout.rows = [...this.localPage.layout.rows];
      this.$emit('update', this.localPage);
    },
    addRow() {
      this.localPage.layout.rows.push({
        id: this.generateRowId(),
        columns: 1,
        widgets: [],
        backgroundColor: '',
        collapsible: false,
        sectionTitle: '',
        collapsed: false,
        defaultCollapsed: false
      });
      this.initializeColumnArrays();
      this.$emit('update', this.localPage);
    },
    insertRow(index) {
      // Insert a new row at the specified index
      this.localPage.layout.rows.splice(index, 0, {
        id: this.generateRowId(),
        columns: 1,
        widgets: [],
        backgroundColor: '',
        collapsible: false,
        sectionTitle: '',
        collapsed: false,
        defaultCollapsed: false
      });
      this.initializeColumnArrays();
      this.$emit('update', this.localPage);
    },
    deleteRow(rowIndex) {
      this.showDeleteDialog = true;
      this.deleteDialogTitle = this.t('Delete row');
      this.deleteDialogMessage = this.t('Are you sure you want to delete this row?');
      this.deleteCallback = () => {
        this.localPage.layout.rows.splice(rowIndex, 1);
        // Reinitialize column arrays to reflect the deleted row
        this.initializeColumnArrays();
        this.$emit('update', this.localPage);
      };
    },
    showWidgetPickerForColumn(rowIndex, column) {
      this.targetRowIndex = rowIndex;
      this.targetColumn = column;
      this.showWidgetPicker = true;
    },
    addWidget(widgetType) {
      const newWidget = this.createWidget(widgetType);
      newWidget.id = `widget-${this.nextWidgetId++}`;

      // Check if adding to header row
      if (this.targetHeaderRow) {
        newWidget.order = this.headerRowWidgets.length + 1;
        this.headerRowWidgets.push(newWidget);
        this.localPage.layout.headerRow.widgets = [...this.headerRowWidgets];

        this.showWidgetPicker = false;

        // Open editor modal for widgets that need configuration
        if (widgetType === 'image' || widgetType === 'links' || widgetType === 'file' || widgetType === 'heading' || widgetType === 'video' || widgetType === 'news') {
          this.editHeaderRowWidget(newWidget);
        }

        this.targetHeaderRow = false;
        return;
      }

      // Check if adding to side column
      if (this.targetSideColumn) {
        const side = this.targetSideColumn;
        newWidget.order = this.sideColumnWidgets[side].length + 1;
        this.sideColumnWidgets[side].push(newWidget);
        this.localPage.layout.sideColumns[side].widgets = [...this.sideColumnWidgets[side]];

        this.showWidgetPicker = false;

        // Open editor modal for widgets that need configuration
        if (widgetType === 'image' || widgetType === 'links' || widgetType === 'file' || widgetType === 'heading' || widgetType === 'video' || widgetType === 'news') {
          this.editSideColumnWidget(newWidget, side);
        }

        this.targetSideColumn = null;
        return;
      }

      // Use target row and column if specified
      const rowIndex = this.targetRowIndex !== null ? this.targetRowIndex : 0;
      const column = this.targetColumn !== null ? this.targetColumn : 1;

      // Ensure row exists
      if (this.localPage.layout.rows.length === 0) {
        this.addRow();
      }

      const row = this.localPage.layout.rows[rowIndex];
      newWidget.column = column;
      newWidget.order = row.widgets.filter(w => w.column === column).length + 1;
      row.widgets.push(newWidget);

      // Update column arrays
      this.initializeColumnArrays();

      this.showWidgetPicker = false;

      // Open editor modal for widgets that need configuration
      if (widgetType === 'image' || widgetType === 'links' || widgetType === 'file' || widgetType === 'heading' || widgetType === 'video' || widgetType === 'news') {
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
        case 'video':
          widget.provider = 'embed';
          widget.src = '';
          widget.title = '';
          widget.autoplay = false;
          widget.loop = false;
          widget.muted = false;
          break;
        case 'divider':
        case 'spacer':
          // No additional properties needed for divider
          break;
        case 'news':
          widget.title = '';
          widget.sourcePageId = null;
          widget.sourcePath = '';
          widget.layout = 'list';
          widget.columns = 3;
          widget.limit = 5;
          widget.sortBy = 'modified';
          widget.sortOrder = 'desc';
          widget.showImage = true;
          widget.showDate = true;
          widget.showExcerpt = true;
          widget.excerptLength = 100;
          widget.autoplayInterval = 5;
          widget.filters = [];
          widget.filterOperator = 'AND';
          break;
      }

      return widget;
    },
    editWidget(widget, rowIndex) {
      this.editingWidget = structuredClone(widget);
      this.editingRowIndex = rowIndex;
    },
    saveWidget(updatedWidget) {
      // Check if editing a header row widget
      if (this.editingHeaderRow) {
        const index = this.headerRowWidgets.findIndex(w => w.id === this.editingWidget.id);

        if (index !== -1) {
          updatedWidget.id = this.editingWidget.id;
          updatedWidget.order = this.editingWidget.order;

          this.headerRowWidgets.splice(index, 1, updatedWidget);
          this.localPage.layout.headerRow.widgets = [...this.headerRowWidgets];

          this.localPage = structuredClone(this.localPage);
          this.$emit('update', this.localPage);
        }

        this.editingWidget = null;
        this.editingHeaderRow = false;
        return;
      }

      // Check if editing a side column widget
      if (this.editingSideColumn) {
        const side = this.editingSideColumn;
        const widgets = this.sideColumnWidgets[side];
        const index = widgets.findIndex(w => w.id === this.editingWidget.id);

        if (index !== -1) {
          updatedWidget.id = this.editingWidget.id;
          updatedWidget.order = this.editingWidget.order;

          widgets.splice(index, 1, updatedWidget);
          this.localPage.layout.sideColumns[side].widgets = [...widgets];

          this.localPage = structuredClone(this.localPage);
          this.$emit('update', this.localPage);
        }

        this.editingWidget = null;
        this.editingSideColumn = null;
        return;
      }

      const row = this.localPage.layout.rows[this.editingRowIndex];
      const index = row.widgets.findIndex(w => w.id === this.editingWidget.id);

      if (index !== -1) {
        // Preserve the id, column, and order from the original widget
        updatedWidget.id = this.editingWidget.id;
        updatedWidget.column = this.editingWidget.column;
        updatedWidget.order = this.editingWidget.order;

        // Use splice to ensure Vue reactivity detects the change
        row.widgets.splice(index, 1, updatedWidget);

        // Reinitialize column arrays to reflect the updated widget
        this.initializeColumnArrays();

        // Force trigger the watcher by creating a deep clone
        this.localPage = structuredClone(this.localPage);

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
    /**
     * Generic helper to duplicate a widget in any zone
     * @param {Object} widget - The widget to duplicate
     * @param {Array} targetArray - The array containing the widget
     * @param {Function} syncCallback - Callback to sync changes back to layout
     */
    duplicateWidgetGeneric(widget, targetArray, syncCallback) {
      const duplicatedWidget = structuredClone(widget);
      duplicatedWidget.id = `widget-${this.nextWidgetId++}`;

      const index = targetArray.findIndex(w => w.id === widget.id);
      if (index !== -1) {
        duplicatedWidget.order = widget.order + 1;
        targetArray.splice(index + 1, 0, duplicatedWidget);

        // Update order for subsequent widgets
        targetArray
          .filter(w => w.order > widget.order && w.id !== duplicatedWidget.id)
          .forEach(w => w.order++);

        if (syncCallback) syncCallback();
      }
    },
    duplicateWidget(rowIndex, widget) {
      const columnWidgets = this.columnArrays[`${rowIndex}-${widget.column}`];
      this.duplicateWidgetGeneric(widget, columnWidgets, () => this.syncAllZones());
    },
    duplicateHeaderRowWidget(widget) {
      this.duplicateWidgetGeneric(widget, this.headerRowWidgets, () => this.syncAllZones());
    },
    duplicateSideColumnWidget(widget, side) {
      this.duplicateWidgetGeneric(widget, this.sideColumnWidgets[side], () => this.syncAllZones());
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
          this.$emit('update', this.localPage);
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
    },

    // Side Column Methods
    initializeSideColumns() {
      // Ensure sideColumns structure exists
      if (!this.localPage.layout.sideColumns) {
        this.localPage.layout.sideColumns = {
          left: { enabled: false, backgroundColor: '', widgets: [] },
          right: { enabled: false, backgroundColor: '', widgets: [] }
        };
      }

      // Initialize widget arrays
      ['left', 'right'].forEach(side => {
        if (!this.localPage.layout.sideColumns[side]) {
          this.localPage.layout.sideColumns[side] = { enabled: false, backgroundColor: '', widgets: [] };
        }
        this.sideColumnWidgets[side] = this.localPage.layout.sideColumns[side].widgets || [];

        // Ensure all widgets have IDs
        this.sideColumnWidgets[side].forEach(widget => {
          if (!widget.id) {
            widget.id = `widget-${this.nextWidgetId++}`;
          }
        });
      });
    },
    hasSideColumn(side) {
      return this.localPage.layout.sideColumns?.[side]?.enabled === true;
    },
    addSideColumn(side) {
      if (!this.localPage.layout.sideColumns) {
        this.localPage.layout.sideColumns = {};
      }
      this.localPage.layout.sideColumns[side] = {
        enabled: true,
        backgroundColor: '',
        widgets: []
      };
      this.sideColumnWidgets[side] = [];
      // Force reactivity
      this.localPage = structuredClone(this.localPage);
      this.$emit('update', this.localPage);
    },
    removeSideColumn(side) {
      this.showDeleteDialog = true;
      this.deleteDialogTitle = this.t('Remove column');
      this.deleteDialogMessage = this.t('Are you sure you want to remove this column?');
      this.deleteCallback = () => {
        if (this.localPage.layout.sideColumns?.[side]) {
          this.localPage.layout.sideColumns[side].enabled = false;
          this.localPage.layout.sideColumns[side].widgets = [];
          this.sideColumnWidgets[side] = [];
          // Force reactivity
          this.localPage = structuredClone(this.localPage);
          this.$emit('update', this.localPage);
        }
      };
    },
    getSideColumnWidgets(side) {
      return this.sideColumnWidgets[side] || [];
    },
    getSideColumnBgColor(side) {
      return this.localPage.layout.sideColumns?.[side]?.backgroundColor || '';
    },
    getSideColumnStyle(side) {
      const sideColumn = this.localPage.layout.sideColumns?.[side];
      if (!sideColumn) return {};

      const style = {};
      if (sideColumn.backgroundColor) {
        style.backgroundColor = sideColumn.backgroundColor;
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
    setSideColumnBackgroundColor(side, color) {
      if (this.localPage.layout.sideColumns?.[side]) {
        this.localPage.layout.sideColumns[side].backgroundColor = color;
        // Force reactivity
        this.localPage = structuredClone(this.localPage);
        this.$emit('update', this.localPage);
      }
    },
    showSideColumnWidgetPicker(side) {
      this.targetSideColumn = side;
      this.targetRowIndex = null;
      this.targetColumn = null;
      this.showWidgetPicker = true;
    },
    onSideColumnDragEnd(side) {
      this.$nextTick(() => {
        // Sync all zones to handle cross-zone drags
        this.syncAllZones();
      });
    },
    editSideColumnWidget(widget, side) {
      this.editingWidget = structuredClone(widget);
      this.editingSideColumn = side;
      this.editingRowIndex = null;
    },
    updateSideColumnWidget(updatedWidget, side) {
      const widgets = this.sideColumnWidgets[side];
      const index = widgets.findIndex(w => w.id === updatedWidget.id);
      if (index !== -1) {
        widgets[index] = updatedWidget;
        this.localPage.layout.sideColumns[side].widgets = [...widgets];
        this.$emit('update', this.localPage);
      }
    },
    deleteSideColumnWidget(side, widgetId) {
      this.showDeleteDialog = true;
      this.deleteDialogTitle = this.t('Delete widget');
      this.deleteDialogMessage = this.t('Are you sure you want to delete this widget?');
      this.deleteCallback = () => {
        const index = this.sideColumnWidgets[side].findIndex(w => w.id === widgetId);
        if (index !== -1) {
          this.sideColumnWidgets[side].splice(index, 1);
          this.localPage.layout.sideColumns[side].widgets = [...this.sideColumnWidgets[side]];
          this.$emit('update', this.localPage);
        }
      };
    },

    // Header Row Methods
    initializeHeaderRow() {
      // Ensure headerRow structure exists
      if (!this.localPage.layout.headerRow) {
        this.localPage.layout.headerRow = {
          enabled: false,
          backgroundColor: '',
          widgets: []
        };
      }

      // Initialize widget array
      this.headerRowWidgets = this.localPage.layout.headerRow.widgets || [];

      // Ensure all widgets have IDs
      this.headerRowWidgets.forEach(widget => {
        if (!widget.id) {
          widget.id = `widget-${this.nextWidgetId++}`;
        }
      });
    },
    addHeaderRow() {
      if (!this.localPage.layout.headerRow) {
        this.localPage.layout.headerRow = {};
      }
      this.localPage.layout.headerRow = {
        enabled: true,
        backgroundColor: '',
        widgets: []
      };
      this.headerRowWidgets = [];
      // Force reactivity
      this.localPage = structuredClone(this.localPage);
      this.$emit('update', this.localPage);
    },
    removeHeaderRow() {
      this.showDeleteDialog = true;
      this.deleteDialogTitle = this.t('Remove header row');
      this.deleteDialogMessage = this.t('Are you sure you want to remove the header row?');
      this.deleteCallback = () => {
        if (this.localPage.layout.headerRow) {
          this.localPage.layout.headerRow.enabled = false;
          this.localPage.layout.headerRow.widgets = [];
          this.headerRowWidgets = [];
          // Force reactivity
          this.localPage = structuredClone(this.localPage);
          this.$emit('update', this.localPage);
        }
      };
    },
    getHeaderRowBgColor() {
      return this.localPage.layout.headerRow?.backgroundColor || '';
    },
    getHeaderRowStyle() {
      const headerRow = this.localPage.layout.headerRow;
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
    setHeaderRowBackgroundColor(color) {
      if (this.localPage.layout.headerRow) {
        this.localPage.layout.headerRow.backgroundColor = color;
        // Force reactivity
        this.localPage = structuredClone(this.localPage);
        this.$emit('update', this.localPage);
      }
    },
    showHeaderRowWidgetPicker() {
      this.targetHeaderRow = true;
      this.targetSideColumn = null;
      this.targetRowIndex = null;
      this.targetColumn = null;
      this.showWidgetPicker = true;
    },
    onHeaderRowDragEnd() {
      this.$nextTick(() => {
        // Sync all zones to handle cross-zone drags
        this.syncAllZones();
      });
    },
    editHeaderRowWidget(widget) {
      this.editingWidget = structuredClone(widget);
      this.editingHeaderRow = true;
      this.editingSideColumn = null;
      this.editingRowIndex = null;
    },
    updateHeaderRowWidget(updatedWidget) {
      const index = this.headerRowWidgets.findIndex(w => w.id === updatedWidget.id);
      if (index !== -1) {
        this.headerRowWidgets[index] = updatedWidget;
        this.localPage.layout.headerRow.widgets = [...this.headerRowWidgets];
        this.$emit('update', this.localPage);
      }
    },
    deleteHeaderRowWidget(widgetId) {
      this.showDeleteDialog = true;
      this.deleteDialogTitle = this.t('Delete widget');
      this.deleteDialogMessage = this.t('Are you sure you want to delete this widget?');
      this.deleteCallback = () => {
        const index = this.headerRowWidgets.findIndex(w => w.id === widgetId);
        if (index !== -1) {
          this.headerRowWidgets.splice(index, 1);
          this.localPage.layout.headerRow.widgets = [...this.headerRowWidgets];
          this.$emit('update', this.localPage);
        }
      };
    }
  }
};
</script>

<style scoped>
.page-editor-wrapper {
  display: flex;
  flex-direction: column;
  width: 100%;
}

.page-editor-container {
  display: flex;
  gap: 16px;
  width: 100%;
}

.page-editor {
  flex: 1;
  min-width: 0;
  width: 100%;
}

/* Header Row Editor Styles */
.header-row-editor {
  width: 100%;
  padding: 16px;
  margin-bottom: 16px;
  border: 2px dashed var(--color-border-dark);
  border-radius: var(--border-radius-large);
  background-color: transparent;
  box-sizing: border-box;
}

.header-row-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  padding-bottom: 8px;
  border-bottom: 1px solid var(--color-border);
}

.header-row-title {
  font-weight: 600;
  font-size: 14px;
}

.header-row-actions {
  display: flex;
  gap: 4px;
}

.header-row-widgets {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.header-widget-drop-zone {
  min-height: 80px;
}

.add-header-row-container {
  display: flex;
  justify-content: center;
  margin-bottom: 16px;
  padding: 12px;
  background: var(--color-background-hover);
  border-radius: var(--border-radius-large);
}

.add-header-row-btn {
  width: auto;
}

/* Side Column Editor Styles */
.side-column-editor {
  flex-shrink: 0;
  width: 280px;
  min-width: 250px;
  max-width: 320px;
  padding: 16px;
  border: 2px dashed var(--color-border-dark);
  border-radius: var(--border-radius-large);
  background-color: var(--color-background-dark);
  box-sizing: border-box;
  align-self: flex-start;
}

.side-column-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  padding-bottom: 8px;
  border-bottom: 1px solid var(--color-border);
}

.side-column-title {
  font-weight: 600;
  font-size: 14px;
}

.side-column-actions {
  display: flex;
  gap: 4px;
}

.side-column-widgets {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.side-widget-drop-zone {
  min-height: 80px;
}

.side-column-buttons {
  display: flex;
  gap: 8px;
  margin-bottom: 16px;
  padding: 12px;
  background: var(--color-background-hover);
  border-radius: var(--border-radius-large);
}

/* Mobile responsive */
@media (max-width: 768px) {
  .page-editor-container {
    flex-direction: column;
  }

  .side-column-editor {
    width: 100%;
    min-width: 100%;
    max-width: 100%;
    order: 1;
  }

  .side-column-left {
    order: -1;
  }

  .page-editor {
    order: 0;
  }

  .side-column-buttons {
    flex-direction: column;
  }

  /* Row controls responsive */
  .row-controls {
    flex-wrap: wrap;
    gap: 8px;
    padding: 8px;
  }

  .collapsible-controls {
    flex-wrap: wrap;
    gap: 6px;
    margin-right: 8px;
  }

  .row-section-title {
    flex: 1 1 100%;
    min-width: 100px;
    max-width: none;
    margin-right: 0;
    margin-top: 4px;
  }

  .default-collapsed-label {
    margin-right: 8px;
  }

  /* Widget wrapper responsive */
  .widget-wrapper {
    padding: 8px;
  }

  .floating-toolbar {
    flex-wrap: wrap;
    gap: 4px;
    padding: 4px;
  }

  .floating-toolbar button {
    padding: 4px 6px;
    min-width: 28px;
    height: 28px;
  }
}

@media (max-width: 480px) {
  .row-controls {
    padding: 6px;
  }

  .collapsible-toggle-label {
    font-size: 12px;
  }

  .row-section-title {
    height: 32px;
    font-size: 14px;
  }

  .default-collapsed-label {
    font-size: 11px;
  }

  .column-button.small {
    width: 24px;
    height: 24px;
    font-size: 11px;
  }
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

/* Row drag handle */
.row-drag-handle {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 4px 8px;
  margin-right: 8px;
  cursor: grab;
  color: inherit;
  opacity: 0.6;
  border-radius: var(--border-radius);
  transition: all 0.15s ease;
}

.row-drag-handle:hover {
  background: rgba(0, 0, 0, 0.1);
  opacity: 1;
}

.row-drag-handle:active {
  cursor: grabbing;
  background: rgba(0, 0, 0, 0.15);
}

/* Collapsible controls container */
.collapsible-controls {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-right: 12px;
}

/* Main collapsible toggle checkbox with label */
.collapsible-toggle-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  font-weight: 500;
  color: inherit;
  cursor: pointer;
  white-space: nowrap;
  user-select: none;
}

.collapsible-toggle-label input[type="checkbox"] {
  margin: 0;
  cursor: pointer;
  width: 16px;
  height: 16px;
  accent-color: var(--color-primary-element);
}

/* Section title input */
.row-section-title {
  flex: 0 1 200px;
  min-width: 120px;
  max-width: 300px;
  height: 28px;
  margin-right: 12px;
  padding: 4px 8px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-main-background);
  font-size: 13px;
  font-weight: 600;
  color: var(--color-main-text);
}

.row-section-title:focus {
  outline: none;
  border-color: var(--color-primary-element);
  box-shadow: 0 0 0 2px var(--color-primary-element-light);
}

.row-section-title::placeholder {
  color: var(--color-text-maxcontrast);
  font-weight: normal;
}

/* Default collapsed checkbox label */
.default-collapsed-label {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-right: 12px;
  font-size: 12px;
  color: inherit;
  opacity: 0.8;
  cursor: pointer;
  white-space: nowrap;
}

.default-collapsed-label input[type="checkbox"] {
  margin: 0;
  cursor: pointer;
}

/* Row content container */
.row-content {
  /* No overflow: hidden - needed for floating toolbar visibility */
}

.row-content.collapsed {
  display: none;
}

/* Rows container for drag-and-drop */
.rows-container {
  display: flex;
  flex-direction: column;
}

.row-wrapper {
  display: flex;
  flex-direction: column;
}

/* Drag ghost styling */
.sortable-ghost .page-row {
  opacity: 0.5;
  background: var(--color-primary-element-light);
}

.sortable-drag .page-row {
  opacity: 1;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
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
