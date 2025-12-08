import { ref } from 'vue'

// Simple audio with fallback - only UI sounds
const audioEnabled = ref(true)
const audioCache = new Map()

export function useAudio() {
    const playSound = (soundName) => {
        if (!audioEnabled.value) return

        try {
            const soundMap = {
                correct: '/sounds/correct.mp3',
                incorrect: '/sounds/incorrect.mp3',
                click: '/sounds/click.mp3',
                timeout: '/sounds/timeout.mp3',
                win: '/sounds/win.mp3',
                levelup: '/sounds/levelup.mp3',
            }

            const src = soundMap[soundName]
            if (!src) return

            let audio = audioCache.get(src)
            if (!audio) {
                audio = new Audio(src)
                audioCache.set(src, audio)
            }

            // Clone to allow overlapping
            const clone = audio.cloneNode()
            clone.volume = 0.5
            clone.play().catch(() => {
                // Silently fail - audio not critical
            })
        } catch (e) {
            // Audio not available - continue without it
        }
    }

    return {
        audioEnabled,
        playCorrect: () => playSound('correct'),
        playIncorrect: () => playSound('incorrect'),
        playClick: () => playSound('click'),
        playTimeout: () => playSound('timeout'),
        playWin: () => playSound('win'),
        playLevelUp: () => playSound('levelup'),
        toggleAudio: () => { audioEnabled.value = !audioEnabled.value },
    }
}
