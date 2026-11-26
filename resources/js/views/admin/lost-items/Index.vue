<script setup>
import { onMounted, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import http, { apiError, unwrapPage } from '@/api/http';
import { formatMt } from '@/utils/format';

const toast = useToast();
const loading = ref(true);
const rows = ref([]);

const load = async () => {
    loading.value = true;
    try {
        const response = await http.get('/lost-items', { params: { per_page: 50 } });
        rows.value = unwrapPage(response.data).items;
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error), life: 4000 });
    } finally {
        loading.value = false;
    }
};

const closeItem = async (row) => {
    try {
        await http.post(`/lost-items/${row.id}/support`, { status: 'closed', support_notes: 'Fechado pelo suporte' });
        toast.add({ severity: 'success', summary: 'Fechado', life: 2500 });
        await load();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error), life: 4000 });
    }
};

onMounted(load);
</script>

<template>
    <div class="card">
        <h2 class="m-0 mb-4">Objectos perdidos (táxi)</h2>
        <DataTable :value="rows" :loading="loading">
            <Column header="Pedido">
                <template #body="{ data }">{{ data.order?.code }}</template>
            </Column>
            <Column header="Cliente">
                <template #body="{ data }">{{ data.customer?.name }}</template>
            </Column>
            <Column header="Motorista">
                <template #body="{ data }">{{ data.driver?.name || '—' }}</template>
            </Column>
            <Column field="description" header="Descrição" />
            <Column field="status_label" header="Estado" />
            <Column header="Taxa">
                <template #body="{ data }">{{ formatMt(data.return_fee) }}</template>
            </Column>
            <Column>
                <template #body="{ data }">
                    <Button v-if="!['closed', 'cancelled', 'returned'].includes(data.status)" label="Fechar" size="small" @click="closeItem(data)" />
                </template>
            </Column>
        </DataTable>
    </div>
</template>
