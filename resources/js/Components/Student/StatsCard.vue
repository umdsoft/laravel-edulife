<script setup>
defineProps({
    title: String,
    value: [String, Number],
    icon: String,
    color: {
        type: String,
        default: 'purple'
    }
});

const colors = {
    purple: 'bg-purple-100 text-purple-600',
    blue: 'bg-blue-100 text-blue-600',
    green: 'bg-green-100 text-green-600',
    yellow: 'bg-yellow-100 text-yellow-600',
    orange: 'bg-orange-100 text-orange-600',
    red: 'bg-red-100 text-red-600',
};
</script>

<template>
    <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4">
            <div :class="['w-12 h-12 rounded-xl flex items-center justify-center', colors[color]]">
                <slot name="icon">
                    <span class="text-2xl">{{ icon }}</span>
                </slot>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">{{ title }}</p>
                <p class="text-xl font-bold text-gray-900">{{ value }}</p>
            </div>
        </div>
    </div>
</template>
