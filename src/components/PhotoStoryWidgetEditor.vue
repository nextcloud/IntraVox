<template>
  <div class="photo-story-editor">
    <!-- ============ SECTION: SOURCE ============ -->
    <h4 class="ps-section-heading">{{ t('intravox', 'Source') }}</h4>

    <!-- Folder picker — empty + filters = cross-folder MetaVox search; "/" = whole drive -->
    <div class="editor-section">
      <label class="editor-label" for="ps-folder">{{ t('intravox', 'Source folder') }}</label>
      <div class="ps-folder-row">
        <input
          id="ps-folder"
          type="text"
          v-model="localConfig.folderPath"
          :placeholder="t('intravox', '/Photos/Albums/…')"
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
      <p class="editor-hint">
        {{ t('intravox', 'Pick a folder, or use "/" for your entire drive.') }}
        <template v-if="metaVoxAvailable">
          {{ t('intravox', 'Leave blank to search MetaVox-tagged photos across all folders — requires at least one filter below.') }}
        </template>
      </p>
      <div class="ps-capability-badge" :class="{ rich: metaVoxAvailable }">
        <CheckCircle v-if="metaVoxAvailable" :size="14" />
        <AlertCircle v-else :size="14" />
        <span v-if="capLoading">{{ t('intravox', 'Checking capabilities …') }}</span>
        <span v-else-if="metaVoxAvailable">{{ t('intravox', 'MetaVox: rich metadata available') }}</span>
        <span v-else>{{ t('intravox', 'MetaVox: basic EXIF only') }}</span>
      </div>
    </div>

    <!-- MetaVox filter builder -->
    <div class="editor-section" v-if="metaVoxAvailable">
      <div class="editor-label">{{ t('intravox', 'Filters') }}</div>
      <p v-if="!localConfig.metaVoxFilters || localConfig.metaVoxFilters.length === 0" class="editor-hint">
        {{ t('intravox', 'No filters. Click "Add filter" to filter by date, location, people, or subject.') }}
      </p>
      <div v-for="(filter, idx) in localConfig.metaVoxFilters || []" :key="idx" class="ps-filter-row">
        <NcSelect
          :model-value="fieldOptionFor(filter.field)"
          :options="filterFieldOptions"
          :placeholder="t('intravox', 'Field')"
          class="ps-filter-field"
          @update:model-value="setFilterField(idx, $event)"
        />
        <NcSelect
          :model-value="opOptionFor(filter.op, filter.field)"
          :options="operatorOptionsFor(filter.field)"
          :placeholder="t('intravox', 'Operator')"
          class="ps-filter-op"
          @update:model-value="setFilterOp(idx, $event)"
        />
        <!-- Render the value input based on the field's type and operator -->
        <NcSelect
          v-if="valueRenderFor(filter) === 'select'"
          :model-value="filter.value"
          :options="valueOptionsFor(filter.field)"
          :taggable="filter.op === 'in' || valueOptionsFor(filter.field).length === 0"
          :multiple="filter.op === 'in'"
          :placeholder="t('intravox', 'Value')"
          class="ps-filter-value"
          @update:model-value="setFilterValue(idx, $event)"
        />
        <input
          v-else-if="valueRenderFor(filter) === 'date'"
          type="date"
          :value="filter.value"
          class="editor-input ps-filter-value-text"
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
          class="editor-input ps-filter-value-text ps-narrow"
          @input="setFilterValue(idx, $event.target.value)"
        />
        <input
          v-else-if="valueRenderFor(filter) === 'number'"
          type="number"
          :value="filter.value"
          :placeholder="t('intravox', 'Value')"
          class="editor-input ps-filter-value-text ps-narrow"
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
          class="editor-input ps-filter-value-text"
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
      <p
        v-if="isCrossFolderEffective && (!localConfig.metaVoxFilters || localConfig.metaVoxFilters.length === 0)"
        class="ps-filter-warning"
      >
        {{ t('intravox', 'Cross-folder mode (empty source folder) requires at least one filter') }}
      </p>
    </div>

    <!-- ============ SECTION: LAYOUT ============ -->
    <h4 class="ps-section-heading">{{ t('intravox', 'Layout') }}</h4>

    <!-- Mode -->
    <div class="editor-section">
      <div class="editor-label">{{ t('intravox', 'Layout mode') }}</div>
      <div class="ps-mode-grid">
        <button
          v-for="opt in modeOptions"
          :key="opt.value"
          type="button"
          class="ps-mode-btn"
          :class="{ active: localConfig.mode === opt.value }"
          :disabled="!isModeAvailable(opt.value)"
          :title="isModeAvailable(opt.value) ? opt.label : t('intravox', 'Requires metadata not available in this folder')"
          @click="setMode(opt.value)"
        >
          <component :is="opt.icon" :size="24" />
          <span>{{ opt.label }}</span>
        </button>
      </div>
      <p class="editor-hint">{{ modeHint }}</p>
    </div>

    <!-- Sort-by + direction: shown for any mode that has a clear order
         (Timeline / Grid / On-this-day). Highlights has its own scorer so
         the dropdown is hidden there. -->
    <div v-if="sortApplicable" class="editor-section">
      <div class="editor-label">{{ t('intravox', 'Order') }}</div>
      <div class="ps-sort-row">
        <NcSelect
          :model-value="sortByOption"
          :options="sortByOptions"
          :placeholder="t('intravox', 'Sort by')"
          class="ps-sort-by"
          @update:model-value="setSortBy"
        />
        <div class="ps-sort-group">
          <NcCheckboxRadioSwitch
            :model-value="localConfig.sortOrder || 'desc'"
            value="desc"
            name="ps-sort"
            type="radio"
            @update:model-value="setSortOrder"
          >
            {{ sortDirLabelDesc }}
          </NcCheckboxRadioSwitch>
          <NcCheckboxRadioSwitch
            :model-value="localConfig.sortOrder || 'desc'"
            value="asc"
            name="ps-sort"
            type="radio"
            @update:model-value="setSortOrder"
          >
            {{ sortDirLabelAsc }}
          </NcCheckboxRadioSwitch>
        </div>
      </div>
      <p v-if="metaVoxAvailable && isMetaVoxSortKey" class="editor-hint">
        {{ t('intravox', 'Sorting on a MetaVox field reorders within each loaded page (best-effort across infinite scroll).') }}
      </p>
    </div>

    <!-- Visual style — only for Timeline -->
    <div v-if="localConfig.mode === 'timeline'" class="editor-section">
      <div class="editor-label">{{ t('intravox', 'Visual style') }}</div>
      <div class="ps-style-group">
        <NcCheckboxRadioSwitch
          v-for="opt in styleOptions"
          :key="opt.value"
          :model-value="localConfig.style"
          :value="opt.value"
          name="ps-style"
          type="radio"
          @update:model-value="setStyle"
        >
          <strong>{{ opt.label }}</strong>
          <small class="ps-style-hint">{{ opt.hint }}</small>
        </NcCheckboxRadioSwitch>
      </div>
    </div>

    <!-- ============ SECTION: DISPLAY ============ -->
    <h4 class="ps-section-heading">{{ t('intravox', 'Display') }}</h4>

    <!-- Limit -->
    <div class="editor-section">
      <label class="editor-label" for="ps-limit">{{ t('intravox', 'Maximum photos') }}</label>
      <input
        id="ps-limit"
        type="number"
        min="1"
        max="500"
        v-model.number="localConfig.limit"
        :placeholder="t('intravox', 'All')"
        class="editor-input ps-narrow"
        @input="emitUpdate"
      />
      <p class="editor-hint">{{ t('intravox', 'Cap the total number of photos shown. Leave blank to load everything via infinite scroll.') }}</p>
    </div>

    <!-- Columns (grid mode) -->
    <div v-if="localConfig.mode === 'grid'" class="editor-section">
      <div class="editor-label">{{ t('intravox', 'Columns') }}</div>
      <div class="ps-columns">
        <button
          v-for="c in [2, 3, 4, 5]"
          :key="c"
          type="button"
          class="ps-col-btn"
          :class="{ active: localConfig.columns === c }"
          @click="setColumns(c)"
        >
          {{ c }}
        </button>
      </div>
    </div>

    <!-- Toggles -->
    <div class="editor-section">
      <NcCheckboxRadioSwitch
        :model-value="localConfig.showCaptions !== false"
        @update:model-value="toggleCaptions"
      >
        {{ t('intravox', 'Show captions') }}
      </NcCheckboxRadioSwitch>

      <NcCheckboxRadioSwitch
        :model-value="!!localConfig.showMap"
        :disabled="!capabilities || !capabilities.hasLocation || !mapGloballyEnabled"
        @update:model-value="toggleMap"
      >
        {{ t('intravox', 'Show overview map') }}
        <small v-if="!mapGloballyEnabled" class="ps-disabled-hint">
          ({{ t('intravox', 'Disabled by administrator') }})
        </small>
        <small v-else-if="capabilities && !capabilities.hasLocation" class="ps-disabled-hint">
          ({{ t('intravox', 'No location data') }})
        </small>
      </NcCheckboxRadioSwitch>

      <NcCheckboxRadioSwitch
        :model-value="localConfig.showDayMaps !== false"
        :disabled="!capabilities || !capabilities.hasLocation || localConfig.mode !== 'timeline' || !mapGloballyEnabled"
        @update:model-value="toggleDayMaps"
      >
        {{ t('intravox', 'Show daily mini-map') }}
        <small v-if="!mapGloballyEnabled" class="ps-disabled-hint">
          ({{ t('intravox', 'Disabled by administrator') }})
        </small>
        <small v-else-if="localConfig.mode !== 'timeline'" class="ps-disabled-hint">
          ({{ t('intravox', 'Timeline only') }})
        </small>
        <small v-else-if="capabilities && !capabilities.hasLocation" class="ps-disabled-hint">
          ({{ t('intravox', 'No location data') }})
        </small>
      </NcCheckboxRadioSwitch>

      <p
        v-if="capabilities && !capabilities.hasLocation && mapGloballyEnabled"
        class="editor-hint ps-no-gps-hint"
      >
        {{ t('intravox', 'No GPS data found in this folder. Map features are disabled. Run the MetaVox EXIF backfill to enable maps for photos with embedded GPS.') }}
      </p>

      <NcCheckboxRadioSwitch
        :model-value="localConfig.hideRawDuplicates !== false"
        @update:model-value="toggleHideRawDuplicates"
      >
        {{ t('intravox', 'Hide RAW when JPG/HEIC exists') }}
        <small class="ps-disabled-hint">
          ({{ t('intravox', 'Collapses RAW+JPG pairs from RAW-capable cameras') }})
        </small>
      </NcCheckboxRadioSwitch>
    </div>

  </div>
