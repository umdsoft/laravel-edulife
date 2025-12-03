<script setup>
import { ref, computed } from 'vue';
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
</script>

<template>
    <div class="min-h-[600px] flex flex-col">
        <!-- Header / Stats -->
        <div class="flex justify-between items-center mb-8">
            <PlayerStats :player="currentUser" :score="myScore" :is-current-user="true" class="w-64" />

            <div class="flex flex-col items-center">
                <div class="text-sm font-bold text-gray-500 uppercase tracking-widest mb-2">VS</div>
                <BattleTimer :duration="15" :active="!isAnswered" @timeout="handleTimeout"
                    :key="currentQuestionIndex" />
            </div>

            <PlayerStats :player="opponent" :score="opponentScore" :is-current-user="false" class="w-64" />
        </div>

        <!-- Game Area -->
        <div class="flex-1 flex items-center justify-center">
            <transition name="fade" mode="out-in">
                <QuestionCard v-if="currentQuestion" :key="currentQuestion.id" :question="currentQuestion"
                    :selected-option-id="selectedOptionId" :disabled="isAnswered" @select="handleSelect" />

                <div v-else class="text-center">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Jang yakunlandi!</h2>
                    <p class="text-gray-500">Natijalar hisoblanmoqda...</p>
                </div>
            </transition>
        </div>

        <!-- Progress -->
        <div class="mt-8 flex justify-center gap-2">
            <div v-for="(q, index) in battle.questions" :key="q.id" class="w-3 h-3 rounded-full transition-colors"
                :class="index === currentQuestionIndex ? 'bg-purple-600 scale-125' : (index < currentQuestionIndex ? 'bg-gray-300' : 'bg-gray-100')">
            </div>
        </div>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
