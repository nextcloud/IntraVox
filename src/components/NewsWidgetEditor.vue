<template>
  <div class="news-widget-editor">
    <!-- Widget Title -->
    <div class="editor-section">
      <label class="editor-label">{{ t('Widget title (optional)') }}</label>
      <input
        type="text"
        v-model="localWidget.title"
        :placeholder="t('e.g., Latest News')"
        class="editor-input"
        @input="emitUpdate"
      />
    </div>

    <!-- Source Location -->
    <div class="editor-section">
      <label class="editor-label">{{ t('Source location') }}</label>
      <PageTreeSelect
        v-model="localWidget.sourcePageId"
        :placeholder="t('All pages')"
        :clearable="true"
        @select="handleSourceSelect"
      />
      <p class="editor-hint">{{ t('Select a page or folder to show content from, including all subpages') }}</p>
    </div>

    <!-- Layout Selection -->
    <div class="editor-section">
      <label class="editor-label">{{ t('Layout') }}</label>
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
      <label class="editor-label">{{ t('Columns') }}</label>
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
      <label class="editor-label">{{ t('Autoplay interval (seconds)') }}</label>
      <div class="limit-selector">
        <input
          type="range"
          v-model.number="localWidget.autoplayInterval"
          min="0"
          max="30"
          class="limit-slider"
          @input="emitUpdate"
        />
        <span class="limit-value">{{ localWidget.autoplayInterval === 0 ? t('Off') : localWidget.autoplayInterval + 's' }}</span>
      </div>
      <p class="editor-hint">{{ t('Set to 0 to disable autoplay') }}</p>
    </div>

    <!-- Number of items -->
    <div class="editor-section">
      <label class="editor-label">{{ t('Number of items') }}</label>
      <div class="limit-selector">
        <input
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
      <label class="editor-label">{{ t('Sort by') }}</label>
      <div class="sort-options">
        <select v-model="localWidget.sortBy" class="editor-select" @change="emitUpdate">
          <option value="modified">{{ t('Date modified') }}</option>
          <option value="title">{{ t('Title') }}</option>
        </select>
        <button
          class="sort-order-button"
          @click="toggleSortOrder"
          :title="localWidget.sortOrder === 'desc' ? t('Newest first') : t('Oldest first')"
        >
          <SortDescending v-if="localWidget.sortOrder === 'desc'" :size="20" />
          <SortAscending v-else :size="20" />
        </button>
      </div>
    </div>

    <!-- Display Options -->
    <div class="editor-section">
      <label class="editor-label">{{ t('Display options') }}</label>
      <div class="display-options">
        <label class="checkbox-option">
          <input type="checkbox" v-model="localWidget.showImage" @change="emitUpdate" />
          <span>{{ t('Show image') }}</span>
        </label>
        <label class="checkbox-option">
          <input type="checkbox" v-model="localWidget.showDate" @change="emitUpdate" />
          <span>{{ t('Show date') }}</span>
        </label>
        <label class="checkbox-option">
          <input type="checkbox" v-model="localWidget.showExcerpt" @change="emitUpdate" />
          <span>{{ t('Show excerpt') }}</span>
        </label>
      </div>
    </div>

    <!-- MetaVox Filters -->
    <div class="editor-section" v-if="metavoxAvailable">
      <label class="editor-label">
        {{ t('MetaVox filters') }}
        <span class="label-hint">({{ t('optional') }})</span>
      </label>

      <div v-if="filters.length > 0" class="filters-list">
        <div v-for="(filter, index) in filters" :key="index" class="filter-row">
          <select v-model="filter.fieldName" class="filter-field" @change="syncFilters">
            <option value="">{{ t('Select field') }}</option>
            <option v-for="field in metavoxFields" :key="field.field_name" :value="field.field_name">
              {{ field.field_label || field.field_name }}
            </option>
          </select>
          <select v-model="filter.operator" class="filter-operator" @change="emitUpdate">
            <option value="equals">{{ t('equals') }}</option>
            <option value="contains">{{ t('contains') }}</option>
            <option value="not_empty">{{ t('is not empty') }}</option>
          </select>
          <input
            v-if="filter.operator !== 'not_empty'"
            type="text"
            v-model="filter.value"
            class="filter-value"
            :placeholder="t('Value')"
            @input="emitUpdate"
          />
          <button class="filter-remove" @click="removeFilter(index)">
            <Close :size="16" />
          </button>
        </div>

        <div v-if="filters.length > 1" class="filter-operator-toggle">
          <span>{{ t('Match') }}</span>
          <button
            class="operator-button"
            :class="{ 'operator-button--active': localWidget.filterOperator === 'AND' }"
            @click="setFilterOperator('AND')"
          >
            {{ t('all') }}
          </button>
          <button
            class="operator-button"
            :class="{ 'operator-button--active': localWidget.filterOperator === 'OR' }"
            @click="setFilterOperator('OR')"
          >
            {{ t('any') }}
          </button>
          <span>{{ t('filters') }}</span>
        </div>
      </div>

      <button class="add-filter-button" @click="addFilter">
        <Plus :size="16" />
        {{ t('Add filter') }}
      </button>
    </div>

    <div v-else class="editor-section metavox-notice">
      <Information :size="20" />
      <span>{{ t('Install MetaVox for advanced filtering options') }}</span>
    </div>
  </div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n';
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
import PageTreeSelect from './PageTreeSelect.vue';

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
    PageTreeSelect,
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
    };
  },
  computed: {
    layoutOptions() {
      return [
        { value: 'list', label: this.t('List'), icon: 'ViewList' },
        { value: 'grid', label: this.t('Grid'), icon: 'ViewGrid' },
        { value: 'carousel', label: this.t('Carousel'), icon: 'ViewCarousel' },
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
    t(key, vars = {}) {
      return t('intravox', key, vars);
    },
    createDefaultWidget() {
      return {
        type: 'news',
        title: '',
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
      } catch (error) {
        this.metavoxAvailable = false;
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
      });
      // Don't call syncFilters() here - it would remove the empty filter immediately
      // The filter will be synced when user selects a field
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

.filter-field {
  flex: 2;
  padding: 6px 8px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  font-size: 13px;
}

.filter-operator {
  flex: 1;
  padding: 6px 8px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  font-size: 13px;
}

.filter-value {
  flex: 2;
  padding: 6px 8px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  font-size: 13px;
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
</style>