</template>

<script>
import axios from '@nextcloud/axios';
import { generateUrl } from '@nextcloud/router';
import { translate, translatePlural } from '@nextcloud/l10n';
import { getFilePickerBuilder } from '@nextcloud/dialogs';
import { NcButton, NcCheckboxRadioSwitch, NcSelect } from '@nextcloud/vue';
import FolderOpen from 'vue-material-design-icons/FolderOpen.vue';
import CheckCircle from 'vue-material-design-icons/CheckCircle.vue';
import AlertCircle from 'vue-material-design-icons/AlertCircle.vue';
import ViewList from 'vue-material-design-icons/ViewList.vue';
import ViewGallery from 'vue-material-design-icons/ViewGallery.vue';
import ViewModule from 'vue-material-design-icons/ViewModule.vue';
import Calendar from 'vue-material-design-icons/Calendar.vue';
import Plus from 'vue-material-design-icons/Plus.vue';
import Close from 'vue-material-design-icons/Close.vue';

export default {
  name: 'PhotoStoryWidgetEditor',
  components: {
    NcButton,
    NcCheckboxRadioSwitch,
    NcSelect,
    FolderOpen,
    CheckCircle,
    AlertCircle,
    ViewList,
    ViewGallery,
    ViewModule,
    Calendar,
    Plus,
    Close,
  },
  props: {
    widget: { type: Object, required: true },
  },
  emits: ['update'],
  data() {
    return {
      localConfig: this.createDefaultConfig(),
      capabilities: null,
      metaVoxAvailable: false,
      mapGloballyEnabled: true,
      capLoading: true,
      metaVoxFields: [],
    };
  },
  computed: {
    modeOptions() {
      return [
        { value: 'timeline', label: this.t('intravox', 'Timeline'), icon: 'ViewList' },
        { value: 'highlights', label: this.t('intravox', 'Highlights'), icon: 'ViewGallery' },
        { value: 'grid', label: this.t('intravox', 'Grid'), icon: 'ViewModule' },
        { value: 'on-this-day', label: this.t('intravox', 'On this day'), icon: 'Calendar' },
      ];
    },
    styleOptions() {
      return [
        { value: 'magazine', label: this.t('intravox', 'Magazine'), hint: this.t('intravox', 'Glossy editorial (serif)') },
        { value: 'apple', label: this.t('intravox', 'Apple'), hint: this.t('intravox', 'Clean grid (default)') },
        { value: 'travelogue', label: this.t('intravox', 'Travelogue'), hint: this.t('intravox', 'Travel diary with timeline rail') },
      ];
    },
    sortApplicable() {
      // Highlights uses its own internal scorer; Map mode (if present) too.
      const m = this.localConfig.mode;
      return m === 'timeline' || m === 'grid' || m === 'on-this-day';
    },
    sortByOptions() {
      // Base file-level options always available
      const base = [
        { label: this.t('intravox', 'Date taken'), value: 'taken_at', isMeta: true },
        { label: this.t('intravox', 'Date modified'), value: 'mtime', isMeta: false },
        { label: this.t('intravox', 'Filename'), value: 'name', isMeta: false },
        { label: this.t('intravox', 'File size'), value: 'size', isMeta: false },
      ];
      // MetaVox fields contribute additional sort keys. Skip multiselect/tags
      // (no sane sort order) and checkbox (binary).
      const skipTypes = new Set(['multiselect', 'tags', 'checkbox']);
      const fromMv = (this.metaVoxFields || [])
        .filter(f => !skipTypes.has(f.field_type))
        .filter(f => !['exif_taken_at'].includes(f.field_name))  // already covered by 'taken_at'
        .map(f => ({ label: f.field_label || f.field_name, value: f.field_name, isMeta: true }));
      return [...base, ...fromMv];
    },
    sortByOption() {
      const v = this.localConfig.sortBy || 'mtime';
      return this.sortByOptions.find(o => o.value === v) || this.sortByOptions[0];
    },
    isMetaVoxSortKey() {
      const opt = this.sortByOption;
      // Anything beyond the basic file-level columns is treated as metadata-driven
      return opt && opt.value !== 'mtime' && opt.value !== 'name' && opt.value !== 'size';
    },
    sortDirLabelDesc() {
      const v = this.localConfig.sortBy || 'mtime';
      // For temporal sorts the natural reading is "Newest first"; for name/text
      // sorts "Z–A" is clearer than "Descending"; for size "Largest first".
      if (v === 'taken_at' || v === 'mtime') return this.t('intravox', 'Newest first');
      if (v === 'name') return this.t('intravox', 'Z → A');
      if (v === 'size') return this.t('intravox', 'Largest first');
      return this.t('intravox', 'Descending');
    },
    sortDirLabelAsc() {
      const v = this.localConfig.sortBy || 'mtime';
      if (v === 'taken_at' || v === 'mtime') return this.t('intravox', 'Oldest first');
      if (v === 'name') return this.t('intravox', 'A → Z');
      if (v === 'size') return this.t('intravox', 'Smallest first');
      return this.t('intravox', 'Ascending');
    },
    modeHint() {
      switch (this.localConfig.mode) {
        case 'timeline':
          return this.t('intravox', 'Chronological — photos grouped by day with sticky date headers.');
        case 'highlights':
          return this.t('intravox', 'Automatically curated — best photos are picked using EXIF metadata (people, subjects, location, file size) and duplicates from photo bursts are collapsed.');
        case 'grid':
          return this.t('intravox', 'Masonry grid — all photos in a continuous wall, no date grouping.');
        case 'on-this-day':
          return this.t('intravox', 'Photos taken on this same date in earlier years.');
        default:
          return '';
      }
    },
    filterFieldOptions() {
      return this.metaVoxFields.map(f => ({
        label: f.field_label || f.field_name,
        value: f.field_name,
      }));
    },
    /**
     * Cross-folder mode is no longer an explicit user toggle — it's emergent
     * from leaving the source folder blank (the backend already interprets
     * empty as cross-folder; this just gives the UI something to react to).
     */
    isCrossFolderEffective() {
      const path = (this.localConfig.folderPath || '').trim();
      return path === '';
    },
  },
  watch: {
    /**
     * Keep the legacy allMetaVoxFolders flag in sync with the emergent
     * cross-folder mode so pages saved by 1.5.4+ stay consistent for older
     * page-readers (the field is still part of the persisted config).
     */
    widget: {
      immediate: true,
      deep: true,
      handler(w) {
        this.localConfig = {
          ...this.createDefaultConfig(),
          ...(w.config || {}),
        };
      },
    },
    'localConfig.folderPath'(newVal) {
      // Sync the legacy `allMetaVoxFolders` flag with emergent cross-folder mode.
      const path = (newVal || '').trim();
      const wantsCross = (path === '');
      if (this.localConfig.allMetaVoxFolders !== wantsCross) {
        this.localConfig.allMetaVoxFolders = wantsCross;
      }
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
        style: 'apple',
        limit: null,
        columns: 3,
        showCaptions: true,
        showMap: false,
        showDayMaps: true,
        sortOrder: 'desc',
        sortBy: 'mtime',
        allMetaVoxFolders: false,
        metaVoxFilters: [],
        hideRawDuplicates: true,
      };
    },
    async fetchCapabilities() {
      this.capLoading = true;
      try {
        const url = generateUrl('/apps/intravox/api/photo-story/capabilities');
        const res = await axios.get(url);
        this.capabilities = res.data?.capabilities || null;
        this.metaVoxAvailable = !!res.data?.metaVoxAvailable;
        // Honor admin global map-disable: hides toggles and dimms map preview.
        if (res.data?.map && typeof res.data.map === 'object') {
          this.mapGloballyEnabled = res.data.map.enabled !== false;
        }
      } catch (e) {
        this.capabilities = null;
        this.metaVoxAvailable = false;
        this.mapGloballyEnabled = true;
      } finally {
        this.capLoading = false;
      }
    },
    isModeAvailable(mode) {
      if (!this.capabilities) return true;
      if (mode === 'on-this-day') return !!this.capabilities.hasDate;
      return true;
    },
    setMode(mode) {
      if (!this.isModeAvailable(mode)) return;
      this.localConfig.mode = mode;
      this.emitUpdate();
    },
    setStyle(val) {
      if (!val) return;
      this.localConfig.style = val;
      this.emitUpdate();
    },
    setSortOrder(val) {
      if (val !== 'asc' && val !== 'desc') return;
      this.localConfig.sortOrder = val;
      this.emitUpdate();
    },
    setSortBy(opt) {
      const v = opt && opt.value ? String(opt.value) : 'mtime';
      this.localConfig.sortBy = v;
      this.emitUpdate();
    },
    setColumns(c) {
      this.localConfig.columns = c;
      this.emitUpdate();
    },
    toggleCaptions(val) {
      this.localConfig.showCaptions = !!val;
      this.emitUpdate();
    },
    toggleMap(val) {
      this.localConfig.showMap = !!val;
      this.emitUpdate();
    },
    toggleDayMaps(val) {
      this.localConfig.showDayMaps = !!val;
      this.emitUpdate();
    },
    toggleHideRawDuplicates(val) {
      this.localConfig.hideRawDuplicates = !!val;
      this.emitUpdate();
    },
    async loadMetaVoxFields() {
      try {
        const url = generateUrl('/apps/intravox/api/photo-story/metavox-fields');
        const res = await axios.get(url);
        this.metaVoxFields = Array.isArray(res.data?.fields) ? res.data.fields : [];
      } catch (e) {
        this.metaVoxFields = [];
      }
    },
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
          return [
            { label: this.t('intravox', 'equals'), value: 'equals' },
          ];
        case 'number':
          return [
            { label: this.t('intravox', 'equals'), value: 'equals' },
          ];
        // text, url, user, filelink, anything unknown
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
    /**
     * Decide which value-input control to render for a filter row. Driven by
     * the MetaVox field_type so user-created custom fields get the right
     * widget without IntraVox knowing them by name.
     */
    valueRenderFor(filter) {
      const def = this.fieldDefByName(filter.field);
      const type = def?.field_type || 'text';
      const op = filter.op || 'equals';
      // Year operator on date field → integer year input
      if ((type === 'date' || type === 'datetime') && op === 'year_equals') {
        return 'year';
      }
      // Plain date input
      if (type === 'date' || type === 'datetime') {
        return 'date';
      }
      if (type === 'number') {
        return 'number';
      }
      if (type === 'checkbox') {
        return 'checkbox';
      }
      // Enumerated values from MetaVox field_options
      if (this.valueOptionsFor(filter.field).length > 0) {
        return 'select';
      }
      // multiselect/tags free-form: still a tag-input so user can type
      if (type === 'multiselect' || type === 'tags') {
        return 'select';  // taggable=true is set in the template based on op
      }
      // text, url, user, filelink, anything else
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
      // Reset operator to a valid default for the new field type.
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
      // NcSelect can pass an object {label,value} or a plain string when taggable
      let v = value;
      if (v && typeof v === 'object' && !Array.isArray(v)) {
        v = v.value;
      }
      this.localConfig.metaVoxFilters[idx].value = v ?? '';
      this.emitUpdate();
    },
    async browseFolder() {
      try {
        const picker = getFilePickerBuilder(this.t('intravox', 'Pick a photo folder'))
          .setMultiSelect(false)
          .setMimeTypeFilter(['httpd/unix-directory'])
          .setType(1) // FilePickerType.Choose
          .allowDirectories(true)
          .build();
        const result = await picker.pick();
        // Normalise an empty / root selection to '/' so the widget knows to
        // search the user's whole drive instead of silently dropping the
        // existing folderPath config.
        const toPath = (r) => {
          if (typeof r === 'string') return r === '' ? '/' : r;
          if (Array.isArray(r) && r.length > 0) {
            const first = r[0];
            const p = typeof first === 'string' ? first : (first?.path || '');
            return p === '' ? '/' : p;
          }
          return null;
        };
        const next = toPath(result);
        if (next !== null) {
          this.localConfig.folderPath = next;
          this.emitUpdate();
        }
      } catch (e) {
        // User cancelled or picker unavailable
      }
    },
    emitUpdate() {
      this.$emit('update', {
        ...this.widget,
        type: 'photo-story',
        config: { ...this.localConfig },
      });
    },
  },
};
</script>

