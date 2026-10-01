<template>
    <div class="grid">
        <div class="col-12">
            <div class="card">
                <Toast />
                
                <!-- Header -->
                <div class="flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="m-0">Solicitação {{ request.request_number }}</h5>
                        <Tag :value="request.status" :severity="getStatusSeverity(request.status)" class="mt-2" />
                    </div>
                    <div class="flex gap-2">
                        <Button label="Voltar" icon="pi pi-arrow-left" class="p-button-text" @click="router.back()" />
                        <Button label="Editar" icon="pi pi-pencil" v-if="canEdit" @click="editRequest" />
                    </div>
                </div>

                <Divider />

                <!-- Informações da Solicitação -->
                <div class="grid">
                    <div class="col-12 md:col-6">
                        <h6>Informações do Item</h6>
                        <p><strong>Item:</strong> {{ request.item?.name }}</p>
                        <p><strong>Código:</strong> {{ request.item?.code }}</p>
                        <p><strong>Quantidade:</strong> {{ request.quantity }} {{ request.item?.unit }}</p>
                        <p><strong>Stock Disponível:</strong> {{ request.item?.current_stock }} {{ request.item?.unit }}</p>
                    </div>

                    <div class="col-12 md:col-6">
                        <h6>Detalhes da Solicitação</h6>
                        <p><strong>Referência:</strong> {{ request.reference }}</p>
                        <p><strong>Work Order:</strong> {{ request.work_order }}</p>
                        <p><strong>ID Equipamento:</strong> {{ request.equipment_id }}</p>
                        <p><strong>Motivo:</strong> {{ request.replacement_reason }}</p>
                    </div>

                    <div class="col-12 md:col-6">
                        <h6>Solicitante</h6>
                        <p><strong>Nome:</strong> {{ request.requester?.name }}</p>
                        <p><strong>Email:</strong> {{ request.requester?.email }}</p>
                        <p><strong>Departamento:</strong> {{ request.requester?.department.name }}</p>
                    </div>

                    <div class="col-12 md:col-6">
                        <h6>Datas</h6>
                        <p><strong>Solicitado em:</strong> {{ formatDate(request.requested_at) }}</p>
                        <p v-if="request.completed_at"><strong>Concluído em:</strong> {{ formatDate(request.completed_at) }}</p>
                    </div>

                    <div class="col-12" v-if="request.notes">
                        <h6>Observações</h6>
                        <p>{{ request.notes }}</p>
                    </div>
                </div>

                <Divider />

                <!-- Ações de Workflow -->
                <div class="flex flex-wrap gap-2 mb-4" v-if="showWorkflowActions">
                    <Button label="Preparar" icon="pi pi-box" severity="warning" @click="prepareRequest" v-if="request.status === 'solicitado' && canManageStock" />
                    <Button label="Enviar" icon="pi pi-send" severity="info" @click="sendRequest" v-if="request.status === 'preparando' && canManageStock" />
                    <Button label="Confirmar Recebimento" icon="pi pi-check" severity="success" @click="receiveRequest" v-if="request.status === 'enviando'" />
                    <Button label="Confirmar Entrega" icon="pi pi-check-circle" severity="success" @click="deliverRequest" v-if="request.status === 'recebido'" />
                    <Button label="Cancelar" icon="pi pi-times" severity="danger" class="p-button-outlined" @click="confirmCancel" v-if="request.status === 'solicitado'" />
                </div>

                <!-- Histórico de Status -->
                <h6>Histórico de Status</h6>
                <Timeline :value="statusHistory" align="left" class="customized-timeline">
                    <template #marker="slotProps">
                        <span class="flex w-2rem h-2rem align-items-center justify-content-center text-white border-circle z-1 shadow-1" :style="{backgroundColor: getTimelineColor(slotProps.item.to_status)}">
                            <i :class="getTimelineIcon(slotProps.item.to_status)"></i>
                        </span>
                    </template>
                    <template #content="slotProps">
                        <Card>
                            <template #title>
                                {{ slotProps.item.to_status }}
                            </template>
                            <template #subtitle>
                                {{ formatDate(slotProps.item.changed_at) }}
                            </template>
                            <template #content>
                                <p><strong>Por:</strong> {{ slotProps.item.changed_by?.name }}</p>
                                <p v-if="slotProps.item.notes"><strong>Observações:</strong> {{ slotProps.item.notes }}</p>
                            </template>
                        </Card>
                    </template>
                </Timeline>

                <!-- Movimentações de Stock -->
                <div v-if="stockMovements.length > 0">
                    <Divider />
                    <h6>Movimentações de Stock</h6>
                    <DataTable :value="stockMovements">
                        <Column field="movement_type" header="Tipo">
                            <template #body="slotProps">
                                <Tag :value="slotProps.data.movement_type" :severity="slotProps.data.movement_type === 'entrada' ? 'success' : 'danger'" />
                            </template>
                        </Column>
                        <Column field="quantity" header="Quantidade"></Column>
                        <Column field="previous_stock" header="Stock Anterior"></Column>
                        <Column field="new_stock" header="Novo Stock"></Column>
                        <Column field="user.name" header="Usuário"></Column>
                        <Column field="movement_date" header="Data">
                            <template #body="slotProps">
                                {{ formatDate(slotProps.data.movement_date) }}
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
        </div>
    </div>

    <!-- Dialog de Confirmação -->
    <Dialog v-model:visible="actionDialog.visible" :style="{width: '450px'}" :header="actionDialog.title" :modal="true">
        <div class="field">
            <label for="notes">Observações</label>
            <Textarea v-model="actionDialog.notes" id="notes" rows="3" class="w-full" />
        </div>
        <template #footer>
            <Button label="Cancelar" icon="pi pi-times" text @click="actionDialog.visible = false"/>
            <Button :label="actionDialog.confirmLabel" icon="pi pi-check" @click="executeAction" :loading="loading" />
        </template>
    </Dialog>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const request= ref({});
