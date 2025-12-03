<script setup>
import { Head, router } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import TournamentBracket from '@/Components/Gamification/TournamentBracket.vue';

const props = defineProps({
    tournament: Object,
    isJoined: Boolean,
});

const joinTournament = () => {
    router.post(route('student.tournaments.join', props.tournament.id));
};
</script>

<template>

    <Head :title="tournament.title" />

    <StudentLayout>
        <div class="max-w-6xl mx-auto py-8">
            <!-- Header -->
            <div class="bg-white rounded-3xl p-8 border border-gray-200 shadow-sm mb-8 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-64 h-64 bg-yellow-100 rounded-full -mr-32 -mt-32 opacity-50"></div>

                <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <span
                                class="px-3 py-1 bg-yellow-100 text-yellow-800 text-xs font-bold rounded-full uppercase tracking-wide">
                                {{ tournament.status }}
                            </span>
                            <span class="text-gray-500 text-sm">{{ new Date(tournament.start_date).toLocaleDateString()
                                }}</span>
                        </div>
                        <h1 class="text-4xl font-bold text-gray-900 mb-2">{{ tournament.title }}</h1>
                        <p class="text-gray-600 max-w-xl">{{ tournament.description }}</p>
                    </div>

                    <div class="flex flex-col items-end gap-4">
                        <div class="text-right">
                            <div class="text-sm text-gray-500 mb-1">Sovrin jamg'armasi</div>
                            <div class="text-2xl font-bold text-yellow-600 flex items-center gap-1">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                                {{ tournament.prize_pool || 0 }} XP
                            </div>
                        </div>

                        <button v-if="!isJoined && tournament.status === 'upcoming'" @click="joinTournament"
                            class="px-8 py-3 bg-purple-600 text-white font-bold rounded-xl hover:bg-purple-700 transition-colors shadow-lg shadow-purple-200">
                            Qo'shilish
                        </button>
                        <div v-else-if="isJoined"
                            class="px-6 py-3 bg-green-100 text-green-700 font-bold rounded-xl flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                            Siz ishtirok etyapsiz
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bracket -->
            <div class="bg-white rounded-3xl border border-gray-200 p-8 overflow-hidden">
                <h3 class="text-xl font-bold text-gray-900 mb-6">Turnir jadvali</h3>
                <TournamentBracket :tournament="tournament" />
            </div>
        </div>
    </StudentLayout>
</template>
