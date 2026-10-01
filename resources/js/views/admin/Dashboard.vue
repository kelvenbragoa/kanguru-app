<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import http, { apiPayload, unwrapPage } from '@/api/http';
import SparkBars from '@/components/SparkBars.vue';
import { formatDate, formatMt, statusLabel, statusSeverity } from '@/utils/format';

const router = useRouter();
const loading = ref(true);
const stats = ref({
    total_orders: 0,
    pending_orders: 0,
    unassigned_orders: 0,
    active_vehicles: 0,
    online_drivers: 0,
    pending_payments: 0,
    total_revenue: 0,
    delivered_today: 0,
});
const reports = ref({ series: [], by_type: [], by_shop: [], drivers: [] });
const recent = ref([]);

const cards = [
    { key: 'total_orders', label: 'Pedidos', icon: 'pi pi-send', to: 'orders.index' },
    { key: 'unassigned_orders', label: 'Sem motorista', icon: 'pi pi-users', to: 'dispatch.index' },
    { key: 'online_drivers', label: 'Motoristas online', icon: 'pi pi-wifi', to: 'dispatch.index' },
    { key: 'pending_payments', label: 'Pagamentos pendentes', icon: 'pi pi-wallet', to: 'payments.index' },
    { key: 'delivered_today', label: 'Entregues hoje', icon: 'pi pi-check-circle' },
    { key: 'active_vehicles', label: 'Veículos disponíveis', icon: 'pi pi-car', to: 'vehicles.index' },
    { key: 'total_revenue', label: 'Receita', icon: 'pi pi-chart-line', money: true },
    { key: 'pending_orders', label: 'Pendentes', icon: 'pi pi-clock', to: 'orders.index' },
];

onMounted(async () => {
    try {
        const [statsRes, reportsRes, ordersRes] = await Promise.all([
            http.get('/dashboard/stats'),
            http.get('/dashboard/reports', { params: { days: 14 } }),
            http.get('/orders', { params: { per_page: 8 } }),
        ]);
        stats.value = { ...stats.value, ...apiPayload(statsRes) };
        reports.value = apiPayload(reportsRes);
        recent.value = unwrapPage(ordersRes.data).items;
    } catch {
        recent.value = [];
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div>
        <div class="mb-6">
            <h2 class="m-0 text-2xl font-bold">Painel</h2>
            <p class="text-muted-color mt-1 mb-0">Operação YALA em Maputo</p>
        </div>

        <div class="grid grid-cols-12 gap-4 mb-6">
            <div v-for="card in cards" :key="card.key" class="col-span-12 sm:col-span-6 xl:col-span-3">
                <div class="card mb-0 cursor-pointer" @click="card.to && router.push({ name: card.to })">
                    <div class="flex justify-between mb-3">
                        <span class="font-medium">{{ card.label }}</span>
                        <i :class="card.icon" class="text-primary"></i>
                    </div>
                    <div class="text-3xl font-bold">
                        {{ card.money ? formatMt(stats[card.key]) : stats[card.key] ?? 0 }}
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-4 mb-6">
            <div class="col-span-12 xl:col-span-8 card mb-0">
                <h3 class="mt-0">Pedidos (14 dias)</h3>
                <SparkBars :items="reports.series" value-key="orders" label-key="label" />
            </div>
            <div class="col-span-12 xl:col-span-4 card mb-0">
                <h3 class="mt-0">Receita (14 dias)</h3>
                <SparkBars :items="reports.series" value-key="revenue" label-key="label" color="#111827" />
            </div>
        </div>

        <div class="grid grid-cols-12 gap-4 mb-6">
            <div class="col-span-12 lg:col-span-6 card mb-0">
                <h3 class="mt-0">Por tipo de serviço</h3>
                <DataTable :value="reports.by_type">
                    <Column field="name" header="Tipo" />
                    <Column field="orders" header="Pedidos" />
                    <Column header="Volume">
                        <template #body="{ data }">{{ formatMt(data.revenue) }}</template>
                    </Column>
                </DataTable>
            </div>
            <div class="col-span-12 lg:col-span-6 card mb-0">
                <h3 class="mt-0">Motoristas</h3>
                <DataTable :value="reports.drivers">
                    <Column field="name" header="Nome" />
                    <Column field="deliveries" header="Entregas" />
                    <Column header="Ganhos">
                        <template #body="{ data }">{{ formatMt(data.earnings) }}</template>
                    </Column>
                    <Column header="">
                        <template #body="{ data }">
                            <Tag :value="data.is_online ? 'Online' : 'Offline'" :severity="data.is_online ? 'success' : 'secondary'" />
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <div class="card">
            <div class="flex justify-between items-center mb-4">
                <h3 class="m-0">Pedidos recentes</h3>
                <Button label="Ver todos" text @click="router.push({ name: 'orders.index' })" />
            </div>
            <DataTable :value="recent" :loading="loading" dataKey="id" responsiveLayout="scroll">
                <Column field="code" header="Código" />
                <Column header="Cliente">
                    <template #body="{ data }">{{ data.user?.name || '—' }}</template>
                </Column>
                <Column header="Tipo">
                    <template #body="{ data }">{{ data.order_type?.name || '—' }}</template>
                </Column>
                <Column header="Estado">
                    <template #body="{ data }">
                        <Tag :value="statusLabel(data.order_status)" :severity="statusSeverity(data.order_status)" />
                    </template>
                </Column>
                <Column header="Total">
                    <template #body="{ data }">{{ formatMt(data.total_price) }}</template>
                </Column>
                <Column header="Data">
                    <template #body="{ data }">{{ formatDate(data.created_at) }}</template>
                </Column>
                <Column>
                    <template #body="{ data }">
                        <Button icon="pi pi-eye" text rounded @click="router.push({ name: 'orders.show', params: { id: data.id } })" />
                    </template>
                </Column>
            </DataTable>
        </div>
    </div>
</template>
