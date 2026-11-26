<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';

const host = ref(null);
const loading = ref(true);
const error = ref('');
const router = useRouter();

const managedLinks = [];
let cssEl = null;
let fontEl = null;
let configScript = null;
let mainScript = null;

function ensureStyles() {
    if (!document.getElementById('kanguro-site-css')) {
        cssEl = document.createElement('link');
        cssEl.id = 'kanguro-site-css';
        cssEl.rel = 'stylesheet';
        cssEl.href = '/site/assets/css/styles.css';
        document.head.appendChild(cssEl);
    }
    if (!document.getElementById('kanguro-poppins')) {
        fontEl = document.createElement('link');
        fontEl.id = 'kanguro-poppins';
        fontEl.rel = 'stylesheet';
        fontEl.href = 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap';
        document.head.appendChild(fontEl);
    }
}

function loadScript(src) {
    return new Promise((resolve, reject) => {
        const existing = document.querySelector(`script[data-kanguro-src="${src}"]`);
        if (existing) {
            existing.remove();
        }
        const el = document.createElement('script');
        el.src = src;
        el.async = false;
        el.dataset.kanguroSrc = src;
        el.onload = () => resolve(el);
        el.onerror = () => reject(new Error(`Falha ao carregar ${src}`));
        document.body.appendChild(el);
    });
}

function interceptSpaLinks(root) {
    root.querySelectorAll('a[href^="/"]').forEach((a) => {
        const href = a.getAttribute('href');
        if (!href || href.startsWith('//') || href.startsWith('/site/')) return;
        const onClick = (event) => {
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) return;
            event.preventDefault();
            router.push(href);
        };
        a.addEventListener('click', onClick);
        managedLinks.push({ a, onClick });
    });
}

onMounted(async () => {
    document.documentElement.classList.add('js');
    document.body.classList.add('kanguro-public');
    ensureStyles();

    try {
        const response = await fetch('/site/home-fragment.html', { cache: 'no-cache' });
        if (!response.ok) throw new Error('Não foi possível carregar a página.');
        host.value.innerHTML = await response.text();
        interceptSpaLinks(host.value);

        configScript = await loadScript('/site/assets/js/config.js');
        mainScript = await loadScript('/site/assets/js/main.js');
        loading.value = false;
    } catch (e) {
        error.value = e.message || 'Erro ao carregar o site.';
        loading.value = false;
    }
});

onBeforeUnmount(() => {
    document.body.classList.remove('kanguro-public', 'nav-open', 'dialog-open');
    managedLinks.forEach(({ a, onClick }) => a.removeEventListener('click', onClick));
    managedLinks.length = 0;
    [configScript, mainScript].forEach((el) => el?.remove());
});
</script>

<template>
    <div class="kanguro-home">
        <div v-if="loading" class="kanguro-home__boot">A carregar…</div>
        <div v-else-if="error" class="kanguro-home__boot">{{ error }}</div>
        <div ref="host" class="kanguro-home__content" />
    </div>
</template>

<style>
.kanguro-home__boot {
    min-height: 60vh;
    display: grid;
    place-items: center;
    font-family: Poppins, sans-serif;
    color: #171717;
}
body.kanguro-public {
    margin: 0;
}
</style>
