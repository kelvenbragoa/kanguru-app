<script setup>
import PublicHeader from '@/components/PublicHeader.vue';
</script>

<template>
    <div class="page">
        <PublicHeader />
        <main>
            <h1>Página não encontrada</h1>
            <p>O endereço que abriu não existe.</p>
            <router-link to="/">Voltar à página inicial</router-link>
        </main>
    </div>
</template>

<style scoped>
.page {
    min-height: 100vh;
    background: #f9fafb;
}
main {
    max-width: 640px;
    margin: 0 auto;
    padding: 4rem 1.5rem;
    text-align: center;
}
a {
    font-weight: 700;
    color: #111827;
}
</style>
