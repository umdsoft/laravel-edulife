<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import StudentLayout from '@/Layouts/StudentLayout.vue'

const props = defineProps({
    levels: Array,
    config: Object,
    userStats: Object,
    gameModes: Object,
    errorTypes: Object,
    powerups: Object,
    achievements: Array,
    userAchievements: Array,
})

const showTutorial = ref(false)
const showAchievements = ref(false)
const selectedMode = ref('spot_error')

const totalStars = computed(() => {
    return props.levels?.reduce((sum, level) => sum + (level.best_stars || 0), 0) || 0
})
const maxStars = computed(() => (props.levels?.length || 10) * 3)
const completedLevels = computed(() => props.levels?.filter(l => l.completed).length || 0)
const totalLevels = computed(() => props.levels?.length || 10)

const progressPercentage = computed(() => {
    return maxStars.value > 0 ? Math.round((totalStars.value / maxStars.value) * 100) : 0
})

const earnedAchievements = computed(() => {
    if (!props.achievements || !props.userAchievements) return []
    return props.achievements.filter(a => props.userAchievements.includes(a.id))
})

const gameModesList = computed(() => {
    return Object.values(props.gameModes || {})
})

const powerupsList = computed(() => {
    return Object.values(props.powerups || {})
})

const errorTypesList = computed(() => {
    return Object.values(props.errorTypes || {})
})

// Unique vibrant colors for each level - more distinct and beautiful
const levelGradients = {
    1: 'from-amber-400 via-amber-500 to-yellow-600',
    2: 'from-orange-400 via-orange-500 to-amber-600',
    3: 'from-rose-400 via-rose-500 to-orange-600',
    4: 'from-red-400 via-red-500 to-rose-600',
    5: 'from-pink-400 via-pink-500 to-rose-600',
    6: 'from-fuchsia-400 via-fuchsia-500 to-pink-600',
    7: 'from-purple-400 via-purple-500 to-fuchsia-600',
    8: 'from-violet-400 via-violet-500 to-purple-600',
    9: 'from-indigo-400 via-indigo-500 to-violet-600',
    10: 'from-blue-400 via-blue-500 to-indigo-600',
}

// CEFR level badge colors
const cefrColors = {
    'A1': 'bg-emerald-500',
    'A2': 'bg-cyan-500',
    'B1': 'bg-blue-500',
    'B2': 'bg-violet-500',
    'C1': 'bg-purple-500',
    'C2': 'bg-rose-500',
}

function getLevelIcon(level) {
    return level.icon || '🔍'
}

function getGradientClass(level) {
    return levelGradients[level.number] || 'from-amber-500 to-orange-600'
}

function getCefrBadgeClass(cefrLevel) {
    return cefrColors[cefrLevel] || 'bg-gray-500'
}

function getTimeLimit(level) {
    // Get time limit based on selected mode
    if (typeof level.time_limit === 'object') {
        return level.time_limit[selectedMode.value] || level.time_limit.spot_error || 120
    }
    return level.time_limit || 120
}

function playLevel(level) {
    if (!level.unlocked) return
    router.visit(`/student/english/games/error-hunter/play/${level.number}`)
}

function getModeIcon(mode) {
    const icons = {
        'spot_error': '🎯',
        'fix_error': '🔧',
        'rewrite': '✍️',
    }
    return icons[mode.id] || mode.icon || '🎮'
}

function getPowerupIcon(powerup) {
    return powerup.icon || '🔮'
}

function isAchievementUnlocked(achievementId) {
    return (props.userAchievements || []).includes(achievementId)
}
</script>

