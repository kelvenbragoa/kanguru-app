<script setup>
import { ref, onMounted, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';

const toast = useToast();
const loading = ref(false);
const requests = ref([]);
const successSound = new Audio('/sound.mp3');

const statuses = [
    { key: 'solicitado', label: 'Solicitado', color: 'blue', icon: 'pi-inbox', nextAction: 'prepare', nextLabel: 'Preparar' },
    { key: 'preparando', label: 'Preparando', color: 'orange', icon: 'pi-box', nextAction: 'send', nextLabel: 'Enviar' },
    { key: 'enviando', label: 'Enviando', color: 'cyan', icon: 'pi-send', nextAction: 'receive', nextLabel: 'Confirmar Recebimento' },
    { key: 'recebido', label: 'Recebido', color: 'teal', icon: 'pi-check-square', nextAction: 'deliver', nextLabel: 'Entregar' },
    { key: 'entregue', label: 'Entregue', color: 'green', icon: 'pi-check-circle', nextAction: null, nextLabel: null }
];

const getRequestsByStatus = (status) => {
    return requests.value.filter(req => req.status === status);
};

const loadRequests = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/requests', {
            params: {
                per_page: 1000 // Carregar todas
            }
        });
        requests.value = response.data.data.filter(req => req.status !== 'cancelado');
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Erro',
            detail: 'Erro ao carregar solicitações',
            life: 3000
        });
    } finally {
        loading.value = false;
    }
};

const moveToNextStatus = async (request, action) => {
    try {
        await axios.post(`/api/requests/${request.id}/${action}`);
        toast.add({
            severity: 'success',
            summary: 'Sucesso',
            detail: 'Solicitação movida com sucesso',
            life: 3000
        });
        successSound.currentTime = 0;
        successSound.play();
        await loadRequests();
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Erro',
            detail: error.response?.data?.message || 'Erro ao mover solicitação',
            life: 5000
        });
    }
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-PT', {
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getStatusColor = (statusKey) => {
    const status = statuses.find(s => s.key === statusKey);
    return status?.color || 'secondary';
};

onMounted(() => {
    loadRequests();
});
</script>

