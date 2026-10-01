<script setup>
import { onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import http, { apiError, unwrapPage } from '@/api/http';
import { formatDate, formatMt, paymentLabel, PAYMENT_METHOD_OPTIONS, PAYMENT_STATUS_OPTIONS } from '@/utils/format';

const router = useRouter();
const toast = useToast();
const confirm = useConfirm();
const loading = ref(false);
const payments = ref([]);
const total = ref(0);
const page = ref(1);
const rows = ref(15);
const status = ref(null);
const method = ref(null);
const search = ref('');

const load = async (nextPage = 1) => {
    loading.value = true;
    page.value = nextPage;
    try {
        const response = await http.get('/payments', {
            params: {
                page: nextPage,
                per_page: rows.value,
                ...(status.value ? { status: status.value } : {}),
                ...(method.value ? { payment_method: method.value } : {}),
                ...(search.value ? { search: search.value } : {}),
            },
        });
        const payload = unwrapPage(response.data);
        payments.value = payload.items;
        total.value = payload.total;
    } finally {
        loading.value = false;
    }
};

const onPage = (event) => {
    rows.value = event.rows;
    load(event.page + 1);
};

const confirmPayment = (row) => {
    confirm.require({
        header: 'Confirmar pagamento',
        message: `Marcar ${formatMt(row.amount)} de ${row.order?.code || 'este pedido'} como pago?`,
        acceptLabel: 'Confirmar',
        rejectLabel: 'Voltar',
        accept: async () => {
            try {
                await http.post(`/payments/${row.id}/confirm`);
                toast.add({ severity: 'success', summary: 'Pago', life: 2500 });
                await load(page.value);
            } catch (error) {
                toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível confirmar.'), life: 4000 });
            }
        },
    });
};

const refundPayment = (row) => {
    confirm.require({
        header: 'Reembolsar',
        message: `Reembolsar ${formatMt(row.amount)} do pedido ${row.order?.code || ''}?`,
        acceptLabel: 'Reembolsar',
        rejectLabel: 'Voltar',
        acceptClass: 'p-button-danger',
        accept: async () => {
            try {
                await http.post(`/payments/${row.id}/refund`, { reason: 'Reembolso pela operação YALA' });
                toast.add({ severity: 'success', summary: 'Reembolsado', life: 2500 });
                await load(page.value);
            } catch (error) {
                toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível reembolsar.'), life: 4000 });
            }
        },
    });
};

watch([status, method], () => load(1));
onMounted(() => load(1));
</script>

<template>
    <div class="card">
        <div class="flex flex-col gap-3 mb-4">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div>
                    <h2 class="m-0">Pagamentos</h2>
                    <p class="text-muted-color mt-1 mb-0">M-Pesa, e-Mola, cartão e dinheiro na entrega.</p>
                </div>
            </div>
            <div class="flex flex-col md:flex-row gap-2">
                <InputText v-model="search" placeholder="Código ou cliente" class="w-full md:w-16rem" @keyup.enter="load(1)" />
                <Select v-model="status" :options="PAYMENT_STATUS_OPTIONS" optionLabel="label" optionValue="value" placeholder="Estado" showClear class="w-full md:w-14rem" />
                <Select v-model="method" :options="PAYMENT_METHOD_OPTIONS" optionLabel="label" optionValue="value" placeholder="Método" showClear class="w-full md:w-14rem" />
                <Button icon="pi pi-search" @click="load(1)" />
            </div>
        </div>

        <DataTable
            :value="payments"
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
            <Column header="Pedido">
                <template #body="{ data }">{{ data.order?.code || '—' }}</template>
            </Column>
            <Column header="Cliente">
                <template #body="{ data }">{{ data.order?.user?.name || '—' }}</template>
            </Column>
            <Column header="Método">
                <template #body="{ data }">{{ data.method_label || paymentLabel(data.payment_method) }}</template>
            </Column>
            <Column header="Estado">
                <template #body="{ data }">
                    <Tag :value="paymentLabel(data.status)" :severity="data.status === 'completed' ? 'success' : data.status === 'pending' ? 'warn' : 'danger'" />
                </template>
            </Column>
            <Column header="Valor">
                <template #body="{ data }">{{ formatMt(data.amount) }}</template>
            </Column>
            <Column header="Data">
                <template #body="{ data }">{{ formatDate(data.created_at) }}</template>
            </Column>
            <Column>
                <template #body="{ data }">
                    <Button v-if="data.status === 'pending'" icon="pi pi-check" text rounded v-tooltip.top="'Confirmar'" @click="confirmPayment(data)" />
                    <Button v-if="data.status === 'completed'" icon="pi pi-undo" text rounded severity="warn" v-tooltip.top="'Reembolsar'" @click="refundPayment(data)" />
                    <Button v-if="data.order_id" icon="pi pi-eye" text rounded @click="router.push({ name: 'orders.show', params: { id: data.order_id } })" />
                </template>
            </Column>
        </DataTable>
    </div>
</template>
