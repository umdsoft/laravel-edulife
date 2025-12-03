<script setup>
defineProps({
    percentage: {
        type: Number,
        required: true,
        validator: (value) => value >= 0 && value <= 100
    },
    color: {
        type: String,
        default: 'purple' // purple, blue, green, yellow
    },
    height: {
        type: String,
        default: 'h-2'
    },
    showLabel: {
        type: Boolean,
        default: false
    }
});

const colorClasses = {
    purple: 'bg-purple-600',
    blue: 'bg-blue-600',
    green: 'bg-green-600',
    yellow: 'bg-yellow-500',
};
</script>

<template>
    <div class="w-full">
        <div v-if="showLabel" class="flex justify-between mb-1">
            <span class="text-sm font-medium text-gray-700">Progress</span>
            <span class="text-sm font-medium text-gray-700">{{ percentage }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full overflow-hidden" :class="height">
            <div class="h-full rounded-full transition-all duration-500 ease-out"
                :class="colorClasses[color] || colorClasses.purple" :style="{ width: `${percentage}%` }">
            </div>
        </div>
    </div>
</template>
