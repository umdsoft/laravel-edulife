<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import StudentLayout from '@/Layouts/StudentLayout.vue'

const props = defineProps({
    levels: { type: Array, required: true },
    config: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({}) }
})

const showTutorial = ref(false)

const totalStars = computed(() => {
    return props.levels?.reduce((sum, level) => sum + (level.stars || 0), 0) || 0
})

const maxStars = computed(() => (props.levels?.length || 0) * 3)

const completedLevels = computed(() => {
    return props.levels?.filter(level => level.completed).length || 0
})

const playLevel = (level) => {
    if (!level.unlocked) return
    router.visit(`/student/english/games/dictation/play/${level.number}`)
}

const gameModes = [
    { icon: '🎧', name: "Audio tinglash", desc: "TTS orqali so'z va gaplarni eshiting" },
    { icon: '✍️', name: "Yozish mashqi", desc: "Eshitganingizni aniq yozing" },
    { icon: '🐢', name: "Sekin rejim", desc: "Audiolarni sekinroq tinglash" },
    { icon: '💡', name: "Maslahatlar", desc: "Qiynalsangiz, yordam oling" },
]

const tips = [
    { icon: '🎧', text: "Diqqat bilan tinglang - har bir tovush muhim!" },
    { icon: '✍️', text: "Tinish belgilari va katta harflarga e'tibor bering" },
    { icon: '🔄', text: "Qayta tinglash imkoniyatidan foydalaning" },
]

const getLevelColor = (cefrLevel) => {
    const colors = {
        'A1': '#10b981',
        'A2': '#3b82f6',
        'B1': '#8b5cf6',
        'B2': '#f97316',
        'C1': '#ec4899',
        'C2': '#ef4444'
    }
    return colors[cefrLevel] || '#6366f1'
}

const adjustColor = (hex, amount) => {
    if (!hex) return '#6366f1'
    const num = parseInt(hex.replace('#', ''), 16)
    const r = Math.max(0, Math.min(255, (num >> 16) + amount))
    const g = Math.max(0, Math.min(255, ((num >> 8) & 0x00FF) + amount))
    const b = Math.max(0, Math.min(255, (num & 0x0000FF) + amount))
    return `#${(1 << 24 | r << 16 | g << 8 | b).toString(16).slice(1)}`
}
</script>

