<script setup>
import { computed } from 'vue'
import { XMarkIcon, LockClosedIcon, CheckIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    achievement: Object,
})

const emit = defineEmits(['close'])

const formattedDate = computed(() => {
    if (!props.achievement?.earnedAt) return null
    return new Date(props.achievement.earnedAt).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    })
})

const progressPercentage = computed(() => {
    if (!props.achievement?.current_progress || !props.achievement?.target) return 0
    return Math.min(100, Math.round((props.achievement.current_progress / props.achievement.target) * 100))
})
</script>

<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" @click.self="emit('close')">
            <div class="bg-white dark:bg-gray-800 rounded-3xl max-w-md w-full mx-4 overflow-hidden shadow-2xl">
                <!-- Header -->
                <div 
                    class="p-8 text-center"
                    :class="achievement?.isEarned 
                        ? 'bg-gradient-to-br from-yellow-400 to-orange-500' 
                        : 'bg-gray-200 dark:bg-gray-700'"
                >
                    <button 
                        @click="emit('close')"
                        class="absolute top-4 right-4 p-2 rounded-full bg-white/20 hover:bg-white/30 transition-colors"
                    >
                        <XMarkIcon class="w-5 h-5" :class="achievement?.isEarned ? 'text-white' : 'text-gray-600'" />
                    </button>
                    
                    <div 
                        class="text-7xl mb-4"
                        :class="achievement?.isEarned ? '' : 'grayscale opacity-50'"
                    >
                        {{ achievement?.icon || '🏆' }}
                    </div>
                    
                    <h2 
                        class="text-2xl font-bold"
                        :class="achievement?.isEarned ? 'text-white' : 'text-gray-700 dark:text-gray-300'"
                    >
                        {{ achievement?.name }}
                    </h2>
                    
                    <div 
                        v-if="achievement?.isEarned"
                        class="inline-flex items-center mt-2 px-3 py-1 bg-white/20 rounded-full text-white text-sm"
                    >
                        <CheckIcon class="w-4 h-4 mr-1" />
                        Unlocked
                    </div>
                    <div v-else class="inline-flex items-center mt-2 px-3 py-1 bg-gray-300 dark:bg-gray-600 rounded-full text-gray-600 dark:text-gray-300 text-sm">
                        <LockClosedIcon class="w-4 h-4 mr-1" />
                        Locked
                    </div>
                </div>
                
                <!-- Content -->
                <div class="p-6">
                    <!-- Description -->
                    <p class="text-gray-600 dark:text-gray-400 text-center mb-6">
                        {{ achievement?.description }}
                    </p>
                    
                    <!-- Progress (for locked achievements) -->
                    <div v-if="!achievement?.isEarned && achievement?.target" class="mb-6">
                        <div class="flex justify-between text-sm text-gray-500 mb-2">
                            <span>Progress</span>
                            <span>{{ achievement?.current_progress || 0 }} / {{ achievement?.target }}</span>
                        </div>
                        <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div 
                                class="h-full bg-gradient-to-r from-blue-400 to-blue-600 rounded-full transition-all"
                                :style="{ width: `${progressPercentage}%` }"
                            ></div>
                        </div>
                    </div>
                    
                    <!-- Earned Date -->
                    <div v-if="formattedDate" class="text-center text-sm text-gray-500">
                        Earned on {{ formattedDate }}
                    </div>
                    
                    <!-- Rewards -->
                    <div v-if="achievement?.rewards" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <h4 class="text-sm font-medium text-gray-500 mb-3">Rewards</h4>
                        <div class="flex justify-center space-x-6">
                            <div v-if="achievement.rewards.xp" class="text-center">
                                <p class="text-xl font-bold text-purple-600">+{{ achievement.rewards.xp }}</p>
                                <p class="text-xs text-gray-500">XP</p>
                            </div>
                            <div v-if="achievement.rewards.coins" class="text-center">
                                <p class="text-xl font-bold text-yellow-600">+{{ achievement.rewards.coins }}</p>
                                <p class="text-xs text-gray-500">Coins</p>
                            </div>
                            <div v-if="achievement.rewards.gems" class="text-center">
                                <p class="text-xl font-bold text-cyan-600">+{{ achievement.rewards.gems }}</p>
                                <p class="text-xs text-gray-500">Gems</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