<style scoped>
.photo-story-editor {
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

.editor-hint {
  margin: 0;
  font-size: 12px;
  color: var(--color-text-maxcontrast);
}

.editor-input {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-main-background);
  color: var(--color-main-text);
  font-size: 14px;
}

.editor-input.ps-narrow {
  max-width: 160px;
}

.editor-input:focus {
  outline: none;
  border-color: var(--color-primary);
}

.ps-folder-row {
  display: flex;
  gap: 8px;
  align-items: center;
}

.ps-folder-row .editor-input {
  flex: 1;
}

.ps-capability-badge {
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

.ps-capability-badge.rich {
  background: var(--color-primary-element-light);
  color: var(--color-primary-element-light-text);
}

.ps-section-heading {
  margin: 24px 0 4px;
  padding-bottom: 6px;
  border-bottom: 1px solid var(--color-border);
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--color-text-maxcontrast);
}

.ps-section-heading:first-child {
  margin-top: 0;
}

.ps-mode-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
}

.ps-mode-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  padding: 12px 8px;
  border: 2px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-main-background);
  cursor: pointer;
  transition: all 0.15s;
  color: var(--color-main-text);
}

.ps-mode-btn:hover:not(:disabled) {
  border-color: var(--color-primary-element-light);
}

.ps-mode-btn.active {
  border-color: var(--color-primary);
  background: var(--color-primary-element-light);
}

