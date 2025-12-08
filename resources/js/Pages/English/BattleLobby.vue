<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import EnglishLayout from '@/Components/English/Layout/EnglishLayout.vue'
import { useEnglishStore } from '@/Stores/englishStore'
import { useWebSocket } from '@/Composables/useWebSocket'
import { useAudio } from '@/Composables/useAudio'
import { 
    UserIcon,
    TrophyIcon,
    BoltIcon,
    XMarkIcon,
    ArrowPathIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    profile: Object,
    battleHistory: { type: Array, default: () => [] },
    stats: Object,
})

const store = useEnglishStore()
const ws = useWebSocket()
const audio = useAudio()

const isSearching = ref(false)
const searchTime = ref(0)
const searchInterval = ref(null)
const selectedMode = ref('quick')
const battleChannel = ref(null)

const eloTier = computed(() => store.eloTier)

const battleModes = [
    { id: 'quick', name: 'Quick Match', icon: BoltIcon, description: '5 rounds, 15 sec each', color: 'from-blue-500 to-blue-600' },
    { id: 'ranked', name: 'Ranked', icon: TrophyIcon, description: 'ELO rating at stake', color: 'from-purple-500 to-purple-600' },
]

const tierColors = {
    bronze: 'text-orange-600',
    silver: 'text-gray-400',
    gold: 'text-yellow-500',
    platinum: 'text-cyan-400',
    diamond: 'text-blue-400',
}

const startSearch = async () => {
    isSearching.value = true
    searchTime.value = 0
    
    // Start timer
    searchInterval.value = setInterval(() => {
        searchTime.value++
    }, 1000)
    
    try {
        const response = await axios.post('/api/v1/english/battles/find-match', {
            mode: selectedMode.value,
        })
        
        // Listen for match found
        battleChannel.value = ws.joinBattleChannel(response.data.data.queue_id)
        
        battleChannel.value.listen('BattleStarted', (event) => {
            audio.playSuccess()
            clearInterval(searchInterval.value)
            router.visit(`/english/battle/${event.battle.id}`)
        })
        
    } catch (error) {
        console.error('Failed to find match:', error)
        cancelSearch()
    }
}

const cancelSearch = async () => {
    isSearching.value = false
    clearInterval(searchInterval.value)
    
    if (battleChannel.value) {
        ws.leaveChannel(battleChannel.value)
    }
    
    try {
        await axios.post('/api/v1/english/battles/cancel-search')
    } catch (error) {
        console.error('Failed to cancel search:', error)
    }
}

const formatSearchTime = computed(() => {
    const mins = Math.floor(searchTime.value / 60)
    const secs = searchTime.value % 60
    return `${mins}:${secs.toString().padStart(2, '0')}`
})

onUnmounted(() => {
    if (searchInterval.value) {
        clearInterval(searchInterval.value)
    }
    if (battleChannel.value) {
        ws.leaveChannel(battleChannel.value)
    }
})
</script>

<template>
    <EnglishLayout title="Battle Arena">
        <div class="max-w-4xl mx-auto px-4 py-6">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">⚔️ Battle Arena</h1>
                <p class="text-gray-600 dark:text-gray-400">Challenge other learners in real-time English battles!</p>
            </div>
            
            <!-- Player Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 mb-8 shadow-lg">
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 rounded-full bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center text-white text-2xl">
                        {{ profile?.user?.name?.[0]?.toUpperCase() || 'U' }}
                    </div>
                    <div class="flex-1">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ profile?.user?.name }}</h2>
                        <div class="flex items-center space-x-4 mt-1">
                            <span class="text-lg font-medium" :class="tierColors[eloTier]">
                                {{ profile?.elo_rating || 1000 }} ELO
                            </span>
                            <span class="text-sm text-gray-500 capitalize">{{ eloTier }} Tier</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ stats?.wins || 0 }}W - {{ stats?.losses || 0 }}L
                        </p>
                        <p class="text-sm text-gray-500">Win Rate: {{ stats?.win_rate || 0 }}%</p>
                    </div>
                </div>
            </div>
            
            <!-- Searching State -->
            <div v-if="isSearching" class="text-center py-12">
                <div class="relative w-32 h-32 mx-auto mb-6">
                    <div class="absolute inset-0 border-4 border-purple-200 rounded-full"></div>
                    <div class="absolute inset-0 border-4 border-t-purple-500 rounded-full animate-spin"></div>
                    <div class="absolute inset-4 bg-white dark:bg-gray-800 rounded-full flex items-center justify-center">
                        <span class="text-2xl font-mono font-bold text-purple-600">{{ formatSearchTime }}</span>
                    </div>
                </div>
                
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Finding Opponent...</h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6">Searching for a player with similar skill level</p>
                
                <button
                    @click="cancelSearch"
                    class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
                >
                    <XMarkIcon class="w-5 h-5 inline mr-2" />
                    Cancel
                </button>
            </div>
            
            <!-- Mode Selection -->
            <template v-else>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Select Mode</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                    <div
                        v-for="mode in battleModes"
                        :key="mode.id"
                        @click="selectedMode = mode.id"
                        class="relative p-6 rounded-2xl cursor-pointer transition-all"
                        :class="selectedMode === mode.id 
                            ? `bg-gradient-to-br ${mode.color} text-white shadow-lg scale-105` 
                            : 'bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 hover:border-purple-500'"
                    >
                        <component :is="mode.icon" class="w-8 h-8 mb-2" />
                        <h4 class="text-xl font-bold">{{ mode.name }}</h4>
                        <p :class="selectedMode === mode.id ? 'text-white/80' : 'text-gray-500'">
                            {{ mode.description }}
                        </p>
                    </div>
                </div>
                
                <!-- Start Button -->
                <div class="text-center">
                    <button
                        @click="startSearch"
                        class="px-12 py-4 bg-gradient-to-r from-purple-500 to-purple-600 text-white text-xl font-bold rounded-2xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all"
                    >
                        Find Match
                    </button>
                </div>
                
                <!-- Recent Battles -->
                <div v-if="battleHistory?.length" class="mt-12">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recent Battles</h3>
                    <div class="space-y-3">
                        <div
                            v-for="battle in battleHistory.slice(0, 5)"
                            :key="battle.id"
                            class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 rounded-xl"
                        >
                            <div class="flex items-center space-x-3">
                                <div 
                                    class="w-3 h-3 rounded-full"
                                    :class="battle.won ? 'bg-green-500' : 'bg-red-500'"
                                ></div>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    vs {{ battle.opponent_name }}
                                </span>
                            </div>
                            <div class="text-right">
                                <span 
                                    class="font-bold"
                                    :class="battle.elo_change >= 0 ? 'text-green-500' : 'text-red-500'"
                                >
                                    {{ battle.elo_change >= 0 ? '+' : '' }}{{ battle.elo_change }} ELO
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </EnglishLayout>
</template>
