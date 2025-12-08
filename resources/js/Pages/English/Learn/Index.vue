<template>

    <Head title="Ingliz tili - O'rganish" />

    <StudentLayout>
        <!-- Main Two-Column Layout -->
        <div class="flex flex-col xl:flex-row gap-6">
            <!-- LEFT: Main Content Area -->
            <div class="flex-1 min-w-0 space-y-6">
                <!-- Header -->
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">
                        🎯 O'rganish yo'li
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        Darajangizni tanlang va o'rganishni boshlang
                    </p>
                </div>

                <!-- Level Selector Cards -->
                <LevelSelector :levels="levels" :selected="selectedLevel" @select="selectLevel" />

                <!-- Module List -->
                <div v-if="selectedLevel">
                    <ModuleList :modules="modules" :loading="isLoading" @start-lesson="startLesson" />
                </div>
                <div v-else
                    class="text-center py-12 text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/50 rounded-2xl">
                    <div class="text-4xl mb-3">📚</div>
                    <p>Ochiq bo'lgan darajani tanlang</p>
                </div>
            </div>

            <!-- RIGHT: Progress Sidebar -->
            <aside class="w-full xl:w-80 flex-shrink-0 space-y-4">
                <!-- Overall Progress Card -->
                <div
                    class="bg-gradient-to-br from-indigo-500 via-purple-500 to-purple-600 rounded-2xl p-5 text-white shadow-xl relative overflow-hidden">
                    <!-- Decorative circles -->
                    <div class="absolute -top-6 -right-6 w-24 h-24 bg-white/10 rounded-full blur-sm"></div>
                    <div class="absolute -bottom-4 -left-4 w-20 h-20 bg-white/10 rounded-full blur-sm"></div>

                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-9 h-9 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                                    📊
                                </div>
                                <span class="font-bold">Umumiy progress</span>
                            </div>
                            <span class="text-3xl font-bold">
                                {{ overallProgress }}%
                            </span>
                        </div>

                        <!-- Progress bar -->
                        <div class="h-2.5 bg-white/20 rounded-full overflow-hidden mb-3">
                            <div class="h-full bg-white rounded-full transition-all duration-700"
                                :style="{ width: `${overallProgress}%` }"></div>
                        </div>

                        <div class="flex justify-between text-sm text-white/80">
                            <span>{{ totalCompleted }} dars tugatildi</span>
                            <span>{{ totalLessons }} jami dars</span>
                        </div>
                    </div>
                </div>

                <!-- Selected Level Card -->
                <div v-if="selectedLevel"
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <!-- Level Header with gradient -->
                    <div :class="['p-4 text-white relative overflow-hidden', levelGradientClass]">
                        <div class="absolute -top-4 -right-4 w-16 h-16 bg-white/10 rounded-full"></div>
                        <div class="relative z-10 flex items-center gap-3">
                            <div
                                class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center text-2xl">
                                {{ getLevelEmoji(selectedLevel.code) }}
                            </div>
                            <div>
                                <h3 class="font-bold text-lg">{{ selectedLevel.code }} - {{ selectedLevel.name }}</h3>
                                <p class="text-sm text-white/80">{{ getLevelDescription(selectedLevel.code) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Section -->
                    <div class="p-4 space-y-4">
                        <!-- Progress bar section -->
                        <div>
                            <div class="flex items-center justify-between text-sm mb-2">
                                <span class="text-gray-600 dark:text-gray-400">Bajarildi</span>
                                <span :class="['font-bold text-lg', levelTextColorClass]">
                                    {{ Math.round(selectedLevel.progress_percent || 0) }}%
                                </span>
                            </div>
                            <div class="h-2.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-700" :class="levelProgressClass"
                                    :style="{ width: `${selectedLevel.progress_percent || 0}%` }"></div>
                            </div>
                            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-2">
                                <span>{{ selectedLevel.completed_lessons || 0 }} tugatildi</span>
                                <span>{{ selectedLevel.total_lessons || 0 }} jami dars</span>
                            </div>
                        </div>

                        <!-- Stats Grid -->
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-xl p-3 text-center">
                                <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ selectedLevel.completed_lessons || 0 }}
                                </div>
                                <div class="text-xs text-emerald-600/70 dark:text-emerald-400/70 mt-0.5">Tugatilgan
                                </div>
                            </div>
                            <div class="bg-amber-50 dark:bg-amber-900/20 rounded-xl p-3 text-center">
                                <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">
                                    {{ remainingLessons }}
                                </div>
                                <div class="text-xs text-amber-600/70 dark:text-amber-400/70 mt-0.5">Qolgan</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Motivational Card (when no level selected) -->
                <div v-else
                    class="bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-700 rounded-2xl p-5 text-center">
                    <div class="text-4xl mb-3">🎓</div>
                    <h3 class="font-bold text-gray-900 dark:text-white mb-1">Darajani tanlang</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        O'zingizga mos darajani tanlab, o'rganishni boshlang
                    </p>
                </div>

                <!-- Additional Info Card -->
                <div class="bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-blue-900/20 dark:to-indigo-900/20 
                            rounded-2xl p-4 border border-blue-100 dark:border-blue-800/30">
                    <div class="flex items-start gap-3">
                        <div
                            class="w-10 h-10 bg-blue-500 rounded-xl flex items-center justify-center text-white flex-shrink-0">
                            �
                        </div>
                        <div>
                            <h4 class="font-semibold text-blue-900 dark:text-blue-100 text-sm">Har kuni o'rganing!</h4>
                            <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">
                                Muntazam o'rganish natijalarni tezlashtiradi. Har kuni kamida 1 dars tugatishga harakat
                                qiling.
                            </p>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </StudentLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import LevelSelector from './Components/LevelSelector.vue'
