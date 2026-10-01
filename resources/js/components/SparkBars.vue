<script setup>
import { computed } from 'vue';

const props = defineProps({
    items: { type: Array, default: () => [] },
    valueKey: { type: String, default: 'value' },
    labelKey: { type: String, default: 'label' },
    color: { type: String, default: '#f59e0b' },
});

const max = computed(() => Math.max(...props.items.map((item) => Number(item[props.valueKey] || 0)), 1));
</script>

<template>
    <div class="spark">
        <div v-for="item in items" :key="item[labelKey]" class="spark-col">
            <div
                class="spark-bar"
                :style="{ height: `${(Number(item[valueKey] || 0) / max) * 100}%`, background: color }"
                :title="`${item[labelKey]}: ${item[valueKey]}`"
            ></div>
            <span>{{ item[labelKey] }}</span>
        </div>
    </div>
</template>

<style scoped>
.spark {
    display: flex;
    align-items: flex-end;
    gap: 0.35rem;
    height: 10rem;
}
.spark-col {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    height: 100%;
    justify-content: flex-end;
    min-width: 0;
}
.spark-bar {
    width: 100%;
    min-height: 2px;
    border-radius: 0.35rem 0.35rem 0 0;
}
.spark-col span {
    font-size: 0.65rem;
    color: #6b7280;
    margin-top: 0.35rem;
    white-space: nowrap;
}
</style>
