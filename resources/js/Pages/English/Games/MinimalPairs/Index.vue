<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import StudentLayout from '@/Layouts/StudentLayout.vue'

const props = defineProps({
    levels: Array,
    config: Object,
    userStats: Object,
    gameModes: Object,
    categories: Object,
    powerups: Object,
    achievements: Array,
    userAchievements: Array,
})

const showTutorial = ref(false)
const showAchievements = ref(false)
const selectedMode = ref('listen_choose')

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

const categoriesList = computed(() => {
    return Object.values(props.categories || {})
})

const listeningRank = computed(() => {
    return props.userStats?.listening_rank?.current || null
})

// Unique audio-themed gradients for each level
const levelGradients = {
    1: 'from-emerald-400 via-emerald-500 to-green-600',
    2: 'from-lime-400 via-lime-500 to-green-600',
    3: 'from-teal-400 via-teal-500 to-cyan-600',
    4: 'from-cyan-400 via-cyan-500 to-blue-600',
    5: 'from-blue-400 via-blue-500 to-indigo-600',
    6: 'from-indigo-400 via-indigo-500 to-violet-600',
    7: 'from-violet-400 via-violet-500 to-purple-600',
    8: 'from-purple-400 via-purple-500 to-fuchsia-600',
    9: 'from-fuchsia-400 via-fuchsia-500 to-pink-600',
    10: 'from-rose-400 via-rose-500 to-red-600',
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
    return level.icon || '👂'
}

function getGradientClass(level) {
    return levelGradients[level.number] || 'from-emerald-500 to-teal-600'
}

function getCefrBadgeClass(cefrLevel) {
    return cefrColors[cefrLevel] || 'bg-gray-500'
}

function getTimeLimit(level) {
    if (typeof level.time_limit === 'object') {
        return level.time_limit[selectedMode.value] || level.time_limit.listen_choose || 15
    }
    return level.time_limit || 15
}

function playLevel(level) {
    if (!level.unlocked) return
    router.visit(`/student/english/games/minimal-pairs/play/${level.number}`)
}

function getModeIcon(mode) {
    const icons = {
        'listen_choose': '🎧',
        'same_different': '🔀',
        'odd_one_out': '🎯',
        'speak_compare': '🎤',
    }
    return icons[mode.id] || mode.icon || '🎵'
}

function getPowerupIcon(powerup) {
    return powerup.icon || '🔮'
}

function isAchievementUnlocked(achievementId) {
    return (props.userAchievements || []).includes(achievementId)
}

function isModeAvailableForLevel(modeId, level) {
    if (!level) return false
    const availableModes = level.available_modes || ['listen_choose']
    return availableModes.includes(modeId)
}
</script>

