<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';

const year = new Date().getFullYear();
const prose = ref('');
const loading = ref(true);

onMounted(async () => {
    document.body.classList.add('kanguro-public');
    if (!document.getElementById('kanguro-site-css')) {
        const css = document.createElement('link');
        css.id = 'kanguro-site-css';
        css.rel = 'stylesheet';
        css.href = '/site/assets/css/styles.css';
        document.head.appendChild(css);
    }
    try {
        const response = await fetch('/site/privacidade-prose.html', { cache: 'no-cache' });
        prose.value = await response.text();
    } catch (_) {
        prose.value = '<p>Não foi possível carregar a política de privacidade.</p>';
    } finally {
        loading.value = false;
    }
});

onBeforeUnmount(() => {
    document.body.classList.remove('kanguro-public');
});
</script>

<template>
    <div>
        <a class="skip-link" href="#conteudo">Saltar para o conteúdo</a>
        <header class="header">
            <div class="container header__inner">
                <RouterLink class="header__logo" to="/" aria-label="Kanguro — página inicial">
                    <img src="/site/assets/img/kanguro-logo.webp" alt="Kanguro" width="1152" height="836">
                </RouterLink>
                <RouterLink class="btn btn--sm" to="/" style="margin-left:auto">Voltar ao início</RouterLink>
            </div>
        </header>
        <main id="conteudo">
            <section class="page-hero"><div class="container">
                <span class="eyebrow">Kanguro</span>
                <h1>Política de Privacidade</h1>
                <p>Como a Kanguro recolhe, usa, partilha e protege os dados pessoais.</p>
            </div></section>
            <div class="container prose">
                <p v-if="loading">A carregar…</p>
                <div v-else v-html="prose" />
            </div>
        </main>
        <footer class="footer" style="padding-top:0">
            <div class="container footer__bottom" style="margin-top:0">
                <p>© {{ year }} Kanguro. Todos os direitos reservados.</p>
                <p>
                    <RouterLink to="/privacidade">Privacidade</RouterLink>
                    ·
                    <RouterLink to="/termos">Termos</RouterLink>
                    ·
                    <RouterLink to="/#contacto">Contacto</RouterLink>
                </p>
            </div>
        </footer>
    </div>
</template>
