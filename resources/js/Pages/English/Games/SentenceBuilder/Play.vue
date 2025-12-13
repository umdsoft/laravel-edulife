<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import axios from 'axios'
import SessionComplete from './components/SessionComplete.vue'

const props = defineProps({
    session: { type: Object, required: true }
})

const showResultModal = ref(false)
const sessionResult = ref(null)

const currentSentenceIndex = ref(0)
const userAnswer = ref([])
const availableWords = ref([])
const checkResult = ref(null)
const isChecking = ref(false)
const score = ref(props.session.score || 0)
const hintsUsed = ref(0)
const sessionComplete = ref(false)
const startTime = ref(Date.now())

const currentSentence = computed(() => props.session.sentences[currentSentenceIndex.value])
const level = computed(() => props.session.level)
const progressPercent = computed(() => Math.round(((currentSentenceIndex.value + 1) / props.session.sentences.length) * 100))

const initSentence = () => {
    if (!currentSentence.value) {
        completeSession()
        return
    }
    userAnswer.value = []
    availableWords.value = [...currentSentence.value.scrambled_words]
    checkResult.value = null
    hintsUsed.value = 0
    startTime.value = Date.now()
}

onMounted(() => {
    initSentence()
})

const addWord = (word, index) => {
    if (checkResult.value?.is_correct) return
    availableWords.value.splice(index, 1)
    userAnswer.value.push(word)
}

const removeWord = (word, index) => {
    if (checkResult.value?.is_correct) return
    userAnswer.value.splice(index, 1)
    availableWords.value.push(word)
}

const checkAnswer = async () => {
    if (userAnswer.value.length === 0 || isChecking.value) return
    isChecking.value = true

    try {
        const response = await axios.post('/student/english/games/sentence-builder/check', {
            session_id: props.session.session_id,
            sentence_id: currentSentence.value.id,
            answer: userAnswer.value,
            time_seconds: (Date.now() - startTime.value) / 1000,
            hints_used: hintsUsed.value
        })
        checkResult.value = response.data
        if (response.data.is_correct) {
            score.value = response.data.total_score
        }
    } catch (error) {
        console.error(error)
    } finally {
        isChecking.value = false
    }
}

const useHint = async () => {
    if (isChecking.value || checkResult.value?.is_correct) return
    try {
        const response = await axios.post('/student/english/games/sentence-builder/hint', {
            session_id: props.session.session_id,
            sentence_id: currentSentence.value.id,
            current_answer: userAnswer.value
        })
        const { hint_word, index } = response.data
        if (hint_word) {
            hintsUsed.value++
            if (userAnswer.value[index] === hint_word) return
            const bankIdx = availableWords.value.indexOf(hint_word)
            if (bankIdx > -1) {
                availableWords.value.splice(bankIdx, 1)
            } else {
                const ansIdx = userAnswer.value.indexOf(hint_word)
                if (ansIdx > -1) userAnswer.value.splice(ansIdx, 1)
            }
            userAnswer.value.splice(index, 0, hint_word)
        }
    } catch (error) {
        console.error(error)
    }
}

const nextSentence = () => {
    if (currentSentenceIndex.value < props.session.sentences.length - 1) {
        currentSentenceIndex.value++
        initSentence()
    } else {
        completeSession()
    }
}

const completeSession = async () => {
    try {
        const response = await axios.post('/student/english/games/sentence-builder/complete', {
            session_id: props.session.session_id
        })
        sessionResult.value = response.data
        sessionComplete.value = true
        showResultModal.value = true
    } catch (error) {
        console.error(error)
        sessionComplete.value = true
    }
}

const goBack = () => {
    router.visit('/student/english/games/sentence-builder')
}

const restartLevel = () => {
    router.visit(`/student/english/games/sentence-builder/play/${level.value.number}`)
}

const closeResultModal = () => {
    showResultModal.value = false
    goBack()
}
</script>

