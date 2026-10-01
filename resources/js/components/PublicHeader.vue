<script setup>
import { useAuth } from '@/composables/useAuth';

defineProps({
    dark: { type: Boolean, default: false },
});

const { isAuthenticated, isStaff } = useAuth();
</script>

<template>
    <header class="kg-nav" :class="{ 'kg-nav--dark': dark }">
        <router-link to="/" class="kg-brand">
            <span class="kg-mark">Y</span>
            <span>YALA</span>
        </router-link>
        <nav class="kg-links">
            <router-link to="/rastrear">Rastrear</router-link>
            <router-link v-if="isStaff" to="/admin">Painel</router-link>
            <router-link v-else-if="!isAuthenticated" to="/login" class="kg-cta">Entrar</router-link>
        </nav>
    </header>
</template>

<style scoped>
.kg-nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.5rem;
    max-width: 1120px;
    margin: 0 auto;
}
.kg-nav--dark {
    color: #fff;
}
.kg-brand {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    font-weight: 800;
    font-size: 1.25rem;
    color: inherit;
    text-decoration: none;
}
.kg-mark {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 0.7rem;
    background: #36b750;
    color: #1a1400;
    display: grid;
    place-items: center;
    font-weight: 900;
}
.kg-links {
    display: flex;
    align-items: center;
    gap: 1.25rem;
}
.kg-links a {
    color: inherit;
    text-decoration: none;
    font-weight: 600;
    opacity: 0.9;
}
.kg-cta {
    background: #36b750;
    color: #1a1400 !important;
    padding: 0.55rem 1.1rem;
    border-radius: 999px;
    opacity: 1 !important;
}
</style>
