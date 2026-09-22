<template>
  <div class="feed-layout-grid-wrapper">
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
      :feed-image="feedImage"
      :compact="true"
      @open-article="$emit('open-article', $event)"
      />
    </div>
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
  emits: ['open-article'],
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
    gridStyle() {
      const columns = Math.max(2, Math.min(Number(this.widget.columns) || 3, 4));
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
/*
 * The container is the WRAPPER, not the grid.
 *
 * container-type on .feed-layout-grid itself did nothing: a container query
 * matches against an ANCESTOR container, never the element carrying the
 * declaration. So @container below never fired, and a 433px-wide grid in a
 * narrow page column kept the three columns configured for a full-width row —
 * 134px per cell, of which an 80px thumbnail left 16px for the headline.
 */
.feed-layout-grid-wrapper {
  container-type: inline-size;
  min-width: 0;
}

.feed-layout-grid {
  display: grid;
  gap: 16px;
  min-width: 0;
  overflow: hidden;
}

@container (max-width: 500px) {
  .feed-layout-grid {
    grid-template-columns: 1fr !important;
  }
}

@container (min-width: 501px) and (max-width: 800px) {
  .feed-layout-grid {
    grid-template-columns: repeat(2, 1fr) !important;
  }
}

@media (max-width: 600px) {
  .feed-layout-grid {
    grid-template-columns: 1fr !important;
  }
}
</style>
