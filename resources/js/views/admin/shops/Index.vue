<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import http, { apiError, unwrapPage } from '@/api/http';
import { formatMt } from '@/utils/format';

const router = useRouter();
const toast = useToast();
const confirm = useConfirm();
const loading = ref(false);
const shops = ref([]);
const total = ref(0);
const page = ref(1);
const rows = ref(15);
const search = ref('');

const load = async (nextPage = 1) => {
    loading.value = true;
    page.value = nextPage;
    try {
        const response = await http.get('/shops', {
            params: {
                page: nextPage,
                per_page: rows.value,
                ...(search.value ? { search: search.value } : {}),
            },
        });
        const payload = unwrapPage(response.data);
        shops.value = payload.items;
        total.value = payload.total;
    } finally {
        loading.value = false;
    }
};

const onPage = (event) => {
    rows.value = event.rows;
    load(event.page + 1);
};

const remove = (shop) => {
    confirm.require({
        header: 'Apagar estabelecimento',
        message: `Apagar ${shop.name}? Só é possível se não tiver produtos.`,
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Apagar',
        rejectLabel: 'Voltar',
        acceptClass: 'p-button-danger',
        accept: async () => {
            try {
                await http.delete(`/shops/${shop.id}`);
                toast.add({ severity: 'success', summary: 'Apagado', life: 2500 });
                await load(page.value);
            } catch (error) {
                toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível apagar.'), life: 4000 });
            }
        },
    });
};

onMounted(() => load(1));
</script>

<template>
    <div class="card">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
            <div>
                <h2 class="m-0">Estabelecimentos</h2>
                <p class="text-muted-color mt-1 mb-0">Lojas, restaurantes e farmácias visíveis na app.</p>
            </div>
            <div class="flex gap-2">
                <InputText v-model="search" placeholder="Procurar" @keyup.enter="load(1)" />
                <Button icon="pi pi-search" @click="load(1)" />
                <Button label="Novo estabelecimento" icon="pi pi-plus" @click="router.push({ name: 'shops.create' })" />
            </div>
        </div>

        <DataTable
            :value="shops"
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
            <Column field="address" header="Morada" />
            <Column field="phone" header="Telefone" />
            <Column field="email" header="Email" />
            <Column header="Taxa">
                <template #body="{ data }">{{ data.delivery_fee != null ? formatMt(data.delivery_fee) : '—' }}</template>
            </Column>
            <Column header="" style="width: 12rem">
                <template #body="{ data }">
                    <Button icon="pi pi-plus" text rounded v-tooltip.top="'Adicionar produto'" @click="router.push({ name: 'products.create', query: { shop_id: data.id } })" />
                    <Button icon="pi pi-pencil" text rounded @click="router.push({ name: 'shops.edit', params: { id: data.id } })" />
                    <Button icon="pi pi-trash" text rounded severity="danger" @click="remove(data)" />
                </template>
            </Column>
        </DataTable>
    </div>
</template>
