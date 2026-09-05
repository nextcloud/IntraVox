<template>
  <div class="news-widget-editor">
    <!-- Widget Title -->
    <div class="editor-section">
      <label class="editor-label" for="news-widget-title">{{ t('intravox', 'Widget title (optional)') }}</label>
      <input
        id="news-widget-title"
        type="text"
        v-model="localWidget.title"
        :placeholder="t('intravox', 'e.g., latest news')"
        class="editor-input"
        @input="emitUpdate"
      />
    </div>

    <!-- Background Color -->
    <div class="editor-section">
      <label class="editor-label">{{ t('intravox', 'Background color') }}</label>
      <div class="color-presets">
        <button
          type="button"
          :class="{ active: !localWidget.backgroundColor }"
          @click="setBackgroundColor(null)"
          class="color-preset-btn"
        >
          {{ t('intravox', 'None') }}
        </button>
        <button
          type="button"
          :class="{ active: localWidget.backgroundColor === 'var(--color-background-hover)' }"
          @click="setBackgroundColor('var(--color-background-hover)')"
          class="color-preset-btn"
        >
          {{ t('intravox', 'Light') }}
        </button>
        <button
          type="button"
          :class="{ active: localWidget.backgroundColor === 'var(--color-primary-element)' }"
          @click="setBackgroundColor('var(--color-primary-element)')"
          class="color-preset-btn"
        >
          {{ t('intravox', 'Primary') }}
        </button>
      </div>
    </div>

    <!-- Source Location -->
    <div class="editor-section">
      <label class="editor-label">{{ t('intravox', 'Source location') }}</label>
      <PageTreeSelect
        v-model="localWidget.sourcePageId"
        :placeholder="t('intravox', 'All pages')"
        :clearable="true"
        @select="handleSourceSelect"
      />
      <p class="editor-hint">{{ t('intravox', 'Select a page or folder to show content from, including all subpages') }}</p>
    </div>

    <!-- Layout Selection -->
    <div class="editor-section">
      <label class="editor-label">{{ t('intravox', 'Layout') }}</label>
      <div class="layout-options">
        <button
          v-for="layout in layoutOptions"
          :key="layout.value"
          class="layout-option"
          :class="{ 'layout-option--active': localWidget.layout === layout.value }"
          @click="setLayout(layout.value)"
        >
          <component :is="layout.icon" :size="24" />
          <span>{{ layout.label }}</span>
        </button>
      </div>
    </div>

    <!-- Grid Columns (only for grid layout) -->
    <div v-if="localWidget.layout === 'grid'" class="editor-section">
      <label class="editor-label">{{ t('intravox', 'Columns') }}</label>
      <div class="columns-selector">
        <button
          v-for="cols in [2, 3, 4]"
          :key="cols"
          class="column-option"
          :class="{ 'column-option--active': localWidget.columns === cols }"
          @click="setColumns(cols)"
        >
          {{ cols }}
        </button>
      </div>
    </div>

    <!-- Autoplay Interval (only for carousel layout) -->
    <div v-if="localWidget.layout === 'carousel'" class="editor-section">
      <label class="editor-label" for="news-widget-autoplay">{{ t('intravox', 'Autoplay interval (seconds)') }}</label>
      <div class="limit-selector">
        <input
          id="news-widget-autoplay"
          type="range"
          v-model.number="localWidget.autoplayInterval"
          min="0"
          max="30"
          class="limit-slider"
          @input="emitUpdate"
        />
        <span class="limit-value">{{ localWidget.autoplayInterval === 0 ? t('intravox', 'Off') : localWidget.autoplayInterval + 's' }}</span>
      </div>
      <p class="editor-hint">{{ t('intravox', 'Set to 0 to disable autoplay') }}</p>
    </div>

    <!-- Number of items -->
    <div class="editor-section">
      <label class="editor-label" for="news-widget-limit">{{ t('intravox', 'Number of items') }}</label>
      <div class="limit-selector">
        <input
          id="news-widget-limit"
          type="range"
          v-model.number="localWidget.limit"
          min="1"
          max="20"
          class="limit-slider"
          @input="emitUpdate"
        />
        <span class="limit-value">{{ localWidget.limit }}</span>
      </div>
    </div>

    <!-- Sort Options -->
    <div class="editor-section">
      <label class="editor-label" for="news-widget-sort-by">{{ t('intravox', 'Sort by') }}</label>
      <div class="sort-options">
        <select id="news-widget-sort-by" v-model="localWidget.sortBy" class="editor-select" @change="emitUpdate">
          <option value="modified">{{ t('intravox', 'Date modified') }}</option>
          <option value="title">{{ t('intravox', 'Title') }}</option>
        </select>
        <button
          class="sort-order-button"
          @click="toggleSortOrder"
          :title="localWidget.sortOrder === 'desc' ? t('intravox', 'Newest first') : t('intravox', 'Oldest first')"
        >
          <SortDescending v-if="localWidget.sortOrder === 'desc'" :size="20" />
          <SortAscending v-else :size="20" />
        </button>
      </div>
    </div>

    <!-- Display Options -->
    <div class="editor-section">
      <label class="editor-label">{{ t('intravox', 'Display options') }}</label>
      <div class="display-options">
        <label class="checkbox-option">
          <input type="checkbox" v-model="localWidget.showImage" @change="emitUpdate" />
          <span>{{ t('intravox', 'Show image') }}</span>
        </label>
        <label class="checkbox-option">
          <input type="checkbox" v-model="localWidget.showDate" @change="emitUpdate" />
          <span>{{ t('intravox', 'Show date') }}</span>
        </label>
        <label class="checkbox-option">
          <input type="checkbox" v-model="localWidget.showExcerpt" @change="emitUpdate" />
          <span>{{ t('intravox', 'Show excerpt') }}</span>
        </label>
      </div>
    </div>

    <!-- Publication Filter -->
    <div class="editor-section">
      <label class="checkbox-option publication-filter-option">
        <input type="checkbox" v-model="localWidget.filterPublished" @change="emitUpdate" />
        <span>{{ t('intravox', 'Show only published pages') }}</span>
      </label>
      <p class="editor-hint">{{ t('intravox', 'Filter pages based on publication and expiration dates configured in admin settings.') }}</p>

      <div v-if="localWidget.filterPublished && !metavoxAvailable" class="publication-warning">
        <AlertCircle :size="16" />
        <span>{{ t('intravox', 'MetaVox is required for publication filtering but is not available.') }}</span>
      </div>

      <div v-else-if="localWidget.filterPublished && !publicationFieldsConfigured" class="publication-warning">
        <AlertCircle :size="16" />
        <span>{{ t('intravox', 'Publication date fields have not been configured in admin settings.') }}</span>
      </div>
    </div>

    <!-- MetaVox Filters -->
    <div class="editor-section" v-if="metavoxAvailable">
      <label class="editor-label">
        {{ t('intravox', 'MetaVox filters') }}
        <span class="label-hint">({{ t('intravox', 'optional') }})</span>
      </label>

      <div v-if="filters.length > 0" class="filters-list">
        <div v-for="(filter, index) in filters" :key="index" class="filter-row">
          <NcSelect
            :model-value="fieldOptionFor(filter.fieldName)"
            :options="fieldSelectOptions"
            :clearable="false"
            :aria-label="t('intravox', 'Filter field')"
            :placeholder="t('intravox', 'Select field')"
            label="label"
            class="filter-field"
            @update:model-value="setFilterField(filter, $event)" />
          <NcSelect
            :model-value="operatorOptionFor(filter)"
            :options="getOperatorsForField(filter.fieldName)"
            :clearable="false"
            :aria-label="t('intravox', 'Filter operator')"
            :placeholder="t('intravox', 'Operator')"
            label="label"
            class="filter-operator"
            @update:model-value="setRowOperator(filter, $event)" />

          <!-- Value input - conditional based on field type and operator -->
          <template v-if="!requiresNoValue(filter.operator)">
            <!-- Date field -->
            <input
              v-if="getFieldType(filter.fieldName) === 'date'"
              type="date"
              v-model="filter.value"
              class="filter-value"
              :aria-label="t('intravox', 'Filter value')"
              @input="emitUpdate"
            />

            <!-- Number field -->
            <input
              v-else-if="getFieldType(filter.fieldName) === 'number'"
              type="number"
              v-model="filter.value"
              class="filter-value"
              :placeholder="t('intravox', 'Value')"
              :aria-label="t('intravox', 'Filter value')"
              @input="emitUpdate"
            />

            <!-- Select field with equals operator -->
            <NcSelect
              v-else-if="getFieldType(filter.fieldName) === 'select' && filter.operator === 'equals'"
              :model-value="filter.value || null"
              :options="getFieldOptions(filter.fieldName)"
              :aria-label="t('intravox', 'Filter value')"
              :placeholder="t('intravox', 'Select value')"
              class="filter-value"
              @update:model-value="setFilterValue(filter, $event)" />

            <!-- Several options at once: a multiselect field, or a select
                 field asked for with "is one of". One control for both; a
                 native <select multiple> needed ctrl/cmd-click to pick more
                 than one, with nothing in the UI saying so (#111).
                 keep-open, not close-on-select: NcSelect derives the latter
                 from the former, so setting close-on-select directly does
                 nothing and the list shuts after every pick. -->
            <NcSelect
              v-else-if="isMultiValueFilter(filter)"
              :model-value="filter.values || []"
              :options="getFieldOptions(filter.fieldName)"
              :multiple="true"
              :keep-open="true"
              :aria-label="t('intravox', 'Filter values')"
              :placeholder="t('intravox', 'Filter values')"
              class="filter-value filter-value--multi"
              @update:model-value="setFilterValues(filter, $event)" />

            <!-- Text/Textarea field (default) -->
            <input
              v-else
              type="text"
              v-model="filter.value"
              class="filter-value"
              :placeholder="t('intravox', 'Value')"
              :aria-label="t('intravox', 'Filter value')"
              @input="emitUpdate"
            />
          </template>

          <button class="filter-remove" @click="removeFilter(index)">
            <Close :size="16" />
          </button>
        </div>

        <div v-if="filters.length > 1" class="filter-operator-toggle">
          <span>{{ t('intravox', 'Match') }}</span>
          <button
            class="operator-button"
            :class="{ 'operator-button--active': localWidget.filterOperator === 'AND' }"
            @click="setFilterOperator('AND')"
          >
            {{ t('intravox', 'all') }}
          </button>
          <button
            class="operator-button"
            :class="{ 'operator-button--active': localWidget.filterOperator === 'OR' }"
            @click="setFilterOperator('OR')"
          >
            {{ t('intravox', 'any') }}
          </button>
          <span>{{ t('intravox', 'filters') }}</span>
        </div>
      </div>

      <button class="add-filter-button" @click="addFilter">
        <Plus :size="16" />
        {{ t('intravox', 'Add filter') }}
      </button>
    </div>

    <div v-else class="editor-section metavox-notice">
      <Information :size="20" />
      <span>{{ t('intravox', 'Install MetaVox for advanced filtering options') }}</span>
    </div>
  </div>
</template>

<script>
import { translate } from '@nextcloud/l10n';
import { generateUrl } from '@nextcloud/router';
import axios from '@nextcloud/axios';
import ViewList from 'vue-material-design-icons/ViewList.vue';
import ViewGrid from 'vue-material-design-icons/ViewGrid.vue';
import ViewCarousel from 'vue-material-design-icons/ViewCarousel.vue';
import SortAscending from 'vue-material-design-icons/SortAscending.vue';
import SortDescending from 'vue-material-design-icons/SortDescending.vue';
import Plus from 'vue-material-design-icons/Plus.vue';
import Close from 'vue-material-design-icons/Close.vue';
import Information from 'vue-material-design-icons/Information.vue';
import AlertCircle from 'vue-material-design-icons/AlertCircle.vue';
import PageTreeSelect from './PageTreeSelect.vue';
import { NcSelect } from '@nextcloud/vue';

export default {
  name: 'NewsWidgetEditor',
  components: {
    ViewList,
    ViewGrid,
    ViewCarousel,
    SortAscending,
    SortDescending,
    Plus,
    Close,
    Information,
    AlertCircle,
    PageTreeSelect,
    NcSelect,
  },
  props: {
    widget: {
      type: Object,
      required: true,
    },
  },
  emits: ['update'],
  data() {
    return {
      localWidget: this.createDefaultWidget(),
      metavoxAvailable: false,
      metavoxFields: [],
      filters: [],
      publicationFieldsConfigured: false,
    };
  },
  computed: {
    /**
     * The MetaVox fields as NcSelect options.
     *
     * The stored filter keeps the bare field_name; only the control needs the
     * {value, label} shape.
     */
    fieldSelectOptions() {
      return (this.metavoxFields || []).map(field => ({
        value: field.field_name,
        label: field.field_label || field.field_name,
      }));
    },
    // Operator labels translated here (via literal t('intravox', …) calls so the
    // l10n extractor finds them). The template previously used t(op.label) with
    // a single argument, which @nextcloud/l10n read as the app id → undefined →
    // blank operator options (same bug class as #79).
    operatorsByFieldType() {
      return {
        text: [
          { value: 'equals', label: this.t('intravox', 'equals') },
          { value: 'contains', label: this.t('intravox', 'contains') },
          { value: 'not_contains', label: this.t('intravox', 'does not contain') },
          { value: 'not_empty', label: this.t('intravox', 'is not empty') },
          { value: 'empty', label: this.t('intravox', 'is empty') },
        ],
        textarea: [
          { value: 'equals', label: this.t('intravox', 'equals') },
          { value: 'contains', label: this.t('intravox', 'contains') },
          { value: 'not_contains', label: this.t('intravox', 'does not contain') },
          { value: 'not_empty', label: this.t('intravox', 'is not empty') },
          { value: 'empty', label: this.t('intravox', 'is empty') },
        ],
        date: [
          { value: 'equals', label: this.t('intravox', 'equals') },
          { value: 'before', label: this.t('intravox', 'is before') },
          { value: 'after', label: this.t('intravox', 'is after') },
          { value: 'not_empty', label: this.t('intravox', 'is not empty') },
          { value: 'empty', label: this.t('intravox', 'is empty') },
        ],
        number: [
          { value: 'equals', label: this.t('intravox', 'equals') },
          { value: 'greater_than', label: this.t('intravox', 'is greater than') },
          { value: 'less_than', label: this.t('intravox', 'is less than') },
          { value: 'greater_or_equal', label: this.t('intravox', 'is greater or equal') },
          { value: 'less_or_equal', label: this.t('intravox', 'is less or equal') },
          { value: 'not_empty', label: this.t('intravox', 'is not empty') },
          { value: 'empty', label: this.t('intravox', 'is empty') },
        ],
        select: [
          { value: 'equals', label: this.t('intravox', 'equals') },
          { value: 'in', label: this.t('intravox', 'is one of') },
          { value: 'not_empty', label: this.t('intravox', 'is not empty') },
          { value: 'empty', label: this.t('intravox', 'is empty') },
        ],
        multiselect: [
          { value: 'contains', label: this.t('intravox', 'contains') },
          { value: 'contains_all', label: this.t('intravox', 'contains all') },
          { value: 'not_empty', label: this.t('intravox', 'is not empty') },
          { value: 'empty', label: this.t('intravox', 'is empty') },
        ],
        checkbox: [
          { value: 'is_true', label: this.t('intravox', 'is true') },
          { value: 'is_false', label: this.t('intravox', 'is false') },
          { value: 'not_empty', label: this.t('intravox', 'is not empty') },
        ],
      };
    },
    layoutOptions() {
      return [
        { value: 'list', label: this.t('intravox', 'List'), icon: 'ViewList' },
        { value: 'grid', label: this.t('intravox', 'Grid'), icon: 'ViewGrid' },
        { value: 'carousel', label: this.t('intravox', 'Carousel'), icon: 'ViewCarousel' },
      ];
    },
  },
  watch: {
    widget: {
      immediate: true,
      deep: true,
      handler(newWidget) {
        this.localWidget = {
          ...this.createDefaultWidget(),
          ...newWidget,
        };
        this.filters = [...(newWidget.filters || [])];
      },
    },
  },
  mounted() {
    this.checkMetaVox();
  },
  methods: {
    t(app, text, vars) {
      return translate(app, text, vars);
    },
    createDefaultWidget() {
      return {
        type: 'news',
        title: '',
        backgroundColor: null,
        sourcePageId: null,
        sourcePath: '',
        layout: 'list',
        columns: 3,
        limit: 5,
        sortBy: 'modified',
        sortOrder: 'desc',
        showImage: true,
        showDate: true,
        showExcerpt: true,
        excerptLength: 100,
        autoplayInterval: 5,
        filters: [],
        filterOperator: 'AND',
        filterPublished: false,
      };
    },
    handleSourceSelect(page) {
      if (page) {
        this.localWidget.sourcePageId = page.uniqueId;
      } else {
        this.localWidget.sourcePageId = null;
      }
      this.emitUpdate();
    },
    async checkMetaVox() {
      try {
        const response = await axios.get(generateUrl('/apps/intravox/api/metavox/status'));
        this.metavoxAvailable = response.data.installed && response.data.enabled;

        if (this.metavoxAvailable) {
          await this.loadMetaVoxFields();
        }

        // Always check publication config (it shows a warning if not configured)
        await this.checkPublicationConfig();
      } catch (error) {
        this.metavoxAvailable = false;
      }
    },
    async checkPublicationConfig() {
      try {
        const response = await axios.get(generateUrl('/apps/intravox/api/settings/publication'));
        const settings = response.data;
        this.publicationFieldsConfigured = !!(settings.publishDateField || settings.expirationDateField);
      } catch (error) {
        this.publicationFieldsConfigured = false;
      }
    },
    async loadMetaVoxFields() {
      try {
        // Get fields from IntraVox API which fetches MetaVox fields for the IntraVox groupfolder
        const response = await axios.get(generateUrl('/apps/intravox/api/metavox/fields'));
        this.metavoxFields = response.data.fields || [];
      } catch (error) {
        // MetaVox API might not be available
        this.metavoxFields = [];
      }
    },
    setLayout(layout) {
      this.localWidget.layout = layout;
      this.emitUpdate();
    },
    setColumns(cols) {
      this.localWidget.columns = cols;
      this.emitUpdate();
    },
    toggleSortOrder() {
      this.localWidget.sortOrder = this.localWidget.sortOrder === 'desc' ? 'asc' : 'desc';
      this.emitUpdate();
    },
    addFilter() {
      this.filters.push({
        fieldName: '',
        operator: 'equals',
        value: '',
        values: [], // For 'in' and multiselect operators
      });
      // Don't call syncFilters() here - it would remove the empty filter immediately
      // The filter will be synced when user selects a field
    },
    getFieldType(fieldName) {
      const field = this.metavoxFields.find(f => f.field_name === fieldName);
      return field?.field_type || 'text';
    },
    getOperatorsForField(fieldName) {
      const fieldType = this.getFieldType(fieldName);
      return this.operatorsByFieldType[fieldType] || this.operatorsByFieldType.text;
    },
    getFieldOptions(fieldName) {
      const field = this.metavoxFields.find(f => f.field_name === fieldName);
      if (!field?.options) {
        return [];
      }

      let options = field.options;

      // Handle string options (newline-separated or JSON array)
      if (typeof options === 'string') {
        // Try to parse as JSON array first
        if (options.startsWith('[')) {
          try {
            options = JSON.parse(options);
          } catch (e) {
            // Not valid JSON, treat as newline-separated
            options = options.split('\n').filter(o => o.trim());
          }
        } else {
          // Newline-separated options
          options = options.split('\n').filter(o => o.trim());
        }
      }

      // Ensure we have an array
      if (!Array.isArray(options)) {
        return [];
      }

      return options.filter(o => o && (typeof o === 'string' ? o.trim() : true));
    },
    handleFieldChange(filter) {
      const fieldType = this.getFieldType(filter.fieldName);
      const validOperators = this.operatorsByFieldType[fieldType] || this.operatorsByFieldType.text;

      // Reset to first valid operator if current is not valid
      if (!validOperators.find(op => op.value === filter.operator)) {
        filter.operator = validOperators[0].value;
      }

      // Clear values when switching fields
      filter.value = '';
      filter.values = [];

      this.syncFilters();
    },
    handleOperatorChange(filter) {
      // Clear values array when not using 'in' or multiselect operators
      if (!['in', 'contains', 'contains_all'].includes(filter.operator)) {
        filter.values = [];
      }
      this.emitUpdate();
    },
    /**
     * Whether this row lets the editor pick several options at once: a
     * multiselect field, or a select field asked for with "is one of".
     */
    isMultiValueFilter(filter) {
      const type = this.getFieldType(filter.fieldName);
      return type === 'multiselect' || (type === 'select' && filter.operator === 'in');
    },
    /**
     * NcSelect works with option objects, the stored filter with plain field
     * names. These two map between them; the saved shape is unchanged.
     */
    fieldOptionFor(fieldName) {
      return this.fieldSelectOptions.find(o => o.value === fieldName) || null;
    },
    operatorOptionFor(filter) {
      const options = this.getOperatorsForField(filter.fieldName);
      return options.find(o => o.value === filter.operator) || null;
    },
    setFilterField(filter, option) {
      filter.fieldName = option?.value ?? '';
      this.handleFieldChange(filter);
    },
    setRowOperator(filter, option) {
      if (!option?.value) {
        return;
      }
      filter.operator = option.value;
      this.handleOperatorChange(filter);
    },
    setFilterValue(filter, value) {
      // Options are plain strings here, but NcSelect hands back whatever it
      // was given, so unwrap an object shape defensively.
      filter.value = (value && typeof value === 'object') ? (value.value ?? '') : (value ?? '');
      this.emitUpdate();
    },
    setFilterValues(filter, values) {
      const list = Array.isArray(values) ? values : (values ? [values] : []);
      filter.values = list.map(v => (v && typeof v === 'object') ? v.value : v).filter(v => v != null && v !== '');
      this.emitUpdate();
    },
    requiresNoValue(operator) {
      return ['not_empty', 'empty', 'is_true', 'is_false'].includes(operator);
    },
    removeFilter(index) {
      this.filters.splice(index, 1);
      this.syncFilters();
    },
    setFilterOperator(operator) {
      this.localWidget.filterOperator = operator;
      this.emitUpdate();
    },
    syncFilters() {
      this.localWidget.filters = this.filters.filter(f => f.fieldName);
      this.emitUpdate();
    },
    emitUpdate() {
      this.$emit('update', { ...this.localWidget });
    },
    setBackgroundColor(color) {
      this.localWidget.backgroundColor = color;
      this.emitUpdate();
    },
  },
};
</script>

<style scoped>
.news-widget-editor {
  display: flex;
  flex-direction: column;
  gap: 20px;
  padding: 16px;
}

.editor-section {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.editor-label {
  font-weight: 600;
  font-size: 14px;
  color: var(--color-main-text);
}

.label-hint {
  font-weight: 400;
  color: var(--color-text-maxcontrast);
}

.editor-hint {
  margin: 0;
  font-size: 12px;
  color: var(--color-text-maxcontrast);
}

.editor-input,
.editor-select {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-main-background);
  font-size: 14px;
}

.editor-input:focus,
.editor-select:focus {
  border-color: var(--color-primary);
  outline: none;
}

.color-presets {
  display: flex;
  gap: 8px;
}

.color-preset-btn {
  flex: 1;
  padding: 8px 12px;
  border: 2px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-main-background);
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s;
}

