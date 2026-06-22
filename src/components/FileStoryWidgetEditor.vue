<template>
  <div class="file-story-editor">
    <!-- ============ SOURCE ============ -->
    <h4 class="fse-section-heading">{{ t('intravox', 'Source') }}</h4>

    <div class="editor-section">
      <label class="editor-label" for="fse-folder">{{ t('intravox', 'Source folder') }}</label>
      <div class="fse-folder-row">
        <input
          id="fse-folder"
          type="text"
          v-model="localConfig.folderPath"
          :placeholder="t('intravox', '/Documents/ …')"
          class="editor-input"
          @change="emitUpdate"
        />
        <NcButton type="secondary" @click="browseFolder">
          <template #icon>
            <FolderOpen :size="18" />
          </template>
          {{ t('intravox', 'Browse …') }}
        </NcButton>
      </div>
      <p class="editor-hint">{{ t('intravox', 'Pick a folder. Documents are streamed directly from this location.') }}</p>
      <div class="fse-capability-badge" :class="{ rich: metaVoxAvailable && !sourceIsFederated }">
        <CheckCircle v-if="metaVoxAvailable && !sourceIsFederated" :size="14" />
        <AlertCircle v-else :size="14" />
        <span v-if="metaVoxAvailable && !sourceIsFederated">{{ t('intravox', 'MetaVox: rich metadata available') }}</span>
        <span v-else-if="sourceIsFederated">{{ t('intravox', 'Federated share — MetaVox metadata is not available because the source lives on another Nextcloud instance. Files are shown with name, date and file type only.') }}</span>
        <span v-else>{{ t('intravox', 'MetaVox not available — basic file metadata only') }}</span>
      </div>
    </div>

    <!-- MetaVox filter builder (only when MetaVox is available and source is not pure-federated) -->
    <div class="editor-section" v-if="metaVoxAvailable && !sourceIsFederated">
      <div class="editor-label">{{ t('intravox', 'Filters') }}</div>
      <p v-if="!localConfig.metaVoxFilters || localConfig.metaVoxFilters.length === 0" class="editor-hint">
        {{ t('intravox', 'No filters. Click "Add filter" to filter by author, project, status or any other MetaVox field.') }}
      </p>
      <div v-for="(filter, idx) in localConfig.metaVoxFilters || []" :key="idx" class="fse-filter-row">
        <NcSelect
          :model-value="fieldOptionFor(filter.field)"
          :options="filterFieldOptions"
          :placeholder="t('intravox', 'Field')"
          class="fse-filter-field"
          @update:model-value="setFilterField(idx, $event)"
        />
        <NcSelect
          :model-value="opOptionFor(filter.op, filter.field)"
          :options="operatorOptionsFor(filter.field)"
          :placeholder="t('intravox', 'Operator')"
          class="fse-filter-op"
          @update:model-value="setFilterOp(idx, $event)"
        />
        <NcSelect
          v-if="valueRenderFor(filter) === 'select'"
          :model-value="filter.value"
          :options="valueOptionsFor(filter.field)"
          :taggable="filter.op === 'in' || valueOptionsFor(filter.field).length === 0"
          :multiple="filter.op === 'in'"
          :placeholder="t('intravox', 'Value')"
          class="fse-filter-value"
          @update:model-value="setFilterValue(idx, $event)"
        />
        <input
          v-else-if="valueRenderFor(filter) === 'date'"
          type="date"
          :value="filter.value"
          class="editor-input fse-filter-value-text"
          @input="setFilterValue(idx, $event.target.value)"
        />
        <input
          v-else-if="valueRenderFor(filter) === 'year'"
          type="number"
          min="1900"
          max="2100"
          step="1"
          :value="filter.value"
          :placeholder="t('intravox', 'Year')"
          class="editor-input fse-filter-value-text fse-narrow"
          @input="setFilterValue(idx, $event.target.value)"
        />
        <input
          v-else-if="valueRenderFor(filter) === 'number'"
          type="number"
          :value="filter.value"
          :placeholder="t('intravox', 'Value')"
          class="editor-input fse-filter-value-text fse-narrow"
          @input="setFilterValue(idx, $event.target.value)"
        />
        <NcCheckboxRadioSwitch
          v-else-if="valueRenderFor(filter) === 'checkbox'"
          :model-value="filter.value === '1' || filter.value === true || filter.value === 'true'"
          @update:model-value="setFilterValue(idx, $event ? '1' : '0')"
        >
          {{ t('intravox', 'Checked') }}
        </NcCheckboxRadioSwitch>
        <input
          v-else
          type="text"
          :value="filter.value"
          :placeholder="t('intravox', 'Value')"
          class="editor-input fse-filter-value-text"
          @input="setFilterValue(idx, $event.target.value)"
        />
        <NcButton type="tertiary-no-background" :title="t('intravox', 'Remove filter')" @click="removeFilter(idx)">
          <template #icon>
            <Close :size="16" />
          </template>
        </NcButton>
      </div>
      <NcButton type="secondary" @click="addFilter">
        <template #icon>
          <Plus :size="16" />
        </template>
        {{ t('intravox', 'Add filter') }}
      </NcButton>
    </div>

    <!-- ============ LAYOUT ============ -->
    <h4 class="fse-section-heading">{{ t('intravox', 'Layout') }}</h4>

    <div class="editor-section">
      <div class="editor-label">{{ t('intravox', 'Layout mode') }}</div>
      <div class="fse-mode-grid">
        <button
          v-for="opt in modeOptions"
          :key="opt.value"
          type="button"
          class="fse-mode-btn"
          :class="{ active: localConfig.mode === opt.value }"
          @click="setMode(opt.value)"
        >
          <component :is="opt.icon" :size="24" />
          <span>{{ opt.label }}</span>
        </button>
      </div>
      <p class="editor-hint">{{ modeHint }}</p>
    </div>

    <!-- Tile size (only for tiles mode) -->
    <div v-if="localConfig.mode === 'tiles'" class="editor-section">
      <div class="editor-label">{{ t('intravox', 'Tile size') }}</div>
      <div class="fse-tile-size-row">
        <button
          v-for="opt in tileSizeOptions"
          :key="opt.value"
          type="button"
          class="fse-mode-btn fse-tile-size-btn"
          :class="{ active: (localConfig.tileSize || 'medium') === opt.value }"
          @click="setTileSize(opt.value)"
        >
          <span>{{ opt.label }}</span>
        </button>
      </div>
    </div>

    <!-- Group-by (only for grouped mode) -->
    <div v-if="localConfig.mode === 'grouped'" class="editor-section">
      <div class="editor-label">{{ t('intravox', 'Group by') }}</div>
      <NcSelect
        :model-value="groupByOption"
        :options="groupByOptions"
        :placeholder="t('intravox', 'Group by')"
        @update:model-value="setGroupBy"
      />
      <p class="editor-hint">{{ t('intravox', 'Pick what to cluster files under. "Category" groups by file type (PDF / Spreadsheets / etc); other options come from MetaVox fields.') }}</p>
    </div>

    <!-- Timeline granularity (only for timeline mode) -->
    <div v-if="localConfig.mode === 'timeline'" class="editor-section">
      <div class="editor-label">{{ t('intravox', 'Group by date') }}</div>
      <div class="fse-granularity-row">
        <NcCheckboxRadioSwitch
          v-for="opt in granularityOptions"
          :key="opt.value"
          :model-value="localConfig.granularity || 'month'"
          :value="opt.value"
          name="fse-granularity"
          type="radio"
          @update:model-value="setGranularity"
        >
          {{ opt.label }}
        </NcCheckboxRadioSwitch>
      </div>
      <p class="editor-hint">{{ t('intravox', 'Per day works for daily reports / notes; per month suits monthly newsletters and quarterly contracts; per year is for archives.') }}</p>
    </div>

    <!-- Sort -->
    <div class="editor-section">
      <div class="editor-label">{{ t('intravox', 'Order') }}</div>
      <div class="fse-sort-row">
        <NcSelect
          :model-value="sortByOption"
          :options="sortByOptions"
          :placeholder="t('intravox', 'Sort by')"
          class="fse-sort-by"
          @update:model-value="setSortBy"
        />
        <div class="fse-sort-group">
          <NcCheckboxRadioSwitch
            :model-value="localConfig.sortOrder || 'desc'"
            value="desc"
            name="fse-sort"
            type="radio"
            @update:model-value="setSortOrder"
          >
            {{ sortDirLabelDesc }}
          </NcCheckboxRadioSwitch>
          <NcCheckboxRadioSwitch
            :model-value="localConfig.sortOrder || 'desc'"
            value="asc"
            name="fse-sort"
            type="radio"
            @update:model-value="setSortOrder"
          >
            {{ sortDirLabelAsc }}
          </NcCheckboxRadioSwitch>
        </div>
      </div>
    </div>

    <!-- ============ DISPLAY ============ -->
    <h4 class="fse-section-heading">{{ t('intravox', 'Display') }}</h4>

    <!-- Visible columns: gebruiker kiest wat naast bestandsnaam getoond wordt -->
    <div class="editor-section">
      <div class="editor-label">{{ t('intravox', 'Show columns') }}</div>
      <div class="fse-columns">
        <NcCheckboxRadioSwitch
          v-for="opt in columnOptions"
          :key="opt.value"
          :model-value="isColumnVisible(opt.value)"
          @update:model-value="toggleColumn(opt.value, $event)"
        >
          {{ opt.label }}
        </NcCheckboxRadioSwitch>
      </div>
      <p class="editor-hint">{{ t('intravox', 'Pick which fields appear next to the filename. The filename + file-type icon are always shown.') }}</p>
    </div>

    <div class="editor-section">
      <label class="editor-label" for="fse-limit">{{ t('intravox', 'Maximum documents') }}</label>
      <input
        id="fse-limit"
        type="number"
        min="1"
        max="500"
        v-model.number="localConfig.limit"
        :placeholder="t('intravox', 'All')"
        class="editor-input fse-narrow"
        @input="emitUpdate"
      />
      <p class="editor-hint">{{ t('intravox', 'Cap the total number of documents. Leave blank to load everything via infinite scroll.') }}</p>
    </div>
  </div>
