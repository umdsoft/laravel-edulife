<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const stories = ref(props.content?.stories || [
    {
        title: 'A Day at the Beach',
        paragraphs: [
            { text: 'Last summer, my family went to the beach. The weather was ___ and sunny.', blank: 'warm', options: ['warm', 'cold', 'snowy', 'dark'] },
            { text: 'We ___ in the ocean for hours. The water was so refreshing!', blank: 'swam', options: ['swam', 'flew', 'drove', 'cooked'] },
            { text: 'For lunch, we ate sandwiches and ___ juice.', blank: 'drank', options: ['ate', 'drank', 'threw', 'wrote'] },
            { text: 'In the evening, we watched a beautiful ___. The sky turned orange and pink.', blank: 'sunset', options: ['sunrise', 'sunset', 'storm', 'rainbow'] },
        ]
    },
])

const currentStoryIndex = ref(0)
const currentParagraphIndex = ref(0)
const selectedAnswer = ref(null)
const feedback = ref(null)
const completedBlanks = ref({})

const currentStory = computed(() => stories.value[currentStoryIndex.value])
const currentParagraph = computed(() => currentStory.value?.paragraphs[currentParagraphIndex.value])

const displayText = computed(() => {
    if (!currentParagraph.value) return ''
    const completed = completedBlanks.value[currentParagraphIndex.value]
    if (completed) {
        return currentParagraph.value.text.replace('___', `<span class="font-bold text-green-600">${completed}</span>`)
    }
    return currentParagraph.value.text.replace('___', '<span class="px-2 py-1 bg-yellow-200 rounded">___</span>')
})

const selectAnswer = (option) => {
    if (feedback.value) return
    
    selectedAnswer.value = option
    
    if (option === currentParagraph.value.blank) {
        feedback.value = 'correct'
        completedBlanks.value[currentParagraphIndex.value] = option
        emit('score', 15)
        emit('correct')
        playAudio('/sounds/correct.mp3')
    } else {
        feedback.value = 'incorrect'
        playAudio('/sounds/incorrect.mp3')
    }
    
    setTimeout(() => nextParagraph(), 1500)
}

const nextParagraph = () => {
    if (currentParagraphIndex.value < currentStory.value.paragraphs.length - 1) {
        currentParagraphIndex.value++
        selectedAnswer.value = null
        feedback.value = null
    } else if (currentStoryIndex.value < stories.value.length - 1) {
        currentStoryIndex.value++
        currentParagraphIndex.value = 0
        selectedAnswer.value = null
        feedback.value = null
        completedBlanks.value = {}
    } else {
        emit('complete')
    }
}

const totalBlanks = computed(() => 
    stories.value.reduce((sum, s) => sum + s.paragraphs.length, 0)
)
const completedCount = computed(() => {
    let count = 0
    for (let i = 0; i < currentStoryIndex.value; i++) {
        count += stories.value[i].paragraphs.length
    }
    return count + Object.keys(completedBlanks.value).length
})
const progress = computed(() => Math.round((completedCount.value / totalBlanks.value) * 100))
</script>

<template>
    <div class="flex flex-col items-center py-6">
        <div class="w-full max-w-lg mb-4 px-4">
            <div class="flex justify-between text-sm text-white/80 mb-2">
                <span>{{ completedCount }} / {{ totalBlanks }}</span>
            </div>
            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-violet-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="bg-white/20 rounded-xl px-4 py-2 mb-4">
            <span class="text-white font-medium">📖 {{ currentStory?.title }}</span>
        </div>
        
        <div class="w-full max-w-lg bg-white rounded-2xl p-6 mb-6 mx-4">
            <div v-for="(para, index) in currentStory?.paragraphs.slice(0, currentParagraphIndex)" 
                :key="'prev-' + index" class="mb-4 text-gray-600">
                <p v-html="para.text.replace('___', `<span class='font-bold text-green-600'>${completedBlanks[index] || para.blank}</span>`)"></p>
            </div>
            
            <p class="text-lg text-gray-900 leading-relaxed" v-html="displayText"></p>
        </div>
        
        <div class="w-full max-w-lg grid grid-cols-2 gap-3 px-4">
            <button v-for="option in currentParagraph?.options" :key="option"
                @click="selectAnswer(option)"
                :disabled="feedback !== null"
                class="p-4 rounded-xl font-medium transition-all"
                :class="{
                    'bg-white text-gray-900 hover:bg-violet-50': !feedback,
                    'bg-green-500 text-white': feedback && option === currentParagraph?.blank,
                    'bg-red-500 text-white': feedback === 'incorrect' && selectedAnswer === option,
                    'opacity-50': feedback && option !== currentParagraph?.blank && selectedAnswer !== option,
                }">
                {{ option }}
            </button>
        </div>
    </div>
</template>
