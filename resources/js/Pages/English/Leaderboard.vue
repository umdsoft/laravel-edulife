<script setup>
import { ref, computed } from 'vue'
import EnglishLayout from '@/Components/English/Layout/EnglishLayout.vue'
import { useEnglishStore } from '@/Stores/englishStore'
import { 
    TrophyIcon, 
    FireIcon, 
    AcademicCapIcon,
    BoltIcon,
    ChartBarIcon 
} from '@heroicons/vue/24/outline'

const props = defineProps({
    leaderboards: Object,
    userRanks: Object,
    profile: Object,
})

const store = useEnglishStore()

const selectedType = ref('xp')
const selectedPeriod = ref('weekly')

const types = [
    { id: 'xp', label: 'XP', icon: AcademicCapIcon },
    { id: 'streak', label: 'Streak', icon: FireIcon },
    { id: 'elo', label: 'ELO', icon: BoltIcon },
    { id: 'battles', label: 'Battles Won', icon: TrophyIcon },
]

const periods = [
    { id: 'daily', label: 'Today' },
    { id: 'weekly', label: 'This Week' },
    { id: 'monthly', label: 'This Month' },
    { id: 'all_time', label: 'All Time' },
]

const currentLeaderboard = computed(() => {
    return props.leaderboards?.[selectedType.value]?.[selectedPeriod.value] || []
})

const currentUserRank = computed(() => {
    return props.userRanks?.[selectedType.value]?.[selectedPeriod.value] || null
})

const getMedalEmoji = (rank) => {
    if (rank === 1) return '🥇'
    if (rank === 2) return '🥈'
    if (rank === 3) return '🥉'
    return rank
}

const getValueLabel = (entry) => {
    switch (selectedType.value) {
        case 'xp': return `${entry.score?.toLocaleString()} XP`
        case 'streak': return `${entry.score} days`
        case 'elo': return `${entry.score} ELO`
        case 'battles': return `${entry.score} wins`
        default: return entry.score
    }
}

const tierColors = {
    bronze: 'from-orange-400 to-orange-600',
    silver: 'from-gray-300 to-gray-500',
    gold: 'from-yellow-400 to-yellow-600',
    platinum: 'from-cyan-400 to-cyan-600',
    diamond: 'from-blue-400 to-purple-500',
}
</script>

