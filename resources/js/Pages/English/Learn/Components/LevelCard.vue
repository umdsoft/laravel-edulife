<template>
    <button
        @click="$emit('select', level)"
        :disabled="!level.is_unlocked"
        :class="[
            'relative p-6 rounded-2xl border-2 transition-all duration-300',
            'flex flex-col items-center text-center min-w-[140px]',
            isSelected 
                ? `border-${level.color}-500 bg-${level.color}-50 dark:bg-${level.color}-900/20 shadow-lg scale-105`
                : level.is_unlocked
                    ? 'border-gray-200 dark:border-gray-700 hover:border-gray-300 hover:shadow-md'
                    : 'border-gray-100 dark:border-gray-800 opacity-60 cursor-not-allowed'
        ]"
    >
        <!-- Lock overlay for locked levels -->
        <div v-if="!level.is_unlocked" class="absolute inset-0 bg-gray-500/10 rounded-2xl flex items-center justify-center">
            <span class="text-3xl">🔒</span>
        </div>
        
        <!-- Level icon -->
        <div :class="[
            'w-16 h-16 rounded-full flex items-center justify-center text-2xl mb-3',
            `bg-${level.color}-100 dark:bg-${level.color}-900/30`
        ]">
            {{ level.icon }}
        </div>
        
        <!-- Level code -->
        <span :class="[
            'text-2xl font-bold',
            `text-${level.color}-600 dark:text-${level.color}-400`
        ]">
            {{ level.code }}
        </span>
        
        <!-- Level name -->
        <span class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            {{ level.name }}
        </span>
        
        <!-- Progress -->
        <div class="mt-3 w-full">
            <div class="h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                <div 
                    :class="`h-full bg-${level.color}-500 rounded-full`"
                    :style="{ width: `${level.progress_percent}%` }"
                ></div>
            </div>
            <span class="text-xs text-gray-500 mt-1">
                {{ level.completed_lessons }}/{{ level.total_lessons }} lessons
            </span>
        </div>
        
        <!-- Status badge -->
        <span v-if="level.progress_percent === 100" class="absolute -top-2 -right-2 text-xl">
            ⭐
        </span>
    </button>
</template>

<script setup>
defineProps({
    level: {
        type: Object,
        required: true
    },
    isSelected: {
        type: Boolean,
        default: false
    }
})

defineEmits(['select'])
</script>
