<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import http, { apiError, apiPayload } from '@/api/http';

const router = useRouter();
const toast = useToast();
const loading = ref(true);
const stats = ref({
    shops: [],
    pending_orders: 0,
    preparing_orders: 0,
    delivered_today: 0,
    low_stock_products: 0,
});
let timer = null;
let lastPending = 0;

const load = async (silent = false) => {
    if (!silent) loading.value = true;
    try {
        const response = await http.get('/business/dashboard');
        const data = apiPayload(response);
        if (silent && data.pending_orders > lastPending) {
            toast.add({
                severity: 'warn',
                summary: 'Novo pedido',
                detail: 'Tem pedidos à espera de aceitação.',
                life: 5000,
            });
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.frequency.value = 880;
                gain.gain.value = 0.05;
                osc.start();
                osc.stop(ctx.currentTime + 0.25);
            } catch {
                // som opcional
            }
        }
        lastPending = data.pending_orders;
        stats.value = data;
    } catch (error) {
        if (!silent) {
            toast.add({
                severity: 'error',
                summary: 'Erro',
                detail: apiError(error, 'Não foi possível carregar o painel.'),
                life: 4000,
            });
        }
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    load();
    timer = window.setInterval(() => load(true), 15000);
});
onUnmounted(() => timer && window.clearInterval(timer));
</script>

<template>
    <div>
        <div class="card mb-4">
            <h2 class="m-0">Kanguro Business</h2>
            <p class="text-muted-color mt-2 mb-0">
                {{ stats.shops?.length ? stats.shops.map((s) => s.name).join(' · ') : 'Sem loja associada — peça ao admin para ligar a sua conta.' }}
            </p>
        </div>

        <div v-if="loading" class="card">A carregar…</div>
        <div v-else class="grid grid-cols-12 gap-4">
            <div class="col-span-12 md:col-span-3 card mb-0">
                <div class="text-muted-color">À espera</div>
                <div class="text-4xl font-bold mt-2">{{ stats.pending_orders }}</div>
                <Button label="Ver inbox" class="mt-3" text @click="router.push({ name: 'business.orders', query: { inbox: 1 } })" />
            </div>
            <div class="col-span-12 md:col-span-3 card mb-0">
                <div class="text-muted-color">Em preparação</div>
                <div class="text-4xl font-bold mt-2">{{ stats.preparing_orders }}</div>
            </div>
            <div class="col-span-12 md:col-span-3 card mb-0">
                <div class="text-muted-color">Entregues hoje</div>
                <div class="text-4xl font-bold mt-2">{{ stats.delivered_today }}</div>
            </div>
            <div class="col-span-12 md:col-span-3 card mb-0">
                <div class="text-muted-color">Stock baixo</div>
                <div class="text-4xl font-bold mt-2">{{ stats.low_stock_products }}</div>
                <Button label="Produtos" class="mt-3" text @click="router.push({ name: 'business.products' })" />
            </div>
        </div>
    </div>
</template>
