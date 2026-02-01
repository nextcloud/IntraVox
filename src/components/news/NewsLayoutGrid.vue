<template>
  <div class="news-layout-grid" :style="gridStyle">
    <NewsItem
      v-for="item in items"
      :key="item.uniqueId"
      :item="item"
      :show-image="widget.showImage !== false"
      :show-date="widget.showDate !== false"
      :show-excerpt="widget.showExcerpt !== false"
      :excerpt-length="widget.excerptLength || 80"
      :item-background="itemBackgroundMode"
      class="news-grid-item"
      @navigate="$emit('navigate', $event)"
    />
  </div>
</template>

<script>
import NewsItem from './NewsItem.vue';
import { isDarkBackground as isDarkBg, isLightBackground as isLightBg, getEffectiveBackgroundColor } from '../../utils/colorUtils.js';

export default {
  name: 'NewsLayoutGrid',
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
    gridStyle() {
      const columns = this.widget.columns || 3;
      return {
        gridTemplateColumns: `repeat(${columns}, 1fr)`,
      };
    },
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

      // Fallback to default
      return 'default';
    },
  },
};
</script>

<style scoped>
.news-layout-grid {
  display: grid;
  gap: 16px;
  grid-template-columns: repeat(3, 1fr);
}

.news-grid-item {
  flex-direction: column;
}

.news-grid-item :deep(.news-item-image) {
  width: 100%;
  height: 140px;
}

.news-grid-item :deep(.news-item-content) {
  padding: 0;
}

@media (max-width: 900px) {
  .news-layout-grid {
    grid-template-columns: repeat(2, 1fr) !important;
  }
}

@media (max-width: 600px) {
  .news-layout-grid {
    grid-template-columns: 1fr !important;
  }
}

/* Dark mode styling is now handled by NewsItem via item-background prop */
</style>
