<script setup>
import { onMounted, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import http, { apiError, apiPayload } from '@/api/http';
import { formatDate, formatMt } from '@/utils/format';

const toast = useToast();
const loading = ref(true);
const data = ref({ payable: 0, paid: 0, entries: [] });

const load = async () => {
    loading.value = true;
    try {
        data.value = apiPayload(await http.get('/business/earnings')) || data.value;
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível carregar.'), life: 4000 });
    } finally {
        loading.value = false;
    }
};

onMounted(load);
</script>

<template>
    <div>
        <div class="card mb-4">
            <h2 class="m-0">Ganhos</h2>
            <p class="text-muted-color mt-2 mb-0">Valores líquidos após comissão Kanguro.</p>
        </div>
        <div v-if="loading" class="card">A carregar…</div>
        <template v-else>
            <div class="grid grid-cols-12 gap-4 mb-4">
                <div class="col-span-12 md:col-span-6 card mb-0">
                    <div class="text-muted-color">A receber</div>
                    <div class="text-3xl font-bold mt-2">{{ formatMt(data.payable) }}</div>
                </div>
                <div class="col-span-12 md:col-span-6 card mb-0">
                    <div class="text-muted-color">Já pago</div>
                    <div class="text-3xl font-bold mt-2">{{ formatMt(data.paid) }}</div>
                </div>
            </div>
            <div class="card">
                <DataTable :value="data.entries || []">
                    <Column field="description" header="Descrição" />
                    <Column header="Valor">
                        <template #body="{ data: row }">{{ formatMt(row.amount) }}</template>
                    </Column>
                    <Column field="status" header="Estado" />
                    <Column header="Quando">
                        <template #body="{ data: row }">{{ formatDate(row.created_at) }}</template>
                    </Column>
                </DataTable>
            </div>
        </template>
    </div>
</template>
