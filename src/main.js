import { createApp, defineAsyncComponent, h } from 'vue';
import { translate } from '@nextcloud/l10n';
import { parseFragment } from './utils/headingAnchors.js';
import './css/agl-sovereign-theme.scss';

// Get the root element
const rootElement = document.getElementById('app-intravox');

// Check if this is a public page view (token-based sharing of internal pages)
// Can be indicated by:
// 1. data-is-public="true" attribute on root element (set by PHP)
// 2. URL path /s/{token} (share access route)
// 3. ?share= query parameter (legacy, may not work due to NC routing)
const urlParams = new URLSearchParams(window.location.search);
const pathMatch = window.location.pathname.match(/\/apps\/intravox\/s\/([a-zA-Z0-9]+)/);
const shareToken = rootElement?.dataset?.token || (pathMatch ? pathMatch[1] : null) || urlParams.get('share') || '';
const isPublic = rootElement?.dataset?.isPublic === 'true' || (shareToken && !document.querySelector('#user-menu'));

let rootComponent;
let rootProps = {};

if (isPublic && shareToken) {
	// Public page mode - render simplified view (token-based sharing)
	// Priority: ?page= query param (survives chat apps/email) > #hash (legacy)
	const pageFromQuery = urlParams.get('page') || '';
	// Een sectielink draagt de pagina bij zich: `#<pageId>#h-<slug>`. Zonder
	// splitsen werd die hele string als pagina-id doorgegeven en meldde de
	// share "pagina niet beschikbaar".
	const pageFromHash = parseFragment(window.location.hash).pageId || '';
	const pageUniqueId = pageFromQuery || pageFromHash;

	rootProps = { token: shareToken, pageUniqueId };
	rootComponent = defineAsyncComponent({
		loader: () => import('./components/PublicPageView.vue'),
		delay: 0,
		loadingComponent: {
			render() {
				return h('div', { class: 'agl-entry-state', role: 'status' }, translate('intravox', 'Loading shared page …'));
			},
		},
		errorComponent: {
			render() {
				return h('div', { class: 'agl-entry-state', role: 'alert' }, [
					h('p', translate('intravox', 'The shared page could not be loaded.')),
					h('button', { type: 'button', onClick: () => window.location.reload() }, translate('intravox', 'Reload page')),
				]);
			},
		},
		onError(error, retry, fail, attempts) {
			if (attempts < 2) retry();
			else fail(error);
		},
	});
} else {
	rootComponent = defineAsyncComponent({
		loader: () => import('./App.vue'),
		delay: 0,
		loadingComponent: {
			render() {
				return h('div', { class: 'agl-entry-state', role: 'status' }, translate('intravox', 'Loading Sovereign 365 …'));
			},
		},
		errorComponent: {
			render() {
				return h('div', { class: 'agl-entry-state', role: 'alert' }, [
					h('p', translate('intravox', 'Sovereign 365 could not be loaded.')),
					h('button', { type: 'button', onClick: () => window.location.reload() }, translate('intravox', 'Reload page')),
				]);
			},
		},
		onError(error, retry, fail, attempts) {
			if (attempts < 2) retry();
			else fail(error);
		},
	});
}

// No global $t/$n aliases: such a call works at runtime but is invisible to
// the Transifex bot, which only extracts t() with the app id as first argument.
// Components define their own t()/n() helpers; check-l10n-sync.js enforces it.

createApp(rootComponent, rootProps).mount('#app-intravox');
