<script setup>
import PublicHeader from '@/components/PublicHeader.vue';
</script>

<template>
    <div class="home">
        <PublicHeader dark />

        <section class="hero">
            <div class="hero-inner">
                <p class="eyebrow">Maputo · Moçambique</p>
                <h1>Entrega, carga e mudança no mesmo sítio.</h1>
                <p class="lead">
                    A YALA liga clientes, lojas e motoristas. Peça na app, pague em M-Pesa ou e-Mola,
                    e acompanhe o pedido até à porta.
                </p>
                <div class="actions">
                    <router-link to="/login" class="btn-primary">Entrar no portal</router-link>
                    <router-link to="/rastrear" class="btn-ghost">Rastrear pedido</router-link>
                </div>
            </div>
        </section>

        <section class="services">
            <article>
                <i class="pi pi-shopping-bag"></i>
                <h3>Entregas</h3>
                <p>Comida, farmácia e supermercado com taxa em meticais.</p>
            </article>
            <article>
                <i class="pi pi-box"></i>
                <h3>Carga</h3>
                <p>Transporte de mercadoria com motorista e veículo certos.</p>
            </article>
            <article>
                <i class="pi pi-home"></i>
                <h3>Mudança</h3>
                <p>Recolha e entrega de volumes entre dois pontos em Maputo.</p>
            </article>
        </section>

        <section class="pay">
            <h2>Pague como quiser</h2>
            <div class="pay-row">
                <span>M-Pesa</span>
                <span>e-Mola</span>
                <span>Dinheiro</span>
                <span>Cartão</span>
            </div>
        </section>

        <footer class="foot">
            <p>Clientes e motoristas usam a app YALA. Este site é o portal da equipa.</p>
            <p>© {{ new Date().getFullYear() }} YALA</p>
        </footer>
    </div>
</template>

<style scoped>
.home {
    min-height: 100vh;
    background: #111827;
    color: #f9fafb;
}
.hero {
    padding: 3.5rem 1.5rem 4rem;
}
.hero-inner {
    max-width: 720px;
    margin: 0 auto;
    text-align: center;
}
.eyebrow {
    color: #36b750;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    font-size: 0.8rem;
}
h1 {
    font-size: clamp(2rem, 5vw, 3.4rem);
    line-height: 1.15;
    margin: 0.75rem 0 1rem;
    font-weight: 800;
}
.lead {
    color: #d1d5db;
    font-size: 1.1rem;
    line-height: 1.6;
    margin-bottom: 2rem;
}
.actions {
    display: flex;
    gap: 0.75rem;
    justify-content: center;
    flex-wrap: wrap;
}
.btn-primary,
.btn-ghost {
    display: inline-flex;
    align-items: center;
    padding: 0.85rem 1.4rem;
    border-radius: 999px;
    font-weight: 700;
    text-decoration: none;
}
.btn-primary {
    background: #36b750;
    color: #1a1400;
}
.btn-ghost {
    border: 1px solid #4b5563;
    color: #f9fafb;
}
.services {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem;
    max-width: 960px;
    margin: 0 auto;
    padding: 0 1.5rem 4rem;
}
.services article {
    background: #1f2937;
    border-radius: 1.25rem;
    padding: 1.5rem;
}
.services i {
    color: #36b750;
    font-size: 1.4rem;
}
.services h3 {
    margin: 0.75rem 0 0.4rem;
}
.services p {
    color: #9ca3af;
    margin: 0;
}
.pay {
    text-align: center;
    padding: 0 1.5rem 4rem;
}
.pay h2 {
    margin-bottom: 1rem;
}
.pay-row {
    display: flex;
    gap: 0.75rem;
    justify-content: center;
    flex-wrap: wrap;
}
.pay-row span {
    background: #36b750;
    color: #1a1400;
    font-weight: 800;
    padding: 0.5rem 1rem;
    border-radius: 999px;
}
.foot {
    border-top: 1px solid #374151;
    padding: 2rem 1.5rem;
    text-align: center;
    color: #9ca3af;
    font-size: 0.9rem;
}
</style>
