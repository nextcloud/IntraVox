<template>
  <div class="news-layout-list">
    <NewsItem
      v-for="item in items"
      :key="item.uniqueId"
      :item="item"
      :show-image="widget.showImage !== false"
      :show-date="widget.showDate !== false"
      :show-excerpt="widget.showExcerpt !== false"
      :excerpt-length="widget.excerptLength || 100"
      :item-background="itemBackgroundMode"
      @navigate="$emit('navigate', $event)"
    />
  </div>
</template>

<script>
import NewsItem from './NewsItem.vue';

export default {
  name: 'NewsLayoutList',
  components: {
    NewsItem,
  },
  props: {
    items: {
      type: Array,
      required: true,
    },
    widget: {
      type: Object,
      required: true,
    },
    rowBackgroundColor: {
      type: String,
      default: '',
    },
  },
  emits: ['navigate'],
  computed: {
    effectiveBackgroundColor() {
      // Widget's own backgroundColor takes precedence over row background
      return this.widget.backgroundColor || this.rowBackgroundColor || '';
    },
    isDarkBackground() {
      const darkBackgrounds = [
        'var(--color-primary-element)',
        'var(--color-error)',
        'var(--color-success)',
      ];
      return darkBackgrounds.includes(this.effectiveBackgroundColor);
    },
    itemBackgroundMode() {
      const bgColor = this.widget.backgroundColor;

      // No widget background color set -> transparent items (inherit parent)
      if (!bgColor) {
        return 'transparent';
      }

      // Light container -> white items
      if (bgColor === 'var(--color-background-hover)') {
        return 'white';
      }

      // Dark container (Primary, etc.) -> dark mode items
      if (this.isDarkBackground) {
        return 'dark';
      }

      // Fallback to default
      return 'default';
    },
  },
};
</script>

<style scoped>
.news-layout-list {
  container-type: inline-size;
  container-name: news-list;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

/* Compact card layout voor smalle containers (side columns) */
@container news-list (max-width: 280px) {
  .news-layout-list :deep(.news-item) {
    flex-direction: column;
    padding: 0;
    overflow: hidden;
  }

  .news-layout-list :deep(.news-item-image) {
    width: 100%;
    height: 120px;
    border-radius: var(--border-radius-large) var(--border-radius-large) 0 0;
  }

  .news-layout-list :deep(.news-item-content) {
    padding: 12px;
  }

  .news-layout-list :deep(.news-item-title) {
    font-size: 14px;
  }

  .news-layout-list :deep(.news-item-excerpt) {
    display: none; /* Verberg excerpt in compact mode */
  }
}

/* Dark mode styling is now handled by NewsItem via item-background prop */
</style>
