<script setup>
import { ref, computed, onMounted, shallowRef } from 'vue'
import { router } from '@inertiajs/vue3'
import { useTimer } from '@/Composables/useTimer'
import { useAudio } from '@/Composables/useAudio'
import { XMarkIcon, ClockIcon, StarIcon } from '@heroicons/vue/24/solid'
import confetti from 'canvas-confetti'

// ============================================
// ACTIVE GAMES - 61 ta (Audio-ga bog'liq emaslar)
// ============================================

// === VOCABULARY GAMES ===
import WordScrambleGame from '@/Components/English/Games/WordScrambleGame.vue'
import WordMatchGame from '@/Components/English/Games/WordMatchGame.vue'
import HangmanGame from '@/Components/English/Games/HangmanGame.vue'
import WordSearchGame from '@/Components/English/Games/WordSearchGame.vue'
import MemoryCardsGame from '@/Components/English/Games/MemoryCardsGame.vue'
import SpeedVocabGame from '@/Components/English/Games/SpeedVocabGame.vue'
import TypingRaceGame from '@/Components/English/Games/TypingRaceGame.vue'
import CrosswordGame from '@/Components/English/Games/CrosswordGame.vue'
import FlashcardReviewGame from '@/Components/English/Games/FlashcardReviewGame.vue'
import SynonymAntonymGame from '@/Components/English/Games/SynonymAntonymGame.vue'
import WordAssociationGame from '@/Components/English/Games/WordAssociationGame.vue'
import PictureWordGame from '@/Components/English/Games/PictureWordGame.vue'
import WordLadderGame from '@/Components/English/Games/WordLadderGame.vue'
import AnagramHuntGame from '@/Components/English/Games/AnagramHuntGame.vue'
import WordChainGame from '@/Components/English/Games/WordChainGame.vue'
import RhymeTimeGame from '@/Components/English/Games/RhymeTimeGame.vue'
import CompoundWordsGame from '@/Components/English/Games/CompoundWordsGame.vue'
import PrefixSuffixGame from '@/Components/English/Games/PrefixSuffixGame.vue'
import ContextCluesGame from '@/Components/English/Games/ContextCluesGame.vue'
import WordDetectiveGame from '@/Components/English/Games/WordDetectiveGame.vue'
import WordWheelGame from '@/Components/English/Games/WordWheelGame.vue'
import VocabularyPyramidGame from '@/Components/English/Games/VocabularyPyramidGame.vue'
import WordEvolutionGame from '@/Components/English/Games/WordEvolutionGame.vue'
import WordMemoryGame from '@/Components/English/Games/WordMemoryGame.vue'
import WordAssociationChainGame from '@/Components/English/Games/WordAssociationChainGame.vue'
import WordFamilyTreeGame from '@/Components/English/Games/WordFamilyTreeGame.vue'

// === GRAMMAR GAMES ===
import SentenceBuilderGame from '@/Components/English/Games/SentenceBuilderGame.vue'
import GrammarQuizGame from '@/Components/English/Games/GrammarQuizGame.vue'
import FillInBlanksGame from '@/Components/English/Games/FillInBlanksGame.vue'
import VerbConjugationGame from '@/Components/English/Games/VerbConjugationGame.vue'
import IdiomQuizGame from '@/Components/English/Games/IdiomQuizGame.vue'
import PhrasalVerbGame from '@/Components/English/Games/PhrasalVerbGame.vue'
import ErrorCorrectionGame from '@/Components/English/Games/ErrorCorrectionGame.vue'
import QuestionFormationGame from '@/Components/English/Games/QuestionFormationGame.vue'
import CollocationsGame from '@/Components/English/Games/CollocationsGame.vue'
import PrepositionGame from '@/Components/English/Games/PrepositionGame.vue'
import ArticleGame from '@/Components/English/Games/ArticleGame.vue'
import TenseTransformationGame from '@/Components/English/Games/TenseTransformationGame.vue'
import ActivePassiveGame from '@/Components/English/Games/ActivePassiveGame.vue'
import DirectIndirectSpeechGame from '@/Components/English/Games/DirectIndirectSpeechGame.vue'
import ConditionalGame from '@/Components/English/Games/ConditionalGame.vue'
import ModalVerbsGame from '@/Components/English/Games/ModalVerbsGame.vue'
import ComparativeSuperlativeGame from '@/Components/English/Games/ComparativeSuperlativeGame.vue'
import IrregularVerbsGame from '@/Components/English/Games/IrregularVerbsGame.vue'
import SentenceTransformGame from '@/Components/English/Games/SentenceTransformGame.vue'
import PunctuationGame from '@/Components/English/Games/PunctuationGame.vue'

// === READING GAMES ===
import ReadingComprehensionGame from '@/Components/English/Games/ReadingComprehensionGame.vue'
import DialogueBuilderGame from '@/Components/English/Games/DialogueBuilderGame.vue'
import StoryCompletionGame from '@/Components/English/Games/StoryCompletionGame.vue'
import SpeedReadingGame from '@/Components/English/Games/SpeedReadingGame.vue'

