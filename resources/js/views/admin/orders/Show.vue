<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import http, { apiError, apiPayload } from '@/api/http';
import { useAuth } from '@/composables/useAuth';
import LiveMap from '@/components/LiveMap.vue';
import {
    canCancelOrder,
    formatDate,
    formatMt,
    isFinalStatus,
    paymentLabel,
    statusLabel,
    statusSeverity,
} from '@/utils/format';

const route = useRoute();
const router = useRouter();
const toast = useToast();
const confirm = useConfirm();
const { isStaff } = useAuth();

const loading = ref(true);
const order = ref(null);
const assignVisible = ref(false);
const statusVisible = ref(false);
const shopAcceptVisible = ref(false);
const assigning = ref(false);
const updating = ref(false);
const shopWorking = ref(false);
const drivers = ref([]);
const vehicles = ref([]);
const statuses = ref([]);
const driverId = ref(null);
const vehicleId = ref(null);
const nextStatusId = ref(null);
const statusNote = ref('');
const preparationMinutes = ref(20);
const exceptions = ref([]);
const exceptionTypes = ref([]);
const exceptionVisible = ref(false);
const resolvingException = ref(false);
const exceptionForm = ref({ type: 'other', description: '', cost_bearer: 'none', cost_amount: 0 });
let timer = null;

const canCancel = computed(() => isStaff.value && canCancelOrder(order.value));
const needsDriver = computed(
    () => order.value && !order.value.agent_user_id && !isFinalStatus(order.value.order_status)
);
const canChangeStatus = computed(() => isStaff.value && order.value && !isFinalStatus(order.value.order_status));
const isShopOrder = computed(() => Boolean(order.value?.shop_id));
const statusName = computed(() => order.value?.order_status?.name);
const canShopAccept = computed(
    () => isStaff.value && isShopOrder.value && ['pending', 'confirmed'].includes(statusName.value)
);
const canShopReady = computed(
    () => isStaff.value && isShopOrder.value && ['preparing', 'confirmed'].includes(statusName.value)
);
const location = computed(() => order.value?.current_location || null);

const loadExceptions = async () => {
    try {
        exceptions.value = apiPayload(await http.get(`/orders/${route.params.id}/exceptions`)) || [];
    } catch {
        exceptions.value = [];
    }
};

const load = async (silent = false) => {
    if (!silent) loading.value = true;
    try {
        const response = await http.get(`/orders/${route.params.id}`);
        order.value = apiPayload(response);
        if (!silent) await loadExceptions();
    } catch {
        if (!silent) {
            toast.add({ severity: 'error', summary: 'Erro', detail: 'Pedido não encontrado.', life: 3000 });
            router.push({ name: 'orders.index' });
        }
    } finally {
        loading.value = false;
    }
};

const openException = async () => {
    if (!exceptionTypes.value.length) {
        try {
            exceptionTypes.value = apiPayload(await http.get('/order-exceptions/types')) || [];
        } catch {
            exceptionTypes.value = [];
        }
    }
    exceptionForm.value = { type: 'other', description: '', cost_bearer: 'none', cost_amount: 0 };
    exceptionVisible.value = true;
};

const saveException = async () => {
    resolvingException.value = true;
    try {
        const selected = exceptionTypes.value.find((t) => t.type === exceptionForm.value.type);
        await http.post('/order-exceptions', {
            order_id: Number(route.params.id),
            ...exceptionForm.value,
            cost_bearer: exceptionForm.value.cost_bearer || selected?.default_cost_bearer || 'none',
        });
        toast.add({ severity: 'success', summary: 'Excepção registada', life: 2500 });
        exceptionVisible.value = false;
        await loadExceptions();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível registar.'), life: 4000 });
    } finally {
        resolvingException.value = false;
    }
};