const statusHistory = ref([]);
const stockMovements = ref([]);
const loading = ref(false);

const actionDialog = ref({
    visible: false,
    title: '',
    confirmLabel: '',
    action: null,
    notes: ''
});

// Permissões (em produção, isso viria do store/auth)
const canManageStock = ref(true); // Simular permissão

const canEdit = computed(() => {
    return request.value.status === 'solicitado';
});

const showWorkflowActions = computed(() => {
    return !['entregue', 'cancelado'].includes(request.value.status);
});

onMounted(async () => {
    await loadRequest();
});

const loadRequest = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/api/requests/${route.params.id}`);
        request.value = response.data;
        statusHistory.value = response.data.status_history || [];
        stockMovements.value = response.data.stock_movements || [];
    } catch (error) {
        toast.add({severity:'error', summary:'Erro', detail:'Erro ao carregar solicitação', life: 3000});
    } finally {
        loading.value = false;
    }
};

const prepareRequest = () => {
    showActionDialog('Preparar Pedido', 'Preparar', () => performWorkflowAction('prepare'));
};

const sendRequest = () => {
    showActionDialog('Enviar Pedido', 'Enviar', () => performWorkflowAction('send'));
};

const receiveRequest = () => {
    showActionDialog('Confirmar Recebimento', 'Confirmar', () => performWorkflowAction('receive'));
};

const deliverRequest = () => {
    showActionDialog('Confirmar Entrega', 'Confirmar', () => performWorkflowAction('deliver'));
};

const confirmCancel = () => {
    showActionDialog('Cancelar Pedido', 'Cancelar', () => performWorkflowAction('cancel'));
};

const showActionDialog = (title, confirmLabel, action) => {
    actionDialog.value = {
        visible: true,
        title,
        confirmLabel,
        action,
        notes: ''
    };
};

const executeAction = () => {
    if (actionDialog.value.action) {
        actionDialog.value.action();
    }
};

const performWorkflowAction = async (action) => {
    loading.value = true;
    try {
        await axios.post(`/api/requests/${request.value.id}/${action}`, {
            notes: actionDialog.value.notes
        });

        toast.add({severity:'success', summary:'Sucesso', detail:`Ação realizada com sucesso`, life: 3000});
        actionDialog.value.visible = false;
        actionDialog.value.notes = '';
        await loadRequest();
    } catch (error) {
        toast.add({severity:'error', summary:'Erro', detail:error.response?.data?.message || 'Erro ao executar ação', life: 3000});
    } finally {
        loading.value = false;
    }
};

const editRequest = () => {
    router.push(`/admin/stock/requests/${request.value.id}/edit`);
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

const getTimelineColor = (status) => {
    const colorMap = {
        solicitado: '#3B82F6',
        preparando: '#F59E0B',
        enviando: '#06B6D4',
        recebido: '#10B981',
        entregue: '#059669',
        cancelado: '#EF4444'
    };
    return colorMap[status] || '#6B7280';
};

const getTimelineIcon = (status) => {
    const iconMap = {
        solicitado: 'pi pi-inbox',
        preparando: 'pi pi-box',
        enviando: 'pi pi-send',
        recebido: 'pi pi-check',
        entregue: 'pi pi-check-circle',
        cancelado: 'pi pi-times'
    };
    return iconMap[status] || 'pi pi-circle-fill';
};

const formatDate = (date) => {
    return date ? new Date(date).toLocaleString('pt-PT') : '-';
};
</script>
