import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { initializeTheme } from './composables/useAppearance';
import i18n from './i18n';
import axios from 'axios';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Handle CSRF token mismatch (419 errors) - auto refresh page to get new token
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 419) {
            // CSRF token mismatch - reload page to get fresh token
            window.location.reload();
        }
        return Promise.reject(error);
    }
);

// Also handle Inertia navigation errors for CSRF mismatch
router.on('invalid', (event) => {
    if (event.detail.response.status === 419) {
        event.preventDefault();
        window.location.reload();
    }
});

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(i18n);

        // Set initial locale from Inertia props
        if (props.initialPage.props.locale) {
            i18n.global.locale.value = props.initialPage.props.locale as 'en' | 'ar';
        }

        app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
