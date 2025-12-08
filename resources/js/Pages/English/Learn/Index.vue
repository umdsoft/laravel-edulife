<template>
    <EnglishLayout>
        <div class="max-w-7xl mx-auto px-4 py-8">
            <!-- Header with overall progress -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    🎯 Learning Path
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">
                    Choose your level and start learning
                </p>
                
                <!-- Overall Progress Bar -->
                <div class="mt-4 bg-gray-100 dark:bg-gray-800 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Overall Progress
                        </span>
                        <span class="text-sm font-bold text-indigo-600">
                            {{ overallProgress || 0 }}%
                        </span>
                    </div>
                    <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div 
                            class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transition-all duration-500"
                            :style="{ width: `${overallProgress || 0}%` }"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- Level Selector -->
            <LevelSelector 
                :levels="levels"
                :selected="selectedLevel"
                @select="selectLevel"
            />

            <!-- Selected Level Content -->
            <div v-if="selectedLevel" class="mt-8">
                <LevelProgress 
                    :level="selectedLevel"
                    class="mb-6"
                />
                
                <ModuleList
                    :modules="modules"
                    :loading="isLoading"
                    @start-lesson="startLesson"
                />
            </div>
            <div v-else class="mt-8 text-center text-gray-500">
                Please select an unlocked level to view its content.
            </div>
        </div>
    </EnglishLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import EnglishLayout from '@/Components/English/Layout/EnglishLayout.vue'
import LevelSelector from './Components/LevelSelector.vue'
import ModuleList from './Components/ModuleList.vue'
import LevelProgress from './Components/LevelProgress.vue'

const props = defineProps({
    levels: { type: Array, default: () => [] },
    currentLevel: Object,
    modules: Array,
    userProgress: Object
})

const selectedLevel = ref(props.currentLevel || (props.levels.length > 0 ? props.levels[0] : null))
const isLoading = ref(false)

// Update selectedLevel when currentLevel prop changes (e.g. after navigation)
watch(() => props.currentLevel, (newVal) => {
    if (newVal) selectedLevel.value = newVal
})

const selectLevel = async (level) => {
    if (!level.is_unlocked) return
    if (selectedLevel.value && selectedLevel.value.id === level.id) return // Already selected
    
    selectedLevel.value = level
    isLoading.value = true
    
    router.get(route('student.english.levels', { level: level.code }), {}, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => isLoading.value = false
    })
}

const startLesson = (lesson) => {
    if (!lesson.is_unlocked) return
    router.visit(route('student.english.lesson', lesson.id))
}

const overallProgress = computed(() => {
    if (!props.levels || props.levels.length === 0) return 0
    const total = props.levels.reduce((sum, l) => sum + l.total_lessons, 0)
    const completed = props.levels.reduce((sum, l) => sum + l.completed_lessons, 0)
    if (total === 0) return 0
    return Math.round((completed / total) * 100)
})
</script>
