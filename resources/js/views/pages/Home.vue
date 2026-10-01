<script setup>
import PublicHeader from '@/components/PublicHeader.vue';

const year = new Date().getFullYear();

const services = [
    {
        title: 'Ir a qualquer sítio',
        tone: 'indigo',
        items: ['Entrega', 'Express', 'Recolha', 'Entre bairros'],
    },
    {
        title: 'Comer a qualquer hora',
        tone: 'mint',
        items: ['Restaurantes', 'Farmácia', 'Supermercado'],
    },
    {
        title: 'Levar qualquer coisa',
        tone: 'violet',
        items: ['Carga', 'Mudança', 'Volumes', 'Porta a porta'],
    },
    {
        title: 'Pagar como quiser',
        tone: 'navy',
        items: ['M-Pesa', 'e-Mola', 'Dinheiro', 'Cartão'],
    },
];

const steps = [
    {
        title: 'Peça na app',
        text: 'Clientes escolhem a loja ou dois pontos em Maputo e confirmam o pedido.',
    },
    {
        title: 'Acompanhe ao vivo',
        text: 'O código do pedido abre o rastreio neste site, com estado e localização.',
    },
    {
        title: 'A equipa opera aqui',
        text: 'Despacho, pagamentos e catálogo ficam no portal, com o mesmo acesso de sempre.',
    },
];
</script>