.color-preset-btn:hover {
  border-color: var(--color-primary-element-light);
}

.color-preset-btn.active {
  border-color: var(--color-primary);
  background: var(--color-primary-element-light);
}

.layout-options {
  display: flex;
  gap: 8px;
}

.layout-option {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  padding: 12px 8px;
  border: 2px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-main-background);
  cursor: pointer;
  transition: all 0.2s;
}

.layout-option:hover {
  border-color: var(--color-primary-element-light);
}

.layout-option--active {
  border-color: var(--color-primary);
  background: var(--color-primary-element-light);
}

.layout-option span {
  font-size: 12px;
  font-weight: 500;
}

.columns-selector {
  display: flex;
  gap: 8px;
}

.column-option {
  width: 40px;
  height: 36px;
  border: 2px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-main-background);
  font-weight: 600;
  cursor: pointer;
}

.column-option:hover {
  border-color: var(--color-primary-element-light);
}

.column-option--active {
  border-color: var(--color-primary);
  background: var(--color-primary-element-light);
}

.limit-selector {
  display: flex;
  align-items: center;
  gap: 12px;
}

.limit-slider {
  flex: 1;
}

.limit-value {
  min-width: 24px;
  font-weight: 600;
  text-align: center;
}

.sort-options {
  display: flex;
  gap: 8px;
}

