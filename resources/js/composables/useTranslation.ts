import { ref, computed } from 'vue';

// Global translations state
const currentLocale = ref<string>(localStorage.getItem('locale') || 'pt');
const translations = ref<any>({});

// Load translations from API
async function loadTranslations(locale: string) {
    try {
        const response = await fetch(`/api/translations/${locale}`);
        if (response.ok) {
            translations.value = await response.json();
            console.log('Translations loaded for:', locale, translations.value);
        } else {
            console.error('Failed to load translations:', response.status);
        }
    } catch (error) {
        console.error('Failed to load translations:', error);
    }
}

export function useTranslation() {
    const locale = computed(() => currentLocale.value);
    
    const t = (key: string, fallback: string = '') => {
        const keys = key.split('.');
        let value: any = translations.value;
        
        for (const k of keys) {
            if (value && typeof value === 'object' && k in value) {
                value = value[k];
            } else {
                return fallback || key;
            }
        }
        
        return value || fallback || key;
    };
    
    const changeLocale = async (newLocale: string) => {
        currentLocale.value = newLocale;
        localStorage.setItem('locale', newLocale);
        await loadTranslations(newLocale);
    };
    
    // Initialize translations
    if (Object.keys(translations.value).length === 0) {
        loadTranslations(currentLocale.value);
    }
    
    return { 
        t, 
        locale,
        changeLocale 
    };
}
