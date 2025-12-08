<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import DailyGoalRing from '@/Components/English/Dashboard/DailyGoalRing.vue'
import QuickActionCard from '@/Components/English/Dashboard/QuickActionCard.vue'
import StreakCalendar from '@/Components/English/Dashboard/StreakCalendar.vue'
import LeaderboardMini from '@/Components/English/Dashboard/LeaderboardMini.vue'
import {
    PlayIcon,
    BookOpenIcon,
    PuzzlePieceIcon,
    BoltIcon,
    FireIcon,
    SparklesIcon,
    AcademicCapIcon,
    TrophyIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    profile: Object,
    currentLevel: Object,
    dailyChallenge: Object,
    wordsForReview: { type: Number, default: 0 },
    recentActivity: Array,
    weeklyLeaderboard: Array,
    achievements: Array,
})

const levelProgress = computed(() => {
    if (!props.currentLevel) return 0
    const { current_xp, xp_for_next_level } = props.currentLevel
    if (!xp_for_next_level) return 0
    return Math.round((current_xp / xp_for_next_level) * 100)
})

const quickActions = computed(() => [
    {
        title: 'O\'qishni davom ettirish',
        description: 'Oxirgi darsdan davom eting',
        icon: PlayIcon,
        href: '/student/english/levels',
        gradient: 'from-blue-500 to-blue-600',
        badge: props.currentLevel?.current_lesson,
    },
    {
        title: 'So\'zlarni takrorlash',
        description: `${props.wordsForReview || 0} ta so'z takrorlash uchun`,
        icon: BookOpenIcon,
        href: '/student/english/vocabulary/review',
        gradient: 'from-purple-500 to-purple-600',
        badge: props.wordsForReview || null,
    },
    {
        title: 'O\'yinlar',
        description: 'O\'yin orqali o\'rganing',
        icon: PuzzlePieceIcon,
        href: '/student/english/games',
        gradient: 'from-green-500 to-green-600',
    },
    {
        title: 'Bellashuv',
        description: 'Boshqalar bilan raqobatlashing',
        icon: BoltIcon,
        href: '/student/english/battle',
        gradient: 'from-orange-500 to-orange-600',
    },
])
</script>

<template>

    <Head title="Ingliz tili - Dashboard" />

    <StudentLayout>
        <div class="space-y-6">
            <!-- Welcome Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Xush kelibsiz, {{ profile?.name || 'O\'quvchi' }}! 👋
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        {{ currentLevel?.order_number || 1 }}-daraja - {{ currentLevel?.name || 'Boshlang\'ich' }}
                    </p>
                </div>
                <div class="mt-3 sm:mt-0">
                    <span
                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300">
                        {{ currentLevel?.icon || '📚' }} {{ currentLevel?.code || 'A1' }}
                    </span>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl p-4 text-white">
                    <div class="flex items-center justify-between">
                        <FireIcon class="w-8 h-8 opacity-80" />
                        <span class="text-3xl font-bold">{{ profile?.current_streak || 0 }}</span>
                    </div>
                    <p class="mt-2 text-sm opacity-90">Kunlik streak</p>
                </div>

                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl p-4 text-white">
                    <div class="flex items-center justify-between">
                        <SparklesIcon class="w-8 h-8 opacity-80" />
                        <span class="text-3xl font-bold">{{ profile?.total_xp || 0 }}</span>
                    </div>
                    <p class="mt-2 text-sm opacity-90">Jami XP</p>
                </div>

                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-4 text-white">
                    <div class="flex items-center justify-between">
                        <AcademicCapIcon class="w-8 h-8 opacity-80" />
                        <span class="text-3xl font-bold">{{ profile?.words_learned || 0 }}</span>
                    </div>
                    <p class="mt-2 text-sm opacity-90">O'rganilgan so'zlar</p>
                </div>

                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl p-4 text-white">
                    <div class="flex items-center justify-between">
                        <TrophyIcon class="w-8 h-8 opacity-80" />
                        <span class="text-3xl font-bold">{{ profile?.elo_rating || 1000 }}</span>
                    </div>
                    <p class="mt-2 text-sm opacity-90">Battle ELO</p>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid lg:grid-cols-3 gap-6">
                <!-- Left Column -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Daily Goal -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Kunlik maqsad</h2>
                            <Link href="/student/english/daily" class="text-sm text-blue-600 hover:text-blue-700">
                                Barchasi
                            </Link>
                        </div>
                        <div class="flex items-center space-x-6">
                            <DailyGoalRing :xp-progress="dailyChallenge?.xp_progress || 0"
                                :xp-goal="dailyChallenge?.xp_goal || 50"
                                :tasks-completed="dailyChallenge?.tasks_completed || 0"
                                :tasks-total="dailyChallenge?.tasks_total || 3" />
                            <div class="flex-1">
                                <p class="text-gray-600 dark:text-gray-400 mb-2">
                                    {{ dailyChallenge?.xp_progress || 0 }} / {{ dailyChallenge?.xp_goal || 50 }} XP
                                </p>
                                <div class="space-y-2">
                                    <div v-for="task in dailyChallenge?.tasks || []" :key="task.id"
                                        class="flex items-center space-x-2">
                                        <span class="w-5 h-5 rounded-full flex items-center justify-center text-xs"
                                            :class="task.completed ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-400'">
                                            {{ task.completed ? '✓' : '' }}
                                        </span>
                                        <span class="text-sm"
                                            :class="task.completed ? 'line-through text-gray-400' : 'text-gray-700 dark:text-gray-300'">
                                            {{ task.name }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="grid grid-cols-2 gap-4">
                        <QuickActionCard v-for="action in quickActions" :key="action.title" :title="action.title"
                            :description="action.description" :icon="action.icon" :href="action.href"
                            :gradient="action.gradient" :badge="action.badge" />
                    </div>

                    <!-- Level Progress -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Daraja progressi</h2>
                            <span class="text-sm text-gray-500">{{ levelProgress }}%</span>
                        </div>
                        <div class="relative h-4 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div class="absolute inset-y-0 left-0 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full transition-all duration-500"
                                :style="{ width: `${levelProgress}%` }"></div>
                        </div>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            {{ currentLevel?.current_xp || 0 }} / {{ currentLevel?.xp_for_next_level || 500 }} XP
                            keyingi daraja uchun
                        </p>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-6">
                    <!-- Streak Calendar -->
                    <StreakCalendar :streak-days="profile?.streak_calendar || []" />

                    <!-- Mini Leaderboard -->
                    <LeaderboardMini :entries="weeklyLeaderboard || []" />

                    <!-- Recent Achievements -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Yutuqlar</h2>
                            <Link href="/student/english/achievements"
                                class="text-sm text-blue-600 hover:text-blue-700">
                                Barchasi
                            </Link>
                        </div>
                        <div class="space-y-3">
                            <div v-for="achievement in achievements?.slice(0, 3)" :key="achievement.id"
                                class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center text-xl" :class="{
                                    'bg-yellow-100': achievement.tier === 'gold',
                                    'bg-gray-100': achievement.tier === 'silver',
                                    'bg-orange-100': achievement.tier === 'bronze',
                                }">
                                    {{ achievement.icon || '🏆' }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ achievement.name }}
                                    </p>
                                    <p class="text-xs text-gray-500 truncate">
                                        {{ achievement.description }}
                                    </p>
                                </div>
                            </div>
                            <div v-if="!achievements?.length" class="text-center text-gray-500 py-4">
                                Hali yutuqlar yo'q. O'rganishni boshlang!
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
