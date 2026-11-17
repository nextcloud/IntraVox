<template>
  <div class="agl-page-tools" role="group" :aria-label="t('intravox', 'Page tools')">
    <button type="button" :aria-expanded="structureOpen" :title="t('intravox', 'Page structure')"
            :aria-label="t('intravox', 'Page structure')" @click="$emit('toggle-structure')">
      <FileTree :size="18" />
    </button>
    <button type="button" :aria-expanded="detailsOpen" :title="t('intravox', 'Details')"
            :aria-label="t('intravox', 'Details')" @click="$emit('toggle-details')">
      <InformationOutline :size="18" />
    </button>
  </div>
</template>
<script>
import { translate } from '@nextcloud/l10n';
import FileTree from 'vue-material-design-icons/FileTree.vue';
import InformationOutline from 'vue-material-design-icons/InformationOutline.vue';
export default {
  name: 'AglPageTools',
  components: { FileTree, InformationOutline },
  props: { structureOpen: Boolean, detailsOpen: Boolean },
  emits: ['toggle-structure', 'toggle-details'],
  methods: { t(app, text) { return translate(app, text); } },
};
</script>
<style scoped>
.agl-page-tools { display: inline-flex; gap: 2px; }
button { display: grid; place-items: center; width: 34px; height: 34px; padding: 0; border: 1px solid var(--color-border); border-radius: 999px; background: var(--color-main-background); color: var(--color-main-text); cursor: pointer; }
button:hover, button[aria-expanded="true"] { background: var(--color-background-hover); }
button:focus-visible { outline: 3px solid #c4a0f2; outline-offset: 2px; }
</style>
