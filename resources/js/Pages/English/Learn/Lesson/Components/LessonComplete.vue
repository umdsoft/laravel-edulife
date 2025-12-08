<script setup>
import { ref, onMounted } from 'vue'
import uz from '@/Lang/uz'

const props = defineProps({
    lesson: Object,
    rewards: Object,
    stats: Object,
    nextLesson: Object
})

const emit = defineEmits(['next', 'retry', 'exit'])

// Translations (O'zbek tili)
const t = uz

const showConfetti = ref(false)
const animateIn = ref(false)

const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60)
    const secs = seconds % 60
    return `${mins}:${secs.toString().padStart(2, '0')}`
}

const getTitle = () => {
    if (props.rewards?.stars === 3) return `${t.lesson.greatJob} 🌟`
    if (props.rewards?.stars === 2) return `${t.lesson.wellDone} 👏`
    if (props.rewards?.stars === 1) return `${t.lesson.keepGoing} 👍`
    return t.lesson.lessonComplete
}

const getSubtitle = () => {
    const lessonTitle = props.lesson?.title || 'Dars'
    return `"${lessonTitle}" ${t.lesson.completed.toLowerCase()}`
}

onMounted(() => {
    setTimeout(() => {
        showConfetti.value = true
        animateIn.value = true
    }, 100)
})
</script>

<template>
    <div class="text-center py-8 relative">
        <!-- Confetti Effect -->
        <div v-if="showConfetti && rewards?.stars >= 2" class="absolute inset-0 pointer-events-none overflow-hidden">
            <div class="confetti-container">
                <div v-for="n in 30" :key="n" class="confetti" :style="{ '--delay': `${n * 0.1}s`, '--x': `${Math.random() * 100}%` }"></div>
            </div>
        </div>
        
        <!-- Stars -->
        <div class="flex justify-center gap-3 mb-6">
            <span 
                v-for="n in 3" 
                :key="n"
                :class="[
                    'text-5xl transition-all duration-500 transform',
                    animateIn ? 'opacity-100 scale-100' : 'opacity-0 scale-50',
                    n <= (rewards?.stars || 0) ? '' : 'grayscale opacity-30'
                ]"
                :style="{ transitionDelay: `${n * 150}ms` }"
            >
                ⭐
            </span>
        </div>
        
        <!-- Title -->
        <h2 
            class="text-3xl font-bold text-gray-900 dark:text-white mb-2 transition-all duration-500"
            :class="animateIn ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            :style="{ transitionDelay: '400ms' }"
        >
            {{ getTitle() }}
        </h2>
        <p 
            class="text-gray-600 dark:text-gray-400 mb-8 transition-all duration-500"
            :class="animateIn ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            :style="{ transitionDelay: '500ms' }"
        >
            {{ getSubtitle() }}
        </p>
        
        <!-- Stats Grid -->
        <div 
            class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-8 transition-all duration-500"
            :class="animateIn ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            :style="{ transitionDelay: '600ms' }"
        >
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
                <div class="text-2xl font-bold text-emerald-600">{{ stats?.correct || 0 }}</div>
                <div class="text-xs text-gray-500">{{ t.lesson.correctAnswers }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
                <div class="text-2xl font-bold text-indigo-600">{{ rewards?.accuracy || 0 }}%</div>
                <div class="text-xs text-gray-500">{{ t.lesson.accuracy }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
                <div class="text-2xl font-bold text-amber-600">🔥 {{ stats?.maxStreak || 0 }}</div>
                <div class="text-xs text-gray-500">{{ t.lesson.streak }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
                <div class="text-2xl font-bold text-purple-600">{{ formatTime(stats?.time || 0) }}</div>
                <div class="text-xs text-gray-500">{{ t.lesson.timeSpent }}</div>
            </div>
        </div>
        
        <!-- Rewards -->
        <div 
            class="bg-gradient-to-r from-indigo-500 to-purple-500 rounded-2xl p-6 mb-8 text-white shadow-lg transition-all duration-500"
            :class="animateIn ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            :style="{ transitionDelay: '700ms' }"
        >
            <h3 class="text-lg font-semibold mb-4">🎁 {{ t.lesson.youEarned }}</h3>
            <div class="flex justify-center gap-12">
                <div class="text-center">
                    <div class="text-4xl font-bold">+{{ rewards?.xp || 0 }}</div>
                    <div class="text-indigo-200 text-sm">{{ t.lesson.xpEarned }}</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold">+{{ rewards?.coins || 0 }}</div>
                    <div class="text-indigo-200 text-sm">{{ t.lesson.coinsEarned }} 💰</div>
                </div>
            </div>
        </div>
        
        <!-- Actions (O'zbek tilida) -->
        <div 
            class="flex flex-col sm:flex-row justify-center gap-4 transition-all duration-500"
            :class="animateIn ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            :style="{ transitionDelay: '800ms' }"
        >
            <button
                v-if="nextLesson"
                @click="$emit('next')"
                type="button"
                class="px-8 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl font-bold text-lg transition-all duration-200 hover:scale-105 active:scale-95 flex items-center justify-center gap-2 shadow-lg hover:shadow-xl"
            >
                {{ t.lesson.nextLesson }}
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
            <button
                v-else
                @click="$emit('exit')"
                type="button"
                class="px-8 py-4 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white rounded-xl font-bold text-lg transition-all duration-200 hover:scale-105 active:scale-95 flex items-center justify-center gap-2 shadow-lg hover:shadow-xl"
            >
                {{ t.lesson.complete }}
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </button>
            <button
                @click="$emit('retry')"
                type="button"
                class="px-6 py-4 bg-gray-100 hover:bg-gray-200 active:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-xl font-semibold transition-all duration-200 hover:scale-105 active:scale-95 border-2 border-gray-200 dark:border-gray-600"
            >
                {{ t.lesson.repeatLesson }}
            </button>
        </div>
    </div>
</template>

<style scoped>
.confetti-container {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    overflow: hidden;
}

.confetti {
    position: absolute;
    width: 10px;
    height: 10px;
    top: -10px;
    left: var(--x);
    background: linear-gradient(45deg, #ffd700, #ff6b6b, #4ecdc4, #45b7d1, #96ceb4);
    animation: confetti-fall 3s ease-out forwards;
    animation-delay: var(--delay);
    border-radius: 2px;
}

@keyframes confetti-fall {
    0% {
        transform: translateY(0) rotate(0deg);
        opacity: 1;
    }
    100% {
        transform: translateY(500px) rotate(720deg);
        opacity: 0;
    }
}
</style>
