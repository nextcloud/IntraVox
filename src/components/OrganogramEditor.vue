<template>
  <section class="agl-organogram-editor">
    <label class="form-group">
      <span>{{ t('intravox', 'Title:') }}</span>
      <input :value="widget.title || defaultTitle" class="widget-input" type="text" @input="emit('title', $event.target.value)" />
    </label>
    <label class="checkbox-label"><input type="checkbox" :checked="widget.defaultExpanded !== false" @change="emit('defaultExpanded', $event.target.checked)" />{{ t('intravox', 'Expanded by default') }}</label>
    <label class="checkbox-label"><input type="checkbox" :checked="widget.showCompanies !== false" @change="emit('showCompanies', $event.target.checked)" />{{ t('intravox', 'Show companies') }}</label>
    <OrganogramWidget :widget="{ ...widget, title: widget.title || defaultTitle }" />
  </section>
</template>

<script>
import { translate } from '@nextcloud/l10n';
import { defineAsyncComponent } from 'vue';

export default {
  name: 'OrganogramEditor',
  components: { OrganogramWidget: defineAsyncComponent(() => import('./OrganogramWidget.vue')) },
  props: { widget: { type: Object, required: true } },
  emits: ['update'],
  data() { return { defaultTitle: 'Athena Group | Executive & Functional Matrix' }; },
  methods: {
    t(app, text, vars) { return translate(app, text, vars); },
    emit(key, value) { this.$emit('update', { [key]: value }); },
  },
};
</script>
