<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'
import { SpeakerWaveIcon, ArrowPathIcon, ArrowRightIcon, ArrowLeftIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    vocabulary: { type: Array, default: () => [] },
})

const emit = defineEmits(['next', 'back'])
const audio = useAudio()

const currentIndex = ref(0)
const isFlipped = ref(false)
const learnedWords = ref([])

const currentWord = computed(() => props.vocabulary[currentIndex.value])
const isLastWord = computed(() => currentIndex.value === props.vocabulary.length - 1)
const progress = computed(() => ((currentIndex.value + 1) / props.vocabulary.length) * 100)

const flipCard = () => {
    isFlipped.value = !isFlipped.value
}

const playPronunciation = () => {
    if (currentWord.value?.audio_url) {
        audio.playAudio(currentWord.value.audio_url)
    }
}

const markAsLearned = () => {
    if (!learnedWords.value.includes(currentWord.value.id)) {
        learnedWords.value.push(currentWord.value.id)
    }
    nextWord()
}

const nextWord = () => {
    if (isLastWord.value) {
        emit('next')
    } else {
        isFlipped.value = false
        currentIndex.value++
    }
}

const previousWord = () => {
    if (currentIndex.value > 0) {
        isFlipped.value = false
        currentIndex.value--
    }
}
</script>

<template>
    <div class="max-w-2xl mx-auto px-4 py-8">
        <div class="text-center mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Learn New Words</h2>
            <p class="text-gray-600 dark:text-gray-400">Tap the card to flip</p>
        </div>
        
        <!-- Progress -->
        <div class="mb-8">
            <div class="flex justify-between text-sm text-gray-500 mb-2">
                <span>{{ currentIndex + 1 }} / {{ vocabulary.length }}</span>
                <span>{{ learnedWords.length }} learned</span>
            </div>
            <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                <div 
                    class="h-full bg-green-500 rounded-full transition-all duration-300"
                    :style="{ width: `${progress}%` }"
                ></div>
            </div>
        </div>
        
        <!-- Flashcard -->
        <div 
            @click="flipCard"
            class="relative h-80 mb-8 cursor-pointer perspective"
        >
            <div 
                class="absolute inset-0 transition-transform duration-500 transform-style-preserve-3d"
                :class="{ 'rotate-y-180': isFlipped }"
            >
                <!-- Front -->
                <div class="absolute inset-0 bg-gradient-to-br from-blue-500 to-purple-600 rounded-3xl p-8 flex flex-col items-center justify-center text-white backface-hidden shadow-xl">
                    <span class="text-5xl font-bold mb-4">{{ currentWord?.word }}</span>
                    <span class="text-xl opacity-80">{{ currentWord?.phonetic || '/.../' }}</span>
                    <button 
                        @click.stop="playPronunciation"
                        class="mt-4 p-3 bg-white/20 rounded-full hover:bg-white/30 transition-colors"
                    >
                        <SpeakerWaveIcon class="w-6 h-6" />
                    </button>
                    <p class="mt-4 text-sm opacity-70">Tap to see translation</p>
                </div>
                
                <!-- Back -->
                <div class="absolute inset-0 bg-white dark:bg-gray-800 rounded-3xl p-8 flex flex-col items-center justify-center backface-hidden rotate-y-180 shadow-xl">
                    <span class="text-4xl font-bold text-gray-900 dark:text-white mb-4">{{ currentWord?.translation_uz }}</span>
                    <span class="text-lg text-gray-600 dark:text-gray-400 mb-2">{{ currentWord?.part_of_speech }}</span>
                    <p class="text-center text-gray-600 dark:text-gray-400 italic">
                        "{{ currentWord?.example_sentence }}"
                    </p>
                    <p class="text-center text-gray-500 text-sm mt-2">
                        {{ currentWord?.example_translation }}
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="flex items-center justify-between">
            <button 
                @click="$emit('back')"
                class="flex items-center space-x-2 px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
            >
                <ArrowLeftIcon class="w-5 h-5" />
                <span>Back</span>
            </button>
            
            <div class="flex items-center space-x-3">
                <button
                    @click="previousWord"
                    :disabled="currentIndex === 0"
                    class="p-3 rounded-xl border border-gray-300 dark:border-gray-600 disabled:opacity-50"
                >
                    <ArrowPathIcon class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                </button>
                
                <button
                    @click="markAsLearned"
                    class="px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl font-medium hover:shadow-lg transition-all"
                >
                    {{ isLastWord ? 'Continue' : 'Got it!' }}
                </button>
            </div>
        </div>
    </div>
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