// === WRITING GAMES ===
import TimedTranslationGame from '@/Components/English/Games/TimedTranslationGame.vue'
import WordCategoriesGame from '@/Components/English/Games/WordCategoriesGame.vue'

// === GAME-BASED (Gamification) ===
import SentenceAuctionGame from '@/Components/English/Games/SentenceAuctionGame.vue'
import WordTetrisGame from '@/Components/English/Games/WordTetrisGame.vue'
import GrammarRaceGame from '@/Components/English/Games/GrammarRaceGame.vue'
import WordMazeGame from '@/Components/English/Games/WordMazeGame.vue'
import SentenceSnakeGame from '@/Components/English/Games/SentenceSnakeGame.vue'
import WordDefenseGame from '@/Components/English/Games/WordDefenseGame.vue'
import GrammarDuelGame from '@/Components/English/Games/GrammarDuelGame.vue'
import VocabularyBattleGame from '@/Components/English/Games/VocabularyBattleGame.vue'
import SentenceJigsawGame from '@/Components/English/Games/SentenceJigsawGame.vue'

// ============================================
// DISABLED GAMES (9 ta) - Audio tayyor bo'lganda yoqiladi
// ============================================
// - SpellingBeeGame
// - DictationGame
// - AudioMatchGame
// - ListeningComprehensionGame
// - PronunciationGame
// - MinimalPairsGame
// - WordStressGame
// - WordBingoGame
// - ListeningChallengeGame

const props = defineProps({
    game: Object,
    level: Object,
    content: Object,
    attempt: Object,
})

// All 61 active game components mapping
const gameComponents = {
    // === VOCABULARY GAMES (26) ===
    word_scramble: WordScrambleGame,
    word_match: WordMatchGame,
    hangman: HangmanGame,
    word_search: WordSearchGame,
    memory_cards: MemoryCardsGame,
    speed_vocab: SpeedVocabGame,
    typing_race: TypingRaceGame,
    crossword: CrosswordGame,
    flashcard_review: FlashcardReviewGame,
    synonym_antonym: SynonymAntonymGame,
    word_association: WordAssociationGame,
    picture_word: PictureWordGame,
    word_ladder: WordLadderGame,
    anagram_hunt: AnagramHuntGame,
    word_chain: WordChainGame,
    rhyme_time: RhymeTimeGame,
    compound_words: CompoundWordsGame,
    prefix_suffix: PrefixSuffixGame,
    context_clues: ContextCluesGame,
    word_detective: WordDetectiveGame,
    word_wheel: WordWheelGame,
    vocabulary_pyramid: VocabularyPyramidGame,
    word_evolution: WordEvolutionGame,
    word_memory: WordMemoryGame,
    word_association_chain: WordAssociationChainGame,
    word_family_tree: WordFamilyTreeGame,

    // === GRAMMAR GAMES (18) ===
    sentence_builder: SentenceBuilderGame,
    grammar_quiz: GrammarQuizGame,
    fill_in_blanks: FillInBlanksGame,
    verb_conjugation: VerbConjugationGame,
    idiom_quiz: IdiomQuizGame,
    phrasal_verb: PhrasalVerbGame,
    error_correction: ErrorCorrectionGame,
    question_formation: QuestionFormationGame,
    collocations: CollocationsGame,
    preposition: PrepositionGame,
    article: ArticleGame,
    tense_transformation: TenseTransformationGame,
    active_passive: ActivePassiveGame,
    direct_indirect: DirectIndirectSpeechGame,
    conditional: ConditionalGame,
    modal_verbs: ModalVerbsGame,
    comparative_superlative: ComparativeSuperlativeGame,
    irregular_verbs: IrregularVerbsGame,
    sentence_transform: SentenceTransformGame,
    punctuation: PunctuationGame,

    // === READING GAMES (4) ===
    reading_comprehension: ReadingComprehensionGame,
    dialogue_builder: DialogueBuilderGame,
    story_completion: StoryCompletionGame,
    speed_reading: SpeedReadingGame,

    // === WRITING GAMES (2) ===
    timed_translation: TimedTranslationGame,
    word_categories: WordCategoriesGame,

    // === GAME-BASED (11) ===
    sentence_auction: SentenceAuctionGame,
    word_tetris: WordTetrisGame,
    grammar_race: GrammarRaceGame,
    word_maze: WordMazeGame,
    sentence_snake: SentenceSnakeGame,
    word_defense: WordDefenseGame,
    grammar_duel: GrammarDuelGame,
    vocabulary_battle: VocabularyBattleGame,
    sentence_jigsaw: SentenceJigsawGame,
}

const { playWin } = useAudio()

const currentComponent = shallowRef(gameComponents[props.game?.code])

const score = ref(0)
const correctCount = ref(0)
const streak = ref(0)
const isComplete = ref(false)
const results = ref(null)

const { time, isWarning, formatTime, start, stop } = useTimer({
    initialTime: props.level?.time_limit || 120,
    countdown: true,
    onComplete: () => completeGame(),
})

const handleScore = (points) => {
    score.value += points
}

const handleCorrect = () => {
    correctCount.value++
    streak.value++
}