<template>
    <div class="grid">
        <div class="col-12">
            <div class="card">
                <Toast />
                <div class="flex justify-content-between align-items-center mb-4">
                    <h5 class="m-0">Workflow de Solicitações</h5>
                    <Button label="Atualizar" icon="pi pi-refresh" :loading="loading" @click="loadRequests" outlined />
                </div>

                <!-- Container com scroll horizontal para as colunas -->
                <div class="kanban-container">
                    <div class="kanban-board">
                        <div v-for="status in statuses" :key="status.key" class="kanban-column">
                            <!-- Cabeçalho da Coluna -->
                            <div :class="['column-header', `bg-${status.color}-100`]">
                                <div class="flex align-items-center justify-content-center gap-2">
                                    <i :class="['pi', status.icon, `text-${status.color}-700`]"></i>
                                    <span :class="['font-bold', `text-${status.color}-700`]">{{ status.label }}</span>
                                </div>
                                <div :class="['text-sm mt-2', `text-${status.color}-600`]">
                                    {{ getRequestsByStatus(status.key).length }} solicitações
                                </div>
                            </div>

                            <!-- Lista de Cards -->
                            <div class="column-cards">
                                <div v-if="getRequestsByStatus(status.key).length === 0" class="text-center p-4 surface-50 border-round">
                                    <i class="pi pi-inbox text-400 text-3xl mb-2"></i>
                                    <p class="text-500 m-0">Nenhuma solicitação</p>
                                </div>

                                <div 
                                    v-for="request in getRequestsByStatus(status.key)" 
                                    :key="request.id"
                                    class="kanban-card"
                                    :style="`border-left-color: var(--${status.color}-500)`"
                                >
                                    <!-- Número do Pedido -->
                                    <div class="flex justify-content-between align-items-start mb-2">
                                        <span class="font-bold text-primary">{{ request.request_number }}</span>
                                        <Tag :value="request.status" :severity="getStatusColor(request.status)" />
                                    </div>

                                    <!-- Item -->
                                    <div class="mb-2">
                                        <div class="text-900 font-medium mb-1">{{ request.item?.name }}</div>
                                        <div class="text-600 text-sm">
                                            <i class="pi pi-box mr-1"></i>
                                            {{ request.quantity }} {{ request.item?.unit }}
                                        </div>
                                    </div>

                                    <!-- Informações Adicionais -->
                                    <div class="text-sm text-600 mb-2">
                                        <div class="flex align-items-center mb-1">
                                            <i class="pi pi-user mr-2"></i>
                                            <span>{{ request.requester?.name }}</span>
                                        </div>
                                        <div class="flex align-items-center mb-1">
                                            <i class="pi pi-wrench mr-2"></i>
                                            <span>WO: {{ request.work_order }}</span>
                                        </div>
                                        <div class="flex align-items-center mb-1">
                                            <i class="pi pi-map-marker mr-2"></i>
                                            <span>Loc: {{ request.item.location }}</span>
                                        </div>
                                        <div class="flex align-items-center mb-1">
                                            <i class="pi pi-calendar mr-2"></i>
                                            <span>{{ formatDate(request.requested_at) }}</span>
                                        </div>
                                    </div>

                                    <!-- Última Mudança de Estado -->
                                    <div v-if="request.status_history && request.status_history.length > 0" class="text-xs text-500 border-top-1 surface-border pt-2">
                                        <div class="flex align-items-center gap-1">
                                            <i class="pi pi-history"></i>
                                            <span class="font-medium">{{ request.status_history[0].changed_by?.name }}</span>
                                        </div>
                                        <div class="mt-1">{{ formatDate(request.status_history[0].changed_at) }}</div>
                                    </div>

                                    <!-- Botão de Ação -->
                                    <div v-if="status.nextAction" class="mt-3">
                                        <Button 
                                            :label="status.nextLabel"
                                            icon="pi pi-arrow-right"
                                            iconPos="right"
                                            :severity="status.color"
                                            class="w-full"
                                            size="small"
                                            @click="moveToNextStatus(request, status.nextAction)"
                                        />
                                    </div>

                                    <!-- Badge de Concluído -->
                                    <div v-else class="mt-3">
                                        <div class="text-center p-2 bg-green-50 border-round">
                                            <i class="pi pi-check text-green-600 mr-2"></i>
                                            <span class="text-green-700 font-medium text-sm">Concluído</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Container Kanban com scroll horizontal */
.kanban-container {
    width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
    padding-bottom: 1rem;
}

.kanban-board {
    display: flex;
    gap: 1.5rem;
    min-height: 70vh;
    padding: 0.5rem;
}

/* Coluna individual */
.kanban-column {
    flex: 0 0 320px;
    min-width: 320px;
    max-width: 320px;
    display: flex;
    flex-direction: column;
    background: var(--surface-ground);
    border-radius: 8px;
    padding: 0.75rem;
}

/* Cabeçalho da coluna */
.column-header {
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 8px;
    text-align: center;
    flex-shrink: 0;
}

/* Container dos cards dentro da coluna */
.column-cards {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    padding-right: 0.5rem;
}

/* Card individual */
.kanban-card {
    background: white;
    padding: 1rem;
    border-radius: 8px;
    border-left: 3px solid;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    cursor: pointer;
}

.kanban-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transform: translateY(-2px);
}

/* Scrollbar personalizado para o container horizontal */
.kanban-container::-webkit-scrollbar {
    height: 10px;
}

.kanban-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 5px;
}

.kanban-container::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 5px;
}

.kanban-container::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Scrollbar personalizado para as colunas */
.column-cards::-webkit-scrollbar {
    width: 6px;
}

.column-cards::-webkit-scrollbar-track {
    background: transparent;
}

.column-cards::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 3px;
}

.column-cards::-webkit-scrollbar-thumb:hover {
    background: #a0aec0;
}
</style>
