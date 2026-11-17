<script>
import { findNativeSearchTrigger, openNativeSearch } from '../utils/aglGlobalSearch.js';

export default {
  name: 'AglGlobalSearchShortcut',
  mounted() {
    document.addEventListener('keydown', this.onKeydown);
  },
  beforeUnmount() {
    document.removeEventListener('keydown', this.onKeydown);
  },
  methods: {
    onKeydown(event) {
      if (!(event.metaKey || event.ctrlKey) || event.key.toLowerCase() !== 'k' || event.altKey) return;
      const target = event.target;
      if (target?.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(target?.tagName)) return;
      const trigger = findNativeSearchTrigger();
      if (!trigger) return;
      event.preventDefault();
      this.openSearch();
    },
    openSearch() {
      return openNativeSearch();
    },
  },
  render() {
    return null;
  },
};
</script>
