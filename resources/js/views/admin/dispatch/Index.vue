<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import http, { apiError, apiPayload } from '@/api/http';
import LiveMap from '@/components/LiveMap.vue';
import { formatDate, formatMt, statusLabel, statusSeverity } from '@/utils/format';

const router = useRouter();
const toast = useToast();
const loading = ref(false);
const acting = ref(false);
const board = ref({ unassigned: [], active: [], drivers: [], unassigned_count: 0, online_count: 0, busy_count: 0 });
let timer = null;

const load = async (silent = false) => {
    if (!silent) loading.value = true;
    try {
        const response = await http.get('/dispatch/board');
        board.value = apiPayload(response);
    } finally {
        loading.value = false;
    }
};

const retry = async (order) => {
    acting.value = true;
    try {
        const response = await http.post(`/dispatch/orders/${order.id}/retry`);
        const driver = apiPayload(response).driver;
        toast.add({
            severity: driver ? 'success' : 'warn',
            summary: driver ? 'Atribuído' : 'Sem motorista online',
            detail: driver ? `${driver.name} ficou com ${order.code}.` : 'Nenhum motorista disponível neste momento.',
            life: 3500,
        });
        await load(true);
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível despachar.'), life: 4000 });
    } finally {
        acting.value = false;
    }
};

const unassign = async (order) => {
    acting.value = true;
    try {
        await http.post(`/dispatch/orders/${order.id}/unassign`);
        toast.add({ severity: 'success', summary: 'Removido', detail: 'O motorista foi libertado.', life: 2500 });
        await load(true);
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível libertar.'), life: 4000 });
    } finally {
        acting.value = false;
    }
};

const driverStatus = (driver) => {
    if (!driver.is_online) return { label: 'Offline', severity: 'secondary' };
    if (driver.is_busy) return { label: 'Ocupado', severity: 'warn' };
    return { label: 'Livre', severity: 'success' };
};

onMounted(() => {
    load();
    timer = window.setInterval(() => load(true), 12000);
});
onUnmounted(() => timer && window.clearInterval(timer));
</script>

<template>
    <div>
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
            <div>
                <h2 class="m-0">Despacho</h2>
                <p class="text-muted-color mt-1 mb-0">Pedidos sem motorista e frota em tempo real.</p>
            </div>
            <Button icon="pi pi-refresh" label="Actualizar" :loading="loading" @click="load()" />
        </div>

        <div class="grid grid-cols-12 gap-4 mb-4">
            <div class="col-span-12 md:col-span-4 card mb-0">
                <span class="text-muted-color">À espera</span>
                <div class="text-3xl font-bold">{{ board.unassigned_count }}</div>
            </div>
            <div class="col-span-12 md:col-span-4 card mb-0">
                <span class="text-muted-color">Online</span>
                <div class="text-3xl font-bold">{{ board.online_count }}</div>
            </div>
            <div class="col-span-12 md:col-span-4 card mb-0">
                <span class="text-muted-color">Ocupados</span>
                <div class="text-3xl font-bold">{{ board.busy_count }}</div>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-12 xl:col-span-6 card mb-0">
                <h3 class="mt-0">Sem motorista</h3>
                <DataTable :value="board.unassigned" :loading="loading" dataKey="id">
                    <Column field="code" header="Código" />
                    <Column header="Cliente">
                        <template #body="{ data }">{{ data.user?.name || '—' }}</template>
                    </Column>
                    <Column header="Destino">
                        <template #body="{ data }">{{ data.destination }}</template>
                    </Column>
                    <Column header="Total">
                        <template #body="{ data }">{{ formatMt(data.total_price) }}</template>
                    </Column>
                    <Column>
                        <template #body="{ data }">
                            <Button icon="pi pi-bolt" text rounded v-tooltip.top="'Tentar atribuir'" :disabled="acting" @click="retry(data)" />
                            <Button icon="pi pi-eye" text rounded @click="router.push({ name: 'orders.show', params: { id: data.id } })" />
                        </template>
                    </Column>
                    <template #empty>Nenhum pedido à espera.</template>
                </DataTable>
            </div>

            <div class="col-span-12 xl:col-span-6 card mb-0">
                <h3 class="mt-0">Motoristas</h3>
                <DataTable :value="board.drivers" :loading="loading" dataKey="id">
                    <Column field="name" header="Nome" />
                    <Column header="Estado">
                        <template #body="{ data }">
                            <Tag :value="driverStatus(data).label" :severity="driverStatus(data).severity" />
                        </template>
                    </Column>
                    <Column header="Veículo">
                        <template #body="{ data }">{{ data.vehicle?.license_plate_number || '—' }}</template>
                    </Column>
                    <Column header="Telefone">
                        <template #body="{ data }">{{ data.phone || '—' }}</template>
                    </Column>
                </DataTable>
            </div>

            <div class="col-span-12 card mb-0">
                <h3 class="mt-0">Em curso</h3>
                <DataTable :value="board.active" :loading="loading" dataKey="id">
                    <Column field="code" header="Código" />
                    <Column header="Motorista">
                        <template #body="{ data }">{{ data.agent?.name || '—' }}</template>
                    </Column>
                    <Column header="Estado">
                        <template #body="{ data }">
                            <Tag :value="statusLabel(data.order_status)" :severity="statusSeverity(data.order_status)" />
                        </template>
                    </Column>
                    <Column header="Destino">
                        <template #body="{ data }">{{ data.destination }}</template>
                    </Column>
                    <Column header="Actualizado">
                        <template #body="{ data }">{{ formatDate(data.updated_at) }}</template>
                    </Column>
                    <Column>
                        <template #body="{ data }">
                            <Button icon="pi pi-user-minus" text rounded severity="warn" v-tooltip.top="'Libertar motorista'" :disabled="acting" @click="unassign(data)" />
                            <Button icon="pi pi-eye" text rounded @click="router.push({ name: 'orders.show', params: { id: data.id } })" />
                        </template>
                    </Column>
                    <template #empty>Nenhuma entrega activa.</template>
                </DataTable>
            </div>

            <div v-if="board.active?.[0]?.current_location" class="col-span-12 card mb-0">
                <h3 class="mt-0">Última localização (pedido {{ board.active[0].code }})</h3>
                <LiveMap
                    :latitude="board.active[0].current_location.latitude"
                    :longitude="board.active[0].current_location.longitude"
                    :updated-at="formatDate(board.active[0].current_location.updated_at)"
                    :label="board.active[0].agent?.name || 'Motorista'"
                />
            </div>
        </div>
    </div>
</template>
