<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { useTranslation } from '@/composables/useTranslation';

const { locale, changeLocale } = useTranslation();
const isOpen = ref(false);

const languages = [
    { code: 'pt', name: 'Português', flag: '🇵🇹' },
    { code: 'en', name: 'English', flag: '🇬🇧' }
];

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
    console.log('Dropdown toggled:', isOpen.value, 'Current locale:', locale.value, 'Languages:', languages);
};

const closeDropdown = () => {
    isOpen.value = false;
};

const handleClickOutside = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    if (!target.closest('.language-selector')) {
        closeDropdown();
    }
};

const handleChangeLanguage = async (newLocale: string) => {
    if (newLocale !== locale.value) {
        await changeLocale(newLocale);
        // Post to backend to store in session
        try {
            await fetch('/locale/change', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({ locale: newLocale })
            });
        } catch (error) {
            console.error('Failed to update locale on server:', error);
        }
    }
    closeDropdown();
};

const getCurrentLanguage = () => {
    return languages.find(lang => lang.code === locale.value) || languages[0];
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="language-selector">
        <button 
            @click="toggleDropdown"
            class="flex items-center gap-2 px-4 py-2 bg-white/10 backdrop-blur-sm rounded-full hover:bg-white/20 transition-all text-white cursor-pointer"
        >
            <span class="text-xl">{{ getCurrentLanguage().flag }}</span>
            <span class="font-medium text-sm uppercase">{{ getCurrentLanguage().code }}</span>
            <i :class="['pi text-xs transition-transform', isOpen ? 'pi-chevron-up' : 'pi-chevron-down']"></i>
        </button>
        
        <transition name="dropdown">
            <div 
                v-if="isOpen"
                @click.stop
                class="language-dropdown absolute top-full right-0 mt-2 bg-white rounded-lg shadow-xl overflow-hidden min-w-[180px] z-[9999]"
            >
                <button
                    v-for="lang in languages"
                    :key="lang.code"
                    @click="handleChangeLanguage(lang.code)"
                    type="button"
                    :class="[
                        'w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-blue-50 transition-colors cursor-pointer',
                        locale === lang.code ? 'bg-blue-50 text-blue-900 font-semibold' : 'text-gray-700'
                    ]"
                >
                    <span class="text-xl">{{ lang.flag }}</span>
                    <span class="text-sm">{{ lang.name }}</span>
                    <i v-if="locale === lang.code" class="pi pi-check text-blue-600 ml-auto text-xs"></i>
                </button>
            </div>
        </transition>
    </div>
</template>

<style scoped>
.language-selector {
    position: relative;
    display: inline-block;
}

.language-dropdown {
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    border: 1px solid rgba(0, 0, 0, 0.1);
}

.language-dropdown button {
    border: none;
    background: transparent;
}

/* Dropdown animation */
.dropdown-enter-active {
    transition: all 0.2s ease-out;
}

.dropdown-leave-active {
    transition: all 0.15s ease-in;
}

.dropdown-enter-from {
    opacity: 0;
    transform: translateY(-8px);
}

.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}
</style>
