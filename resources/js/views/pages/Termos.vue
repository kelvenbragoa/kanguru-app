<script setup>
import { onMounted, onBeforeUnmount } from 'vue';
import { RouterLink } from 'vue-router';

const year = new Date().getFullYear();

onMounted(() => {
    document.body.classList.add('kanguro-public');
    if (!document.getElementById('kanguro-site-css')) {
        const css = document.createElement('link');
        css.id = 'kanguro-site-css';
        css.rel = 'stylesheet';
        css.href = '/site/assets/css/styles.css';
        document.head.appendChild(css);
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
                <h1>Termos e Condições</h1>
                <p>Condições de utilização do website institucional da Kanguro.</p>
            </div></section>
            <div class="container prose">
                <p class="notice">
                    <strong>Documento provisório.</strong> Este texto é uma base de trabalho e deve ser revisto por um jurista e ajustado à legislação moçambicana antes do lançamento oficial.
                </p>
                <h2>1. Âmbito</h2>
                <p>Estes termos aplicam-se à utilização do website institucional da Kanguro. A utilização da aplicação Kanguro terá termos próprios, publicados no momento do lançamento.</p>
                <h2>2. Informação apresentada</h2>
                <p>Os ecrãs da aplicação mostrados neste website são ilustrativos. As funcionalidades e áreas de serviço disponíveis podem variar e serão comunicadas nos canais oficiais.</p>
                <h2>3. Formulários</h2>
                <p>O envio de um formulário de parceria, colaboração ou lista de espera representa apenas uma manifestação de interesse e não cria qualquer vínculo contratual.</p>
                <h2>4. Propriedade intelectual</h2>
                <p>O nome Kanguro, o logótipo, a mascote e os restantes elementos de identidade visual pertencem à Kanguro e não podem ser usados sem autorização.</p>
                <h2>5. Alterações</h2>
                <p>Estes termos podem ser atualizados. A versão em vigor é sempre a publicada nesta página.</p>
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
