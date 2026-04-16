<template>
  <div class="calendar-widget-editor">
    <!-- Widget Title -->
    <div class="editor-section">
      <label class="editor-label" for="calendar-widget-title">{{ t('Widget title (optional)') }}</label>
      <input
        id="calendar-widget-title"
        type="text"
        v-model="localWidget.title"
        :placeholder="t('e.g., Upcoming Events')"
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

    <!-- Calendar Selection -->
    <div class="editor-section">
      <label class="editor-label">{{ t('Calendars') }}</label>

      <div v-if="loadingCalendars" class="calendars-loading">
        <NcLoadingIcon :size="20" />
        <span>{{ t('Loading calendars...') }}</span>
      </div>

      <div v-else-if="calendars.length === 0" class="calendars-empty">
        <span>{{ t('No calendars available') }}</span>
      </div>

      <div v-else class="calendar-list">
        <label
          v-for="calendar in calendars"
          :key="calendar.id"
          class="calendar-option"
        >
          <input
            type="checkbox"
            :value="calendar.id"
            v-model="localWidget.calendarIds"
            @change="emitUpdate"
          />
          <span
            class="calendar-color-dot"
            :style="{ backgroundColor: calendar.color }"
          ></span>
          <span class="calendar-name">{{ calendar.displayName }}</span>
          <span v-if="calendar.isReadOnly" class="calendar-readonly">({{ t('read-only') }})</span>
        </label>
      </div>
    </div>

    <!-- Date Range -->
    <div class="editor-section">
      <label class="editor-label" for="calendar-widget-date-range">{{ t('Date range') }}</label>
      <select id="calendar-widget-date-range" v-model="localWidget.dateRange" class="editor-select" @change="emitUpdate">
        <optgroup :label="t('Future')">
          <option value="upcoming">{{ t('Upcoming (30 days)') }}</option>
          <option value="this_week">{{ t('This week') }}</option>
          <option value="next_two_weeks">{{ t('Next 2 weeks') }}</option>
          <option value="this_month">{{ t('This month') }}</option>
          <option value="next_three_months">{{ t('Next 3 months') }}</option>
          <option value="next_six_months">{{ t('Next 6 months') }}</option>
          <option value="next_year">{{ t('Next year') }}</option>
        </optgroup>
        <optgroup :label="t('Past')">
          <option value="past_week">{{ t('Past week') }}</option>
          <option value="past_month">{{ t('Past month') }}</option>
          <option value="past_three_months">{{ t('Past 3 months') }}</option>
        </optgroup>
      </select>
    </div>

    <!-- Number of events -->
    <div class="editor-section">
      <label class="editor-label" for="calendar-widget-limit">{{ t('Number of events') }}</label>
      <div class="limit-selector">
        <input
          id="calendar-widget-limit"
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

    <!-- Display Options -->
    <div class="editor-section">
      <label class="editor-label">{{ t('Display options') }}</label>
      <div class="display-options">
        <label class="checkbox-option">
          <input type="checkbox" v-model="localWidget.showTime" @change="emitUpdate" />
          <span>{{ t('Show time') }}</span>
        </label>
        <label class="checkbox-option">
          <input type="checkbox" v-model="localWidget.showLocation" @change="emitUpdate" />
          <span>{{ t('Show location') }}</span>
        </label>
      </div>
    </div>
  </div>
</template>

<script>
import { NcLoadingIcon } from '@nextcloud/vue';
import { generateUrl } from '@nextcloud/router';
import { translate as t } from '@nextcloud/l10n';
import axios from '@nextcloud/axios';

export default {
  name: 'CalendarWidgetEditor',
  components: {
    NcLoadingIcon,
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
      calendars: [],
      loadingCalendars: true,
    };
  },
  watch: {
    widget: {
      immediate: true,
      deep: true,
      handler(newWidget) {
        this.localWidget = {
          ...this.createDefaultWidget(),
          ...newWidget,
          // Ensure calendarIds is always an array
          calendarIds: [...(newWidget.calendarIds || [])],
        };
      },
    },
  },
  mounted() {
    this.fetchCalendars();
  },
  methods: {
    t(key, vars = {}) {
      return t('intravox', key, vars);
    },
    createDefaultWidget() {
      return {
        type: 'calendar',
        title: '',
        backgroundColor: null,
        calendarIds: [],
        dateRange: 'upcoming',
        limit: 5,
        showTime: true,
        showLocation: false,
      };
    },
    async fetchCalendars() {
      this.loadingCalendars = true;
      try {
        const url = generateUrl('/apps/intravox/api/calendar/calendars');
        const response = await axios.get(url);
        this.calendars = response.data.calendars || [];
      } catch (err) {
        console.error('[CalendarWidgetEditor] Failed to fetch calendars:', err);
        this.calendars = [];
      } finally {
        this.loadingCalendars = false;
      }
    },
    setBackgroundColor(color) {
      this.localWidget.backgroundColor = color;
      this.emitUpdate();
    },
    emitUpdate() {
      this.$emit('update', { ...this.localWidget });
    },
  },
};
</script>

<style scoped>
.calendar-widget-editor {
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

.editor-input {
  padding: 8px 12px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-main-background);
  color: var(--color-main-text);
  font-size: 14px;
}

.editor-select {
  padding: 8px 12px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-main-background);
  color: var(--color-main-text);
  font-size: 14px;
}

.color-presets {
  display: flex;
  gap: 8px;
}

.color-preset-btn {
  padding: 6px 12px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-main-background);
  color: var(--color-main-text);
  cursor: pointer;
  font-size: 13px;
}

.color-preset-btn.active {
  border-color: var(--color-primary);
  background: var(--color-primary-element-light);
  color: var(--color-primary-element-light-text);
}

.calendars-loading {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px;
  color: var(--color-text-maxcontrast);
  font-size: 13px;
}

.calendars-empty {
  padding: 12px;
  color: var(--color-text-maxcontrast);
  font-size: 13px;
  font-style: italic;
}

.calendar-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
  max-height: 200px;
  overflow-y: auto;
  padding: 4px 0;
}

.calendar-option {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 8px;
  border-radius: var(--border-radius);
  cursor: pointer;
  font-size: 14px;
  transition: background 0.15s;
}

.calendar-option:hover {
  background: var(--color-background-hover);
}

.calendar-color-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}

.calendar-name {
  color: var(--color-main-text);
}

.calendar-readonly {
  color: var(--color-text-maxcontrast);
  font-size: 12px;
}

.limit-selector {
  display: flex;
  align-items: center;
  gap: 12px;
}

.limit-slider {
  flex: 1;
  accent-color: var(--color-primary);
}

.limit-value {
  min-width: 30px;
  text-align: center;
  font-weight: 600;
  font-size: 14px;
  color: var(--color-main-text);
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
  font-size: 14px;
  color: var(--color-main-text);
}
</style>
