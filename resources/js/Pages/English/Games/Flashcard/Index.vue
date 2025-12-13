<template>
    <StudentLayout :headerStars="totalStars">
        <div class="min-h-screen bg-gray-50">
            <!-- Premium Marketing Hero -->
            <div class="relative overflow-hidden mx-6 mt-6 rounded-[2rem]">
                <!-- Animated Gradient Background -->
                <div class="absolute inset-0 bg-gradient-to-br from-violet-600 via-purple-600 to-fuchsia-600">
                    <!-- Animated Orbs -->
                    <div class="absolute top-0 left-1/4 w-72 h-72 bg-pink-500/30 rounded-full blur-3xl animate-pulse" />
                    <div class="absolute bottom-0 right-1/4 w-64 h-64 bg-blue-500/30 rounded-full blur-3xl animate-pulse"
                        style="animation-delay: 1s" />
                    <div class="absolute top-1/2 left-0 w-48 h-48 bg-cyan-500/20 rounded-full blur-3xl animate-pulse"
                        style="animation-delay: 2s" />

                    <!-- Grid Pattern -->
                    <div class="absolute inset-0 opacity-10"
                        style="background-image: url(&quot;data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E&quot;)" />
                </div>

                <!-- Floating Animated Emojis -->
                <div class="absolute inset-0 pointer-events-none overflow-hidden">
                    <span class="floating-emoji" style="left: 5%; top: 25%; animation-delay: 0s;">📚</span>
                    <span class="floating-emoji" style="left: 12%; top: 65%; animation-delay: 1.2s;">✨</span>
                    <span class="floating-emoji" style="left: 22%; top: 35%; animation-delay: 2.4s;">🎯</span>
                    <span class="floating-emoji" style="right: 22%; top: 30%; animation-delay: 0.6s;">🧠</span>
                    <span class="floating-emoji" style="right: 12%; top: 60%; animation-delay: 1.8s;">💡</span>
                    <span class="floating-emoji" style="right: 5%; top: 40%; animation-delay: 3s;">🔥</span>
                    <span class="floating-emoji" style="left: 40%; top: 75%; animation-delay: 0.3s;">⭐</span>
                    <span class="floating-emoji" style="right: 40%; top: 15%; animation-delay: 2.1s;">🎓</span>
                    <span class="floating-emoji" style="left: 8%; top: 45%; animation-delay: 1.5s;">🚀</span>
                    <span class="floating-emoji" style="right: 8%; top: 20%; animation-delay: 2.7s;">💎</span>
                </div>

                <div class="relative">
                    <!-- Navigation -->
                    <div class="max-w-[1600px] mx-auto px-10 pt-6">
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
                    <div class="max-w-[1600px] mx-auto px-10 pt-6 pb-8 text-center">
                        <!-- Badge -->
                        <div
                            class="inline-flex items-center gap-2 bg-white/15 backdrop-blur-sm rounded-full px-5 py-2 mb-5 border border-white/20">
                            <span class="relative flex h-2.5 w-2.5">
                                <span
                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-400"></span>
                            </span>
                            <span class="text-white text-sm font-medium">🏆 Ingliz tilini o'rganish uchun #1 usul</span>
                        </div>

                        <!-- Main Title -->
                        <div class="flex items-center justify-center gap-5 mb-4">
                            <div
                                class="w-18 h-18 bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center shadow-2xl shadow-purple-500/30 border border-white/20 p-4">
                                <span class="text-5xl">🃏</span>
                            </div>
                            <h1 class="text-5xl md:text-6xl font-black text-white tracking-tight drop-shadow-lg">
                                Flashcard Flip
                            </h1>
                        </div>

                        <p class="text-xl text-white/90 mb-6 max-w-2xl mx-auto leading-relaxed">
                            <span class="text-white font-bold">500+</span> so'zni kartochkalar orqali o'rganing va
                            <span
                                class="bg-gradient-to-r from-yellow-300 to-orange-300 bg-clip-text text-transparent font-bold">xotirangizni</span>
                            mustahkamlang
                        </p>

                        <!-- Stats Cards -->
                        <div class="flex flex-wrap justify-center gap-4 mb-6">
                            <div
                                class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl px-6 py-4 hover:bg-white/15 transition">
                                <div class="flex items-center gap-4">
                                    <div
                                        class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-xl flex items-center justify-center shadow-lg">
                                        <span class="text-2xl">⭐</span>
                                    </div>
                                    <div class="text-left">
                                        <p class="text-3xl font-black text-white leading-none">{{ totalStars }}</p>
                                        <p class="text-white/60 text-sm">{{ maxStars }} dan</p>
                                    </div>
                                </div>
                            </div>
                            <div
                                class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl px-6 py-4 hover:bg-white/15 transition">
                                <div class="flex items-center gap-4">
                                    <div
                                        class="w-12 h-12 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-xl flex items-center justify-center shadow-lg">
                                        <span class="text-2xl">🎯</span>
                                    </div>
                                    <div class="text-left">
                                        <p class="text-3xl font-black text-white leading-none">{{ completedLevels }}</p>
                                        <p class="text-white/60 text-sm">{{ levels.length }} leveldan</p>
                                    </div>
                                </div>
                            </div>
                            <button @click="showTutorial = true"
                                class="bg-white/10 backdrop-blur-md border border-white/20 hover:bg-white/20 rounded-2xl px-6 py-4 transition group">
                                <div class="flex items-center gap-4">
                                    <div
                                        class="w-12 h-12 bg-gradient-to-br from-blue-400 to-indigo-500 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition">
                                        <span class="text-2xl">💡</span>
                                    </div>
                                    <span class="text-white font-bold text-lg">Qanday o'ynaladi?</span>
                                </div>
                            </button>
                        </div>

                        <!-- Features Badges -->
                        <div class="flex flex-wrap justify-center gap-3">
                            <span
                                class="bg-gradient-to-r from-purple-500/30 to-pink-500/30 backdrop-blur border border-white/20 text-white px-4 py-2 rounded-full text-sm font-medium flex items-center gap-2">
                                <span>🧠</span> Spaced Repetition
                            </span>
                            <span
                                class="bg-gradient-to-r from-blue-500/30 to-cyan-500/30 backdrop-blur border border-white/20 text-white px-4 py-2 rounded-full text-sm font-medium flex items-center gap-2">
                                <span>🔊</span> Audio talaffuz
                            </span>
                            <span
                                class="bg-gradient-to-r from-emerald-500/30 to-teal-500/30 backdrop-blur border border-white/20 text-white px-4 py-2 rounded-full text-sm font-medium flex items-center gap-2">
                                <span>📈</span> A1 → C2 darajalar
                            </span>
                            <span
                                class="bg-gradient-to-r from-orange-500/30 to-yellow-500/30 backdrop-blur border border-white/20 text-white px-4 py-2 rounded-full text-sm font-medium flex items-center gap-2">
                                <span>🎮</span> Gamification
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Levels Section -->
            <div class="max-w-[1600px] mx-auto px-8 py-10">
                <div class="flex items-center justify-between mb-8">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-14 h-14 bg-gradient-to-br from-purple-500 to-pink-500 rounded-2xl flex items-center justify-center shadow-lg">
                            <span class="text-3xl">📚</span>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Levellarni tanlang</h2>
                            <p class="text-gray-500">A1 dan C2 gacha - har bir daraja uchun maxsus kartochkalar</p>
                        </div>
                    </div>
                    <div class="hidden md:flex items-center gap-3 text-sm">
                        <span
                            class="flex items-center gap-2 bg-green-100 text-green-700 px-3 py-1.5 rounded-full font-medium">
                            <span class="w-2.5 h-2.5 bg-green-500 rounded-full" /> Ochiq
                        </span>
                        <span
                            class="flex items-center gap-2 bg-gray-100 text-gray-500 px-3 py-1.5 rounded-full font-medium">
                            <span class="w-2.5 h-2.5 bg-gray-400 rounded-full" /> Qulflangan
                        </span>
                    </div>
                </div>

                <!-- 4 Column Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <div v-for="level in levels" :key="level.number" @click="startLevel(level)"
                        class="group relative bg-white rounded-2xl overflow-hidden transition-all duration-300 cursor-pointer shadow-md hover:shadow-2xl border border-gray-100"
                        :class="[level.is_unlocked ? 'hover:scale-[1.03] hover:-translate-y-2' : 'opacity-60 cursor-not-allowed']">

                        <!-- Card Header -->
                        <div class="relative h-32 flex items-center px-5" :style="{
                            background: level.is_unlocked
                                ? `linear-gradient(135deg, ${level.accent_color}, ${adjustColor(level.accent_color, -25)})`
                                : 'linear-gradient(135deg, #9ca3af, #6b7280)'
                        }">

                            <div class="flex items-center gap-4 flex-1">
                                <div
                                    class="w-16 h-16 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center shadow-lg">
                                    <span class="text-4xl">{{ level.icon }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex gap-2 mb-1.5">
                                        <span
                                            class="text-xs bg-white/30 text-white px-2 py-0.5 rounded-full font-bold uppercase">
                                            {{ level.cefr_level }}
                                        </span>
                                        <span
                                            class="text-xs bg-white/20 text-white px-2 py-0.5 rounded-full font-medium">
                                            Level {{ level.number }}
                                        </span>
                                    </div>
                                    <h3 class="font-bold text-white text-xl truncate">{{ level.name_uz }}</h3>
                                </div>
                            </div>

                            <div class="flex flex-col items-center ml-2">
                                <div class="flex gap-0.5">
                                    <span v-for="i in 3" :key="i" class="text-xl"
                                        :class="i <= level.stars_earned ? 'text-yellow-300 drop-shadow' : 'text-white/30'">★</span>
                                </div>
                            </div>

                            <div v-if="!level.is_unlocked"
                                class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm flex flex-col items-center justify-center">
                                <span class="text-5xl mb-2">🔒</span>
                                <span class="text-white font-bold text-lg">{{ level.required_stars }} ⭐ kerak</span>
                            </div>

                            <div v-if="level.is_completed"
                                class="absolute top-3 right-3 bg-green-500 text-white text-xs font-bold px-3 py-1 rounded-full flex items-center gap-1 shadow-lg">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd" />
                                </svg>
                                Tugatildi
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="p-5">
                            <div v-if="level.is_unlocked" class="mb-4">
                                <div class="flex justify-between text-sm text-gray-500 mb-2">
                                    <span>Progress</span>
                                    <span class="font-bold text-gray-700">{{ Math.round((level.cards_learned /
                                        level.cards_to_master) * 100) }}%</span>
                                </div>
                                <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500"
                                        :style="{ width: `${Math.min(100, (level.cards_learned / level.cards_to_master) * 100)}%`, background: `linear-gradient(90deg, ${level.accent_color}, ${adjustColor(level.accent_color, 20)})` }" />
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2 mb-4">
                                <span v-for="cat in level.categories?.slice(0, 3)" :key="cat"
                                    class="text-xs bg-gray-100 text-gray-600 px-2.5 py-1 rounded-lg font-medium">{{
                                    formatCategory(cat) }}</span>
                                <span v-if="level.categories?.length > 3" class="text-xs text-gray-400 px-2 py-1">+{{
                                    level.categories.length - 3 }}</span>
                            </div>

                            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                                <div class="flex gap-4 text-sm text-gray-500">
                                    <span class="flex items-center gap-1.5 font-medium">
                                        <span class="text-lg">⚡</span> {{ level.xp_reward }} XP
                                    </span>
                                    <span class="flex items-center gap-1.5 font-medium">
                                        <span class="text-lg">🪙</span> {{ level.coin_reward }}
                                    </span>
                                </div>
                                <span v-if="level.is_unlocked"
                                    class="text-sm font-bold flex items-center gap-1 group-hover:gap-2 transition-all px-3 py-1.5 rounded-lg"
                                    :style="{ color: level.accent_color, background: `${level.accent_color}15` }">
                                    O'ynash
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7" />
                                    </svg>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tutorial Modal -->
            <Teleport to="body">
                <div v-if="showTutorial"
                    class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 p-4"
                    @click.self="showTutorial = false">
                    <div class="bg-white rounded-3xl max-w-lg w-full shadow-2xl overflow-hidden animate-scale-in">
                        <div
                            class="relative bg-gradient-to-br from-violet-600 via-purple-600 to-fuchsia-600 p-8 text-center">
                            <button @click="showTutorial = false"
                                class="absolute top-4 right-4 w-10 h-10 bg-white/20 hover:bg-white/30 rounded-full flex items-center justify-center text-white transition">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                            <div
                                class="w-20 h-20 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                <span class="text-5xl">🃏</span>
                            </div>
                            <h3 class="text-2xl font-black text-white mb-2">Qanday o'ynash kerak?</h3>
                            <p class="text-white/70">3 ta oddiy qadam!</p>
                        </div>

                        <div class="p-6 space-y-4">
                            <div class="flex items-center gap-4 p-4 bg-blue-50 rounded-2xl">
                                <div
                                    class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center flex-shrink-0 text-white font-bold text-lg">
                                    1</div>
                                <div>
                                    <p class="font-bold text-gray-800">So'zni ko'ring</p>
                                    <p class="text-sm text-gray-500">Inglizcha so'z ko'rsatiladi</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 p-4 bg-purple-50 rounded-2xl">
                                <div
                                    class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center flex-shrink-0 text-white font-bold text-lg">
                                    2</div>
                                <div>
                                    <p class="font-bold text-gray-800">Kartani aylantiring</p>
                                    <p class="text-sm text-gray-500">Tarjimasini ko'ring</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 p-4 bg-green-50 rounded-2xl">
                                <div
                                    class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center flex-shrink-0 text-white font-bold text-lg">
                                    3</div>
                                <div>
                                    <p class="font-bold text-gray-800">Baholang</p>
                                    <p class="text-sm text-gray-500">Qanchalik bilganingizni tanlang</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-4 gap-2 pt-4 border-t">
                                <div class="text-center p-2 bg-red-50 rounded-xl">
                                    <span class="text-2xl">😣</span>
                                    <p class="text-[10px] font-bold text-red-600 mt-1">Qayta</p>
                                </div>
                                <div class="text-center p-2 bg-orange-50 rounded-xl">
                                    <span class="text-2xl">😕</span>
                                    <p class="text-[10px] font-bold text-orange-600 mt-1">Qiyin</p>
                                </div>
                                <div class="text-center p-2 bg-green-50 rounded-xl">
                                    <span class="text-2xl">🙂</span>
                                    <p class="text-[10px] font-bold text-green-600 mt-1">Yaxshi</p>
                                </div>
                                <div class="text-center p-2 bg-blue-50 rounded-xl">
                                    <span class="text-2xl">😊</span>
                                    <p class="text-[10px] font-bold text-blue-600 mt-1">Oson</p>
                                </div>
                            </div>
                        </div>

                        <div class="p-6 bg-gray-50">
                            <button @click="showTutorial = false"
                                class="w-full py-4 bg-gradient-to-r from-violet-600 to-fuchsia-600 text-white font-bold rounded-xl hover:opacity-90 transition text-lg">
                                Tushundim! 🚀
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>
        </div>
    </StudentLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

const props = defineProps({
    levels: Array,
    totalStars: Number,
    maxStars: Number,
})

const showTutorial = ref(false)

const completedLevels = computed(() => {
    return props.levels.filter(l => l.is_completed).length
})

const categoryNames = {
    animals: "Hayvonlar",
    colors: "Ranglar",
    numbers: "Raqamlar",
    family: "Oila",
    food: "Ovqat",
    objects: "Narsalar",
    travel: "Sayohat",
    weather: "Ob-havo",
    clothes: "Kiyim",
    verbs: "Fe'llar",
    emotions: "Hissiyotlar",
    opinions: "Fikrlar",
    education: "Ta'lim",
    work: "Ish",
    technology: "Texnologiya",
    business: "Biznes",
    environment: "Atrof-muhit",
    science: "Fan",
    arts: "San'at",
    idioms: "Idiomalar",
    phrasal_verbs: "Phrasal",
    advanced: "Murakkab",
    academic: "Akademik",
    daily_routines: "Kundalik",
}

const formatCategory = (cat) => categoryNames[cat] || cat

const adjustColor = (hex, amount) => {
    if (!hex) return '#6366F1'
    const num = parseInt(hex.replace('#', ''), 16)
    const r = Math.max(0, Math.min(255, (num >> 16) + amount))
    const g = Math.max(0, Math.min(255, ((num >> 8) & 0x00FF) + amount))
    const b = Math.max(0, Math.min(255, (num & 0x0000FF) + amount))
    return `#${(1 << 24 | r << 16 | g << 8 | b).toString(16).slice(1)}`
}

const startLevel = (level) => {
    if (!level.is_unlocked) return
    router.visit(`/student/english/games/flashcard/play/${level.number}`)
}
</script>

<style scoped>
@keyframes scale-in {
    from {
        transform: scale(0.9);
        opacity: 0;
    }

    to {
        transform: scale(1);
        opacity: 1;
    }
}

.animate-scale-in {
    animation: scale-in 0.3s ease-out;
}

@keyframes float {

    0%,
    100% {
        transform: translateY(0) rotate(0deg);
        opacity: 0.5;
    }

    50% {
        transform: translateY(-15px) rotate(8deg);
        opacity: 0.8;
    }
}

.floating-emoji {
    position: absolute;
    font-size: 1.75rem;
    animation: float 5s ease-in-out infinite;
    opacity: 0.5;
    filter: drop-shadow(0 0 8px rgba(255, 255, 255, 0.3));
}
</style>
