<template>
    <StudentLayout title="Daily Missions">
        <div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">

            <!-- Header with Timer -->
            <div class="bg-gradient-to-r from-orange-500 to-red-500 rounded-xl p-8 text-white mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-3xl font-bold mb-2">Daily Missions</h2>
                        <p class="text-orange-100">Complete all missions for bonus rewards!</p>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl font-mono font-bold">{{ reset_time.hours }}:{{
                            String(reset_time.minutes).padStart(2, '0') }}:{{ String(reset_time.seconds).padStart(2,
                            '0') }}</div>
                        <div class="text-sm text-orange-200">Until Reset</div>
                    </div>
                </div>
            </div>

            <!-- Progress -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <div class="flex items-center justify-between mb-3">
                    <span class="font-semibold">Daily Progress</span>
                    <span class="text-sm text-gray-600">{{ completed_count }} / {{ total_count }}</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="bg-gradient-to-r from-green-400 to-blue-500 h-3 rounded-full transition-all"
                        :style="{ width: (completed_count / total_count * 100) + '%' }"></div>
                </div>
            </div>

            <!-- Missions List -->
            <div class="space-y-4">
                <div v-for="userMission in missions" :key="userMission.id" class="bg-white rounded-xl shadow-md p-6"
                    :class="{ 'ring-2 ring-green-500': userMission.is_completed && !userMission.is_claimed }">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-start gap-4">
                            <div class="text-4xl">{{ userMission.mission.icon }}</div>
                            <div>
                                <h3 class="font-bold text-lg">{{ userMission.mission.title }}</h3>
                                <p class="text-gray-600 text-sm">{{ userMission.mission.description }}</p>
                            </div>
                        </div>

                        <div v-if="userMission.is_completed" class="flex items-center gap-2">
                            <button v-if="!userMission.is_claimed" @click="claimReward(userMission)"
                                class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 font-semibold">
                                Claim Reward
                            </button>
                            <span v-else class="text-green-600 font-semibold">✓ Claimed</span>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mb-3">
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-gray-600">Progress</span>
                            <span class="font-semibold">{{ userMission.current_value }} / {{ userMission.target_value
                                }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full transition-all"
                                :style="{ width: (userMission.current_value / userMission.target_value * 100) + '%' }">
                            </div>
                        </div>
                    </div>

                    <!-- Rewards -->
                    <div class="flex items-center gap-4 text-sm">
                        <div class="flex items-center gap-1 text-green-600">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />
                            </svg>
                            <span>{{ userMission.mission.xp_reward }} XP</span>
                        </div>
                        <div class="flex items-center gap-1 text-yellow-600">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24"
                                class="w-4 h-4">
                                <path fill-rule="evenodd"
                                    d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM12.75 6a.75.75 0 0 0-1.5 0v.816a3.836 3.836 0 0 0-1.72.756c-.712.566-1.112 1.35-1.112 2.178 0 .829.4 1.612 1.113 2.178.502.4 1.102.647 1.719.756v2.978a2.536 2.536 0 0 1-.921-.421l-.879-.66a.75.75 0 0 0-.9 1.2l.879.66c.533.4 1.169.645 1.821.75V18a.75.75 0 0 0 1.5 0v-.81a4.124 4.124 0 0 0 1.821-.749c.745-.559 1.179-1.344 1.179-2.191 0-.847-.434-1.632-1.179-2.191a4.122 4.122 0 0 0-1.821-.75V8.354c.29.082.559.213.786.393l.415.33a.75.75 0 0 0 .933-1.175l-.415-.33a3.836 3.836 0 0 0-1.719-.755V6Z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span>{{ userMission.mission.coin_reward }} COIN</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </StudentLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';

const props = defineProps({
    missions: Array,
    completed_count: Number,
    total_count: Number,
    reset_time: Object,
});

const claimReward = (userMission) => {
    axios.post(route('student.missions.claim', userMission.id))
        .then(() => {
            router.reload();
        });
};

// Update timer every second
let timerInterval = null;

onMounted(() => {
    timerInterval = setInterval(() => {
        if (props.reset_time.seconds > 0) {
            props.reset_time.seconds--;
        } else if (props.reset_time.minutes > 0) {
            props.reset_time.minutes--;
            props.reset_time.seconds = 59;
        } else if (props.reset_time.hours > 0) {
            props.reset_time.hours--;
            props.reset_time.minutes = 59;
            props.reset_time.seconds = 59;
        } else {
            router.reload(); // Refresh when timer reaches 0
        }
    }, 1000);
});

onUnmounted(() => {
    if (timerInterval) {
        clearInterval(timerInterval);
    }
});
</script>
