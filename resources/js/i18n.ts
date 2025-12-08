import { createI18n } from 'vue-i18n';
import en from './locales/en.json';
import ar from './locales/ar.json';

// Get initial locale from Inertia props or default to 'ar'
const getInitialLocale = () => {
    // This will be set from Inertia after mount
    return 'ar';
};

const i18n = createI18n({
    legacy: false, // Use Composition API mode
    locale: getInitialLocale(),
    fallbackLocale: 'ar',
    messages: {
        en,
        ar,
    },
});

export default i18n;
