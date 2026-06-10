import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';

import es from './locales/es.json';
import en from './locales/en.json';
import fr from './locales/fr.json';
import de from './locales/de.json';
import pt from './locales/pt.json';
import it from './locales/it.json';

export const SUPPORTED_LANGS = [
    { code: 'es', label: 'Español' },
    { code: 'en', label: 'English' },
    { code: 'fr', label: 'Français' },
    { code: 'de', label: 'Deutsch' },
    { code: 'pt', label: 'Português' },
    { code: 'it', label: 'Italiano' },
];

// Detecta idioma guardado o el del navegador
const saved = typeof localStorage !== 'undefined' ? localStorage.getItem('pagepolis_lang') : null;
const browserLang = typeof navigator !== 'undefined' ? navigator.language.split('-')[0] : 'es';
const SUPPORTED = ['es', 'en', 'fr', 'de', 'pt', 'it'];
const detected = saved && SUPPORTED.includes(saved) ? saved
    : SUPPORTED.includes(browserLang) ? browserLang
    : 'es';

i18n
    .use(initReactI18next)
    .init({
        resources: { es: { translation: es }, en: { translation: en }, fr: { translation: fr }, de: { translation: de }, pt: { translation: pt }, it: { translation: it } },
        lng: detected,
        fallbackLng: 'es',
        interpolation: { escapeValue: false },
        initImmediate: false,
    });

export default i18n;
