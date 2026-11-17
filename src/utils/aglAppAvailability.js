/** Display availability only: the host application still enforces authorization. */
export function markUnavailableApps(items, availability, label = 'Not enabled') {
  return (items || []).map(item => {
    const unavailable = !!item.appId && availability?.[item.appId] !== true;
    return {
      ...item,
      ...(unavailable ? { title: `${item.title} — ${label}`, unavailable: true } : {}),
      ...(item.children ? { children: markUnavailableApps(item.children, availability, label) } : {}),
    };
  });
}
