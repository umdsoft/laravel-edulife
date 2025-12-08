<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import { useAudio } from '@/Composables/useAudio'
import { SpeakerWaveIcon, CheckIcon, XMarkIcon, ArrowPathIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    wordsForReview: { type: Array, default: () => [] },
    profile: Object,
})

const audio = useAudio()

const currentIndex = ref(0)
const isFlipped = ref(false)
const isReviewing = ref(false)
const reviewedCount = ref(0)

const currentWord = computed(() => props.wordsForReview[currentIndex.value])
const isComplete = computed(() => currentIndex.value >= props.wordsForReview.length)
const progress = computed(() => ((currentIndex.value) / props.wordsForReview.length) * 100)

// SM-2 Quality Ratings
const qualityRatings = [
    { value: 0, label: 'Forgot', color: 'bg-red-500', description: 'Total blackout' },
    { value: 1, label: 'Wrong', color: 'bg-red-400', description: 'Wrong answer' },
    { value: 2, label: 'Hard', color: 'bg-orange-400', description: 'Correct with difficulty' },
    { value: 3, label: 'Good', color: 'bg-yellow-400', description: 'Correct after hesitation' },
    { value: 4, label: 'Easy', color: 'bg-green-400', description: 'Correct with slight hesitation' },
    { value: 5, label: 'Perfect', color: 'bg-green-500', description: 'Perfect response' },
]

const flipCard = () => {
    isFlipped.value = true
}

const playPronunciation = () => {
    if (currentWord.value?.vocabulary?.audio_url) {
        audio.playAudio(currentWord.value.vocabulary.audio_url)
    }
}

const submitReview = async (quality) => {
    if (isReviewing.value) return

    isReviewing.value = true

    try {
        await axios.post(`/api/v1/english/vocabulary/${currentWord.value.id}/review`, {
            quality: quality,
        })

        reviewedCount.value++

        if (quality >= 3) {
            audio.playCorrect()
        }

        // Move to next word
        isFlipped.value = false
        currentIndex.value++

    } catch (error) {
        console.error('Failed to submit review:', error)
    } finally {
        isReviewing.value = false
    }
}

const finishReview = () => {
    router.visit('/student/english')
}
</script>

<template>

    <Head title="Ingliz tili - So'z takrorlash" />

    <StudentLayout>
        <div class="max-w-2xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">So'zlarni takrorlash</h1>
                    <p class="text-gray-600 dark:text-gray-400">{{ wordsForReview.length }} ta so'z takrorlash uchun</p>
                </div>
            </div>

            <!-- Progress -->
            <div class="mb-8">
                <div class="flex justify-between text-sm text-gray-500 mb-2">
                    <span>{{ currentIndex }} / {{ wordsForReview.length }}</span>
                    <span>{{ reviewedCount }} reviewed</span>
                </div>
                <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-purple-500 to-purple-600 rounded-full transition-all duration-300"
                        :style="{ width: `${progress}%` }"></div>
                </div>
            </div>

            <!-- Complete State -->
            <div v-if="isComplete" class="text-center py-12">
                <div
                    class="w-24 h-24 mx-auto mb-6 rounded-full bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center text-white text-4xl">
                    🎉
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Review Complete!</h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6">You reviewed {{ reviewedCount }} words</p>
                <button @click="finishReview"
                    class="px-8 py-3 bg-gradient-to-r from-blue-500 to-purple-500 text-white rounded-xl font-medium">
                    Back to Dashboard
                </button>
            </div>

            <!-- Review Card -->
            <div v-else>
                <div @click="!isFlipped && flipCard()" class="relative h-72 mb-8 cursor-pointer perspective">
                    <div class="absolute inset-0 transition-transform duration-500 transform-style-preserve-3d"
                        :class="{ 'rotate-y-180': isFlipped }">
                        <!-- Front - Word -->
                        <div
                            class="absolute inset-0 bg-gradient-to-br from-purple-500 to-purple-700 rounded-3xl p-8 flex flex-col items-center justify-center text-white backface-hidden shadow-xl">
                            <span class="text-4xl font-bold mb-4">{{ currentWord?.vocabulary?.word }}</span>
                            <span class="text-lg opacity-80">{{ currentWord?.vocabulary?.phonetic }}</span>
                            <button @click.stop="playPronunciation"
                                class="mt-4 p-3 bg-white/20 rounded-full hover:bg-white/30 transition-colors">
                                <SpeakerWaveIcon class="w-6 h-6" />
                            </button>
                            <p class="mt-4 text-sm opacity-70">Tap to see answer</p>
                        </div>

                        <!-- Back - Translation -->
                        <div
                            class="absolute inset-0 bg-white dark:bg-gray-800 rounded-3xl p-8 flex flex-col items-center justify-center backface-hidden rotate-y-180 shadow-xl">
                            <span class="text-3xl font-bold text-gray-900 dark:text-white mb-4">{{
                                currentWord?.vocabulary?.translation_uz }}</span>
                            <span class="text-gray-600 dark:text-gray-400 mb-2">{{
                                currentWord?.vocabulary?.part_of_speech }}</span>
                            <p class="text-center text-gray-600 dark:text-gray-400 italic">
                                "{{ currentWord?.vocabulary?.example_sentence }}"
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Quality Buttons (SM-2) -->
                <div v-if="isFlipped" class="space-y-4">
                    <p class="text-center text-gray-600 dark:text-gray-400 mb-4">How well did you remember?</p>
                    <div class="grid grid-cols-3 gap-2">
                        <button v-for="rating in qualityRatings" :key="rating.value" @click="submitReview(rating.value)"
                            :disabled="isReviewing"
                            class="py-3 px-2 rounded-xl text-white font-medium transition-all hover:scale-105 disabled:opacity-50"
                            :class="rating.color">
                            <span class="text-sm">{{ rating.label }}</span>
                        </button>
                    </div>
                    <p class="text-xs text-center text-gray-500">
                        0-2: Will review again soon | 3-5: Review interval increased
                    </p>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>

<style scoped>
.perspective {
    perspective: 1000px;
}

.transform-style-preserve-3d {
    transform-style: preserve-3d;
}

.backface-hidden {
    backface-visibility: hidden;
}

.rotate-y-180 {
    transform: rotateY(180deg);
}
</style>
