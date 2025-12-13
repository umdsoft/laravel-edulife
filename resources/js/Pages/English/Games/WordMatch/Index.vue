<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import StudentLayout from '@/Layouts/StudentLayout.vue'

const props = defineProps({
    levels: Array,
    config: Object,
    userStats: Object,
    gameModes: Object,
    powerups: Object,
    achievements: Array,
    userAchievements: Array,
})

const showTutorial = ref(false)
const showAchievements = ref(false)
const selectedMode = ref('classic_match')

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

function getLevelIcon(level) {
    return level.icon || '🔗'
}

function getGradientClass(level) {
    // Unique colors for each level based on CEFR
    const levelColors = {
        1: 'from-emerald-500 to-green-600',    // A1
        2: 'from-green-500 to-teal-600',        // A1+
        3: 'from-cyan-500 to-blue-600',         // A2
        4: 'from-blue-500 to-indigo-600',       // A2+
        5: 'from-indigo-500 to-purple-600',     // B1
        6: 'from-purple-500 to-violet-600',     // B1+
        7: 'from-violet-500 to-fuchsia-600',    // B2
        8: 'from-fuchsia-500 to-pink-600',      // B2+
        9: 'from-rose-500 to-red-600',          // C1
        10: 'from-amber-500 to-orange-600',     // C2
    }
    return levelColors[level.number] || level.color || 'from-purple-500 to-indigo-600'
}

function playLevel(level) {
    if (!level.unlocked) return
    router.visit(`/student/english/games/word-match/play/${level.number}`)
}

function getModeIcon(mode) {
    const icons = {
        'classic_match': '🎯',
        'memory_flip': '🧠',
        'speed_match': '⚡',
    }
    return icons[mode.id] || '🎮'
}

function getPowerupIcon(powerup) {
    return powerup.icon || '🔮'
}
</script>

