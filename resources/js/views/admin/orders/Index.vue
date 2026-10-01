<script setup>
import { onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import http, { unwrapPage } from '@/api/http';
import { formatDate, formatMt, STATUS_OPTIONS, statusLabel, statusSeverity, toDateParam, TYPE_OPTIONS } from '@/utils/format';

const router = useRouter();
const loading = ref(false);
const orders = ref([]);
const total = ref(0);
const page = ref(1);
const rows = ref(15);
const status = ref(null);
const type = ref(null);
const search = ref('');
const dateFrom = ref(null);
const dateTo = ref(null);
const unassigned = ref(false);

const load = async (nextPage = 1) => {
    loading.value = true;
    page.value = nextPage;
    try {
        const response = await http.get('/orders', {
            params: {
                page: nextPage,
                per_page: rows.value,
                ...(status.value ? { status: status.value } : {}),
                ...(type.value ? { type: type.value } : {}),
                ...(search.value ? { search: search.value } : {}),
                ...(toDateParam(dateFrom.value) ? { date_from: toDateParam(dateFrom.value) } : {}),
                ...(toDateParam(dateTo.value) ? { date_to: toDateParam(dateTo.value) } : {}),
                ...(unassigned.value ? { unassigned: 1 } : {}),
            },
        });
        const payload = unwrapPage(response.data);
        orders.value = payload.items;
        total.value = payload.total;
    } finally {
        loading.value = false;
    }
};

const onPage = (event) => {
    rows.value = event.rows;
    load(event.page + 1);
};

watch([status, type, unassigned], () => load(1));
onMounted(() => load(1));
</script>

<template>
    <div class="card">
        <div class="flex flex-col gap-3 mb-4">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <h2 class="m-0">Pedidos</h2>
                <Button label="Novo pedido" icon="pi pi-plus" @click="router.push({ name: 'orders.create' })" />
            </div>
            <div class="flex flex-col lg:flex-row gap-2">
                <InputText v-model="search" placeholder="Código, cliente, origem..." class="w-full lg:w-18rem" @keyup.enter="load(1)" />
                <Select v-model="status" :options="STATUS_OPTIONS" optionLabel="label" optionValue="value" placeholder="Estado" showClear class="w-full lg:w-14rem" />
                <Select v-model="type" :options="TYPE_OPTIONS" optionLabel="label" optionValue="value" placeholder="Tipo" showClear class="w-full lg:w-14rem" />
                <DatePicker v-model="dateFrom" dateFormat="dd/mm/yy" placeholder="De" showIcon class="w-full lg:w-12rem" />
                <DatePicker v-model="dateTo" dateFormat="dd/mm/yy" placeholder="Até" showIcon class="w-full lg:w-12rem" />
                <div class="flex items-center gap-2">
                    <ToggleSwitch v-model="unassigned" />
                    <span class="text-sm">Sem motorista</span>
                </div>
                <Button icon="pi pi-search" @click="load(1)" />
            </div>
        </div>

        <DataTable
            :value="orders"
            :loading="loading"
            dataKey="id"
            lazy
            paginator
            :first="(page - 1) * rows"
            :rows="rows"
            :totalRecords="total"
            :rowsPerPageOptions="[10, 15, 25, 50]"
            @page="onPage"
            responsiveLayout="scroll"
        >
            <Column field="code" header="Código" />
            <Column header="Cliente">
                <template #body="{ data }">{{ data.user?.name || '—' }}</template>
            </Column>
            <Column header="Tipo">
                <template #body="{ data }">{{ data.order_type?.name || '—' }}</template>
            </Column>
            <Column header="Origem">
                <template #body="{ data }">{{ data.origin }}</template>
            </Column>
            <Column header="Destino">
                <template #body="{ data }">{{ data.destination }}</template>
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
</template>
