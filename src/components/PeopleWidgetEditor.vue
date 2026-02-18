<template>
  <div class="people-widget-editor">
    <!-- Widget Title -->
    <div class="editor-section">
      <label class="editor-label">{{ t('Widget title (optional)') }}</label>
      <input
        type="text"
        v-model="localWidget.title"
        :placeholder="t('e.g., Our Team')"
        class="editor-input"
        @input="emitUpdate"
      />
    </div>

    <!-- Background Color -->
    <div class="editor-section">
      <label class="editor-label">{{ t('Background color') }}</label>
      <div class="color-presets">
        <button
          type="button"
          :class="{ active: !localWidget.backgroundColor }"
          @click="setBackgroundColor(null)"
          class="color-preset-btn"
        >
          {{ t('None') }}
        </button>
        <button
          type="button"
          :class="{ active: localWidget.backgroundColor === 'var(--color-background-hover)' }"
          @click="setBackgroundColor('var(--color-background-hover)')"
          class="color-preset-btn"
        >
          {{ t('Light') }}
        </button>
        <button
          type="button"
          :class="{ active: localWidget.backgroundColor === 'var(--color-primary-element)' }"
          @click="setBackgroundColor('var(--color-primary-element)')"
          class="color-preset-btn"
        >
          {{ t('Primary') }}
        </button>
      </div>
    </div>

    <!-- Selection Mode -->
    <div class="editor-section">
      <label class="editor-label">{{ t('Selection mode') }}</label>
      <div class="mode-selector">
        <button
          type="button"
          class="mode-btn"
          :class="{ active: localWidget.selectionMode === 'manual' }"
          @click="setSelectionMode('manual')"
        >
          <AccountMultiplePlus :size="20" />
          <span>{{ t('Manual selection') }}</span>
        </button>
        <button
          type="button"
          class="mode-btn"
          :class="{ active: localWidget.selectionMode === 'filter' }"
          @click="setSelectionMode('filter')"
        >
          <Filter :size="20" />
          <span>{{ t('Filter by attributes') }}</span>
        </button>
      </div>
    </div>

    <!-- Manual Selection Mode -->
    <div v-if="localWidget.selectionMode === 'manual'" class="editor-section">
      <label class="editor-label">{{ t('Select people') }}</label>
      <UserSelect
        v-model="localWidget.selectedUsers"
        @update:model-value="handleUsersChange"
      />
    </div>

    <!-- Filter Mode -->
    <div v-if="localWidget.selectionMode === 'filter'" class="editor-section">
      <label class="editor-label">
        {{ t('Filters') }}
        <span class="label-hint">({{ t('show users matching these criteria') }})</span>
      </label>

      <div v-if="filters.length > 0" class="filters-list">
        <div v-for="(filter, index) in filters" :key="index" class="filter-row">
          <select v-model="filter.fieldName" class="filter-field" @change="handleFieldChange(filter)">
            <option value="">{{ t('Select field') }}</option>
            <option v-for="field in availableFields" :key="field.fieldName" :value="field.fieldName">
              {{ field.label }}
            </option>
          </select>

          <select v-model="filter.operator" class="filter-operator" @change="emitUpdate">
            <option v-for="op in getOperatorsForField(filter.fieldName)" :key="op.value" :value="op.value">
              {{ t(op.label) }}
            </option>
          </select>

          <!-- Value input - conditional based on field type -->
          <template v-if="!requiresNoValue(filter.operator)">
            <!-- Group field (select from groups) -->
            <select
              v-if="filter.fieldName === 'group' && filter.operator !== 'in'"
              v-model="filter.value"
              class="filter-value"
              @change="emitUpdate"
            >
              <option value="">{{ t('Select group') }}</option>
              <option v-for="group in groups" :key="group.id" :value="group.id">
                {{ group.displayName }}
              </option>
            </select>

            <!-- Group field with 'in' operator (multiple) -->
            <select
              v-else-if="filter.fieldName === 'group' && filter.operator === 'in'"
              v-model="filter.values"
              class="filter-value filter-value--multi"
              multiple
              @change="emitUpdate"
            >
              <option v-for="group in groups" :key="group.id" :value="group.id">
                {{ group.displayName }}
              </option>
            </select>

            <!-- Date field with within_next_days operator (number input) -->
            <input
              v-else-if="filter.operator === 'within_next_days'"
              type="number"
              v-model="filter.value"
              class="filter-value filter-value--days"
              :placeholder="t('Days')"
              min="1"
              max="365"
              @input="emitUpdate"
            />

            <!-- Text field (default) -->
            <input
              v-else
              type="text"
              v-model="filter.value"
              class="filter-value"
              :placeholder="t('Value')"
              @input="emitUpdate"
            />
          </template>

          <button type="button" class="filter-remove" @click="removeFilter(index)">
            <Close :size="16" />
          </button>
        </div>

        <div v-if="filters.length > 1" class="filter-operator-toggle">
          <span>{{ t('Match') }}</span>
          <button
            type="button"
            class="operator-button"
            :class="{ 'operator-button--active': localWidget.filterOperator === 'AND' }"
            @click="setFilterOperator('AND')"
          >
            {{ t('all') }}
          </button>
          <button
            type="button"
            class="operator-button"
            :class="{ 'operator-button--active': localWidget.filterOperator === 'OR' }"
            @click="setFilterOperator('OR')"
          >
            {{ t('any') }}
          </button>
          <span>{{ t('filters') }}</span>
        </div>
      </div>

      <button type="button" class="add-filter-button" @click="addFilter">
        <Plus :size="16" />
        {{ t('Add filter') }}
      </button>
    </div>

    <!-- Layout Selection -->
    <div class="editor-section">
      <label class="editor-label">{{ t('Layout') }}</label>
      <div class="layout-options">
        <button
          v-for="layout in layoutOptions"
          :key="layout.value"
          type="button"
          class="layout-option"
          :class="{ 'layout-option--active': localWidget.layout === layout.value }"
          @click="setLayout(layout.value)"
        >
          <component :is="layout.icon" :size="24" />
          <span>{{ layout.label }}</span>
        </button>
      </div>
    </div>

    <!-- Grid/Card Columns -->
    <div v-if="localWidget.layout === 'grid' || localWidget.layout === 'card'" class="editor-section">
      <label class="editor-label">{{ t('Columns') }}</label>
      <div class="columns-selector">
        <button
          v-for="cols in columnOptions"
          :key="cols"
          type="button"
          class="column-option"
          :class="{ 'column-option--active': localWidget.columns === cols }"
          @click="setColumns(cols)"
        >
          {{ cols }}
        </button>
      </div>
    </div>

    <!-- Number of people -->
    <div class="editor-section">
      <label class="editor-label">{{ t('Maximum people to show') }}</label>
      <div class="limit-selector">
        <input
          type="range"
          v-model.number="localWidget.limit"
          min="1"
          max="50"
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
          <option value="displayName">{{ t('Name') }}</option>
          <option value="email">{{ t('Email') }}</option>
        </select>
        <button
          type="button"
          class="sort-order-button"
          @click="toggleSortOrder"
          :title="localWidget.sortOrder === 'asc' ? t('Ascending') : t('Descending')"
        >
          <SortAscending v-if="localWidget.sortOrder === 'asc'" :size="20" />
          <SortDescending v-else :size="20" />
        </button>
      </div>
    </div>

    <!-- Display Options -->
    <div class="editor-section">
      <label class="editor-label">{{ t('Display options') }}</label>
      <div class="display-options">
        <!-- Basic Information -->
        <div class="display-group">
          <span class="display-group-header">{{ t('Basic information') }}</span>
          <label class="checkbox-option">
            <input type="checkbox" v-model="localWidget.showFields.avatar" @change="emitUpdate" />
            <span>{{ t('Avatar') }}</span>
          </label>
          <label class="checkbox-option">
            <input type="checkbox" v-model="localWidget.showFields.displayName" @change="emitUpdate" />
            <span>{{ t('Name') }}</span>
          </label>
          <label class="checkbox-option">
            <input type="checkbox" v-model="localWidget.showFields.pronouns" @change="emitUpdate" />
            <span>{{ t('Pronouns') }}</span>
          </label>
          <label class="checkbox-option">
            <input type="checkbox" v-model="localWidget.showFields.role" @change="syncTitleWithRole" />
            <span>{{ t('Role') }}</span>
          </label>
          <label class="checkbox-option">
            <input type="checkbox" v-model="localWidget.showFields.headline" @change="emitUpdate" />
            <span>{{ t('Headline') }}</span>
          </label>
          <label class="checkbox-option">
            <input type="checkbox" v-model="localWidget.showFields.department" @change="emitUpdate" />
            <span>{{ t('Department') }}</span>
          </label>
        </div>

        <!-- Contact -->
        <div class="display-group">
          <span class="display-group-header">{{ t('Contact') }}</span>
          <label class="checkbox-option">
            <input type="checkbox" v-model="localWidget.showFields.email" @change="emitUpdate" />
            <span>{{ t('Email') }}</span>
          </label>
          <label class="checkbox-option">
            <input type="checkbox" v-model="localWidget.showFields.phone" @change="emitUpdate" />
            <span>{{ t('Phone') }}</span>
          </label>
          <label class="checkbox-option">
            <input type="checkbox" v-model="localWidget.showFields.address" @change="emitUpdate" />
            <span>{{ t('Address') }}</span>
          </label>
          <label class="checkbox-option">
            <input type="checkbox" v-model="localWidget.showFields.website" @change="emitUpdate" />
            <span>{{ t('Website') }}</span>
          </label>
          <label class="checkbox-option">
            <input type="checkbox" v-model="localWidget.showFields.birthdate" @change="emitUpdate" />
            <span>{{ t('Date of birth') }}</span>
          </label>
        </div>

        <!-- Extended -->
        <div class="display-group">
          <span class="display-group-header">{{ t('Extended') }}</span>
          <label class="checkbox-option">
            <input type="checkbox" v-model="localWidget.showFields.biography" @change="emitUpdate" />
            <span>{{ t('Biography') }}</span>
          </label>
          <label class="checkbox-option">
            <input type="checkbox" v-model="localWidget.showFields.socialLinks" @change="emitUpdate" />
            <span>{{ t('Social links (X/Bluesky/Fediverse)') }}</span>
          </label>
          <label class="checkbox-option">
            <input type="checkbox" v-model="localWidget.showFields.customFields" @change="emitUpdate" />
            <span>{{ t('Custom fields (LDAP/OIDC)') }}</span>
          </label>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n';
