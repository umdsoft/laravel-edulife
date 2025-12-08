<script setup>
import { ref, computed } from 'vue'
import EnglishLayout from '@/Components/English/Layout/EnglishLayout.vue'
import AchievementCard from '@/Components/English/Achievement/AchievementCard.vue'
import AchievementModal from '@/Components/English/Achievement/AchievementModal.vue'
import { TrophyIcon, LockClosedIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    achievements: { type: Array, default: () => [] },
    userAchievements: { type: Array, default: () => [] },
    stats: Object,
})

const selectedTab = ref('all')
const selectedAchievement = ref(null)
const showModal = ref(false)

const tabs = [
    { id: 'all', label: 'All' },
    { id: 'unlocked', label: 'Unlocked' },
    { id: 'locked', label: 'Locked' },
]

const earnedIds = computed(() => new Set(props.userAchievements.map(ua => ua.achievement_id)))

const filteredAchievements = computed(() => {
    return props.achievements.filter(achievement => {
        const isEarned = earnedIds.value.has(achievement.id)
        if (selectedTab.value === 'unlocked') return isEarned
        if (selectedTab.value === 'locked') return !isEarned
        return true
    }).sort((a, b) => {
        // Sort earned first, then by category
        const aEarned = earnedIds.value.has(a.id)
        const bEarned = earnedIds.value.has(b.id)
        if (aEarned !== bEarned) return bEarned - aEarned
        return a.category?.localeCompare(b.category)
    })
})

const progressStats = computed(() => ({
    total: props.achievements.length,
    earned: props.userAchievements.length,
    percentage: Math.round((props.userAchievements.length / props.achievements.length) * 100) || 0,
}))

const openAchievement = (achievement) => {
    selectedAchievement.value = {
        ...achievement,
        isEarned: earnedIds.value.has(achievement.id),
        earnedAt: props.userAchievements.find(ua => ua.achievement_id === achievement.id)?.earned_at,
    }
    showModal.value = true
}
</script>

<template>
    <EnglishLayout title="Achievements">
        <div class="max-w-4xl mx-auto px-4 py-6">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center">
                        <TrophyIcon class="w-8 h-8 text-yellow-500 mr-2" />
                        Achievements
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">Collect badges and show off your progress!</p>
                </div>
            </div>
            
            <!-- Progress Card -->
            <div class="bg-gradient-to-br from-yellow-400 to-orange-500 rounded-2xl p-6 mb-8 text-white">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-lg opacity-80">Achievement Progress</p>
                        <p class="text-4xl font-bold">{{ progressStats.earned }} / {{ progressStats.total }}</p>
                    </div>
                    <div class="text-6xl">🏆</div>
                </div>
                <div class="h-3 bg-white/30 rounded-full overflow-hidden">
                    <div 
                        class="h-full bg-white rounded-full transition-all duration-500"
                        :style="{ width: `${progressStats.percentage}%` }"
                    ></div>
                </div>
                <p class="text-right mt-2 text-sm opacity-80">{{ progressStats.percentage }}% Complete</p>
            </div>
            
            <!-- Tabs -->
            <div class="flex space-x-2 mb-6 bg-gray-100 dark:bg-gray-800 rounded-xl p-1">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    @click="selectedTab = tab.id"
                    class="flex-1 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
                    :class="selectedTab === tab.id 
                        ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow' 
                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
                >
                    {{ tab.label }}
                </button>
            </div>
            
            <!-- Achievements Grid -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <AchievementCard
                    v-for="achievement in filteredAchievements"
                    :key="achievement.id"
                    :achievement="achievement"
                    :is-earned="earnedIds.has(achievement.id)"
                    @click="openAchievement(achievement)"
                />
            </div>
            
            <!-- Empty State -->
            <div v-if="!filteredAchievements.length" class="text-center py-12">
                <LockClosedIcon class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
                <p class="text-gray-500 dark:text-gray-400">No achievements found</p>
            </div>
            
            <!-- Achievement Modal -->
            <AchievementModal
                v-if="showModal"
                :achievement="selectedAchievement"
                @close="showModal = false"
            />
        </div>
    </EnglishLayout>
</template>