const resolveException = async (item) => {
    resolvingException.value = true;
    try {
        await http.post(`/order-exceptions/${item.id}/resolve`, {
            cost_bearer: item.cost_bearer,
            cost_amount: item.cost_amount,
            resolution_notes: 'Resolvida na ficha do pedido',
        });
        toast.add({ severity: 'success', summary: 'Resolvida', life: 2500 });
        await loadExceptions();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível resolver.'), life: 4000 });
    } finally {
        resolvingException.value = false;
    }
};

const openAssign = async () => {
    assignVisible.value = true;
    const [driversRes, vehiclesRes] = await Promise.all([http.get('/drivers/available'), http.get('/vehicles-available')]);
    drivers.value = apiPayload(driversRes) || [];
    vehicles.value = apiPayload(vehiclesRes) || [];
};

const assign = async () => {
    if (!driverId.value || !vehicleId.value) {
        toast.add({ severity: 'warn', summary: 'Atenção', detail: 'Escolha motorista e veículo.', life: 3000 });
        return;
    }
    assigning.value = true;
    try {
        await http.post(`/orders/${order.value.id}/assign-driver`, {
            driver_id: driverId.value,
            vehicle_id: vehicleId.value,
        });
        toast.add({ severity: 'success', summary: 'Atribuído', detail: 'Motorista atribuído ao pedido.', life: 3000 });
        assignVisible.value = false;
        await load();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível atribuir.'), life: 4000 });
    } finally {
        assigning.value = false;
    }
};

const retryDispatch = async () => {
    try {
        const response = await http.post(`/dispatch/orders/${order.value.id}/retry`);
        const driver = apiPayload(response).driver;
        toast.add({
            severity: driver ? 'success' : 'warn',
            summary: driver ? 'Atribuído' : 'Sem motorista',
            detail: driver ? `${driver.name} ficou com o pedido.` : 'Nenhum motorista online.',
            life: 3500,
        });
        await load();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível despachar.'), life: 4000 });
    }
};

const openStatus = async () => {
    if (!statuses.value.length) {
        const response = await http.get('/catalog/options');
        statuses.value = (apiPayload(response).order_statuses || []).filter((item) => item.id !== order.value?.order_status_id);
    }
    nextStatusId.value = null;
    statusNote.value = '';
    statusVisible.value = true;
};

const updateStatus = async () => {
    if (!nextStatusId.value) return;
    updating.value = true;
    try {
        const selected = statuses.value.find((item) => item.id === nextStatusId.value);
        await http.put(`/tracking/orders/${order.value.id}/status`, {
            order_status_id: nextStatusId.value,
            description: statusNote.value || selected?.display_name || 'Actualização pela operação',
            local: order.value.destination || order.value.origin || 'Maputo',
        });
        toast.add({ severity: 'success', summary: 'Estado actualizado', life: 2500 });
        statusVisible.value = false;
        await load();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível actualizar.'), life: 4000 });
    } finally {
        updating.value = false;
    }
};

const confirmPayment = (row) => {
    confirm.require({
        message: `Confirmar pagamento de ${formatMt(row.amount)}?`,
        header: 'Confirmar pagamento',
        acceptLabel: 'Confirmar',
        rejectLabel: 'Voltar',
        accept: async () => {
            try {
                await http.post(`/payments/${row.id}/confirm`);
                toast.add({ severity: 'success', summary: 'Pago', life: 2500 });
                await load();
            } catch (error) {
                toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível confirmar.'), life: 4000 });
            }
        },
    });
};

const refundPayment = (row) => {
    confirm.require({
        message: `Reembolsar ${formatMt(row.amount)}?`,
        header: 'Reembolsar',
        acceptLabel: 'Reembolsar',
        rejectLabel: 'Voltar',
        acceptClass: 'p-button-danger',
        accept: async () => {
            try {
                await http.post(`/payments/${row.id}/refund`, { reason: 'Reembolso pela operação' });
                toast.add({ severity: 'success', summary: 'Reembolsado', life: 2500 });
                await load();
            } catch (error) {
                toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível reembolsar.'), life: 4000 });
            }
        },
    });
};