.sort-options .editor-select {
  flex: 1;
}

.sort-order-button {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  padding: 0;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-main-background);
  cursor: pointer;
}

.sort-order-button:hover {
  background: var(--color-background-hover);
}

.display-options {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.checkbox-option {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}

.checkbox-option input {
  width: 16px;
  height: 16px;
}

.filters-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.filter-row {
  display: flex;
  gap: 8px;
  align-items: center;
}

/* Widths only. The three filter controls are NcSelect now, which draws its own
   border, padding and focus ring; repeating them here put a second box around
   the component. The <input> variants below still need the native styling. */
.filter-field {
  flex: 2;
  min-width: 0;
}

.filter-operator {
  flex: 1;
  min-width: 0;
}

.filter-value {
  flex: 2;
  min-width: 0;
}

input.filter-value {
  padding: 6px 8px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  font-size: 13px;
}

/* A row can wrap to two lines once several chips are selected, so the controls
   line up at the top rather than drifting apart vertically. */
.filter-row {
  align-items: flex-start;
}

.filter-remove {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  padding: 0;
  border: none;
  border-radius: var(--border-radius);
  background: transparent;
  color: var(--color-text-maxcontrast);
  cursor: pointer;
}

.filter-remove:hover {
  background: var(--color-error-hover);
  color: var(--color-error);
}

.filter-operator-toggle {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: var(--color-text-maxcontrast);
}

.operator-button {
  padding: 4px 12px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-main-background);
  font-size: 12px;
  cursor: pointer;
}

.operator-button--active {
  background: var(--color-primary);
  border-color: var(--color-primary);
  color: var(--color-primary-text);
}

.add-filter-button {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  border: 1px dashed var(--color-border);
  border-radius: var(--border-radius);
  background: transparent;
  color: var(--color-text-maxcontrast);
  font-size: 13px;
  cursor: pointer;
}

.add-filter-button:hover {
  border-color: var(--color-primary);
  color: var(--color-primary);
}

.metavox-notice {
  flex-direction: row;
  align-items: center;
  gap: 8px;
  padding: 12px;
  background: var(--color-background-hover);
  border-radius: var(--border-radius);
  color: var(--color-text-maxcontrast);
  font-size: 13px;
}

/* Publication Filter */
.publication-filter-option {
  margin-bottom: 4px;
}

.publication-warning {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 8px;
  padding: 10px 12px;
  background: var(--color-warning-light, #fff3cd);
  border: 1px solid var(--color-warning, #ffc107);
  border-radius: var(--border-radius);
  color: var(--color-warning-text, #856404);
  font-size: 13px;
}

.publication-warning .material-design-icon {
  flex-shrink: 0;
}
</style>
