<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import http, { apiPayload } from '@/api/http';
import { formatDate, isFinalStatus, statusLabel, statusSeverity } from '@/utils/format';
import PublicHeader from '@/components/PublicHeader.vue';
import LiveMap from '@/components/LiveMap.vue';

const route = useRoute();
const toast = useToast();
const code = ref(typeof route.query.code === 'string' ? route.query.code : '');
const loading = ref(false);
const result = ref(null);
let timer = null;

const location = computed(() => result.value?.current_location || null);

const search = async (silent = false) => {
    const value = code.value.trim().toUpperCase();
    if (!value) {
        if (!silent) {
            toast.add({ severity: 'warn', summary: 'Código em falta', detail: 'Introduza o código do pedido (ex.: KNG-...).', life: 3000 });
        }
        return;
    }
    if (!silent) loading.value = true;
    try {
        const response = await http.get(`/tracking/${encodeURIComponent(value)}`);
        result.value = apiPayload(response);
        code.value = value;
    } catch (error) {
        result.value = null;
        if (!silent) {
            toast.add({
                severity: 'error',
                summary: 'Não encontrado',
                detail: error.response?.data?.message === 'Order not found' ? 'Não há pedido com esse código.' : 'Não foi possível rastrear.',
                life: 4000,
            });
        }
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    if (code.value) search();
    timer = window.setInterval(() => {
        if (result.value && !isFinalStatus(result.value.current_status)) {
            search(true);
        }
    }, 12000);
});
onUnmounted(() => timer && window.clearInterval(timer));
</script>

<template>
    <div class="track-page">
        <PublicHeader />
        <main class="wrap">
            <h1>Rastrear pedido</h1>
            <p class="lead">Introduza o código que recebeu na app, por exemplo <strong>KNG-1001</strong>.</p>

            <form class="search" @submit.prevent="search()">
                <InputText v-model="code" placeholder="KNG-..." class="flex-1" />
                <Button type="submit" label="Procurar" :loading="loading" />
            </form>

            <div v-if="result" class="result">
                <div class="head">
                    <h2>{{ result.order?.code }}</h2>
                    <Tag :value="statusLabel(result.current_status)" :severity="statusSeverity(result.current_status)" />
                </div>
                <p><strong>De:</strong> {{ result.order?.origin }}</p>
                <p><strong>Para:</strong> {{ result.order?.destination }}</p>
                <p v-if="result.driver"><strong>Motorista:</strong> {{ result.driver.name }}</p>
                <p v-if="result.vehicle"><strong>Veículo:</strong> {{ result.vehicle.license_plate_number }}</p>

                <LiveMap
                    class="mt-4"
                    :latitude="location?.latitude ?? null"
                    :longitude="location?.longitude ?? null"
                    :updated-at="location?.updated_at ? formatDate(location.updated_at) : null"
                    :label="result.driver?.name || 'Motorista'"
                />

                <h3>Histórico</h3>
                <ul class="timeline">
                    <li v-for="item in result.tracking_history" :key="item.id">
                        <strong>{{ statusLabel(item.order_status) }}</strong>
                        <span>{{ item.description }}</span>
                        <small>{{ formatDate(item.created_at) }} · {{ item.local }}</small>
                    </li>
                </ul>
            </div>
        </main>
    </div>
</template>

<style scoped>
.track-page {
    min-height: 100vh;
    background: #f9fafb;
    color: #111827;
}
.wrap {
    max-width: 720px;
    margin: 0 auto;
    padding: 2rem 1.5rem 4rem;
}
.lead {
    color: #4b5563;
}
.search {
    display: flex;
    gap: 0.75rem;
    margin: 1.5rem 0 2rem;
}
.result {
    background: #fff;
    border-radius: 1.25rem;
    padding: 1.5rem;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
}
.head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}
.timeline {
    list-style: none;
    padding: 0;
    margin: 1rem 0 0;
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
}
.timeline li {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    border-left: 3px solid #36b750;
    padding-left: 0.85rem;
}
.timeline small {
    color: #6b7280;
}
</style>
