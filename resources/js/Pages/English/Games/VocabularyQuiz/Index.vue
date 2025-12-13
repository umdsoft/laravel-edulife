<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed, onMounted } from 'vue'
import StudentLayout from '@/Layouts/StudentLayout.vue'

const props = defineProps({
    levels: Array,
    config: Object,
    userStats: Object,
    powerups: Array,
    achievements: Array,
    userAchievements: Array,
})

const showTutorial = ref(false)
const showAchievements = ref(false)
const selectedLevel = ref(null)

const totalStars = computed(() => props.userStats?.total_stars || 0)
const maxStars = computed(() => props.userStats?.max_stars || 30)
const completedLevels = computed(() => props.userStats?.completed_levels || 0)
const totalLevels = computed(() => props.userStats?.total_levels || 10)
const wordsLearned = computed(() => props.userStats?.words_learned || 0)

const progressPercentage = computed(() => {
    return maxStars.value > 0 ? Math.round((totalStars.value / maxStars.value) * 100) : 0
})

const earnedAchievements = computed(() => {
    if (!props.achievements || !props.userAchievements) return []
    return props.achievements.filter(a => props.userAchievements.includes(a.id))
})

function getLevelIcon(level) {
    const icons = {
        'seedling': '🌱',
        'book-open': '📖',
        'trending-up': '📈',
        'layers': '📚',
        'target': '🎯',
        'award': '🏅',
        'zap': '⚡',
        'star': '⭐',
        'crown': '👑',
        'gem': '💎',
    }
    return icons[level.icon] || '📚'
}

function getGradientClass(level) {
    const gradients = {
        'emerald': 'from-emerald-500 to-emerald-600',
        'blue': 'from-blue-500 to-blue-600',
        'purple': 'from-purple-500 to-purple-600',
        'orange': 'from-orange-500 to-orange-600',
        'rose': 'from-rose-500 to-rose-600',
        'amber': 'from-amber-500 to-yellow-500',
    }
    return gradients[level.color] || 'from-blue-500 to-blue-600'
}

function playLevel(level) {
    if (!level.unlocked) return
    router.visit(`/student/english/games/vocabulary-quiz/play/${level.number}`)
}

function getPowerupIcon(powerup) {
    const icons = {
        'scissors': '✂️',
        'fast-forward': '⏩',
        'clock': '⏰',
        'lightbulb': '💡',
        'zap': '⚡',
    }
    return icons[powerup.icon] || '🔮'
}

function getAchievementIcon(achievement) {
    const icons = {
        'flag': '🚩',
        'star': '⭐',
        'flame': '🔥',
        'zap': '⚡',
        'bolt': '⚡',
        'book': '📖',
        'award': '🏅',
        'graduation-cap': '🎓',
        'trophy': '🏆',
        'shield': '🛡️',
        'calendar': '📅',
        'crown': '👑',
    }
    return icons[achievement.icon] || '🏆'
}
</script>

