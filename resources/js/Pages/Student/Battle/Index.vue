<template>
    <StudentLayout title="1v1 Battle Arena">
        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100 text-sm">ELO Rating</p>
                            <p class="text-3xl font-bold">{{ stats.elo_rating }}</p>
                        </div>
                        <div class="text-5xl opacity-50">🎯</div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-6 text-white shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-100 text-sm">Rank</p>
                            <p class="text-3xl font-bold capitalize">{{ stats.rank }}</p>
                        </div>
                        <div class="text-5xl opacity-50">👑</div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-100 text-sm">Wins</p>
                            <p class="text-3xl font-bold">{{ stats.battles_won }}</p>
                        </div>
                        <div class="text-5xl opacity-50">🏆</div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl p-6 text-white shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-red-100 text-sm">Win Rate</p>
                            <p class="text-3xl font-bold">{{ stats.win_rate }}%</p>
                        </div>
                        <div class="text-5xl opacity-50">📊</div>
                    </div>
                </div>
            </div>

            <!-- Find Match Section -->
            <div class="bg-white rounded-xl shadow-md p-8 mb-8">
                <h2 class="text-2xl font-bold mb-6 text-center">Find a Battle</h2>

                <div v-if="!searching && !activeBattle" class="max-w-md mx-auto">
                    <select v-model="selectedDirection" class="w-full px-4 py-3 rounded-lg border mb-4">
                        <option :value="null">Any Topic</option>
                        <option v-for="dir in directions" :key="dir.id" :value="dir.id">
                            {{ dir.name }}
                        </option>
                    </select>

                    <button @click="startSearch"
                        class="w-full bg-gradient-to-r from-red-500 to-red-600 text-white font-bold py-4 px-6 rounded-lg hover:from-red-600 hover:to-red-700 transition-all text-lg">
                        🔥 Find Opponent
                    </button>
                </div>

                <div v-if="searching" class="text-center">
                    <div class="text-6xl mb-4 animate-bounce">⚔️</div>
                    <p class="text-xl font-bold mb-2">Searching for opponent...</p>
                    <p class="text-gray-600">{{ searchTime }}s</p>
                    <button @click="cancelSearch" class="mt-4 text-red-600 hover:underline">
                        Cancel
                    </button>
                </div>
            </div>

            <!-- Recent Battles -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-xl font-bold mb-4">Recent Battles</h3>

                <div v-if="recentBattles.length === 0" class="text-center py-8 text-gray-500">
                    No battles yet. Start your first battle!
                </div>

                <div v-else class="space-y-3">
                    <div v-for="battle in recentBattles" :key="battle.id"
                        class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center gap-4">
                            <div
                                :class="battle.winner_id === $page.props.auth.user.id ? 'text-green-600' : 'text-red-600'">
                                {{ battle.winner_id === $page.props.auth.user.id ? '🏆' : '❌' }}
                            </div>
                            <div>
                                <p class="font-semibold">
                                    vs {{ battle.player1_id === $page.props.auth.user.id ? battle.player2.first_name :
                                    battle.player1.first_name }}
                                </p>
                                <p class="text-sm text-gray-600">
                                    {{ battle.player1_score }} - {{ battle.player2_score }}
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-600">{{ formatDate(battle.ended_at) }}</p>
                            <p class="text-sm font-bold"
                                :class="battle.player1_elo_change > 0 ? 'text-green-600' : 'text-red-600'">
                                {{ battle.player1_id === $page.props.auth.user.id ? battle.player1_elo_change :
                                    battle.player2_elo_change > 0 ? '+' : '' }}
                                {{ battle.player1_id === $page.props.auth.user.id ? battle.player1_elo_change :
                                battle.player2_elo_change }} ELO
                            </p>
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
    activeBattle: Object,
    recentBattles: Array,
    stats: Object,
    directions: Array,
});

const searching = ref(false);
const selectedDirection = ref(null);
const searchTime = ref(0);
let searchInterval = null;

const startSearch = () => {
    searching.value = true;
    searchTime.value = 0;

    searchInterval = setInterval(() => {
        searchTime.value++;
    }, 1000);

    axios.post(route('student.battles.find'), {
        direction_id: selectedDirection.value,
    }).then(response => {
        if (response.data.match_found) {
            router.visit(response.data.redirect);
        } else {
            // Poll for match
            pollForMatch(response.data.battle_id);
        }
    });
};

const pollForMatch = (battleId) => {
    const interval = setInterval(() => {
        axios.get(route('student.battles.show', battleId))
            .then(response => {
                if (response.data.status === 'in_progress') {
                    clearInterval(interval);
                    router.visit(route('student.battles.show', battleId));
                }
            });
    }, 2000);
};

const cancelSearch = () => {
    searching.value = false;
    clearInterval(searchInterval);
    axios.post(route('student.battles.cancel'));
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};

onMounted(() => {
    if (props.activeBattle) {
        router.visit(route('student.battles.show', props.activeBattle.id));
    }
});

onUnmounted(() => {
    if (searchInterval) {
        clearInterval(searchInterval);
    }
});
</script>
