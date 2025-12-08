import { createI18n } from 'vue-i18n';
import en from './locales/en.json';
import ar from './locales/ar.json';

// Get initial locale from Inertia props or default to 'en'
const getInitialLocale = () => {
    // This will be set from Inertia after mount
    return 'en';
};

const i18n = createI18n({
    legacy: false, // Use Composition API mode
    locale: getInitialLocale(),
    fallbackLocale: 'en',
    messages: {
        en,
        ar,
    },
});

export default i18n;
