<script setup>
import { onMounted, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import http, { apiError, apiPayload } from '@/api/http';
import { formatMt } from '@/utils/format';

const toast = useToast();
const loading = ref(true);
const wallets = ref([]);
const pending = ref([]);
const creditDialog = ref(false);
const creditForm = ref({ userId: null, name: '', amount: 200, method: 'cash_office', notes: '' });
const typeDialog = ref(false);
const typeForm = ref({ userId: null, name: '', driver_type: 'independent', min_balance: null });

const load = async () => {
    loading.value = true;
    try {
        const w = apiPayload(await http.get('/driver-wallets', { params: { per_page: 50 } }));
        wallets.value = w?.data || w || [];
        const p = apiPayload(await http.get('/driver-wallets/pending-topups', { params: { per_page: 50 } }));
        pending.value = p?.data || p || [];
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível carregar.'), life: 4000 });
    } finally {
        loading.value = false;
    }
};

const openCredit = (row) => {
    creditForm.value = {
        userId: row.user_id || row.user?.id,
        name: row.user?.name || `#${row.user_id}`,
        amount: 200,
        method: 'cash_office',
        notes: '',
    };
    creditDialog.value = true;
};

const submitCredit = async () => {
    try {
        await http.post(`/driver-wallets/${creditForm.value.userId}/credit`, {
            amount: creditForm.value.amount,
            method: creditForm.value.method,
            notes: creditForm.value.notes || undefined,
        });
        toast.add({ severity: 'success', summary: 'Saldo creditado', life: 2500 });
        creditDialog.value = false;
        await load();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Falha ao creditar.'), life: 4000 });
    }
};

const openType = (row) => {
    typeForm.value = {
        userId: row.user_id || row.user?.id,
        name: row.user?.name || `#${row.user_id}`,
        driver_type: row.driver_type || 'independent',
        min_balance: row.min_balance,
    };
    typeDialog.value = true;
};

const submitType = async () => {
    try {
        await http.post(`/driver-wallets/${typeForm.value.userId}/type`, {
            driver_type: typeForm.value.driver_type,
            min_balance: typeForm.value.min_balance || null,
        });
        toast.add({ severity: 'success', summary: 'Tipo actualizado', life: 2500 });
        typeDialog.value = false;
        await load();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Falha ao actualizar.'), life: 4000 });
    }
};

const confirmTopup = async (row) => {
    try {
        await http.post(`/driver-wallet-topups/${row.id}/confirm`);
        toast.add({ severity: 'success', summary: 'Recarga confirmada', life: 2500 });
        await load();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Falha ao confirmar.'), life: 4000 });
    }
};

const rejectTopup = async (row) => {
    try {
        await http.post(`/driver-wallet-topups/${row.id}/reject`, { notes: 'Rejeitada no portal' });
        toast.add({ severity: 'warn', summary: 'Recarga rejeitada', life: 2500 });
        await load();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Falha ao rejeitar.'), life: 4000 });
    }
};

onMounted(load);
</script>

<template>
    <div>
        <div class="card mb-4">
            <h2 class="m-0">Carteiras dos motoristas</h2>
            <p class="text-muted-color mt-2 mb-0">
                Independentes: saldo pré-pago. Comissão Kanguro é debitada por viagem; abaixo do mínimo não recebem corridas.
            </p>
        </div>

        <div v-if="loading" class="card">A carregar…</div>
        <template v-else>
            <div class="card mb-4">
                <h3 class="mt-0">Recargas pendentes</h3>
                <DataTable :value="pending" emptyMessage="Sem pedidos pendentes">
                    <Column header="Motorista">
                        <template #body="{ data }">{{ data.user?.name || data.user_id }}</template>
                    </Column>
                    <Column header="Valor">
                        <template #body="{ data }">{{ formatMt(data.amount) }}</template>
                    </Column>
                    <Column field="method" header="Método" />
                    <Column field="external_ref" header="Referência" />
                    <Column field="notes" header="Notas" />
                    <Column>
                        <template #body="{ data }">
                            <div class="flex gap-2">
                                <Button label="Confirmar" size="small" @click="confirmTopup(data)" />
                                <Button label="Rejeitar" size="small" severity="secondary" @click="rejectTopup(data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>

            <div class="card">
                <h3 class="mt-0">Saldos</h3>
                <DataTable :value="wallets" emptyMessage="Sem carteiras">
                    <Column header="Motorista">
                        <template #body="{ data }">{{ data.user?.name || data.user_id }}</template>
                    </Column>
                    <Column field="driver_type" header="Tipo" />
                    <Column header="Saldo">
                        <template #body="{ data }">{{ formatMt(data.balance) }}</template>
                    </Column>
                    <Column header="Mínimo">
                        <template #body="{ data }">{{ formatMt(data.effective_min_balance ?? data.min_balance) }}</template>
                    </Column>
                    <Column header="Pode receber">
                        <template #body="{ data }">
                            <Tag :severity="data.can_receive_jobs ? 'success' : 'danger'" :value="data.can_receive_jobs ? 'Sim' : 'Recarregar'" />
                        </template>
                    </Column>
                    <Column>
                        <template #body="{ data }">
                            <div class="flex gap-2">
                                <Button label="Creditar" size="small" @click="openCredit(data)" />
                                <Button label="Tipo" size="small" severity="secondary" @click="openType(data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </template>

        <Dialog v-model:visible="creditDialog" header="Creditar saldo" modal style="width: 28rem">
            <p class="mt-0">{{ creditForm.name }}</p>
            <div class="flex flex-column gap-3">
                <div>
                    <label class="block mb-1">Valor (MT)</label>
                    <InputNumber v-model="creditForm.amount" :min="50" class="w-full" />
                </div>
                <div>
                    <label class="block mb-1">Método</label>
                    <Select
                        v-model="creditForm.method"
                        :options="[
                            { label: 'Escritório (cash)', value: 'cash_office' },
                            { label: 'M-Pesa', value: 'mpesa' },
                            { label: 'e-Mola', value: 'emola' },
                            { label: 'Cartão', value: 'card' },
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full"
                    />
                </div>
                <div>
                    <label class="block mb-1">Notas</label>
                    <InputText v-model="creditForm.notes" class="w-full" />
                </div>
            </div>
            <template #footer>
                <Button label="Cancelar" severity="secondary" @click="creditDialog = false" />
                <Button label="Creditar" @click="submitCredit" />
            </template>
        </Dialog>

        <Dialog v-model:visible="typeDialog" header="Tipo de motorista" modal style="width: 28rem">
            <p class="mt-0">{{ typeForm.name }}</p>
            <div class="flex flex-column gap-3">
                <div>
                    <label class="block mb-1">Tipo</label>
                    <Select
                        v-model="typeForm.driver_type"
                        :options="[
                            { label: 'Independente (carteira pré-paga)', value: 'independent' },
                            { label: 'Frota / escritório (fecho de conta)', value: 'fleet' },
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full"
                    />
                </div>
                <div>
                    <label class="block mb-1">Mínimo personalizado (opcional)</label>
                    <InputNumber v-model="typeForm.min_balance" :min="0" class="w-full" />
                </div>
            </div>
            <template #footer>
                <Button label="Cancelar" severity="secondary" @click="typeDialog = false" />
                <Button label="Guardar" @click="submitType" />
            </template>
        </Dialog>
    </div>
</template>
