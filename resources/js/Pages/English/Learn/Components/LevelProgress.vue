<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="text-2xl">{{ level.icon }}</span>
                    {{ level.code }} - {{ level.name }} Level
                </h2>
                <div class="text-sm text-gray-500 mt-1">
                    {{ level.description }}
                </div>
            </div>
            <div class="text-right">
                <div class="text-2xl font-bold text-indigo-600">
                    {{ Math.round(level.progress_percent) }}%
                </div>
                <div class="text-sm text-gray-500">Completed</div>
            </div>
        </div>
        
        <div class="h-4 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
            <div 
                class="h-full rounded-full transition-all duration-1000 relative overflow-hidden"
                :class="`bg-${level.color}-500`"
                :style="{ width: `${level.progress_percent}%` }"
            >
                <div class="absolute inset-0 bg-white/20 animate-pulse"></div>
            </div>
        </div>
        
        <div class="mt-4 flex items-center justify-between text-sm text-gray-500">
            <span>{{ level.completed_lessons }} finished</span>
            <span>{{ level.total_lessons }} total lessons</span>
        </div>
    </div>
</template>

<script setup>
defineProps({
    level: {
        type: Object,
        required: true
    }
})
</script>
