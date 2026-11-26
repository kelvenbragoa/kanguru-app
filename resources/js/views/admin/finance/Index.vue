<script setup>
import { onMounted, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import http, { apiError, apiPayload } from '@/api/http';
import { formatDate, formatMt } from '@/utils/format';

const toast = useToast();
const loading = ref(true);
const summary = ref({});
const payouts = ref([]);
const creating = ref(false);
const period = ref({
    period_start: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10),
    period_end: new Date().toISOString().slice(0, 10),
});

const load = async () => {
    loading.value = true;
    try {
        summary.value = apiPayload(await http.get('/finance/summary')) || {};
        const payload = apiPayload(await http.get('/finance/payouts', { params: { per_page: 50 } }));
        payouts.value = payload?.data || payload || [];
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível carregar.'), life: 4000 });
    } finally {
        loading.value = false;
    }
};

const createCycles = async () => {
    creating.value = true;
    try {
        await http.post('/finance/payouts', period.value);
        toast.add({ severity: 'success', summary: 'Ciclos criados', life: 2500 });
        await load();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível criar ciclos.'), life: 4000 });
    } finally {
        creating.value = false;
    }
};

const markPaid = async (row) => {
    try {
        await http.post(`/finance/payouts/${row.id}/pay`);
        toast.add({ severity: 'success', summary: 'Pago', life: 2500 });
        await load();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível marcar como pago.'), life: 4000 });
    }
};

onMounted(load);
</script>

<template>
    <div>
        <div class="card mb-4">
            <h2 class="m-0">Finanças</h2>
            <p class="text-muted-color mt-2 mb-0">Comissões, valores devidos e ciclos de pagamento (todo o país).</p>
        </div>

        <div v-if="loading" class="card">A carregar…</div>
        <template v-else>
            <div class="grid grid-cols-12 gap-4 mb-4">
                <div class="col-span-12 md:col-span-3 card mb-0">
                    <div class="text-muted-color">Devido a parceiros</div>
                    <div class="text-3xl font-bold mt-2">{{ formatMt(summary.partner_payable) }}</div>
                </div>
                <div class="col-span-12 md:col-span-3 card mb-0">
                    <div class="text-muted-color">Devido a estafetas/motoristas</div>
                    <div class="text-3xl font-bold mt-2">{{ formatMt(summary.courier_payable) }}</div>
                </div>
                <div class="col-span-12 md:col-span-3 card mb-0">
                    <div class="text-muted-color">Receita plataforma</div>
                    <div class="text-3xl font-bold mt-2">{{ formatMt(summary.platform_revenue) }}</div>
                </div>
                <div class="col-span-12 md:col-span-3 card mb-0">
                    <div class="text-muted-color">Ciclos abertos</div>
                    <div class="text-3xl font-bold mt-2">{{ summary.open_payouts || 0 }}</div>
                </div>
            </div>

            <div class="card mb-4">
                <h3 class="mt-0">Criar ciclo de pagamento</h3>
                <div class="flex flex-wrap gap-3 items-end">
                    <div>
                        <label class="block mb-1">De</label>
                        <InputText v-model="period.period_start" type="date" />
                    </div>
                    <div>
                        <label class="block mb-1">Até</label>
                        <InputText v-model="period.period_end" type="date" />
                    </div>
                    <Button label="Gerar ciclos" :loading="creating" @click="createCycles" />
                </div>
            </div>

            <div class="card">
                <h3 class="mt-0">Ciclos</h3>
                <DataTable :value="payouts">
                    <Column header="Beneficiário">
                        <template #body="{ data }">{{ data.beneficiary_label }}</template>
                    </Column>
                    <Column field="beneficiary_type" header="Tipo" />
                    <Column header="Período">
                        <template #body="{ data }">{{ formatDate(data.period_start) }} → {{ formatDate(data.period_end) }}</template>
                    </Column>
                    <Column header="Valor">
                        <template #body="{ data }">{{ formatMt(data.amount) }}</template>
                    </Column>
                    <Column field="status_label" header="Estado" />
                    <Column>
                        <template #body="{ data }">
                            <Button v-if="data.status === 'open'" label="Marcar pago" size="small" @click="markPaid(data)" />
                        </template>
                    </Column>
                </DataTable>
            </div>
        </template>
    </div>
</template>
