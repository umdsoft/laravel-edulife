<template>
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <div class="flex items-start justify-between mb-4">
            <!-- Icon Placeholder -->
            <div class="p-3 rounded-xl text-2xl" :style="{ backgroundColor: iconBgColor }">
                {{ iconEmoji }}
            </div>

            <!-- Trend -->
            <span v-if="trend" class="flex items-center gap-1 text-sm font-medium text-green-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M5 10l7-7m0 0l7 7m-7-7v18" />
                </svg>
                {{ trend }}%
            </span>
        </div>

        <!-- Value -->
        <div class="mb-2">
            <span class="text-3xl font-bold text-gray-900">
                {{ formattedValue }}
            </span>
        </div>

        <!-- Title -->
        <div class="text-sm text-gray-500">
            {{ title }}
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: String,
    value: [String, Number],
    color: {
        type: String,
        default: 'blue',
    },
    trend: [String, Number],
    formatAsCurrency: Boolean,
});

// Icon mapping based on color
const iconEmoji = computed(() => {
    const icons = {
        blue: '👥',
        green: '👨‍🏫',
        purple: '📚',
        yellow: '💰',
    };
    return icons[props.color] || '📊';
});

const iconBgColor = computed(() => {
    const colors = {
        blue: '#3B82F620',
        green: '#05966920',
        purple: '#7C3AED20',
        yellow: '#F5950720',
    };
    return colors[props.color] || '#E5E7EB';
});

const formattedValue = computed(() => {
    if (props.formatAsCurrency) {
        return new Intl.NumberFormat('uz-UZ', {
            style: 'currency',
            currency: 'UZS',
            maximumFractionDigits: 0,
        }).format(props.value);
    }

    return new Intl.NumberFormat('uz-UZ').format(props.value);
});
</script>
