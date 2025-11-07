import { createApp } from 'vue';
import { getLanguage, translate, translatePlural } from '@nextcloud/l10n';
import App from './App.vue';

console.log('IntraVox: Initializing app...');
console.log('IntraVox: Current language:', getLanguage());

const app = createApp(App);

// Make translation functions globally available
app.config.globalProperties.$t = (text, vars = {}) => translate('intravox', text, vars);
app.config.globalProperties.$n = (singular, plural, count, vars = {}) => translatePlural('intravox', singular, plural, count, vars);

app.mount('#app-intravox');
console.log('IntraVox: App mounted successfully!');
