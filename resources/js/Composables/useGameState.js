import { reactive, computed } from 'vue'
import { useAudio } from './useAudio'

export function useGameState(totalQuestions = 10) {
    const { playCorrect, playIncorrect, playWin } = useAudio()

    const state = reactive({
        score: 0,
        correct: 0,
        incorrect: 0,
        streak: 0,
        maxStreak: 0,
        currentIndex: 0,
        isComplete: false,
    })

    const progress = computed(() =>
        Math.round((state.currentIndex / totalQuestions) * 100)
    )

    const accuracy = computed(() => {
        const total = state.correct + state.incorrect
        return total === 0 ? 0 : Math.round((state.correct / total) * 100)
    })

    const stars = computed(() => {
        if (accuracy.value >= 90) return 3
        if (accuracy.value >= 70) return 2
        if (accuracy.value >= 50) return 1
        return 0
    })

    const recordCorrect = (points = 10) => {
        state.correct++
        state.streak++
        state.maxStreak = Math.max(state.maxStreak, state.streak)

        // Streak bonus
        const bonus = state.streak >= 3 ? (state.streak - 2) * 5 : 0
        state.score += points + bonus

        playCorrect()
        return points + bonus
    }

    const recordIncorrect = () => {
        state.incorrect++
        state.streak = 0
        playIncorrect()
    }

    const nextQuestion = () => {
        state.currentIndex++
        if (state.currentIndex >= totalQuestions) {
            complete()
        }
    }

    const complete = () => {
        state.isComplete = true
        if (stars.value >= 2) playWin()
    }

    const reset = () => {
        state.score = 0
        state.correct = 0
        state.incorrect = 0
        state.streak = 0
        state.maxStreak = 0
        state.currentIndex = 0
        state.isComplete = false
    }

    return { state, progress, accuracy, stars, recordCorrect, recordIncorrect, nextQuestion, complete, reset }
}