import { generateUrl } from '@nextcloud/router';
import axios from '@nextcloud/axios';
import AccountMultiplePlus from 'vue-material-design-icons/AccountMultiplePlus.vue';
import Filter from 'vue-material-design-icons/Filter.vue';
import ViewGrid from 'vue-material-design-icons/ViewGrid.vue';
import ViewList from 'vue-material-design-icons/ViewList.vue';
import ViewModule from 'vue-material-design-icons/ViewModule.vue';
import SortAscending from 'vue-material-design-icons/SortAscending.vue';
import SortDescending from 'vue-material-design-icons/SortDescending.vue';
import Plus from 'vue-material-design-icons/Plus.vue';
import Close from 'vue-material-design-icons/Close.vue';
import UserSelect from './UserSelect.vue';

export default {
  name: 'PeopleWidgetEditor',
  components: {
    AccountMultiplePlus,
    Filter,
    ViewGrid,
    ViewList,
    ViewModule,
    SortAscending,
    SortDescending,
    Plus,
    Close,
    UserSelect,
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
      groups: [],
      availableFields: [],
      filters: [],
      operatorsByFieldType: {
        text: [
          { value: 'equals', label: 'equals' },
          { value: 'contains', label: 'contains' },
          { value: 'not_contains', label: 'does not contain' },
          { value: 'not_empty', label: 'is not empty' },
          { value: 'empty', label: 'is empty' },
        ],
        select: [
          { value: 'equals', label: 'equals' },
          { value: 'in', label: 'is one of' },
          { value: 'not_empty', label: 'is not empty' },
        ],
        date: [
          { value: 'is_today', label: 'is today' },
          { value: 'within_next_days', label: 'is within next X days' },
          { value: 'not_empty', label: 'is not empty' },
          { value: 'empty', label: 'is empty' },
        ],
      },
    };
  },
  computed: {
    layoutOptions() {
      return [
        { value: 'card', label: this.t('Cards'), icon: 'ViewModule' },
        { value: 'list', label: this.t('List'), icon: 'ViewList' },
        { value: 'grid', label: this.t('Grid'), icon: 'ViewGrid' },
      ];
    },
    columnOptions() {
      return this.localWidget.layout === 'grid' ? [2, 3, 4] : [2, 3, 4];
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
          showFields: {
            ...this.createDefaultWidget().showFields,
            ...(newWidget.showFields || {}),
          },
        };
        this.filters = [...(newWidget.filters || [])];
      },
    },
  },
  mounted() {
    this.loadGroups();
    this.loadFields();
  },
  methods: {
    t(key, vars = {}) {
      return t('intravox', key, vars);
    },
    createDefaultWidget() {
      return {
        type: 'people',
        title: '',
        backgroundColor: null,
        selectionMode: 'manual',
        selectedUsers: [],
        filters: [],
        filterOperator: 'AND',
        layout: 'card',
        columns: 3,
        limit: 12,
        sortBy: 'displayName',
        sortOrder: 'asc',
        showFields: {
          // Basic information
          avatar: true,
          displayName: true,
          pronouns: false,
          role: true,
          headline: false,
          department: true,
          // Contact
          email: true,
          phone: false,
          address: false,
          website: false,
          birthdate: false,
          // Extended
          biography: false,
          socialLinks: false,
          customFields: false,
          // Legacy (for backwards compatibility)
          title: true,
        },
      };
    },
    syncTitleWithRole() {
      // Keep legacy 'title' field in sync with 'role'
      this.localWidget.showFields.title = this.localWidget.showFields.role;
      this.emitUpdate();
    },
    emitUpdate() {
      this.localWidget.filters = this.filters;
      this.$emit('update', { ...this.localWidget });
    },
    async loadGroups() {
      try {
        const url = generateUrl('/apps/intravox/api/users/groups');
        const response = await axios.get(url);
        this.groups = response.data.groups || [];
      } catch (err) {
        console.error('[PeopleWidgetEditor] Failed to load groups:', err);
      }
    },
    async loadFields() {
      try {
        const url = generateUrl('/apps/intravox/api/users/fields');
        const response = await axios.get(url);
        this.availableFields = response.data.fields || [];
      } catch (err) {
        console.error('[PeopleWidgetEditor] Failed to load fields:', err);
        // Fallback to basic fields
        this.availableFields = [
          { fieldName: 'group', label: 'Group', type: 'select' },
          { fieldName: 'displayname', label: 'Name', type: 'text' },
          { fieldName: 'email', label: 'Email', type: 'text' },
          { fieldName: 'organisation', label: 'Organisation', type: 'text' },
          { fieldName: 'role', label: 'Role', type: 'text' },
        ];
      }
    },
    setBackgroundColor(color) {
      this.localWidget.backgroundColor = color;
      this.emitUpdate();
    },
    setSelectionMode(mode) {
      this.localWidget.selectionMode = mode;
      this.emitUpdate();
    },
    handleUsersChange(users) {
      this.localWidget.selectedUsers = users;
      this.emitUpdate();
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
      this.localWidget.sortOrder = this.localWidget.sortOrder === 'asc' ? 'desc' : 'asc';
      this.emitUpdate();
    },
    addFilter() {
      this.filters.push({
        fieldName: '',
        operator: 'equals',
        value: '',
        values: [],
      });
      this.emitUpdate();
    },
    removeFilter(index) {
      this.filters.splice(index, 1);
      this.emitUpdate();
    },
    handleFieldChange(filter) {
      // Reset operator and value when field changes
      const fieldType = this.getFieldType(filter.fieldName);
      const operators = this.getOperatorsForField(filter.fieldName);
      filter.operator = operators[0]?.value || 'equals';
      filter.value = '';
      filter.values = [];
      this.emitUpdate();
    },
    getFieldType(fieldName) {
      const field = this.availableFields.find(f => f.fieldName === fieldName);
      return field?.type || 'text';
    },
    getOperatorsForField(fieldName) {
      const fieldType = this.getFieldType(fieldName);
      return this.operatorsByFieldType[fieldType] || this.operatorsByFieldType.text;
    },
    requiresNoValue(operator) {
      return ['not_empty', 'empty', 'is_today'].includes(operator);
    },
    setFilterOperator(op) {
      this.localWidget.filterOperator = op;
      this.emitUpdate();
    },
  },
};
</script>

