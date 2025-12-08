<script setup>
import { ref, computed } from 'vue'
import QuizExercise from '../Components/QuizExercise.vue'

const props = defineProps({
    content: Object,
    phase: String,
    currentStep: Number
})

const emit = defineEmits(['answer', 'next', 'phase-change', 'complete'])

// Dialogue lines
const dialogue = computed(() => props.content?.dialogue || [])
const rolePlay = computed(() => props.content?.rolePlay || [])
const quiz = computed(() => props.content?.quiz || [])

// State
const currentDialogueLine = ref(0)
const showFullDialogue = ref(false)
const currentRolePlayIndex = ref(0)
const currentQuizIndex = ref(0)

// Next dialogue line
const nextDialogueLine = () => {
    if (currentDialogueLine.value < dialogue.value.length - 1) {
        currentDialogueLine.value++
    } else {
        showFullDialogue.value = true
    }
}

// Start role play
const startRolePlay = () => {
    if (rolePlay.value.length > 0) {
        emit('phase-change', 'practice')
    } else if (quiz.value.length > 0) {
        emit('phase-change', 'quiz')
    } else {
        emit('complete')
    }
}

// Handle role play answer
const handleRolePlayAnswer = (isCorrect) => {
    emit('answer', isCorrect)
    emit('next')
    
    setTimeout(() => {
        if (currentRolePlayIndex.value < rolePlay.value.length - 1) {
            currentRolePlayIndex.value++
        } else if (quiz.value.length > 0) {
            emit('phase-change', 'quiz')
        } else {
            emit('complete')
        }
    }, 1500)
}

// Handle quiz answer
const handleQuizAnswer = (isCorrect) => {
    emit('answer', isCorrect)
    emit('next')
    
    setTimeout(() => {
        if (currentQuizIndex.value < quiz.value.length - 1) {
            currentQuizIndex.value++
        } else {
            emit('complete')
        }
    }, 1500)
}
</script>

<template>
    <div>
        <!-- Phase 1: Learn Dialogue -->
        <div v-if="phase === 'learn'" class="space-y-6">
            <!-- Phase Header -->
            <div class="text-center">
                <div class="inline-flex items-center gap-2 bg-pink-100 dark:bg-pink-900/30 text-pink-700 dark:text-pink-300 px-4 py-2 rounded-full text-sm font-medium mb-4">
                    <span>💬</span>
                    <span>Conversation</span>
                </div>
            </div>
            
            <!-- Dialogue Box -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                <!-- Dialogue Lines -->
                <div class="space-y-4 max-h-80 overflow-y-auto">
                    <div 
                        v-for="(line, i) in dialogue" 
                        :key="i"
                        :class="[
                            'flex gap-3 transition-all duration-300',
                            i > currentDialogueLine && !showFullDialogue ? 'opacity-0 h-0 overflow-hidden' : 'opacity-100',
                            line.speaker === 'A' ? 'justify-start' : 'justify-end'
                        ]"
                    >
                        <!-- Avatar -->
                        <div 
                            v-if="line.speaker === 'A'"
                            class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-xl shrink-0"
                        >
                            👤
                        </div>
                        
                        <!-- Speech Bubble -->
                        <div 
                            :class="[
                                'max-w-[70%] p-3 rounded-2xl',
                                line.speaker === 'A' 
                                    ? 'bg-gray-100 dark:bg-gray-700 rounded-tl-none' 
                                    : 'bg-indigo-500 text-white rounded-tr-none'
                            ]"
                        >
                            <p :class="line.speaker === 'A' ? 'text-gray-800 dark:text-gray-200' : 'text-white'">
                                {{ line.text }}
                            </p>
                            <p 
                                v-if="line.translation"
                                :class="[
                                    'text-xs mt-1',
                                    line.speaker === 'A' ? 'text-gray-500 dark:text-gray-400' : 'text-indigo-200'
                                ]"
                            >
                                {{ line.translation }}
                            </p>
                        </div>
                        
                        <!-- Avatar B -->
                        <div 
                            v-if="line.speaker === 'B'"
                            class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-xl shrink-0"
                        >
                            🧑
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Navigation -->
            <div class="flex justify-center">
                <button
                    v-if="!showFullDialogue"
                    @click="nextDialogueLine"
                    class="px-8 py-3 bg-indigo-600 text-white rounded-xl font-semibold hover:bg-indigo-700 transition-all flex items-center gap-2"
                >
                    Next Line
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
                <button
                    v-else
                    @click="startRolePlay"
                    class="px-8 py-3 bg-emerald-600 text-white rounded-xl font-semibold hover:bg-emerald-700 transition-all flex items-center gap-2"
                >
                    Start Practice
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Phase 2: Role Play -->
        <div v-else-if="phase === 'practice'" class="space-y-6">
            <div class="text-center">
                <div class="inline-flex items-center gap-2 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 px-4 py-2 rounded-full text-sm font-medium mb-4">
                    <span>🎭</span>
                    <span>Role Play</span>
                </div>
                <p class="text-gray-500 dark:text-gray-400 text-sm">
                    Choose the best response
                </p>
            </div>
            
            <QuizExercise
                v-if="rolePlay.length > 0"
                :question="rolePlay[currentRolePlayIndex]"
                @answer="handleRolePlayAnswer"
            />
        </div>
        
        <!-- Phase 3: Quiz -->
        <div v-else-if="phase === 'quiz'" class="space-y-6">
            <div class="text-center">
                <div class="inline-flex items-center gap-2 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 px-4 py-2 rounded-full text-sm font-medium mb-4">
                    <span>📝</span>
                    <span>Final Quiz</span>
                </div>
            </div>
            
            <QuizExercise
                v-if="quiz.length > 0"
                :question="quiz[currentQuizIndex]"
                @answer="handleQuizAnswer"
            />
        </div>
    </div>
</template>
