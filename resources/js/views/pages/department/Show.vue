<template>
    <div class="grid">
        <div class="col-12">
            <div class="card">
                <Toast />
                <div class="flex justify-content-between align-items-center mb-4">
                    <h5>Detalhes do Usuário</h5>
                    <div class="flex gap-2">
                        <Button label="Editar" icon="pi pi-pencil" severity="warning" @click="editUser" />
                        <Button label="Voltar" icon="pi pi-arrow-left" severity="secondary" @click="goBack" />
                    </div>
                </div>

                <div v-if="loading" class="flex justify-content-center">
                    <ProgressSpinner />
                </div>

                <div v-else-if="user">
                    <div class="grid">
                        <div class="col-12 md:col-6">
                            <div class="field mb-3">
                                <label class="font-bold">Nome</label>
                                <p>{{ user.name }}</p>
                            </div>
                        </div>

                        <div class="col-12 md:col-6">
                            <div class="field mb-3">
                                <label class="font-bold">Email</label>
                                <p>{{ user.email }}</p>
                            </div>
                        </div>

                        <div class="col-12 md:col-6">
                            <div class="field mb-3">
                                <label class="font-bold">Role</label>
                                <p>
                                    <Tag :value="getRoleLabel(user.role)" :severity="getRoleSeverity(user.role)" />
                                </p>
                            </div>
                        </div>

                        <div class="col-12 md:col-6">
                            <div class="field mb-3">
                                <label class="font-bold">Departamento</label>
                                <p>{{ user.department || '-' }}</p>
                            </div>
                        </div>

                        <div class="col-12 md:col-6">
                            <div class="field mb-3">
                                <label class="font-bold">Status</label>
                                <p>
                                    <Tag :value="user.is_active ? 'Ativo' : 'Inativo'" :severity="user.is_active ? 'success' : 'danger'" />
                                </p>
                            </div>
                        </div>

                        <div class="col-12 md:col-6">
                            <div class="field mb-3">
                                <label class="font-bold">Data de Cadastro</label>
                                <p>{{ formatDate(user.created_at) }}</p>
                            </div>
                        </div>
                    </div>

                    <Divider />

                    <h6>Últimas Solicitações</h6>
                    <DataTable :value="user.requests" :paginator="user.requests?.length > 10" :rows="10" v-if="user.requests?.length">
                        <Column field="request_number" header="Número" :sortable="true"></Column>
                        <Column field="status" header="Status" :sortable="true">
                            <template #body="slotProps">
                                <Tag :value="getStatusLabel(slotProps.data.status)" :severity="getStatusSeverity(slotProps.data.status)" />
                            </template>
                        </Column>
                        <Column field="created_at" header="Data" :sortable="true">
                            <template #body="slotProps">
                                {{ formatDate(slotProps.data.created_at) }}
                            </template>
                        </Column>
                        <Column>
                            <template #body="slotProps">
                                <Button icon="pi pi-eye" severity="info" text rounded @click="viewRequest(slotProps.data)" />
                            </template>
                        </Column>
                    </DataTable>
                    <Message v-else severity="info">Nenhuma solicitação encontrada</Message>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const route = useRoute();
const toast = useToast();

const user = ref(null);
const loading = ref(false);

onMounted(() => {
    loadUser();
});

const loadUser = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/api/users/${route.params.id}`);
        user.value = response.data;
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: 'Erro ao carregar usuário', life: 3000 });
        goBack();
    } finally {
        loading.value = false;
    }
};

const editUser = () => {
    router.push(`/admin/users/${route.params.id}/edit`);
};

const goBack = () => {
    router.push('/admin/users');
};

const viewRequest = (request) => {
    router.push(`/admin/stock/requests/${request.id}`);
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
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

const getStatusLabel = (status) => {
    const statusMap = {
        solicitado: 'Solicitado',
        preparando: 'Preparando',
        enviando: 'Enviando',
        recebido: 'Recebido',
        entregue: 'Entregue',
        cancelado: 'Cancelado'
    };
    return statusMap[status] || status;
};

const getStatusSeverity = (status) => {
    const severityMap = {
        solicitado: 'info',
        preparando: 'warning',
        enviando: 'warning',
        recebido: 'success',
        entregue: 'success',
        cancelado: 'danger'
    };
    return severityMap[status] || 'info';
};
</script>