<style scoped>
.people-widget-editor {
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
  font-weight: 500;
  color: var(--color-main-text);
  font-size: 14px;
}

.label-hint {
  font-weight: 400;
  color: var(--color-text-maxcontrast);
  font-size: 12px;
}

.editor-input,
.editor-select {
  padding: 8px 12px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-main-background);
  font-size: 14px;
}

.editor-input:focus,
.editor-select:focus {
  border-color: var(--color-primary-element);
  outline: none;
}

/* Color presets */
.color-presets {
  display: flex;
  gap: 8px;
}

.color-preset-btn {
  padding: 6px 12px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-main-background);
  cursor: pointer;
  font-size: 13px;
  transition: all 0.15s ease;
}

.color-preset-btn:hover {
  background: var(--color-background-hover);
}

.color-preset-btn.active {
  border-color: var(--color-primary-element);
  background: var(--color-primary-element-light);
}

/* Mode selector */
.mode-selector {
  display: flex;
  gap: 8px;
}

.mode-btn {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  padding: 12px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-main-background);
  cursor: pointer;
  font-size: 13px;
  transition: all 0.15s ease;
}

.mode-btn:hover {
  background: var(--color-background-hover);
}

.mode-btn.active {
  border-color: var(--color-primary-element);
  background: var(--color-primary-element-light);
}

