<script setup>
import { Head, Link } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';

const props = defineProps({
    stats: Object,
    categories: Array,
    recentAttempts: Array,
    earnedBadges: Array,
    skills: Object,
    bestExperiments: Array,
});
</script>

<template>

    <Head title="Mening Progressim - Virtual Lab" />

    <StudentLayout>
        <div class="progress-page">
            <header class="page-header">
                <Link :href="route('student.lab.index')" class="back-link">← Orqaga</Link>
                <h1>📈 Mening Progressim</h1>
            </header>

            <!-- Stats Overview -->
            <div class="stats-grid">
                <div class="stat-card level-card">
                    <div class="level-circle">
                        <span class="level-number">{{ stats.level }}</span>
                    </div>
                    <div class="level-info">
                        <span class="level-title">{{ stats.level_title }}</span>
                        <div class="xp-progress">
                            <div class="bar" :style="{ width: stats.level_progress + '%' }"></div>
                        </div>
                        <span class="xp-text">{{ stats.xp_to_next_level }} XP keyingi darajagacha</span>
                    </div>
                </div>

                <div class="stat-card">
                    <span class="stat-icon">⚡</span>
                    <div>
                        <span class="stat-value">{{ stats.total_xp.toLocaleString() }}</span>
                        <span class="stat-label">Jami XP</span>
                    </div>
                </div>

                <div class="stat-card">
                    <span class="stat-icon">🧪</span>
                    <div>
                        <span class="stat-value">{{ stats.completed_experiments }}</span>
                        <span class="stat-label">Yakunlangan tajribalar</span>
                    </div>
                </div>

                <div class="stat-card">
                    <span class="stat-icon">🔥</span>
                    <div>
                        <span class="stat-value">{{ stats.current_streak }}</span>
                        <span class="stat-label">Kunlik streak</span>
                    </div>
                </div>

                <div class="stat-card">
                    <span class="stat-icon">🏅</span>
                    <div>
                        <span class="stat-value">{{ stats.badges_count }}</span>
                        <span class="stat-label">Badgelar</span>
                    </div>
                </div>
            </div>

            <!-- Categories Progress -->
            <section class="section">
                <h2 class="section-title">Bo'limlar bo'yicha</h2>
                <div class="categories-progress">
                    <div v-for="cat in categories" :key="cat.id" class="category-progress-item">
                        <div class="cat-icon" :style="{ background: cat.color }">{{ cat.icon }}</div>
                        <div class="cat-info">
                            <span class="cat-name">{{ cat.name }}</span>
                            <div class="progress-bar">
                                <div class="bar"
                                    :style="{ width: cat.progress?.completion_percent + '%', background: cat.color }">
                                </div>
                            </div>
                        </div>
                        <span class="cat-percent">{{ Math.round(cat.progress?.completion_percent || 0) }}%</span>
                    </div>
                </div>
            </section>

            <!-- Earned Badges -->
            <section v-if="earnedBadges?.length" class="section">
                <h2 class="section-title">🏅 Qo'lga kiritilgan badgelar</h2>
                <div class="badges-row">
                    <div v-for="badge in earnedBadges" :key="badge.id" class="badge-mini">
                        <span class="badge-icon">{{ badge.icon }}</span>
                        <span class="badge-name">{{ badge.name }}</span>
                    </div>
                </div>
                <Link :href="route('student.lab.badges')" class="view-all-link">
                Barcha badgelarni ko'rish →
                </Link>
            </section>

            <!-- Recent Attempts -->
            <section class="section">
                <h2 class="section-title">So'nggi urinishlar</h2>
                <div class="attempts-table">
                    <div v-for="a in recentAttempts" :key="a.id" class="attempt-row">
                        <div class="attempt-info">
                            <span class="attempt-title">{{ a.experiment.title }}</span>
                            <span class="attempt-cat">{{ a.experiment.category }}</span>
                        </div>
                        <span class="attempt-grade" :class="a.status">
                            {{ a.grade || a.status_label }}
                        </span>
                        <span class="attempt-percent">{{ a.percentage }}%</span>
                        <span class="attempt-xp">+{{ a.xp_earned }} XP</span>
                        <span class="attempt-date">{{ a.created_at }}</span>
                    </div>
                </div>
            </section>
        </div>
    </StudentLayout>