<template>
    <Head title="Diktant - Dictation" />

    <StudentLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900 -m-6 p-6 font-sans">

            <!-- Premium Hero Section -->
            <div class="relative overflow-hidden rounded-[2rem] mb-8">
                <!-- Animated Gradient Background -->
                <div class="absolute inset-0 bg-gradient-to-br from-violet-600 via-purple-600 to-indigo-600">
                    <!-- Animated Orbs -->
                    <div class="absolute top-0 left-1/4 w-72 h-72 bg-pink-500/30 rounded-full blur-3xl animate-pulse" />
                    <div class="absolute bottom-0 right-1/4 w-64 h-64 bg-blue-500/30 rounded-full blur-3xl animate-pulse"
                        style="animation-delay: 1s" />
                    <div class="absolute top-1/2 right-0 w-48 h-48 bg-cyan-500/20 rounded-full blur-3xl animate-pulse"
                        style="animation-delay: 2s" />

                    <!-- Grid Pattern -->
                    <div class="absolute inset-0 opacity-10"
                        style="background-image: url(&quot;data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E&quot;)" />
                </div>

                <!-- Floating Animated Emojis -->
                <div class="absolute inset-0 pointer-events-none overflow-hidden">
                    <span class="floating-emoji" style="left: 5%; top: 20%; animation-delay: 0s;">🎧</span>
                    <span class="floating-emoji" style="left: 12%; top: 60%; animation-delay: 1.2s;">✍️</span>
                    <span class="floating-emoji" style="left: 25%; top: 35%; animation-delay: 2.4s;">🔊</span>
                    <span class="floating-emoji" style="right: 25%; top: 25%; animation-delay: 0.6s;">📝</span>
                    <span class="floating-emoji" style="right: 12%; top: 55%; animation-delay: 1.8s;">🎵</span>
                    <span class="floating-emoji" style="right: 5%; top: 35%; animation-delay: 3s;">⭐</span>
                    <span class="floating-emoji" style="left: 40%; top: 70%; animation-delay: 0.3s;">🎯</span>
                    <span class="floating-emoji" style="right: 40%; top: 15%; animation-delay: 2.1s;">🏆</span>
                </div>

                <div class="relative">
                    <!-- Navigation -->
                    <div class="max-w-[1600px] mx-auto px-6 md:px-10 pt-6">
                        <Link href="/student/english/games"
                            class="inline-flex items-center gap-3 text-white/80 hover:text-white transition group">
                            <div
                                class="w-11 h-11 rounded-xl bg-white/15 backdrop-blur-sm flex items-center justify-center group-hover:bg-white/25 transition border border-white/10">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 19l-7-7 7-7" />
                                </svg>
                            </div>
                            <span class="font-semibold text-lg">Orqaga</span>
                        </Link>
                    </div>

                    <!-- Hero Content -->
                    <div class="max-w-[1600px] mx-auto px-6 md:px-10 pt-6 pb-8">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8">
                            <!-- Left: Title & Description -->
                            <div class="flex-1 text-center lg:text-left">
                                <!-- Badge -->
                                <div
                                    class="inline-flex items-center gap-2 bg-white/15 backdrop-blur-sm rounded-full px-5 py-2 mb-5 border border-white/20">
                                    <span class="relative flex h-2.5 w-2.5">
                                        <span
                                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-400"></span>
                                    </span>
                                    <span class="text-white text-sm font-medium">🎧 Audio O'yin</span>
                                </div>

                                <!-- Main Title -->
                                <div class="flex items-center justify-center lg:justify-start gap-5 mb-4">
                                    <div
                                        class="w-16 h-16 md:w-20 md:h-20 bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center shadow-2xl shadow-purple-500/30 border border-white/20">
                                        <span class="text-4xl md:text-5xl">🎧</span>
                                    </div>
                                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-black text-white tracking-tight drop-shadow-lg">
                                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-pink-300 to-violet-300">Diktant</span>
                                    </h1>
                                </div>

                                <p class="text-lg md:text-xl text-white/90 mb-6 max-w-2xl mx-auto lg:mx-0 leading-relaxed">
                                    Ingliz tilida <span class="text-pink-300 font-bold">eshitish ko'nikmasini</span> rivojlantiring!
                                    Tinglang va <span class="text-yellow-300 font-bold">aniq yozing</span>.
                                </p>

                                <!-- Features Badges -->
                                <div class="flex flex-wrap justify-center lg:justify-start gap-3">
                                    <span
                                        class="bg-gradient-to-r from-purple-500/30 to-pink-500/30 backdrop-blur border border-white/20 text-white px-4 py-2 rounded-full text-sm font-medium flex items-center gap-2">
                                        <span>🔊</span> TTS Audio
                                    </span>
                                    <span
                                        class="bg-gradient-to-r from-blue-500/30 to-cyan-500/30 backdrop-blur border border-white/20 text-white px-4 py-2 rounded-full text-sm font-medium flex items-center gap-2">
                                        <span>📊</span> 10 daraja
                                    </span>
                                    <span
                                        class="bg-gradient-to-r from-emerald-500/30 to-teal-500/30 backdrop-blur border border-white/20 text-white px-4 py-2 rounded-full text-sm font-medium flex items-center gap-2">
                                        <span>📈</span> A1 → C2
                                    </span>
                                    <span
                                        class="bg-gradient-to-r from-orange-500/30 to-yellow-500/30 backdrop-blur border border-white/20 text-white px-4 py-2 rounded-full text-sm font-medium flex items-center gap-2">
                                        <span>🐢</span> Sekin rejim
                                    </span>
                                </div>
                            </div>

                            <!-- Right: Stats & Tutorial -->
                            <div class="flex flex-col gap-4">
                                <!-- Stats Cards Row -->
                                <div class="flex flex-wrap justify-center lg:justify-end gap-3">
                                    <div
                                        class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl px-5 py-4 hover:bg-white/15 transition">
                                        <div class="flex items-center gap-4">
                                            <div
                                                class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-xl flex items-center justify-center shadow-lg">
                                                <span class="text-2xl">⭐</span>
                                            </div>
                                            <div class="text-left">
                                                <p class="text-2xl md:text-3xl font-black text-white leading-none">{{ totalStars }}</p>
                                                <p class="text-white/60 text-sm">{{ maxStars }} dan</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div
                                        class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl px-5 py-4 hover:bg-white/15 transition">
                                        <div class="flex items-center gap-4">
                                            <div
                                                class="w-12 h-12 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-xl flex items-center justify-center shadow-lg">
                                                <span class="text-2xl">✅</span>
                                            </div>
                                            <div class="text-left">
                                                <p class="text-2xl md:text-3xl font-black text-white leading-none">{{ completedLevels }}</p>
                                                <p class="text-white/60 text-sm">{{ levels.length }} leveldan</p>
                                            </div>
                                        </div>
                                    </div>
                                    <button @click="showTutorial = true"
                                        class="bg-white/10 backdrop-blur-md border border-white/20 hover:bg-white/20 rounded-2xl px-5 py-4 transition group">
                                        <div class="flex items-center gap-4">
                                            <div
                                                class="w-12 h-12 bg-gradient-to-br from-purple-400 to-pink-500 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition">
                                                <span class="text-2xl">💡</span>
                                            </div>
                                            <span class="text-white font-bold text-base">Qanday<br class="hidden md:block"> o'ynaladi?</span>
                                        </div>
                                    </button>
                                </div>

                                <!-- Game Modes Preview -->
                                <div class="hidden lg:grid grid-cols-2 gap-2">
                                    <div v-for="(mode, index) in gameModes" :key="mode.name"
                                        class="group bg-white/10 backdrop-blur-md rounded-xl p-3 border border-white/20 hover:bg-white/15 transition cursor-default">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-lg flex items-center justify-center text-lg shadow-lg"
                                                :class="[
                                                    index === 0 ? 'bg-gradient-to-br from-violet-400 to-violet-600' : '',
                                                    index === 1 ? 'bg-gradient-to-br from-pink-400 to-pink-600' : '',
                                                    index === 2 ? 'bg-gradient-to-br from-cyan-400 to-cyan-600' : '',
                                                    index === 3 ? 'bg-gradient-to-br from-amber-400 to-amber-600' : '',
                                                ]">
                                                {{ mode.icon }}
                                            </div>
                                            <div>
                                                <p class="text-white font-bold text-xs">{{ mode.name }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Hero Illustration -->
                            <div class="hidden xl:block relative w-72 h-72 flex-shrink-0 animate-float">
                                <div
                                    class="absolute inset-0 bg-gradient-to-tr from-purple-400/30 to-pink-400/30 blur-2xl rounded-full">
                                </div>
                                <div
                                    class="relative z-10 bg-white/10 backdrop-blur-xl border border-white/20 p-5 rounded-3xl shadow-xl rotate-6 transform hover:rotate-0 transition-all duration-500">
                                    <div class="flex gap-2 mb-3">
                                        <div class="w-3 h-3 rounded-full bg-red-400"></div>
                                        <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                                        <div class="w-3 h-3 rounded-full bg-green-400"></div>
                                    </div>
                                    <!-- Audio Visualization -->
                                    <div class="flex items-end justify-center gap-1.5 h-24 mb-3">
                                        <div class="w-3 bg-gradient-to-t from-violet-500 to-pink-500 rounded-full animate-wave" style="height: 30%; animation-delay: 0s;"></div>
                                        <div class="w-3 bg-gradient-to-t from-violet-500 to-pink-500 rounded-full animate-wave" style="height: 60%; animation-delay: 0.1s;"></div>
                                        <div class="w-3 bg-gradient-to-t from-violet-500 to-pink-500 rounded-full animate-wave" style="height: 90%; animation-delay: 0.2s;"></div>
                                        <div class="w-3 bg-gradient-to-t from-violet-500 to-pink-500 rounded-full animate-wave" style="height: 70%; animation-delay: 0.3s;"></div>
                                        <div class="w-3 bg-gradient-to-t from-violet-500 to-pink-500 rounded-full animate-wave" style="height: 50%; animation-delay: 0.4s;"></div>
                                        <div class="w-3 bg-gradient-to-t from-violet-500 to-pink-500 rounded-full animate-wave" style="height: 80%; animation-delay: 0.5s;"></div>
                                        <div class="w-3 bg-gradient-to-t from-violet-500 to-pink-500 rounded-full animate-wave" style="height: 40%; animation-delay: 0.6s;"></div>
                                    </div>
                                    <div class="text-center">
                                        <span class="text-4xl">🎧</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Levels Section -->
            <div class="max-w-[1600px] mx-auto px-2 md:px-4">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-12 bg-gradient-to-br from-violet-500 to-purple-500 rounded-2xl flex items-center justify-center shadow-lg">
                            <span class="text-2xl">📚</span>
                        </div>
                        <div>
                            <h2 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white">Barcha Darajalar</h2>
                            <p class="text-gray-500 dark:text-gray-400 text-sm">A1 dan C2 gacha - so'z, ibora va gaplar</p>
                        </div>
                    </div>
                    <div class="hidden md:flex items-center gap-3 text-sm">
                        <span
                            class="flex items-center gap-2 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 px-3 py-1.5 rounded-full font-medium">
                            <span class="w-2.5 h-2.5 bg-green-500 rounded-full" /> Ochiq
                        </span>
                        <span
                            class="flex items-center gap-2 bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 px-3 py-1.5 rounded-full font-medium">
                            <span class="w-2.5 h-2.5 bg-gray-400 rounded-full" /> Qulflangan
                        </span>
                    </div>
                </div>

                <!-- Levels Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                    <div v-for="level in levels" :key="level.number" @click="playLevel(level)"
                        class="group relative bg-white dark:bg-gray-800 rounded-2xl overflow-hidden transition-all duration-300 cursor-pointer shadow-md hover:shadow-2xl border border-gray-100 dark:border-gray-700"
                        :class="[level.unlocked ? 'hover:scale-[1.03] hover:-translate-y-2' : 'opacity-60 cursor-not-allowed grayscale-[0.3]']">

                        <!-- Card Header -->
                        <div class="relative h-28 flex items-center px-4" :style="{
                            background: level.unlocked
                                ? `linear-gradient(135deg, ${getLevelColor(level.cefr_level)}, ${adjustColor(getLevelColor(level.cefr_level), -25)})`
                                : 'linear-gradient(135deg, #9ca3af, #6b7280)'
                        }">
                            <!-- Decorative Elements -->
                            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/20 rounded-full blur-xl"></div>
                            <div class="absolute -left-4 -bottom-4 w-16 h-16 bg-black/10 rounded-full blur-xl"></div>

                            <div class="flex items-center gap-3 flex-1 relative z-10">
                                <div
                                    class="w-14 h-14 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center shadow-lg border border-white/10">
                                    <span class="text-3xl">{{ level.completed && level.stars === 3 ? '🏆' : (level.completed ? '✅' : '🎧') }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex gap-2 mb-1">
                                        <span
                                            class="text-[10px] bg-white/30 text-white px-2 py-0.5 rounded-full font-bold uppercase">
                                            {{ level.cefr_level }}
                                        </span>
                                        <span
                                            class="text-[10px] bg-white/20 text-white px-2 py-0.5 rounded-full font-medium">
                                            Level {{ level.number }}
                                        </span>
                                    </div>
                                    <h3 class="font-bold text-white text-lg truncate">{{ level.name_uz || level.name }}</h3>
                                </div>
                            </div>

                            <div class="flex flex-col items-center ml-2 relative z-10">
                                <div class="flex gap-0.5">
                                    <span v-for="i in 3" :key="i" class="text-lg"
                                        :class="i <= (level.stars || 0) ? 'text-yellow-300 drop-shadow' : 'text-white/30'">★</span>
                                </div>
                            </div>

                            <!-- Locked Overlay -->
                            <div v-if="!level.unlocked"
                                class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm flex flex-col items-center justify-center">
                                <span class="text-4xl mb-1">🔒</span>
                                <span class="text-white font-bold text-sm">Oldingi darajani yakunlang</span>
                            </div>

                            <!-- Completed Badge -->
                            <div v-if="level.completed"
                                class="absolute top-2 right-2 bg-green-500 text-white text-[10px] font-bold px-2.5 py-1 rounded-full flex items-center gap-1 shadow-lg">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd" />
                                </svg>
                                Tugatildi
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="p-4">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-3 line-clamp-1">
                                {{ level.description_uz || level.description }}
                            </p>

                            <!-- Content Type -->
                            <div class="flex flex-wrap gap-1.5 mb-3">
                                <span class="text-[10px] bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-400 px-2 py-0.5 rounded-full font-medium">
                                    {{ level.content_type === 'words' ? "So'zlar" : level.content_type === 'phrases' ? 'Iboralar' : level.content_type === 'sentences' ? 'Gaplar' : 'Paragraflar' }}
                                </span>
                                <span v-if="level.best_score" class="text-[10px] bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 px-2 py-0.5 rounded-full font-medium">
                                    Eng yaxshi: {{ level.best_score }}
                                </span>
                            </div>

                            <!-- Footer -->
                            <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-700">
                                <div class="flex gap-3 text-xs text-gray-500 dark:text-gray-400">
                                    <span class="flex items-center gap-1 font-medium">
                                        <span class="text-base">⚡</span> {{ level.xp_reward || 50 }} XP
                                    </span>
                                    <span class="flex items-center gap-1 font-medium">
                                        <span class="text-base">🪙</span> {{ level.coin_reward || 5 }}
                                    </span>
                                </div>
                                <span v-if="level.unlocked"
                                    class="text-xs font-bold flex items-center gap-1 group-hover:gap-2 transition-all px-3 py-1.5 rounded-lg"
                                    :style="{ color: getLevelColor(level.cefr_level), background: `${getLevelColor(level.cefr_level)}15` }">
                                    O'ynash
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7" />
                                    </svg>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tips Section -->
            <div class="max-w-[1600px] mx-auto px-2 md:px-4 mt-8">
                <div class="bg-gradient-to-r from-violet-50 to-purple-50 dark:from-violet-900/20 dark:to-purple-900/20 rounded-2xl p-5 border border-violet-200 dark:border-violet-800">
                    <h3 class="font-bold text-violet-900 dark:text-violet-300 mb-3 flex items-center gap-2">
                        <span>💡</span> Foydali Maslahatlar
                    </h3>
                    <div class="grid md:grid-cols-3 gap-3">
                        <div v-for="tip in tips" :key="tip.text"
                            class="flex items-start gap-3 bg-white/50 dark:bg-white/5 rounded-xl p-3">
                            <span class="text-xl">{{ tip.icon }}</span>
                            <p class="text-gray-700 dark:text-gray-300 text-sm">{{ tip.text }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tutorial Modal -->
            <Teleport to="body">
                <div v-if="showTutorial"
                    class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 p-4"
                    @click.self="showTutorial = false">
                    <div class="bg-white dark:bg-gray-800 rounded-3xl max-w-lg w-full shadow-2xl overflow-hidden animate-scale-in max-h-[90vh] overflow-y-auto">
                        <!-- Modal Header -->
                        <div class="relative bg-gradient-to-br from-violet-600 via-purple-600 to-indigo-600 p-6 text-center">
                            <button @click="showTutorial = false"
                                class="absolute top-4 right-4 w-10 h-10 bg-white/20 hover:bg-white/30 rounded-full flex items-center justify-center text-white transition">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                            <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-3">
                                <span class="text-4xl">🎧</span>
                            </div>
                            <h3 class="text-2xl font-black text-white mb-1">Diktant</h3>
                            <p class="text-white/70 text-sm">Qanday o'ynash kerak?</p>
                        </div>

                        <!-- Modal Content -->
                        <div class="p-5 space-y-4">
                            <!-- Game Goal -->
                            <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-4 border border-green-200 dark:border-green-800">
                                <h4 class="font-bold text-green-800 dark:text-green-400 mb-1 flex items-center gap-2 text-sm">
                                    <span>🎯</span> O'yin Maqsadi
                                </h4>
                                <p class="text-green-700 dark:text-green-300 text-sm">
                                    <strong>Audio yozuvini diqqat bilan tinglang</strong> va eshitganingizni to'g'ri yozing. Bu eshitish va yozish ko'nikmalaringizni rivojlantiradi!
                                </p>
                            </div>

                            <!-- How to Play Steps -->
                            <div>
                                <h4 class="font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2 text-sm">
                                    <span>📋</span> O'ynash Qadamlari
                                </h4>
                                <div class="space-y-2">
                                    <div class="flex items-center gap-3 bg-violet-50 dark:bg-violet-900/20 rounded-lg p-2.5">
                                        <div class="w-6 h-6 bg-violet-500 text-white text-xs font-bold rounded flex items-center justify-center flex-shrink-0">1</div>
                                        <p class="text-gray-800 dark:text-gray-200 text-sm">🔊 tugmasini bosib audiolarni tinglang</p>
                                    </div>
                                    <div class="flex items-center gap-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg p-2.5">
                                        <div class="w-6 h-6 bg-purple-500 text-white text-xs font-bold rounded flex items-center justify-center flex-shrink-0">2</div>
                                        <p class="text-gray-800 dark:text-gray-200 text-sm">Eshitganingizni matn maydoniga yozing</p>
                                    </div>
                                    <div class="flex items-center gap-3 bg-pink-50 dark:bg-pink-900/20 rounded-lg p-2.5">
                                        <div class="w-6 h-6 bg-pink-500 text-white text-xs font-bold rounded flex items-center justify-center flex-shrink-0">3</div>
                                        <p class="text-gray-800 dark:text-gray-200 text-sm">"Tekshirish" tugmasini bosing</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Game Modes -->
                            <div>
                                <h4 class="font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2 text-sm">
                                    <span>🎮</span> Xususiyatlar
                                </h4>
                                <div class="grid grid-cols-2 gap-2">
                                    <div v-for="mode in gameModes" :key="mode.name"
                                        class="flex items-center gap-2 bg-gray-50 dark:bg-gray-700 rounded-lg p-2.5">
                                        <span class="text-xl">{{ mode.icon }}</span>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white text-xs">{{ mode.name }}</p>
                                            <p class="text-gray-500 dark:text-gray-400 text-[10px]">{{ mode.desc }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Scoring -->
                            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl p-4 border border-yellow-200 dark:border-yellow-800">
                                <h4 class="font-bold text-yellow-800 dark:text-yellow-400 mb-2 flex items-center gap-2 text-sm">
                                    <span>⭐</span> Ball Tizimi
                                </h4>
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div class="flex justify-between items-center bg-white/50 dark:bg-black/20 rounded p-2">
                                        <span class="text-gray-700 dark:text-gray-300">90%+ aniqlik:</span>
                                        <span class="font-bold text-green-600">3 ⭐</span>
                                    </div>
                                    <div class="flex justify-between items-center bg-white/50 dark:bg-black/20 rounded p-2">
                                        <span class="text-gray-700 dark:text-gray-300">75%+ aniqlik:</span>
                                        <span class="font-bold text-blue-600">2 ⭐</span>
                                    </div>
                                    <div class="flex justify-between items-center bg-white/50 dark:bg-black/20 rounded p-2">
                                        <span class="text-gray-700 dark:text-gray-300">50%+ aniqlik:</span>
                                        <span class="font-bold text-amber-600">1 ⭐</span>
                                    </div>
                                    <div class="flex justify-between items-center bg-white/50 dark:bg-black/20 rounded p-2">
                                        <span class="text-gray-700 dark:text-gray-300">Streak bonus:</span>
                                        <span class="font-bold text-purple-600">+10%</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="p-5 bg-gray-50 dark:bg-gray-900">
                            <button @click="showTutorial = false"
                                class="w-full py-4 bg-gradient-to-r from-violet-600 to-purple-600 text-white font-bold rounded-xl hover:opacity-90 transition text-lg shadow-lg">
                                Tushundim! 🎧
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>

        </div>
    </StudentLayout>
</template>

<style scoped>
@keyframes float {
    0%, 100% { transform: translateY(0px) rotate(6deg); }
    50% { transform: translateY(-10px) rotate(3deg); }
}

.animate-float {
    animation: float 6s ease-in-out infinite;
}

@keyframes scale-in {
    from { transform: scale(0.9); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

.animate-scale-in {
    animation: scale-in 0.3s ease-out;
}

@keyframes emoji-float {
    0%, 100% { transform: translateY(0) rotate(0deg); opacity: 0.5; }
    50% { transform: translateY(-15px) rotate(8deg); opacity: 0.8; }
}

.floating-emoji {
    position: absolute;
    font-size: 1.75rem;
    animation: emoji-float 5s ease-in-out infinite;
    opacity: 0.5;
    filter: drop-shadow(0 0 8px rgba(255, 255, 255, 0.3));
}

@keyframes wave {
    0%, 100% { height: 30%; }
    50% { height: 90%; }
}

.animate-wave {
    animation: wave 1s ease-in-out infinite;
}
</style>
