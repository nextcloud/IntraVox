<template>
  <section class="agl-context-bar" aria-label="Sovereign 365 workspace context">
    <div class="agl-context-bar__workspace">
      <span class="agl-context-bar__brand">Sovereign 365 <small>Athena Group</small></span>
      <span class="agl-context-bar__eyebrow">{{ t('intravox', 'WORKSPACE LENS') }}</span>
      <div class="agl-context-bar__lenses" role="group" :aria-label="t('intravox', 'Workspace lens')">
        <button v-for="lens in visibleLenses" :key="lens.id"
                class="agl-context-bar__lens" type="button"
                :aria-pressed="context === lens.id ? 'true' : 'false'"
                @click="selectContext(lens.id)">{{ lens.label }}</button>
      </div>
    </div>
    <nav class="agl-context-bar__launches" :aria-label="t('intravox', 'Cloud locations')">
      <a class="agl-context-bar__launch" href="#page-a1000002-0002-4000-8000-000000000021">
        <span class="agl-context-bar__pulse" aria-hidden="true"></span>{{ t('intravox', 'Nassau C3') }}
      </a>
      <a class="agl-context-bar__launch" href="#page-a1000002-0002-4000-8000-000000000022">
        <span class="agl-context-bar__pulse" aria-hidden="true"></span>{{ t('intravox', 'Athens C3') }}
      </a>
    </nav>
  </section>
</template>

<script>
import axios from '@nextcloud/axios';
import { generateOcsUrl } from '@nextcloud/router';
import { loadState } from '@nextcloud/initial-state';
import { translate } from '@nextcloud/l10n';
import { AGL_CONTEXT_KEY, AGL_ENTITIES, isExcomEligible } from '../utils/aglBrandContext.js';

export default {
  name: 'AglBrandContextBar',
  emits: ['context-change', 'identity-change'],
  data() {
    return { context: 'all', storedContext: 'all', identity: null, identityResolved: false };
  },
  computed: {
    eligible() {
      return this.identityResolved && isExcomEligible(this.identity);
    },
    visibleLenses() {
      const lenses = [
        { id: 'all', label: this.t('intravox', 'All Authorized') },
        { id: 'cloud-carib', label: this.t('intravox', 'Cloud Carib') },
        { id: 'athena-cloud', label: this.t('intravox', 'Athena Cloud') },
      ];
      if (this.eligible) lenses.push({ id: 'excom', label: this.t('intravox', 'ExCom HoldCo') });
      return lenses;
    },
  },
  async mounted() {
    this.restoreContext();
    this.applyDocumentState();
    await this.resolveIdentity();
    this.context = this.storedContext === 'excom' && !this.eligible ? 'all' : this.storedContext;
    this.$emit('context-change', this.context);
    this.applyDocumentState();
  },
  beforeUnmount() {
    delete document.documentElement.dataset.aglContext;
    delete document.documentElement.dataset.aglExcom;
  },
  methods: {
    t(app, text, vars) {
      return translate(app, text, vars);
    },
    restoreContext() {
      try {
        const stored = window.localStorage.getItem(AGL_CONTEXT_KEY);
        this.storedContext = AGL_ENTITIES.includes(stored) ? stored : 'all';
      } catch {
        this.storedContext = 'all';
      }
    },
    async resolveIdentity() {
      let identity = null;
      try {
        const initial = loadState('intravox', 'aglIdentity', null);
        if (initial && typeof initial.uid === 'string' && Array.isArray(initial.groups)) {
          identity = initial;
        } else if (initial !== null) {
          identity = null;
        } else {
          const response = await axios.get(generateOcsUrl('/cloud/user', {}, { ocsVersion: 2 }), {
            headers: { 'OCS-APIRequest': 'true' },
          });
          const user = response.data?.ocs?.data;
          if (user && typeof user.id === 'string') {
            identity = { uid: user.id, groups: Array.isArray(user.groups) ? user.groups : [] };
          }
        }
      } catch {
        identity = null;
      }
      this.identity = identity;
      this.identityResolved = true;
      this.$emit('identity-change', { eligible: this.eligible });
    },
    selectContext(context) {
      if (!AGL_ENTITIES.includes(context) || (context === 'excom' && !this.eligible)) return;
      this.context = context;
      try {
        window.localStorage.setItem(AGL_CONTEXT_KEY, context);
      } catch {
        // Storage is optional; this session remains usable without persistence.
      }
      this.applyDocumentState();
      this.$emit('context-change', context);
    },
    applyDocumentState() {
      document.documentElement.dataset.aglContext = this.context;
      document.documentElement.dataset.aglExcom = this.eligible ? 'true' : 'false';
      document.dispatchEvent(new CustomEvent('agl:context-change', { detail: { context: this.context, eligible: this.eligible } }));
    },
  },
};
</script>
