<script setup>
import { ref, computed, watch } from 'vue'
import uz from '@/Lang/uz'

const props = defineProps({
    question: {
        type: Object,
        required: true
    },
    questionIndex: {
        type: Number,
        default: 0
    },
    totalQuestions: {
        type: Number,
        default: 1
    },
    isLastQuestion: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['answer', 'next', 'finish'])

// Translations (O'zbek tili - A1-B1 uchun)
const t = uz

// State
const selectedAnswer = ref(null)
const feedback = ref(null) // 'correct' | 'incorrect' | null
const isChecked = ref(false)
const isAnswered = ref(false)

// Reset when question changes
watch(() => props.question, () => {
    reset()
}, { deep: true })

// Javobni tanlash (lekin tekshirmaslik)
const selectAnswer = (index) => {
    if (isChecked.value) return
    selectedAnswer.value = index
}

// Javobni tekshirish
const checkAnswer = () => {
    if (selectedAnswer.value === null) return
    
    isChecked.value = true
    isAnswered.value = true
    
    const isCorrect = selectedAnswer.value === props.question.correctAnswer
    feedback.value = isCorrect ? 'correct' : 'incorrect'
    
    // Natijani parent ga yuborish (faqat statistika uchun)
    emit('answer', isCorrect)
}

// Keyingisiga o'tish
const handleNext = () => {
    if (props.isLastQuestion) {
        emit('finish')
    } else {
        emit('next')
    }
}

// Reset (parent tomonidan chaqiriladi yoki question o'zgarganda)
const reset = () => {
    selectedAnswer.value = null
    feedback.value = null
    isAnswered.value = false
    showContinueButton.value = false
}

// Parent uchun reset metodini expose qilish
defineExpose({ reset })

// Option styling
const getOptionClass = (index) => {
    const base = 'w-full p-4 rounded-xl border-2 text-left transition-all duration-200 flex items-center gap-3'
    
    if (!isAnswered.value) {
        return `${base} border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-600 hover:shadow-md cursor-pointer`
    }
    
    if (index === props.question.correctAnswer) {
        return `${base} border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20`
    }
    
    if (selectedAnswer.value === index && feedback.value === 'incorrect') {
        return `${base} border-red-500 bg-red-50 dark:bg-red-900/20 animate-shake`
    }
    
    return `${base} border-gray-200 dark:border-gray-700 opacity-50 cursor-not-allowed`
}

const getLetterClass = (index) => {
    const base = 'w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold shrink-0'
    
    if (!isAnswered.value) {
        return `${base} bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300`
    }
    
    if (index === props.question.correctAnswer) {
        return `${base} bg-emerald-500 text-white`
    }
    
    if (selectedAnswer.value === index && feedback.value === 'incorrect') {
        return `${base} bg-red-500 text-white`
    }
    
    return `${base} bg-gray-100 dark:bg-gray-700 text-gray-400`
}

const letters = ['A', 'B', 'C', 'D', 'E', 'F']
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
        <!-- Savol -->
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 text-center">
            {{ question?.question || 'Savol' }}
        </h3>
        
        <!-- Variantlar -->
        <div class="grid gap-3">
            <button
                v-for="(option, index) in question?.options || []"
                :key="index"
                @click="selectAnswer(index)"
                :disabled="isChecked"
                :class="getOptionClass(index)"
            >
                <!-- Variant harfi -->
                <span :class="getLetterClass(index)">
                    {{ letters[index] }}
                </span>
                
                <!-- Variant matni -->
                <span class="flex-1 text-gray-700 dark:text-gray-300 font-medium">
                    {{ option }}
                </span>
                
                <!-- Feedback ikonka -->
                <span v-if="isChecked && index === question.correctAnswer" class="text-xl shrink-0">
                    ✅
                </span>
                <span v-else-if="isChecked && feedback === 'incorrect' && selectedAnswer === index" class="text-xl shrink-0">
                    ❌
                </span>
            </button>
        </div>
        
        <!-- Tushuntirish -->
        <Transition
            enter-active-class="transition-all duration-300"
            enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
        >
            <div 
                v-if="isChecked && question?.explanation"
                :class="[
                    'mt-4 p-4 rounded-xl text-sm',
                    feedback === 'correct' 
                        ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300'
                        : 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300'
                ]"
            >
                💡 {{ question.explanation }}
            </div>
        </Transition>
        
        <!-- ACTIONS: CHECK or CONTINUE -->
        <div class="mt-8 flex justify-center min-h-[60px]">
            <!-- Check Button -->
            <button
                v-if="!isChecked"
                @click="checkAnswer"
                :disabled="selectedAnswer === null"
                :class="[
                    'px-8 py-3 rounded-xl font-bold text-white transition-all transform',
                    selectedAnswer !== null
                        ? 'bg-indigo-600 hover:bg-indigo-700 active:scale-95 hover:shadow-lg'
                        : 'bg-gray-300 dark:bg-gray-700 cursor-not-allowed'
                ]"
            >
                {{ t.lesson.check }}
            </button>

            <!-- Continue Button -->
            <button
                v-else
                @click="handleNext"
                :class="[
                    'px-8 py-3 rounded-xl font-bold text-white transition-all flex items-center gap-2 transform active:scale-95 hover:shadow-lg',
                    isLastQuestion 
                        ? 'bg-emerald-600 hover:bg-emerald-700'
                        : 'bg-indigo-600 hover:bg-indigo-700'
                ]"
            >
                {{ isLastQuestion ? t.lesson.finish : t.lesson.continue }}
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path v-if="isLastQuestion" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
    </div>
</template>

<style scoped>
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    20%, 60% { transform: translateX(-5px); }
    40%, 80% { transform: translateX(5px); }
}

.animate-shake {
    animation: shake 0.5s ease-in-out;
}
</style>