<template>
    <Head title="So'z Viktorinasi - Vocabulary Quiz" />

    <StudentLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900 -m-6 p-6 font-sans">
            <!-- Premium Hero Section -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 p-8 mb-8">
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
                                <span class="text-4xl">🧠</span>
                                {{ config?.game_name_uz || "So'z Viktorinasi" }}
                            </h1>
                            <p class="text-white/80 mt-2 text-lg">
                                {{ config?.description_uz || "Turli savol turlari bilan so'z boyligingizni sinab ko'ring" }}
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
                            <div class="text-3xl font-bold text-white">{{ wordsLearned }}</div>
                            <div class="text-white/70 text-sm">So'zlar o'rganildi</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center">
                            <div class="text-3xl font-bold text-white">{{ progressPercentage }}%</div>
                            <div class="text-white/70 text-sm">Umumiy progress</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Powerups Section - Compact Horizontal -->
            <div class="mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-4">
                    <div class="flex items-center justify-between flex-wrap gap-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">🔮</span>
                            <span class="font-semibold text-gray-800 dark:text-white">Maxsus Kuchlar:</span>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <div v-for="powerup in powerups" :key="powerup.id"
                                 class="group relative flex items-center gap-2 px-3 py-2 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600 rounded-xl hover:shadow-md transition-all cursor-default">
                                <span class="text-xl group-hover:scale-110 transition-transform">{{ getPowerupIcon(powerup) }}</span>
                                <div class="hidden sm:block">
                                    <div class="font-medium text-gray-800 dark:text-white text-sm leading-tight">{{ powerup.name_uz }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ powerup.max_uses }}x</div>
                                </div>
                                <!-- Tooltip for mobile -->
                                <div class="sm:hidden absolute -top-12 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10">
                                    {{ powerup.name_uz }} ({{ powerup.max_uses }}x)
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
                             'relative rounded-2xl overflow-hidden transition-all duration-300 cursor-pointer group',
                             level.unlocked
                                 ? 'hover:scale-105 hover:shadow-2xl'
                                 : 'opacity-60 cursor-not-allowed'
                         ]">
                        <!-- Level Card Background -->
                        <div :class="[
                            'absolute inset-0 bg-gradient-to-br',
                            getGradientClass(level)
                        ]"></div>

                        <!-- Lock Overlay -->
                        <div v-if="!level.unlocked" class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm z-10 flex items-center justify-center">
                            <div class="text-center">
                                <span class="text-4xl">🔒</span>
                                <p class="text-white/80 text-sm mt-2">{{ level.required_stars }} yulduz kerak</p>
                            </div>
                        </div>

                        <!-- Card Content -->
                        <div class="relative z-5 p-5">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-4xl">{{ getLevelIcon(level) }}</span>
                                <span class="bg-white/20 px-3 py-1 rounded-full text-white text-sm font-medium">
                                    {{ level.cefr_level }}
                                </span>
                            </div>

                            <h3 class="text-lg font-bold text-white mb-1">{{ level.name_uz }}</h3>
                            <p class="text-white/70 text-sm mb-4">{{ level.description_uz }}</p>

                            <!-- Stars -->
                            <div class="flex items-center gap-1 mb-3">
                                <span v-for="i in 3" :key="i"
                                      :class="i <= (level.stars || 0) ? 'text-yellow-300' : 'text-white/30'"
                                      class="text-xl">⭐</span>
                            </div>

                            <!-- Level Info -->
                            <div class="flex items-center justify-between text-white/70 text-sm">
                                <span>{{ level.questions_count }} savol</span>
                                <span>{{ level.time_limit }}s</span>
                            </div>

                            <!-- Completed Badge -->
                            <div v-if="level.completed" class="absolute top-3 right-3">
                                <span class="bg-green-500 text-white text-xs px-2 py-1 rounded-full">✓</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Question Types Info -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg mb-8">
                <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                    <span>❓</span> Savol Turlari
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                        <span class="text-2xl">📝</span>
                        <div>
                            <div class="font-semibold text-gray-800 dark:text-white">So'zdan Ta'rifga</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">So'zning to'g'ri ta'rifini toping</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                        <span class="text-2xl">🔤</span>
                        <div>
                            <div class="font-semibold text-gray-800 dark:text-white">Ta'rifdan So'zga</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Ta'rifga mos so'zni toping</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                        <span class="text-2xl">🌐</span>
                        <div>
                            <div class="font-semibold text-gray-800 dark:text-white">Tarjima</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Inglizcha-O'zbekcha tarjima</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                        <span class="text-2xl">🔄</span>
                        <div>
                            <div class="font-semibold text-gray-800 dark:text-white">Sinonim/Antonim</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Ma'nodosh va zid ma'noli so'zlar</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tips Section -->
            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-2xl p-6 text-white">
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
                        <div class="font-semibold mb-2">🎯 Kuchlardan foydalaning</div>
                        <p class="text-sm text-white/80">Qiyin savollarda maxsus kuchlardan foydalaning!</p>
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
                                <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900 rounded-xl flex items-center justify-center text-2xl shrink-0">1</div>
                                <div>
                                    <h3 class="font-semibold text-gray-800 dark:text-white mb-1">Bosqichni tanlang</h3>
                                    <p class="text-gray-600 dark:text-gray-400">Ochiq bo'lgan bosqichlardan birini tanlang. Yangi bosqichlarni ochish uchun yulduzlar yig'ing.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900 rounded-xl flex items-center justify-center text-2xl shrink-0">2</div>
                                <div>
                                    <h3 class="font-semibold text-gray-800 dark:text-white mb-1">Savollarga javob bering</h3>
                                    <p class="text-gray-600 dark:text-gray-400">Har bir savolga berilgan vaqt ichida javob bering. To'g'ri variantni tanlang.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900 rounded-xl flex items-center justify-center text-2xl shrink-0">3</div>
                                <div>
                                    <h3 class="font-semibold text-gray-800 dark:text-white mb-1">Maxsus kuchlardan foydalaning</h3>
                                    <p class="text-gray-600 dark:text-gray-400">50/50, Yordam, Qo'shimcha vaqt va boshqa kuchlar sizga yordam beradi.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900 rounded-xl flex items-center justify-center text-2xl shrink-0">4</div>
                                <div>
                                    <h3 class="font-semibold text-gray-800 dark:text-white mb-1">Yutuqlarni to'plang</h3>
                                    <p class="text-gray-600 dark:text-gray-400">Ball, XP va tangalar yig'ing. 3 yulduz olish uchun 90%+ aniqlikka erishing!</p>
                                </div>
                            </div>
                        </div>

                        <button @click="showTutorial = false"
                                class="w-full mt-6 py-3 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-xl font-semibold hover:opacity-90 transition-opacity">
                            Tushundim!
                        </button>
                    </div>
                </div>
            </div>

            <!-- Achievements Modal -->
            <div v-if="showAchievements" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                                <span>🏆</span> Yutuqlar
                            </h2>
                            <button @click="showAchievements = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl">
                                <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div v-for="achievement in achievements" :key="achievement.id"
                                 :class="[
                                     'p-4 rounded-xl border-2 transition-all',
                                     userAchievements.includes(achievement.id)
                                         ? 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-400'
                                         : 'bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600 opacity-60'
                                 ]">
                                <div class="flex items-center gap-3">
                                    <span class="text-3xl">{{ getAchievementIcon(achievement) }}</span>
                                    <div>
                                        <div class="font-semibold text-gray-800 dark:text-white">{{ achievement.name_uz }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ achievement.description_uz }}</div>
                                        <div class="text-xs text-indigo-600 dark:text-indigo-400 mt-1">
                                            +{{ achievement.reward.xp }} XP, +{{ achievement.reward.coins }} tanga
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button @click="showAchievements = false"
                                class="w-full mt-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-white rounded-xl font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                            Yopish
                        </button>
                    </div>
                </div>
            </div>
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
</style>
