<template>
  <div class="agl-metadata-fields">
    <label>{{ t('intravox', 'Entity') }}
      <select :value="entity || 'all'" :aria-label="t('intravox', 'Entity')" @change="$emit('update:entity', $event.target.value)">
        <option value="all">{{ t('intravox', 'All / shared') }}</option>
        <option value="cloud-carib">{{ t('intravox', 'Cloud Carib') }}</option>
        <option value="athena-cloud">{{ t('intravox', 'Athena Cloud') }}</option>
        <option value="excom">{{ t('intravox', 'ExCom') }}</option>
      </select>
    </label>
    <label><input type="checkbox" :checked="excomOnly === true" @change="$emit('update:excomOnly', $event.target.checked)" />{{ t('intravox', 'ExCom only') }}</label>
  </div>
</template>

<script>
import { translate } from '@nextcloud/l10n';

export default {
  name: 'AglMetadataFields',
  props: {
    entity: { type: String, default: 'all' },
    excomOnly: { type: Boolean, default: false },
  },
  emits: ['update:entity', 'update:excomOnly'],
  methods: {
    t(app, text, vars) { return translate(app, text, vars); },
  },
};
</script>

<style scoped>
.agl-metadata-fields { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; color: var(--color-text-maxcontrast); font-size: 12px; }
.agl-metadata-fields label { display: inline-flex; align-items: center; gap: 6px; }
</style>
