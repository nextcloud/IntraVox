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
  },
  emits: ['navigate'],
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
</style>
