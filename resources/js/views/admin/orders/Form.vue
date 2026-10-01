<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import http, { apiError, apiPayload, unwrapPage } from '@/api/http';
import { PAYMENT_METHOD_OPTIONS } from '@/utils/format';

const router = useRouter();
const toast = useToast();
const loading = ref(false);
const saving = ref(false);
const options = ref({ customers: [], order_types: [], shops: [] });
const products = ref([]);
const form = ref({
    user_id: null,
    order_type_id: 1,
    shop_id: null,
    origin: '',
    destination: '',
    delivery_fee: 80,
    weight: null,
    notes: '',
    payment_method: 'cash',
    payer_phone: '',
});
const item = ref({ product_id: null, quantity: 1, price: null });
const items = ref([]);

const selectedProduct = computed(() => products.value.find((p) => p.id === item.value.product_id));

const loadOptions = async () => {
    loading.value = true;
    try {
        const response = await http.get('/catalog/options');
        options.value = apiPayload(response);
    } finally {
        loading.value = false;
    }
};

const loadProducts = async () => {
    items.value = [];
    products.value = [];
    if (!form.value.shop_id) return;
    const response = await http.get('/products', { params: { shop_id: form.value.shop_id, per_page: 50 } });
    products.value = unwrapPage(response.data).items;
};

const addItem = () => {
    const product = selectedProduct.value;
    if (!product) return;
    items.value.push({
        product_id: product.id,
        name: product.name,
        quantity: item.value.quantity || 1,
        price: item.value.price ?? Number(product.price),
    });
    item.value = { product_id: null, quantity: 1, price: null };
};

const save = async () => {
    saving.value = true;
    try {
        const payload = {
            ...form.value,
            items: items.value.map(({ product_id, quantity, price }) => ({ product_id, quantity, price })),
        };
        const response = await http.post('/orders', payload);
        const order = apiPayload(response);
        toast.add({ severity: 'success', summary: 'Pedido criado', detail: order.code, life: 3000 });
        router.push({ name: 'orders.show', params: { id: order.id } });
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível criar o pedido.'), life: 4000 });
    } finally {
        saving.value = false;
    }
};

onMounted(loadOptions);
watch(() => form.value.shop_id, loadProducts);
</script>

<template>
    <div>
        <Button icon="pi pi-arrow-left" label="Pedidos" text class="mb-3" @click="router.push({ name: 'orders.index' })" />
        <div class="card max-w-3xl">
            <h2 class="mt-0">Novo pedido</h2>
            <p class="text-muted-color mt-0">Registo para um cliente (telefone / balcão).</p>

            <form class="flex flex-col gap-4" @submit.prevent="save">
                <div>
                    <label class="font-semibold block mb-2">Cliente *</label>
                    <Select
                        v-model="form.user_id"
                        :options="options.customers"
                        optionValue="id"
                        placeholder="Escolher cliente"
                        filter
                        class="w-full"
                        :disabled="loading"
                    >
                        <template #option="{ option }">{{ option.name }} · {{ option.email }}</template>
                        <template #value="{ value, placeholder }">
                            <span v-if="value">{{ options.customers.find((c) => c.id === value)?.name }}</span>
                            <span v-else>{{ placeholder }}</span>
                        </template>
                    </Select>
                </div>
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-12 md:col-span-6">
                        <label class="font-semibold block mb-2">Tipo *</label>
                        <Select v-model="form.order_type_id" :options="options.order_types" optionLabel="name" optionValue="id" class="w-full" />
                    </div>
                    <div class="col-span-12 md:col-span-6">
                        <label class="font-semibold block mb-2">Estabelecimento</label>
                        <Select v-model="form.shop_id" :options="options.shops" optionLabel="name" optionValue="id" showClear placeholder="Opcional" class="w-full" />
                    </div>
                </div>
                <div>
                    <label class="font-semibold block mb-2">Origem *</label>
                    <InputText v-model="form.origin" class="w-full" />
                </div>
                <div>
                    <label class="font-semibold block mb-2">Destino *</label>
                    <InputText v-model="form.destination" class="w-full" />
                </div>
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-12 md:col-span-6">
                        <label class="font-semibold block mb-2">Taxa de entrega (MT) *</label>
                        <InputNumber v-model="form.delivery_fee" mode="decimal" :min="0" :minFractionDigits="2" fluid />
                    </div>
                    <div class="col-span-12 md:col-span-6">
                        <label class="font-semibold block mb-2">Peso (kg)</label>
                        <InputNumber v-model="form.weight" mode="decimal" :min="0" :minFractionDigits="2" fluid />
                    </div>
                </div>
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-12 md:col-span-6">
                        <label class="font-semibold block mb-2">Pagamento</label>
                        <Select v-model="form.payment_method" :options="PAYMENT_METHOD_OPTIONS" optionLabel="label" optionValue="value" class="w-full" />
                    </div>
                    <div class="col-span-12 md:col-span-6">
                        <label class="font-semibold block mb-2">Telefone M-Pesa / e-Mola</label>
                        <InputText v-model="form.payer_phone" class="w-full" />
                    </div>
                </div>
                <div>
                    <label class="font-semibold block mb-2">Notas</label>
                    <Textarea v-model="form.notes" rows="3" class="w-full" />
                </div>

                <div v-if="form.shop_id" class="border-t border-surface-200 pt-4">
                    <h3 class="mt-0">Itens</h3>
                    <div class="flex flex-col md:flex-row gap-2 mb-3">
                        <Select v-model="item.product_id" :options="products" optionLabel="name" optionValue="id" placeholder="Produto" class="flex-1" />
                        <InputNumber v-model="item.quantity" :min="1" class="w-6rem" />
                        <Button type="button" label="Adicionar" icon="pi pi-plus" @click="addItem" />
                    </div>
                    <ul v-if="items.length" class="m-0 pl-4">
                        <li v-for="(row, index) in items" :key="index">
                            {{ row.quantity }} × {{ row.name }} — MT {{ row.price }}
                            <Button icon="pi pi-times" text rounded severity="danger" @click="items.splice(index, 1)" />
                        </li>
                    </ul>
                </div>

                <div class="flex gap-2 justify-end">
                    <Button type="button" label="Cancelar" severity="secondary" outlined @click="router.push({ name: 'orders.index' })" />
                    <Button type="submit" label="Criar pedido" :loading="saving" />
                </div>
            </form>
        </div>
    </div>
</template>
