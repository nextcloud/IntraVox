export const AGL_CONTEXT_KEY = 'agl.intravox.brandContext';
export const AGL_EXCOM_UIDS = Object.freeze([
  'scott.mackenzie',
  'victor.kovacs',
  'alfredo.carrasco',
  'stelios.xeroudakis',
]);
export const AGL_ENTITIES = Object.freeze(['all', 'cloud-carib', 'athena-cloud', 'excom']);

export function isExcomEligible(identity) {
  if (!identity || typeof identity.uid !== 'string' || !identity.uid.trim() || !Array.isArray(identity.groups)) {
    return false;
  }
  return identity.groups.includes('ExCom')
    || identity.groups.includes('admin')
    || AGL_EXCOM_UIDS.includes(identity.uid);
}

export function filterAglTree(items, context = 'all', eligible = false) {
  if (!Array.isArray(items)) return [];
  return items.reduce((visible, item) => {
    if (!item || typeof item !== 'object') return visible;
    const entity = item.entity == null ? 'all' : item.entity;
    if (!AGL_ENTITIES.includes(entity)) return visible;
    if ((item.excomOnly === true || entity === 'excom') && !eligible) return visible;
    if (context !== 'all' && entity !== 'all' && entity !== context && !(context === 'excom' && item.excomOnly === true)) return visible;
    const children = filterAglTree(item.children, context, eligible);
    const hasLink = !!(item.uniqueId || item.url);
    if (!hasLink && children.length === 0) return visible;
    visible.push({ ...item, children });
    return visible;
  }, []);
}

export function isVisibleInAglContext(item, context = 'all', eligible = false) {
  if (!item || typeof item !== 'object') return false;
  const entity = item.entity == null ? 'all' : item.entity;
  if (!AGL_ENTITIES.includes(entity)) return false;
  if ((item.excomOnly === true || entity === 'excom') && !eligible) return false;
  return context === 'all' || entity === 'all' || entity === context || (context === 'excom' && item.excomOnly === true);
}
