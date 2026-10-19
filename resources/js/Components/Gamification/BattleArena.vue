<script setup>
import { ref, computed } from 'vue';
import { CheckCircleIcon } from '@heroicons/vue/24/outline';
import BattleTimer from './BattleTimer.vue';
import PlayerStats from './PlayerStats.vue';
import QuestionCard from './QuestionCard.vue';

const props = defineProps({
    battle: Object,
    currentUser: Object,
});

const emit = defineEmits(['answer']);

const currentQuestionIndex = ref(0);
const selectedOptionId = ref(null);
const isAnswered = ref(false);

const currentQuestion = computed(() => {
    return props.battle.questions[currentQuestionIndex.value]?.question;
});

const opponent = computed(() => {
    return props.battle.player1_id === props.currentUser.id
        ? props.battle.player2
        : props.battle.player1;
});

const myScore = computed(() => {
    return props.battle.player1_id === props.currentUser.id
        ? props.battle.player1_score
        : props.battle.player2_score;
});

const opponentScore = computed(() => {
    return props.battle.player1_id === props.currentUser.id
        ? props.battle.player2_score
        : props.battle.player1_score;
});

const handleSelect = (optionId) => {
    if (isAnswered.value) return;

    selectedOptionId.value = optionId;
    isAnswered.value = true;

    emit('answer', {
        questionId: currentQuestion.value.id,
        optionId: optionId
    });

    // Auto advance after delay (simulated for now)
    setTimeout(() => {
        if (currentQuestionIndex.value < props.battle.questions.length - 1) {
            currentQuestionIndex.value++;
            selectedOptionId.value = null;
            isAnswered.value = false;
        }
    }, 1500);
};

const handleTimeout = () => {
    if (!isAnswered.value) {
        // Handle timeout as wrong answer or skip
        handleSelect(null);
    }
};

const totalQuestions = computed(() => props.battle.questions?.length || 0);
</script>

<template>
    <div class="flex min-h-[560px] flex-col gap-6">
        <!-- Players + timer -->
        <div class="grid grid-cols-2 items-center gap-3 md:grid-cols-[1fr_auto_1fr] md:gap-6">
            <PlayerStats :player="currentUser" :score="myScore" :is-current-user="true" />

            <div class="order-first col-span-2 flex flex-col items-center gap-1 md:order-none md:col-span-1">
                <BattleTimer :duration="15" :active="!isAnswered" @timeout="handleTimeout"
                    :key="currentQuestionIndex" />
                <span v-if="totalQuestions" class="text-xs font-medium tabular-nums text-fg-subtle">
                    Savol {{ Math.min(currentQuestionIndex + 1, totalQuestions) }} / {{ totalQuestions }}
                </span>
            </div>

            <PlayerStats :player="opponent" :score="opponentScore" :is-current-user="false" />
        </div>

        <!-- Game area -->
        <div class="flex flex-1 items-center justify-center">
            <transition name="fade" mode="out-in">
                <QuestionCard v-if="currentQuestion" :key="currentQuestion.id" :question="currentQuestion"
                    :selected-option-id="selectedOptionId" :disabled="isAnswered" @select="handleSelect" />

                <div v-else class="text-center" role="status">
                    <span class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-success-50 text-success">
                        <CheckCircleIcon class="h-6 w-6" aria-hidden="true" />
                    </span>
                    <h2 class="text-xl font-semibold text-fg">Jang yakunlandi</h2>
                    <p class="mt-1 text-sm text-fg-muted">Natijalar hisoblanmoqda…</p>
                </div>
            </transition>
        </div>

        <!-- Progress -->
        <div v-if="totalQuestions" class="flex justify-center gap-2" aria-hidden="true">
            <span v-for="(q, index) in battle.questions" :key="q.id"
                class="h-2 rounded-full transition-all duration-150"
                :class="index === currentQuestionIndex ? 'w-6 bg-brand-600' : (index < currentQuestionIndex ? 'w-2 bg-fg-subtle' : 'w-2 bg-surface-3')">
            </span>
        </div>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease-out;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

@media (prefers-reduced-motion: reduce) {
    .fade-enter-active,
    .fade-leave-active {
        transition: none;
    }
}
</style>
