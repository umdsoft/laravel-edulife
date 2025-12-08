import { ref, readonly } from 'vue'

// State
const isSpeaking = ref(false)
const isSupported = ref(typeof window !== 'undefined' && 'speechSynthesis' in window)

/**
 * Text-to-Speech composable for English word pronunciation
 */
export function useTTS() {

    // Mavjud ovozlarni olish
    const getVoices = () => {
        return new Promise((resolve) => {
            if (!isSupported.value) {
                resolve([])
                return
            }

            let voices = speechSynthesis.getVoices()
            if (voices.length) {
                resolve(voices)
                return
            }

            // Ovozlar yuklanishini kutish
            speechSynthesis.onvoiceschanged = () => {
                voices = speechSynthesis.getVoices()
                resolve(voices)
            }

            // Timeout - 2 sekunddan keyin mavjud ovozlarni qaytarish
            setTimeout(() => {
                resolve(speechSynthesis.getVoices())
            }, 2000)
        })
    }

    // Ingliz tili ovozini topish
    const getEnglishVoice = async () => {
        const voices = await getVoices()

        // Prioritet bo'yicha ovoz tanlash
        const preferredVoices = [
            'Google US English',
            'Google UK English Female',
            'Google UK English Male',
            'Microsoft Zira',
            'Microsoft David',
            'Samantha', // macOS
            'Alex',     // macOS
            'Karen',    // macOS Australian
        ]

        for (const name of preferredVoices) {
            const voice = voices.find(v => v.name.includes(name))
            if (voice) return voice
        }

        // Ingliz tilida biror ovoz
        const englishVoice = voices.find(v =>
            v.lang.startsWith('en') ||
            v.lang === 'en-US' ||
            v.lang === 'en-GB'
        )

        return englishVoice || voices[0]
    }

    /**
     * So'zni o'qish
     * @param {string} text - O'qiladigan matn
     * @param {object} options - Sozlamalar (rate, pitch, volume, onEnd, onError)
     */
    const speak = async (text, options = {}) => {
        if (!isSupported.value) {
            console.warn('TTS is not supported in this browser')
            return false
        }

        if (!text || text.trim() === '') {
            return false
        }

        // Agar hozir gapirmoqda bo'lsa, to'xtatish
        if (isSpeaking.value) {
            speechSynthesis.cancel()
        }

        const utterance = new SpeechSynthesisUtterance(text)

        // Ovoz sozlamalari
        try {
            utterance.voice = await getEnglishVoice()
        } catch (e) {
            console.warn('Could not get voice:', e)
        }

        utterance.rate = options.rate || 0.9  // Biroz sekinroq (o'rganuvchilar uchun)
        utterance.pitch = options.pitch || 1
        utterance.volume = options.volume || 1
        utterance.lang = 'en-US'

        // Event listeners
        utterance.onstart = () => {
            isSpeaking.value = true
        }

        utterance.onend = () => {
            isSpeaking.value = false
            options.onEnd?.()
        }

        utterance.onerror = (event) => {
            isSpeaking.value = false
            console.error('TTS Error:', event.error)
            options.onError?.(event.error)
        }

        // Gapirish
        speechSynthesis.speak(utterance)

        return true
    }

    /**
     * Gaprishni to'xtatish
     */
    const stop = () => {
        if (isSpeaking.value && isSupported.value) {
            speechSynthesis.cancel()
            isSpeaking.value = false
        }
    }

    /**
     * So'zni sekin o'qish
     * @param {string} text - O'qiladigan matn
     */
    const speakSlowly = async (text) => {
        return speak(text, { rate: 0.6 })
    }

    /**
     * Gapni normal tezlikda o'qish
     * @param {string} text - O'qiladigan matn
     */
    const speakSentence = async (text) => {
        return speak(text, { rate: 0.85 })
    }

    return {
        isSpeaking: readonly(isSpeaking),
        isSupported: readonly(isSupported),
        speak,
        speakSlowly,
        speakSentence,
        stop
    }
}
