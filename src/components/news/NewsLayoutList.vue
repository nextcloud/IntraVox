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
import { isDarkBackground as isDarkBg, isLightBackground as isLightBg, getEffectiveBackgroundColor } from '../../utils/colorUtils.js';

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
      return getEffectiveBackgroundColor(this.widget.backgroundColor, this.rowBackgroundColor);
    },
    isDarkBackground() {
      return isDarkBg(this.effectiveBackgroundColor);
    },
    itemBackgroundMode() {
      const containerBg = this.effectiveBackgroundColor;

      if (!containerBg) {
        return 'transparent';
      }

      if (isDarkBg(containerBg)) {
        return 'dark';
      }

      if (isLightBg(containerBg)) {
        return 'white';
      }

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
