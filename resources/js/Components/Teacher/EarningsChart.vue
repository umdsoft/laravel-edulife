<script setup>
import { computed } from 'vue';

const props = defineProps({
    data: Array,
});

// Simple bar chart implementation using CSS/HTML
// In a real app, use Chart.js or ApexCharts
const maxAmount = computed(() => {
    if (!props.data || props.data.length === 0) return 100;
    return Math.max(...props.data.map(d => d.amount));
});

const getHeight = (amount) => {
    if (maxAmount.value === 0) return '0%';
    return `${(amount / maxAmount.value) * 100}%`;
};
</script>

<template>
    <div class="h-64 flex items-end justify-between gap-2">
        <div v-for="(item, index) in data" :key="index" class="flex-1 flex flex-col items-center gap-2 group">
            <div class="relative w-full flex items-end justify-center h-full">
                <div class="w-full max-w-[40px] bg-emerald-100 rounded-t-lg group-hover:bg-emerald-200 transition-colors relative"
                    :style="{ height: getHeight(item.amount) }">
                    <!-- Tooltip -->
                    <div
                        class="absolute -top-10 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-xs py-1 px-2 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10">
                        {{ new Intl.NumberFormat('uz-UZ').format(item.amount) }} UZS
                    </div>
                </div>
            </div>
            <span class="text-xs text-gray-400 font-medium">{{ item.month }}</span>
        </div>
    </div>
</template>
