<script setup>
import { useAuth } from '@/composables/useAuth';

const { isAuthenticated, isStaff, isBusiness } = useAuth();
const portalPath = () => (isBusiness.value ? '/business' : '/admin');
const showPortal = () => !isAuthenticated.value || isStaff.value || isBusiness.value;
</script>

<template>
    <header class="header">
        <div class="container header__inner">
            <router-link to="/" class="header__logo" aria-label="Kanguro — página inicial">
                <img src="/site/assets/img/kanguro-logo.webp" alt="Kanguro" width="160" height="48">
            </router-link>
            <nav class="header__actions" style="margin-left:auto;display:flex;gap:.5rem;align-items:center">
                <router-link to="/rastrear" class="btn btn--sm btn--outline">Rastrear</router-link>
                <router-link
                    v-if="showPortal()"
                    :to="isAuthenticated ? portalPath() : '/login'"
                    class="btn btn--sm btn--yellow"
                    style="--bd: var(--black, #171717)"
                >
                    {{ isAuthenticated ? 'Painel' : 'Entrar' }}
                </router-link>
            </nav>
        </div>
    </header>
</template>
