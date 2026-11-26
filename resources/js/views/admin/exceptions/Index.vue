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
const status = ref('open');
const resolveVisible = ref(false);
const resolving = ref(false);
const selected = ref(null);
const form = ref({ cost_bearer: 'none', cost_amount: 0, resolution_notes: '' });

const load = async () => {
    loading.value = true;
    try {
        const payload = apiPayload(await http.get('/order-exceptions', { params: { status: status.value || undefined, per_page: 50 } }));
        items.value = payload?.data || payload || [];
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível carregar.'), life: 4000 });
    } finally {
        loading.value = false;
    }
};

const openResolve = (row) => {
    selected.value = row;
    form.value = {
        cost_bearer: row.cost_bearer || 'none',
        cost_amount: Number(row.cost_amount || 0),
        resolution_notes: '',
    };
    resolveVisible.value = true;
};

const resolve = async () => {
    if (!selected.value) return;
    resolving.value = true;
    try {
        await http.post(`/order-exceptions/${selected.value.id}/resolve`, form.value);
        toast.add({ severity: 'success', summary: 'Resolvida', life: 2500 });
        resolveVisible.value = false;
        await load();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível resolver.'), life: 4000 });
    } finally {
        resolving.value = false;
    }
};

onMounted(load);
</script>

<template>
    <div class="card">
        <div class="flex flex-wrap justify-between gap-3 mb-4">
            <div>
                <h2 class="m-0">Excepções</h2>
                <p class="text-muted-color mt-2 mb-0">Registos do PDF para definir quem assume o custo.</p>
            </div>
            <Select
                v-model="status"
                :options="[
                    { value: 'open', label: 'Abertas' },
                    { value: 'resolved', label: 'Resolvidas' },
                    { value: '', label: 'Todas' },
                ]"
                optionLabel="label"
                optionValue="value"
                @change="load"
            />
        </div>

        <DataTable :value="items" :loading="loading">
            <Column header="Pedido">
                <template #body="{ data }">
                    <Button
                        v-if="data.order_id"
                        :label="data.order?.code || `#${data.order_id}`"
                        text
                        @click="router.push({ name: 'orders.show', params: { id: data.order_id } })"
                    />
                </template>
            </Column>
            <Column field="type_label" header="Tipo" />
            <Column field="cost_bearer_label" header="Custo" />
            <Column header="Valor">
                <template #body="{ data }">{{ formatMt(data.cost_amount) }}</template>
            </Column>
            <Column field="status_label" header="Estado" />
            <Column header="Quando">
                <template #body="{ data }">{{ formatDate(data.created_at) }}</template>
            </Column>
            <Column>
                <template #body="{ data }">
                    <Button v-if="data.status === 'open'" label="Resolver" size="small" @click="openResolve(data)" />
                </template>
            </Column>
        </DataTable>

        <Dialog v-model:visible="resolveVisible" header="Resolver excepção" modal :style="{ width: '28rem' }">
            <div class="flex flex-col gap-3">
                <div>
                    <label class="block mb-1">Quem assume</label>
                    <Select
                        v-model="form.cost_bearer"
                        :options="[
                            { value: 'none', label: 'Ninguém' },
                            { value: 'customer', label: 'Cliente' },
                            { value: 'partner', label: 'Parceiro' },
                            { value: 'kanguro', label: 'Kanguro' },
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full"
                    />
                </div>
                <div>
                    <label class="block mb-1">Valor (MT)</label>
                    <InputNumber v-model="form.cost_amount" class="w-full" :min="0" />
                </div>
                <div>
                    <label class="block mb-1">Notas</label>
                    <Textarea v-model="form.resolution_notes" rows="3" class="w-full" />
                </div>
            </div>
            <template #footer>
                <Button label="Fechar" text @click="resolveVisible = false" />
                <Button label="Resolver" :loading="resolving" @click="resolve" />
            </template>
        </Dialog>
    </div>
</template>
