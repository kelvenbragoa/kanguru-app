<template>
    <div class="grid">
        <div class="col-12">
            <div class="card">
                <Toast />
                <Toolbar class="mb-4">
                    <template v-slot:start>
                        <h5 class="m-0">Itens de Stock</h5>
                    </template>
                    <template v-slot:end>
                        <Button label="Novo Item" icon="pi pi-plus" severity="success" @click="createItem" />
                    </template>
                </Toolbar>

                <!-- Filtros -->
                <div class="grid mb-4 gap-3">
                    <div class="col-12 md:col-4">
                        <IconField iconPosition="left">
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText v-model="filters.search" placeholder="Buscar por código ou nome..." @input="debounceSearch" class="w-full" />
                        </IconField>
                    </div>
                    <div class="col-12 md:col-3">
                        <Select v-model="filters.category" :options="categories" placeholder="Todas as Categorias" @change="loadItems" class="w-full" :showClear="true" />
                    </div>
                    <div class="col-12 md:col-2">
                        <div class="flex align-items-center h-full">
                            <Checkbox v-model="filters.lowStock" inputId="lowStock" binary @change="loadItems" />
                            <label for="lowStock" class="ml-2">Stock Baixo</label>
                        </div>
                    </div>
                    <div class="col-12 md:col-3">
                        <Button label="Limpar Filtros" icon="pi pi-filter-slash" severity="secondary" outlined class="w-full" @click="clearFilters" />
                    </div>
                </div>

                <DataTable 
                    :value="items" 
                    :lazy="true"
                    :paginator="true"
                    :rows="pagination.per_page"
                    :totalRecords="pagination.total"
                    :loading="loading"
                    @page="onPage"
                    dataKey="id" 
                    responsiveLayout="scroll"
                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                    :rowsPerPageOptions="[10, 15, 25, 50]"
                    currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} itens"
                >
                    <Column field="code" header="Código" :sortable="true" style="min-width: 120px;">
                        <template #body="slotProps">
                            <strong>{{ slotProps.data.code }}</strong>
                        </template>
                    </Column>
                    <Column field="name" header="Nome" :sortable="true" style="min-width: 200px;"></Column>
                    <Column field="category.name" header="Categoria" style="min-width: 120px;"></Column>
                    <Column field="location" header="Localização" style="min-width: 150px;"></Column>
                    <Column field="current_stock" header="Stock Atual" style="min-width: 130px;">
                        <template #body="slotProps">
                            <div class="flex align-items-center gap-2">
                                <span :class="{'text-red-500 font-bold': slotProps.data.current_stock <= slotProps.data.min_stock}">
                                    {{ slotProps.data.current_stock }} {{ slotProps.data.unit }}
                                </span>
                                <i v-if="slotProps.data.current_stock <= slotProps.data.min_stock" class="pi pi-exclamation-triangle text-red-500" v-tooltip.top="'Stock abaixo do mínimo'"></i>
                            </div>
                        </template>
                    </Column>
                    <Column field="min_stock" header="Stock Mín" style="min-width: 120px;">
                        <template #body="slotProps">
                            {{ slotProps.data.min_stock }} {{ slotProps.data.unit }}
                        </template>
                    </Column>
                    <Column field="is_active" header="Status" style="min-width: 100px;">
                        <template #body="slotProps">
                            <Tag :value="slotProps.data.is_active ? 'Ativo' : 'Inativo'" :severity="slotProps.data.is_active ? 'success' : 'danger'" />
                        </template>
                    </Column>
                    <Column header="Ações" style="min-width: 120px;">
                        <template #body="slotProps">
                            <Button icon="pi pi-eye" severity="info" text rounded class="mr-2" @click="viewItem(slotProps.data.id)" v-tooltip.top="'Visualizar'" />
                            <Button icon="pi pi-pencil" severity="warning" text rounded @click="editItem(slotProps.data.id)" v-tooltip.top="'Editar'" />
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';

const router = useRouter();
const toast = useToast();

const items = ref([]);
const loading = ref(false);
const categories = ref([]);

const pagination = ref({
    current_page: 1,
    per_page: 15,
    total: 0
});

const filters = ref({
    search: '',
    category: null,
    lowStock: false
});

let searchTimeout;

onMounted(() => {
    loadItems();
    loadCategories();
});

const loadItems = async (page = 1) => {
    loading.value = true;
    try {
        const params = {
            page: page,
            per_page: pagination.value.per_page,
            search: filters.value.search,
            category: filters.value.category,
            low_stock: filters.value.lowStock
        };

        const response = await axios.get('/api/items', { params });
        items.value = response.data.data;
        pagination.value = {
            current_page: response.data.current_page,
            per_page: response.data.per_page,
            total: response.data.total
        };
    } catch (error) {
        toast.add({severity:'error', summary:'Erro', detail:'Erro ao carregar itens', life: 3000});
    } finally {
        loading.value = false;
    }
};

const loadCategories = async () => {
    try {
        const response = await axios.get('/api/items');
        const allItems = response.data.data;
        const uniqueCategories = [...new Set(allItems.map(item => item.category).filter(Boolean))];
        categories.value = [null, ...uniqueCategories];
    } catch (error) {
        console.error('Erro ao carregar categorias:', error);
    }
};

const onPage = (event) => {
    loadItems(event.page + 1);
};

const debounceSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        loadItems();
    }, 500);
};

const clearFilters = () => {
    filters.value = {
        search: '',
        category: null,
        lowStock: false
    };
    loadItems();
};

const createItem = () => {
    router.push('/admin/stock/items/create');
};

const viewItem = (id) => {
    router.push(`/admin/stock/items/${id}`);
};

const editItem = (id) => {
    router.push(`/admin/stock/items/${id}/edit`);
};
</script>
