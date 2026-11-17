const visible = element => element instanceof HTMLElement
  && element.getBoundingClientRect().height > 0
  && getComputedStyle(element).visibility !== 'hidden';

export function findNativeSearchTrigger() {
  // Nextcloud versions wrap the trigger differently. Never click the container
  // or a hidden reset/filter button inside the search panel.
  return [...document.querySelectorAll('button#unified-search, .unified-search-menu button, #unified-search .header-menu__trigger, #unified-search button, #unified-search[role="button"], button.unified-search__trigger, button[aria-label*="Search" i], [role="button"][aria-label*="Search" i]')]
    .find(element => visible(element) && !element.disabled);
}

export function openNativeSearch() {
  const input = [...document.querySelectorAll('.unified-search-modal input, .unified-search__input-wrapper input, .unified-search input')]
    .find(visible);
  if (input) {
    input.focus();
    return true;
  }
  const nativeSearch = window.OCP?.UnifiedSearch;
  try {
    if (typeof nativeSearch?.open === 'function') {
      nativeSearch.open();
      return true;
    }
    if (typeof nativeSearch?.show === 'function') {
      nativeSearch.show();
      return true;
    }
  } catch {
    // A missing or incompatible host API falls through to the DOM trigger.
  }
  const trigger = findNativeSearchTrigger();
  if (!trigger) return false;
  trigger.click();
  // Native search owns its autofocus and focus trap.
  return true;
}
