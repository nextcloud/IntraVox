<template>
  <div class="feed-layout-list">
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
      :feed-image="feedImage"
    />
  </div>
</template>

<script>
import FeedItem from './FeedItem.vue';
import { isDarkBackground as isDarkBg, isLightBackground as isLightBg } from '../../utils/colorUtils.js';

export default {
  name: 'FeedLayoutList',
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
    feedImage: {
      type: String,
      default: null,
    },
    rowBackgroundColor: {
      type: String,
      default: '',
    },
  },
  computed: {
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
.feed-layout-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  min-width: 0;
  overflow: hidden;
  container-type: inline-size;
}
</style>
