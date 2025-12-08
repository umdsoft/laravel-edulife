<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { useEnglishStore } from '@/Stores/englishStore'
import { useAudio } from '@/Composables/useAudio'
import LessonIntro from '@/Components/English/Lesson/LessonIntro.vue'
import LessonVocabulary from '@/Components/English/Lesson/LessonVocabulary.vue'
import LessonGrammar from '@/Components/English/Lesson/LessonGrammar.vue'
import LessonPractice from '@/Components/English/Lesson/LessonPractice.vue'
import LessonQuiz from '@/Components/English/Lesson/LessonQuiz.vue'
import LessonComplete from '@/Components/English/Lesson/LessonComplete.vue'
import { XMarkIcon, ChevronLeftIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    lesson: Object,
    vocabulary: Array,
    grammarPoints: Array,
    exercises: Array,
    userProgress: Object,
})

const store = useEnglishStore()
const audio = useAudio()

const currentStep = ref('intro')
const progress = ref(0)
const quizResults = ref(null)
const startTime = ref(Date.now())

const steps = ['intro', 'vocabulary', 'grammar', 'practice', 'quiz', 'complete']

const stepIndex = computed(() => steps.indexOf(currentStep.value))
const totalSteps = computed(() => steps.length - 1)

const progressPercentage = computed(() => {
    return Math.round((stepIndex.value / totalSteps.value) * 100)
})

const goToStep = (step) => {
    currentStep.value = step
    progress.value = steps.indexOf(step)
}

const nextStep = () => {
    const currentIndex = steps.indexOf(currentStep.value)
    if (currentIndex < steps.length - 1) {
        currentStep.value = steps[currentIndex + 1]
        audio.playClick()
    }
}

const previousStep = () => {
    const currentIndex = steps.indexOf(currentStep.value)
    if (currentIndex > 0) {
        currentStep.value = steps[currentIndex - 1]
    }
}

const completeQuiz = (results) => {
    quizResults.value = results
    currentStep.value = 'complete'
    audio.playSuccess()
}

const finishLesson = async () => {
    const timeSpent = Math.round((Date.now() - startTime.value) / 1000)
    
    try {
        await axios.post(`/api/v1/english/lessons/${props.lesson.id}/complete`, {
            quiz_results: quizResults.value,
            time_spent_seconds: timeSpent,
        })
        
        router.visit('/student/english/learn')
    } catch (error) {
        console.error('Failed to complete lesson:', error)
    }
}

const exitLesson = () => {
    if (confirm('Are you sure you want to exit? Your progress will be lost.')) {
        router.visit('/student/english/learn')
    }
}

onMounted(() => {
    store.setCurrentLesson(props.lesson)
    startTime.value = Date.now()
})

onUnmounted(() => {
    store.clearCurrentLesson()
})
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Header -->
        <header class="fixed top-0 left-0 right-0 z-50 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between h-14 px-4">
                <button @click="exitLesson" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                    <XMarkIcon class="w-6 h-6 text-gray-500" />
                </button>
                
                <!-- Progress bar -->
                <div class="flex-1 mx-4">
                    <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div 
                            class="h-full bg-gradient-to-r from-green-400 to-green-600 rounded-full transition-all duration-500"
                            :style="{ width: `${progressPercentage}%` }"
                        ></div>
                    </div>
                </div>
                
                <span class="text-sm font-medium text-gray-500">{{ stepIndex + 1 }}/{{ totalSteps + 1 }}</span>
            </div>
        </header>
        
        <!-- Main Content -->
        <main class="pt-14 pb-20">
            <transition name="fade" mode="out-in">
                <!-- Intro -->
                <LessonIntro 
                    v-if="currentStep === 'intro'"
                    :lesson="lesson"
                    @next="nextStep"
                />
                
                <!-- Vocabulary -->
                <LessonVocabulary 
                    v-else-if="currentStep === 'vocabulary'"
                    :vocabulary="vocabulary"
                    @next="nextStep"
                    @back="previousStep"
                />
                
                <!-- Grammar -->
                <LessonGrammar 
                    v-else-if="currentStep === 'grammar'"
                    :grammar-points="grammarPoints"
                    @next="nextStep"
                    @back="previousStep"
                />
                
                <!-- Practice -->
                <LessonPractice 
                    v-else-if="currentStep === 'practice'"
                    :exercises="exercises"
                    @next="nextStep"
                    @back="previousStep"
                />
                
                <!-- Quiz -->
                <LessonQuiz 
                    v-else-if="currentStep === 'quiz'"
                    :lesson="lesson"
                    :vocabulary="vocabulary"
                    @complete="completeQuiz"
                    @back="previousStep"
                />
                
                <!-- Complete -->
                <LessonComplete 
                    v-else-if="currentStep === 'complete'"
                    :lesson="lesson"
                    :results="quizResults"
                    @finish="finishLesson"
                />
            </transition>
        </main>
    </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
}
</style>