<template>
    <Head title="Xato Ovchisi - Error Hunter" />

    <StudentLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900 -m-6 p-6 font-sans">
            <!-- Premium Hero Section -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-amber-500 via-orange-500 to-red-600 p-8 mb-8 shadow-2xl">
                <!-- Animated Background -->
                <div class="absolute inset-0 overflow-hidden">
                    <div class="absolute -top-1/2 -right-1/2 w-full h-full bg-white/10 rounded-full blur-3xl animate-pulse"></div>
                    <div class="absolute -bottom-1/2 -left-1/2 w-full h-full bg-white/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
                    <!-- Decorative elements -->
                    <div class="absolute top-10 right-10 w-20 h-20 bg-yellow-400/20 rounded-full blur-2xl"></div>
                    <div class="absolute bottom-10 left-10 w-32 h-32 bg-red-400/20 rounded-full blur-2xl"></div>
                </div>

                <!-- Header Content -->
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <Link href="/student/english/games" class="inline-flex items-center text-white/90 hover:text-white mb-4 transition-colors font-medium">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                O'yinlarga qaytish
                            </Link>
                            <h1 class="text-3xl md:text-4xl font-bold text-white flex items-center gap-3 drop-shadow-lg">
                                <span class="text-4xl">🔍</span>
                                {{ config?.game_name_uz || "Xato Ovchisi" }}
                            </h1>
                            <p class="text-white/90 mt-2 text-lg drop-shadow-md">
                                {{ config?.description_uz || "Gaplardagi grammatika, imlo va tinish belgisi xatolarini toping va tuzating" }}
                            </p>
                        </div>

                        <div class="flex gap-3">
                            <button @click="showAchievements = true" class="p-3 bg-white/20 hover:bg-white/30 rounded-xl backdrop-blur-sm transition-all shadow-lg hover:shadow-xl hover:scale-105">
                                <span class="text-2xl">🏆</span>
                            </button>
                            <button @click="showTutorial = true" class="p-3 bg-white/20 hover:bg-white/30 rounded-xl backdrop-blur-sm transition-all shadow-lg hover:shadow-xl hover:scale-105">
                                <span class="text-2xl">❓</span>
                            </button>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center shadow-lg border border-white/10">
                            <div class="text-3xl font-bold text-white drop-shadow-md">{{ totalStars }}</div>
                            <div class="text-white/90 text-sm flex items-center justify-center gap-1 font-medium">
                                <span class="text-yellow-300 drop-shadow-md">⭐</span> Yulduzlar
                            </div>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center shadow-lg border border-white/10">
                            <div class="text-3xl font-bold text-white drop-shadow-md">{{ userStats?.total_errors_found || 0 }}</div>
                            <div class="text-white/90 text-sm font-medium">🎯 Xatolar topildi</div>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center shadow-lg border border-white/10">
                            <div class="text-3xl font-bold text-white drop-shadow-md">{{ userStats?.best_streak || 0 }}</div>
                            <div class="text-white/90 text-sm font-medium">🔥 Eng yaxshi streak</div>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center shadow-lg border border-white/10">
                            <div class="text-3xl font-bold text-white drop-shadow-md">{{ userStats?.accuracy_rate || 0 }}%</div>
                            <div class="text-white/90 text-sm font-medium">✅ Aniqlik</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Game Modes Section -->
            <div class="mb-6">
                <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                    <span>🎮</span> O'yin Rejimlari
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div v-for="mode in gameModesList" :key="mode.id"
                         @click="selectedMode = mode.id"
                         :class="[
                             'relative p-5 rounded-2xl cursor-pointer transition-all duration-300 border-2 shadow-lg',
                             selectedMode === mode.id
                                 ? 'bg-gradient-to-br from-amber-500 to-orange-600 border-amber-400 shadow-amber-200 dark:shadow-amber-900/50 scale-[1.02]'
                                 : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:border-amber-400 hover:shadow-xl'
                         ]">
                        <div class="flex items-center gap-4">
                            <div :class="[
                                'w-14 h-14 rounded-xl flex items-center justify-center text-3xl shadow-md',
                                selectedMode === mode.id
                                    ? 'bg-white/20'
                                    : 'bg-gradient-to-br from-amber-100 to-orange-100 dark:from-amber-900/50 dark:to-orange-900/50'
                            ]">
                                {{ getModeIcon(mode) }}
                            </div>
                            <div>
                                <h3 :class="[
                                    'font-bold text-lg',
                                    selectedMode === mode.id ? 'text-white drop-shadow-md' : 'text-gray-800 dark:text-white'
                                ]">{{ mode.name_uz }}</h3>
                                <p :class="[
                                    'text-sm',
                                    selectedMode === mode.id ? 'text-white/90' : 'text-gray-500 dark:text-gray-400'
                                ]">{{ mode.description_uz }}</p>
                            </div>
                        </div>
                        <div v-if="selectedMode === mode.id" class="absolute top-3 right-3">
                            <span class="bg-white/30 backdrop-blur-sm text-white text-xs px-3 py-1.5 rounded-full font-semibold shadow-md">✓ Tanlangan</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Error Types Section -->
            <div class="mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-4">
                    <div class="flex items-center justify-between flex-wrap gap-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">📝</span>
                            <span class="font-semibold text-gray-800 dark:text-white">Xato Turlari:</span>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <div v-for="errorType in errorTypesList" :key="errorType.id"
                                 class="group relative flex items-center gap-2 px-3 py-2 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600 rounded-xl hover:shadow-md transition-all cursor-default">
                                <span class="text-lg group-hover:scale-110 transition-transform">{{ errorType.icon }}</span>
                                <div class="hidden sm:block">
                                    <div class="font-medium text-gray-800 dark:text-white text-sm leading-tight">{{ errorType.name_uz }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Powerups Section -->
            <div class="mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-4">
                    <div class="flex items-center justify-between flex-wrap gap-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">🔮</span>
                            <span class="font-semibold text-gray-800 dark:text-white">Maxsus Kuchlar:</span>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <div v-for="powerup in powerupsList" :key="powerup.id"
                                 class="group relative flex items-center gap-2 px-3 py-2 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600 rounded-xl hover:shadow-md transition-all cursor-default">
                                <span class="text-xl group-hover:scale-110 transition-transform">{{ getPowerupIcon(powerup) }}</span>
                                <div class="hidden sm:block">
                                    <div class="font-medium text-gray-800 dark:text-white text-sm leading-tight">{{ powerup.name_uz }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ powerup.cost_coins }} tanga</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Levels Grid -->
            <div class="mb-8">
                <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                    <span>📊</span> Bosqichlar
                </h2>

                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-5">
                    <div v-for="level in levels" :key="level.number"
                         @click="playLevel(level)"
                         :class="[
                             'relative rounded-2xl overflow-hidden transition-all duration-300 cursor-pointer group shadow-xl',
                             level.unlocked
                                 ? 'hover:scale-105 hover:shadow-2xl hover:-translate-y-1'
                                 : 'cursor-not-allowed'
                         ]">
                        <!-- Level Card Background with unique gradient -->
                        <div :class="['absolute inset-0 bg-gradient-to-br', getGradientClass(level)]"></div>

                        <!-- Shine effect on hover -->
                        <div class="absolute inset-0 bg-gradient-to-tr from-white/0 via-white/20 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

                        <!-- Decorative pattern -->
                        <div class="absolute inset-0 opacity-10">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-white rounded-full -translate-y-1/2 translate-x-1/2"></div>
                            <div class="absolute bottom-0 left-0 w-24 h-24 bg-black rounded-full translate-y-1/2 -translate-x-1/2"></div>
                        </div>

                        <!-- Lock Overlay -->
                        <div v-if="!level.unlocked" class="absolute inset-0 bg-gray-900/70 backdrop-blur-[2px] z-20 flex items-center justify-center">
                            <div class="text-center px-4">
                                <div class="w-14 h-14 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-2 shadow-xl backdrop-blur-sm">
                                    <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <p class="text-white text-sm font-bold drop-shadow-lg">
                                    {{ level.unlock_requirement?.stars || 1 }} ⭐ kerak
                                </p>
                            </div>
                        </div>

                        <!-- Card Content -->
                        <div class="relative z-10 p-4 h-full flex flex-col min-h-[220px]">
                            <!-- Header with Icon and CEFR Badge -->
                            <div class="flex items-start justify-between mb-3">
                                <div class="w-12 h-12 bg-white/25 backdrop-blur-sm rounded-xl flex items-center justify-center shadow-lg">
                                    <span class="text-2xl filter drop-shadow-md">{{ getLevelIcon(level) }}</span>
                                </div>
                                <span :class="[
                                    'px-2.5 py-1 rounded-lg text-white text-xs font-bold shadow-lg',
                                    getCefrBadgeClass(level.cefr_level)
                                ]">
                                    {{ level.cefr_level }}
                                </span>
                            </div>

                            <!-- Level Number & Name -->
                            <div class="mb-2">
                                <h3 class="text-xl font-black text-white drop-shadow-lg">
                                    {{ level.number }}-daraja
                                </h3>
                                <p class="text-white/90 text-sm font-medium drop-shadow-md line-clamp-1">
                                    {{ level.name_uz }}
                                </p>
                            </div>

                            <!-- Spacer -->
                            <div class="flex-1"></div>

                            <!-- Stars -->
                            <div class="flex items-center gap-0.5 mb-3">
                                <template v-for="i in 3" :key="i">
                                    <svg v-if="i <= (level.best_stars || 0)"
                                         class="w-6 h-6 text-yellow-300 drop-shadow-lg"
                                         fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                    <svg v-else
                                         class="w-6 h-6 text-white/40"
                                         fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                </template>
                            </div>

                            <!-- Level Info Pills -->
                            <div class="flex items-center gap-2">
                                <span class="flex items-center gap-1.5 bg-white/25 backdrop-blur-sm px-3 py-1.5 rounded-lg text-white text-sm font-semibold shadow-md">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    {{ level.sentences_count }}
                                </span>
                                <span class="flex items-center gap-1.5 bg-white/25 backdrop-blur-sm px-3 py-1.5 rounded-lg text-white text-sm font-semibold shadow-md">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ getTimeLimit(level) }}s
                                </span>
                            </div>

                            <!-- Completed Badge -->
                            <div v-if="level.completed" class="absolute top-3 left-3">
                                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center shadow-lg border-2 border-white">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- How It Works Section -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg mb-8 border border-gray-100 dark:border-gray-700">
                <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                    <span>🎯</span> Qanday O'ynaladi
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="flex items-start gap-3 p-4 bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 rounded-xl border border-amber-200/50 dark:border-amber-800/50">
                        <div class="w-10 h-10 bg-amber-500 rounded-lg flex items-center justify-center text-white font-bold shadow-md">1</div>
                        <div>
                            <div class="font-semibold text-gray-800 dark:text-white">Rejimni tanlang</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Xatoni toping, tuzating yoki qayta yozing</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-4 bg-gradient-to-br from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20 rounded-xl border border-orange-200/50 dark:border-orange-800/50">
                        <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center text-white font-bold shadow-md">2</div>
                        <div>
                            <div class="font-semibold text-gray-800 dark:text-white">Gapni o'qing</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Diqqat bilan xatoni aniqlang</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-4 bg-gradient-to-br from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 rounded-xl border border-red-200/50 dark:border-red-800/50">
                        <div class="w-10 h-10 bg-red-500 rounded-lg flex items-center justify-center text-white font-bold shadow-md">3</div>
                        <div>
                            <div class="font-semibold text-gray-800 dark:text-white">Xatoni tuzating</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">To'g'ri javobni tanlang yoki yozing</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-4 bg-gradient-to-br from-rose-50 to-pink-50 dark:from-rose-900/20 dark:to-pink-900/20 rounded-xl border border-rose-200/50 dark:border-rose-800/50">
                        <div class="w-10 h-10 bg-rose-500 rounded-lg flex items-center justify-center text-white font-bold shadow-md">4</div>
                        <div>
                            <div class="font-semibold text-gray-800 dark:text-white">Yutuqlar yig'ing</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Ball, XP va tangalar oling</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tips Section -->
            <div class="bg-gradient-to-r from-amber-500 via-orange-500 to-red-600 rounded-2xl p-6 text-white shadow-xl">
                <h2 class="text-xl font-bold mb-4 flex items-center gap-2 drop-shadow-md">
                    <span>💡</span> Maslahatlar
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white/15 backdrop-blur-sm rounded-xl p-4 border border-white/10 shadow-lg hover:bg-white/20 transition-colors">
                        <div class="font-semibold mb-2 drop-shadow-md flex items-center gap-2">
                            <span class="text-xl">⚡</span> Tezlik muhim
                        </div>
                        <p class="text-sm text-white/90">Tez javob berish uchun vaqt bonusi olasiz!</p>
                    </div>
                    <div class="bg-white/15 backdrop-blur-sm rounded-xl p-4 border border-white/10 shadow-lg hover:bg-white/20 transition-colors">
                        <div class="font-semibold mb-2 drop-shadow-md flex items-center gap-2">
                            <span class="text-xl">🔥</span> Streak saqlang
                        </div>
                        <p class="text-sm text-white/90">Ketma-ket to'g'ri javoblar multiplikatorni oshiradi!</p>
                    </div>
                    <div class="bg-white/15 backdrop-blur-sm rounded-xl p-4 border border-white/10 shadow-lg hover:bg-white/20 transition-colors">
                        <div class="font-semibold mb-2 drop-shadow-md flex items-center gap-2">
                            <span class="text-xl">✍️</span> Qayta Yozing
                        </div>
                        <p class="text-sm text-white/90">Bu rejim eng ko'p XP va ball beradi!</p>
                    </div>
                </div>
            </div>

            <!-- Tutorial Modal -->
            <Transition name="modal">
                <div v-if="showTutorial" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" @click.self="showTutorial = false">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto shadow-2xl">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-6">
                                <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Qanday o'ynash kerak?</h2>
                                <button @click="showTutorial = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors">
                                    <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <div class="space-y-6">
                                <div class="flex items-start gap-4 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl">
                                    <div class="w-12 h-12 bg-amber-500 rounded-xl flex items-center justify-center text-2xl shrink-0 shadow-md">🎯</div>
                                    <div>
                                        <h3 class="font-bold text-gray-800 dark:text-white mb-1">Xatoni Toping</h3>
                                        <p class="text-gray-600 dark:text-gray-400">Gapdagi xato so'zni topib, ustiga bosing. Xato so'z belgilanadi.</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-4 p-4 bg-orange-50 dark:bg-orange-900/20 rounded-xl">
                                    <div class="w-12 h-12 bg-orange-500 rounded-xl flex items-center justify-center text-2xl shrink-0 shadow-md">🔧</div>
                                    <div>
                                        <h3 class="font-bold text-gray-800 dark:text-white mb-1">Xatoni Tuzating</h3>
                                        <p class="text-gray-600 dark:text-gray-400">Xatoli so'zni ko'ring va 4 ta variantdan to'g'risini tanlang.</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-4 p-4 bg-red-50 dark:bg-red-900/20 rounded-xl">
                                    <div class="w-12 h-12 bg-red-500 rounded-xl flex items-center justify-center text-2xl shrink-0 shadow-md">✍️</div>
                                    <div>
                                        <h3 class="font-bold text-gray-800 dark:text-white mb-1">Qayta Yozing</h3>
                                        <p class="text-gray-600 dark:text-gray-400">Xatoli gapni o'qing va to'g'rilangan versiyasini yozing. Eng ko'p XP beradi!</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-4 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
                                    <div class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center text-2xl shrink-0 shadow-md">🔮</div>
                                    <div>
                                        <h3 class="font-bold text-gray-800 dark:text-white mb-1">Maxsus Kuchlar</h3>
                                        <p class="text-gray-600 dark:text-gray-400">Qiyin holatlarda yordam olish uchun: Xatoni yoritish, Xato turi ko'rsatmasi, O'tkazib yuborish va Qo'shimcha vaqt.</p>
                                    </div>
                                </div>
                            </div>

                            <button @click="showTutorial = false"
                                    class="w-full mt-6 py-3 bg-gradient-to-r from-amber-500 to-orange-600 text-white rounded-xl font-semibold hover:opacity-90 transition-opacity shadow-lg">
                                Tushundim!
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- Achievements Modal -->
            <Transition name="modal">
                <div v-if="showAchievements" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" @click.self="showAchievements = false">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full shadow-2xl overflow-hidden">
                        <!-- Header -->
                        <div class="bg-gradient-to-r from-amber-500 via-orange-500 to-red-600 p-5">
                            <div class="flex items-center justify-between">
                                <h2 class="text-xl font-bold text-white flex items-center gap-2 drop-shadow-md">
                                    <span class="text-2xl">🏆</span> Yutuqlar
                                </h2>
                                <button @click="showAchievements = false" class="p-2 hover:bg-white/20 rounded-xl transition-colors">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <p class="text-white/90 text-sm mt-1">{{ earnedAchievements.length }}/{{ achievements?.length || 0 }} yutuq ochilgan</p>
                        </div>

                        <!-- Achievements List -->
                        <div class="p-4 max-h-[60vh] overflow-y-auto space-y-3">
                            <div v-for="achievement in achievements" :key="achievement.id"
                                 :class="[
                                     'flex items-center gap-4 p-4 rounded-xl transition-all',
                                     isAchievementUnlocked(achievement.id)
                                         ? 'bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/30 dark:to-amber-900/30 border-2 border-yellow-400'
                                         : 'bg-gray-50 dark:bg-gray-700/50 border-2 border-transparent opacity-70'
                                 ]">
                                <!-- Icon -->
                                <div :class="[
                                    'w-14 h-14 rounded-xl flex items-center justify-center text-2xl shrink-0',
                                    isAchievementUnlocked(achievement.id)
                                        ? 'bg-gradient-to-br from-yellow-400 to-amber-500 shadow-lg'
                                        : 'bg-gray-200 dark:bg-gray-600'
                                ]">
                                    {{ achievement.icon }}
                                </div>

                                <!-- Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="font-bold text-gray-800 dark:text-white">{{ achievement.name_uz }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400 line-clamp-1">{{ achievement.description_uz }}</div>
                                    <div class="flex items-center gap-3 mt-1">
                                        <span class="text-xs font-medium text-amber-600 dark:text-amber-400">+{{ achievement.xp_reward }} XP</span>
                                        <span class="text-xs font-medium text-yellow-600 dark:text-yellow-400">+{{ achievement.coin_reward }} 🪙</span>
                                    </div>
                                </div>

                                <!-- Status -->
                                <div v-if="isAchievementUnlocked(achievement.id)" class="shrink-0">
                                    <span class="text-2xl">✅</span>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="p-4 border-t border-gray-100 dark:border-gray-700">
                            <button @click="showAchievements = false"
                                    class="w-full py-3 bg-gradient-to-r from-amber-500 to-orange-600 text-white rounded-xl font-semibold hover:opacity-90 transition-opacity shadow-lg">
                                Yopish
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </StudentLayout>
</template>

<style scoped>
@keyframes pulse {
    0%, 100% { opacity: 0.5; }
    50% { opacity: 0.8; }
}

.animate-pulse {
    animation: pulse 3s ease-in-out infinite;
}

.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Modal Transition */
.modal-enter-active,
.modal-leave-active {
    transition: all 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from > div,
.modal-leave-to > div {
    transform: scale(0.9) translateY(20px);
}
</style>
