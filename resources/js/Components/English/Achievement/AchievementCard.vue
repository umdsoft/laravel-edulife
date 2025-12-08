<script setup>
import { computed } from 'vue'
import { LockClosedIcon } from '@heroicons/vue/24/solid'

const props = defineProps({
    achievement: Object,
    isEarned: Boolean,
})

defineEmits(['click'])

const categoryColors = {
    streak: 'from-orange-400 to-red-500',
    vocabulary: 'from-blue-400 to-blue-600',
    lessons: 'from-green-400 to-green-600',
    games: 'from-purple-400 to-purple-600',
    battles: 'from-pink-400 to-pink-600',
    social: 'from-cyan-400 to-cyan-600',
    special: 'from-yellow-400 to-yellow-600',
}

const gradientClass = computed(() => {
    return categoryColors[props.achievement?.category] || 'from-gray-400 to-gray-600'
})
</script>

<template>
    <div 
        @click="$emit('click')"
        class="relative cursor-pointer group"
    >
        <div 
            class="aspect-square rounded-2xl p-4 flex flex-col items-center justify-center transition-all duration-300 group-hover:scale-105"
            :class="isEarned 
                ? `bg-gradient-to-br ${gradientClass} shadow-lg` 
                : 'bg-gray-200 dark:bg-gray-700'"
        >
            <!-- Icon -->
            <div 
                class="text-5xl mb-2"
                :class="isEarned ? '' : 'grayscale opacity-50'"
            >
                {{ achievement?.icon || '🏆' }}
            </div>
            
            <!-- Name -->
            <p 
                class="text-sm font-medium text-center line-clamp-2"
                :class="isEarned ? 'text-white' : 'text-gray-500 dark:text-gray-400'"
            >
                {{ achievement?.name }}
            </p>
            
            <!-- Lock Indicator -->
            <div v-if="!isEarned" class="absolute top-2 right-2">
                <LockClosedIcon class="w-5 h-5 text-gray-400 dark:text-gray-500" />
            </div>
            
            <!-- Rarity Badge -->
            <div 
                v-if="achievement?.rarity && isEarned"
                class="absolute top-2 right-2 px-2 py-0.5 rounded-full text-xs font-medium"
                :class="{
                    'bg-gray-200 text-gray-700': achievement.rarity === 'common',
                    'bg-green-200 text-green-700': achievement.rarity === 'uncommon',
                    'bg-blue-200 text-blue-700': achievement.rarity === 'rare',
                    'bg-purple-200 text-purple-700': achievement.rarity === 'epic',
                    'bg-yellow-200 text-yellow-700': achievement.rarity === 'legendary',
                }"
            >
                {{ achievement.rarity }}
            </div>
        </div>
    </div>
</template>