const handleIncorrect = () => {
    streak.value = 0
}

const completeGame = async () => {
    stop()
    isComplete.value = true
    
    const percentage = Math.min(100, Math.round((score.value / (props.level?.min_score || 100)) * 100))
    const stars = percentage >= 95 ? 3 : percentage >= 80 ? 2 : percentage >= 60 ? 1 : 0
    
    results.value = {
        score: score.value,
        correct: correctCount.value,
        time_spent: (props.level?.time_limit || 120) - time.value,
        percentage,
        stars,
        xp_earned: props.level?.xp_reward || 20,
        coins_earned: props.level?.coin_reward || 5,
    }
    
    if (stars >= 2) {
        playWin()
        confetti({ particleCount: 100, spread: 70, origin: { y: 0.6 } })
    }
    
    try {
        await axios.post(`/api/v1/english/games/attempts/${props.attempt?.id}/submit`, results.value)
    } catch (error) {
        console.error('Failed to submit game results:', error)
    }
}

const exitGame = () => {
    if (confirm('Are you sure you want to exit?')) {
        router.visit('/student/english/games')
    }
}

onMounted(() => {
    start()
})
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-600 to-purple-700 flex flex-col">
        <!-- Header -->
        <header class="fixed top-0 left-0 right-0 z-50 bg-black/20 backdrop-blur-sm">
            <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between">
                <button @click="exitGame" class="p-2 rounded-lg hover:bg-white/10 transition-colors">
                    <XMarkIcon class="w-6 h-6 text-white" />
                </button>
                
                <div class="flex items-center space-x-4">
                    <!-- Streak -->
                    <div v-if="streak >= 3" class="flex items-center px-3 py-1 bg-amber-500 rounded-full shadow-lg shadow-amber-500/30">
                        <span class="text-lg">🔥</span>
                        <span class="ml-1 font-bold text-white">{{ streak }}</span>
                    </div>
                    
                    <!-- Score -->
                    <div class="px-4 py-1 bg-white/20 rounded-full">
                        <span class="text-white font-bold">{{ score }}</span>
                    </div>
                    
                    <!-- Timer -->
                    <div class="flex items-center space-x-1 px-4 py-1 rounded-full transition-all"
                        :class="isWarning ? 'bg-red-500 animate-pulse' : 'bg-white/20'">
                        <ClockIcon class="w-5 h-5 text-white" />
                        <span class="text-white font-mono">{{ formatTime(time) }}</span>
                    </div>
                    
                    <!-- Correct count -->
                    <div class="px-4 py-1 bg-green-500/50 rounded-full">
                        <span class="text-white font-bold">✓ {{ correctCount }}</span>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Game Content -->
        <main class="flex-1 pt-20 pb-8">
            <div v-if="!isComplete" class="max-w-4xl mx-auto px-4">
                <component
                    :is="currentComponent"
                    :content="content"
                    :level="level"
                    @score="handleScore"
                    @correct="handleCorrect"
                    @incorrect="handleIncorrect"
                    @complete="completeGame"
                />
            </div>
            
            <!-- Results Screen -->
            <div v-else class="max-w-md mx-auto px-4 text-center">
                <div class="bg-white rounded-3xl p-8 shadow-xl">
                    <h1 class="text-3xl font-bold text-gray-900 mb-4">
                        {{ results?.stars >= 2 ? '🎉 Great Job!' : '💪 Good Try!' }}
                    </h1>
                    
                    <div class="flex justify-center space-x-2 mb-6">
                        <StarIcon v-for="i in 3" :key="i" class="w-12 h-12 transition-all"
                            :class="i <= results?.stars ? 'text-yellow-400' : 'text-gray-300'" />
                    </div>
                    
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="text-center">
                            <p class="text-3xl font-bold text-blue-600">{{ results?.score }}</p>
                            <p class="text-sm text-gray-500">Score</p>
                        </div>
                        <div class="text-center">
                            <p class="text-3xl font-bold text-green-600">{{ results?.correct }}</p>
                            <p class="text-sm text-gray-500">Correct</p>
                        </div>
                        <div class="text-center">
                            <p class="text-3xl font-bold text-purple-600">{{ formatTime(results?.time_spent) }}</p>
                            <p class="text-sm text-gray-500">Time</p>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 rounded-xl p-4 mb-6">
                        <p class="text-sm text-gray-500 mb-2">Rewards</p>
                        <div class="flex justify-center space-x-6">
                            <span class="text-purple-600 font-bold">+{{ results?.xp_earned }} XP</span>
                            <span class="text-yellow-600 font-bold">+{{ results?.coins_earned }} 🪙</span>
                        </div>
                    </div>
                    
                    <div class="flex space-x-3">
                        <button @click="router.reload()"
                            class="flex-1 py-3 bg-gray-100 rounded-xl font-medium text-gray-600 hover:bg-gray-200 transition-colors">
                            Play Again
                        </button>
                        <button @click="router.visit('/student/english/games')"
                            class="flex-1 py-3 bg-blue-600 rounded-xl font-medium text-white hover:bg-blue-700 transition-colors">
                            Continue
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
