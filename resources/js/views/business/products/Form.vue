<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import http, { apiError, apiPayload } from '@/api/http';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const isEdit = computed(() => Boolean(route.params.id));
const loading = ref(false);
const saving = ref(false);
const shops = ref([]);
const options = ref({ product_categories: [], product_statuses: [] });
const form = ref({
    shop_id: null,
    name: '',
    description: '',
    price: null,
    product_category_id: null,
    product_status_id: 1,
    weight: null,
    stock_qty: null,
    low_stock_threshold: 5,
    image: '',
});

const load = async () => {
    loading.value = true;
    try {
        const [dash, catalog] = await Promise.all([
            http.get('/business/dashboard'),
            http.get('/business/catalog-options'),
        ]);
        shops.value = apiPayload(dash).shops || [];
        options.value = apiPayload(catalog);
        if (!isEdit.value && shops.value.length) {
            form.value.shop_id = shops.value[0].id;
        }
        if (!isEdit.value) return;

        const list = await http.get('/business/products', { params: { per_page: 100 } });
        const product = (apiPayload(list).data || []).find((p) => Number(p.id) === Number(route.params.id));
        if (!product) throw new Error('missing');
        form.value = {
            shop_id: product.shop_id,
            name: product.name || '',
            description: product.description || '',
            price: product.price != null ? Number(product.price) : null,
            product_category_id: product.product_category_id,
            product_status_id: product.product_status_id,
            weight: product.weight != null ? Number(product.weight) : null,
            stock_qty: product.stock_qty != null ? Number(product.stock_qty) : null,
            low_stock_threshold: product.low_stock_threshold != null ? Number(product.low_stock_threshold) : 5,
            image: product.image || '',
        };
    } catch {
        toast.add({ severity: 'error', summary: 'Erro', detail: 'Não foi possível carregar.', life: 3000 });
        if (isEdit.value) router.push({ name: 'business.products' });
    } finally {
        loading.value = false;
    }
};

const save = async () => {
    saving.value = true;
    try {
        if (isEdit.value) {
            await http.put(`/business/products/${route.params.id}`, form.value);
            toast.add({ severity: 'success', summary: 'Guardado', life: 2500 });
        } else {
            await http.post('/business/products', form.value);
            toast.add({ severity: 'success', summary: 'Criado', life: 2500 });
        }
        router.push({ name: 'business.products' });
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível guardar.'), life: 4000 });
    } finally {
        saving.value = false;
    }
};

onMounted(load);
</script>

<template>
    <div>
        <Button icon="pi pi-arrow-left" label="Produtos" text class="mb-3" @click="router.push({ name: 'business.products' })" />
        <div class="card max-w-3xl">
            <h2 class="mt-0">{{ isEdit ? 'Editar produto' : 'Novo produto' }}</h2>
            <form class="flex flex-col gap-4" @submit.prevent="save">
                <div>
                    <label class="font-semibold block mb-2">Loja *</label>
                    <Select v-model="form.shop_id" :options="shops" optionLabel="name" optionValue="id" class="w-full" :disabled="loading || isEdit" />
                </div>
                <div>
                    <label class="font-semibold block mb-2">Nome *</label>
                    <InputText v-model="form.name" class="w-full" :disabled="loading" />
                </div>
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-12 md:col-span-6">
                        <label class="font-semibold block mb-2">Preço (MT) *</label>
                        <InputNumber v-model="form.price" mode="decimal" :min="0" :minFractionDigits="2" :maxFractionDigits="2" fluid :disabled="loading" />
                    </div>
                    <div class="col-span-12 md:col-span-6">
                        <label class="font-semibold block mb-2">Stock</label>
                        <InputNumber v-model="form.stock_qty" :min="0" fluid :disabled="loading" />
                    </div>
                </div>
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-12 md:col-span-6">
                        <label class="font-semibold block mb-2">Categoria *</label>
                        <Select v-model="form.product_category_id" :options="options.product_categories" optionLabel="name" optionValue="id" class="w-full" :disabled="loading" />
                    </div>
                    <div class="col-span-12 md:col-span-6">
                        <label class="font-semibold block mb-2">Estado *</label>
                        <Select v-model="form.product_status_id" :options="options.product_statuses" optionLabel="name" optionValue="id" class="w-full" :disabled="loading" />
                    </div>
                </div>
                <div>
                    <label class="font-semibold block mb-2">Descrição</label>
                    <Textarea v-model="form.description" rows="3" class="w-full" :disabled="loading" />
                </div>
                <div class="flex gap-2">
                    <Button type="submit" label="Guardar" :loading="saving" />
                    <Button type="button" label="Cancelar" severity="secondary" outlined @click="router.push({ name: 'business.products' })" />
                </div>
            </form>
        </div>
    </div>
</template>
