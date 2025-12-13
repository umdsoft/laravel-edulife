<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import {
    PuzzlePieceIcon,
    TrophyIcon,
    StarIcon,
    PlayIcon,
    SparklesIcon,
    AcademicCapIcon,
    BoltIcon,
    FireIcon,
    LockClosedIcon,
} from '@heroicons/vue/24/outline'
import { CheckCircleIcon } from '@heroicons/vue/24/solid'

const props = defineProps({
    games: { type: Array, default: () => [] },
    highScores: Object,
    stats: Object,
})

// Color palette for game cards
const colorPalettes = [
    { bg: 'from-blue-500 to-indigo-600', shadow: 'shadow-blue-500/25', ring: 'ring-blue-400' },
    { bg: 'from-purple-500 to-pink-600', shadow: 'shadow-purple-500/25', ring: 'ring-purple-400' },
    { bg: 'from-emerald-500 to-teal-600', shadow: 'shadow-emerald-500/25', ring: 'ring-emerald-400' },
    { bg: 'from-orange-500 to-red-600', shadow: 'shadow-orange-500/25', ring: 'ring-orange-400' },
    { bg: 'from-cyan-500 to-blue-600', shadow: 'shadow-cyan-500/25', ring: 'ring-cyan-400' },
    { bg: 'from-rose-500 to-pink-600', shadow: 'shadow-rose-500/25', ring: 'ring-rose-400' },
    { bg: 'from-amber-500 to-orange-600', shadow: 'shadow-amber-500/25', ring: 'ring-amber-400' },
    { bg: 'from-violet-500 to-purple-600', shadow: 'shadow-violet-500/25', ring: 'ring-violet-400' },
    { bg: 'from-lime-500 to-green-600', shadow: 'shadow-lime-500/25', ring: 'ring-lime-400' },
]

// Game benefits
const benefits = [
    { icon: AcademicCapIcon, title: "So'z boyligini oshiring", desc: "Har kuni yangi so'zlar o'rganing" },
    { icon: BoltIcon, title: "Tez fikrlashni rivojlantiring", desc: "Vaqt chegarasida o'ynang" },
    { icon: FireIcon, title: "Streak'ingizni saqlang", desc: "Har kuni o'ynab XP to'plang" },
    { icon: TrophyIcon, title: "Yutuqlarga erishing", desc: "Leaderboard'da o'rin oling" },
]

// Categories
const categories = ref([
    { id: 'all', name: "Barcha o'yinlar", icon: '🎮' },
    { id: 'vocabulary', name: "So'z boyligini oshirish", icon: '📚' },
    { id: 'grammar', name: "Grammatika", icon: '✍️' },
    { id: 'speed', name: "Tezlik", icon: '⚡' },
])

const activeCategory = ref('all')

// Use games from props with color assignment
const gameCards = computed(() => {
    const gamesData = props.games && props.games.length > 0 ? props.games : [
        { id: 'word_scramble', code: 'word_scramble', name: 'Word Scramble', icon: '🔀', description: "Unscramble the letters", category: 'vocabulary' },
        { id: 'word_match', code: 'word_match', name: 'Word Match', icon: '🎯', description: "Match words with meanings", category: 'vocabulary' },
        { id: 'hangman', code: 'hangman', name: 'Hangman', icon: '☠️', description: "Guess the word", category: 'vocabulary' },
        { id: 'spelling_bee', code: 'spelling_bee', name: 'Spelling Bee', icon: '🐝', description: "Listen and spell", category: 'vocabulary' },
        { id: 'memory_cards', code: 'memory_cards', name: 'Memory Cards', icon: '🃏', description: "Find matching pairs", category: 'vocabulary' },
        { id: 'speed_vocab', code: 'speed_vocab', name: 'Speed Vocab', icon: '⚡', description: "Race against time", category: 'speed' },
    ]
    
    // Detect category from game code
    const getCategory = (code) => {
        if (!code) return 'vocabulary'
        const speedGames = ['speed_vocab', 'typing_race', 'speed_reading', 'word_blitz']
        const grammarGames = ['grammar_quiz', 'sentence_builder', 'fill_in_blanks', 'verb_conjugation', 'error_correction', 'article', 'preposition', 'tense_transformation', 'active_passive', 'conditional', 'modal_verbs', 'punctuation', 'direct_indirect', 'comparative_superlative', 'irregular_verbs', 'sentence_transform', 'question_formation', 'collocations', 'phrasal_verb', 'idiom_quiz']
        if (speedGames.includes(code)) return 'speed'
        if (grammarGames.includes(code)) return 'grammar'
        return 'vocabulary'
    }
    
    return gamesData.map((game, index) => ({
        ...game,
        colors: colorPalettes[index % colorPalettes.length],
        displayName: game.name, // Always use English name
        category: game.category || getCategory(game.code),
        isLocked: game.is_premium && !props.stats?.isPremium,
        stars: game.best_stars || 0,
        highScore: props.highScores?.[game.code] || 0,
    }))
})

