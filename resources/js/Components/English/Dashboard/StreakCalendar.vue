<script setup>
import { computed } from 'vue'
import { FireIcon } from '@heroicons/vue/24/solid'

const props = defineProps({
    streakDays: { type: Array, default: () => [] },
})

const days = ['M', 'T', 'W', 'T', 'F', 'S', 'S']

const weekDays = computed(() => {
    const today = new Date()
    const result = []
    
    for (let i = 6; i >= 0; i--) {
        const date = new Date(today)
        date.setDate(date.getDate() - i)
        const dateStr = date.toISOString().split('T')[0]
        
        result.push({
            day: days[(date.getDay() + 6) % 7],
            date: dateStr,
            isToday: i === 0,
            hasStreak: props.streakDays.includes(dateStr),
        })
    }
    
    return result
})

const currentStreak = computed(() => {
    let streak = 0
    for (let i = weekDays.value.length - 1; i >= 0; i--) {
        if (weekDays.value[i].hasStreak) streak++
        else if (i < weekDays.value.length - 1) break
    }
    return streak
})
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Weekly Streak</h2>
            <div class="flex items-center space-x-1 text-orange-500">
                <FireIcon class="w-5 h-5" />
                <span class="font-bold">{{ currentStreak }}</span>
            </div>
        </div>
        
        <div class="flex justify-between">
            <div v-for="(day, index) in weekDays" :key="index" class="flex flex-col items-center space-y-2">
                <span class="text-xs text-gray-500">{{ day.day }}</span>
                <div class="w-10 h-10 rounded-full flex items-center justify-center transition-all"
                    :class="{
                        'bg-orange-100 dark:bg-orange-900/30': day.hasStreak,
                        'bg-gray-100 dark:bg-gray-700': !day.hasStreak,
                        'ring-2 ring-blue-500 ring-offset-2 dark:ring-offset-gray-800': day.isToday,
                    }">
                    <FireIcon v-if="day.hasStreak" class="w-5 h-5 text-orange-500" />
                </div>
            </div>
        </div>
        
        <p class="mt-4 text-sm text-center text-gray-600 dark:text-gray-400">
            <template v-if="currentStreak > 0">🔥 {{ currentStreak }} day streak! Keep it up!</template>
            <template v-else>Start your streak today!</template>
        </p>
    </div>
</template>
