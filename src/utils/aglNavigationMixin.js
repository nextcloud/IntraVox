import { loadState } from '@nextcloud/initial-state';
import { filterAglTree } from './aglBrandContext.js';
import { markUnavailableApps } from './aglAppAvailability.js';

/** Workspace visibility and safe application navigation, shared by menu layouts. */
export default {
  computed: {
    visibleItems() {
      return markUnavailableApps(filterAglTree(this.items,
        document.documentElement.dataset.aglContext || 'all',
        !this.isPublic && document.documentElement.dataset.aglExcom === 'true'),
      loadState('intravox', 'appAvailability', {}), this.t('intravox', 'Not enabled'));
    },
  },
  methods: {
    aglAttrs(item) {
      return { 'data-agl-entity': item.entity || 'all',
        'data-excom-only': item.excomOnly ? 'true' : 'false',
        'aria-disabled': item.unavailable ? 'true' : undefined,
        title: item.unavailable ? item.availabilityHint : undefined };
    },
    getItemUrl(item) {
      if (item.unavailable) return '#';
      return item.url || (item.uniqueId ? `#${item.uniqueId}` : '#');
    },
    handleItemClick(item) {
      if (item.unavailable) return;
      this.$emit('navigate', item);
      this.hideDropdown();
      this.hideMegaMenu();
    },
    handleMobileItemClick(item) {
      if (item.unavailable) return;
      if (item.uniqueId || item.url) this.$emit('navigate', item);
      else this.toggleMobileItem(this.getItemKey(item));
    },
  },
};
