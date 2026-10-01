<template>
    <div class="grid">
        <div class="col-12">
            <div class="card">
                <Toast />
                <Toolbar class="mb-4">
                    <template v-slot:start>
                        <h5 class="m-0">Gerenciar Departamentos</h5>
                    </template>
                    <template v-slot:end>
                        <Button label="Novo Departamento" icon="pi pi-plus" severity="success" @click="openNew" />
                    </template>
                </Toolbar>

                <!-- Filtros -->
                <div class="grid mb-4 gap-3">
                    <div class="col-12 md:col-5">
                        <IconField iconPosition="left">
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText v-model="filters['global'].value" placeholder="Buscar..." class="w-full" />
                        </IconField>
                    </div>
                    <div class="col-12 md:col-3">
                        <Button label="Limpar Filtros" icon="pi pi-filter-slash" severity="secondary" outlined class="w-full" @click="clearFilters" />
                    </div>
                </div>

                <DataTable
                    ref="dt"
                    :value="departments"
                    dataKey="id"
                    :paginator="true"
                    :rows="15"
                    :filters="filters"
                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                    :rowsPerPageOptions="[10, 15, 25, 50]"
                    currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} usuários"
                    :loading="loading"
                    responsiveLayout="scroll"
                >
                    <Column field="name" header="Nome" :sortable="true" style="min-width: 200px;"></Column>
                    <Column header="Ações" style="min-width: 150px;">
                        <template #body="slotProps">
                            <Button icon="pi pi-eye" severity="info" text rounded class="mr-2" @click="viewUser(slotProps.data)" v-tooltip.top="'Visualizar'" />
                            <Button icon="pi pi-pencil" severity="warning" text rounded class="mr-2" @click="editUser(slotProps.data)" v-tooltip.top="'Editar'" />
                            <Button icon="pi pi-trash" severity="danger" text rounded @click="confirmDeleteUser(slotProps.data)" v-tooltip.top="'Deletar'" />
                        </template>
                    </Column>
                </DataTable>

                <Dialog v-model:visible="deleteUserDialog" :style="{ width: '450px' }" header="Confirmar Exclusão" :modal="true">
                    <div class="flex align-items-center gap-3">
                        <i class="pi pi-exclamation-triangle text-orange-500" style="font-size: 2rem" />
                        <span v-if="user">Tem certeza que deseja deletar o usuário <b>{{ user.name }}</b>?</span>
                    </div>
                    <template #footer>
                        <Button label="Não" icon="pi pi-times" severity="secondary" outlined @click="deleteUserDialog = false" />
                        <Button label="Sim, Deletar" icon="pi pi-check" severity="danger" @click="deleteUser" />
                    </template>
                </Dialog>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { FilterMatchMode } from '@primevue/core/api';
import { useToast } from 'primevue/usetoast';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const toast = useToast();

const departments = ref([]);
const user = ref({});
const deleteUserDialog = ref(false);
const loading = ref(false);

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    role: { value: null, matchMode: FilterMatchMode.EQUALS }
});

const roleOptions = ref([
    { label: 'Solicitante', value: 'solicitante' },
    { label: 'Armazém', value: 'armazem' },
    { label: 'Gestor', value: 'gestor' },
    { label: 'Administrador', value: 'admin' }
]);

onMounted(() => {
    loadDepartments();
});

const loadDepartments = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/departments');
        departments.value = response.data.data;
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: 'Erro ao carregar Departamentos', life: 3000 });
    } finally {
        loading.value = false;
    }
};

const clearFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        role: { value: null, matchMode: FilterMatchMode.EQUALS }
    };
};

const openNew = () => {
    router.push('/admin/departments/create');
};

const viewDm = (dmData) => {
    router.push(`/admin/departments/${dmData.id}`);
};

const editDm = (dmData) => {
    router.push(`/admin/departments/${dmData.id}/edit`);
};

const confirmDeleteDm = (dmData) => {
    department.value = departmentData;
    deleteUserDialog.value = true;
};

const deleteDepartment = async () => {
    try {
        await axios.delete(`/api/departments/${department.value.id}`);
        deleteDepartmentDialog.value = false;
        toast.add({ severity: 'success', summary: 'Sucesso', detail: 'Departamento deletado', life: 3000 });
        loadDepartments();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: error.response?.data?.message || 'Erro ao deletar departamento', life: 3000 });
    }
};

const getRoleLabel = (role) => {
    const roleMap = {
        solicitante: 'Solicitante',
        armazem: 'Armazém',
        gestor: 'Gestor',
        admin: 'Administrador'
    };
    return roleMap[role] || role;
};

const getRoleSeverity = (role) => {
    const severityMap = {
        admin: 'danger',
        gestor: 'warning',
        armazem: 'info',
        solicitante: 'success'
    };
    return severityMap[role] || 'info';
};
</script>
