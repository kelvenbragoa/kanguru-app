<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const stats = ref({
    requests: {},
    items_total: 0,
    items_low_stock: 0
});

onMounted(async () => {
    try {
        const response = await axios.get('/api/dashboard/stats');
        stats.value = response.data;
    } catch (error) {
        console.error('Erro ao carregar estatísticas:', error);
    }
});
</script>

<template>
    <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <div class="card mb-0">
            <div class="flex justify-between mb-4">
                <div>
                    <span class="block text-muted-color font-medium mb-4">Solicitados</span>
                    <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">{{ stats.requests?.solicitado || 0 }}</div>
                </div>
                <div class="flex items-center justify-center bg-blue-100 dark:bg-blue-400/10 rounded-lg" style="width: 2.5rem; height: 2.5rem">
                    <i class="pi pi-inbox text-blue-500 !text-xl"></i>
                </div>
            </div>
            <span class="text-primary font-medium">Aguardando </span>
            <span class="text-muted-color">preparação</span>
        </div>
    </div>
    <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <div class="card mb-0">
            <div class="flex justify-between mb-4">
                <div>
                    <span class="block text-muted-color font-medium mb-4">Preparando</span>
                    <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">{{ stats.requests?.preparando || 0 }}</div>
                </div>
                <div class="flex items-center justify-center bg-orange-100 dark:bg-orange-400/10 rounded-lg" style="width: 2.5rem; height: 2.5rem">
                    <i class="pi pi-box text-orange-500 !text-xl"></i>
                </div>
            </div>
            <span class="text-primary font-medium">Em processo </span>
            <span class="text-muted-color">no armazém</span>
        </div>
    </div>
    <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <div class="card mb-0">
            <div class="flex justify-between mb-4">
                <div>
                    <span class="block text-muted-color font-medium mb-4">Enviando</span>
                    <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">{{ stats.requests?.enviando || 0 }}</div>
                </div>
                <div class="flex items-center justify-center bg-cyan-100 dark:bg-cyan-400/10 rounded-lg" style="width: 2.5rem; height: 2.5rem">
                    <i class="pi pi-send text-cyan-500 !text-xl"></i>
                </div>
            </div>
            <span class="text-primary font-medium">Em trânsito </span>
            <span class="text-muted-color">para entrega</span>
        </div>
    </div>
    <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <div class="card mb-0">
            <div class="flex justify-between mb-4">
                <div>
                    <span class="block text-muted-color font-medium mb-4">Itens Críticos</span>
                    <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">{{ stats.items_low_stock || 0 }}</div>
                </div>
                <div class="flex items-center justify-center bg-red-100 dark:bg-red-400/10 rounded-lg" style="width: 2.5rem; height: 2.5rem">
                    <i class="pi pi-exclamation-triangle text-red-500 !text-xl"></i>
                </div>
            </div>
            <span class="text-red-500 font-medium">Stock baixo </span>
            <span class="text-muted-color">requer atenção</span>
        </div>
    </div>
</template>
