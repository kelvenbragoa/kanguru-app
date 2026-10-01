<template>
    <div class="grid">
        <div class="col-12">
            <div class="card">
                <Toast />
                
                <div class="flex justify-content-between align-items-center mb-4">
                    <h5>Detalhes do Item</h5>
                    <div class="flex gap-2">
                        <Button label="Editar" icon="pi pi-pencil" @click="editItem" />
                        <Button label="Voltar" icon="pi pi-arrow-left" severity="secondary" @click="goBack" />
                    </div>
                </div>

                <div v-if="loading" class="flex justify-content-center align-items-center" style="min-height: 300px;">
                    <ProgressSpinner />
                </div>

                <div v-else-if="item">
                    <!-- Informações Principais -->
                    <Panel header="Informações Gerais" class="mb-4">
                        <div class="grid">
                            <div class="col-12 md:col-6">
                                <div class="field">
                                    <label class="font-semibold">Código:</label>
                                    <p class="text-lg">{{ item.code }}</p>
                                </div>
                            </div>
                            <div class="col-12 md:col-6">
                                <div class="field">
                                    <label class="font-semibold">Nome:</label>
                                    <p class="text-lg">{{ item.name }}</p>
                                </div>
                            </div>
                            <div class="col-12" v-if="item.description">
                                <div class="field">
                                    <label class="font-semibold">Descrição:</label>
                                    <p>{{ item.description }}</p>
                                </div>
                            </div>
                            <div class="col-12 md:col-4">
                                <div class="field">
                                    <label class="font-semibold">Unidade:</label>
                                    <p>{{ item.unit }}</p>
                                </div>
                            </div>
                            <div class="col-12 md:col-4">
                                <div class="field">
                                    <label class="font-semibold">Categoria:</label>
                                    <p>{{ item.category.name || '-' }}</p>
                                </div>
                            </div>
                            <div class="col-12 md:col-4">
                                <div class="field">
                                    <label class="font-semibold">Localização:</label>
                                    <p>{{ item.location || '-' }}</p>
                                </div>
                            </div>
                            <div class="col-12 md:col-3">
                                <div class="field">
                                    <label class="font-semibold">Status:</label>
                                    <div>
                                        <Tag :value="item.is_active ? 'Ativo' : 'Inativo'" :severity="item.is_active ? 'success' : 'danger'" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Panel>

                    <!-- Informações de Stock -->
                    <Panel header="Informações de Stock" class="mb-4">
                        <div class="grid">
                            <div class="col-12 md:col-4">
                                <div class="field">
                                    <label class="font-semibold">Stock Atual:</label>
                                    <p class="text-2xl font-bold" :class="{'text-red-500': item.current_stock <= item.min_stock}">
                                        {{ item.current_stock }} {{ item.unit }}
                                        <i v-if="item.current_stock <= item.min_stock" class="pi pi-exclamation-triangle ml-2" v-tooltip.top="'Stock abaixo do mínimo'"></i>
                                    </p>
                                </div>
                            </div>
                            <div class="col-12 md:col-4">
                                <div class="field">
                                    <label class="font-semibold">Stock Mínimo:</label>
                                    <p class="text-xl">{{ item.min_stock }} {{ item.unit }}</p>
                                </div>
                            </div>
                            <div class="col-12 md:col-4">
                                <div class="field">
                                    <label class="font-semibold">Stock Máximo:</label>
                                    <p class="text-xl">{{ item.max_stock }} {{ item.unit }}</p>
                                </div>
                            </div>
                        </div>
                    </Panel>

                    <!-- Últimos Movimentos -->
                    <Panel header="Últimos Movimentos de Stock" v-if="item.stock_movements && item.stock_movements.length > 0">
                        <DataTable :value="item.stock_movements" responsiveLayout="scroll">
                            <Column field="movement_date" header="Data" :sortable="true">
                                <template #body="slotProps">
                                    {{ formatDate(slotProps.data.movement_date) }}
                                </template>
                            </Column>
                            <Column field="type" header="Tipo">
                                <template #body="slotProps">
                                    <Tag :value="getMovementTypeLabel(slotProps.data.type)" :severity="getMovementTypeSeverity(slotProps.data.type)" />
                                </template>
                            </Column>
                            <Column field="quantity" header="Quantidade">
                                <template #body="slotProps">
                                    <span :class="slotProps.data.type === 'entrada' ? 'text-green-500' : 'text-red-500'">
                                        {{ slotProps.data.type === 'entrada' ? '+' : '-' }}{{ slotProps.data.quantity }} {{ item.unit }}
                                    </span>
                                </template>
                            </Column>
                            <Column field="stock_after" header="Stock Após">
                                <template #body="slotProps">
                                    {{ slotProps.data.stock_after }} {{ item.unit }}
                                </template>
                            </Column>
                            <Column field="user.name" header="Responsável"></Column>
                            <Column field="notes" header="Observações">
                                <template #body="slotProps">
                                    {{ slotProps.data.notes || '-' }}
                                </template>
                            </Column>
                        </DataTable>
                    </Panel>
                    <Panel v-else header="Últimos Movimentos de Stock">
                        <p class="text-center text-gray-500">Nenhum movimento registrado</p>
                    </Panel>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const item = ref(null);
const loading = ref(false);

onMounted(() => {
    loadItem();
});

const loadItem = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/api/items/${route.params.id}`);
        item.value = response.data;
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Erro',
            detail: 'Erro ao carregar item',
            life: 3000
        });
        router.push('/admin/stock/items');
    } finally {
        loading.value = false;
    }
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getMovementTypeLabel = (type) => {
    const labels = {
        'entrada': 'Entrada',
        'saida': 'Saída',
        'ajuste': 'Ajuste',
        'devolucao': 'Devolução'
    };
    return labels[type] || type;
};

const getMovementTypeSeverity = (type) => {
    const severities = {
        'entrada': 'success',
        'saida': 'danger',
        'ajuste': 'warning',
        'devolucao': 'info'
    };
    return severities[type] || 'secondary';
};

const editItem = () => {
    router.push(`/admin/stock/items/${route.params.id}/edit`);
};

const goBack = () => {
    router.push('/admin/stock/items');
};
</script>