</template>

<script>
import axios from '@nextcloud/axios';
import { generateUrl } from '@nextcloud/router';
import { translate, translatePlural } from '@nextcloud/l10n';
import { NcButton, NcCheckboxRadioSwitch, NcSelect } from '@nextcloud/vue';
import FolderOpen from 'vue-material-design-icons/FolderOpen.vue';
import CheckCircle from 'vue-material-design-icons/CheckCircle.vue';
import AlertCircle from 'vue-material-design-icons/AlertCircle.vue';
import Close from 'vue-material-design-icons/Close.vue';
import Plus from 'vue-material-design-icons/Plus.vue';
import ViewList from 'vue-material-design-icons/ViewList.vue';
import ViewGrid from 'vue-material-design-icons/ViewGrid.vue';
import FormatListBulleted from 'vue-material-design-icons/FormatListBulleted.vue';
import FolderMultiple from 'vue-material-design-icons/FolderMultiple.vue';

export default {
  name: 'FileStoryWidgetEditor',
  components: {
    NcButton, NcCheckboxRadioSwitch, NcSelect,
    FolderOpen, CheckCircle, AlertCircle, Close, Plus,
    ViewList, ViewGrid, FormatListBulleted, FolderMultiple,
  },
  props: {
    widget: { type: Object, required: true },
  },
  emits: ['update'],
  data() {
    return {
      localConfig: this.createDefaultConfig(),
      metaVoxAvailable: false,
      metaVoxFields: [],
      sourceIsFederated: false,
      sourceFederatedInfo: null,
      _capabilitiesDebounce: null,
    };
  },
  computed: {
    modeOptions() {
      return [
        { value: 'timeline', label: this.t('intravox', 'Timeline'), icon: 'ViewList' },
        { value: 'tiles', label: this.t('intravox', 'Tiles'), icon: 'ViewGrid' },
        { value: 'list', label: this.t('intravox', 'List'), icon: 'FormatListBulleted' },
        { value: 'grouped', label: this.t('intravox', 'Grouped'), icon: 'FolderMultiple' },
      ];
    },
    tileSizeOptions() {
      return [
        { value: 'small', label: this.t('intravox', 'Small') },
        { value: 'medium', label: this.t('intravox', 'Medium') },
        { value: 'large', label: this.t('intravox', 'Large') },
      ];
    },
    modeHint() {
      switch (this.localConfig.mode) {
        case 'timeline':
          return this.t('intravox', 'Chronological — documents grouped by date with sticky headers. Granularity (day/month/year) configurable below.');
        case 'tiles':
          return this.t('intravox', 'Visual grid — each document rendered as a tile with first-page preview thumbnail. Best for browsable document libraries.');
        case 'list':
          return this.t('intravox', 'Flat sortable list — no grouping.');
        case 'grouped':
          return this.t('intravox', 'Cluster documents by category or MetaVox field (author, project, status, …).');
        default:
          return '';
      }
    },
    granularityOptions() {
      return [
        { value: 'day', label: this.t('intravox', 'Per day') },
        { value: 'month', label: this.t('intravox', 'Per month') },
        { value: 'year', label: this.t('intravox', 'Per year') },
      ];
    },
    columnOptions() {
      return [
        { value: 'date', label: this.t('intravox', 'Date') },
        { value: 'size', label: this.t('intravox', 'File size') },
        { value: 'path', label: this.t('intravox', 'Folder path') },
      ];
    },
    sortByOptions() {
      const base = [
        { label: this.t('intravox', 'Date modified'), value: 'mtime' },
        { label: this.t('intravox', 'Filename'), value: 'name' },
        { label: this.t('intravox', 'File size'), value: 'size' },
      ];
      if (this.sourceIsFederated) return base;
      const skip = new Set(['multiselect', 'tags', 'checkbox']);
      const meta = (this.metaVoxFields || [])
        .filter(f => !skip.has(f.field_type))
        .map(f => ({ label: f.field_label || f.field_name, value: f.field_name }));
      return [...base, ...meta];
    },
    sortByOption() {
      const v = this.localConfig.sortBy || 'mtime';
      return this.sortByOptions.find(o => o.value === v) || this.sortByOptions[0];
    },
    sortDirLabelDesc() {
      const v = this.localConfig.sortBy || 'mtime';
      if (v === 'mtime') return this.t('intravox', 'Newest first');
      if (v === 'name') return this.t('intravox', 'Z → A');
      if (v === 'size') return this.t('intravox', 'Largest first');
      return this.t('intravox', 'Descending');
    },
    sortDirLabelAsc() {
      const v = this.localConfig.sortBy || 'mtime';
      if (v === 'mtime') return this.t('intravox', 'Oldest first');
      if (v === 'name') return this.t('intravox', 'A → Z');
      if (v === 'size') return this.t('intravox', 'Smallest first');
      return this.t('intravox', 'Ascending');
    },
    groupByOptions() {
      const base = [
        { label: this.t('intravox', 'Category (file type)'), value: 'category' },
      ];
      if (this.sourceIsFederated) return base;
      // Single-valued MetaVox fields make sense for grouping (multiselect doesn't).
      const okTypes = new Set(['text', 'select', 'dropdown', 'user', 'date']);
      const meta = (this.metaVoxFields || [])
        .filter(f => okTypes.has(f.field_type))
        .map(f => ({ label: f.field_label || f.field_name, value: f.field_name }));
      return [...base, ...meta];
    },
    groupByOption() {
      const v = this.localConfig.groupBy || 'category';
      return this.groupByOptions.find(o => o.value === v) || this.groupByOptions[0];
    },
    filterFieldOptions() {
      return this.metaVoxFields.map(f => ({
        label: f.field_label || f.field_name,
        value: f.field_name,
      }));
    },
  },
  watch: {
    widget: {
      immediate: true,
      deep: true,
      handler(w) {
        this.localConfig = { ...this.createDefaultConfig(), ...(w.config || {}) };
      },
    },
    'localConfig.folderPath'() {
      if (this._capabilitiesDebounce) clearTimeout(this._capabilitiesDebounce);
      this._capabilitiesDebounce = setTimeout(() => this.fetchCapabilities(), 300);
    },
  },
  mounted() {
    this.fetchCapabilities();
    this.loadMetaVoxFields();
  },
  methods: {
    t(app, text, vars) {
      return translate(app, text, vars);
    },
    createDefaultConfig() {
      return {
        folderPath: '',
        mode: 'timeline',
        groupBy: 'category',
        granularity: 'month',           // sensible default for documents
        limit: null,
        sortOrder: 'desc',
        sortBy: 'mtime',
        dateField: 'mtime',
        visibleColumns: ['date'],
        tileSize: 'medium',
        metaVoxFilters: [],
      };
    },
    async fetchCapabilities() {
      try {
        const url = generateUrl('/apps/intravox/api/file-story/capabilities');
        const folder = (this.localConfig.folderPath || '').trim();
        const res = await axios.get(url, { params: folder !== '' ? { folder } : {} });
        this.metaVoxAvailable = !!res.data?.metaVoxAvailable;
        this.sourceIsFederated = !!res.data?.sourceIsFederated;
        this.sourceFederatedInfo = res.data?.sourceFederatedInfo || null;
      } catch (e) {
        this.metaVoxAvailable = false;
        this.sourceIsFederated = false;
        this.sourceFederatedInfo = null;
      }
    },
    async loadMetaVoxFields() {
      try {
        const url = generateUrl('/apps/intravox/api/file-story/metavox-fields');
        const res = await axios.get(url);
        this.metaVoxFields = Array.isArray(res.data?.fields) ? res.data.fields : [];
      } catch (e) {
        this.metaVoxFields = [];
      }
    },
    async browseFolder() {
      // Use NC file-picker dialog via OC API. Falls back to no-op if unavailable.
      try {
        if (typeof window !== 'undefined' && window.OC && window.OC.dialogs && typeof window.OC.dialogs.filepicker === 'function') {
          window.OC.dialogs.filepicker(
            this.t('intravox', 'Pick a folder'),
            (path) => {
              if (typeof path !== 'string') return;
              // OC.dialogs.filepicker returns '' when the user picks the
              // top-level "Home" / root in the picker. Translate that to '/'
              // which our backend treats as "entire drive". Otherwise the
              // widget would silently lose its configured folder.
              this.localConfig.folderPath = path === '' ? '/' : path;
              this.emitUpdate();
            },
            false,
            'httpd/unix-directory',
            true,
          );
        }
      } catch (e) { /* ignore */ }
    },
    setMode(value) {
      this.localConfig.mode = value;
      this.emitUpdate();
    },
    setTileSize(value) {
      if (!['small', 'medium', 'large'].includes(value)) return;
      this.localConfig.tileSize = value;
      this.emitUpdate();
    },
    setSortOrder(val) {
      if (val !== 'asc' && val !== 'desc') return;
      this.localConfig.sortOrder = val;
      this.emitUpdate();
    },
    setSortBy(opt) {
      this.localConfig.sortBy = opt && opt.value ? String(opt.value) : 'mtime';
      this.emitUpdate();
    },
    setGroupBy(opt) {
      this.localConfig.groupBy = opt && opt.value ? String(opt.value) : 'category';
      this.emitUpdate();
    },
    // ============ MetaVox filter-builder helpers ============
    // Identical to PhotoStoryWidgetEditor — both widgets share the same shape
    // so the values round-trip through PageService::sanitizeWidget unchanged.
    fieldDefByName(name) {
      return this.metaVoxFields.find(f => f.field_name === name) || null;
    },
    fieldOptionFor(fieldName) {
      if (!fieldName) return null;
      const def = this.fieldDefByName(fieldName);
      return { label: def?.field_label || fieldName, value: fieldName };
    },
    opOptionFor(op, fieldName) {
      if (!op) return null;
      const opts = this.operatorOptionsFor(fieldName);
      return opts.find(o => o.value === op) || { label: op, value: op };
    },
    operatorOptionsFor(fieldName) {
      const def = this.fieldDefByName(fieldName);
      const type = def?.field_type || 'text';
      switch (type) {
        case 'multiselect':
        case 'tags':
          return [
            { label: this.t('intravox', 'contains'), value: 'contains' },
            { label: this.t('intravox', 'is any of'), value: 'in' },
          ];
        case 'date':
        case 'datetime':
          return [
            { label: this.t('intravox', 'equals'), value: 'equals' },
            { label: this.t('intravox', 'year is'), value: 'year_equals' },
          ];
        case 'select':
        case 'dropdown':
          return [
            { label: this.t('intravox', 'equals'), value: 'equals' },
            { label: this.t('intravox', 'is any of'), value: 'in' },
          ];
        case 'checkbox':
        case 'number':
          return [{ label: this.t('intravox', 'equals'), value: 'equals' }];
        default:
          return [
            { label: this.t('intravox', 'equals'), value: 'equals' },
            { label: this.t('intravox', 'contains'), value: 'contains' },
          ];
      }
    },
    valueOptionsFor(fieldName) {
      const def = this.fieldDefByName(fieldName);
      if (!def || !Array.isArray(def.options)) return [];
      return def.options;
    },
    valueRenderFor(filter) {
      const def = this.fieldDefByName(filter.field);
      const type = def?.field_type || 'text';
      const op = filter.op || 'equals';
      if ((type === 'date' || type === 'datetime') && op === 'year_equals') return 'year';
      if (type === 'date' || type === 'datetime') return 'date';
      if (type === 'number') return 'number';
      if (type === 'checkbox') return 'checkbox';
      if (this.valueOptionsFor(filter.field).length > 0) return 'select';
      if (type === 'multiselect' || type === 'tags') return 'select';
      return 'text';
    },
    addFilter() {
      if (!Array.isArray(this.localConfig.metaVoxFilters)) {
        this.localConfig.metaVoxFilters = [];
      }
      this.localConfig.metaVoxFilters.push({ field: '', op: 'equals', value: '' });
      this.emitUpdate();
    },
    removeFilter(idx) {
      if (!Array.isArray(this.localConfig.metaVoxFilters)) return;
      this.localConfig.metaVoxFilters.splice(idx, 1);
      this.emitUpdate();
    },
    setFilterField(idx, selected) {
      if (!Array.isArray(this.localConfig.metaVoxFilters)) return;
      const value = selected && typeof selected === 'object' ? selected.value : selected;
      this.localConfig.metaVoxFilters[idx].field = value || '';
      // Reset operator to a valid default for the new field type
      const opts = this.operatorOptionsFor(value || '');
      if (opts.length && !opts.find(o => o.value === this.localConfig.metaVoxFilters[idx].op)) {
        this.localConfig.metaVoxFilters[idx].op = opts[0].value;
      }
      this.emitUpdate();
    },
    setFilterOp(idx, selected) {
      if (!Array.isArray(this.localConfig.metaVoxFilters)) return;
      const value = selected && typeof selected === 'object' ? selected.value : selected;
      this.localConfig.metaVoxFilters[idx].op = value || 'equals';
      this.emitUpdate();
    },
    setFilterValue(idx, value) {
      if (!Array.isArray(this.localConfig.metaVoxFilters)) return;
      let v = value;
      if (v && typeof v === 'object' && !Array.isArray(v)) v = v.value;
      this.localConfig.metaVoxFilters[idx].value = v ?? '';
      this.emitUpdate();
    },
    setGranularity(val) {
      if (!['day', 'month', 'year'].includes(val)) return;
      this.localConfig.granularity = val;
      this.emitUpdate();
    },
    setDateField(val) {
      if (!['mtime', 'taken_at', 'created'].includes(val)) return;
      this.localConfig.dateField = val;
      this.emitUpdate();
    },
    isColumnVisible(key) {
      const cols = this.localConfig.visibleColumns;
      if (!Array.isArray(cols)) return key === 'date';
      return cols.includes(key);
    },
    toggleColumn(key, on) {
      let cols = Array.isArray(this.localConfig.visibleColumns) ? [...this.localConfig.visibleColumns] : ['date'];
      if (on) {
        if (!cols.includes(key)) cols.push(key);
      } else {
        cols = cols.filter(c => c !== key);
      }
      this.localConfig.visibleColumns = cols;
      this.emitUpdate();
    },
    emitUpdate() {
      this.$emit('update', { ...this.widget, config: { ...this.localConfig } });
    },
  },
};
</script>

