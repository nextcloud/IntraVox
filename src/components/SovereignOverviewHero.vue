<template>
  <section class="sovereign-hero" aria-label="Sovereign 365 executive workspace">
    <div class="sovereign-hero__copy">
      <slot />
    </div>
    <div class="sovereign-hero__tools">
      <div class="sovereign-hero__clocks" aria-label="Dual-C3 local time">
        <div class="sovereign-hero__clock">
          <span class="sovereign-hero__location">{{ t('intravox', 'Nassau C3') }} <b>[{{ nassauZone }}]</b></span>
          <time :datetime="now.toISOString()">{{ nassauTime }}</time>
        </div>
        <span class="sovereign-hero__divider" aria-hidden="true"></span>
        <div class="sovereign-hero__clock">
          <span class="sovereign-hero__location">{{ t('intravox', 'Athens C3') }} <b>[{{ athensZone }}]</b></span>
          <time :datetime="now.toISOString()">{{ athensTime }}</time>
        </div>
      </div>
      <button class="sovereign-hero__search" type="button" @click="openSearch">
        <Magnify :size="19" aria-hidden="true" />
        <span>{{ t('intravox', 'Search Vaults, SOPs, Policies & Messages...') }}</span>
        <kbd>⌘K / Ctrl+K</kbd>
      </button>
    </div>
  </section>
</template>

<script>
import { translate } from '@nextcloud/l10n';
import { showError } from '@nextcloud/dialogs';
import Magnify from 'vue-material-design-icons/Magnify.vue';
import { openNativeSearch } from '../utils/aglGlobalSearch.js';

export default {
  name: 'SovereignOverviewHero',
  components: { Magnify },
  data() {
    return { now: new Date(), clockTimer: null };
  },
  computed: {
    nassauParts() { return this.clockParts('America/Nassau'); },
    athensParts() { return this.clockParts('Europe/Athens'); },
    nassauTime() { return this.nassauParts.time; },
    athensTime() { return this.athensParts.time; },
    nassauZone() { return this.nassauParts.zone; },
    athensZone() { return this.athensParts.zone; },
  },
  mounted() {
    this.clockTimer = window.setInterval(() => { this.now = new Date(); }, 30000);
  },
  beforeUnmount() {
    if (this.clockTimer !== null) window.clearInterval(this.clockTimer);
  },
  methods: {
    t(app, text, vars) { return translate(app, text, vars); },
    clockParts(timeZone) {
      const parts = new Intl.DateTimeFormat('en-US', {
        timeZone, hour: '2-digit', minute: '2-digit', hourCycle: 'h23', timeZoneName: 'long',
      }).formatToParts(this.now);
      const longZone = parts.find(part => part.type === 'timeZoneName')?.value || '';
      const zone = timeZone === 'America/Nassau'
        ? (longZone.includes('Daylight') ? 'EDT' : 'EST')
        : (longZone.includes('Summer') || longZone.includes('Daylight') ? 'EEST' : 'EET');
      return {
        time: `${parts.find(part => part.type === 'hour')?.value || ''}:${parts.find(part => part.type === 'minute')?.value || ''}`,
        zone,
      };
    },
    openSearch() {
      if (!openNativeSearch()) showError(this.t('intravox', 'Global search is unavailable in this session.'));
    },
  },
};
</script>

<style scoped>
.sovereign-hero {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(300px, .92fr);
  align-items: center;
  gap: clamp(20px, 4vw, 54px);
  padding: clamp(20px, 3vw, 34px);
  margin: 0 0 18px;
  border: 1px solid var(--agl-card-border, var(--color-border));
  border-radius: 16px;
  background: var(--agl-surface, var(--color-main-background));
  box-shadow: 0 8px 24px rgb(39 25 47 / 6%);
}
.sovereign-hero__copy { min-width: 0; }
.sovereign-hero__tools { display: grid; gap: 13px; min-width: 0; }
.sovereign-hero__clocks {
  display: flex;
  align-items: stretch;
  justify-content: space-between;
  gap: 14px;
  padding: 11px 14px;
  border: 1px solid var(--agl-card-border, var(--color-border));
  border-radius: 10px;
  background: var(--agl-column, var(--color-background-hover));
}
.sovereign-hero__clock { display: grid; gap: 4px; }
.sovereign-hero__location {
  color: var(--color-text-maxcontrast);
  font-size: 10px;
  font-weight: 750;
  letter-spacing: .075em;
  text-transform: uppercase;
}
.sovereign-hero__location b { color: var(--agl-accent, var(--color-primary-element)); }
.sovereign-hero__clock time {
  color: var(--color-main-text);
  font: 600 22px/1.1 var(--font-face, sans-serif);
  font-variant-numeric: tabular-nums;
}
.sovereign-hero__divider { width: 1px; background: var(--color-border); }
.sovereign-hero__search {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  min-height: 48px;
  padding: 9px 13px;
  border: 1px solid var(--agl-card-border, var(--color-border));
  border-radius: 10px;
  background: var(--color-main-background);
  color: var(--color-main-text);
  font: inherit;
  text-align: left;
  cursor: pointer;
  transition: transform .16s ease, border-color .16s ease;
}
.sovereign-hero__search:hover { transform: translateY(-1px); border-color: var(--agl-accent, var(--color-primary-element)); }
.sovereign-hero__search svg { flex: 0 0 auto; color: var(--agl-accent, var(--color-primary-element)); }
.sovereign-hero__search span { flex: 1; min-width: 0; font-size: 13px; }
.sovereign-hero__search kbd {
  flex: 0 0 auto;
  padding: 3px 6px;
  border: 1px solid var(--color-border);
  border-radius: 5px;
  color: var(--color-text-maxcontrast);
  font: 600 10px/1.2 var(--font-face, sans-serif);
  white-space: nowrap;
}
@media (max-width: 760px) {
  .sovereign-hero { grid-template-columns: 1fr; gap: 18px; }
  .sovereign-hero__search { flex-wrap: wrap; }
}
</style>
