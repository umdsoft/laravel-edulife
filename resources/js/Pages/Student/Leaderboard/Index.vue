<template>
    <StudentLayout title="Leaderboard">
        <div class="max-w-6xl mx-auto py-6 sm:px-6 lg:px-8">

            <!-- Type Tabs -->
            <div class="flex gap-2 mb-6">
                <a :href="route('student.leaderboard.index', { type: 'xp' })" class="px-6 py-3 rounded-lg font-semibold"
                    :class="type === 'xp' ? 'bg-gradient-to-r from-green-500 to-green-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'">
                    🌟 XP Leaders
                </a>
                <a :href="route('student.leaderboard.index', { type: 'battles' })"
                    class="px-6 py-3 rounded-lg font-semibold"
                    :class="type === 'battles' ? 'bg-gradient-to-r from-red-500 to-red-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'">
                    ⚔️ Battle Leaders
                </a>
                <a :href="route('student.leaderboard.index', { type: 'weekly' })"
                    class="px-6 py-3 rounded-lg font-semibold"
                    :class="type === 'weekly' ? 'bg-gradient-to-r from-purple-500 to-purple-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'">
                    🔥 Weekly Top
                </a>
            </div>

            <!-- User Position (if not in top 100) -->
            <div v-if="user_position && user_position > 100"
                class="bg-yellow-50 border-2 border-yellow-200 rounded-xl p-4 mb-6">
                <p class="text-center font-semibold text-yellow-800">
                    Your Position: <span class="text-2xl">#{{ user_position }}</span>
                </p>
            </div>

            <!-- Leaderboard Table -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Rank</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Student</th>
                                <th v-if="type === 'xp'"
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Level</th>
                                <th v-if="type === 'xp'"
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    XP</th>
                                <th v-if="type === 'battles'"
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    ELO</th>
                                <th v-if="type === 'battles'"
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Wins</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Rank</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="(player, index) in leaderboard" :key="player.user_id"
                                :class="{ 'bg-yellow-50': player.user_id === $page.props.auth.user.id }">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center justify-center w-10 h-10 rounded-full font-bold"
                                        :class="getRankClass(player.rank)">
                                        {{ player.rank }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 flex-shrink-0">
                                            <img class="h-10 w-10 rounded-full"
                                                :src="player.avatar || '/default-avatar.png'" alt="">
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ player.name }}</div>
                                            <div v-if="player.streak_days" class="text-xs text-gray-500">🔥 {{
                                                player.streak_days }} day streak</div>
                                        </div>
                                    </div>
                                </td>
                                <td v-if="type === 'xp'"
                                    class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-purple-600">
                                    Level {{ player.level }}
                                </td>
                                <td v-if="type === 'xp'"
                                    class="px-6 py-4 whitespace-nowrap text-sm font-bold text-green-600">
                                    {{ player.xp.toLocaleString() }} XP
                                </td>
                                <td v-if="type === 'battles'"
                                    class="px-6 py-4 whitespace-nowrap text-sm font-bold text-blue-600">
                                    {{ player.elo_rating }}
                                </td>
                                <td v-if="type === 'battles'" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ player.battles_won }} / {{ player.battles_played }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold capitalize"
                                        :class="getRankBadgeClass(player.rank_badge)">
                                        {{ player.rank_badge }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </StudentLayout>
</template>

<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';

const props = defineProps({
    leaderboard: Array,
    user_position: Number,
    type: String,
});

const getRankClass = (rank) => {
    if (rank === 1) return 'bg-gradient-to-br from-yellow-300 to-yellow-500 text-yellow-900';
    if (rank === 2) return 'bg-gradient-to-br from-gray-300 to-gray-400 text-gray-900';
    if (rank === 3) return 'bg-gradient-to-br from-orange-300 to-orange-500 text-orange-900';
    return 'bg-gray-100 text-gray-700';
};

const getRankBadgeClass = (rank) => {
    const classes = {
        bronze: 'bg-orange-100 text-orange-700',
        silver: 'bg-gray-200 text-gray-700',
        gold: 'bg-yellow-100 text-yellow-700',
        platinum: 'bg-cyan-100 text-cyan-700',
        diamond: 'bg-blue-100 text-blue-700',
        master: 'bg-purple-100 text-purple-700',
    };
    return classes[rank] || classes.bronze;
};
</script>
