<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import { ArrowLeftIcon, StarIcon, LockClosedIcon } from '@heroicons/vue/24/solid'

const props = defineProps({
    levels: {
        type: Array,
        default: () => []
    },
    stats: {
        type: Object,
        default: () => ({})
    },
    config: {
        type: Object,
        default: () => ({})
    },
    achievements: {
        type: Array,
        default: () => []
    },
    gameModes: {
        type: Array,
        default: () => []
    },
    sequenceTypes: {
        type: Array,
        default: () => []
    },
    totalStars: {
        type: Number,
        default: 0
    },
    memoryRanks: {
        type: Array,
        default: () => []
    }
})

const selectedMode = ref('words')
const selectedType = ref('forward')
const showTutorial = ref(false)

const currentRank = computed(() => {
    if (!props.memoryRanks.length) return null

    let current = props.memoryRanks[0]
    for (const rank of props.memoryRanks) {
        if ((props.stats.total_xp || 0) >= rank.xp_required) {
            current = rank
        }
    }
    return current
})

const maxStars = computed(() => props.levels?.length * 3 || 30)

const levelColors = [
    '#3B82F6', // blue
    '#8B5CF6', // violet
    '#EC4899', // pink
    '#F59E0B', // amber
    '#10B981', // emerald
    '#F97316', // orange
    '#6366F1', // indigo
    '#14B8A6', // teal
    '#EF4444', // red
    '#84CC16', // lime
]

const getLevelGradient = (index) => {
    const color = levelColors[index % levelColors.length]
    return `linear-gradient(135deg, ${color}, ${adjustColor(color, -40)})`
}

const adjustColor = (color, amount) => {
    const hex = color.replace('#', '')
    const num = parseInt(hex, 16)
    const r = Math.max(0, Math.min(255, (num >> 16) + amount))
    const g = Math.max(0, Math.min(255, ((num >> 8) & 0x00FF) + amount))
    const b = Math.max(0, Math.min(255, (num & 0x0000FF) + amount))
    return `#${(1 << 24 | r << 16 | g << 8 | b).toString(16).slice(1)}`
}

const tips = [
    { icon: '🧠', text: "Har kuni mashq qiling - xotira kuchi ortadi!" },
    { icon: '⭐', text: "3 ta yulduz olish uchun 100% aniqlik kerak" },
    { icon: '🎯', text: "Diqqatni jamlang va tezlikni oshiring!" },
]
</script>