<template>
    <Head title="O'ynash - Gap Quruvchi" />

    <div class="game-container">
        <!-- Background -->
        <div class="game-bg">
            <div class="orb orb-1"></div>
            <div class="orb orb-2"></div>
            <div class="orb orb-3"></div>
        </div>

        <div class="game-content">
            <!-- Header -->
            <header class="game-header">
                <button @click="goBack" class="back-btn">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                <div class="level-info">
                    <div class="level-badges">
                        <span class="level-badge">LEVEL {{ level?.number }}</span>
                        <span class="cefr-badge">{{ level?.cefr_level }}</span>
                    </div>
                    <h1 class="level-name">{{ level?.name_uz }}</h1>
                </div>

                <div class="score-box">
                    <span class="score-icon">⭐</span>
                    <span class="score-value">{{ score }}</span>
                </div>
            </header>

            <!-- Progress -->
            <div class="progress-section">
                <div class="progress-info">
                    <span>Savol {{ currentSentenceIndex + 1 }} / {{ props.session.sentences.length }}</span>
                    <span>{{ progressPercent }}%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" :style="{ width: progressPercent + '%' }"></div>
                </div>
            </div>

            <!-- Game Area -->
            <main v-if="!sessionComplete" class="game-main">
                <!-- Translation -->
                <div class="translation-section">
                    <div class="translation-badge">
                        <span>🇺🇿</span>
                        <span>Tarjima</span>
                    </div>
                    <h2 class="translation-text">{{ currentSentence?.translation_uz }}</h2>

                    <!-- Feedback -->
                    <div v-if="checkResult" class="feedback">
                        <div v-if="checkResult.is_correct" class="feedback-correct">
                            ✅ To'g'ri! +{{ checkResult.points_earned }} ball
                        </div>
                        <div v-else class="feedback-wrong">
                            ❌ Xato! Qayta urinib ko'ring
                        </div>
                    </div>
                </div>

                <!-- Answer Zone -->
                <div class="answer-section">
                    <div class="section-label">Javobingiz</div>
                    <div class="answer-zone" :class="{
                        'answer-correct': checkResult?.is_correct,
                        'answer-wrong': checkResult && !checkResult.is_correct
                    }">
                        <button v-for="(word, index) in userAnswer"
                            :key="'ans-' + index"
                            @click="removeWord(word, index)"
                            :disabled="checkResult?.is_correct"
                            class="word-btn word-selected">
                            {{ word }}
                        </button>
                        <span v-if="userAnswer.length === 0" class="placeholder-text">
                            So'zlarni bu yerga qo'shing...
                        </span>
                    </div>
                </div>

                <!-- Word Bank -->
                <div class="bank-section">
                    <div class="section-label">Mavjud so'zlar</div>
                    <div class="word-bank">
                        <button v-for="(word, index) in availableWords"
                            :key="'bank-' + index"
                            @click="addWord(word, index)"
                            :disabled="checkResult?.is_correct"
                            class="word-btn word-available">
                            {{ word }}
                        </button>
                    </div>
                </div>

                <!-- Actions -->
                <div class="actions">
                    <button v-if="!checkResult?.is_correct" @click="useHint" :disabled="isChecking" class="hint-btn">
                        💡
                    </button>
                    <button v-if="!checkResult?.is_correct"
                        @click="checkAnswer"
                        :disabled="userAnswer.length === 0 || isChecking"
                        class="check-btn">
                        {{ isChecking ? 'Tekshirilmoqda...' : 'Tekshirish ✓' }}
                    </button>
                    <button v-else @click="nextSentence" class="next-btn">
                        Keyingisi →
                    </button>
                </div>

                <!-- Grammar Tip -->
                <div v-if="currentSentence?.grammar_tip && checkResult && !checkResult.is_correct" class="grammar-tip">
                    <span class="tip-icon">📚</span>
                    <div>
                        <div class="tip-title">Grammatika maslahati</div>
                        <div class="tip-text">{{ currentSentence.grammar_tip }}</div>
                    </div>
                </div>
            </main>

            <SessionComplete
                :show="showResultModal"
                :result="sessionResult"
                :level="level"
                @close="closeResultModal"
                @restart="restartLevel"
            />
        </div>
    </div>
