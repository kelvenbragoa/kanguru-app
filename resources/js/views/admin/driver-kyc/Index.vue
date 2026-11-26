<script setup>
import { onMounted, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import http, { apiError, apiPayload } from '@/api/http';

const toast = useToast();
const loading = ref(true);
const rows = ref([]);

const load = async () => {
    loading.value = true;
    try {
        const response = await http.get('/driver-kyc', { params: { per_page: 50 } });
        rows.value = apiPayload(response).data || [];
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error), life: 4000 });
    } finally {
        loading.value = false;
    }
};

const review = async (row, status) => {
    try {
        await http.post(`/driver-kyc/${row.id}/review`, { status });
        toast.add({ severity: 'success', summary: status === 'approved' ? 'Aprovado' : 'Rejeitado', life: 2500 });
        await load();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error), life: 4000 });
    }
};

onMounted(load);
</script>

<template>
    <div class="card">
        <h2 class="m-0 mb-4">KYC motoristas</h2>
        <DataTable :value="rows" :loading="loading">
            <Column header="Motorista">
                <template #body="{ data }">{{ data.user?.name }} · {{ data.user?.profile?.phone || '—' }}</template>
            </Column>
            <Column field="license_number" header="Carta" />
            <Column field="status_label" header="Estado" />
            <Column header="Acções">
                <template #body="{ data }">
                    <Button v-if="data.status === 'pending'" label="Aprovar" size="small" class="mr-2" @click="review(data, 'approved')" />
                    <Button v-if="data.status === 'pending'" label="Rejeitar" size="small" severity="danger" outlined @click="review(data, 'rejected')" />
                </template>
            </Column>
        </DataTable>
    </div>
</template>
