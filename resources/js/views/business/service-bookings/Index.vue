<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import http, { apiError, apiPayload } from '@/api/http';
import { formatDate, formatMt } from '@/utils/format';

const router = useRouter();
const toast = useToast();
const loading = ref(true);
const items = ref([]);

const load = async () => {
    loading.value = true;
    try {
        const payload = apiPayload(await http.get('/business/service-bookings'));
        items.value = payload?.data || payload || [];
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível carregar.'), life: 4000 });
    } finally {
        loading.value = false;
    }
};

onMounted(load);
</script>

<template>
    <div class="card">
        <div class="mb-4">
            <h2 class="m-0">Pedidos de serviço</h2>
            <p class="text-muted-color mt-2 mb-0">Aceita, executa e fecha com o código do cliente.</p>
        </div>

        <DataTable :value="items" :loading="loading">
            <Column field="code" header="Código" />
            <Column header="Serviço">
                <template #body="{ data }">{{ data.offering?.title }}</template>
            </Column>
            <Column header="Cliente">
                <template #body="{ data }">{{ data.user?.name }}</template>
            </Column>
            <Column header="Quando">
                <template #body="{ data }">{{ formatDate(data.scheduled_at) }}</template>
            </Column>
            <Column header="Total">
                <template #body="{ data }">{{ formatMt(data.price) }}</template>
            </Column>
            <Column field="status_label" header="Estado" />
            <Column>
                <template #body="{ data }">
                    <Button icon="pi pi-eye" text rounded @click="router.push({ name: 'business.serviceBookings.show', params: { id: data.id } })" />
                </template>
            </Column>
        </DataTable>
    </div>
</template>
