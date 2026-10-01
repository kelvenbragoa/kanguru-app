<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import http, { apiError, apiPayload } from '@/api/http';
import ImageUpload from '@/components/ImageUpload.vue';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const isEdit = computed(() => Boolean(route.params.id));
const loading = ref(false);
const saving = ref(false);
const options = ref({ shops: [], product_categories: [], product_statuses: [] });
const form = ref({
    shop_id: null,
    name: '',
    description: '',
    price: null,
    product_category_id: null,
    product_status_id: 1,
    weight: null,
    image: '',
});

const loadOptions = async () => {
    const response = await http.get('/catalog/options');
    options.value = apiPayload(response);
};

const load = async () => {
    loading.value = true;
    try {
        await loadOptions();
        if (route.query.shop_id) {
            form.value.shop_id = Number(route.query.shop_id);
        }
        if (!isEdit.value) return;
        const response = await http.get(`/products/${route.params.id}`);
        const product = apiPayload(response);
        form.value = {
            shop_id: product.shop_id,
            name: product.name || '',
            description: product.description || '',
            price: product.price != null ? Number(product.price) : null,
            product_category_id: product.product_category_id,
            product_status_id: product.product_status_id,
            weight: product.weight != null ? Number(product.weight) : null,
            image: product.image || '',
        };
    } catch {
        toast.add({
            severity: 'error',
            summary: 'Erro',
            detail: isEdit.value ? 'Produto não encontrado.' : 'Não foi possível carregar as opções.',
            life: 3000,
        });
        if (isEdit.value) {
            router.push({ name: 'products.index' });
        }
    } finally {
        loading.value = false;
    }
};

const save = async () => {
    saving.value = true;
    try {
        if (isEdit.value) {
            await http.put(`/products/${route.params.id}`, form.value);
            toast.add({ severity: 'success', summary: 'Guardado', detail: 'Produto actualizado.', life: 2500 });
        } else {
            await http.post('/products', form.value);
            toast.add({ severity: 'success', summary: 'Criado', detail: 'Produto adicionado ao estabelecimento.', life: 2500 });
        }
        router.push({ name: 'products.index', query: form.value.shop_id ? { shop_id: form.value.shop_id } : {} });
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
        <Button icon="pi pi-arrow-left" label="Produtos" text class="mb-3" @click="router.push({ name: 'products.index' })" />
        <div class="card max-w-3xl">
            <h2 class="mt-0">{{ isEdit ? 'Editar produto' : 'Novo produto' }}</h2>
            <p class="text-muted-color mt-0">Preço em meticais (MT). O produto aparece na app do cliente.</p>

            <form class="flex flex-col gap-4" @submit.prevent="save">
                <div>
                    <label class="font-semibold block mb-2">Estabelecimento *</label>
                    <Select
                        v-model="form.shop_id"
                        :options="options.shops"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Escolher"
                        class="w-full"
                        :disabled="loading"
                    />
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
                        <label class="font-semibold block mb-2">Peso (kg)</label>
                        <InputNumber v-model="form.weight" mode="decimal" :min="0" :minFractionDigits="2" :maxFractionDigits="2" fluid :disabled="loading" />
                    </div>
                </div>
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-12 md:col-span-6">
                        <label class="font-semibold block mb-2">Categoria *</label>
                        <Select
                            v-model="form.product_category_id"
                            :options="options.product_categories"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Escolher"
                            class="w-full"
                            :disabled="loading"
                        />
                    </div>
                    <div class="col-span-12 md:col-span-6">
                        <label class="font-semibold block mb-2">Estado *</label>
                        <Select
                            v-model="form.product_status_id"
                            :options="options.product_statuses"
                            optionLabel="name"
                            optionValue="id"
                            class="w-full"
                            :disabled="loading"
                        />
                    </div>
                </div>
                <div>
                    <label class="font-semibold block mb-2">Descrição</label>
                    <Textarea v-model="form.description" rows="3" class="w-full" :disabled="loading" />
                </div>
                <div>
                    <label class="font-semibold block mb-2">Imagem</label>
                    <ImageUpload v-model="form.image" />
                </div>
                <div class="flex gap-2 justify-end">
                    <Button type="button" label="Cancelar" severity="secondary" outlined @click="router.push({ name: 'products.index' })" />
                    <Button type="submit" :label="isEdit ? 'Guardar' : 'Adicionar produto'" :loading="saving" />
                </div>
            </form>
        </div>
    </div>
</template>
