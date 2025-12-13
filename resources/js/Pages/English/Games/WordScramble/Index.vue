<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import StudentLayout from '@/Layouts/StudentLayout.vue'

const props = defineProps({
    levels: Array,
    config: Object,
    userStats: Object,
    categories: Array,
    achievements: Array,
    userAchievements: Array,
})

const showTutorial = ref(false)
const showAchievements = ref(false)
const selectedLevel = ref(null)

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

const hints = computed(() => {
    return Object.values(props.config?.hint_system?.hints || {})
})

function getLevelIcon(level) {
    return level.icon || '🌱'
}

function getGradientClass(level) {
    return level.color || 'from-purple-500 to-pink-600'
}

function playLevel(level) {
    if (!level.unlocked) return
    router.visit(`/student/english/games/word-scramble/play/${level.number}`)
}

function getHintIcon(hint) {
    const icons = {
        'definition': '📖',
        'translation': '🌐',
        'first_letter': '🔤',
        'reveal_letter': '✨',
        'image': '🖼️',
    }
    return icons[hint.id] || '💡'
}

function getAchievementIcon(achievement) {
    return achievement.icon || '🏆'
}
</script>

<template>
    <Head title="So'z Jumboq - Word Scramble" />

    <StudentLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900 -m-6 p-6 font-sans">
            <!-- Premium Hero Section -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-purple-600 via-pink-600 to-indigo-600 p-8 mb-8">
                <!-- Animated Background -->
                <div class="absolute inset-0 overflow-hidden">
                    <div class="absolute -top-1/2 -right-1/2 w-full h-full bg-white/5 rounded-full blur-3xl animate-pulse"></div>
                    <div class="absolute -bottom-1/2 -left-1/2 w-full h-full bg-white/5 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
                </div>

                <!-- Header Content -->
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <Link href="/student/english/games" class="inline-flex items-center text-white/80 hover:text-white mb-4 transition-colors">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                O'yinlarga qaytish
                            </Link>
                            <h1 class="text-3xl md:text-4xl font-bold text-white flex items-center gap-3">
                                <span class="text-4xl">🔤</span>
                                {{ config?.game_name_uz || "So'z Jumboq" }}
                            </h1>
                            <p class="text-white/80 mt-2 text-lg">
                                {{ config?.description_uz || "Aralashgan harflarni to'g'ri tartibda joylashtiring" }}
                            </p>
                        </div>

                        <div class="flex gap-3">
                            <button @click="showAchievements = true" class="p-3 bg-white/10 hover:bg-white/20 rounded-xl backdrop-blur-sm transition-all">
                                <span class="text-2xl">🏆</span>
                            </button>
                            <button @click="showTutorial = true" class="p-3 bg-white/10 hover:bg-white/20 rounded-xl backdrop-blur-sm transition-all">
                                <span class="text-2xl">❓</span>
                            </button>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center">
                            <div class="text-3xl font-bold text-white">{{ totalStars }}</div>
                            <div class="text-white/70 text-sm flex items-center justify-center gap-1">
                                <span class="text-yellow-300">⭐</span> Yulduzlar
                            </div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center">
                            <div class="text-3xl font-bold text-white">{{ completedLevels }}/{{ totalLevels }}</div>
                            <div class="text-white/70 text-sm">Bosqichlar</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center">
                            <div class="text-3xl font-bold text-white">{{ userStats?.total_words_completed || 0 }}</div>
                            <div class="text-white/70 text-sm">So'zlar yechilgan</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center">
                            <div class="text-3xl font-bold text-white">{{ userStats?.best_streak || 0 }}</div>
                            <div class="text-white/70 text-sm">🔥 Eng yaxshi streak</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hints Section - Compact Horizontal -->
            <div class="mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-4">
                    <div class="flex items-center justify-between flex-wrap gap-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">💡</span>
                            <span class="font-semibold text-gray-800 dark:text-white">Maslahatlar:</span>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <div v-for="hint in hints" :key="hint.id"
                                 class="group relative flex items-center gap-2 px-3 py-2 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600 rounded-xl hover:shadow-md transition-all cursor-default">
                                <span class="text-xl group-hover:scale-110 transition-transform">{{ getHintIcon(hint) }}</span>
                                <div class="hidden sm:block">
                                    <div class="font-medium text-gray-800 dark:text-white text-sm leading-tight">{{ hint.name_uz }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ hint.cost_coins > 0 ? hint.cost_coins + ' tanga' : 'Bepul' }}</div>
                                </div>
                                <!-- Tooltip for mobile -->
                                <div class="sm:hidden absolute -top-12 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10">
                                    {{ hint.name_uz }}
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
                             'relative rounded-2xl overflow-hidden transition-all duration-300 cursor-pointer group min-h-[220px]',
                             level.unlocked
                                 ? 'hover:scale-105 hover:shadow-2xl'
                                 : 'cursor-not-allowed'
                         ]">
                        <!-- Level Card Background -->
                        <div :class="[
                            'absolute inset-0 bg-gradient-to-br',
                            getGradientClass(level)
                        ]"></div>

                        <!-- Lock Overlay -->
                        <div v-if="!level.unlocked" class="absolute inset-0 bg-black/50 backdrop-blur-[2px] z-20 flex items-center justify-center">
                            <div class="text-center">
                                <div class="w-14 h-14 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-2">
                                    <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <p class="text-white text-sm font-medium">{{ level.unlock_requirement?.stars || 1 }} yulduz kerak</p>
                            </div>
                        </div>

                        <!-- Card Content -->
                        <div class="relative z-10 p-5 h-full flex flex-col">
                            <!-- Header -->
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-3xl drop-shadow-lg">{{ getLevelIcon(level) }}</span>
                                <span class="bg-white/25 backdrop-blur-sm px-3 py-1 rounded-full text-white text-sm font-bold shadow-lg">
                                    {{ level.cefr_level }}
                                </span>
                            </div>

                            <!-- Title & Description -->
                            <h3 class="text-lg font-bold text-white drop-shadow-md mb-1">{{ level.name_uz }}</h3>
                            <p class="text-white/90 text-sm mb-3 drop-shadow-sm line-clamp-2">{{ level.description_uz }}</p>

                            <!-- Spacer -->
                            <div class="flex-1"></div>

                            <!-- Stars -->
                            <div class="flex items-center gap-1 mb-2">
                                <span v-for="i in 3" :key="i"
                                      :class="i <= (level.best_stars || 0) ? 'text-yellow-300 drop-shadow-lg' : 'text-white/40'"
                                      class="text-xl">⭐</span>
                            </div>

                            <!-- Level Info -->
                            <div class="flex items-center justify-between text-white/90 text-sm font-medium">
                                <span class="bg-white/20 px-2 py-1 rounded-lg">{{ level.words_count }} so'z</span>
                                <span class="bg-white/20 px-2 py-1 rounded-lg">{{ level.word_length_min }}-{{ level.word_length_max }} harf</span>
                            </div>

                            <!-- Completed Badge -->
                            <div v-if="level.completed" class="absolute top-3 right-3 z-10">
                                <span class="bg-green-500 text-white text-xs px-2 py-1 rounded-full font-bold shadow-lg">✓</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- How It Works Section -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg mb-8">
                <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                    <span>🎮</span> Qanday O'ynaladi
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                        <span class="text-2xl">1️⃣</span>
                        <div>
                            <div class="font-semibold text-gray-800 dark:text-white">Harflarni ko'ring</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Aralashgan harflar ekranda ko'rsatiladi</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                        <span class="text-2xl">2️⃣</span>
                        <div>
                            <div class="font-semibold text-gray-800 dark:text-white">Sudrab joylashtiring</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Harflarni to'g'ri tartibda joylashtiring</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                        <span class="text-2xl">3️⃣</span>
                        <div>
                            <div class="font-semibold text-gray-800 dark:text-white">Maslahatlar</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Qiyinchilikda maslahatlardan foydalaning</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                        <span class="text-2xl">4️⃣</span>
                        <div>
                            <div class="font-semibold text-gray-800 dark:text-white">Ball yig'ing</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Tezroq javob bering va streak hosil qiling</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tips Section -->
            <div class="bg-gradient-to-r from-purple-500 to-pink-600 rounded-2xl p-6 text-white">
                <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                    <span>💡</span> Maslahatlar
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white/10 rounded-xl p-4">
                        <div class="font-semibold mb-2">⚡ Tezroq javob bering</div>
                        <p class="text-sm text-white/80">Tez javob berish uchun qo'shimcha ball olasiz!</p>
                    </div>
                    <div class="bg-white/10 rounded-xl p-4">
                        <div class="font-semibold mb-2">🔥 Streak saqlang</div>
                        <p class="text-sm text-white/80">Ketma-ket to'g'ri javoblar ball multiplikatorini oshiradi!</p>
                    </div>
                    <div class="bg-white/10 rounded-xl p-4">
                        <div class="font-semibold mb-2">📏 Uzun so'zlar</div>
                        <p class="text-sm text-white/80">Uzun so'zlar uchun ko'proq ball beriladi!</p>
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
                                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-xl flex items-center justify-center text-2xl shrink-0">1</div>
                                <div>
                                    <h3 class="font-semibold text-gray-800 dark:text-white mb-1">Bosqichni tanlang</h3>
                                    <p class="text-gray-600 dark:text-gray-400">Ochiq bo'lgan bosqichlardan birini tanlang. Har bir bosqichda o'ziga xos qiyinlik darajasi bor.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-xl flex items-center justify-center text-2xl shrink-0">2</div>
                                <div>
                                    <h3 class="font-semibold text-gray-800 dark:text-white mb-1">Harflarni joylashtiring</h3>
                                    <p class="text-gray-600 dark:text-gray-400">Aralashgan harflarni bosing yoki sudrab to'g'ri tartibda joylashtiring.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-xl flex items-center justify-center text-2xl shrink-0">3</div>
                                <div>
                                    <h3 class="font-semibold text-gray-800 dark:text-white mb-1">Maslahatlardan foydalaning</h3>
                                    <p class="text-gray-600 dark:text-gray-400">Ta'rif, tarjima, birinchi harf kabi maslahatlar sizga yordam beradi.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-xl flex items-center justify-center text-2xl shrink-0">4</div>
                                <div>
                                    <h3 class="font-semibold text-gray-800 dark:text-white mb-1">Yutuqlarni to'plang</h3>
                                    <p class="text-gray-600 dark:text-gray-400">Ball, XP va tangalar yig'ing. 3 yulduz olish uchun 90%+ aniqlikka erishing!</p>
                                </div>
                            </div>
                        </div>

                        <button @click="showTutorial = false"
                                class="w-full mt-6 py-3 bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-xl font-semibold hover:opacity-90 transition-opacity">
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
                        <div class="bg-gradient-to-r from-purple-600 to-pink-600 p-5">
                            <div class="flex items-center justify-between">
                                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                                    <span class="text-2xl">🏆</span> Yutuqlar
                                </h2>
                                <button @click="showAchievements = false" class="p-2 hover:bg-white/20 rounded-xl transition-colors">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <p class="text-white/80 text-sm mt-1">{{ earnedAchievements.length }}/{{ achievements?.length || 0 }} yutuq ochilgan</p>
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
                                    class="w-full py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-xl font-semibold hover:opacity-90 transition-opacity">
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
