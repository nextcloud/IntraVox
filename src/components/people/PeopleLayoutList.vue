<template>
  <div class="people-layout-list">
    <PersonItem
      v-for="user in users"
      :key="user.uid"
      :user="user"
      layout="list"
      :show-fields="widget.showFields || {}"
      :item-background="itemBackgroundMode"
      class="people-list-item"
    />
  </div>
</template>

<script>
import PersonItem from './PersonItem.vue';
import { isDarkBackground as isDarkBg, isLightBackground as isLightBg, getEffectiveBackgroundColor } from '../../utils/colorUtils.js';

export default {
  name: 'PeopleLayoutList',
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
    effectiveBackgroundColor() {
      return getEffectiveBackgroundColor(this.widget.backgroundColor, this.rowBackgroundColor);
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

      return 'transparent';
    },
  },
};
</script>

<style scoped>
.people-layout-list {
  display: flex;
  flex-direction: column;
  border-radius: var(--border-radius);
  overflow: hidden;
}
</style>
