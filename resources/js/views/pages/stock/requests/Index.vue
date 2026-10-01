<template>
    <div class="grid">
        <div class="col-12">
            <div class="card">
                <Toast />
                <Toolbar class="mb-4">
                    <template v-slot:start>
                        <h5 class="m-0">Solicitações de Material</h5>
                    </template>
                    <template v-slot:end>
                        <Button label="Nova Solicitação" icon="pi pi-plus" severity="success" @click="createRequest" />
                    </template>
                </Toolbar>

                <!-- Filtros -->
                <div class="grid mb-4 gap-3">
                    <div class="col-12 md:col-4">
                        <IconField iconPosition="left">
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText v-model="filters.search" placeholder="Buscar por número, work order, equipamento..." @input="debounceSearch" class="w-full" />
                        </IconField>
                    </div>
                    <div class="col-12 md:col-4">
                        <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="Todos os Status" @change="loadRequests" class="w-full" :showClear="true" />
                    </div>
                    <div class="col-12 md:col-4">
                        <Button label="Limpar Filtros" icon="pi pi-filter-slash" severity="secondary" outlined class="w-full" @click="clearFilters" />
                    </div>
                </div>

                <DataTable 
                    ref="dt" 
                    :value="requests" 
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
                    currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} solicitações"
                >
                    <Column field="request_number" header="Nº Pedido" :sortable="true" style="min-width: 130px;">
                        <template #body="slotProps">
                            <strong>{{ slotProps.data.request_number }}</strong>
                        </template>
                    </Column>
                    <Column field="item.name" header="Item" style="min-width: 180px;"></Column>
                    <Column field="quantity" header="Quantidade" style="min-width: 120px;">
                        <template #body="slotProps">
                            {{ slotProps.data.quantity }} {{ slotProps.data.item?.unit }}
                        </template>
                    </Column>
                    <Column field="work_order" header="Work Order" style="min-width: 130px;"></Column>
                    <Column field="status" header="Status" :sortable="true" style="min-width: 120px;">
                        <template #body="slotProps">
                            <Tag :value="slotProps.data.status" :severity="getStatusSeverity(slotProps.data.status)" />
                        </template>
                    </Column>
                    <Column field="requester.name" header="Solicitante" style="min-width: 150px;"></Column>
                    <Column field="requested_at" header="Data" :sortable="true" style="min-width: 150px;">
                        <template #body="slotProps">
                            {{ formatDate(slotProps.data.requested_at) }}
                        </template>
                    </Column>
                    <Column header="Ações" style="min-width: 150px;">
                        <template #body="slotProps">
                            <Button icon="pi pi-eye" severity="info" text rounded class="mr-2" @click="viewRequest(slotProps.data.id)" v-tooltip.top="'Visualizar'" />
                            <Button icon="pi pi-pencil" severity="warning" text rounded class="mr-2" @click="editRequest(slotProps.data.id)" v-if="canEdit(slotProps.data)" v-tooltip.top="'Editar'" />
                            <Button icon="pi pi-times" severity="danger" text rounded @click="confirmCancel(slotProps.data)" v-if="canCancel(slotProps.data)" v-tooltip.top="'Cancelar'" />
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>
    </div>

    <!-- Dialog de Cancelamento -->
    <Dialog v-model:visible="cancelDialog" :style="{width: '450px'}" header="Cancelar Solicitação" :modal="true">
        <div class="flex align-items-center gap-3">
            <i class="pi pi-exclamation-triangle text-orange-500" style="font-size: 2rem" />
            <span v-if="selectedRequest">Tem certeza que deseja cancelar a solicitação <b>{{ selectedRequest.request_number }}</b>?</span>
        </div>
        <template #footer>
            <Button label="Não" icon="pi pi-times" severity="secondary" outlined @click="cancelDialog = false"/>
            <Button label="Sim, Cancelar" icon="pi pi-check" severity="danger" @click="cancelRequest"/>
        </template>
    </Dialog>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';

const router = useRouter();
const toast = useToast();
const dt = ref();

const requests = ref([]);
const loading = ref(false);
const cancelDialog = ref(false);
const selectedRequest = ref(null);

const pagination = ref({
    current_page: 1,
    per_page: 15,
    total: 0
});

const filters = ref({
    status: null,
    search: ''
});

const statusOptions = ref([
    { label: 'Todos', value: null },
    { label: 'Solicitado', value: 'solicitado' },
    { label: 'Preparando', value: 'preparando' },
    { label: 'Enviando', value: 'enviando' },
    { label: 'Recebido', value: 'recebido' },
    { label: 'Entregue', value: 'entregue' },
    { label: 'Cancelado', value: 'cancelado' }
]);

let searchTimeout;

onMounted(() => {
    loadRequests();
});

const loadRequests = async (page = 1) => {
    loading.value = true;
    try {
        const params = {
            page: page,
            per_page: pagination.value.per_page,
            status: filters.value.status,
            search: filters.value.search
        };

        const response = await axios.get('/api/requests', { params });
        requests.value = response.data.data;
        pagination.value = {
            current_page: response.data.current_page,
            per_page: response.data.per_page,
            total: response.data.total
        };
    } catch (error) {
        toast.add({severity:'error', summary:'Erro', detail:'Erro ao carregar solicitações', life: 3000});
    } finally {
        loading.value = false;
    }
};

const onPage = (event) => {
    loadRequests(event.page + 1);
};

const debounceSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        loadRequests();
    }, 500);
};

const clearFilters = () => {
    filters.value = {
        status: null,
        search: ''
    };
    loadRequests();
};

const createRequest = () => {
    router.push('/admin/stock/requests/create');
};

const viewRequest = (id) => {
    router.push(`/admin/stock/requests/${id}`);
};

const editRequest = (id) => {
    router.push(`/admin/stock/requests/${id}/edit`);
};

const confirmCancel = (request) => {
    selectedRequest.value = request;
    cancelDialog.value = true;
};

const cancelRequest = async () => {
    try {
        await axios.delete(`/api/requests/${selectedRequest.value.id}`);
        toast.add({severity:'success', summary:'Sucesso', detail:'Solicitação cancelada com sucesso', life: 3000});
        cancelDialog.value = false;
        loadRequests();
    } catch (error) {
        toast.add({severity:'error', summary:'Erro', detail:error.response?.data?.message || 'Erro ao cancelar solicitação', life: 3000});
    }
};

const canEdit = (request) => {
    return request.status === 'solicitado';
};

const canCancel = (request) => {
    return request.status === 'solicitado';
};

const getStatusSeverity = (status) => {
    const severityMap = {
        solicitado: 'info',
        preparando: 'warning',
        enviando: 'warn',
        recebido: 'success',
        entregue: 'success',
        cancelado: 'danger'
    };
    return severityMap[status] || 'info';
};

const formatDate = (date) => {
    return new Date(date).toLocaleString('pt-PT');
};
</script>
