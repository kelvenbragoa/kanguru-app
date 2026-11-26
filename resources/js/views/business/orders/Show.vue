<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import http, { apiError, apiPayload } from '@/api/http';
import { formatDate, formatMt, statusLabel, statusSeverity } from '@/utils/format';

const route = useRoute();
const router = useRouter();
const toast = useToast();
const confirm = useConfirm();

const loading = ref(true);
const working = ref(false);
const order = ref(null);
const acceptVisible = ref(false);
const preparationMinutes = ref(20);

const statusName = computed(() => order.value?.order_status?.name);
const canAccept = computed(() => ['pending', 'confirmed'].includes(statusName.value));
const canReady = computed(() => ['preparing', 'confirmed'].includes(statusName.value));
const canReject = computed(() => ['pending', 'confirmed'].includes(statusName.value));

const load = async () => {
    loading.value = true;
    try {
        const response = await http.get(`/business/orders/${route.params.id}`);
        order.value = apiPayload(response);
    } catch {
        toast.add({ severity: 'error', summary: 'Erro', detail: 'Pedido não encontrado.', life: 3000 });
        router.push({ name: 'business.orders' });
    } finally {
        loading.value = false;
    }
};

const shopAccept = async () => {
    working.value = true;
    try {
        await http.post(`/business/orders/${order.value.id}/shop-accept`, {
            preparation_minutes: preparationMinutes.value,
        });
        toast.add({ severity: 'success', summary: 'Aceite', detail: 'Pedido em preparação.', life: 3000 });
        acceptVisible.value = false;
        await load();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível aceitar.'), life: 4000 });
    } finally {
        working.value = false;
    }
};

const shopReady = async () => {
    working.value = true;
    try {
        await http.post(`/business/orders/${order.value.id}/shop-ready`);
        toast.add({ severity: 'success', summary: 'Pronto', detail: 'Pedido pronto para recolha.', life: 3000 });
        await load();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível marcar pronto.'), life: 4000 });
    } finally {
        working.value = false;
    }
};

const reject = () => {
    confirm.require({
        message: `Rejeitar o pedido ${order.value.code}? O cliente será reembolsado.`,
        header: 'Rejeitar pedido',
        acceptLabel: 'Rejeitar',
        rejectLabel: 'Voltar',
        acceptClass: 'p-button-danger',
        accept: async () => {
            working.value = true;
            try {
                await http.post(`/business/orders/${order.value.id}/reject`, {
                    reason: 'Parceiro recusou o pedido',
                });
                toast.add({ severity: 'success', summary: 'Rejeitado', life: 3000 });
                await load();
            } catch (error) {
                toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível rejeitar.'), life: 4000 });
            } finally {
                working.value = false;
            }
        },
    });
};

onMounted(load);
</script>

<template>
    <div>
        <Button icon="pi pi-arrow-left" label="Pedidos" text class="mb-3" @click="router.push({ name: 'business.orders' })" />

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
                        <Button v-if="canAccept" label="Aceitar" icon="pi pi-check" severity="success" @click="acceptVisible = true" />
                        <Button v-if="canReady" label="Marcar pronto" icon="pi pi-box" :loading="working" @click="shopReady" />
                        <Button v-if="canReject" label="Rejeitar" icon="pi pi-times" severity="danger" outlined :loading="working" @click="reject" />
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-12 lg:col-span-6 card mb-0">
                    <h3>Entrega</h3>
                    <p><strong>Cliente:</strong> {{ order.user?.name }}</p>
                    <p><strong>Destino:</strong> {{ order.destination }}</p>
                    <p><strong>Total:</strong> {{ formatMt(order.total_price) }}</p>
                    <p v-if="order.preparation_minutes"><strong>Preparação:</strong> {{ order.preparation_minutes }} min</p>
                </div>
                <div class="col-span-12 lg:col-span-6 card mb-0">
                    <h3>Itens</h3>
                    <ul v-if="order.order_items?.length" class="m-0 pl-4">
                        <li v-for="item in order.order_items" :key="item.id" class="mb-2">
                            {{ item.quantity }}× {{ item.product?.name || 'Item' }} — {{ formatMt(item.price) }}
                        </li>
                    </ul>
                    <p v-else class="text-muted-color mb-0">Sem itens.</p>
                </div>
            </div>
        </div>

        <Dialog v-model:visible="acceptVisible" header="Aceitar pedido" modal :style="{ width: '28rem' }">
            <div class="flex flex-col gap-3">
                <p class="m-0 text-muted-color">Indica os minutos de preparação. Se ≤ 15, o estafeta é chamado já.</p>
                <InputNumber v-model="preparationMinutes" :min="1" :max="180" fluid />
            </div>
            <template #footer>
                <Button label="Fechar" text @click="acceptVisible = false" />
                <Button label="Aceitar" :loading="working" @click="shopAccept" />
            </template>
        </Dialog>
    </div>
</template>
