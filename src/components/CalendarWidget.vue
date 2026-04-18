<template>
  <div class="calendar-widget" :class="{ 'has-background': !!effectiveBackgroundColor, 'has-dark-background': hasDarkBackground }" :style="getContainerStyle()">
    <h3 v-if="widget.title" class="calendar-widget-title" :style="titleStyle">{{ widget.title }}</h3>

    <div v-if="loading" class="calendar-loading" role="status" aria-live="polite">
      <NcLoadingIcon :size="32" />
      <span>{{ t('Loading events...') }}</span>
    </div>

    <div v-else-if="error" class="calendar-error" role="alert">
      <AlertCircle :size="24" />
      <span>{{ error }}</span>
    </div>

    <div v-else-if="!hasCalendars" class="calendar-empty">
      <CalendarIcon :size="32" />
      <p>{{ t('No calendar selected') }}</p>
      <small>{{ t('Select one or more calendars in the widget editor') }}</small>
    </div>

    <div v-else-if="events.length === 0" class="calendar-empty">
      <CalendarIcon :size="32" />
      <p>{{ t('No upcoming events') }}</p>
    </div>

    <div v-else class="calendar-event-list">
      <component
        :is="getEventUrl(event) ? 'a' : 'div'"
        v-for="event in events"
        :key="event.uid"
        :href="getEventUrl(event) || undefined"
        :target="event.isExternal && getEventUrl(event) ? '_blank' : undefined"
        :rel="event.isExternal && getEventUrl(event) ? 'noopener noreferrer' : undefined"
        class="calendar-event"
        :class="{ 'calendar-event--no-link': !getEventUrl(event) }"
      >
        <div class="event-date-badge" :style="{ backgroundColor: event.calendarColor }">
          <span class="event-date-day">{{ getDayNumber(event.start) }}</span>
          <span class="event-date-month">{{ getMonthAbbr(event.start) }}</span>
        </div>
        <div class="event-details">
          <span v-if="getProximityLabel(event.start)" class="event-proximity" :style="{ color: event.calendarColor }">
            {{ getProximityLabel(event.start) }}
          </span>
          <span class="event-title">{{ event.summary }}</span>
          <span v-if="widget.showTime !== false" class="event-time">
            <template v-if="event.isAllDay">{{ t('All day') }}</template>
            <template v-else>
              {{ formatTime(event.start) }}<template v-if="event.end"> - {{ formatTime(event.end) }}</template>
            </template>
          </span>
          <span v-if="widget.showLocation && event.location" class="event-location">
            {{ event.location }}
          </span>
        </div>
      </component>
    </div>
  </div>
</template>

<script>
import { NcLoadingIcon } from '@nextcloud/vue';
import { generateUrl } from '@nextcloud/router';
import { translate as t } from '@nextcloud/l10n';
import axios from '@nextcloud/axios';
import AlertCircle from 'vue-material-design-icons/AlertCircle.vue';
import CalendarIcon from 'vue-material-design-icons/Calendar.vue';

