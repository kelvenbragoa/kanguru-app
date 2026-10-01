<script setup>
import { useAuth } from '@/composables/useAuth';

const { isAuthenticated, isStaff } = useAuth();
</script>

<template>
    <header class="nav">
        <div class="nav-inner">
            <router-link to="/" class="brand">yala</router-link>
            <nav class="links">
                <a href="/#servicos">Serviços</a>
                <a href="/#sobre">Sobre</a>
                <a href="/#como">Como funciona</a>
                <router-link to="/rastrear">Rastrear</router-link>
                <router-link v-if="isStaff" to="/admin">Painel</router-link>
            </nav>
            <router-link v-if="!isAuthenticated || isStaff" :to="isStaff ? '/admin' : '/login'" class="enter">
                {{ isStaff ? 'Painel' : 'Entrar' }}
            </router-link>
        </div>
    </header>
</template>

<style scoped>
.nav {
    position: sticky;
    top: 0;
    z-index: 20;
    background: #fff;
    border-bottom: 1px solid #f0f2f4;
}
.nav-inner {
    max-width: 1120px;
    margin: 0 auto;
    min-height: 4.25rem;
    padding: 0.75rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
}
.brand {
    color: #36b750;
    font-weight: 800;
    font-size: 1.65rem;
    letter-spacing: -0.04em;
    text-decoration: none;
    line-height: 1;
}
.links {
    display: flex;
    flex-wrap: wrap;
    gap: 1.15rem;
    margin-left: auto;
}
.links a {
    color: #1f2933;
    text-decoration: none;
    font-size: 0.95rem;
    font-weight: 500;
}
.links a.router-link-active {
    color: #1b5c2c;
}
.enter {
    border: 1px solid #e5e7eb;
    border-radius: 999px;
    padding: 0.4rem 0.9rem;
    text-decoration: none;
    color: #1f2933;
    font-weight: 600;
    font-size: 0.92rem;
    white-space: nowrap;
}
@media (max-width: 760px) {
    .nav-inner {
        flex-wrap: wrap;
    }
    .links {
        order: 3;
        width: 100%;
        margin-left: 0;
    }
}
</style>