<style scoped>
.file-story-editor {
  display: flex;
  flex-direction: column;
  gap: 20px;
  padding: 16px;
  /* The NcModal that hosts this editor doesn't scroll content beyond its
     viewport; without an inner cap the bottom of the form (Save/Cancel)
     ends up cut off on shorter laptop screens. Mirror PhotoStoryEditor's
     approach by giving the form a bounded height with its own scrollbar. */
  max-height: calc(100vh - 200px);
  overflow-y: auto;
}

.fse-section-heading {
  margin: 24px 0 4px;
  padding-bottom: 6px;
  border-bottom: 1px solid var(--color-border);
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--color-text-maxcontrast);
}

.fse-section-heading:first-child {
  margin-top: 0;
}

.editor-section {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.editor-label {
  font-size: 13px;
  font-weight: 600;
}

.editor-input {
  padding: 6px 10px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-main-background);
  color: var(--color-main-text);
}

.editor-hint {
  font-size: 11px;
  color: var(--color-text-maxcontrast);
  margin: 0;
}

.fse-narrow {
  max-width: 120px;
}

.fse-folder-row {
  display: flex;
  gap: 8px;
}

.fse-folder-row .editor-input {
  flex: 1;
}

.fse-capability-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  margin-top: 8px;
  border-radius: var(--border-radius);
  background: var(--color-background-hover);
  color: var(--color-text-maxcontrast);
  font-size: 11px;
  width: max-content;
}

