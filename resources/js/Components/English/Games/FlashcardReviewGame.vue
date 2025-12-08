<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'
import { SpeakerWaveIcon, ArrowLeftIcon, ArrowRightIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const cards = ref(props.content?.words || [])
const currentIndex = ref(0)
const isFlipped = ref(false)
const ratings = ref({})
const reviewedCount = ref(0)

const currentCard = computed(() => cards.value[currentIndex.value])
const progress = computed(() => Math.round((reviewedCount.value / cards.value.length) * 100))

const flipCard = () => {
    isFlipped.value = !isFlipped.value
}

const playCardAudio = () => {
    if (currentCard.value?.audio_url) {
        playAudio(currentCard.value.audio_url)
    }
}

const rateCard = (rating) => {
    ratings.value[currentCard.value.id] = rating
    
    const points = rating >= 4 ? 15 : rating >= 3 ? 10 : 5
    emit('score', points)
    
    if (rating >= 3) {
        emit('correct')
    }
    
    reviewedCount.value++
    
    setTimeout(() => {
        if (currentIndex.value < cards.value.length - 1) {
            currentIndex.value++
            isFlipped.value = false
        } else {
            emit('complete')
        }
    }, 300)
}

const goToPrev = () => {
    if (currentIndex.value > 0) {
        currentIndex.value--
        isFlipped.value = false
    }
}

const goToNext = () => {
    if (currentIndex.value < cards.value.length - 1) {
        currentIndex.value++
        isFlipped.value = false
    }
}

const ratingButtons = [
    { value: 1, label: 'Again', color: 'bg-red-500', desc: "Didn't know" },
    { value: 2, label: 'Hard', color: 'bg-orange-500', desc: 'Difficult' },
    { value: 3, label: 'Good', color: 'bg-yellow-500', desc: 'With effort' },
    { value: 4, label: 'Easy', color: 'bg-green-400', desc: 'Knew it' },
    { value: 5, label: 'Perfect', color: 'bg-green-500', desc: 'Instant' },
]
</script>

<template>
    <div class="flex flex-col items-center py-8">
        <div class="w-full max-w-md mb-6 px-4">
            <div class="flex justify-between text-sm text-white/80 mb-2">
                <span>{{ currentIndex + 1 }} / {{ cards.length }}</span>
                <span>{{ reviewedCount }} reviewed</span>
            </div>
            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-green-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="flex items-center space-x-4 mb-6">
            <button @click="goToPrev" :disabled="currentIndex === 0"
                class="p-2 rounded-full bg-white/20 text-white hover:bg-white/30 disabled:opacity-30">
                <ArrowLeftIcon class="w-5 h-5" />
            </button>
            
            <span class="text-white/60">Card {{ currentIndex + 1 }}</span>
            
            <button @click="goToNext" :disabled="currentIndex === cards.length - 1"
                class="p-2 rounded-full bg-white/20 text-white hover:bg-white/30 disabled:opacity-30">
                <ArrowRightIcon class="w-5 h-5" />
            </button>
        </div>
        
        <div @click="flipCard" class="w-full max-w-md h-64 cursor-pointer perspective-1000 mb-6">
            <div class="relative w-full h-full transition-transform duration-500 transform-style-3d"
                :class="{ 'rotate-y-180': isFlipped }">
                <div class="absolute inset-0 backface-hidden bg-white rounded-3xl shadow-xl p-8 flex flex-col items-center justify-center">
                    <button @click.stop="playCardAudio"
                        class="absolute top-4 right-4 p-2 rounded-full hover:bg-gray-100">
                        <SpeakerWaveIcon class="w-6 h-6 text-blue-500" />
                    </button>
                    
                    <h2 class="text-4xl font-bold text-gray-900 mb-2">
                        {{ currentCard?.word }}
                    </h2>
                    <p class="text-lg text-gray-500">{{ currentCard?.phonetic }}</p>
                    <p class="text-sm text-gray-400 mt-4">Tap to flip</p>
                </div>
                
                <div class="absolute inset-0 backface-hidden rotate-y-180 bg-gradient-to-br from-blue-500 to-purple-600 rounded-3xl shadow-xl p-8 flex flex-col items-center justify-center text-white">
                    <p class="text-sm opacity-80 mb-2">Translation</p>
                    <h2 class="text-3xl font-bold mb-2">{{ currentCard?.translation_uz }}</h2>
                    
                    <div v-if="currentCard?.example_sentence" class="mt-4 p-3 bg-white/10 rounded-xl text-center">
                        <p class="text-sm opacity-80">Example</p>
                        <p class="italic">{{ currentCard?.example_sentence }}</p>
                    </div>
                </div>
            </div>
        </div>
        
        <Transition name="fade">
            <div v-if="isFlipped" class="w-full max-w-md">
                <p class="text-white/80 text-center mb-3">How well did you know this?</p>
                <div class="grid grid-cols-5 gap-2">
                    <button v-for="btn in ratingButtons" :key="btn.value"
                        @click="rateCard(btn.value)"
                        class="p-3 rounded-xl text-white font-medium transition-all hover:scale-105"
                        :class="btn.color">
                        <span class="block text-sm">{{ btn.label }}</span>
                        <span class="block text-xs opacity-80">{{ btn.value }}</span>
                    </button>
                </div>
            </div>
        </Transition>
        
        <div v-if="!isFlipped" class="text-white/60 text-sm text-center">
            <p>Think of the meaning, then tap to check</p>
        </div>
    </div>
</template>

<style scoped>
.perspective-1000 { perspective: 1000px; }
.transform-style-3d { transform-style: preserve-3d; }
.backface-hidden { backface-visibility: hidden; }
.rotate-y-180 { transform: rotateY(180deg); }
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
