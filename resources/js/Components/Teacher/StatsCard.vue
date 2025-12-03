<script setup>
defineProps({
    title: String,
    value: [String, Number],
    icon: String,
    trend: String,
    trendUp: Boolean,
    description: String,
});
</script>

<template>
    <div class="p-6 bg-white border border-gray-100 rounded-2xl">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 rounded-xl bg-emerald-50 text-emerald-600">
                <slot name="icon">
                    <span class="text-xl">{{ icon }}</span>
                </slot>
            </div>
            <span v-if="trend" :class="[
                'px-2 py-1 text-xs font-medium rounded-lg',
                trendUp ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'
            ]">
                {{ trend }}
            </span>
        </div>

        <div>
            <h3 class="text-sm font-medium text-gray-500">{{ title }}</h3>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl font-bold text-gray-900">{{ value }}</span>
            </div>
            <p v-if="description" class="mt-1 text-xs text-gray-400">
                {{ description }}
            </p>
        </div>
    </div>
</template>
