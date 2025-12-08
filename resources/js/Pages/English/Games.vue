<script setup>
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import {
    PuzzlePieceIcon,
    TrophyIcon,
    StarIcon,
    PlayIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    games: { type: Array, default: () => [] },
    highScores: Object,
})

const gameCards = [
    {
        id: 1,
        name: 'Word Scramble',
        game_type: 'word_scramble',
        icon: '🔀',
        description: 'Unscramble letters to form words',
        color: 'from-blue-500 to-blue-600',
        difficulty: 'Easy'
    },
    {
        id: 2,
        name: 'Word Match',
        game_type: 'word_match',
        icon: '🎯',
        description: 'Match words with translations',
        color: 'from-purple-500 to-purple-600',
        difficulty: 'Easy'
    },
    {
        id: 3,
        name: 'Spelling Bee',
        game_type: 'spelling_bee',
        icon: '🐝',
        description: 'Listen and spell correctly',
        color: 'from-yellow-500 to-orange-500',
        difficulty: 'Medium'
    },
    {
        id: 4,
        name: 'Hangman',
        game_type: 'hangman',
        icon: '☠️',
        description: 'Guess the word letter by letter',
        color: 'from-red-500 to-red-600',
        difficulty: 'Medium'
    },
]

const playGame = async (game) => {
    try {
        // Start game via API
        const response = await axios.post(`/api/v1/english/games/${game.id}/start`, {
            level: 1,
        })

        router.visit(`/english/games/${game.id}/play`)
    } catch (error) {
        console.error('Failed to start game:', error)
        // Navigate anyway for demo
        router.visit(`/english/games/${game.id}/play`)
    }
}
</script>

<template>

    <Head title="Ingliz tili - O'yinlar" />

    <StudentLayout>
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="text-center">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center justify-center">
                    <PuzzlePieceIcon class="w-8 h-8 text-green-500 mr-2" />
                    So'z o'yinlari
                </h1>
                <p class="text-gray-600 dark:text-gray-400">O'yin orqali so'zlarni mashq qiling!</p>
            </div>

            <!-- Games Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div v-for="game in gameCards" :key="game.id"
                    class="bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-lg hover:shadow-xl transition-all group cursor-pointer"
                    @click="playGame(game)">
                    <!-- Game Header -->
                    <div class="p-6 text-white bg-gradient-to-br" :class="game.color">
                        <div class="flex items-center justify-between">
                            <span class="text-4xl">{{ game.icon }}</span>
                            <span class="px-3 py-1 bg-white/20 rounded-full text-sm">
                                {{ game.difficulty }}
                            </span>
                        </div>
                        <h3 class="text-xl font-bold mt-4">{{ game.name }}</h3>
                        <p class="text-white/80 text-sm">{{ game.description }}</p>
                    </div>

                    <!-- Game Stats -->
                    <div class="p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="flex items-center text-gray-500">
                                    <TrophyIcon class="w-5 h-5 mr-1 text-yellow-500" />
                                    <span>{{ highScores?.[game.game_type] || 0 }}</span>
                                </div>
                                <div class="flex items-center text-gray-500">
                                    <StarIcon class="w-5 h-5 mr-1 text-purple-500" />
                                    <span>{{ game.best_stars || 0 }}/3</span>
                                </div>
                            </div>

                            <button
                                class="flex items-center px-4 py-2 bg-gradient-to-r rounded-xl text-white font-medium group-hover:scale-105 transition-transform"
                                :class="game.color">
                                <PlayIcon class="w-5 h-5 mr-1" />
                                Play
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daily Challenge -->
            <div class="mt-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-bold">🎯 Daily Challenge</h3>
                        <p class="text-white/80">Complete all 4 games today for bonus XP!</p>
                        <div class="flex items-center mt-3 space-x-2">
                            <span class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">✓</span>
                            <span class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">✓</span>
                            <span class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center">3</span>
                            <span class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center">4</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-3xl font-bold">+100</p>
                        <p class="text-sm text-white/80">Bonus XP</p>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
