<template>
    <button
        @click="$emit('click', lesson)"
        :disabled="!lesson.is_unlocked"
        class="w-full text-left focus:outline-none" 
    >
        <div :class="[
            'w-full p-4 rounded-xl flex items-center gap-4 transition-all',
            lesson.is_unlocked
                ? 'hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer'
                : 'opacity-50 cursor-not-allowed'
        ]">
            <!-- Status icon -->
            <div :class="['w-10 h-10 rounded-full flex items-center justify-center shrink-0', iconClasses]">
                <span v-if="lesson.status === 'completed'" class="text-lg">✅</span>
                <span v-else-if="lesson.status === 'in_progress'" class="text-lg">🔵</span>
                <span v-else-if="!lesson.is_unlocked" class="text-lg">🔒</span>
                <span v-else class="text-lg">{{ lessonTypeIcon }}</span>
            </div>
            
            <!-- Lesson info -->
            <div class="flex-1 min-w-0">
                <h4 :class="[
                    'font-medium truncate',
                    lesson.status === 'completed' 
                        ? 'text-gray-500 dark:text-gray-400' 
                        : 'text-gray-900 dark:text-white'
                ]">
                    {{ lesson.title }}
                </h4>
                <div class="flex flex-wrap items-center gap-2 sm:gap-3 mt-1">
                    <span class="text-xs text-gray-500 capitalize bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">{{ lesson.type }}</span>
                    <span class="text-xs text-amber-500 font-medium">+{{ lesson.xp_reward }} XP</span>
                    <span class="text-xs text-yellow-500 font-medium">+{{ lesson.coin_reward }} 🪙</span>
                </div>
            </div>
            
            <!-- Score (if completed) -->
            <div v-if="lesson.status === 'completed'" class="text-right shrink-0">
                <div class="flex items-center gap-1">
                    <span v-for="n in 3" :key="n" class="text-sm">
                        {{ n <= Math.ceil((lesson.score || 0) / 34) ? '⭐' : '☆' }}
                    </span>
                </div>
                <span class="text-xs text-gray-500 font-medium">{{ lesson.score || 0 }}%</span>
            </div>
            
            <!-- Arrow for available lessons -->
            <svg 
                v-else-if="lesson.is_unlocked"
                class="w-5 h-5 text-gray-400 shrink-0"
                fill="none" stroke="currentColor" viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </div>
    </button>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    lesson: {
        type: Object,
        required: true
    }
})

defineEmits(['click'])

const lessonTypeIcon = computed(() => ({
    vocabulary: '📚',
    grammar: '📝',
    practice: '✏️',
    conversation: '💬',
    reading: '📖',
    listening: '🎧',
    speaking: '🎤',
    test: '📋',
    standard: '📄',
    review: '🔄'
}[props.lesson.type] || '📄'))

const iconClasses = computed(() => ({
    completed: 'bg-emerald-100 dark:bg-emerald-900/30',
    in_progress: 'bg-blue-100 dark:bg-blue-900/30',
    not_started: 'bg-gray-100 dark:bg-gray-700'
}[props.lesson.status] || 'bg-gray-100 dark:bg-gray-700'))
</script>
