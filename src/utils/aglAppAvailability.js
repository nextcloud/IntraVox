/** Availability is advisory; Nextcloud remains the authorization boundary. */
export function runtimeAppAvailable(appId) {
  return typeof window !== 'undefined' && Boolean(window.OC?.appswebroots?.[appId]);
}

export function appAvailability(appId, availability = {}) {
  if (!appId) return true;
  if (Object.prototype.hasOwnProperty.call(availability || {}, appId)) {
    if (availability[appId] === false) return false;
    if (availability[appId] === true) return true;
  }
  return runtimeAppAvailable(appId) ? true : null;
}

export function resolveLinkAvailability(item, availability = {}) {
  const primary = appAvailability(item?.appId, availability);
  if (primary === true || !item?.fallbackAppId) {
    return { available: primary, appId: item?.appId, url: item?.url };
  }
  const fallback = appAvailability(item.fallbackAppId, availability);
  if (fallback === true) {
    return { available: true, appId: item.fallbackAppId, url: item.fallbackUrl || item.url };
  }
  return { available: primary === false && fallback === false ? false : null, appId: item.fallbackAppId, url: item.fallbackUrl || item.url };
}

export function markUnavailableApps(items, availability, label = 'Availability unconfirmed') {
  return (items || []).map(item => {
    const state = resolveLinkAvailability(item, availability);
    const unavailable = !!item.appId && state.available !== true;
    return {
      ...item,
      ...(unavailable ? { availabilityHint: label, unavailable: true } : {}),
      ...(state.available === true && state.url !== item.url ? { url: state.url, appId: state.appId } : {}),
      ...(item.children ? { children: markUnavailableApps(item.children, availability, label) } : {}),
    };
  });
}