<template>
    <Head title="So'z Moslashtirish - Word Match" />

    <StudentLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900 -m-6 p-6 font-sans">
            <!-- Premium Hero Section -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-purple-600 via-violet-600 to-indigo-700 p-8 mb-8 shadow-2xl">
                <!-- Animated Background -->
                <div class="absolute inset-0 overflow-hidden">
                    <div class="absolute -top-1/2 -right-1/2 w-full h-full bg-white/10 rounded-full blur-3xl animate-pulse"></div>
                    <div class="absolute -bottom-1/2 -left-1/2 w-full h-full bg-white/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
                    <!-- Decorative elements -->
                    <div class="absolute top-10 right-10 w-20 h-20 bg-yellow-400/20 rounded-full blur-2xl"></div>
                    <div class="absolute bottom-10 left-10 w-32 h-32 bg-pink-400/20 rounded-full blur-2xl"></div>
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
                                <span class="text-4xl">🔗</span>
                                {{ config?.game_name_uz || "So'z Moslashtirish" }}
                            </h1>
                            <p class="text-white/90 mt-2 text-lg drop-shadow-md">
                                {{ config?.description_uz || "So'zlarni tarjima, ta'rif va sinonimlar bilan moslang" }}
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
                            <div class="text-3xl font-bold text-white drop-shadow-md">{{ completedLevels }}/{{ totalLevels }}</div>
                            <div class="text-white/90 text-sm font-medium">Bosqichlar</div>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center shadow-lg border border-white/10">
                            <div class="text-3xl font-bold text-white drop-shadow-md">{{ userStats?.total_matches_completed || 0 }}</div>
                            <div class="text-white/90 text-sm font-medium">Mosliklar</div>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center shadow-lg border border-white/10">
                            <div class="text-3xl font-bold text-white drop-shadow-md">{{ userStats?.best_streak || 0 }}</div>
                            <div class="text-white/90 text-sm font-medium">🔥 Eng yaxshi streak</div>
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
                                 ? 'bg-gradient-to-br from-purple-600 to-indigo-700 border-purple-400 shadow-purple-200 dark:shadow-purple-900/50 scale-[1.02]'
                                 : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:border-purple-400 hover:shadow-xl'
                         ]">
                        <div class="flex items-center gap-4">
                            <div :class="[
                                'w-14 h-14 rounded-xl flex items-center justify-center text-3xl',
                                selectedMode === mode.id
                                    ? 'bg-white/20'
                                    : 'bg-gradient-to-br from-purple-100 to-indigo-100 dark:from-purple-900/50 dark:to-indigo-900/50'
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

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                    <div v-for="level in levels" :key="level.number"
                         @click="playLevel(level)"
                         :class="[
                             'relative rounded-2xl overflow-hidden transition-all duration-300 cursor-pointer group min-h-[200px] shadow-lg',
                             level.unlocked
                                 ? 'hover:scale-[1.03] hover:shadow-2xl'
                                 : 'cursor-not-allowed'
                         ]">
                        <!-- Level Card Background -->
                        <div :class="[
                            'absolute inset-0 bg-gradient-to-br',
                            getGradientClass(level)
                        ]"></div>

                        <!-- Decorative overlay for better text readability -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/30 via-transparent to-transparent"></div>

                        <!-- Lock Overlay -->
                        <div v-if="!level.unlocked" class="absolute inset-0 bg-black/60 backdrop-blur-[3px] z-20 flex items-center justify-center">
                            <div class="text-center">
                                <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-3 shadow-xl">
                                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <p class="text-white text-sm font-semibold drop-shadow-lg">{{ level.unlock_requirement?.stars || 1 }} yulduz kerak</p>
                            </div>
                        </div>

                        <!-- Card Content -->
                        <div class="relative z-10 p-5 h-full flex flex-col">
                            <!-- Header -->
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-3xl drop-shadow-lg filter brightness-110">{{ getLevelIcon(level) }}</span>
                                <span class="bg-white/30 backdrop-blur-sm px-3 py-1 rounded-full text-white text-sm font-bold shadow-lg border border-white/20">
                                    {{ level.cefr_level }}
                                </span>
                            </div>

                            <!-- Title & Description -->
                            <h3 class="text-lg font-bold text-white drop-shadow-[0_2px_4px_rgba(0,0,0,0.3)] mb-1">{{ level.name_uz }}</h3>
                            <p class="text-white/95 text-sm mb-3 drop-shadow-md line-clamp-2">{{ level.description_uz }}</p>

                            <!-- Spacer -->
                            <div class="flex-1"></div>

                            <!-- Stars -->
                            <div class="flex items-center gap-1 mb-3">
                                <span v-for="i in 3" :key="i"
                                      :class="i <= (level.best_stars || 0) ? 'text-yellow-300 drop-shadow-[0_2px_4px_rgba(0,0,0,0.4)]' : 'text-white/50'"
                                      class="text-xl">⭐</span>
                            </div>

                            <!-- Level Info -->
                            <div class="flex items-center justify-between text-white text-sm font-semibold">
                                <span class="bg-black/20 backdrop-blur-sm px-3 py-1.5 rounded-lg shadow-md">{{ level.pairs_count }} juftlik</span>
                                <span class="bg-black/20 backdrop-blur-sm px-3 py-1.5 rounded-lg shadow-md">{{ level.time_limit?.classic_match || 120 }}s</span>
                            </div>

                            <!-- Completed Badge -->
                            <div v-if="level.completed" class="absolute top-3 right-3 z-10">
                                <span class="bg-green-500/90 backdrop-blur-sm text-white text-xs px-2.5 py-1 rounded-full font-bold shadow-lg border border-green-400">✓</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- How It Works Section -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg mb-8">
                <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                    <span>🎯</span> Qanday O'ynaladi
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                        <span class="text-2xl">1️⃣</span>
                        <div>
                            <div class="font-semibold text-gray-800 dark:text-white">Rejimni tanlang</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Klassik, Xotira yoki Tezkor rejimni tanlang</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                        <span class="text-2xl">2️⃣</span>
                        <div>
                            <div class="font-semibold text-gray-800 dark:text-white">Juftliklarni toping</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">So'z va tarjimani/ta'rifni moslang</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                        <span class="text-2xl">3️⃣</span>
                        <div>
                            <div class="font-semibold text-gray-800 dark:text-white">Tez bo'ling</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Tezroq moslashtirish ko'proq ball beradi</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                        <span class="text-2xl">4️⃣</span>
                        <div>
                            <div class="font-semibold text-gray-800 dark:text-white">Yutuqlar yig'ing</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Ball, XP va tangalar oling</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tips Section -->
            <div class="bg-gradient-to-r from-purple-600 via-violet-600 to-indigo-700 rounded-2xl p-6 text-white shadow-xl">
                <h2 class="text-xl font-bold mb-4 flex items-center gap-2 drop-shadow-md">
                    <span>💡</span> Maslahatlar
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white/15 backdrop-blur-sm rounded-xl p-4 border border-white/10 shadow-lg">
                        <div class="font-semibold mb-2 drop-shadow-md">⚡ Tezlik muhim</div>
                        <p class="text-sm text-white/90">Tez moslashtirish uchun vaqt bonusi olasiz!</p>
                    </div>
                    <div class="bg-white/15 backdrop-blur-sm rounded-xl p-4 border border-white/10 shadow-lg">
                        <div class="font-semibold mb-2 drop-shadow-md">🔥 Streak saqlang</div>
                        <p class="text-sm text-white/90">Ketma-ket to'g'ri mosliklar multiplikatorni oshiradi!</p>
                    </div>
                    <div class="bg-white/15 backdrop-blur-sm rounded-xl p-4 border border-white/10 shadow-lg">
                        <div class="font-semibold mb-2 drop-shadow-md">🧠 Xotira rejimi</div>
                        <p class="text-sm text-white/90">Xotira rejimida qo'shimcha XP olasiz!</p>
                    </div>
                </div>
            </div>

            <!-- Tutorial Modal -->
            <div v-if="showTutorial" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Qanday o'ynash kerak?</h2>
                            <button @click="showTutorial = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl">
                                <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="space-y-6">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/50 rounded-xl flex items-center justify-center text-2xl shrink-0">🎯</div>
                                <div>
                                    <h3 class="font-semibold text-gray-800 dark:text-white mb-1">Klassik Rejim</h3>
                                    <p class="text-gray-600 dark:text-gray-400">Ikki ustundagi so'zlarni moslang. Chap tomondagi so'zni bosing, keyin o'ng tomondagi mos juftlikni tanlang.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/50 rounded-xl flex items-center justify-center text-2xl shrink-0">🧠</div>
                                <div>
                                    <h3 class="font-semibold text-gray-800 dark:text-white mb-1">Xotira Rejimi</h3>
                                    <p class="text-gray-600 dark:text-gray-400">Kartalarni oching va juftliklarni toping. Kartalar yopiq holda turadi, ikkita kartani oching va mos kelsa, ular ochiq qoladi.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/50 rounded-xl flex items-center justify-center text-2xl shrink-0">⚡</div>
                                <div>
                                    <h3 class="font-semibold text-gray-800 dark:text-white mb-1">Tezkor Rejim</h3>
                                    <p class="text-gray-600 dark:text-gray-400">So'z ko'rsatiladi va to'rtta variant beriladi. To'g'ri tarjimani tez tanlang!</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/50 rounded-xl flex items-center justify-center text-2xl shrink-0">🔮</div>
                                <div>
                                    <h3 class="font-semibold text-gray-800 dark:text-white mb-1">Maxsus Kuchlar</h3>
                                    <p class="text-gray-600 dark:text-gray-400">Qiyin holatlarda maxsus kuchlardan foydalaning: Ko'rsatish, Aralashtirish, Vaqtni to'xtatish va boshqalar.</p>
                                </div>
                            </div>
                        </div>

                        <button @click="showTutorial = false"
                                class="w-full mt-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl font-semibold hover:opacity-90 transition-opacity shadow-lg">
                            Tushundim!
                        </button>
                    </div>
                </div>
            </div>

            <!-- Achievements Modal -->
            <Transition name="modal">
                <div v-if="showAchievements" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" @click.self="showAchievements = false">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full shadow-2xl overflow-hidden">
                        <!-- Header -->
                        <div class="bg-gradient-to-r from-purple-600 via-violet-600 to-indigo-700 p-5">
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
                                     userAchievements?.includes(achievement.id)
                                         ? 'bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/30 dark:to-amber-900/30 border-2 border-yellow-400'
                                         : 'bg-gray-50 dark:bg-gray-700/50 border-2 border-transparent opacity-70'
                                 ]">
                                <!-- Icon -->
                                <div :class="[
                                    'w-14 h-14 rounded-xl flex items-center justify-center text-2xl shrink-0',
                                    userAchievements?.includes(achievement.id)
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
                                        <span class="text-xs font-medium text-purple-600 dark:text-purple-400">+{{ achievement.xp_reward }} XP</span>
                                        <span class="text-xs font-medium text-yellow-600 dark:text-yellow-400">+{{ achievement.coin_reward }} 🪙</span>
                                    </div>
                                </div>

                                <!-- Status -->
                                <div v-if="userAchievements?.includes(achievement.id)" class="shrink-0">
                                    <span class="text-2xl">✅</span>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="p-4 border-t border-gray-100 dark:border-gray-700">
                            <button @click="showAchievements = false"
                                    class="w-full py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl font-semibold hover:opacity-90 transition-opacity shadow-lg">
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
