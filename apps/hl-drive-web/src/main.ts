import { createApp } from 'vue';
import { createPinia } from 'pinia';
import './assets/main.css';
import App from './App.vue';
import CButton from './components/CButton.vue';
import CSelect from './components/CSelect.vue';
import CInput from './components/CInput.vue';
import CPasswodInput from './components/CPasswodInput.vue';
import CTextarea from './components/CTextarea.vue';
import CDropZone from './components/CDropZone.vue';
import CTypeahead from './components/CTypeahead.vue';
import UIPageHeader from './components/UIPageHeader.vue';
import DateDisplay from './components/DateDisplay.vue';
import { Icon } from '@iconify/vue';
import router from './router';
import authPlugin from './plugins/auth';
import ToastPlugin from '@/plugins/toast';
import i18n from './plugins/i18n';
import { SpeedInsights } from '@vercel/speed-insights/vue';
import { useTenantStore } from './stores/tenant';
import { useTenantHeaders } from './composables/useTenantHeaders';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);
app.use(i18n);
app.use(authPlugin);

// Initialize tenant store and headers integration
const tenantStore = useTenantStore();
const { headers } = useTenantHeaders();

// Listen to tenant changes to update headers
window.addEventListener('tenant-changed', () => {
    const tenantHeaders = headers();
    // Headers will be automatically included in API calls via the api service
    console.log('Tenant changed. Active headers:', tenantHeaders);
});

const components = {
    CButton: CButton,
    Button: CButton,
    CSelect: CSelect,
    CInput: CInput,
    CTextarea: CTextarea,
    CDropZone: CDropZone,
    CTypeahead: CTypeahead,
    UIPageHeader: UIPageHeader,
    CPasswodInput: CPasswodInput,
    SpeedInsights: SpeedInsights,
    DateDisplay: DateDisplay,
    Icon: Icon,
    UIcon: Icon,
};

app.use(ToastPlugin, {
    autoClose: 8000,
});

for (let [compName, compObj] of Object.entries(components)) {
    app.component(compName, compObj);
}

app.mount('#app');

// Initialize tenants after app is mounted
tenantStore.initialize().catch((err) => {
    console.error('Failed to initialize tenants:', err);
});