<template>
    <StudentLayout>
        <div class="min-h-screen pb-8">
            <!-- Premium Hero Header -->
            <div class="relative rounded-3xl p-6 md:p-8 mb-8 overflow-hidden">
                <!-- Animated Gradient Background -->
                <div class="absolute inset-0 bg-gradient-to-br from-violet-600 via-purple-600 to-fuchsia-500 animate-gradient"></div>

                <!-- Mesh Pattern Overlay -->
                <div class="absolute inset-0 opacity-30" style="background-image: url(&quot;data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.15'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E&quot;)"></div>

                <!-- Glow Effects -->
                <div class="absolute -top-40 -right-40 w-96 h-96 bg-yellow-400/20 rounded-full blur-3xl animate-pulse-slow"></div>
                <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-fuchsia-500/20 rounded-full blur-3xl animate-pulse-slow-delayed"></div>

                <!-- Floating Elements -->
                <div class="absolute inset-0 overflow-hidden pointer-events-none">
                    <div class="absolute top-8 left-12 text-5xl opacity-20 animate-float">🧠</div>
                    <div class="absolute top-16 right-16 text-4xl opacity-20 animate-float-delayed">🔢</div>
                    <div class="absolute bottom-16 left-1/3 text-3xl opacity-20 animate-float">📊</div>
                    <div class="absolute bottom-8 right-1/4 text-4xl opacity-15 animate-float-delayed">⭐</div>
                </div>

                <div class="relative z-10">
                    <!-- Back Button -->
                    <Link
                        :href="route('student.english.games.index')"
                        class="inline-flex items-center gap-2 text-white/70 hover:text-white text-sm mb-5 transition-all group bg-white/10 backdrop-blur-sm rounded-full px-3 py-1.5 border border-white/10 hover:border-white/30"
                    >
                        <ArrowLeftIcon class="w-4 h-4 transform group-hover:-translate-x-1 transition-transform" />
                        <span class="font-medium">Barcha o'yinlar</span>
                    </Link>

                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        <!-- Title Section -->
                        <div class="flex items-center gap-4">
                            <!-- Animated Icon -->
                            <div class="relative">
                                <div class="absolute inset-0 bg-purple-400/30 rounded-2xl blur-xl animate-pulse"></div>
                                <div class="relative w-16 h-16 md:w-20 md:h-20 bg-gradient-to-br from-purple-400 via-fuchsia-400 to-pink-500 rounded-2xl flex items-center justify-center text-3xl md:text-4xl shadow-2xl shadow-purple-500/40 transform hover:scale-105 hover:rotate-3 transition-all duration-300">
                                    🧠
                                </div>
                            </div>
                            <div>
                                <h1 class="text-3xl md:text-5xl font-black text-white mb-1 tracking-tight">
                                    Sequence <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-orange-400">Recall</span>
                                </h1>
                                <p class="text-white/80 text-sm md:text-lg font-medium">
                                    Xotira va ketma-ketlikni <span class="text-yellow-300 font-bold">mustahkamlash</span>!
                                </p>
                            </div>
                        </div>

                        <!-- Stats Cards -->
                        <div class="flex gap-3">
                            <!-- Stars Progress Card -->
                            <div class="bg-white/15 backdrop-blur-xl rounded-2xl px-5 py-4 border border-white/20 shadow-xl hover:bg-white/20 transition-all">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-amber-500 rounded-xl flex items-center justify-center shadow-lg shadow-yellow-500/30">
                                        <StarIcon class="w-6 h-6 text-white" />
                                    </div>
                                    <div>
                                        <div class="text-3xl font-black text-white">{{ totalStars }}</div>
                                        <div class="text-white/60 text-xs font-medium">/ {{ maxStars }} yulduz</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Help Button -->
                            <button
                                @click="showTutorial = true"
                                class="bg-white/15 backdrop-blur-xl rounded-2xl px-5 py-4 border border-white/20 hover:bg-white/25 hover:border-white/40 transition-all shadow-xl group"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-indigo-500 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/30 group-hover:scale-110 transition-transform">
                                        <span class="text-2xl">❓</span>
                                    </div>
                                    <span class="text-white font-semibold text-sm hidden md:block">Qanday<br>o'ynash?</span>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Stats Row -->
                    <div class="mt-8 grid grid-cols-2 lg:grid-cols-4 gap-3">
                        <div class="group bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 hover:bg-white/20 hover:border-white/30 transition-all">
                            <div class="text-3xl font-black text-white mb-1">{{ stats.games_played || 0 }}</div>
                            <div class="text-white/70 text-sm">O'yinlar</div>
                        </div>
                        <div class="group bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 hover:bg-white/20 hover:border-white/30 transition-all">
                            <div class="text-3xl font-black text-green-400 mb-1">{{ stats.accuracy || 0 }}%</div>
                            <div class="text-white/70 text-sm">Aniqlik</div>
                        </div>
                        <div class="group bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 hover:bg-white/20 hover:border-white/30 transition-all">
                            <div class="text-3xl font-black text-yellow-400 mb-1">{{ stats.best_streak || 0 }}</div>
                            <div class="text-white/70 text-sm">Eng yaxshi seriya</div>
                        </div>
                        <div class="group bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 hover:bg-white/20 hover:border-white/30 transition-all">
                            <div class="text-3xl font-black text-blue-400 mb-1">{{ currentRank?.name || 'Yangi' }}</div>
                            <div class="text-white/70 text-sm">Daraja</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Game Modes Section -->
            <div v-if="gameModes && gameModes.length" class="mb-8">
                <h2 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>🎮</span> O'yin Rejimlari
                </h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div
                        v-for="mode in gameModes"
                        :key="mode.id"
                        class="bg-white dark:bg-gray-800 rounded-2xl p-4 cursor-pointer transform hover:scale-105 transition-all shadow-lg border border-gray-100 dark:border-gray-700"
                        :class="[
                            selectedMode === mode.id
                                ? 'ring-4 ring-purple-500 shadow-xl shadow-purple-500/20'
                                : 'hover:shadow-xl'
                        ]"
                        @click="selectedMode = mode.id"
                    >
                        <div class="text-3xl mb-2">{{ mode.icon }}</div>
                        <div class="text-gray-900 dark:text-white font-semibold">{{ mode.name }}</div>
                        <div class="text-gray-600 dark:text-gray-400 text-xs mt-1">{{ mode.description }}</div>
                    </div>
                </div>
            </div>

            <!-- Sequence Types Section -->
            <div v-if="sequenceTypes && sequenceTypes.length" class="mb-8">
                <h2 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>🔄</span> Ketma-ketlik Turi
                </h2>
                <div class="flex flex-wrap gap-3">
                    <button
                        v-for="type in sequenceTypes"
                        :key="type.id"
                        class="px-6 py-3 rounded-xl font-medium transition-all shadow-lg"
                        :class="[
                            selectedType === type.id
                                ? 'bg-gradient-to-r from-purple-600 to-fuchsia-600 text-white shadow-purple-500/30'
                                : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white hover:shadow-xl border border-gray-200 dark:border-gray-700'
                        ]"
                        @click="selectedType = type.id"
                    >
                        {{ type.icon }} {{ type.name }}
                    </button>
                </div>
            </div>

            <!-- Levels Grid -->
            <div class="mb-8">
                <h2 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>🎯</span> Darajalar
                    <span class="text-sm font-normal text-gray-500">({{ levels?.length || 0 }} ta)</span>
                </h2>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 md:gap-4">
                    <div
                        v-for="(level, index) in levels"
                        :key="level.id"
                        class="relative rounded-2xl overflow-hidden transition-all duration-300"
                        :class="[
                            level.unlocked
                                ? 'cursor-pointer hover:scale-105 hover:shadow-2xl'
                                : 'cursor-not-allowed'
                        ]"
                    >
                        <!-- Unlocked Level Card -->
                        <Link
                            v-if="level.unlocked"
                            :href="route('student.english.games.sequence-recall.play', { level: level.level_number })"
                            class="block p-4 md:p-5 min-h-[180px] md:min-h-[200px] flex flex-col items-center justify-center relative"
                            :style="{ background: getLevelGradient(index) }"
                        >
                            <!-- Completed Badge -->
                            <div
                                v-if="level.completed"
                                class="absolute top-2 right-2 bg-green-500 text-white w-6 h-6 rounded-full flex items-center justify-center shadow-lg"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>

                            <!-- Level Number -->
                            <div class="text-5xl md:text-6xl font-black text-white/90 mb-1">
                                {{ level.level_number }}
                            </div>

                            <!-- Level Name -->
                            <div class="text-white font-bold text-center text-sm md:text-base mb-1">
                                {{ level.name }}
                            </div>

                            <!-- Level Info -->
                            <div class="flex items-center gap-2 mb-2">
                                <span class="bg-white/20 px-2 py-0.5 rounded text-white/90 text-xs font-medium">
                                    {{ level.sequence_length }} so'z
                                </span>
                                <span class="text-white/70 text-xs">{{ level.rounds }} raund</span>
                            </div>

                            <!-- Stars -->
                            <div class="flex gap-0.5 mb-1">
                                <StarIcon
                                    v-for="i in 3"
                                    :key="i"
                                    class="w-5 h-5"
                                    :class="i <= level.stars ? 'text-yellow-400' : 'text-white/30'"
                                />
                            </div>

                            <!-- Best Score -->
                            <div v-if="level.best_score" class="text-white/80 text-xs font-medium">
                                Eng yaxshi: {{ level.best_score }}
                            </div>
                        </Link>

                        <!-- Locked Level Card -->
                        <div
                            v-else
                            class="p-4 md:p-5 min-h-[180px] md:min-h-[200px] flex flex-col items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 border-2 border-gray-200 dark:border-gray-600 border-dashed"
                        >
                            <!-- Lock Icon -->
                            <div class="w-12 h-12 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center mb-3">
                                <LockClosedIcon class="w-6 h-6 text-gray-500 dark:text-gray-400" />
                            </div>

                            <!-- Level Number (faded) -->
                            <div class="text-4xl font-black text-gray-400 dark:text-gray-500 mb-2">
                                {{ level.level_number }}
                            </div>

                            <!-- Unlock Requirement -->
                            <div class="bg-gray-200 dark:bg-gray-600 px-3 py-1 rounded-full">
                                <span class="text-gray-600 dark:text-gray-300 text-xs font-medium">
                                    {{ level.unlock_requirement }}
                                </span>
                            </div>

                            <!-- Empty Stars -->
                            <div class="flex gap-0.5 mt-2">
                                <StarIcon v-for="i in 3" :key="i" class="w-5 h-5 text-gray-300 dark:text-gray-600" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Achievements Section -->
            <div v-if="achievements && achievements.length" class="mb-8">
                <h2 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>🏆</span> Yutuqlar
                </h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div
                        v-for="achievement in achievements.slice(0, 8)"
                        :key="achievement.id"
                        class="bg-white dark:bg-gray-800 rounded-2xl p-4 text-center shadow-lg border border-gray-100 dark:border-gray-700"
                        :class="{ 'opacity-50': !achievement.unlocked }"
                    >
                        <div class="text-3xl mb-2">{{ achievement.icon }}</div>
                        <div class="text-gray-900 dark:text-white font-medium text-sm">{{ achievement.name }}</div>
                        <div class="text-gray-600 dark:text-gray-400 text-xs mt-1">{{ achievement.description }}</div>
                        <div v-if="achievement.unlocked" class="mt-2">
                            <span class="text-xs bg-green-500/20 text-green-600 dark:text-green-400 px-2 py-1 rounded-full font-medium">
                                Ochildi!
                            </span>
                        </div>
                        <div v-else class="mt-2 text-xs text-gray-400">
                            {{ achievement.progress || 0 }}%
                        </div>
                    </div>
                </div>
            </div>

            <!-- Memory Ranks Section -->
            <div v-if="memoryRanks && memoryRanks.length" class="mb-8">
                <h2 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>⚡</span> Xotira Darajalari
                </h2>
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-lg border border-gray-100 dark:border-gray-700">
                    <div class="flex overflow-x-auto gap-4 pb-2">
                        <div
                            v-for="rank in memoryRanks"
                            :key="rank.id"
                            class="flex-shrink-0 text-center p-3 rounded-xl min-w-[120px] transition-all"
                            :class="[
                                currentRank?.id === rank.id
                                    ? 'bg-purple-500/20 ring-2 ring-purple-500'
                                    : stats.total_xp >= rank.xp_required
                                        ? 'bg-green-500/10'
                                        : 'bg-gray-100 dark:bg-gray-700'
                            ]"
                        >
                            <div class="text-2xl mb-1">{{ rank.icon }}</div>
                            <div
                                class="font-medium text-sm"
                                :class="[
                                    currentRank?.id === rank.id
                                        ? 'text-purple-700 dark:text-purple-300'
                                        : 'text-gray-900 dark:text-white'
                                ]"
                            >
                                {{ rank.name }}
                            </div>
                            <div class="text-gray-600 dark:text-gray-400 text-xs">{{ rank.xp_required }} XP</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tips Section -->
            <div class="bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 rounded-2xl p-5 border border-amber-200 dark:border-amber-800">
                <h3 class="font-bold text-amber-900 dark:text-amber-300 mb-3 flex items-center gap-2">
                    <span>💡</span> Foydali Maslahatlar
                </h3>
                <div class="grid md:grid-cols-3 gap-3">
                    <div
                        v-for="tip in tips"
                        :key="tip.text"
                        class="flex items-start gap-3 bg-white/50 dark:bg-white/5 rounded-xl p-3"
                    >
                        <span class="text-xl">{{ tip.icon }}</span>
                        <p class="text-gray-700 dark:text-gray-300 text-sm">{{ tip.text }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tutorial Modal -->
        <Teleport to="body">
            <div
                v-if="showTutorial"
                class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-start justify-center p-4 overflow-y-auto"
                @click.self="showTutorial = false"
            >
                <div class="bg-white dark:bg-gray-800 rounded-2xl w-full max-w-lg my-4 relative animate-scale-in">
                    <!-- Close Button -->
                    <button
                        @click="showTutorial = false"
                        class="absolute -top-3 -right-3 w-10 h-10 bg-gray-800 dark:bg-gray-600 hover:bg-gray-700 rounded-full flex items-center justify-center text-white shadow-lg z-10 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <!-- Header -->
                    <div class="bg-gradient-to-r from-violet-600 to-purple-600 p-5 rounded-t-2xl">
                        <div class="flex items-center gap-3">
                            <span class="text-3xl">🧠</span>
                            <div>
                                <h2 class="text-xl font-black text-white">Sequence Recall</h2>
                                <p class="text-white/80 text-sm">Qanday o'ynash kerak?</p>
                            </div>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-5 space-y-4">
                        <!-- Game Goal -->
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-4 border border-green-200 dark:border-green-800">
                            <h3 class="font-bold text-green-800 dark:text-green-400 mb-1 flex items-center gap-2 text-sm">
                                <span>🎯</span> O'yin Maqsadi
                            </h3>
                            <p class="text-green-700 dark:text-green-300 text-sm">
                                Ko'rsatilgan ketma-ketlikni <strong>eslab qoling</strong> va to'g'ri tartibda qayta kiriting. Xotirangizni sinab ko'ring!
                            </p>
                        </div>

                        <!-- Game Modes -->
                        <div v-if="gameModes && gameModes.length">
                            <h3 class="font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2 text-sm">
                                <span>🎮</span> O'yin Rejimlari
                            </h3>
                            <div class="grid grid-cols-2 gap-2">
                                <div
                                    v-for="mode in gameModes"
                                    :key="mode.id"
                                    class="flex items-center gap-2 bg-gray-50 dark:bg-gray-700 rounded-lg p-2.5"
                                >
                                    <span class="text-xl">{{ mode.icon }}</span>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white text-xs">{{ mode.name }}</p>
                                        <p class="text-gray-500 dark:text-gray-400 text-[10px]">{{ mode.description }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- How to Play Steps -->
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2 text-sm">
                                <span>📋</span> O'ynash Qadamlari
                            </h3>
                            <div class="space-y-2">
                                <div class="flex items-center gap-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg p-2.5">
                                    <div class="w-6 h-6 bg-purple-500 text-white text-xs font-bold rounded flex items-center justify-center flex-shrink-0">1</div>
                                    <p class="text-gray-800 dark:text-gray-200 text-sm">Ketma-ketlikni diqqat bilan kuzating</p>
                                </div>
                                <div class="flex items-center gap-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg p-2.5">
                                    <div class="w-6 h-6 bg-purple-500 text-white text-xs font-bold rounded flex items-center justify-center flex-shrink-0">2</div>
                                    <p class="text-gray-800 dark:text-gray-200 text-sm">Eslab qoling va yodingizda saqlang</p>
                                </div>
                                <div class="flex items-center gap-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg p-2.5">
                                    <div class="w-6 h-6 bg-purple-500 text-white text-xs font-bold rounded flex items-center justify-center flex-shrink-0">3</div>
                                    <p class="text-gray-800 dark:text-gray-200 text-sm">To'g'ri tartibda qayta kiriting</p>
                                </div>
                            </div>
                        </div>

                        <!-- Scoring -->
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl p-4 border border-yellow-200 dark:border-yellow-800">
                            <h3 class="font-bold text-yellow-800 dark:text-yellow-400 mb-2 flex items-center gap-2 text-sm">
                                <span>⭐</span> Yulduzlar Tizimi
                            </h3>
                            <div class="space-y-2 text-xs">
                                <div class="flex justify-between items-center bg-white/50 dark:bg-black/20 rounded p-2">
                                    <span class="text-gray-700 dark:text-gray-300">1 yulduz:</span>
                                    <span class="font-bold text-gray-600 dark:text-gray-400">O'yinni tugatish</span>
                                </div>
                                <div class="flex justify-between items-center bg-white/50 dark:bg-black/20 rounded p-2">
                                    <span class="text-gray-700 dark:text-gray-300">2 yulduz:</span>
                                    <span class="font-bold text-blue-600">80%+ aniqlik</span>
                                </div>
                                <div class="flex justify-between items-center bg-white/50 dark:bg-black/20 rounded p-2">
                                    <span class="text-gray-700 dark:text-gray-300">3 yulduz:</span>
                                    <span class="font-bold text-green-600">100% aniqlik</span>
                                </div>
                            </div>
                        </div>

                        <!-- Start Button -->
                        <button
                            @click="showTutorial = false"
                            class="w-full py-3 bg-gradient-to-r from-violet-600 to-purple-600 text-white font-bold rounded-xl hover:shadow-lg transition-all text-sm"
                        >
                            Tushundim! 🚀
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </StudentLayout>
</template>

<style scoped>
@keyframes float {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(5deg); }
}

@keyframes float-delayed {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-15px) rotate(-5deg); }
}

@keyframes gradient {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

@keyframes pulse-slow {
    0%, 100% { opacity: 0.2; transform: scale(1); }
    50% { opacity: 0.3; transform: scale(1.1); }
}

.animate-float {
    animation: float 6s ease-in-out infinite;
}

.animate-float-delayed {
    animation: float-delayed 5s ease-in-out infinite;
    animation-delay: 1s;
}

.animate-gradient {
    background-size: 200% 200%;
    animation: gradient 8s ease infinite;
}

.animate-pulse-slow {
    animation: pulse-slow 4s ease-in-out infinite;
}

.animate-pulse-slow-delayed {
    animation: pulse-slow 5s ease-in-out infinite;
    animation-delay: 2s;
}

.animate-scale-in {
    animation: scaleIn 0.3s ease-out;
}

@keyframes scaleIn {
    from { transform: scale(0.95); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
</style>
