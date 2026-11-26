<script setup>
import { onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import http, { apiError, apiPayload } from '@/api/http';
import { formatDate, formatMt, statusLabel, statusSeverity } from '@/utils/format';

const route = useRoute();
const router = useRouter();
const toast = useToast();
const loading = ref(true);
const orders = ref([]);
const total = ref(0);
const page = ref(1);

const load = async () => {
    loading.value = true;
    try {
        const response = await http.get('/business/orders', {
            params: {
                page: page.value,
                per_page: 20,
                inbox: route.query.inbox ? 1 : undefined,
            },
        });
        const payload = apiPayload(response);
        orders.value = payload.data || [];
        total.value = payload.total || 0;
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível carregar.'), life: 4000 });
    } finally {
        loading.value = false;
    }
};

watch(() => route.query.inbox, () => {
    page.value = 1;
    load();
});

onMounted(load);
</script>

<template>
    <div>
        <div class="card">
            <div class="flex flex-wrap justify-between gap-3 mb-4">
                <div>
                    <h2 class="m-0">Pedidos</h2>
                    <p class="text-muted-color mt-2 mb-0">{{ route.query.inbox ? 'Inbox — novos e em preparação' : 'Todos os pedidos da loja' }}</p>
                </div>
                <div class="flex gap-2">
                    <Button
                        :label="route.query.inbox ? 'Ver todos' : 'Só inbox'"
                        outlined
                        @click="router.push({ name: 'business.orders', query: route.query.inbox ? {} : { inbox: 1 } })"
                    />
                </div>
            </div>

            <DataTable :value="orders" :loading="loading" paginator :rows="20" :totalRecords="total" lazy @page="(e) => { page = e.page + 1; load(); }">
                <Column field="code" header="Código" />
                <Column header="Cliente">
                    <template #body="{ data }">{{ data.user?.name || '—' }}</template>
                </Column>
                <Column header="Estado">
                    <template #body="{ data }">
                        <Tag :value="statusLabel(data.order_status)" :severity="statusSeverity(data.order_status)" />
                    </template>
                </Column>
                <Column header="Total">
                    <template #body="{ data }">{{ formatMt(data.total_price) }}</template>
                </Column>
                <Column header="Quando">
                    <template #body="{ data }">{{ formatDate(data.created_at) }}</template>
                </Column>
                <Column>
                    <template #body="{ data }">
                        <Button icon="pi pi-eye" text rounded @click="router.push({ name: 'business.orders.show', params: { id: data.id } })" />
                    </template>
                </Column>
            </DataTable>
        </div>
    </div>
</template>
