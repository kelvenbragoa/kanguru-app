<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import http, { apiError, apiPayload } from '@/api/http';
import { formatDate, formatMt } from '@/utils/format';

const router = useRouter();
const toast = useToast();
const loading = ref(true);
const bookings = ref([]);

const load = async () => {
    loading.value = true;
    try {
        const response = await http.get('/business/hotel-bookings', { params: { per_page: 50 } });
        bookings.value = apiPayload(response).data || [];
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
        <h2 class="mt-0">Reservas de hotel</h2>
        <p class="text-muted-color mt-0 mb-4">Confirme check-in com o código de 4 dígitos do cliente.</p>

        <DataTable :value="bookings" :loading="loading">
            <Column field="code" header="Código" />
            <Column header="Cliente">
                <template #body="{ data }">{{ data.user?.name || '—' }}</template>
            </Column>
            <Column header="Quarto">
                <template #body="{ data }">{{ data.room_type?.name || '—' }}</template>
            </Column>
            <Column header="Entrada">
                <template #body="{ data }">{{ formatDate(data.check_in) }}</template>
            </Column>
            <Column header="Saída">
                <template #body="{ data }">{{ formatDate(data.check_out) }}</template>
            </Column>
            <Column header="Total">
                <template #body="{ data }">{{ formatMt(data.total_price) }}</template>
            </Column>
            <Column header="Estado">
                <template #body="{ data }">{{ data.status_label || data.status }}</template>
            </Column>
            <Column>
                <template #body="{ data }">
                    <Button icon="pi pi-eye" text rounded @click="router.push({ name: 'business.bookings.show', params: { id: data.id } })" />
                </template>
            </Column>
        </DataTable>
    </div>
</template>
