<template>
  <div class="people-layout-card" :style="gridStyle">
    <PersonItem
      v-for="user in users"
      :key="user.uid"
      :user="user"
      layout="card"
      :show-fields="widget.showFields || {}"
      :item-background="itemBackgroundMode"
      class="people-card-item"
    />
  </div>
</template>

<script>
import PersonItem from './PersonItem.vue';
import { isDarkBackground as isDarkBg, isLightBackground as isLightBg, getEffectiveBackgroundColor } from '../../utils/colorUtils.js';

export default {
  name: 'PeopleLayoutCard',
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
      const columns = this.widget.columns || 3;
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
.people-layout-card {
  display: grid;
  gap: 16px;
  grid-template-columns: repeat(3, 1fr);
}

@container (max-width: 900px) {
  .people-layout-card {
    grid-template-columns: repeat(2, 1fr) !important;
  }
}

@container (max-width: 500px) {
  .people-layout-card {
    grid-template-columns: 1fr !important;
  }
}

/* Very narrow containers (side columns) - always single column */
@container (max-width: 300px) {
  .people-layout-card {
    grid-template-columns: 1fr !important;
    gap: 12px;
  }
}

/* Fallback for browsers without container query support */
@media (max-width: 900px) {
  .people-layout-card {
    grid-template-columns: repeat(2, 1fr) !important;
  }
}

@media (max-width: 600px) {
  .people-layout-card {
    grid-template-columns: 1fr !important;
  }
}
</style>
