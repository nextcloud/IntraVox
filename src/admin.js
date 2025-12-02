/**
 * IntraVox Admin Settings Entry Point
 */
import { createApp } from 'vue'
import AdminSettings from './components/AdminSettings.vue'
import { loadState } from '@nextcloud/initial-state'
import { translate, translatePlural } from '@nextcloud/l10n'

// Make translation functions available globally
window.t = translate
window.n = translatePlural

// Load initial state from PHP
const initialState = loadState('intravox', 'admin-settings', {
    languages: [],
    imported: false,
})

// Create and mount the Vue app
const app = createApp(AdminSettings, {
    initialState,
})

app.config.globalProperties.t = translate
app.config.globalProperties.n = translatePlural

app.mount('#intravox-admin-settings')