const filteredGames = computed(() => {
    if (activeCategory.value === 'all') return gameCards.value
    return gameCards.value.filter(g => g.category === activeCategory.value)
})

// Game description helper
const getGameDescription = (code) => {
    const descriptions = {
        word_scramble: "Rearrange jumbled letters to form correct words",
        word_match: "Match English words with their meanings",
        hangman: "Guess the hidden word letter by letter",
        spelling_bee: "Listen to words and spell them correctly",
        memory_cards: "Find matching word pairs in a memory game",
        speed_vocab: "Answer as fast as you can before time runs out",
        grammar_quiz: "Test your grammar knowledge with quizzes",
        sentence_builder: "Build correct sentences from word blocks",
        fill_in_blanks: "Complete sentences with correct words",
        typing_race: "Type words as fast as possible",
    }
    return descriptions[code] || "Practice and improve your English skills"
}

// Skill tags helper
const getSkillTags = (code) => {
    const tagMap = {
        word_scramble: ['Vocabulary', 'Spelling'],
        word_match: ['Vocabulary', 'Memory'],
        hangman: ['Vocabulary', 'Spelling'],
        spelling_bee: ['Listening', 'Spelling'],
        memory_cards: ['Memory', 'Vocabulary'],
        speed_vocab: ['Speed', 'Vocabulary'],
        grammar_quiz: ['Grammar', 'Quiz'],
        sentence_builder: ['Grammar', 'Writing'],
        fill_in_blanks: ['Grammar', 'Context'],
        typing_race: ['Speed', 'Typing'],
        vocabulary_quiz: ['Vocabulary', 'Quiz'],
        error_correction: ['Grammar', 'Proofreading'],
    }
    return tagMap[code] || ['Vocabulary']
}

const playGame = (game) => {
    if (game.isLocked) return
    // Use specific route_url if available, otherwise fallback to generic route
    const route = game.route_url || `/student/english/games/${game.id}/play`
    router.visit(route)
}
</script>

