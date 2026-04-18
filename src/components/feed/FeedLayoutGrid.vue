<template>
  <div class="feed-layout-grid" :style="gridStyle">
    <FeedItem
      v-for="item in items"
      :key="item.id"
      :item="item"
      :show-image="widget.showImage !== false"
      :show-date="widget.showDate !== false"
      :show-excerpt="widget.showExcerpt !== false"
      :show-source="widget.showSource || false"
      :excerpt-length="widget.excerptLength || 150"
      :open-in-new-tab="widget.openInNewTab !== false"
      :item-background="itemBackgroundMode"
      :compact="true"
    />
  </div>
</template>

<script>
import FeedItem from './FeedItem.vue';
import { isDarkBackground as isDarkBg, isLightBackground as isLightBg } from '../../utils/colorUtils.js';

export default {
  name: 'FeedLayoutGrid',
  components: {
    FeedItem,
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
  computed: {
    gridStyle() {
      const columns = this.widget.columns || 3;
      return {
        gridTemplateColumns: `repeat(${columns}, 1fr)`,
      };
    },
    effectiveBackgroundColor() {
      return this.widget.backgroundColor || this.rowBackgroundColor || '';
    },
    itemBackgroundMode() {
      const containerBg = this.effectiveBackgroundColor;
      if (!containerBg) return 'default';
      if (isDarkBg(containerBg)) return 'dark';
      if (isLightBg(containerBg)) return 'white';
      return 'default';
    },
  },
};
</script>

<style scoped>
.feed-layout-grid {
  display: grid;
  gap: 8px;
  min-width: 0;
  overflow: hidden;
}

@media (max-width: 600px) {
  .feed-layout-grid {
    grid-template-columns: 1fr !important;
  }
}
</style>