import ModuleList from './Components/ModuleList.vue'

const props = defineProps({
    levels: { type: Array, default: () => [] },
    currentLevel: Object,
    modules: Array,
    userProgress: Object
})

const selectedLevel = ref(props.currentLevel || (props.levels.length > 0 ? props.levels[0] : null))
const isLoading = ref(false)

watch(() => props.currentLevel, (newVal) => {
    if (newVal) selectedLevel.value = newVal
})

const selectLevel = async (level) => {
    if (!level.is_unlocked) return
    if (selectedLevel.value && selectedLevel.value.id === level.id) return

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

// Computed values
const overallProgress = computed(() => {
    if (!props.levels || props.levels.length === 0) return 0
    const total = props.levels.reduce((sum, l) => sum + (l.total_lessons || 0), 0)
    const completed = props.levels.reduce((sum, l) => sum + (l.completed_lessons || 0), 0)
    if (total === 0) return 0
    return Math.round((completed / total) * 100)
})

const totalLessons = computed(() => {
    return props.levels.reduce((sum, l) => sum + (l.total_lessons || 0), 0)
})

const totalCompleted = computed(() => {
    return props.levels.reduce((sum, l) => sum + (l.completed_lessons || 0), 0)
})

const remainingLessons = computed(() => {
    if (!selectedLevel.value) return 0
    return (selectedLevel.value.total_lessons || 0) - (selectedLevel.value.completed_lessons || 0)
})

// Helper functions
const getLevelEmoji = (code) => {
    const emojis = { 'A1': '🌱', 'A2': '🌿', 'A2+': '🌊', 'B1': '💜', 'B2': '🔥', 'C1': '🏔️', 'C2': '👑' }
    return emojis[code] || '📚'
}

const getLevelDescription = (code) => {
    const descriptions = {
        'A1': 'Boshlang\'ich daraja',
        'A2': 'Asosiy daraja',
        'A2+': 'O\'rtadan oldingi',
        'B1': 'O\'rta daraja',
        'B2': 'Yuqori o\'rta',
        'C1': 'Ilg\'or daraja',
        'C2': 'Professional'
    }
    return descriptions[code] || ''
}

// Color configuration
const gradientConfig = {
    A1: { gradient: 'bg-gradient-to-r from-emerald-500 to-teal-500', text: 'text-emerald-600', bar: 'bg-gradient-to-r from-emerald-400 to-teal-500' },
    A2: { gradient: 'bg-gradient-to-r from-blue-500 to-indigo-500', text: 'text-blue-600', bar: 'bg-gradient-to-r from-blue-400 to-indigo-500' },
    'A2+': { gradient: 'bg-gradient-to-r from-cyan-500 to-blue-500', text: 'text-cyan-600', bar: 'bg-gradient-to-r from-cyan-400 to-blue-500' },
    B1: { gradient: 'bg-gradient-to-r from-violet-500 to-purple-500', text: 'text-purple-600', bar: 'bg-gradient-to-r from-violet-400 to-purple-500' },
    B2: { gradient: 'bg-gradient-to-r from-amber-500 to-orange-500', text: 'text-orange-600', bar: 'bg-gradient-to-r from-amber-400 to-orange-500' },
    C1: { gradient: 'bg-gradient-to-r from-rose-500 to-red-500', text: 'text-red-600', bar: 'bg-gradient-to-r from-rose-400 to-red-500' },
    C2: { gradient: 'bg-gradient-to-r from-fuchsia-500 to-pink-500', text: 'text-pink-600', bar: 'bg-gradient-to-r from-fuchsia-400 to-pink-500' }
}

const levelGradientClass = computed(() => {
    return gradientConfig[selectedLevel.value?.code]?.gradient || gradientConfig.A1.gradient
})

const levelTextColorClass = computed(() => {
    return gradientConfig[selectedLevel.value?.code]?.text || gradientConfig.A1.text
})

const levelProgressClass = computed(() => {
    return gradientConfig[selectedLevel.value?.code]?.bar || gradientConfig.A1.bar
})
</script>