.fse-capability-badge.rich {
  background: var(--color-primary-element-light);
  color: var(--color-primary-element-light-text);
}

.fse-mode-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
}

/* Tile-size segmented buttons: shorter than mode buttons (no icon, just label) */
.fse-tile-size-row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
}

.fse-tile-size-btn {
  /* Reuse .fse-mode-btn padding/border but lighter: no icon row, single line label */
  padding: 8px 12px;
}

.fse-mode-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  padding: 12px 8px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-main-background);
  color: var(--color-main-text);
  cursor: pointer;
  font-size: 12px;
  transition: background 0.12s ease, border-color 0.12s ease;
}

.fse-mode-btn:hover {
  background: var(--color-background-hover);
}

.fse-mode-btn.active {
  background: var(--color-primary-element-light);
  border-color: var(--color-primary-element);
  color: var(--color-primary-element-light-text);
}

.fse-sort-row {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.fse-sort-by {
  flex: 1;
  min-width: 200px;
  max-width: 280px;
}

.fse-sort-group {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.fse-granularity-row,
.fse-date-row {
  display: flex;
  gap: 14px;
  flex-wrap: wrap;
}

.fse-columns {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 4px 12px;
}

/* MetaVox filter rows */
.fse-filter-row {
  display: grid;
  grid-template-columns: 1fr 1fr 1.5fr auto;
  gap: 8px;
  align-items: center;
  margin-bottom: 6px;
}

.fse-filter-field, .fse-filter-op, .fse-filter-value {
  min-width: 0;
}

.fse-filter-value-text {
  width: 100%;
}
</style>