export default {
  name: 'CalendarWidget',
  components: {
    NcLoadingIcon,
    AlertCircle,
    CalendarIcon,
  },
  props: {
    widget: {
      type: Object,
      required: true,
    },
    editable: {
      type: Boolean,
      default: false,
    },
    rowBackgroundColor: {
      type: String,
      default: '',
    },
    shareToken: {
      type: String,
      default: '',
    },
    pageId: {
      type: String,
      default: '',
    },
  },
  data() {
    return {
      events: [],
      loading: true,
      error: null,
    };
  },
  computed: {
    hasCalendars() {
      return (this.widget.calendarIds && this.widget.calendarIds.length > 0)
        || (this.widget.externalIcsUrls && this.widget.externalIcsUrls.length > 0);
    },
    effectiveBackgroundColor() {
      return this.widget.backgroundColor || this.rowBackgroundColor || '';
    },
    hasDarkBackground() {
      return this.effectiveBackgroundColor === 'var(--color-primary-element)';
    },
    titleStyle() {
      const bgColor = this.effectiveBackgroundColor;
      const colorMappings = {
        'var(--color-primary-element)': 'var(--color-primary-element-text)',
        'var(--color-primary-element-light)': 'var(--color-primary-element-light-text)',
        'var(--color-background-dark)': 'var(--color-main-text)',
        'var(--color-background-hover)': 'var(--color-main-text)',
      };
      const textColor = colorMappings[bgColor];
      if (textColor) {
        return { color: textColor };
      }
      return {};
    },
  },
  watch: {
    widget: {
      deep: true,
      handler() {
        this.fetchEvents();
      },
    },
  },
  mounted() {
    this.fetchEvents();
  },
  methods: {
    t(key, vars = {}) {
      return t('intravox', key, vars);
    },
    async fetchEvents() {
      if (!this.hasCalendars) {
        this.loading = false;
        return;
      }

      this.loading = true;
      this.error = null;

      try {
        const { rangeStart, rangeEnd } = this.getDateRange();

        const params = new URLSearchParams();
        params.set('calendarIds', (this.widget.calendarIds || []).join(','));
        if (this.widget.externalIcsUrls && this.widget.externalIcsUrls.length > 0) {
          params.set('externalIcsUrls', JSON.stringify(this.widget.externalIcsUrls));
        }
        params.set('rangeStart', rangeStart);
        params.set('rangeEnd', rangeEnd);
        params.set('limit', String(this.widget.limit || 5));

        const url = this.shareToken
          ? generateUrl(`/apps/intravox/api/share/${this.shareToken}/calendar/events?${params}`)
          : generateUrl(`/apps/intravox/api/calendar/events?${params}`);

        const response = await axios.get(url);
        this.events = response.data.events || [];
      } catch (err) {
        console.error('[CalendarWidget] Failed to fetch events:', err);
        this.error = this.t('Failed to load events');
      } finally {
        this.loading = false;
      }
    },
    getDateRange() {
      const now = new Date();
      let rangeStart = now.toISOString();
      let rangeEnd;

      switch (this.widget.dateRange) {
        case 'this_week': {
          const startOfWeek = new Date(now);
          startOfWeek.setDate(now.getDate() - now.getDay() + 1);
          startOfWeek.setHours(0, 0, 0, 0);
          rangeStart = startOfWeek.toISOString();
          const endOfWeek = new Date(startOfWeek);
          endOfWeek.setDate(startOfWeek.getDate() + 7);
          rangeEnd = endOfWeek.toISOString();
          break;
        }
        case 'next_two_weeks': {
          const end = new Date(now);
          end.setDate(now.getDate() + 14);
          rangeEnd = end.toISOString();
          break;
        }
        case 'this_month': {
          const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);
          rangeStart = startOfMonth.toISOString();
          const endOfMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0, 23, 59, 59);
          rangeEnd = endOfMonth.toISOString();
          break;
        }
        case 'next_three_months': {
          const end = new Date(now);
          end.setMonth(now.getMonth() + 3);
          rangeEnd = end.toISOString();
          break;
        }
        case 'next_six_months': {
          const end = new Date(now);
          end.setMonth(now.getMonth() + 6);
          rangeEnd = end.toISOString();
          break;
        }
        case 'next_year': {
          const end = new Date(now);
          end.setFullYear(now.getFullYear() + 1);
          rangeEnd = end.toISOString();
          break;
        }
        case 'past_week': {
          const start = new Date(now);
          start.setDate(now.getDate() - 7);
          rangeStart = start.toISOString();
          rangeEnd = now.toISOString();
          break;
        }
        case 'past_month': {
          const start = new Date(now);
          start.setMonth(now.getMonth() - 1);
          rangeStart = start.toISOString();
          rangeEnd = now.toISOString();
          break;
        }
        case 'past_three_months': {
          const start = new Date(now);
          start.setMonth(now.getMonth() - 3);
          rangeStart = start.toISOString();
          rangeEnd = now.toISOString();
          break;
        }
        case 'upcoming':
        default: {
          const end = new Date(now);
          end.setDate(now.getDate() + 30);
          rangeEnd = end.toISOString();
          break;
        }
      }

      return { rangeStart, rangeEnd };
    },
    isPastRange() {
      return ['past_week', 'past_month', 'past_three_months'].includes(this.widget.dateRange);
    },
    getDayNumber(dateStr) {
      if (!dateStr) return '';
      return new Date(dateStr).getDate();
    },
    getMonthAbbr(dateStr) {
      if (!dateStr) return '';
      return new Date(dateStr).toLocaleDateString(undefined, { month: 'short' }).toUpperCase();
    },
    getProximityLabel(dateStr) {
      if (!dateStr) return null;
      const date = new Date(dateStr);
      const now = new Date();
      const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
      const eventDay = new Date(date.getFullYear(), date.getMonth(), date.getDate());
      const diffDays = Math.round((eventDay - today) / (1000 * 60 * 60 * 24));

      if (diffDays === 0) return this.t('Today');
      if (diffDays === 1) return this.t('Tomorrow');
      return null;
    },
    formatTime(dateStr) {
      if (!dateStr) return '';
      const date = new Date(dateStr);
      return date.toLocaleTimeString(undefined, {
        hour: '2-digit',
        minute: '2-digit',
      });
    },
    getEventUrl(event) {
      // External events: use their URL if available, otherwise no link
      if (event.isExternal) {
        return event.url || undefined;
      }
      // In public share mode, calendar app is not available
      if (this.shareToken) return undefined;
      if (!event.start) return undefined;
      const date = new Date(event.start);
      const dateStr = date.toISOString().split('T')[0];
      return generateUrl(`/apps/calendar/timeGridDay/${dateStr}`);
    },
    getContainerStyle() {
      const style = {};
      if (this.widget.backgroundColor) {
        style.backgroundColor = this.widget.backgroundColor;
        style.padding = '20px';
        style.borderRadius = 'var(--border-radius-large)';
      }
      return style;
    },
  },
};
</script>

