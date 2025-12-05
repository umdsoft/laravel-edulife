<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    leaderboard: Array,
    period: String,
    filter: String,
    userRank: Object,
});

const currentPeriod = ref(props.period);
const currentFilter = ref(props.filter);

const periods = [
    { value: 'weekly', label: 'Haftalik' },
    { value: 'monthly', label: 'Oylik' },
    { value: 'all_time', label: 'Umumiy' },
];

const getRankBadge = (rank) => {
    if (rank === 1) return '🥇';
    if (rank === 2) return '🥈';
    if (rank === 3) return '🥉';
    return rank;
};
</script>

<template>

    <Head title="Lab Reyting - Virtual Lab" />

    <StudentLayout>
        <div class="leaderboard-page">
            <header class="page-header">
                <Link :href="route('student.lab.index')" class="back-link">
                ← Orqaga
                </Link>
                <h1>🏆 Lab Reyting</h1>
            </header>

            <!-- Filters -->
            <div class="filters-bar">
                <div class="period-tabs">
                    <Link v-for="p in periods" :key="p.value"
                        :href="route('student.lab.leaderboard', { period: p.value })" class="tab"
                        :class="{ active: period === p.value }">
                    {{ p.label }}
                    </Link>
                </div>
            </div>

            <!-- User's Rank Card -->
            <div v-if="userRank" class="user-rank-card">
                <div class="rank-info">
                    <span class="rank-position">{{ getRankBadge(userRank.rank) }}</span>
                    <span class="rank-text">Sizning o'rningiz</span>
                </div>
                <div class="rank-stats">
                    <div class="stat">
                        <span class="value">{{ userRank.total_xp }}</span>
                        <span class="label">XP</span>
                    </div>
                    <div class="stat">
                        <span class="value">{{ userRank.completed_experiments }}</span>
                        <span class="label">Tajriba</span>
                    </div>
                </div>
            </div>

            <!-- Leaderboard Table -->
            <div class="leaderboard-container">
                <table class="leaderboard-table">
                    <thead>
                        <tr>
                            <th class="rank-col">O'rin</th>
                            <th class="user-col">O'quvchi</th>
                            <th class="level-col">Daraja</th>
                            <th class="xp-col">XP</th>
                            <th class="exp-col">Tajribalar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="entry in leaderboard" :key="entry.user.id" :class="{
                            'top-3': entry.rank <= 3,
                            'current-user': entry.user.id === $page.props.auth?.user?.id,
                        }">
                            <td class="rank-cell">
                                <span class="rank-badge" :class="`rank-${entry.rank}`">
                                    {{ getRankBadge(entry.rank) }}
                                </span>
                            </td>
                            <td class="user-cell">
                                <div class="user-info">
                                    <div class="avatar" :style="{ background: entry.user.avatar_color || '#3b82f6' }">
                                        {{ entry.user.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div class="user-details">
                                        <span class="name">{{ entry.user.name }}</span>
                                        <span class="school">{{ entry.user.school || 'O\'quvchi' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="level-cell">
                                <span class="level-badge">{{ entry.stats.level }}</span>
                            </td>
                            <td class="xp-cell">
                                <span class="xp-value">{{ entry.stats.total_xp.toLocaleString() }}</span>
                            </td>
                            <td class="exp-cell">
                                {{ entry.stats.completed_experiments }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </StudentLayout>
</template>

<style scoped>
.leaderboard-page {
    max-width: 900px;
    margin: 0 auto;
    padding: 0 1.5rem 3rem;
}

.page-header {
    padding: 1.5rem 0;
}

.back-link {
    display: inline-block;
    color: var(--gray-500);
    text-decoration: none;
    margin-bottom: 1rem;
}

h1 {
    font-size: 1.75rem;
    font-weight: 800;
}

.filters-bar {
    margin-bottom: 1.5rem;
}

.period-tabs {
    display: flex;
    gap: 0.5rem;
    background: var(--gray-100);
    padding: 0.375rem;
    border-radius: 12px;
    width: fit-content;
}

.dark .period-tabs {
    background: var(--gray-800);
}

.tab {
    padding: 0.625rem 1.25rem;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 500;
    color: var(--gray-600);
    text-decoration: none;
    transition: all 0.2s;
}

.tab.active {
    background: white;
    color: var(--gray-900);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.dark .tab.active {
    background: var(--gray-700);
    color: white;
}

.user-rank-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.5rem;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    border-radius: 16px;
    color: white;
    margin-bottom: 1.5rem;
}

.rank-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.rank-position {
    font-size: 2rem;
}

.rank-text {
    font-size: 1.125rem;
    font-weight: 600;
}

.rank-stats {
    display: flex;
    gap: 2rem;
}

.rank-stats .stat {
    text-align: center;
}

.rank-stats .value {
    display: block;
    font-size: 1.25rem;
    font-weight: 700;
}

.rank-stats .label {
    font-size: 0.8rem;
    opacity: 0.8;
}

.leaderboard-container {
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: 16px;
    overflow: hidden;
}

.dark .leaderboard-container {
    background: var(--gray-800);
    border-color: var(--gray-700);
}

.leaderboard-table {
    width: 100%;
    border-collapse: collapse;
}

.leaderboard-table th {
    padding: 1rem 1.25rem;
    text-align: left;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--gray-500);
    text-transform: uppercase;
    background: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
}

.dark .leaderboard-table th {
    background: var(--gray-750);
    border-color: var(--gray-700);
}

.leaderboard-table td {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--gray-100);
}

.dark .leaderboard-table td {
    border-color: var(--gray-700);
}

.leaderboard-table tr:last-child td {
    border-bottom: none;
}

.leaderboard-table tr.top-3 {
    background: rgba(245, 158, 11, 0.05);
}

.leaderboard-table tr.current-user {
    background: rgba(59, 130, 246, 0.1);
}

.rank-badge {
    font-size: 1.25rem;
}

.rank-badge.rank-1 {
    font-size: 1.5rem;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.avatar {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
}

.user-details {
    display: flex;
    flex-direction: column;
}

.name {
    font-weight: 600;
    color: var(--gray-900);
}

.dark .name {
    color: var(--gray-100);
}

.school {
    font-size: 0.8rem;
    color: var(--gray-500);
}

.level-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
    font-weight: 700;
    font-size: 0.9rem;
    border-radius: 8px;
}

.xp-value {
    font-weight: 700;
    color: #10b981;
}

.exp-cell {
    color: var(--gray-600);
}
</style>
