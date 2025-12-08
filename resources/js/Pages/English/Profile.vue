<script setup>
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import { useEnglishStore } from '@/Stores/englishStore'
import {
    CogIcon,
    FireIcon,
    AcademicCapIcon,
    TrophyIcon,
    BookOpenIcon,
    BoltIcon,
    CalendarDaysIcon,
    ChartBarIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    profile: Object,
    stats: Object,
    recentActivity: { type: Array, default: () => [] },
    achievements: { type: Array, default: () => [] },
})

const store = useEnglishStore()

const eloTier = computed(() => store.eloTier)

const tierInfo = {
    bronze: { color: 'from-orange-400 to-orange-600', label: 'Bronze', icon: '🥉' },
    silver: { color: 'from-gray-300 to-gray-500', label: 'Silver', icon: '🥈' },
    gold: { color: 'from-yellow-400 to-yellow-600', label: 'Gold', icon: '🥇' },
    platinum: { color: 'from-cyan-400 to-cyan-600', label: 'Platinum', icon: '💎' },
    diamond: { color: 'from-blue-400 to-purple-500', label: 'Diamond', icon: '👑' },
}

const currentTier = computed(() => tierInfo[eloTier.value] || tierInfo.bronze)

const statCards = computed(() => [
    { label: 'Current Level', value: props.profile?.current_level || 1, icon: AcademicCapIcon, color: 'text-purple-500' },
    { label: 'Total XP', value: props.profile?.total_xp?.toLocaleString() || 0, icon: ChartBarIcon, color: 'text-blue-500' },
    { label: 'Current Streak', value: `${props.profile?.current_streak || 0} days`, icon: FireIcon, color: 'text-orange-500' },
    { label: 'Words Learned', value: props.stats?.words_learned || 0, icon: BookOpenIcon, color: 'text-green-500' },
    { label: 'Lessons Completed', value: props.stats?.lessons_completed || 0, icon: AcademicCapIcon, color: 'text-indigo-500' },
    { label: 'Battles Won', value: props.stats?.battles_won || 0, icon: BoltIcon, color: 'text-pink-500' },
])

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric'
    })
}
</script>

<template>

    <Head title="Ingliz tili - Profil" />

    <StudentLayout>
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Profile Header -->
            <div class="bg-gradient-to-br from-purple-500 to-indigo-600 rounded-3xl p-6 mb-8 text-white">
                <div class="flex items-center space-x-4">
                    <div
                        class="w-20 h-20 rounded-full bg-white/20 flex items-center justify-center text-4xl font-bold border-4 border-white/30">
                        {{ profile?.user?.name?.[0]?.toUpperCase() || 'U' }}
                    </div>
                    <div class="flex-1">
                        <h1 class="text-2xl font-bold">{{ profile?.user?.name }}</h1>
                        <p class="text-purple-200">{{ profile?.user?.email }}</p>
                        <div class="flex items-center space-x-2 mt-2">
                            <span class="px-3 py-1 bg-white/20 rounded-full text-sm">
                                Level {{ profile?.current_level || 1 }}
                            </span>
                            <span class="px-3 py-1 bg-white/20 rounded-full text-sm flex items-center">
                                {{ currentTier.icon }} {{ currentTier.label }}
                            </span>
                        </div>
                    </div>
                    <button class="p-2 rounded-lg bg-white/20 hover:bg-white/30 transition-colors">
                        <CogIcon class="w-6 h-6" />
                    </button>
                </div>

                <!-- Level Progress -->
                <div class="mt-6">
                    <div class="flex justify-between text-sm mb-2">
                        <span>Level {{ profile?.current_level || 1 }}</span>
                        <span>{{ profile?.level_progress || 0 }}%</span>
                    </div>
                    <div class="h-3 bg-white/20 rounded-full overflow-hidden">
                        <div class="h-full bg-white rounded-full transition-all"
                            :style="{ width: `${profile?.level_progress || 0}%` }"></div>
                    </div>
                </div>
            </div>

            <!-- ELO Card -->
            <div class="rounded-2xl p-6 mb-8 text-white bg-gradient-to-br" :class="currentTier.color">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-lg opacity-80">Battle Rating</p>
                        <p class="text-4xl font-bold">{{ profile?.elo_rating || 1000 }} ELO</p>
                    </div>
                    <div class="text-6xl">{{ currentTier.icon }}</div>
                </div>
                <div class="mt-4 flex justify-between text-sm opacity-80">
                    <span>{{ stats?.wins || 0 }} Wins</span>
                    <span>{{ stats?.losses || 0 }} Losses</span>
                    <span>{{ stats?.win_rate || 0 }}% Win Rate</span>
                </div>
            </div>

            <!-- Stats Grid -->
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Your Statistics</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8">
                <div v-for="stat in statCards" :key="stat.label"
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
                    <component :is="stat.icon" class="w-6 h-6 mb-2" :class="stat.color" />
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stat.value }}</p>
                    <p class="text-sm text-gray-500">{{ stat.label }}</p>
                </div>
            </div>

            <!-- Recent Achievements -->
            <div v-if="achievements?.length" class="mb-8">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recent Achievements</h2>
                <div class="flex space-x-4 overflow-x-auto pb-2">
                    <div v-for="achievement in achievements.slice(0, 5)" :key="achievement.id"
                        class="flex-shrink-0 w-20 text-center">
                        <div
                            class="w-16 h-16 mx-auto rounded-xl bg-gradient-to-br from-yellow-400 to-orange-500 flex items-center justify-center text-3xl mb-2">
                            {{ achievement.icon || '🏆' }}
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 line-clamp-2">{{ achievement.name }}</p>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div v-if="recentActivity?.length">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recent Activity</h2>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow overflow-hidden">
                    <div v-for="activity in recentActivity.slice(0, 10)" :key="activity.id"
                        class="flex items-center p-4 border-b border-gray-100 dark:border-gray-700 last:border-0">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3" :class="{
                            'bg-green-100 text-green-600': activity.type === 'lesson',
                            'bg-blue-100 text-blue-600': activity.type === 'vocabulary',
                            'bg-purple-100 text-purple-600': activity.type === 'battle',
                            'bg-yellow-100 text-yellow-600': activity.type === 'achievement',
                        }">
                            <component :is="activity.type === 'lesson' ? AcademicCapIcon
                                : activity.type === 'vocabulary' ? BookOpenIcon
                                    : activity.type === 'battle' ? BoltIcon
                                        : TrophyIcon" class="w-5 h-5" />
                        </div>
                        <div class="flex-1">
                            <p class="text-gray-900 dark:text-white">{{ activity.description }}</p>
                            <p class="text-sm text-gray-500">{{ formatDate(activity.created_at) }}</p>
                        </div>
                        <span v-if="activity.xp" class="text-purple-600 font-medium">+{{ activity.xp }} XP</span>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