<template>
    <div class="home">
        <PublicHeader />

        <section class="hero">
            <p class="kicker">Maputo</p>
            <h1>Entrega, carga e mudança no mesmo sítio.</h1>
            <p class="lead">A YALA liga clientes, lojas e motoristas. Pague em M-Pesa ou e-Mola e acompanhe até à porta.</p>
            <div class="hero-actions">
                <router-link to="/rastrear" class="btn-green">Rastrear pedido</router-link>
                <router-link to="/login" class="btn-text">Entrar no portal</router-link>
            </div>
        </section>

        <section id="servicos" class="services">
            <article v-for="service in services" :key="service.title" class="card" :class="service.tone">
                <h2>{{ service.title }}</h2>
                <ul>
                    <li v-for="item in service.items" :key="item">{{ item }}</li>
                </ul>
                <router-link to="/rastrear" class="go" aria-label="Rastrear">→</router-link>
            </article>
        </section>

        <section id="sobre" class="band">
            <div class="band-inner">
                <div>
                    <h2>Motoristas</h2>
                    <p>
                        Entregue com a YALA e acompanhe os pedidos no telemóvel. A operação em Maputo
                        atribui motorista, veículo e pagamento a partir do portal.
                    </p>
                    <router-link to="/login" class="btn-white">Entrar</router-link>
                </div>
                <div class="panel">
                    <p class="panel-kicker">Na app</p>
                    <h3>Do pedido à porta</h3>
                    <p>Clientes e motoristas usam a aplicação. Este site é o sítio para rastrear e para a equipa entrar.</p>
                </div>
            </div>
        </section>

        <section id="como" class="journal">
            <div class="journal-head">
                <h2>Como funciona</h2>
                <router-link to="/rastrear">Rastrear →</router-link>
            </div>
            <div class="journal-grid">
                <article v-for="step in steps" :key="step.title">
                    <h3>{{ step.title }}</h3>
                    <p>{{ step.text }}</p>
                </article>
            </div>
        </section>

        <footer class="foot">
            <div class="foot-grid">
                <router-link to="/" class="brand">yala</router-link>
                <div>
                    <h4>Serviços</h4>
                    <a href="/#servicos">Entregas</a>
                    <a href="/#servicos">Carga</a>
                    <a href="/#servicos">Mudança</a>
                    <a href="/#servicos">Pagamentos</a>
                </div>
                <div>
                    <h4>Empresa</h4>
                    <a href="/#sobre">Sobre</a>
                    <a href="/#como">Como funciona</a>
                    <router-link to="/login">Entrar</router-link>
                </div>
                <div>
                    <h4>Ajuda</h4>
                    <router-link to="/rastrear">Rastrear pedido</router-link>
                    <p>Clientes e motoristas entram pela app YALA.</p>
                </div>
            </div>
            <div class="legal">
                <span>© {{ year }} YALA</span>
                <span>Maputo, Moçambique</span>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.home {
    background: #fff;
    color: #173028;
    font-family: Lato, sans-serif;
}
.hero {
    max-width: 760px;
    margin: 0 auto;
    padding: 5.5rem 1.5rem 4rem;
    text-align: center;
}
.kicker {
    margin: 0 0 0.75rem;
    color: #36b750;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    font-size: 0.78rem;
}
.hero h1 {
    margin: 0;
    font-size: clamp(2.6rem, 6vw, 4.4rem);
    line-height: 1.05;
    letter-spacing: -0.04em;
    color: #163228;
    font-weight: 800;
}
.lead {
    margin: 1.1rem auto 0;
    max-width: 38rem;
    color: #3d4f46;
    font-size: 1.15rem;
    line-height: 1.55;
}
.hero-actions {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1.25rem;
    margin-top: 1.75rem;
    flex-wrap: wrap;
}
.btn-green,
.btn-white,
.btn-text {
    text-decoration: none;
    font-weight: 700;
}
.btn-green {
    background: #36b750;
    color: #102016;
    border-radius: 999px;
    padding: 0.75rem 1.2rem;
}
.btn-text {
    color: #163228;
}
.services {
    max-width: 1120px;
    margin: 0 auto 4rem;
    padding: 0 1.25rem;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
}
.card {
    position: relative;
    min-height: 18rem;
    border-radius: 1.4rem;
    padding: 1.5rem 1.35rem 3.2rem;
}
.card h2 {
    margin: 0 0 1.1rem;
    font-size: 1.85rem;
    line-height: 1.05;
    letter-spacing: -0.03em;
    max-width: 9ch;
}
.card ul {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}
.card li::before {
    content: '✓ ';
    font-weight: 700;
}
.go {
    position: absolute;
    right: 1.1rem;
    bottom: 1rem;
    color: inherit;
    text-decoration: none;
    font-size: 1.4rem;
}
.indigo {
    background: #3d3db5;
    color: #f4f6ff;
}
.mint {
    background: #d8f6df;
    color: #163228;
}
.violet {
    background: #6d4dff;
    color: #f6f4ff;
}
.navy {
    background: #10233f;
    color: #f4f7fb;
}
.band {
    background: linear-gradient(115deg, #e7f8ef 0%, #b7ebc8 42%, #36b750 100%);
    clip-path: polygon(0 7%, 100% 0, 100% 93%, 0 100%);
    padding: 6.5rem 1.25rem 7rem;
}
.band-inner {
    max-width: 1120px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1.1fr 0.9fr;
    gap: 2rem;
    align-items: center;
}
.band h2,
.journal h2 {
    margin: 0 0 0.75rem;
    font-size: clamp(2rem, 4vw, 2.8rem);
    letter-spacing: -0.03em;
}
.band p,
.journal p,
.panel p {
    line-height: 1.55;
    margin: 0;
}
.btn-white {
    display: inline-block;
    margin-top: 1.25rem;
    background: #fff;
    color: #163228;
    border-radius: 999px;
    padding: 0.65rem 1.1rem;
}
.panel {
    background: rgba(255, 255, 255, 0.72);
    border-radius: 1.4rem;
    padding: 1.5rem;
}
.panel-kicker {
    color: #1b5c2c;
    font-weight: 700;
    margin-bottom: 0.35rem;
}
.panel h3 {
    margin: 0 0 0.5rem;
    font-size: 1.6rem;
}
.journal {
    max-width: 1120px;
    margin: 0 auto;
    padding: 4rem 1.25rem 2rem;
}
.journal-head,
.journal-grid,
.foot-grid,
.legal {
    display: flex;
    justify-content: space-between;
    gap: 1.5rem;
}
.journal-head a,
.foot a,
.foot :deep(a) {
    color: #1b5c2c;
    font-weight: 700;
    text-decoration: none;
}
.journal-grid {
    margin-top: 1.5rem;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
}
.journal article {
    border-top: 1px solid #e6eee9;
    padding-top: 1rem;
}
.journal h3 {
    margin: 0 0 0.5rem;
    font-size: 1.25rem;
}
.foot {
    max-width: 1120px;
    margin: 0 auto;
    padding: 3rem 1.25rem 2rem;
}
.foot-grid > div {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
    min-width: 8rem;
}
.foot h4 {
    margin: 0 0 0.35rem;
}
.foot .brand {
    color: #36b750;
    font-size: 1.7rem;
    font-weight: 800;
    letter-spacing: -0.04em;
    text-decoration: none;
}
.foot p {
    margin: 0.35rem 0 0;
    color: #4b6356;
    max-width: 16rem;
}
.legal {
    margin-top: 2.5rem;
    padding-top: 1rem;
    border-top: 1px solid #edf2ef;
    color: #6b7280;
    font-size: 0.9rem;
}
@media (max-width: 900px) {
    .services,
    .journal-grid,
    .band-inner,
    .foot-grid {
        grid-template-columns: 1fr 1fr;
        display: grid;
    }
    .band {
        clip-path: none;
    }
}
@media (max-width: 640px) {
    .services,
    .journal-grid,
    .band-inner,
    .foot-grid,
    .legal {
        grid-template-columns: 1fr;
        display: grid;
    }
}
</style>