<template>
    <Head title="Minimal Pairs - Tinglash Mashqlari" />

    <StudentLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900 -m-6 p-6 font-sans">
            <!-- Premium Hero Section with Audio Theme -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-500 via-teal-500 to-cyan-600 p-8 mb-8 shadow-2xl">
                <!-- Animated Audio Wave Background -->
                <div class="absolute inset-0 overflow-hidden">
                    <div class="absolute -top-1/2 -right-1/2 w-full h-full bg-white/10 rounded-full blur-3xl animate-pulse"></div>
                    <div class="absolute -bottom-1/2 -left-1/2 w-full h-full bg-white/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
                    <!-- Sound wave decorations -->
                    <div class="absolute top-1/2 left-1/4 -translate-y-1/2 opacity-20">
                        <svg class="w-64 h-64" viewBox="0 0 100 100" fill="none">
                            <path d="M10 50 Q 25 30, 40 50 T 70 50 T 100 50" stroke="white" stroke-width="2" fill="none"/>
                            <path d="M10 50 Q 25 70, 40 50 T 70 50 T 100 50" stroke="white" stroke-width="2" fill="none"/>
                        </svg>
                    </div>
                    <div class="absolute bottom-10 right-10 w-32 h-32 bg-cyan-400/20 rounded-full blur-2xl"></div>
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
                                <span class="text-4xl">👂</span>
                                {{ config?.game_name_uz || "Minimal Juftliklar" }}
                            </h1>
                            <p class="text-white/90 mt-2 text-lg drop-shadow-md">
                                {{ config?.description_uz || "Ingliz tovushlarini farqlashni o'rganing va talaffuzingizni mukammallang" }}
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
                            <div class="text-3xl font-bold text-white drop-shadow-md">{{ userStats?.total_pairs_completed || 0 }}</div>
                            <div class="text-white/90 text-sm font-medium">🎧 Juftliklar</div>
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

                    <!-- Listening Rank -->
                    <div v-if="listeningRank" class="mt-4 bg-white/20 backdrop-blur-sm rounded-xl p-4 shadow-lg border border-white/10">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-white/25 rounded-xl flex items-center justify-center text-2xl shadow-lg">
                                    {{ listeningRank.icon }}
                                </div>
                                <div>
                                    <div class="text-white font-bold drop-shadow-md">{{ listeningRank.name_uz || listeningRank.name }}</div>
                                    <div class="text-white/80 text-sm">Tinglash darajangiz</div>
                                </div>
                            </div>
                            <div v-if="userStats?.listening_rank?.next" class="text-right">
                                <div class="text-white/80 text-sm">Keyingi daraja:</div>
                                <div class="text-white font-semibold flex items-center gap-2">
                                    <span>{{ userStats.listening_rank.next.icon }}</span>
                                    {{ userStats.listening_rank.next.name_uz || userStats.listening_rank.next.name }}
                                </div>
                            </div>
                        </div>
                        <div v-if="userStats?.listening_rank?.progress" class="mt-3">
                            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                                <div class="h-full bg-white rounded-full transition-all duration-500" :style="{ width: userStats.listening_rank.progress + '%' }"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Game Modes Section -->
            <div class="mb-6">
                <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                    <span>🎮</span> O'yin Rejimlari
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div v-for="mode in gameModesList" :key="mode.id"
                         @click="selectedMode = mode.id"
                         :class="[
                             'relative p-5 rounded-2xl cursor-pointer transition-all duration-300 border-2 shadow-lg',
                             selectedMode === mode.id
                                 ? 'bg-gradient-to-br from-emerald-500 to-teal-600 border-emerald-400 shadow-emerald-200 dark:shadow-emerald-900/50 scale-[1.02]'
                                 : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:border-emerald-400 hover:shadow-xl'
                         ]">
                        <div class="flex items-center gap-4">
                            <div :class="[
                                'w-14 h-14 rounded-xl flex items-center justify-center text-3xl shadow-md',
                                selectedMode === mode.id
                                    ? 'bg-white/20'
                                    : 'bg-gradient-to-br from-emerald-100 to-teal-100 dark:from-emerald-900/50 dark:to-teal-900/50'
                            ]">
                                {{ getModeIcon(mode) }}
                            </div>
                            <div>
                                <h3 :class="[
                                    'font-bold text-lg',
                                    selectedMode === mode.id ? 'text-white drop-shadow-md' : 'text-gray-800 dark:text-white'
                                ]">{{ mode.name_uz }}</h3>
                                <p :class="[
                                    'text-sm line-clamp-2',
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

            <!-- Phoneme Categories Section -->
            <div class="mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-5">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="text-2xl">🔤</span>
                        <span class="font-bold text-lg text-gray-800 dark:text-white">Fonem Kategoriyalari</span>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                        <!-- Vowels -->
                        <div class="col-span-full mb-2">
                            <span class="text-sm font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Unlilar</span>
                        </div>
                        <template v-for="cat in categoriesList" :key="cat.id">
                            <div v-if="cat.type === 'vowel'"
                                 class="group flex items-center gap-3 p-3 bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/30 dark:to-teal-900/30 rounded-xl hover:shadow-md transition-all cursor-default border border-emerald-200/50 dark:border-emerald-800/50">
                                <span class="text-2xl group-hover:scale-110 transition-transform">{{ cat.icon }}</span>
                                <div>
                                    <div class="font-semibold text-gray-800 dark:text-white text-sm">{{ cat.name_uz || cat.name }}</div>
                                    <div class="text-xs text-emerald-600 dark:text-emerald-400 font-mono">{{ cat.phonemes }}</div>
                                </div>
                            </div>
                        </template>

                        <!-- Consonants -->
                        <div class="col-span-full mb-2 mt-4">
                            <span class="text-sm font-semibold text-cyan-600 dark:text-cyan-400 uppercase tracking-wider">Undoshlar</span>
                        </div>
                        <template v-for="cat in categoriesList" :key="cat.id + '_cons'">
                            <div v-if="cat.type === 'consonant'"
                                 class="group flex items-center gap-3 p-3 bg-gradient-to-r from-cyan-50 to-blue-50 dark:from-cyan-900/30 dark:to-blue-900/30 rounded-xl hover:shadow-md transition-all cursor-default border border-cyan-200/50 dark:border-cyan-800/50">
                                <span class="text-2xl group-hover:scale-110 transition-transform">{{ cat.icon }}</span>
                                <div>
                                    <div class="font-semibold text-gray-800 dark:text-white text-sm">{{ cat.name_uz || cat.name }}</div>
                                    <div class="text-xs text-cyan-600 dark:text-cyan-400 font-mono">{{ cat.phonemes }}</div>
                                </div>
                            </div>
                        </template>
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

                        <!-- Audio wave pattern decoration -->
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                                    </svg>
                                    {{ level.pairs_count }}
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
                    <div class="flex items-start gap-3 p-4 bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 rounded-xl border border-emerald-200/50 dark:border-emerald-800/50">
                        <div class="w-10 h-10 bg-emerald-500 rounded-lg flex items-center justify-center text-white font-bold shadow-md">1</div>
                        <div>
                            <div class="font-semibold text-gray-800 dark:text-white">Diqqat bilan tinglang</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Audio tovushni tinglang</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-4 bg-gradient-to-br from-teal-50 to-cyan-50 dark:from-teal-900/20 dark:to-cyan-900/20 rounded-xl border border-teal-200/50 dark:border-teal-800/50">
                        <div class="w-10 h-10 bg-teal-500 rounded-lg flex items-center justify-center text-white font-bold shadow-md">2</div>
                        <div>
                            <div class="font-semibold text-gray-800 dark:text-white">Farqni aniqlang</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Qaysi so'z ekanini aniqlang</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-4 bg-gradient-to-br from-cyan-50 to-blue-50 dark:from-cyan-900/20 dark:to-blue-900/20 rounded-xl border border-cyan-200/50 dark:border-cyan-800/50">
                        <div class="w-10 h-10 bg-cyan-500 rounded-lg flex items-center justify-center text-white font-bold shadow-md">3</div>
                        <div>
                            <div class="font-semibold text-gray-800 dark:text-white">Javobingizni tanlang</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">To'g'ri variantni bosing</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-4 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl border border-blue-200/50 dark:border-blue-800/50">
                        <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center text-white font-bold shadow-md">4</div>
                        <div>
                            <div class="font-semibold text-gray-800 dark:text-white">Yutuqlar yig'ing</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Ball, XP va tangalar oling</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tips Section -->
            <div class="bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-600 rounded-2xl p-6 text-white shadow-xl">
                <h2 class="text-xl font-bold mb-4 flex items-center gap-2 drop-shadow-md">
                    <span>💡</span> Maslahatlar
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white/15 backdrop-blur-sm rounded-xl p-4 border border-white/10 shadow-lg hover:bg-white/20 transition-colors">
                        <div class="font-semibold mb-2 drop-shadow-md flex items-center gap-2">
                            <span class="text-xl">🎧</span> Quloq bilan tinglang
                        </div>
                        <p class="text-sm text-white/90">Quloqchinlar yordamida tovushlarni aniqroq eshiting!</p>
                    </div>
                    <div class="bg-white/15 backdrop-blur-sm rounded-xl p-4 border border-white/10 shadow-lg hover:bg-white/20 transition-colors">
                        <div class="font-semibold mb-2 drop-shadow-md flex items-center gap-2">
                            <span class="text-xl">🔁</span> Qayta tinglang
                        </div>
                        <p class="text-sm text-white/90">Ishonchsiz bo'lsangiz, qayta tinglash tugmasini ishlating!</p>
                    </div>
                    <div class="bg-white/15 backdrop-blur-sm rounded-xl p-4 border border-white/10 shadow-lg hover:bg-white/20 transition-colors">
                        <div class="font-semibold mb-2 drop-shadow-md flex items-center gap-2">
                            <span class="text-xl">🎤</span> Gapirish rejimi
                        </div>
                        <p class="text-sm text-white/90">Speak & Compare rejimi orqali talaffuzingizni tekshiring!</p>
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
                                <div class="flex items-start gap-4 p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl">
                                    <div class="w-12 h-12 bg-emerald-500 rounded-xl flex items-center justify-center text-2xl shrink-0 shadow-md">🎧</div>
                                    <div>
                                        <h3 class="font-bold text-gray-800 dark:text-white mb-1">Tinglash va Tanlash</h3>
                                        <p class="text-gray-600 dark:text-gray-400">Audio so'zni tinglang va qaysi so'z ekanini tanlang. Masalan: "ship" yoki "sheep"</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-4 p-4 bg-teal-50 dark:bg-teal-900/20 rounded-xl">
                                    <div class="w-12 h-12 bg-teal-500 rounded-xl flex items-center justify-center text-2xl shrink-0 shadow-md">🔀</div>
                                    <div>
                                        <h3 class="font-bold text-gray-800 dark:text-white mb-1">Bir xil yoki Boshqacha</h3>
                                        <p class="text-gray-600 dark:text-gray-400">Ikki tovushni tinglang va ular bir xilmi yoki boshqachami aniqlang.</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-4 p-4 bg-cyan-50 dark:bg-cyan-900/20 rounded-xl">
                                    <div class="w-12 h-12 bg-cyan-500 rounded-xl flex items-center justify-center text-2xl shrink-0 shadow-md">🎯</div>
                                    <div>
                                        <h3 class="font-bold text-gray-800 dark:text-white mb-1">Boshqasini Toping</h3>
                                        <p class="text-gray-600 dark:text-gray-400">3-4 so'zni tinglang va boshqa tovushli so'zni tanlang.</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                                    <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center text-2xl shrink-0 shadow-md">🎤</div>
                                    <div>
                                        <h3 class="font-bold text-gray-800 dark:text-white mb-1">Gapirish va Solishtirish</h3>
                                        <p class="text-gray-600 dark:text-gray-400">So'zni o'zingiz ayting va native speaker bilan solishtiring. Talaffuzingizni yaxshilang!</p>
                                    </div>
                                </div>
                            </div>

                            <button @click="showTutorial = false"
                                    class="w-full mt-6 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-semibold hover:opacity-90 transition-opacity shadow-lg">
                                Tushundim!
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- Achievements Modal -->
            <Transition name="modal">
                <div v-if="showAchievements" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" @click.self="showAchievements = false">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto shadow-2xl">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-6">
                                <h2 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                                    🏆 Yutuqlar
                                    <span class="text-sm font-normal text-gray-500 dark:text-gray-400">({{ earnedAchievements.length }}/{{ achievements?.length || 0 }})</span>
                                </h2>
                                <button @click="showAchievements = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors">
                                    <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-for="achievement in achievements" :key="achievement.id"
                                     :class="[
                                         'p-4 rounded-xl border-2 transition-all',
                                         isAchievementUnlocked(achievement.id)
                                             ? 'bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/30 dark:to-teal-900/30 border-emerald-300 dark:border-emerald-700'
                                             : 'bg-gray-50 dark:bg-gray-700/50 border-gray-200 dark:border-gray-600 opacity-60'
                                     ]">
                                    <div class="flex items-center gap-3 mb-2">
                                        <span class="text-3xl">{{ achievement.icon }}</span>
                                        <div>
                                            <h3 :class="[
                                                'font-bold',
                                                isAchievementUnlocked(achievement.id) ? 'text-emerald-700 dark:text-emerald-300' : 'text-gray-600 dark:text-gray-400'
                                            ]">{{ achievement.name_uz }}</h3>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ achievement.description_uz }}</p>
                                        </div>
                                    </div>
                                    <div v-if="isAchievementUnlocked(achievement.id)" class="flex items-center gap-2 text-sm">
                                        <span class="text-emerald-600 dark:text-emerald-400 font-semibold">✓ Qo'lga kiritildi!</span>
                                        <span class="text-gray-400">|</span>
                                        <span class="text-yellow-600 dark:text-yellow-400">+{{ achievement.xp_reward }} XP</span>
                                        <span class="text-amber-600 dark:text-amber-400">+{{ achievement.coin_reward }} 🪙</span>
                                    </div>
                                </div>
                            </div>

                            <button @click="showAchievements = false"
                                    class="w-full mt-6 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-semibold hover:opacity-90 transition-opacity shadow-lg">
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
    transform: scale(0.95);
}
</style>