</template>

<style scoped>
.progress-page {
    max-width: 1000px;
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
    margin-bottom: 0.75rem;
}

h1 {
    font-size: 1.75rem;
    font-weight: 800;
}

.stats-grid {
    display: grid;
    grid-template-columns: 2fr repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 2rem;
}

@media (max-width: 900px) {
    .stats-grid {
        grid-template-columns: 1fr 1fr;
    }
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem;
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: 14px;
}

.dark .stat-card {
    background: var(--gray-800);
    border-color: var(--gray-700);
}

.stat-icon {
    font-size: 1.5rem;
}

.stat-value {
    display: block;
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--gray-900);
}

.dark .stat-value {
    color: var(--gray-100);
}

.stat-label {
    font-size: 0.8rem;
    color: var(--gray-500);
}

.level-card {
    grid-column: span 1;
}

.level-circle {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #f59e0b, #d97706);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.level-number {
    font-size: 1.5rem;
    font-weight: 800;
    color: white;
}

.level-info {
    flex: 1;
}

.level-title {
    font-weight: 600;
    color: var(--gray-900);
}

.dark .level-title {
    color: var(--gray-100);
}

.xp-progress {
    height: 8px;
    background: var(--gray-200);
    border-radius: 4px;
    margin: 0.5rem 0;
    overflow: hidden;
}

.dark .xp-progress {
    background: var(--gray-700);
}

.xp-progress .bar {
    height: 100%;
    background: linear-gradient(90deg, #f59e0b, #fbbf24);
    border-radius: 4px;
}

.xp-text {
    font-size: 0.75rem;
    color: var(--gray-500);
}

.section {
    margin-bottom: 2rem;
}

.section-title {
    font-size: 1.125rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.categories-progress {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.category-progress-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.875rem 1rem;
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: 12px;
}

.dark .category-progress-item {
    background: var(--gray-800);
    border-color: var(--gray-700);
}

.cat-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.cat-info {
    flex: 1;
}

.cat-name {
    display: block;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.progress-bar {
    height: 6px;
    background: var(--gray-200);
    border-radius: 3px;
    overflow: hidden;
}

.dark .progress-bar {
    background: var(--gray-700);
}

.progress-bar .bar {
    height: 100%;
    border-radius: 3px;
}

.cat-percent {
    font-weight: 700;
    color: var(--gray-500);
}

.badges-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.badge-mini {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.875rem;
    background: var(--gray-100);
    border-radius: 10px;
}

.dark .badge-mini {
    background: var(--gray-800);
}

.badge-icon {
    font-size: 1rem;
}

.badge-name {
    font-size: 0.85rem;
    font-weight: 500;
}

.view-all-link {
    color: var(--primary);
    text-decoration: none;
    font-size: 0.9rem;
}

.attempts-table {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.attempt-row {
    display: grid;
    grid-template-columns: 1fr 80px 60px 80px 100px;
    gap: 1rem;
    align-items: center;
    padding: 1rem;
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: 10px;
}

.dark .attempt-row {
    background: var(--gray-800);
    border-color: var(--gray-700);
}

.attempt-title {
    font-weight: 600;
}

.attempt-cat {
    display: block;
    font-size: 0.8rem;
    color: var(--gray-500);
}

.attempt-grade {
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    text-align: center;
}

.attempt-grade.completed {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
}

.attempt-percent {
    font-weight: 700;
    color: var(--gray-600);
}

.attempt-xp {
    color: #10b981;
    font-weight: 600;
}

.attempt-date {
    font-size: 0.8rem;
    color: var(--gray-400);
}
</style>
