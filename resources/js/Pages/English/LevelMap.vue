<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import { LockClosedIcon, CheckCircleIcon, StarIcon } from '@heroicons/vue/24/solid'
import { PlayIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    levels: Array,
    currentLevelId: String,
    profile: Object,
})

const selectedLevel = ref(null)

const getLevelStatus = (level) => {
    if (level.progress >= 100) return 'completed'
    if (level.is_unlocked) return 'unlocked'
    return 'locked'
}

const getLevelColor = (level) => {
    const colors = {
        'A1': 'from-green-400 to-green-600',
        'A2': 'from-blue-400 to-blue-600',
        'B1': 'from-purple-400 to-purple-600',
        'B2': 'from-orange-400 to-orange-600',
        'C1': 'from-red-400 to-red-600',
        'C2': 'from-gray-600 to-gray-800',
    }
    return colors[level.code] || 'from-blue-400 to-blue-600'
}

const selectLevel = (level) => {
    if (!level.is_unlocked) return
    selectedLevel.value = level
}

const startLearning = () => {
    if (selectedLevel.value) {
        router.visit(`/english/levels/${selectedLevel.value.id}`)
    }
}
</script>

<template>

    <Head title="Ingliz tili - O'rganish" />

    <StudentLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">O'rganish yo'li</h1>
                    <p class="text-gray-600 dark:text-gray-400">Darajangizni tanlang va o'rganishni boshlang</p>
                </div>
            </div>

            <!-- Level Grid -->
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="level in levels" :key="level.id" @click="selectLevel(level)"
                    class="relative rounded-2xl p-6 cursor-pointer transition-all duration-300" :class="[
                        level.is_unlocked
                            ? 'hover:scale-105 hover:shadow-xl'
                            : 'opacity-60 cursor-not-allowed',
                        selectedLevel?.id === level.id
                            ? 'ring-4 ring-blue-500 ring-offset-2 dark:ring-offset-gray-900'
                            : '',
                        `bg-gradient-to-br ${getLevelColor(level)}`
                    ]">
                    <!-- Lock overlay for locked levels -->
                    <div v-if="!level.is_unlocked"
                        class="absolute inset-0 bg-gray-900/50 rounded-2xl flex items-center justify-center">
                        <LockClosedIcon class="w-12 h-12 text-white/70" />
                    </div>

                    <!-- Completed badge -->
                    <div v-if="level.progress >= 100" class="absolute top-4 right-4">
                        <CheckCircleIcon class="w-8 h-8 text-white" />
                    </div>

                    <!-- Level content -->
                    <div class="text-white">
                        <div class="flex items-center space-x-3 mb-4">
                            <span class="text-4xl">{{ level.icon || '📚' }}</span>
                            <div>
                                <h3 class="text-2xl font-bold">{{ level.code }}</h3>
                                <p class="text-white/80">{{ level.name }}</p>
                            </div>
                        </div>

                        <p class="text-sm text-white/90 mb-4">{{ level.description }}</p>

                        <!-- Progress bar -->
                        <div class="mb-2">
                            <div class="flex justify-between text-sm mb-1">
                                <span>Progress</span>
                                <span>{{ Math.round(level.progress || 0) }}%</span>
                            </div>
                            <div class="h-2 bg-white/30 rounded-full overflow-hidden">
                                <div class="h-full bg-white rounded-full transition-all duration-500"
                                    :style="{ width: `${level.progress || 0}%` }"></div>
                            </div>
                        </div>

                        <!-- Stats -->
                        <div class="flex items-center justify-between text-sm text-white/80">
                            <span>{{ level.total_topics || 0 }} Topics</span>
                            <span>{{ level.total_lessons || 0 }} Lessons</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Selected Level Actions -->
            <div v-if="selectedLevel" class="fixed bottom-20 md:bottom-8 left-0 right-0 px-4">
                <div class="max-w-md mx-auto bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ selectedLevel.code }} - {{
                                selectedLevel.name }}</p>
                            <p class="text-sm text-gray-500">{{ selectedLevel.total_lessons }} lessons available</p>
                        </div>
                        <button @click="startLearning"
                            class="flex items-center space-x-2 px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-500 text-white rounded-xl font-medium hover:shadow-lg transition-all">
                            <PlayIcon class="w-5 h-5" />
                            <span>Start</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
