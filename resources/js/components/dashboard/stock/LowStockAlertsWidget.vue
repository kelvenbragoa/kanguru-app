<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const lowStockItems = ref([]);

onMounted(async () => {
    try {
        const response = await axios.get('/api/dashboard/alerts');
        lowStockItems.value = response.data.low_stock_items || [];
    } catch (error) {
        console.error('Erro ao carregar alertas:', error);
    }
});

const viewItem = (id) => {
    router.push(`/admin/stock/items/${id}`);
};

const getStockPercentage = (item) => {
    if (!item.min_stock || item.min_stock === 0) return 0;
    return Math.round((item.current_stock / item.min_stock) * 100);
};

const getSeverityClass = (percentage) => {
    if (percentage <= 25) return 'bg-red-500';
    if (percentage <= 50) return 'bg-orange-500';
    return 'bg-yellow-500';
};
</script>

<template>
    <div class="card">
        <div class="flex items-center justify-between mb-6">
            <div class="font-semibold text-xl">Alertas de Stock</div>
            <Button label="Ver Itens" icon="pi pi-arrow-right" iconPos="right" text @click="router.push('/admin/stock/items')" />
        </div>

        <div v-if="lowStockItems.length === 0" class="text-center py-6">
            <i class="pi pi-check-circle text-green-500 text-5xl mb-3"></i>
            <p class="text-muted-color">Nenhum item com stock crítico</p>
        </div>

        <ul v-else class="p-0 m-0 list-none">
            <li v-for="(item, index) in lowStockItems.slice(0, 6)" :key="item.id" 
                :class="['flex items-center py-3', index < lowStockItems.length - 1 ? 'border-b border-surface' : '']">
                <div class="w-12 h-12 flex items-center justify-center bg-red-100 dark:bg-red-400/10 rounded-full mr-4 shrink-0">
                    <i class="pi pi-exclamation-triangle !text-xl text-red-500"></i>
                </div>
                <div class="flex-1">
                    <div class="text-surface-900 dark:text-surface-0 font-medium mb-1">
                        {{ item.name }}
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-muted-color text-sm">
                            {{ item.current_stock }} {{ item.unit }}
                        </span>
                        <span class="text-muted-color text-sm">|</span>
                        <span class="text-muted-color text-sm">
                            Mín: {{ item.min_stock }} {{ item.unit }}
                        </span>
                    </div>
                    <div class="mt-2 w-full bg-surface-200 dark:bg-surface-700 rounded-full h-2">
                        <div 
                            :class="['h-2 rounded-full transition-all', getSeverityClass(getStockPercentage(item))]"
                            :style="{ width: `${Math.min(getStockPercentage(item), 100)}%` }"
                        ></div>
                    </div>
                </div>
                <Button icon="pi pi-arrow-right" text rounded severity="secondary" @click="viewItem(item.id)" class="ml-2" />
            </li>
        </ul>

        <div v-if="lowStockItems.length > 6" class="mt-4 text-center">
            <span class="text-muted-color">
                +{{ lowStockItems.length - 6 }} itens adicionais com stock baixo
            </span>
        </div>
    </div>
</template>
