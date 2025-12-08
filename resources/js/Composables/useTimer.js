import { ref, computed, onUnmounted } from 'vue'

export function useTimer(options = {}) {
    const {
        initialTime = 60,
        countdown = true,
        onComplete = () => { },
        warningTime = 10,
    } = options

    const time = ref(initialTime)
    const isRunning = ref(false)
    const isWarning = computed(() => countdown && time.value <= warningTime && time.value > 0)

    let intervalId = null

    const formatTime = (seconds) => {
        if (seconds === null || seconds === undefined) return '0s'
        const secs = Math.max(0, Math.floor(seconds))
        const mins = Math.floor(secs / 60)
        const remainingSecs = secs % 60
        return mins > 0
            ? `${mins}:${remainingSecs.toString().padStart(2, '0')}`
            : `${remainingSecs}s`
    }

    const start = () => {
        if (isRunning.value) return
        isRunning.value = true

        intervalId = setInterval(() => {
            if (countdown) {
                time.value--
                if (time.value <= 0) {
                    stop()
                    onComplete()
                }
            } else {
                time.value++
            }
        }, 1000)
    }

    const stop = () => {
        if (intervalId) {
            clearInterval(intervalId)
            intervalId = null
        }
        isRunning.value = false
    }

    const reset = (newTime = initialTime) => {
        stop()
        time.value = newTime
    }

    const addTime = (seconds) => {
        time.value = Math.max(0, time.value + seconds)
    }

    onUnmounted(stop)

    return { time, isRunning, isWarning, formatTime, start, stop, reset, addTime }
}
