<template>
  <section id="organogram-matrix" class="agl-organogram" :aria-label="title" tabindex="-1">
    <header class="agl-organogram__heading">
      <div>
        <p class="agl-organogram__eyebrow">{{ t('intravox', 'ATHENA GROUP · LEADERSHIP & DELIVERY') }}</p>
        <h2>{{ title }}</h2>
      </div>
      <button class="agl-organogram__toggle" type="button"
              :aria-expanded="expanded ? 'true' : 'false'"
              @click="expanded = !expanded">
        {{ expanded ? t('intravox', 'Collapse matrix') : t('intravox', 'Expand matrix') }}
      </button>
    </header>
    <div v-show="expanded">
      <div v-if="showCompanies" class="agl-organogram__companies" :aria-label="t('intravox', 'Mirrored ExCom governance')">
        <strong class="agl-organogram__companies-label">{{ t('intravox', 'MIRRORED EXCOM GOVERNANCE:') }}</strong>
        <span v-for="company in organogram.companies" :key="company" class="agl-organogram__company">{{ company }}</span>
      </div>
      <div class="agl-organogram__executives" role="group" :aria-label="t('intravox', 'Executive Committee')">
        <button v-for="person in organogram.executives" :key="person.name" class="agl-organogram__executive" type="button" :aria-pressed="selectedExecutive === person.name ? 'true' : 'false'" @click="selectExecutive(person)">
          <strong>{{ person.name }}</strong><span>{{ person.role }}</span>
        </button>
      </div>
      <p v-if="selectedExecutive" class="agl-organogram__selection" aria-live="polite">{{ t('intravox', 'Selected: {name} — {role}', { name: selectedExecutive, role: selectedRole }) }}</p>
      <div class="agl-organogram__columns">
        <article v-for="column in organogram.columns" :key="column.title" class="agl-organogram__column">
          <h3><a class="agl-organogram__column-link" :href="'#' + column.pageId" @click="navigate($event, column.pageId)">{{ column.title }}</a></h3>
          <p v-if="column.subtitle" class="agl-organogram__subtitle">{{ column.subtitle }}</p>
          <component :is="group.pageId ? 'a' : 'section'" v-for="group in column.groups" :key="group.title"
                     class="agl-organogram__group" :class="{ 'agl-organogram__hub-link': !!group.pageId }"
                     :href="group.pageId ? '#' + group.pageId : undefined"
                     @click="group.pageId && navigate($event, group.pageId)">
            <strong :class="{ 'agl-organogram__group-link': !!group.pageId }">{{ group.title }}</strong>
            <ul v-if="group.children"><li v-for="child in group.children" :key="child">{{ child }}</li></ul>
          </component>
        </article>
      </div>
    </div>
  </section>
</template>

<script>
import { translate } from '@nextcloud/l10n';
import { AGL_ORGANOGRAM } from '../data/aglOrganogram.js';

export default {
  name: 'OrganogramWidget',
  props: { widget: { type: Object, required: true } },
  emits: ['navigate'],
  data() {
    return { expanded: this.widget.defaultExpanded !== false, selectedExecutive: null, selectedRole: '' };
  },
  computed: {
    organogram() { return AGL_ORGANOGRAM; },
    title() { return this.widget.title || AGL_ORGANOGRAM.title; },
    showCompanies() { return this.widget.showCompanies !== false; },
  },
  watch: {
    'widget.defaultExpanded'(value) { this.expanded = value !== false; },
  },
  methods: {
    selectExecutive(person) {
      this.selectedExecutive = this.selectedExecutive === person.name ? null : person.name;
      this.selectedRole = this.selectedExecutive ? person.role : '';
    },
    navigate(event, pageId) {
      if (!pageId || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
      event.preventDefault();
      this.$emit('navigate', pageId);
    },
    t(app, text, vars) { return translate(app, text, vars); },
  },
};
</script>
