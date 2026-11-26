<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import http, { apiError, apiPayload } from '@/api/http';
import { formatMt } from '@/utils/format';

const router = useRouter();
const toast = useToast();
const loading = ref(true);
const products = ref([]);
const importVisible = ref(false);
const importCsv = ref('');
const importShopId = ref(null);
const historyVisible = ref(false);
const historyRows = ref([]);
const historyProduct = ref(null);

const load = async () => {
    loading.value = true;
    try {
        const response = await http.get('/business/products', { params: { per_page: 100 } });
        products.value = apiPayload(response).data || [];
        if (!importShopId.value && products.value[0]) {
            importShopId.value = products.value[0].shop_id;
        }
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível carregar.'), life: 4000 });
    } finally {
        loading.value = false;
    }
};

const soldOut = async (row) => {
    try {
        await http.post(`/business/products/${row.id}/sold-out`);
        toast.add({ severity: 'success', summary: 'Esgotado', life: 2500 });
        await load();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível marcar.'), life: 4000 });
    }
};

const exportCsv = async () => {
    try {
        const response = await http.get('/business/products/export', { responseType: 'blob' });
        const url = URL.createObjectURL(new Blob([response.data], { type: 'text/csv' }));
        const a = document.createElement('a');
        a.href = url;
        a.download = 'produtos.csv';
        a.click();
        URL.revokeObjectURL(url);
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Exportação falhou.'), life: 4000 });
    }
};

const doImport = async () => {
    try {
        await http.post('/business/products/import', {
            shop_id: importShopId.value,
            csv: importCsv.value,
        });
        toast.add({ severity: 'success', summary: 'Importado', life: 2500 });
        importVisible.value = false;
        importCsv.value = '';
        await load();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Importação falhou.'), life: 4000 });
    }
};

const showHistory = async (row) => {
    historyProduct.value = row;
    historyVisible.value = true;
    try {
        const response = await http.get(`/business/products/${row.id}/movements`);
        historyRows.value = apiPayload(response).data || [];
    } catch {
        historyRows.value = [];
    }
};

onMounted(load);
</script>

<template>
    <div class="card">
        <div class="flex flex-wrap justify-between gap-3 mb-4">
            <div>
                <h2 class="m-0">Produtos e stock</h2>
                <p class="text-muted-color mt-2 mb-0">Actualize preços e quantidades. Stock vazio = ilimitado.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button label="Exportar CSV" icon="pi pi-download" outlined @click="exportCsv" />
                <Button label="Importar CSV" icon="pi pi-upload" outlined @click="importVisible = true" />
                <Button label="Novo produto" icon="pi pi-plus" @click="router.push({ name: 'business.products.create' })" />
            </div>
        </div>

        <DataTable :value="products" :loading="loading">
            <Column field="name" header="Nome" />
            <Column header="Preço">
                <template #body="{ data }">{{ formatMt(data.price) }}</template>
            </Column>
            <Column header="Stock">
                <template #body="{ data }">{{ data.stock_qty == null ? '∞' : data.stock_qty }}</template>
            </Column>
            <Column header="Estado">
                <template #body="{ data }">{{ data.product_status?.name || '—' }}</template>
            </Column>
            <Column>
                <template #body="{ data }">
                    <Button icon="pi pi-history" text rounded @click="showHistory(data)" />
                    <Button icon="pi pi-pencil" text rounded @click="router.push({ name: 'business.products.edit', params: { id: data.id } })" />
                    <Button icon="pi pi-ban" text rounded severity="warn" @click="soldOut(data)" />
                </template>
            </Column>
        </DataTable>

        <Dialog v-model:visible="importVisible" header="Importar CSV" modal :style="{ width: '36rem' }">
            <p class="text-sm text-muted-color">Colunas: id,shop_id,name,price,stock_qty,product_category_id,product_status_id</p>
            <InputNumber v-model="importShopId" class="w-full mb-3" placeholder="shop_id" />
            <Textarea v-model="importCsv" rows="10" class="w-full" placeholder="Cole o CSV aqui" />
            <template #footer>
                <Button label="Fechar" text @click="importVisible = false" />
                <Button label="Importar" @click="doImport" />
            </template>
        </Dialog>

        <Dialog v-model:visible="historyVisible" :header="`Histórico — ${historyProduct?.name || ''}`" modal :style="{ width: '40rem' }">
            <DataTable :value="historyRows" size="small">
                <Column field="created_at" header="Quando" />
                <Column field="delta" header="Δ" />
                <Column field="stock_after" header="Stock" />
                <Column field="reason" header="Motivo" />
                <Column field="note" header="Nota" />
            </DataTable>
        </Dialog>
    </div>
</template>