</template>

<style scoped>
.game-container {
    min-height: 100vh;
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #1e1b4b 100%);
    position: relative;
    overflow: hidden;
}

.game-bg {
    position: absolute;
    inset: 0;
    overflow: hidden;
    pointer-events: none;
}

.orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    animation: pulse 4s ease-in-out infinite;
}

.orb-1 {
    top: -100px;
    left: 20%;
    width: 300px;
    height: 300px;
    background: rgba(59, 130, 246, 0.3);
}

.orb-2 {
    bottom: -50px;
    right: 20%;
    width: 250px;
    height: 250px;
    background: rgba(139, 92, 246, 0.3);
    animation-delay: 1s;
}

.orb-3 {
    top: 50%;
    left: -50px;
    width: 200px;
    height: 200px;
    background: rgba(6, 182, 212, 0.2);
    animation-delay: 2s;
}

@keyframes pulse {
    0%, 100% { opacity: 0.5; transform: scale(1); }
    50% { opacity: 0.8; transform: scale(1.1); }
}

.game-content {
    position: relative;
    z-index: 10;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
}

/* Header */
.game-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}

.back-btn {
    width: 48px;
    height: 48px;
    border-radius: 16px;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}

.back-btn:hover {
    background: rgba(255,255,255,0.2);
}

.level-info {
    text-align: center;
}

.level-badges {
    display: flex;
    gap: 8px;
    justify-content: center;
    margin-bottom: 4px;
}

.level-badge {
    background: linear-gradient(135deg, #3b82f6, #6366f1);
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
}

.cefr-badge {
    background: rgba(255,255,255,0.15);
    color: rgba(255,255,255,0.7);
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
}

.level-name {
    color: white;
    font-size: 18px;
    font-weight: 700;
    margin: 0;
}

.score-box {
    display: flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, rgba(234,179,8,0.2), rgba(249,115,22,0.2));
    padding: 10px 16px;
    border-radius: 16px;
    border: 1px solid rgba(234,179,8,0.3);
}

.score-icon {
    font-size: 24px;
}

.score-value {
    color: white;
    font-size: 20px;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}

/* Progress */
.progress-section {
    margin-bottom: 30px;
}

.progress-info {
    display: flex;
    justify-content: space-between;
    color: rgba(255,255,255,0.5);
    font-size: 13px;
    margin-bottom: 8px;
}

.progress-bar {
    height: 8px;
    background: rgba(255,255,255,0.1);
    border-radius: 10px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6, #6366f1);
    border-radius: 10px;
    transition: width 0.5s ease;
}

/* Main Game */
.game-main {
    flex: 1;
    display: flex;
    flex-direction: column;
}

/* Translation */
.translation-section {
    text-align: center;
    margin-bottom: 30px;
}

.translation-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(16, 185, 129, 0.2);
    border: 1px solid rgba(16, 185, 129, 0.3);
    padding: 8px 16px;
    border-radius: 30px;
    color: #34d399;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 16px;
}

.translation-text {
    color: white;
    font-size: 28px;
    font-weight: 700;
    line-height: 1.4;
    margin: 0;
}

@media (max-width: 640px) {
    .translation-text {
        font-size: 22px;
    }
}

/* Feedback */
.feedback {
    margin-top: 16px;
}

.feedback-correct {
    display: inline-block;
    background: rgba(34, 197, 94, 0.2);
    border: 1px solid rgba(34, 197, 94, 0.3);
    color: #4ade80;
    padding: 10px 20px;
    border-radius: 30px;
    font-weight: 600;
}

