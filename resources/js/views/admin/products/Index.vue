<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import http, { apiError, apiPayload, unwrapPage } from '@/api/http';
import { formatMt } from '@/utils/format';

const route = useRoute();
const router = useRouter();
const toast = useToast();
const confirm = useConfirm();
const loading = ref(false);
const products = ref([]);
const shops = ref([]);
const total = ref(0);
const page = ref(1);
const rows = ref(15);
const search = ref('');
const shopId = ref(route.query.shop_id ? Number(route.query.shop_id) : null);

const loadShops = async () => {
    const response = await http.get('/catalog/options');
    shops.value = apiPayload(response).shops || [];
};

const load = async (nextPage = 1) => {
    loading.value = true;
    page.value = nextPage;
    try {
        const response = await http.get('/products', {
            params: {
                page: nextPage,
                ...(search.value ? { search: search.value } : {}),
                ...(shopId.value ? { shop_id: shopId.value } : {}),
            },
        });
        const payload = unwrapPage(response.data);
        products.value = payload.items;
        total.value = payload.total;
    } finally {
        loading.value = false;
    }
};

const onPage = (event) => {
    rows.value = event.rows;
    load(event.page + 1);
};

const applyFilters = () => load(1);

const remove = (product) => {
    confirm.require({
        header: 'Apagar produto',
        message: `Apagar ${product.name}?`,
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Apagar',
        rejectLabel: 'Voltar',
        acceptClass: 'p-button-danger',
        accept: async () => {
            try {
                await http.delete(`/products/${product.id}`);
                toast.add({ severity: 'success', summary: 'Apagado', life: 2500 });
                await load(page.value);
            } catch (error) {
                toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível apagar.'), life: 4000 });
            }
        },
    });
};

onMounted(async () => {
    await loadShops();
    await load(1);
});
</script>

<template>
    <div class="card">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 mb-4">
            <div>
                <h2 class="m-0">Produtos</h2>
                <p class="text-muted-color mt-1 mb-0">Itens do catálogo, com preço em MT.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Select
                    v-model="shopId"
                    :options="shops"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Todos os estabelecimentos"
                    showClear
                    class="w-16rem"
                    @change="applyFilters"
                />
                <InputText v-model="search" placeholder="Procurar" @keyup.enter="applyFilters" />
                <Button icon="pi pi-search" @click="applyFilters" />
                <Button label="Novo produto" icon="pi pi-plus" @click="router.push({ name: 'products.create', query: shopId ? { shop_id: shopId } : {} })" />
            </div>
        </div>

        <DataTable
            :value="products"
            :loading="loading"
            dataKey="id"
            lazy
            paginator
            :first="(page - 1) * rows"
            :rows="rows"
            :totalRecords="total"
            @page="onPage"
            responsiveLayout="scroll"
        >
            <Column field="name" header="Nome" />
            <Column header="Estabelecimento">
                <template #body="{ data }">{{ data.shop?.name || '—' }}</template>
            </Column>
            <Column header="Categoria">
                <template #body="{ data }">{{ data.product_category?.name || '—' }}</template>
            </Column>
            <Column header="Preço">
                <template #body="{ data }">{{ formatMt(data.price) }}</template>
            </Column>
            <Column header="Estado">
                <template #body="{ data }">
                    <Tag :value="data.product_status?.name || '—'" :severity="data.product_status?.name === 'Ativo' ? 'success' : 'secondary'" />
                </template>
            </Column>
            <Column header="" style="width: 8rem">
                <template #body="{ data }">
                    <Button icon="pi pi-pencil" text rounded @click="router.push({ name: 'products.edit', params: { id: data.id } })" />
                    <Button icon="pi pi-trash" text rounded severity="danger" @click="remove(data)" />
                </template>
            </Column>
        </DataTable>
    </div>
</template>