const cancelOrder = () => {
    confirm.require({
        message: `Cancelar o pedido ${order.value.code}?`,
        header: 'Confirmar cancelamento',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Cancelar pedido',
        rejectLabel: 'Voltar',
        acceptClass: 'p-button-danger',
        accept: async () => {
            try {
                await http.post(`/orders/${order.value.id}/cancel`);
                toast.add({ severity: 'success', summary: 'Cancelado', detail: 'O pedido foi cancelado.', life: 3000 });
                await load();
            } catch (error) {
                toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível cancelar.'), life: 4000 });
            }
        },
    });
};

const openShopAccept = () => {
    preparationMinutes.value = order.value?.preparation_minutes || 20;
    shopAcceptVisible.value = true;
};

const shopAccept = async () => {
    shopWorking.value = true;
    try {
        await http.post(`/orders/${order.value.id}/shop-accept`, {
            preparation_minutes: preparationMinutes.value,
        });
        toast.add({ severity: 'success', summary: 'Aceite', detail: 'Loja aceitou o pedido.', life: 3000 });
        shopAcceptVisible.value = false;
        await load();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível aceitar.'), life: 4000 });
    } finally {
        shopWorking.value = false;
    }
};

const shopReady = async () => {
    shopWorking.value = true;
    try {
        await http.post(`/orders/${order.value.id}/shop-ready`);
        toast.add({ severity: 'success', summary: 'Pronto', detail: 'Pedido marcado como pronto.', life: 3000 });
        await load();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível marcar pronto.'), life: 4000 });
    } finally {
        shopWorking.value = false;
    }
};

onMounted(() => {
    load();
    timer = window.setInterval(() => {
        if (order.value && !isFinalStatus(order.value.order_status)) {
            load(true);
        }
    }, 12000);
});
onUnmounted(() => timer && window.clearInterval(timer));
</script>