/* Layout options */
.layout-options {
  display: flex;
  gap: 8px;
}

.layout-option {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  padding: 12px 8px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-main-background);
  cursor: pointer;
  transition: all 0.15s ease;
}

.layout-option:hover {
  background: var(--color-background-hover);
}

.layout-option--active {
  border-color: var(--color-primary-element);
  background: var(--color-primary-element-light);
}

.layout-option span {
  font-size: 12px;
  color: var(--color-main-text);
}

/* Columns selector */
.columns-selector {
  display: flex;
  gap: 8px;
}

.column-option {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-main-background);
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.15s ease;
}

.column-option:hover {
  background: var(--color-background-hover);
}

.column-option--active {
  border-color: var(--color-primary-element);
  background: var(--color-primary-element-light);
}

/* Limit selector */
.limit-selector {
  display: flex;
  align-items: center;
  gap: 12px;
}

.limit-slider {
  flex: 1;
}

.limit-value {
  min-width: 30px;
  text-align: right;
  font-weight: 500;
}

/* Sort options */
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
  padding: 8px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-main-background);
  cursor: pointer;
  transition: all 0.15s ease;
}

.sort-order-button:hover {
  background: var(--color-background-hover);
}

/* Display options */
.display-options {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.display-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding-bottom: 12px;
  margin-bottom: 12px;
  border-bottom: 1px solid var(--color-border-dark);
}

