<template>
  <div class="people-layout-grid" :style="gridStyle">
    <PersonItem
      v-for="user in users"
      :key="user.uid"
      :user="user"
      layout="grid"
      :show-fields="widget.showFields || {}"
      :item-background="itemBackgroundMode"
      class="people-grid-item"
    />
  </div>
</template>

<script>
import PersonItem from './PersonItem.vue';
import { isDarkBackground as isDarkBg, isLightBackground as isLightBg, getEffectiveBackgroundColor } from '../../utils/colorUtils.js';

export default {
  name: 'PeopleLayoutGrid',
  components: {
    PersonItem,
  },
  props: {
    users: {
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
      const columns = this.widget.columns || 4;
      return {
        gridTemplateColumns: `repeat(${columns}, 1fr)`,
      };
    },
    effectiveBackgroundColor() {
      return getEffectiveBackgroundColor(this.widget.backgroundColor, this.rowBackgroundColor);
    },
    itemBackgroundMode() {
      const containerBg = this.effectiveBackgroundColor;

      if (!containerBg) {
        return 'default';
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
.people-layout-grid {
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(4, 1fr);
  align-items: start; /* Align items to top to prevent uneven row heights */
}

@container (max-width: 800px) {
  .people-layout-grid {
    grid-template-columns: repeat(3, 1fr) !important;
  }
}

@container (max-width: 500px) {
  .people-layout-grid {
    grid-template-columns: repeat(2, 1fr) !important;
  }
}

/* Fallback for browsers without container query support */
@media (max-width: 900px) {
  .people-layout-grid {
    grid-template-columns: repeat(3, 1fr) !important;
  }
}

@media (max-width: 600px) {
  .people-layout-grid {
    grid-template-columns: repeat(2, 1fr) !important;
  }
}
</style>