<template>
    <div>
        <Button icon="pi pi-arrow-left" label="Pedidos" text class="mb-3" @click="router.push({ name: 'orders.index' })" />

        <div v-if="loading" class="card">A carregar…</div>

        <div v-else-if="order" class="flex flex-col gap-4">
            <div class="card">
                <div class="flex flex-wrap justify-between gap-3">
                    <div>
                        <h2 class="m-0">{{ order.code }}</h2>
                        <p class="text-muted-color mt-2 mb-0">{{ formatDate(order.created_at) }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2 items-start">
                        <Tag :value="statusLabel(order.order_status)" :severity="statusSeverity(order.order_status)" />
                        <Button v-if="canShopAccept" label="Aceitar loja" icon="pi pi-check" severity="success" @click="openShopAccept" />
                        <Button v-if="canShopReady" label="Marcar pronto" icon="pi pi-box" :loading="shopWorking" @click="shopReady" />
                        <Button v-if="needsDriver" label="Despachar" icon="pi pi-bolt" severity="secondary" @click="retryDispatch" />
                        <Button v-if="needsDriver" label="Atribuir motorista" icon="pi pi-user" @click="openAssign" />
                        <Button v-if="canChangeStatus" label="Alterar estado" icon="pi pi-sync" outlined @click="openStatus" />
                        <Button v-if="canCancel" label="Cancelar" icon="pi pi-times" severity="danger" outlined @click="cancelOrder" />
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-12 lg:col-span-6 card mb-0">
                    <h3>Percurso</h3>
                    <p><strong>Tipo:</strong> {{ order.order_type?.name }}</p>
                    <p><strong>Origem:</strong> {{ order.origin }}</p>
                    <p><strong>Destino:</strong> {{ order.destination }}</p>
                    <p v-if="order.notes"><strong>Notas:</strong> {{ order.notes }}</p>
                    <p v-if="order.delivery_code"><strong>Código de entrega:</strong> {{ order.delivery_code }}</p>
                    <p v-if="order.preparation_minutes"><strong>Preparação:</strong> {{ order.preparation_minutes }} min</p>
                    <p v-if="order.courier_call_at"><strong>Chamada estafeta:</strong> {{ formatDate(order.courier_call_at) }}</p>
                    <p><strong>Total:</strong> {{ formatMt(order.total_price) }}</p>
                    <p><strong>Taxa de entrega:</strong> {{ formatMt(order.delivery_fee) }}</p>
                </div>
                <div class="col-span-12 lg:col-span-6 card mb-0">
                    <h3>Pessoas</h3>
                    <p><strong>Cliente:</strong> {{ order.user?.name }} · {{ order.user?.email }}</p>
                    <p><strong>Motorista:</strong> {{ order.agent?.name || 'Ainda sem motorista' }}</p>
                    <p><strong>Veículo:</strong> {{ order.vehicle?.license_plate_number || '—' }}</p>
                    <p v-if="order.shop"><strong>Loja:</strong> {{ order.shop.name }}</p>
                </div>
            </div>

            <div class="card">
                <h3>Localização</h3>
                <LiveMap
                    :latitude="location?.latitude ?? null"
                    :longitude="location?.longitude ?? null"
                    :updated-at="location?.updated_at ? formatDate(location.updated_at) : null"
                    :label="order.agent?.name || 'Motorista'"
                />
            </div>

            <div v-if="order.order_items?.length" class="card">
                <h3>Itens</h3>
                <DataTable :value="order.order_items">
                    <Column header="Produto">
                        <template #body="{ data }">{{ data.product?.name || data.name || '—' }}</template>
                    </Column>
                    <Column field="quantity" header="Qtd" />
                    <Column header="Preço">
                        <template #body="{ data }">{{ formatMt(data.price || data.unit_price) }}</template>
                    </Column>
                </DataTable>
            </div>

            <div class="card">
                <h3>Pagamentos</h3>
                <DataTable v-if="order.payments?.length" :value="order.payments">
                    <Column header="Método">
                        <template #body="{ data }">{{ data.method_label || paymentLabel(data.payment_method) }}</template>
                    </Column>
                    <Column header="Estado">
                        <template #body="{ data }">{{ paymentLabel(data.status) }}</template>
                    </Column>
                    <Column header="Valor">
                        <template #body="{ data }">{{ formatMt(data.amount) }}</template>
                    </Column>
                    <Column header="Data">
                        <template #body="{ data }">{{ formatDate(data.created_at) }}</template>
                    </Column>
                    <Column>
                        <template #body="{ data }">
                            <Button v-if="data.status === 'pending'" label="Confirmar" size="small" @click="confirmPayment(data)" />
                            <Button v-if="data.status === 'completed'" label="Reembolsar" size="small" severity="warn" outlined @click="refundPayment(data)" />
                        </template>
                    </Column>
                </DataTable>
                <p v-else class="text-muted-color mb-0">Ainda sem pagamentos neste pedido.</p>
            </div>

            <div v-if="order.tracking_orders?.length" class="card">
                <h3>Rastreio</h3>
                <ul class="m-0 pl-4">
                    <li v-for="item in order.tracking_orders" :key="item.id" class="mb-3">
                        <strong>{{ statusLabel(item.order_status) }}</strong>
                        — {{ item.description }}
                        <div class="text-muted-color text-sm">{{ formatDate(item.created_at) }} · {{ item.local }}</div>
                    </li>
                </ul>
            </div>

            <div class="card">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="m-0">Excepções</h3>
                    <Button label="Registar" icon="pi pi-plus" size="small" outlined @click="openException" />
                </div>
                <DataTable v-if="exceptions.length" :value="exceptions">
                    <Column field="type_label" header="Tipo" />
                    <Column field="cost_bearer_label" header="Custo" />
                    <Column field="status_label" header="Estado" />
                    <Column header="Quando">
                        <template #body="{ data }">{{ formatDate(data.created_at) }}</template>
                    </Column>
                    <Column>
                        <template #body="{ data }">
                            <Button
                                v-if="data.status === 'open'"
                                label="Resolver"
                                size="small"
                                :loading="resolvingException"
                                @click="resolveException(data)"
                            />
                        </template>
                    </Column>
                </DataTable>
                <p v-else class="text-muted-color mb-0">Sem excepções neste pedido.</p>
            </div>
        </div>

        <Dialog v-model:visible="exceptionVisible" header="Registar excepção" modal :style="{ width: '28rem' }">
            <div class="flex flex-col gap-3">
                <div>
                    <label class="block mb-1">Tipo</label>
                    <Select
                        v-model="exceptionForm.type"
                        :options="exceptionTypes"
                        optionLabel="label"
                        optionValue="type"
                        class="w-full"
                        @change="exceptionForm.cost_bearer = exceptionTypes.find((t) => t.type === exceptionForm.type)?.default_cost_bearer || 'none'"
                    />
                </div>
                <div>
                    <label class="block mb-1">Descrição</label>
                    <Textarea v-model="exceptionForm.description" rows="3" class="w-full" />
                </div>
                <div>
                    <label class="block mb-1">Quem assume</label>
                    <Select
                        v-model="exceptionForm.cost_bearer"
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
                    <InputNumber v-model="exceptionForm.cost_amount" class="w-full" :min="0" />
                </div>
            </div>
            <template #footer>
                <Button label="Fechar" text @click="exceptionVisible = false" />
                <Button label="Guardar" :loading="resolvingException" @click="saveException" />
            </template>
        </Dialog>

        <Dialog v-model:visible="assignVisible" header="Atribuir motorista" modal :style="{ width: '28rem' }">
            <div class="flex flex-col gap-3">
                <label>Motorista</label>
                <Select v-model="driverId" :options="drivers" optionLabel="name" optionValue="id" placeholder="Escolher" class="w-full" />
                <label>Veículo</label>
                <Select v-model="vehicleId" :options="vehicles" optionValue="id" placeholder="Escolher" class="w-full">
                    <template #option="{ option }">{{ option.license_plate_number }} · {{ option.model }}</template>
                    <template #value="{ value, placeholder }">
                        <span v-if="value">{{ vehicles.find((v) => v.id === value)?.license_plate_number }}</span>
                        <span v-else>{{ placeholder }}</span>
                    </template>
                </Select>
            </div>
            <template #footer>
                <Button label="Fechar" text @click="assignVisible = false" />
                <Button label="Atribuir" :loading="assigning" @click="assign" />
            </template>
        </Dialog>

        <Dialog v-model:visible="statusVisible" header="Alterar estado" modal :style="{ width: '28rem' }">
            <div class="flex flex-col gap-3">
                <Select v-model="nextStatusId" :options="statuses" optionValue="id" optionLabel="display_name" placeholder="Novo estado" class="w-full" />
                <Textarea v-model="statusNote" rows="3" placeholder="Nota (opcional)" />
            </div>
            <template #footer>
                <Button label="Fechar" text @click="statusVisible = false" />
                <Button label="Actualizar" :loading="updating" @click="updateStatus" />
            </template>
        </Dialog>

        <Dialog v-model:visible="shopAcceptVisible" header="Aceitar pedido (loja)" modal :style="{ width: '28rem' }">
            <div class="flex flex-col gap-3">
                <p class="m-0 text-muted-color">Define os minutos de preparação. Se ≤ 15, o estafeta é chamado já.</p>
                <label>Minutos de preparação</label>
                <InputNumber v-model="preparationMinutes" :min="1" :max="180" fluid />
            </div>
            <template #footer>
                <Button label="Fechar" text @click="shopAcceptVisible = false" />
                <Button label="Aceitar" :loading="shopWorking" @click="shopAccept" />
            </template>
        </Dialog>
    </div>
</template>
