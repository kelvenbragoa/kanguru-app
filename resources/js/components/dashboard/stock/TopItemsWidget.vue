<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const topItems = ref([]);

onMounted(async () => {
    try {
        const response = await axios.get('/api/dashboard/top-items');
        topItems.value = response.data;
    } catch (error) {
        console.error('Erro ao carregar top itens:', error);
    }
});

const viewItem = (id) => {
    router.push(`/admin/stock/items/${id}`);
};

const getMaxQuantity = () => {
    if (topItems.value.length === 0) return 0;
    return Math.max(...topItems.value.map(item => item.total_requested || 0));
};

const getBarWidth = (quantity) => {
    const max = getMaxQuantity();
    if (max === 0) return 0;
    return Math.round((quantity / max) * 100);
};
</script>

<template>
    <div class="card">
        <div class="flex items-center justify-between mb-6">
            <div class="font-semibold text-xl">Itens Mais Requisitados</div>
            <Button label="Ver Relatório" icon="pi pi-chart-bar" iconPos="right" text />
        </div>

        <div v-if="topItems.length === 0" class="text-center py-6">
            <i class="pi pi-inbox text-muted-color text-5xl mb-3"></i>
            <p class="text-muted-color">Nenhuma requisição registrada</p>
        </div>

        <ul v-else class="p-0 m-0 list-none">
            <li v-for="(item, index) in topItems" :key="item.id" 
                :class="['py-3', index < topItems.length - 1 ? 'border-b border-surface' : '']">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center bg-primary text-primary-contrast rounded-full font-bold" 
                             style="width: 2rem; height: 2rem; font-size: 0.875rem;">
                            {{ index + 1 }}
                        </div>
                        <div>
                            <div class="text-surface-900 dark:text-surface-0 font-medium">
                                {{ item.name }}
                            </div>
                            <div class="text-muted-color text-sm">
                                Código: {{ item.code }}
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-primary">{{ item.total_requested || 0 }}</span>
                        <span class="text-muted-color text-sm">{{ item.unit }}</span>
                        <Button icon="pi pi-eye" text rounded severity="secondary" size="small" @click="viewItem(item.id)" />
                    </div>
                </div>
                <div class="w-full bg-surface-200 dark:bg-surface-700 rounded-full h-2">
                    <div 
                        class="bg-primary h-2 rounded-full transition-all"
                        :style="{ width: `${getBarWidth(item.total_requested || 0)}%` }"
                    ></div>
                </div>
            </li>
        </ul>
    </div>
</template>
