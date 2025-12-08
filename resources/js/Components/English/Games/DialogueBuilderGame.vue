<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const dialogues = ref(props.content?.dialogues || [
    {
        context: 'At a restaurant',
        lines: [
            { speaker: 'Waiter', text: 'Good evening! How many people?', isQuestion: false },
            { speaker: 'You', text: '___', isQuestion: true, answer: 'Two, please.', options: ['Two, please.', 'I like pizza.', 'It is sunny.', 'My name is John.'] },
            { speaker: 'Waiter', text: 'Right this way. Here is your menu.', isQuestion: false },
            { speaker: 'You', text: '___', isQuestion: true, answer: 'Thank you.', options: ['Thank you.', 'Goodbye.', 'I am fine.', 'Nice to meet you.'] },
        ]
    },
    {
        context: 'At a shop',
        lines: [
            { speaker: 'Shop assistant', text: 'Hello! Can I help you?', isQuestion: false },
            { speaker: 'You', text: '___', isQuestion: true, answer: "Yes, I'm looking for a shirt.", options: ["Yes, I'm looking for a shirt.", "I had breakfast.", "The weather is nice.", "I live here."] },
            { speaker: 'Shop assistant', text: 'What size do you need?', isQuestion: false },
            { speaker: 'You', text: '___', isQuestion: true, answer: 'Medium, please.', options: ['Medium, please.', 'Blue color.', 'Very expensive.', 'Tomorrow morning.'] },
        ]
    },
])

const currentDialogueIndex = ref(0)
const currentLineIndex = ref(0)
const selectedAnswer = ref(null)
const feedback = ref(null)
const completedLines = ref([])

const currentDialogue = computed(() => dialogues.value[currentDialogueIndex.value])
const currentLine = computed(() => {
    for (let i = currentLineIndex.value; i < currentDialogue.value?.lines.length; i++) {
        if (currentDialogue.value.lines[i].isQuestion) {
            return { ...currentDialogue.value.lines[i], index: i }
        }
    }
    return null
})

const visibleLines = computed(() => {
    return currentDialogue.value?.lines.slice(0, currentLineIndex.value + 1) || []
})

const selectAnswer = (option) => {
    if (feedback.value) return
    
    selectedAnswer.value = option
    
    if (option === currentLine.value.answer) {
        feedback.value = 'correct'
        completedLines.value.push({
            index: currentLine.value.index,
            answer: option
        })
        emit('score', 20)
        emit('correct')
        playAudio('/sounds/correct.mp3')
    } else {
        feedback.value = 'incorrect'
        playAudio('/sounds/incorrect.mp3')
    }
    
    setTimeout(() => nextLine(), 1500)
}

const nextLine = () => {
    let nextIndex = currentLineIndex.value + 1
    
    while (nextIndex < currentDialogue.value.lines.length && 
           !currentDialogue.value.lines[nextIndex].isQuestion) {
        nextIndex++
    }
    
    if (nextIndex < currentDialogue.value.lines.length) {
        currentLineIndex.value = nextIndex
        selectedAnswer.value = null
        feedback.value = null
    } else if (currentDialogueIndex.value < dialogues.value.length - 1) {
        currentDialogueIndex.value++
        currentLineIndex.value = 0
        selectedAnswer.value = null
        feedback.value = null
        completedLines.value = []
    } else {
        emit('complete')
    }
}

const getLineText = (line, index) => {
    if (!line.isQuestion) return line.text
    const completed = completedLines.value.find(c => c.index === index)
    return completed ? completed.answer : '___'
}

const totalQuestions = computed(() => 
    dialogues.value.reduce((sum, d) => sum + d.lines.filter(l => l.isQuestion).length, 0)
)
const answeredQuestions = computed(() => {
    let count = 0
    for (let i = 0; i < currentDialogueIndex.value; i++) {
        count += dialogues.value[i].lines.filter(l => l.isQuestion).length
    }
    return count + completedLines.value.length
})
const progress = computed(() => Math.round((answeredQuestions.value / totalQuestions.value) * 100))
</script>

<template>
    <div class="flex flex-col items-center py-6">
        <div class="w-full max-w-lg mb-4 px-4">
            <div class="flex justify-between text-sm text-white/80 mb-2">
                <span>{{ answeredQuestions }} / {{ totalQuestions }}</span>
            </div>
            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-indigo-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="bg-white/20 rounded-xl px-4 py-2 mb-4">
            <span class="text-white font-medium">📍 {{ currentDialogue?.context }}</span>
        </div>
        
        <div class="w-full max-w-lg bg-white rounded-2xl p-4 mb-6 mx-4 max-h-[300px] overflow-y-auto">
            <div v-for="(line, index) in visibleLines" :key="index" class="mb-3 last:mb-0">
                <div class="flex items-start space-x-3"
                    :class="line.speaker === 'You' ? 'flex-row-reverse space-x-reverse' : ''">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-bold"
                        :class="line.speaker === 'You' ? 'bg-indigo-500' : 'bg-gray-400'">
                        {{ line.speaker === 'You' ? '👤' : '🧑‍💼' }}
                    </div>
                    <div class="max-w-[70%]">
                        <p class="text-xs text-gray-500 mb-1">{{ line.speaker }}</p>
                        <div class="p-3 rounded-xl"
                            :class="line.speaker === 'You' ? 'bg-indigo-100' : 'bg-gray-100'">
                            <p class="text-gray-900">{{ getLineText(line, index) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div v-if="currentLine" class="w-full max-w-lg space-y-2 px-4">
            <p class="text-white/80 text-sm text-center mb-2">Choose your response:</p>
            <button v-for="option in currentLine.options" :key="option"
                @click="selectAnswer(option)"
                :disabled="feedback !== null"
                class="w-full p-3 rounded-xl font-medium transition-all text-left"
                :class="{
                    'bg-white text-gray-900 hover:bg-indigo-50': !feedback,
                    'bg-green-500 text-white': feedback && option === currentLine.answer,
                    'bg-red-500 text-white': feedback === 'incorrect' && selectedAnswer === option,
                    'opacity-50': feedback && option !== currentLine.answer && selectedAnswer !== option,
                }">
                {{ option }}
            </button>
        </div>
    </div>
</template>