<template>
    <Head title="Ingliz tili - O'yinlar" />

    <StudentLayout>
        <div class="space-y-8">
            <!-- Hero Section -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-violet-600 via-purple-600 to-indigo-600 p-8 lg:p-12">
                <!-- Background decorations -->
                <div class="absolute inset-0 overflow-hidden">
                    <div class="absolute -top-24 -right-24 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
                    <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-[200px] opacity-5">🎮</div>
                </div>
                
                <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div class="text-white">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="px-3 py-1 bg-white/20 rounded-full text-sm font-medium backdrop-blur-sm">
                                ✨ O'yin orqali o'rganing
                            </span>
                        </div>
                        <h1 class="text-3xl lg:text-4xl font-bold mb-3">
                            Ingliz tilini <span class="text-yellow-300">o'yin</span> orqali o'rganing!
                        </h1>
                        <p class="text-white/80 text-lg max-w-xl">
                            Qiziqarli o'yinlar bilan so'z boyligingizni oshiring, grammatikani mustahkamlang va 
                            ingliz tilini <strong>tez</strong> hamda <strong>samarali</strong> o'rganing.
                        </p>
                        
                        <!-- Stats -->
                        <div class="flex flex-wrap gap-6 mt-6">
                            <div class="text-center">
                                <p class="text-3xl font-bold text-yellow-300">{{ gameCards.length }}+</p>
                                <p class="text-white/70 text-sm">O'yinlar</p>
                            </div>
                            <div class="text-center">
                                <p class="text-3xl font-bold text-green-300">{{ stats?.gamesPlayed || 0 }}</p>
                                <p class="text-white/70 text-sm">O'ynalgan</p>
                            </div>
                            <div class="text-center">
                                <p class="text-3xl font-bold text-cyan-300">{{ stats?.totalXp || 0 }}</p>
                                <p class="text-white/70 text-sm">XP yig'ildi</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- CTA Button -->
                    <div class="flex-shrink-0">
                        <button @click="playGame(gameCards[0])" 
                            class="group flex items-center gap-3 px-8 py-4 bg-white text-purple-600 rounded-2xl font-bold text-lg shadow-xl shadow-purple-900/30 hover:shadow-2xl hover:scale-105 transition-all duration-300">
                            <PlayIcon class="w-6 h-6 group-hover:scale-110 transition-transform" />
                            Boshlash
                        </button>
                    </div>
                </div>
            </div>

            <!-- Benefits Section -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div v-for="benefit in benefits" :key="benefit.title"
                    class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 hover:border-purple-200 dark:hover:border-purple-800 transition-colors">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center mb-3">
                        <component :is="benefit.icon" class="w-6 h-6 text-white" />
                    </div>
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-1">{{ benefit.title }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ benefit.desc }}</p>
                </div>
            </div>

            <!-- Category Tabs -->
            <div class="flex flex-wrap gap-2">
                <button v-for="cat in categories" :key="cat.id"
                    @click="activeCategory = cat.id"
                    class="px-4 py-2 rounded-xl font-medium transition-all duration-200"
                    :class="activeCategory === cat.id 
                        ? 'bg-purple-600 text-white shadow-lg shadow-purple-500/30' 
                        : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700'">
                    <span class="mr-1">{{ cat.icon }}</span>
                    {{ cat.name }}
                </button>
            </div>

            <!-- Games Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                <div v-for="game in filteredGames" :key="game.id"
                    @click="playGame(game)"
                    class="group relative bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 cursor-pointer"
                    :class="[game.colors.shadow, game.isLocked ? 'opacity-75' : 'hover:-translate-y-1']">
                    
                    <!-- Gradient Header -->
                    <div class="relative p-5 text-white bg-gradient-to-br" :class="game.colors.bg">
                        <!-- Lock overlay -->
                        <div v-if="game.isLocked" class="absolute inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-10">
                            <div class="text-center">
                                <LockClosedIcon class="w-8 h-8 mx-auto mb-2" />
                                <span class="text-sm font-medium">Premium</span>
                            </div>
                        </div>
                        
                        <!-- Icon & Badge -->
                        <div class="flex items-start justify-between mb-3">
                            <span class="text-4xl filter drop-shadow-lg group-hover:scale-110 transition-transform duration-300">
                                {{ game.icon }}
                            </span>
                            <div class="flex flex-col items-end gap-1">
                                <span v-if="!game.isLocked" class="px-2 py-0.5 bg-white/20 backdrop-blur-sm rounded-full text-xs font-medium">
                                    {{ game.is_premium ? '👑 Premium' : '✨ Free' }}
                                </span>
                                <span class="px-2 py-0.5 bg-black/20 rounded-full text-xs">
                                    {{ game.totalLevels || 10 }} levels
                                </span>
                            </div>
                        </div>
                        
                        <!-- Title & Description -->
                        <h3 class="text-lg font-bold mb-1 group-hover:translate-x-1 transition-transform">
                            {{ game.displayName }}
                        </h3>
                        <p class="text-white/80 text-sm line-clamp-2 min-h-[40px]">
                            {{ game.description || getGameDescription(game.code) }}
                        </p>
                        
                        <!-- Skill Tags -->
                        <div class="flex flex-wrap gap-1 mt-3">
                            <span v-for="skill in getSkillTags(game.code)" :key="skill"
                                class="px-2 py-0.5 bg-white/15 rounded text-xs">
                                {{ skill }}
                            </span>
                        </div>
                    </div>

                    <!-- Stats & Progress Section -->
                    <div class="p-4 space-y-3">
                        <!-- Level Progress -->
                        <div>
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="text-gray-600 dark:text-gray-400">Bosqich</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ game.currentLevel || 1 }} / {{ game.totalLevels || 10 }}
                                </span>
                            </div>
                            <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full rounded-full bg-gradient-to-r transition-all duration-500"
                                    :class="game.colors.bg"
                                    :style="{ width: `${((game.currentLevel || 1) / (game.totalLevels || 10)) * 100}%` }">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Stats Row -->
                        <div class="flex items-center justify-between text-sm">
                            <!-- Stars -->
                            <div class="flex items-center gap-0.5">
                                <template v-for="i in 3" :key="i">
                                    <StarIcon v-if="i <= game.stars" class="w-4 h-4 text-yellow-400 fill-yellow-400" />
                                    <StarIcon v-else class="w-4 h-4 text-gray-300 dark:text-gray-600" />
                                </template>
                            </div>
                            
                            <!-- High Score -->
                            <div class="flex items-center gap-3 text-gray-500 dark:text-gray-400">
                                <div v-if="game.highScore > 0" class="flex items-center">
                                    <TrophyIcon class="w-4 h-4 mr-1 text-yellow-500" />
                                    <span>{{ game.highScore }}</span>
                                </div>
                                <div class="flex items-center">
                                    <SparklesIcon class="w-4 h-4 mr-1 text-purple-500" />
                                    <span>+{{ game.xpReward || 50 }} XP</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Times Played -->
                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-2 border-t border-gray-100 dark:border-gray-700">
                            <span>{{ game.timesPlayed || 0 }} marta o'ynalgan</span>
                            <span v-if="game.lastPlayed" class="text-gray-400">
                                Oxirgi: {{ game.lastPlayed }}
                            </span>
                        </div>

                        <!-- Play Button -->
                        <button 
                            :disabled="game.isLocked"
                            class="w-full flex items-center justify-center gap-2 py-3 rounded-xl font-semibold text-white transition-all duration-300 bg-gradient-to-r disabled:opacity-50 disabled:cursor-not-allowed"
                            :class="[game.colors.bg, !game.isLocked ? 'hover:shadow-lg group-hover:scale-[1.02]' : '']">
                            <PlayIcon class="w-5 h-5" />
                            {{ game.isLocked ? 'Premium kerak' : game.currentLevel > 1 ? 'Davom etish' : "O'ynash" }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Daily Challenge Banner -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-amber-500 via-orange-500 to-red-500 p-6 lg:p-8">
                <div class="absolute inset-0 overflow-hidden">
                    <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
                </div>
                
                <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div class="text-white">
                        <div class="flex items-center gap-2 mb-2">
                            <FireIcon class="w-6 h-6 text-yellow-300" />
                            <span class="font-bold text-lg">Kunlik Challenge</span>
                        </div>
                        <p class="text-white/90 mb-4">Bugun 3 ta turli o'yin o'ynab <strong>+100 bonus XP</strong> yutib oling!</p>
                        
                        <div class="flex items-center gap-2">
                            <div v-for="i in 3" :key="i" 
                                class="w-10 h-10 rounded-full flex items-center justify-center font-bold"
                                :class="i <= 1 ? 'bg-green-400 text-green-900' : 'bg-white/20 text-white'">
                                <CheckCircleIcon v-if="i <= 1" class="w-6 h-6" />
                                <span v-else>{{ i }}</span>
                            </div>
                            <span class="text-white/80 ml-2">1/3 bajarildi</span>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-4">
                        <div class="text-center text-white">
                            <p class="text-4xl font-bold text-yellow-300">+100</p>
                            <p class="text-sm text-white/80">Bonus XP</p>
                        </div>
                        <button class="px-6 py-3 bg-white text-orange-600 rounded-xl font-bold hover:bg-yellow-50 hover:scale-105 transition-all shadow-lg">
                            Davom etish
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
