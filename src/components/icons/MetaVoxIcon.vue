<template>
  <span class="material-design-icon metavox-icon">
    <img :src="iconUrl" :width="size" :height="size" alt="MetaVox" />
  </span>
</template>

<script>
import { imagePath } from '@nextcloud/router'

export default {
  name: 'MetaVoxIcon',
  props: {
    size: {
      type: Number,
      default: 24
    }
  },
  computed: {
    iconUrl() {
      return imagePath('metavox', 'app.svg')
    }
  }
}
</script>

<style scoped>
.metavox-icon {
  display: inline-flex;
  align-self: center;
}
</style>