.display-group:last-child {
  border-bottom: none;
  padding-bottom: 0;
  margin-bottom: 0;
}

.display-group-header {
  font-size: 11px;
  font-weight: 600;
  color: var(--color-text-maxcontrast);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 4px;
}

.checkbox-option {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-size: 14px;
}

.checkbox-option input[type="checkbox"] {
  width: 16px;
  height: 16px;
}

/* Filters */
.filters-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 8px;
}

.filter-row {
  display: flex;
  gap: 8px;
  align-items: flex-start;
}

.filter-field {
  flex: 1;
  padding: 6px 8px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  font-size: 13px;
}

.filter-operator {
  width: 120px;
  padding: 6px 8px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  font-size: 13px;
}

.filter-value {
  flex: 1;
  padding: 6px 8px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  font-size: 13px;
}

.filter-value--multi {
  min-height: 60px;
}

.filter-remove {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 6px;
  border: none;
  background: none;
  color: var(--color-text-maxcontrast);
  cursor: pointer;
  border-radius: var(--border-radius);
}

.filter-remove:hover {
  background: var(--color-background-hover);
  color: var(--color-error);
}

.filter-operator-toggle {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 8px;
  font-size: 13px;
  color: var(--color-text-maxcontrast);
}

.operator-button {
  padding: 4px 10px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-main-background);
  cursor: pointer;
  font-size: 13px;
}

.operator-button:hover {
  background: var(--color-background-hover);
}

.operator-button--active {
  border-color: var(--color-primary-element);
  background: var(--color-primary-element-light);
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
  cursor: pointer;
  font-size: 13px;
  transition: all 0.15s ease;
}

.add-filter-button:hover {
  border-color: var(--color-primary-element);
  color: var(--color-primary-element);
  background: var(--color-primary-element-light);
}
</style>
