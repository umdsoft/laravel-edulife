import { ref } from 'vue'
import confetti from 'canvas-confetti'

export function useConfetti() {
    const isActive = ref(false)

    const fireConfetti = (options = {}) => {
        const {
            particleCount = 100,
            spread = 70,
            origin = { y: 0.6 },
        } = options

        isActive.value = true

        confetti({
            particleCount,
            spread,
            origin,
        })

        setTimeout(() => {
            isActive.value = false
        }, 2000)
    }

    const fireStars = () => {
        const defaults = {
            spread: 360,
            ticks: 50,
            gravity: 0,
            decay: 0.94,
            startVelocity: 30,
            shapes: ['star'],
            colors: ['#FFE400', '#FFBD00', '#E89400', '#FFCA6C', '#FDFFB8']
        }

        confetti({ ...defaults, particleCount: 40, scalar: 1.2, origin: { x: 0.5, y: 0.5 } })
        confetti({ ...defaults, particleCount: 10, scalar: 0.75, origin: { x: 0.5, y: 0.5 } })
    }

    return { isActive, fireConfetti, fireStars }
}
