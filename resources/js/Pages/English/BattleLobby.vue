<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import {
    UserIcon,
    TrophyIcon,
    BoltIcon,
    XMarkIcon,
    ArrowPathIcon,
    FireIcon,
    StarIcon,
    SparklesIcon,
    ChartBarIcon,
    ClockIcon,
    CurrencyDollarIcon,
    UserGroupIcon,
    PlayIcon,
    PlusIcon,
    MagnifyingGlassIcon,
    ShieldCheckIcon,
    AcademicCapIcon,
    GlobeAltIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'
import { FireIcon as FireIconSolid, StarIcon as StarIconSolid } from '@heroicons/vue/24/solid'

const props = defineProps({
    profile: Object,
    battleHistory: { type: Array, default: () => [] },
    stats: Object,
    leaderboard: { type: Array, default: () => [] },
    activeBattles: { type: Array, default: () => [] },
    onlineUsers: { type: Number, default: 0 },
    dailyChallenge: Object,
})

// State
const isSearching = ref(false)
const isCreating = ref(false)
const searchTime = ref(0)
const searchInterval = ref(null)
const selectedMode = ref('quick')
const selectedDifficulty = ref('mixed')
const selectedCategory = ref('all')
const showCreateModal = ref(false)
const showJoinModal = ref(false)
const showFindBattleModal = ref(false)
const battleCode = ref('')
const error = ref('')
const success = ref('')
const opponentType = ref('ai') // 'ai' | 'random' | 'friend'
const isStartingAIBattle = ref(false)

// Battle creation settings
const createSettings = ref({
    name: '',
    rounds: 10,
    timePerQuestion: 15,
    difficulty: 'mixed',
    level: 'mixed', // CEFR level: A1, A2, B1, B2, C1, C2 or 'mixed'
    category: 'all',
    isPrivate: false,
    entryFee: 0,
    prizePool: 0,
    opponentType: 'ai', // 'ai' | 'random' | 'friend'
})

// CEFR Levels for battle
const battleLevels = [
    { id: 'mixed', name: 'Aralash', description: 'Barcha darajalar', icon: '🎲', color: 'purple' },
    { id: 'A1', name: 'A1 - Beginner', description: "Boshlang'ich", icon: '🌱', color: 'green' },
    { id: 'A2', name: 'A2 - Elementary', description: "Boshlang'ich+", icon: '🌿', color: 'green' },
    { id: 'B1', name: 'B1 - Intermediate', description: "O'rta", icon: '🌳', color: 'blue' },
    { id: 'B2', name: 'B2 - Upper-Int', description: "O'rta+", icon: '🌲', color: 'blue' },
    { id: 'C1', name: 'C1 - Advanced', description: 'Yuqori', icon: '🔥', color: 'orange' },
    { id: 'C2', name: 'C2 - Proficient', description: 'Professional', icon: '👑', color: 'red' },
]

// Computed
const eloTier = computed(() => props.profile?.elo_tier || 'bronze')
const userCoins = computed(() => props.profile?.coins || 0)
const canCreateBattle = computed(() => userCoins.value >= 30)

const tierConfig = {
    bronze: { color: 'from-orange-600 to-orange-700', bg: 'bg-orange-100', text: 'text-orange-600', icon: '🥉', min: 0, max: 999 },
    silver: { color: 'from-gray-400 to-gray-500', bg: 'bg-gray-100', text: 'text-gray-500', icon: '🥈', min: 1000, max: 1199 },
    gold: { color: 'from-yellow-500 to-yellow-600', bg: 'bg-yellow-100', text: 'text-yellow-600', icon: '🥇', min: 1200, max: 1399 },
    platinum: { color: 'from-cyan-400 to-cyan-500', bg: 'bg-cyan-100', text: 'text-cyan-500', icon: '💎', min: 1400, max: 1599 },
    diamond: { color: 'from-blue-400 to-blue-500', bg: 'bg-blue-100', text: 'text-blue-500', icon: '💠', min: 1600, max: 1799 },
    master: { color: 'from-purple-500 to-pink-500', bg: 'bg-purple-100', text: 'text-purple-600', icon: '👑', min: 1800, max: 9999 },
}

const currentTierConfig = computed(() => tierConfig[eloTier.value] || tierConfig.bronze)

const battleModes = [
    {
        id: 'quick',
        name: 'Quick Match',
        icon: BoltIcon,
        description: 'Tezkor 5 raundlik bellashuv',
        details: '5 savol • 15 soniya • Bepul',
        color: 'from-blue-500 to-cyan-500',
        cost: 0,
        xpBonus: '1x',
    },
    {
        id: 'ranked',
        name: 'Ranked Battle',
        icon: TrophyIcon,
        description: 'ELO reytingingiz uchun kurashing',
        details: '10 savol • 15 soniya • ELO +/-',
        color: 'from-purple-500 to-pink-500',
        cost: 0,
        xpBonus: '1.5x',
    },
    {
        id: 'tournament',
        name: 'Tournament',
        icon: StarIcon,
        description: 'Katta mukofotlar uchun raqobat',
        details: '15 savol • 12 soniya • Prizes',
        color: 'from-amber-500 to-orange-500',
        cost: 50,
        xpBonus: '2x',
    },
]

const categories = [
    { id: 'all', name: 'Barcha mavzular', icon: GlobeAltIcon },
    { id: 'vocabulary', name: 'Vocabulary', icon: AcademicCapIcon },
    { id: 'grammar', name: 'Grammar', icon: ShieldCheckIcon },
    { id: 'idioms', name: 'Idioms & Phrases', icon: SparklesIcon },
]

const difficulties = [
    { id: 'easy', name: 'Oson', color: 'text-green-500', icon: '🌱', description: 'Boshlang\'ichlar uchun' },
    { id: 'medium', name: "O'rta", color: 'text-yellow-500', icon: '🌿', description: 'O\'rta daraja' },
    { id: 'hard', name: 'Qiyin', color: 'text-red-500', icon: '🔥', description: 'Professional daraja' },
    { id: 'mixed', name: 'Aralash', color: 'text-purple-500', icon: '🎲', description: 'Barcha darajalar' },
]

const formatSearchTime = computed(() => {
    const mins = Math.floor(searchTime.value / 60)
    const secs = searchTime.value % 60
    return `${mins}:${secs.toString().padStart(2, '0')}`
})

const winRate = computed(() => {
    const total = (props.stats?.wins || 0) + (props.stats?.losses || 0)
    if (total === 0) return 0
    return Math.round((props.stats?.wins / total) * 100)
})

const eloProgress = computed(() => {
    const elo = props.profile?.elo_rating || 1000
    const tier = currentTierConfig.value
    const progress = ((elo - tier.min) / (tier.max - tier.min)) * 100
    return Math.min(100, Math.max(0, progress))
})

const nextTier = computed(() => {
    const tiers = Object.keys(tierConfig)
    const currentIndex = tiers.indexOf(eloTier.value)
    if (currentIndex < tiers.length - 1) {
        return tiers[currentIndex + 1]
    }
    return null
})

const eloToNextTier = computed(() => {
    if (!nextTier.value) return 0
    const elo = props.profile?.elo_rating || 1000
    return tierConfig[nextTier.value].min - elo
})

// Methods
const openFindBattleModal = () => {
    showFindBattleModal.value = true
    error.value = ''
}

const startSearch = async () => {
    if (selectedMode.value === 'tournament' && userCoins.value < 50) {
        error.value = "Tournament uchun 50 coin kerak!"
        return
    }

    isSearching.value = true
    searchTime.value = 0
    error.value = ''

    searchInterval.value = setInterval(() => {
        searchTime.value++
    }, 1000)

    try {
        const response = await axios.post('/api/v1/english/battles/find-match', {
            battle_type: selectedMode.value,
            difficulty: selectedDifficulty.value,
            category: selectedCategory.value,
        })

        if (response.data.success && response.data.data.battle) {
            router.visit(`/student/english/battle/${response.data.data.battle.id}`)
        }
    } catch (err) {
        console.error('Failed to find match:', err)
        error.value = err.response?.data?.message || "Raqib topishda xatolik yuz berdi"
        cancelSearch()
    }
}

const cancelSearch = () => {
    isSearching.value = false
    clearInterval(searchInterval.value)
    searchInterval.value = null
}

// Start AI Battle - FREE, no coins required, creates battle and immediately starts with AI opponent
const startAIBattle = async () => {
    // AI battles are FREE - no coin check needed
    isStartingAIBattle.value = true
    error.value = ''

    try {
        const response = await axios.post('/api/v1/english/battles/create', {
            name: 'AI Battle',
            battle_type: 'practice',
            rounds: createSettings.value.rounds,
            time_per_question: createSettings.value.timePerQuestion * 1000,
            opponent_type: 'ai',
            difficulty: createSettings.value.difficulty,
            level: createSettings.value.level,
        })

        if (response.data.success) {
            showCreateModal.value = false
            router.visit(`/student/english/battle/${response.data.data.battle.id}`)
        }
    } catch (err) {
        error.value = err.response?.data?.message || "Battle yaratishda xatolik"
    } finally {
        isStartingAIBattle.value = false
    }
}

// Create battle for random opponent (costs 30 coins)
const startRandomBattle = async () => {
    if (userCoins.value < 30) {
        error.value = "Battle yaratish uchun 30 coin kerak!"
        return
    }

    isCreating.value = true
    error.value = ''

    try {
        const response = await axios.post('/api/v1/english/battles/create', {
            name: createSettings.value.name || `${props.profile?.name}'s Battle`,
            battle_type: 'ranked',
            rounds: createSettings.value.rounds,
            time_per_question: createSettings.value.timePerQuestion * 1000,
            opponent_type: 'random',
            difficulty: createSettings.value.difficulty,
            level: createSettings.value.level,
        })

        if (response.data.success) {
            showCreateModal.value = false
            // Go to waiting room to find random opponent
            router.visit(`/student/english/battle/${response.data.data.battle.id}`)
        }
    } catch (err) {
        error.value = err.response?.data?.message || "Battle yaratishda xatolik"
    } finally {
        isCreating.value = false
    }
}

// Create battle for friend (private with code, costs 30 coins)
const createFriendBattle = async () => {
    if (userCoins.value < 30) {
        error.value = "Battle yaratish uchun 30 coin kerak!"
        return
    }

    isCreating.value = true
    error.value = ''

    try {
        const response = await axios.post('/api/v1/english/battles/create', {
            name: createSettings.value.name || `${props.profile?.name}'s Battle`,
            battle_type: 'friendly',
            rounds: createSettings.value.rounds,
            time_per_question: createSettings.value.timePerQuestion * 1000,
            opponent_type: 'friend',
            difficulty: createSettings.value.difficulty,
            level: createSettings.value.level,
        })

        if (response.data.success) {
            success.value = "Battle muvaffaqiyatli yaratildi!"
            showCreateModal.value = false
            router.visit(`/student/english/battle/${response.data.data.battle.id}`)
        }
    } catch (err) {
        error.value = err.response?.data?.message || "Battle yaratishda xatolik"
    } finally {
        isCreating.value = false
    }
}

const createBattle = async () => {
    if (createSettings.value.opponentType === 'ai') {
        await startAIBattle()
    } else if (createSettings.value.opponentType === 'random') {
        await startRandomBattle()
    } else if (createSettings.value.opponentType === 'friend') {
        await createFriendBattle()
    }
}

const joinBattle = async () => {
    if (!battleCode.value.trim()) {
        error.value = "Battle kodini kiriting"
        return
    }

    try {
        const response = await axios.post('/api/v1/english/battles/join', {
            code: battleCode.value.trim().toUpperCase(),
        })

        if (response.data.success) {
            router.visit(`/student/english/battle/${response.data.data.battle.id}`)
        }
    } catch (err) {
        error.value = err.response?.data?.message || "Battle topilmadi"
    }
}

const joinActiveBattle = (battleId) => {
    router.visit(`/student/english/battle/${battleId}`)
}

const viewBattleDetails = (battleId) => {
    router.visit(`/student/english/battle/${battleId}/results`)
}

onUnmounted(() => {
    if (searchInterval.value) {
        clearInterval(searchInterval.value)
    }
})

// Clear messages after delay
watch([error, success], () => {
    if (error.value || success.value) {
        setTimeout(() => {
            error.value = ''
            success.value = ''
        }, 5000)
    }
})
</script>

<template>
    <Head title="Battle Arena - Ingliz tili bellashuvlari" />

    <StudentLayout>
        <div class="space-y-6">

            <!-- Hero Section -->
            <div class="relative overflow-hidden bg-gradient-to-br from-purple-600 via-indigo-600 to-blue-700 rounded-3xl p-8 text-white">
                <div class="absolute inset-0 bg-[url('/images/pattern-battle.svg')] opacity-10"></div>
                <div class="absolute -top-24 -right-24 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-purple-500/20 rounded-full blur-3xl"></div>

                <div class="relative z-10 flex flex-col lg:flex-row items-center justify-between gap-8">
                    <div class="text-center lg:text-left">
                        <div class="flex items-center justify-center lg:justify-start gap-2 mb-4">
                            <span class="text-4xl">⚔️</span>
                            <h1 class="text-4xl lg:text-5xl font-black">Battle Arena</h1>
                        </div>
                        <p class="text-xl text-white/80 mb-6 max-w-xl">
                            Real-time ingliz tili bellashuvlarida bilimingizni sinang va raqiblaringizni mag'lub eting!
                        </p>
                        <div class="flex flex-wrap items-center justify-center lg:justify-start gap-4">
                            <div class="flex items-center gap-2 bg-white/20 backdrop-blur-sm rounded-full px-4 py-2">
                                <UserGroupIcon class="w-5 h-5" />
                                <span class="font-medium">{{ onlineUsers || 128 }} online</span>
                            </div>
                            <div class="flex items-center gap-2 bg-white/20 backdrop-blur-sm rounded-full px-4 py-2">
                                <FireIconSolid class="w-5 h-5 text-orange-400" />
                                <span class="font-medium">{{ stats?.current_streak || 0 }} streak</span>
                            </div>
                        </div>
                    </div>

                    <!-- Player Card -->
                    <div class="bg-white/10 backdrop-blur-md rounded-2xl p-6 min-w-[320px]">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="relative">
                                <div class="w-20 h-20 rounded-full bg-gradient-to-br overflow-hidden ring-4 ring-white/30"
                                     :class="currentTierConfig.color">
                                    <img v-if="profile?.avatar" :src="profile.avatar" class="w-full h-full object-cover" />
                                    <div v-else class="w-full h-full flex items-center justify-center text-3xl font-bold">
                                        {{ profile?.name?.[0]?.toUpperCase() || 'U' }}
                                    </div>
                                </div>
                                <span class="absolute -bottom-1 -right-1 text-2xl">{{ currentTierConfig.icon }}</span>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold">{{ profile?.name || 'Player' }}</h2>
                                <div class="flex items-center gap-2">
                                    <span class="text-2xl font-black">{{ profile?.elo_rating || 1000 }}</span>
                                    <span class="text-white/60">ELO</span>
                                </div>
                                <span class="text-sm capitalize px-2 py-0.5 rounded-full bg-white/20">
                                    {{ eloTier }} Tier
                                </span>
                            </div>
                        </div>

                        <!-- ELO Progress -->
                        <div class="mb-4">
                            <div class="flex justify-between text-sm mb-1">
                                <span>{{ eloTier.charAt(0).toUpperCase() + eloTier.slice(1) }}</span>
                                <span v-if="nextTier">{{ eloToNextTier }} ELO to {{ nextTier }}</span>
                                <span v-else>Max Tier!</span>
                            </div>
                            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-yellow-400 to-orange-500 rounded-full transition-all duration-500"
                                     :style="{ width: `${eloProgress}%` }"></div>
                            </div>
                        </div>

                        <!-- Stats -->
                        <div class="grid grid-cols-3 gap-2 text-center">
                            <div class="bg-white/10 rounded-lg p-2">
                                <div class="text-lg font-bold text-green-400">{{ stats?.wins || 0 }}</div>
                                <div class="text-xs text-white/60">Wins</div>
                            </div>
                            <div class="bg-white/10 rounded-lg p-2">
                                <div class="text-lg font-bold text-red-400">{{ stats?.losses || 0 }}</div>
                                <div class="text-xs text-white/60">Losses</div>
                            </div>
                            <div class="bg-white/10 rounded-lg p-2">
                                <div class="text-lg font-bold text-yellow-400">{{ winRate }}%</div>
                                <div class="text-xs text-white/60">Win Rate</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            <Transition name="slide-fade">
                <div v-if="error" class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-xl p-4 flex items-center gap-3">
                    <ExclamationTriangleIcon class="w-6 h-6 text-red-500 flex-shrink-0" />
                    <p class="text-red-700 dark:text-red-300">{{ error }}</p>
                </div>
            </Transition>

            <Transition name="slide-fade">
                <div v-if="success" class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-xl p-4 flex items-center gap-3">
                    <CheckCircleIcon class="w-6 h-6 text-green-500 flex-shrink-0" />
                    <p class="text-green-700 dark:text-green-300">{{ success }}</p>
                </div>
            </Transition>

            <!-- Searching State -->
            <Transition name="scale-fade">
                <div v-if="isSearching" class="bg-white dark:bg-gray-800 rounded-3xl p-12 text-center shadow-xl">
                    <div class="relative w-40 h-40 mx-auto mb-8">
                        <!-- Outer spinning ring -->
                        <div class="absolute inset-0 border-4 border-purple-200 dark:border-purple-900 rounded-full"></div>
                        <div class="absolute inset-0 border-4 border-t-purple-500 border-r-transparent border-b-transparent border-l-transparent rounded-full animate-spin"></div>

                        <!-- Middle pulsing ring -->
                        <div class="absolute inset-4 border-4 border-blue-200 dark:border-blue-900 rounded-full animate-pulse"></div>
                        <div class="absolute inset-4 border-4 border-t-blue-500 border-r-transparent border-b-transparent border-l-transparent rounded-full animate-spin" style="animation-duration: 1.5s; animation-direction: reverse;"></div>

                        <!-- Center content -->
                        <div class="absolute inset-8 bg-gradient-to-br from-purple-500 to-blue-500 rounded-full flex items-center justify-center shadow-lg">
                            <div class="text-center text-white">
                                <span class="text-2xl font-mono font-bold">{{ formatSearchTime }}</span>
                            </div>
                        </div>

                        <!-- Floating icons -->
                        <div class="absolute -top-2 left-1/2 -translate-x-1/2 text-2xl animate-bounce">⚔️</div>
                        <div class="absolute -bottom-2 left-1/2 -translate-x-1/2 text-2xl animate-bounce" style="animation-delay: 0.2s;">🎯</div>
                    </div>

                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Raqib qidirilmoqda...</h2>
                    <p class="text-gray-600 dark:text-gray-400 mb-2">
                        {{ selectedMode === 'ranked' ? 'ELO reytingingizga mos raqib' : 'Tezkor bellashuv' }} qidirilmoqda
                    </p>
                    <p class="text-sm text-gray-500 mb-8">
                        O'rtacha kutish vaqti: ~30 soniya
                    </p>

                    <button @click="cancelSearch"
                        class="px-8 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition-all">
                        <XMarkIcon class="w-5 h-5 inline mr-2" />
                        Bekor qilish
                    </button>
                </div>
            </Transition>

            <!-- Main Content -->
            <template v-if="!isSearching">
                <div class="grid lg:grid-cols-3 gap-6">

                    <!-- Left Column - Battle Modes -->
                    <div class="lg:col-span-2 space-y-6">

                        <!-- Quick Actions -->
                        <div class="grid sm:grid-cols-2 gap-4">
                            <button @click="showCreateModal = true"
                                class="group relative overflow-hidden bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl p-6 text-white text-left transition-all hover:scale-[1.02] hover:shadow-xl">
                                <div class="absolute -right-8 -bottom-8 w-32 h-32 bg-white/10 rounded-full"></div>
                                <PlusIcon class="w-8 h-8 mb-3" />
                                <h3 class="text-xl font-bold mb-1">Battle Yaratish</h3>
                                <p class="text-white/80 text-sm">Do'stlaringizni chaqiring</p>
                                <div class="mt-3 flex items-center gap-2 text-sm">
                                    <CurrencyDollarIcon class="w-4 h-4" />
                                    <span>30 coin</span>
                                </div>
                            </button>

                            <button @click="showJoinModal = true"
                                class="group relative overflow-hidden bg-gradient-to-br from-orange-500 to-red-600 rounded-2xl p-6 text-white text-left transition-all hover:scale-[1.02] hover:shadow-xl">
                                <div class="absolute -right-8 -bottom-8 w-32 h-32 bg-white/10 rounded-full"></div>
                                <MagnifyingGlassIcon class="w-8 h-8 mb-3" />
                                <h3 class="text-xl font-bold mb-1">Battle Qo'shilish</h3>
                                <p class="text-white/80 text-sm">Kod orqali qo'shiling</p>
                                <div class="mt-3 flex items-center gap-2 text-sm">
                                    <UserGroupIcon class="w-4 h-4" />
                                    <span>Bepul</span>
                                </div>
                            </button>
                        </div>

                        <!-- Battle Modes -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                <BoltIcon class="w-5 h-5 text-purple-500" />
                                Battle Rejimini Tanlang
                            </h3>

                            <div class="grid gap-4">
                                <div v-for="mode in battleModes" :key="mode.id"
                                    @click="selectedMode = mode.id"
                                    class="relative overflow-hidden rounded-xl p-5 cursor-pointer transition-all border-2"
                                    :class="selectedMode === mode.id
                                        ? `bg-gradient-to-br ${mode.color} text-white border-transparent shadow-lg scale-[1.02]`
                                        : 'bg-gray-50 dark:bg-gray-700/50 border-gray-200 dark:border-gray-600 hover:border-purple-500'">

                                    <div class="flex items-start justify-between">
                                        <div class="flex items-start gap-4">
                                            <div class="p-3 rounded-xl"
                                                 :class="selectedMode === mode.id ? 'bg-white/20' : 'bg-gray-200 dark:bg-gray-600'">
                                                <component :is="mode.icon" class="w-6 h-6"
                                                    :class="selectedMode === mode.id ? 'text-white' : 'text-gray-600 dark:text-gray-300'" />
                                            </div>
                                            <div>
                                                <h4 class="text-lg font-bold" :class="selectedMode !== mode.id && 'text-gray-900 dark:text-white'">
                                                    {{ mode.name }}
                                                </h4>
                                                <p class="text-sm" :class="selectedMode === mode.id ? 'text-white/80' : 'text-gray-500'">
                                                    {{ mode.description }}
                                                </p>
                                                <p class="text-xs mt-1" :class="selectedMode === mode.id ? 'text-white/60' : 'text-gray-400'">
                                                    {{ mode.details }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-xs px-2 py-1 rounded-full"
                                                 :class="selectedMode === mode.id ? 'bg-white/20' : 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400'">
                                                {{ mode.xpBonus }} XP
                                            </div>
                                            <div v-if="mode.cost > 0" class="mt-2 flex items-center gap-1 text-sm"
                                                 :class="selectedMode === mode.id ? 'text-white/80' : 'text-yellow-600'">
                                                <CurrencyDollarIcon class="w-4 h-4" />
                                                {{ mode.cost }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Selected indicator -->
                                    <div v-if="selectedMode === mode.id"
                                         class="absolute top-3 right-3 w-6 h-6 bg-white rounded-full flex items-center justify-center">
                                        <CheckCircleIcon class="w-5 h-5 text-green-500" />
                                    </div>
                                </div>
                            </div>

                            <!-- Start Battle Button -->
                            <button @click="openFindBattleModal"
                                class="w-full mt-6 py-4 bg-gradient-to-r from-purple-500 to-pink-500 text-white text-xl font-bold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all flex items-center justify-center gap-3">
                                <PlayIcon class="w-6 h-6" />
                                Raqib Topish
                            </button>
                        </div>

                        <!-- Active Battles -->
                        <div v-if="activeBattles?.length" class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                <FireIcon class="w-5 h-5 text-orange-500" />
                                Faol Bellashuvlar
                                <span class="ml-auto px-2 py-0.5 bg-red-100 dark:bg-red-900/30 text-red-600 text-sm rounded-full animate-pulse">
                                    LIVE
                                </span>
                            </h3>

                            <div class="space-y-3">
                                <div v-for="battle in activeBattles" :key="battle.id"
                                    class="flex items-center justify-between p-4 bg-gradient-to-r from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20 rounded-xl border border-orange-200 dark:border-orange-800">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-orange-500 flex items-center justify-center text-white">
                                            ⚔️
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">{{ battle.name }}</p>
                                            <p class="text-sm text-gray-500">{{ battle.players_count }}/2 o'yinchi • {{ battle.mode }}</p>
                                        </div>
                                    </div>
                                    <button @click="joinActiveBattle(battle.id)"
                                        class="px-4 py-2 bg-orange-500 text-white rounded-lg font-medium hover:bg-orange-600 transition-colors">
                                        Qo'shilish
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Battles (Quick View) -->
                        <div v-if="battleHistory?.length" class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                <ClockIcon class="w-5 h-5 text-gray-500" />
                                So'nggi Bellashuvlar
                            </h3>

                            <div class="space-y-3">
                                <div v-for="battle in battleHistory.slice(0, 5)" :key="battle.id"
                                    class="flex items-center justify-between p-4 rounded-xl transition-colors"
                                    :class="battle.won ? 'bg-green-50 dark:bg-green-900/20' : (battle.is_draw ? 'bg-yellow-50 dark:bg-yellow-900/20' : 'bg-red-50 dark:bg-red-900/20')">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-xl"
                                             :class="battle.won ? 'bg-green-100 dark:bg-green-900' : (battle.is_draw ? 'bg-yellow-100 dark:bg-yellow-900' : 'bg-red-100 dark:bg-red-900')">
                                            {{ battle.won ? '🏆' : (battle.is_draw ? '🤝' : '😔') }}
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">
                                                vs {{ battle.opponent_name }}
                                                <span v-if="battle.is_ai_battle" class="ml-1 text-xs px-1.5 py-0.5 bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 rounded">AI</span>
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                {{ battle.player_score }} - {{ battle.opponent_score }} • {{ battle.completed_at }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span v-if="!battle.is_ai_battle" class="font-bold text-lg"
                                              :class="battle.elo_change >= 0 ? 'text-green-500' : 'text-red-500'">
                                            {{ battle.elo_change >= 0 ? '+' : '' }}{{ battle.elo_change }}
                                        </span>
                                        <span v-else class="font-bold text-lg text-purple-500">
                                            +{{ battle.xp_earned }} XP
                                        </span>
                                        <p class="text-xs text-gray-500">{{ battle.is_ai_battle ? 'Mashq' : 'ELO' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Mening Natijalarim (My Results - Full Table) -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                <ChartBarIcon class="w-5 h-5 text-indigo-500" />
                                Mening Natijalarim
                                <span v-if="battleHistory?.length" class="ml-auto text-sm font-normal text-gray-500">
                                    Jami: {{ battleHistory.length }} ta battle
                                </span>
                            </h3>

                            <div v-if="battleHistory?.length" class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-gray-200 dark:border-gray-700">
                                            <th class="text-left py-3 px-2 font-semibold text-gray-600 dark:text-gray-400">Sana</th>
                                            <th class="text-left py-3 px-2 font-semibold text-gray-600 dark:text-gray-400">Raqib</th>
                                            <th class="text-center py-3 px-2 font-semibold text-gray-600 dark:text-gray-400">Natija</th>
                                            <th class="text-center py-3 px-2 font-semibold text-gray-600 dark:text-gray-400">Hisob</th>
                                            <th class="text-center py-3 px-2 font-semibold text-gray-600 dark:text-gray-400">To'g'ri</th>
                                            <th class="text-center py-3 px-2 font-semibold text-gray-600 dark:text-gray-400">ELO</th>
                                            <th class="text-center py-3 px-2 font-semibold text-gray-600 dark:text-gray-400">Coin</th>
                                            <th class="text-center py-3 px-2 font-semibold text-gray-600 dark:text-gray-400">XP</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="battle in battleHistory" :key="battle.id"
                                            class="border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                            <!-- Date -->
                                            <td class="py-3 px-2 text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                                {{ battle.completed_at_date || battle.completed_at }}
                                            </td>
                                            <!-- Opponent -->
                                            <td class="py-3 px-2">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold"
                                                         :class="battle.is_ai_battle ? 'bg-purple-100 dark:bg-purple-900/30 text-purple-600' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'">
                                                        {{ battle.is_ai_battle ? '🤖' : (battle.opponent_name?.[0]?.toUpperCase() || '?') }}
                                                    </div>
                                                    <span class="font-medium text-gray-900 dark:text-white">{{ battle.opponent_name }}</span>
                                                    <span v-if="battle.is_ai_battle" class="text-xs px-1.5 py-0.5 bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 rounded">Mashq</span>
                                                </div>
                                            </td>
                                            <!-- Result -->
                                            <td class="py-3 px-2 text-center">
                                                <span class="px-2 py-1 rounded-full text-xs font-bold"
                                                      :class="{
                                                          'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400': battle.won,
                                                          'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400': battle.is_draw,
                                                          'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400': !battle.won && !battle.is_draw
                                                      }">
                                                    {{ battle.won ? 'G\'ALABA' : (battle.is_draw ? 'DURRANG' : 'MAG\'LUB') }}
                                                </span>
                                            </td>
                                            <!-- Score -->
                                            <td class="py-3 px-2 text-center font-mono font-bold">
                                                <span class="text-green-600 dark:text-green-400">{{ battle.player_score }}</span>
                                                <span class="text-gray-400 mx-1">:</span>
                                                <span class="text-red-600 dark:text-red-400">{{ battle.opponent_score }}</span>
                                            </td>
                                            <!-- Correct Answers -->
                                            <td class="py-3 px-2 text-center text-gray-600 dark:text-gray-400">
                                                {{ battle.player_correct || 0 }}/{{ battle.total_rounds || 10 }}
                                            </td>
                                            <!-- ELO Change -->
                                            <td class="py-3 px-2 text-center">
                                                <span v-if="!battle.is_ai_battle" class="font-bold"
                                                      :class="battle.elo_change >= 0 ? 'text-green-500' : 'text-red-500'">
                                                    {{ battle.elo_change >= 0 ? '+' : '' }}{{ battle.elo_change }}
                                                </span>
                                                <span v-else class="text-gray-400">-</span>
                                            </td>
                                            <!-- Coins -->
                                            <td class="py-3 px-2 text-center">
                                                <span v-if="battle.coins_earned > 0" class="font-bold text-yellow-500 flex items-center justify-center gap-1">
                                                    +{{ battle.coins_earned }}
                                                    <CurrencyDollarIcon class="w-4 h-4" />
                                                </span>
                                                <span v-else class="text-gray-400">-</span>
                                            </td>
                                            <!-- XP -->
                                            <td class="py-3 px-2 text-center">
                                                <span class="font-bold text-indigo-500">+{{ battle.xp_earned || 0 }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Empty State -->
                            <div v-else class="text-center py-12">
                                <div class="w-20 h-20 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                    <ChartBarIcon class="w-10 h-10 text-gray-400" />
                                </div>
                                <h4 class="text-lg font-medium text-gray-700 dark:text-gray-300 mb-2">Hali natijalar yo'q</h4>
                                <p class="text-gray-500 mb-4">Birinchi battle'ingizni o'ynang va natijalaringiz shu yerda ko'rinadi</p>
                                <button @click="showCreateModal = true"
                                        class="px-6 py-3 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-xl font-medium hover:shadow-lg transition-all">
                                    Battle Boshlash
                                </button>
                            </div>

                            <!-- Summary Stats -->
                            <div v-if="battleHistory?.length" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                    <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-xl">
                                        <p class="text-2xl font-bold text-green-600">{{ stats?.wins || 0 }}</p>
                                        <p class="text-xs text-green-600/70">G'alabalar</p>
                                    </div>
                                    <div class="text-center p-3 bg-red-50 dark:bg-red-900/20 rounded-xl">
                                        <p class="text-2xl font-bold text-red-600">{{ stats?.losses || 0 }}</p>
                                        <p class="text-xs text-red-600/70">Mag'lubiyatlar</p>
                                    </div>
                                    <div class="text-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
                                        <p class="text-2xl font-bold text-purple-600">{{ winRate }}%</p>
                                        <p class="text-xs text-purple-600/70">G'alaba %</p>
                                    </div>
                                    <div class="text-center p-3 bg-orange-50 dark:bg-orange-900/20 rounded-xl">
                                        <p class="text-2xl font-bold text-orange-600">{{ profile?.best_streak || 0 }}</p>
                                        <p class="text-xs text-orange-600/70">Eng yaxshi streak</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Leaderboard & Stats -->
                    <div class="space-y-6">

                        <!-- Coins Card -->
                        <div class="bg-gradient-to-br from-yellow-400 to-orange-500 rounded-2xl p-6 text-white">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-bold flex items-center gap-2">
                                    <CurrencyDollarIcon class="w-5 h-5" />
                                    Sizning Coinlaringiz
                                </h3>
                                <span class="text-3xl font-black">{{ userCoins }}</span>
                            </div>
                            <p class="text-sm text-white/80 mb-4">
                                Battle yaratish uchun 30 coin kerak
                            </p>
                            <div class="h-2 bg-white/30 rounded-full overflow-hidden">
                                <div class="h-full bg-white rounded-full transition-all"
                                     :style="{ width: `${Math.min(100, (userCoins / 100) * 100)}%` }"></div>
                            </div>
                        </div>

                        <!-- Daily Challenge -->
                        <div v-if="dailyChallenge" class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl p-6 text-white">
                            <div class="flex items-center gap-2 mb-4">
                                <StarIconSolid class="w-6 h-6 text-yellow-400" />
                                <h3 class="font-bold">Kunlik Challenge</h3>
                            </div>
                            <p class="text-white/80 mb-4">{{ dailyChallenge.description }}</p>
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-white/60">Progress</p>
                                    <p class="font-bold">{{ dailyChallenge.current }}/{{ dailyChallenge.target }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-white/60">Mukofot</p>
                                    <p class="font-bold text-yellow-400">+{{ dailyChallenge.reward }} coin</p>
                                </div>
                            </div>
                        </div>

                        <!-- Leaderboard -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                <TrophyIcon class="w-5 h-5 text-yellow-500" />
                                Top O'yinchilar
                            </h3>

                            <div class="space-y-3">
                                <div v-for="(player, index) in leaderboard?.slice(0, 5)" :key="player.id || index"
                                    class="flex items-center gap-3 p-3 rounded-xl"
                                    :class="index < 3 ? 'bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20' : 'bg-gray-50 dark:bg-gray-700/50'">

                                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm"
                                         :class="{
                                             'bg-yellow-400 text-yellow-900': index === 0,
                                             'bg-gray-300 text-gray-700': index === 1,
                                             'bg-orange-400 text-orange-900': index === 2,
                                             'bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300': index > 2
                                         }">
                                        {{ index + 1 }}
                                    </div>

                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br overflow-hidden"
                                         :class="tierConfig[player.elo_tier]?.color || 'from-gray-400 to-gray-500'">
                                        <img v-if="player.avatar" :src="player.avatar" class="w-full h-full object-cover" />
                                        <div v-else class="w-full h-full flex items-center justify-center text-white font-bold">
                                            {{ player.name?.[0] || '?' }}
                                        </div>
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-gray-900 dark:text-white truncate">{{ player.name }}</p>
                                        <p class="text-xs text-gray-500">{{ player.wins }} wins • {{ player.win_rate }}%</p>
                                    </div>

                                    <div class="text-right">
                                        <p class="font-bold text-purple-600 dark:text-purple-400">{{ player.elo_rating }}</p>
                                        <p class="text-xs text-gray-500">ELO</p>
                                    </div>
                                </div>

                                <div v-if="!leaderboard?.length" class="text-center py-8 text-gray-500">
                                    <TrophyIcon class="w-12 h-12 mx-auto mb-2 opacity-30" />
                                    <p>Hali o'yinchilar yo'q</p>
                                </div>
                            </div>
                        </div>

                        <!-- Tips -->
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-2xl p-6 border border-blue-200 dark:border-blue-800">
                            <h3 class="font-bold text-blue-900 dark:text-blue-100 mb-3 flex items-center gap-2">
                                <AcademicCapIcon class="w-5 h-5" />
                                Pro Tips
                            </h3>
                            <ul class="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                                <li class="flex items-start gap-2">
                                    <CheckCircleIcon class="w-4 h-4 mt-0.5 flex-shrink-0 text-blue-500" />
                                    Tezroq javob bersangiz, ko'proq ball olasiz!
                                </li>
                                <li class="flex items-start gap-2">
                                    <CheckCircleIcon class="w-4 h-4 mt-0.5 flex-shrink-0 text-blue-500" />
                                    Ranked o'yinlarda ELO reytingingiz oshadi
                                </li>
                                <li class="flex items-start gap-2">
                                    <CheckCircleIcon class="w-4 h-4 mt-0.5 flex-shrink-0 text-blue-500" />
                                    Kunlik challenge'larni bajaring - bonus coin!
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Create Battle Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showCreateModal = false"></div>

                    <div class="relative bg-white dark:bg-gray-800 rounded-3xl p-6 max-w-2xl w-full shadow-2xl">
                        <button @click="showCreateModal = false"
                                class="absolute top-4 right-4 p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full">
                            <XMarkIcon class="w-6 h-6 text-gray-500" />
                        </button>

                        <div class="text-center mb-6">
                            <div class="w-16 h-16 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-full flex items-center justify-center mx-auto mb-4">
                                <PlusIcon class="w-8 h-8 text-white" />
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Battle Yaratish</h2>
                            <p class="text-gray-500 mt-1">Raqib turini tanlang!</p>
                        </div>

                        <div class="space-y-4">
                            <!-- Opponent Type Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Raqib turi
                                </label>
                                <div class="grid grid-cols-3 gap-3">
                                    <!-- AI Opponent (FREE) -->
                                    <button @click="createSettings.opponentType = 'ai'"
                                            class="p-3 rounded-xl border-2 text-center transition-all"
                                            :class="createSettings.opponentType === 'ai'
                                                ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20'
                                                : 'border-gray-200 dark:border-gray-700 hover:border-emerald-300'">
                                        <div class="w-10 h-10 mx-auto rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-xl mb-2">
                                            🤖
                                        </div>
                                        <p class="font-bold text-gray-900 dark:text-white text-sm">AI Raqib</p>
                                        <span class="inline-block mt-1 px-2 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-600 text-xs font-medium rounded-full">BEPUL</span>
                                    </button>

                                    <!-- Random Opponent -->
                                    <button @click="createSettings.opponentType = 'random'"
                                            class="p-3 rounded-xl border-2 text-center transition-all"
                                            :class="createSettings.opponentType === 'random'
                                                ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20'
                                                : 'border-gray-200 dark:border-gray-700 hover:border-emerald-300'">
                                        <div class="w-10 h-10 mx-auto rounded-full bg-gradient-to-br from-orange-500 to-red-500 flex items-center justify-center text-xl mb-2">
                                            🎲
                                        </div>
                                        <p class="font-bold text-gray-900 dark:text-white text-sm">Ixtiyoriy</p>
                                        <span class="inline-block mt-1 px-2 py-0.5 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 text-xs font-medium rounded-full">30 coin</span>
                                    </button>

                                    <!-- Friend Battle -->
                                    <button @click="createSettings.opponentType = 'friend'"
                                            class="p-3 rounded-xl border-2 text-center transition-all"
                                            :class="createSettings.opponentType === 'friend'
                                                ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20'
                                                : 'border-gray-200 dark:border-gray-700 hover:border-emerald-300'">
                                        <div class="w-10 h-10 mx-auto rounded-full bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center text-xl mb-2">
                                            👥
                                        </div>
                                        <p class="font-bold text-gray-900 dark:text-white text-sm">Do'st bilan</p>
                                        <span class="inline-block mt-1 px-2 py-0.5 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 text-xs font-medium rounded-full">30 coin</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Battle Name (only for friend/random) -->
                            <div v-if="createSettings.opponentType !== 'ai'">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Battle nomi (ixtiyoriy)
                                </label>
                                <input v-model="createSettings.name" type="text"
                                       placeholder="Masalan: Epic Grammar Battle"
                                       class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent" />
                            </div>

                            <!-- Difficulty Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Qiyinlik darajasi
                                </label>
                                <div class="grid grid-cols-2 gap-2">
                                    <button v-for="diff in difficulties" :key="diff.id"
                                            @click="createSettings.difficulty = diff.id"
                                            class="p-3 rounded-xl text-left transition-all border-2"
                                            :class="createSettings.difficulty === diff.id
                                                ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20'
                                                : 'border-gray-200 dark:border-gray-700 hover:border-emerald-300'">
                                        <div class="flex items-center gap-2">
                                            <span class="text-lg">{{ diff.icon }}</span>
                                            <div>
                                                <p class="font-medium text-gray-900 dark:text-white text-sm">{{ diff.name }}</p>
                                                <p class="text-xs text-gray-500">{{ diff.description }}</p>
                                            </div>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <!-- CEFR Level Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Daraja (CEFR Level)
                                </label>
                                <div class="flex flex-wrap gap-1.5">
                                    <button v-for="level in battleLevels" :key="level.id"
                                            @click="createSettings.level = level.id"
                                            class="px-3 py-1.5 rounded-lg text-center transition-all border-2 flex items-center gap-1"
                                            :class="createSettings.level === level.id
                                                ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20'
                                                : 'border-gray-200 dark:border-gray-700 hover:border-emerald-300'">
                                        <span class="text-sm">{{ level.icon }}</span>
                                        <span class="font-medium text-gray-900 dark:text-white text-xs">{{ level.id === 'mixed' ? 'Aralash' : level.id }}</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Rounds -->

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Raundlar soni
                                </label>
                                <div class="flex gap-2">
                                    <button v-for="rounds in [5, 10, 15]" :key="rounds"
                                            @click="createSettings.rounds = rounds"
                                            class="flex-1 py-3 rounded-xl font-medium transition-all"
                                            :class="createSettings.rounds === rounds
                                                ? 'bg-emerald-500 text-white'
                                                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'">
                                        {{ rounds }}
                                    </button>
                                </div>
                            </div>

                            <!-- Cost Warning (only for paid battles) -->
                            <div v-if="createSettings.opponentType !== 'ai'" class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl p-4 flex items-center gap-3">
                                <CurrencyDollarIcon class="w-8 h-8 text-yellow-500" />
                                <div>
                                    <p class="font-medium text-yellow-800 dark:text-yellow-200">Battle yaratish narxi: 30 coin</p>
                                    <p class="text-sm text-yellow-600 dark:text-yellow-400">Sizning balansingiz: {{ userCoins }} coin</p>
                                </div>
                            </div>

                            <!-- Free Info (only for AI) -->
                            <div v-else class="bg-green-50 dark:bg-green-900/20 rounded-xl p-4 flex items-center gap-3">
                                <SparklesIcon class="w-8 h-8 text-green-500" />
                                <div>
                                    <p class="font-medium text-green-800 dark:text-green-200">AI battle bepul!</p>
                                    <p class="text-sm text-green-600 dark:text-green-400">Mashq qiling va XP yig'ing. Coin olinmaydi va berilmaydi.</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex gap-3">
                            <button @click="showCreateModal = false"
                                    class="flex-1 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                Bekor qilish
                            </button>
                            <button @click="createBattle"
                                    :disabled="(createSettings.opponentType !== 'ai' && !canCreateBattle) || isCreating || isStartingAIBattle"
                                    class="flex-1 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-medium hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                <span v-if="isCreating || isStartingAIBattle">Yaratilmoqda...</span>
                                <span v-else-if="createSettings.opponentType === 'ai'">Boshlash (Bepul)</span>
                                <span v-else>Boshlash (30 coin)</span>
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Find Battle Modal (Raqib Topish) -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showFindBattleModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showFindBattleModal = false"></div>

                    <div class="relative bg-white dark:bg-gray-800 rounded-3xl p-8 max-w-lg w-full shadow-2xl max-h-[90vh] overflow-y-auto">
                        <button @click="showFindBattleModal = false"
                                class="absolute top-4 right-4 p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full">
                            <XMarkIcon class="w-6 h-6 text-gray-500" />
                        </button>

                        <div class="text-center mb-6">
                            <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-600 rounded-full flex items-center justify-center mx-auto mb-4">
                                <MagnifyingGlassIcon class="w-8 h-8 text-white" />
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Raqib Topish</h2>
                            <p class="text-gray-500 mt-1">Mavjud battle'lardan tanlang yoki yangi boshlang</p>
                        </div>

                        <!-- Available Battles List -->
                        <div class="space-y-4 mb-6">
                            <h3 class="font-medium text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                <FireIcon class="w-5 h-5 text-orange-500" />
                                Kutayotgan Battle'lar
                                <span v-if="activeBattles?.length" class="px-2 py-0.5 bg-red-100 dark:bg-red-900/30 text-red-600 text-xs rounded-full animate-pulse">
                                    {{ activeBattles.length }} ta
                                </span>
                            </h3>

                            <div v-if="activeBattles?.length" class="space-y-2 max-h-60 overflow-y-auto">
                                <div v-for="battle in activeBattles" :key="battle.id"
                                     class="p-4 bg-gradient-to-r from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20 rounded-xl border border-orange-200 dark:border-orange-800 hover:shadow-md transition-all">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-orange-400 to-red-500 flex items-center justify-center text-white font-bold">
                                                {{ battle.host_name?.[0]?.toUpperCase() || '?' }}
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-900 dark:text-white">{{ battle.name }}</p>
                                                <div class="flex items-center gap-2 text-xs text-gray-500">
                                                    <span>{{ battle.host_name }}</span>
                                                    <span>•</span>
                                                    <span>{{ battle.host_elo }} ELO</span>
                                                    <span>•</span>
                                                    <span class="capitalize">{{ battle.mode }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <button @click="joinActiveBattle(battle.id); showFindBattleModal = false"
                                                class="px-4 py-2 bg-gradient-to-r from-orange-500 to-red-500 text-white rounded-lg font-medium hover:shadow-lg transition-all">
                                            Qo'shilish
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="text-center py-8 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <div class="text-4xl mb-2">😴</div>
                                <p class="text-gray-500">Hozircha kutayotgan battle yo'q</p>
                                <p class="text-sm text-gray-400 mt-1">Yangi battle yarating yoki kod orqali qo'shiling</p>
                            </div>
                        </div>

                        <!-- Divider -->
                        <div class="relative my-6">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-200 dark:border-gray-700"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-4 bg-white dark:bg-gray-800 text-gray-500">yoki</span>
                            </div>
                        </div>

                        <!-- Join with Code -->
                        <div class="space-y-3">
                            <h3 class="font-medium text-gray-700 dark:text-gray-300">Kod orqali qo'shilish</h3>
                            <div class="flex gap-2">
                                <input v-model="battleCode" type="text"
                                       placeholder="XXXXXX"
                                       class="flex-1 px-4 py-3 text-center text-xl font-mono uppercase tracking-widest rounded-xl border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                       maxlength="6" />
                                <button @click="joinBattle(); showFindBattleModal = false"
                                        :disabled="battleCode.trim().length < 6"
                                        class="px-6 py-3 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-xl font-medium hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                    Kirish
                                </button>
                            </div>
                        </div>

                        <!-- Close Button -->
                        <button @click="showFindBattleModal = false"
                                class="w-full mt-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                            Yopish
                        </button>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Join Battle Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showJoinModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showJoinModal = false"></div>

                    <div class="relative bg-white dark:bg-gray-800 rounded-3xl p-8 max-w-md w-full shadow-2xl">
                        <button @click="showJoinModal = false"
                                class="absolute top-4 right-4 p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full">
                            <XMarkIcon class="w-6 h-6 text-gray-500" />
                        </button>

                        <div class="text-center mb-6">
                            <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                                <MagnifyingGlassIcon class="w-8 h-8 text-white" />
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Battle'ga Qo'shilish</h2>
                            <p class="text-gray-500 mt-1">Battle kodini kiriting</p>
                        </div>

                        <div class="space-y-4">
                            <input v-model="battleCode" type="text"
                                   placeholder="XXXXXX"
                                   class="w-full px-4 py-4 text-center text-2xl font-mono uppercase tracking-widest rounded-xl border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                   maxlength="6" />

                            <p class="text-center text-sm text-gray-500">
                                Battle kodini do'stingizdan so'rang
                            </p>
                        </div>

                        <div class="mt-6 flex gap-3">
                            <button @click="showJoinModal = false"
                                    class="flex-1 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                Bekor qilish
                            </button>
                            <button @click="joinBattle"
                                    :disabled="battleCode.trim().length < 6"
                                    class="flex-1 py-3 bg-gradient-to-r from-orange-500 to-red-600 text-white rounded-xl font-medium hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                Qo'shilish
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </StudentLayout>
</template>

<style scoped>
.slide-fade-enter-active,
.slide-fade-leave-active {
    transition: all 0.3s ease;
}
.slide-fade-enter-from,
.slide-fade-leave-to {
    transform: translateY(-10px);
    opacity: 0;
}

.scale-fade-enter-active,
.scale-fade-leave-active {
    transition: all 0.4s ease;
}
.scale-fade-enter-from,
.scale-fade-leave-to {
    transform: scale(0.95);
    opacity: 0;
}

.modal-enter-active,
.modal-leave-active {
    transition: all 0.3s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
.modal-enter-from .relative,
.modal-leave-to .relative {
    transform: scale(0.9) translateY(20px);
}
</style>
