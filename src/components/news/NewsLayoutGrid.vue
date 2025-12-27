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
      class="news-grid-item"
      @navigate="$emit('navigate', $event)"
    />
  </div>
</template>

<script>
import NewsItem from './NewsItem.vue';

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
  },
  emits: ['navigate'],
  computed: {
    gridStyle() {
      const columns = this.widget.columns || 3;
      return {
        gridTemplateColumns: `repeat(${columns}, 1fr)`,
      };
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
</style>