.ps-mode-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.ps-mode-btn span {
  font-size: 12px;
  font-weight: 500;
}

.ps-columns {
  display: flex;
  gap: 6px;
}

.ps-col-btn {
  width: 40px;
  height: 36px;
  border: 2px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-main-background);
  font-weight: 600;
  cursor: pointer;
}

.ps-col-btn:hover {
  border-color: var(--color-primary-element-light);
}

.ps-col-btn.active {
  border-color: var(--color-primary);
  background: var(--color-primary-element-light);
}

.ps-disabled-hint {
  margin-left: 6px;
  color: var(--color-text-maxcontrast);
  font-size: 11px;
}

.ps-no-gps-hint {
  margin-top: 8px;
  padding: 8px 10px;
  background: var(--color-background-hover);
  border-left: 3px solid var(--color-warning, #ffa500);
  font-size: 12px;
  color: var(--color-main-text);
  line-height: 1.4;
}

.ps-style-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.ps-sort-row {
  display: flex;
  gap: 12px;
  align-items: flex-start;
  flex-wrap: wrap;
}

.ps-sort-by {
  flex: 1;
  min-width: 200px;
  max-width: 280px;
}

.ps-sort-group {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.ps-style-hint {
  display: block;
  font-size: 11px;
  color: var(--color-text-maxcontrast);
  margin-left: 4px;
  font-weight: 400;
}

.ps-filter-row {
  display: flex;
  gap: 8px;
  align-items: center;
  margin-bottom: 8px;
}

.ps-filter-field {
  min-width: 140px;
  flex: 1;
}

.ps-filter-op {
  min-width: 120px;
}

.ps-filter-value {
  min-width: 140px;
  flex: 1;
}

.ps-filter-value-text {
  min-width: 140px;
  flex: 1;
}

.ps-filter-warning {
  margin: 8px 0 0 0;
  padding: 6px 10px;
  border-radius: var(--border-radius);
  background: var(--color-warning, #f1c40f);
  color: var(--color-main-text);
  font-size: 12px;
}

@media (max-width: 600px) {
  .ps-mode-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .ps-filter-row {
    flex-wrap: wrap;
  }
}
</style>