<template>
    <EnglishLayout title="Leaderboard">
        <div class="max-w-4xl mx-auto px-4 py-6">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center justify-center">
                    <ChartBarIcon class="w-8 h-8 text-purple-500 mr-2" />
                    Leaderboard
                </h1>
                <p class="text-gray-600 dark:text-gray-400">Compete with learners worldwide</p>
            </div>
            
            <!-- Type Selector -->
            <div class="flex space-x-2 mb-4 overflow-x-auto pb-2">
                <button
                    v-for="type in types"
                    :key="type.id"
                    @click="selectedType = type.id"
                    class="flex items-center space-x-2 px-4 py-2 rounded-xl whitespace-nowrap transition-all"
                    :class="selectedType === type.id 
                        ? 'bg-purple-500 text-white' 
                        : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'"
                >
                    <component :is="type.icon" class="w-5 h-5" />
                    <span>{{ type.label }}</span>
                </button>
            </div>
            
            <!-- Period Selector -->
            <div class="flex space-x-2 mb-8 bg-gray-100 dark:bg-gray-800 rounded-xl p-1">
                <button
                    v-for="period in periods"
                    :key="period.id"
                    @click="selectedPeriod = period.id"
                    class="flex-1 py-2 px-3 rounded-lg text-sm font-medium transition-colors"
                    :class="selectedPeriod === period.id 
                        ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow' 
                        : 'text-gray-600 dark:text-gray-400'"
                >
                    {{ period.label }}
                </button>
            </div>
            
            <!-- User's Current Rank -->
            <div v-if="currentUserRank" class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-2xl p-4 mb-6 text-white">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <span class="text-3xl font-bold">#{{ currentUserRank.rank }}</span>
                        <div>
                            <p class="font-medium">Your Rank</p>
                            <p class="text-sm opacity-80">{{ getValueLabel(currentUserRank) }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm opacity-80">To reach #{{ Math.max(1, currentUserRank.rank - 1) }}</p>
                        <p class="font-medium">Need +{{ currentUserRank.points_to_next || 0 }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Top 3 Podium -->
            <div v-if="currentLeaderboard.length >= 3" class="flex items-end justify-center space-x-4 mb-8">
                <!-- 2nd Place -->
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 rounded-full bg-gradient-to-br from-gray-300 to-gray-500 flex items-center justify-center text-xl font-bold text-white mb-2 shadow-lg">
                        {{ currentLeaderboard[1]?.user?.name?.[0]?.toUpperCase() }}
                    </div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate max-w-20">{{ currentLeaderboard[1]?.user?.name }}</p>
                    <p class="text-xs text-gray-500">{{ getValueLabel(currentLeaderboard[1]) }}</p>
                    <div class="w-20 h-16 bg-gradient-to-t from-gray-300 to-gray-400 rounded-t-lg mt-2 flex items-center justify-center">
                        <span class="text-2xl">🥈</span>
                    </div>
                </div>
                
                <!-- 1st Place -->
                <div class="flex flex-col items-center -mt-4">
                    <div class="w-20 h-20 rounded-full bg-gradient-to-br from-yellow-400 to-yellow-600 flex items-center justify-center text-2xl font-bold text-white mb-2 shadow-lg ring-4 ring-yellow-300">
                        {{ currentLeaderboard[0]?.user?.name?.[0]?.toUpperCase() }}
                    </div>
                    <p class="font-medium text-gray-900 dark:text-white truncate max-w-24">{{ currentLeaderboard[0]?.user?.name }}</p>
                    <p class="text-sm text-gray-500">{{ getValueLabel(currentLeaderboard[0]) }}</p>
                    <div class="w-24 h-24 bg-gradient-to-t from-yellow-400 to-yellow-500 rounded-t-lg mt-2 flex items-center justify-center">
                        <span class="text-3xl">🥇</span>
                    </div>
                </div>
                
                <!-- 3rd Place -->
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-xl font-bold text-white mb-2 shadow-lg">
                        {{ currentLeaderboard[2]?.user?.name?.[0]?.toUpperCase() }}
                    </div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate max-w-20">{{ currentLeaderboard[2]?.user?.name }}</p>
                    <p class="text-xs text-gray-500">{{ getValueLabel(currentLeaderboard[2]) }}</p>
                    <div class="w-20 h-12 bg-gradient-to-t from-orange-400 to-orange-500 rounded-t-lg mt-2 flex items-center justify-center">
                        <span class="text-2xl">🥉</span>
                    </div>
                </div>
            </div>
            
            <!-- Full List -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
                <div
                    v-for="(entry, index) in currentLeaderboard.slice(3)"
                    :key="entry.user_id"
                    class="flex items-center p-4 border-b border-gray-100 dark:border-gray-700 last:border-0"
                    :class="entry.user_id === profile?.user_id ? 'bg-purple-50 dark:bg-purple-900/20' : ''"
                >
                    <span class="w-8 text-center font-bold text-gray-400">{{ index + 4 }}</span>
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center text-white font-medium mx-3">
                        {{ entry.user?.name?.[0]?.toUpperCase() }}
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-gray-900 dark:text-white">
                            {{ entry.user?.name }}
                            <span v-if="entry.user_id === profile?.user_id" class="text-purple-500 text-sm">(You)</span>
                        </p>
                    </div>
                    <span class="font-bold text-gray-900 dark:text-white">{{ getValueLabel(entry) }}</span>
                </div>
                
                <div v-if="!currentLeaderboard.length" class="p-8 text-center text-gray-500">
                    No entries yet. Be the first!
                </div>
            </div>
        </div>
    </EnglishLayout>
</template>
