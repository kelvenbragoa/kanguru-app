<script setup>
import StockStatsWidget from '@/components/dashboard/stock/StockStatsWidget.vue';
import RecentRequestsWidget from '@/components/dashboard/stock/RecentRequestsWidget.vue';
import LowStockAlertsWidget from '@/components/dashboard/stock/LowStockAlertsWidget.vue';
import TopItemsWidget from '@/components/dashboard/stock/TopItemsWidget.vue';
</script>

<template>
    <div class="grid grid-cols-12 gap-8">
        <StockStatsWidget />

        <div class="col-span-12 xl:col-span-6">
            <RecentRequestsWidget />
        </div>
        <div class="col-span-12 xl:col-span-6">
            <LowStockAlertsWidget />
        </div>
        <div class="col-span-12">
            <TopItemsWidget />
        </div>
    </div>
</template>
