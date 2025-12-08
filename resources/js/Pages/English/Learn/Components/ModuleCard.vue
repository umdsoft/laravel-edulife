<template>
    <div :class="[
        'rounded-2xl border overflow-hidden transition-all duration-300',
        module.is_unlocked 
            ? 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800'
            : 'border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900 opacity-70'
    ]">
        <!-- Module Header -->
        <button 
            @click="toggleExpand"
            class="w-full p-4 sm:p-5 flex items-center justify-between focus:outline-none"
            :disabled="!module.is_unlocked"
        >
            <div class="flex items-center gap-4">
                <!-- Module number -->
                <div :class="[
                    'w-10 h-10 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center font-bold text-lg border-2',
                    statusClasses
                ]">
                    {{ module.order }}
                </div>
                
                <!-- Module info -->
                <div class="text-left">
                    <h3 class="font-semibold text-gray-900 dark:text-white leading-tight">
                        {{ module.title }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ module.lessons.length }} lessons • {{ module.estimated_minutes }} min
                    </p>
                </div>
            </div>
            
            <!-- Status & Progress -->
            <div class="flex items-center gap-3 sm:gap-4">
                <!-- Status badge (hidden on small screens) -->
                <span :class="['hidden sm:inline-block px-3 py-1 rounded-full text-xs font-medium', statusBadgeClasses]">
                    {{ statusText }}
                </span>
                
                <!-- Progress ring -->
                <ProgressRing 
                    :progress="module.progress_percent" 
                    :size="40"
                    :stroke="4"
                    :color="progressColor"
                />
                
                <!-- Expand icon -->
                <svg 
                    :class="['w-5 h-5 transition-transform text-gray-400', isExpanded && 'rotate-180']"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
        </button>
        
        <!-- Lessons List (Expandable) -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            leave-active-class="transition-all duration-200 ease-in"
            enter-from-class="opacity-0 max-h-0"
            enter-to-class="opacity-100 max-h-[1000px]"
            leave-from-class="opacity-100 max-h-[1000px]"
            leave-to-class="opacity-0 max-h-0"
        >
            <div v-show="isExpanded && module.is_unlocked" class="border-t border-gray-100 dark:border-gray-700">
                <div class="p-2 sm:p-4 grid gap-1 sm:gap-2">
                    <LessonItem
                        v-for="lesson in module.lessons"
                        :key="lesson.id"
                        :lesson="lesson"
                        @click="$emit('start-lesson', lesson)"
                    />
                </div>
            </div>
        </Transition>
        
        <!-- Locked message -->
        <div v-if="!module.is_unlocked" class="px-5 pb-5 text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center justify-center gap-2">
                🔒 Complete previous module to unlock
            </p>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import LessonItem from './LessonItem.vue'
import ProgressRing from './ProgressRing.vue'

const props = defineProps({
    module: {
        type: Object,
        required: true
    }
})

const isExpanded = ref(props.module.is_unlocked && (props.module.status === 'in_progress' || props.module.status === 'not_started'))

defineEmits(['start-lesson'])

const toggleExpand = () => {
    isExpanded.value = !isExpanded.value
}

const statusText = computed(() => {
    switch(props.module.status) {
        case 'completed': return 'Completed'
        case 'in_progress': return 'In Progress'
        default: return props.module.is_unlocked ? 'Start' : 'Locked'
    }
})

const statusClasses = computed(() => {
    if (!props.module.is_unlocked) return 'bg-gray-100 text-gray-400 border-gray-200 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-500'
    if (props.module.status === 'completed') return 'bg-emerald-100 text-emerald-600 border-emerald-200 dark:bg-emerald-900/30 dark:border-emerald-800 dark:text-emerald-400'
    if (props.module.status === 'in_progress') return 'bg-blue-100 text-blue-600 border-blue-200 dark:bg-blue-900/30 dark:border-blue-800 dark:text-blue-400'
    return 'bg-white text-gray-500 border-gray-200 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300'
})

const statusBadgeClasses = computed(() => {
    if (!props.module.is_unlocked) return 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'
    if (props.module.status === 'completed') return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300'
    if (props.module.status === 'in_progress') return 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300'
    return 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
})

const progressColor = computed(() => {
    if (props.module.status === 'completed') return '#10b981' // emerald-500
    if (props.module.status === 'in_progress') return '#3b82f6' // blue-500
    return '#9ca3af' // gray-400
})
</script>
