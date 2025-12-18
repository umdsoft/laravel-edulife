<script setup>
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    entries: { type: Array, default: () => [] },
})

const getMedalEmoji = (rank) => {
    switch (rank) {
        case 1: return '🥇'
        case 2: return '🥈'
        case 3: return '🥉'
        default: return rank
    }
}
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Leaderboard</h2>
            <Link href="/student/english/leaderboard" class="text-sm text-blue-600 hover:text-blue-700">View all</Link>
        </div>
        
        <div class="space-y-3">
            <div v-for="(entry, index) in entries?.slice(0, 5)" :key="entry.user_id || entry.id || index"
                class="flex items-center space-x-3"
                :class="{ 'bg-blue-50 dark:bg-blue-900/20 -mx-2 px-2 py-1 rounded-lg': entry.is_current_user }">
                <span class="w-8 text-center font-medium text-gray-500">{{ getMedalEmoji(entry.rank || index + 1) }}</span>
                <img :src="entry.avatar || '/images/default-avatar.png'" class="w-8 h-8 rounded-full" :alt="entry.name" />
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ entry.name }}</p>
                </div>
                <span class="text-sm font-bold text-purple-600 dark:text-purple-400">{{ entry.score || entry.xp || 0 }} XP</span>
            </div>
            <div v-if="!entries?.length" class="text-center text-gray-500 py-4">
                <p class="text-sm">Hali ma'lumot yo'q</p>
            </div>
        </div>
    </div>
</template>
