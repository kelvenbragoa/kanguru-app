<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const requests = ref([]);

onMounted(async () => {
    try {
        const response = await axios.get('/api/dashboard/recent-requests');
        requests.value = response.data;
    } catch (error) {
        console.error('Erro ao carregar solicitações:', error);
    }
});

const getStatusSeverity = (status) => {
    const severityMap = {
        solicitado: 'info',
        preparando: 'warn',
        enviando: 'warn',
        recebido: 'success',
        entregue: 'success',
        cancelado: 'danger'
    };
    return severityMap[status] || 'info';
};

const viewRequest = (id) => {
    router.push(`/admin/stock/requests/${id}`);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-PT', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
};
</script>

<template>
    <div class="card">
        <div class="flex items-center justify-between mb-6">
            <div class="font-semibold text-xl">Solicitações Recentes</div>
            <Button label="Ver Todas" icon="pi pi-arrow-right" iconPos="right" text @click="router.push('/admin/stock/requests')" />
        </div>
        <DataTable :value="requests" :rows="5" responsiveLayout="scroll" :loading="requests.length === 0">
            <Column field="request_number" header="Nº Pedido" style="min-width: 120px;">
                <template #body="slotProps">
                    <strong>{{ slotProps.data.request_number }}</strong>
                </template>
            </Column>
            <Column field="item.name" header="Item" style="min-width: 180px;"></Column>
            <Column field="quantity" header="Qtd" style="min-width: 100px;">
                <template #body="slotProps">
                    {{ slotProps.data.quantity }} {{ slotProps.data.item?.unit }}
                </template>
            </Column>
            <Column field="status" header="Status" style="min-width: 120px;">
                <template #body="slotProps">
                    <Tag :value="slotProps.data.status" :severity="getStatusSeverity(slotProps.data.status)" />
                </template>
            </Column>
            <Column field="requested_at" header="Data" style="min-width: 110px;">
                <template #body="slotProps">
                    {{ formatDate(slotProps.data.requested_at) }}
                </template>
            </Column>
            <Column header="Ação" style="min-width: 80px;">
                <template #body="slotProps">
                    <Button icon="pi pi-eye" text rounded severity="info" @click="viewRequest(slotProps.data.id)" v-tooltip.top="'Visualizar'" />
                </template>
            </Column>
        </DataTable>
    </div>
</template>