<style scoped>
.calendar-widget {
  width: 100%;
  margin: 12px 0;
  container-type: inline-size;
}

.calendar-widget-title {
  margin: 0 0 16px 0;
  font-size: 18px;
  font-weight: 600;
  color: var(--color-main-text);
}

.calendar-loading,
.calendar-error,
.calendar-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 40px 20px;
  color: var(--color-text-maxcontrast);
  text-align: center;
}

.calendar-error {
  color: var(--color-error);
}

.calendar-empty {
  background: var(--color-background-hover);
  border: 2px dashed var(--color-border);
  border-radius: var(--border-radius-large);
}

.calendar-empty p {
  margin: 0;
  font-size: 14px;
}

.calendar-empty small {
  margin-top: 4px;
  font-size: 12px;
  opacity: 0.7;
}

.calendar-event-list {
  display: grid;
  grid-template-columns: 1fr;
  gap: 8px;
}

.calendar-event {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 8px;
  border-radius: var(--border-radius-large);
  transition: background 0.15s;
  text-decoration: none;
  color: inherit;
  cursor: pointer;
}

.calendar-event:hover {
  background: var(--color-background-hover);
}

.calendar-event:focus-visible {
  outline: 2px solid var(--color-primary-element);
  outline-offset: 2px;
}

/* Date badge - colored square with day number + month */
.event-date-badge {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  min-width: 48px;
  border-radius: 8px;
  color: #fff;
  flex-shrink: 0;
}

.event-date-day {
  font-size: 18px;
  font-weight: 700;
  line-height: 1.1;
}

.event-date-month {
  font-size: 11px;
  font-weight: 500;
  text-transform: uppercase;
  opacity: 0.85;
  line-height: 1.2;
}

/* Event details - right side */
.event-details {
  display: flex;
  flex-direction: column;
  gap: 1px;
  min-width: 0;
  padding-top: 2px;
}

.event-proximity {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.event-title {
  font-size: 14px;
  font-weight: 600;
  color: var(--color-main-text);
  line-height: 1.3;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.event-time {
  font-size: 12px;
  color: var(--color-text-maxcontrast);
}

.event-location {
  font-size: 12px;
  color: var(--color-text-maxcontrast);
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Dark background theme (Primary color) */
.has-dark-background .event-title {
  color: var(--color-primary-element-text);
}

.has-dark-background .event-time,
.has-dark-background .event-location {
  color: var(--color-primary-element-text);
  opacity: 0.75;
}

.has-dark-background .event-proximity {
  color: #fff !important;
  opacity: 0.9;
}

.has-dark-background .calendar-event:hover {
  background: rgba(255, 255, 255, 0.1);
}

.has-dark-background .calendar-loading,
.has-dark-background .calendar-empty {
  color: var(--color-primary-element-text);
}

.has-dark-background .calendar-empty {
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.3);
}

/* Light background adjustments */
.has-background:not(.has-dark-background) .calendar-empty {
  background: var(--color-main-background);
}

/* Wide mode - 2 columns */
@container (min-width: 500px) {
  .calendar-event-list {
    grid-template-columns: repeat(2, 1fr);
  }
}

/* Extra wide mode - 3 columns */
@container (min-width: 800px) {
  .calendar-event-list {
    grid-template-columns: repeat(3, 1fr);
  }
}

/* Compact mode for narrow containers (side columns ~200-300px) */
@container (max-width: 299px) {
  .event-date-badge {
    width: 40px;
    height: 40px;
    min-width: 40px;
    border-radius: 6px;
  }

  .event-date-day {
    font-size: 16px;
  }

  .event-date-month {
    font-size: 10px;
  }

  .calendar-event {
    gap: 8px;
    padding: 6px 4px;
  }

  .event-title {
    font-size: 13px;
  }

  .event-time {
    font-size: 11px;
  }

  .event-location {
    display: none;
  }

  .event-proximity {
    font-size: 10px;
  }
}
</style>