.feedback-wrong {
    display: inline-block;
    background: rgba(239, 68, 68, 0.2);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #f87171;
    padding: 10px 20px;
    border-radius: 30px;
    font-weight: 600;
    animation: shake 0.5s ease;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    20%, 60% { transform: translateX(-5px); }
    40%, 80% { transform: translateX(5px); }
}

/* Sections */
.section-label {
    text-align: center;
    color: rgba(255,255,255,0.4);
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 12px;
}

/* Answer Zone */
.answer-section {
    margin-bottom: 24px;
}

.answer-zone {
    min-height: 100px;
    background: rgba(255,255,255,0.05);
    border: 2px dashed rgba(255,255,255,0.2);
    border-radius: 20px;
    padding: 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
}

.answer-zone.answer-correct {
    border-color: rgba(34, 197, 94, 0.5);
    background: rgba(34, 197, 94, 0.1);
}

.answer-zone.answer-wrong {
    border-color: rgba(239, 68, 68, 0.5);
    background: rgba(239, 68, 68, 0.1);
}

.placeholder-text {
    color: rgba(255,255,255,0.3);
    font-size: 15px;
}

/* Word Bank */
.bank-section {
    margin-bottom: 30px;
}

.word-bank {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: center;
    min-height: 60px;
}

/* Word Buttons */
.word-btn {
    padding: 12px 20px;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.word-available {
    background: rgba(255,255,255,0.15);
    color: white;
    border: 1px solid rgba(255,255,255,0.25);
}

.word-available:hover:not(:disabled) {
    background: rgba(255,255,255,0.25);
    transform: translateY(-2px);
}

.word-selected {
    background: linear-gradient(135deg, #3b82f6, #6366f1);
    color: white;
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
}

.word-selected:hover:not(:disabled) {
    transform: scale(1.05);
}

.word-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Actions */
.actions {
    display: flex;
    gap: 12px;
    max-width: 500px;
    margin: 0 auto 24px;
    width: 100%;
}

.hint-btn {
    width: 64px;
    height: 64px;
    background: rgba(234, 179, 8, 0.2);
    border: 1px solid rgba(234, 179, 8, 0.3);
    border-radius: 16px;
    font-size: 28px;
    cursor: pointer;
    transition: all 0.2s;
}

.hint-btn:hover:not(:disabled) {
    background: rgba(234, 179, 8, 0.3);
    transform: scale(1.05);
}

.hint-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.check-btn {
    flex: 1;
    height: 64px;
    background: linear-gradient(135deg, #3b82f6, #6366f1);
    color: white;
    border: none;
    border-radius: 16px;
    font-size: 18px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 4px 20px rgba(59, 130, 246, 0.4);
}

.check-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 25px rgba(59, 130, 246, 0.5);
}

.check-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    box-shadow: none;
}

.next-btn {
    flex: 1;
    height: 64px;
    background: linear-gradient(135deg, #22c55e, #10b981);
    color: white;
    border: none;
    border-radius: 16px;
    font-size: 18px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 4px 20px rgba(34, 197, 94, 0.4);
    animation: pulse-btn 2s infinite;
}

@keyframes pulse-btn {
    0%, 100% { box-shadow: 0 4px 20px rgba(34, 197, 94, 0.4); }
    50% { box-shadow: 0 4px 30px rgba(34, 197, 94, 0.6); }
}

.next-btn:hover {
    transform: translateY(-2px);
}

/* Grammar Tip */
.grammar-tip {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    background: rgba(59, 130, 246, 0.15);
    border: 1px solid rgba(59, 130, 246, 0.25);
    border-radius: 16px;
    padding: 16px;
    max-width: 500px;
    margin: 0 auto;
}

.tip-icon {
    font-size: 24px;
}

.tip-title {
    color: #60a5fa;
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 4px;
}

.tip-text {
    color: rgba(255,255,255,0.8);
    font-size: 14px;
}
</style>
