<script setup>
import { Head, Link } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';

const props = defineProps({
    category: Object,
    experiments: Array,
});

const getCategoryIcon = (iconName) => {
    const icons = {
        'cog': '⚙️', 'fire': '🔥', 'bolt': '⚡', 'eye': '👁️',
        'signal': '📡', 'magnet': '🧲', 'atom': '⚛️',
    };
    return icons[iconName] || '🔬';
};
</script>

<template>

    <Head :title="`${category.name} - Virtual Lab`" />

    <StudentLayout>
        <div class="category-page">
            <!-- Header -->
            <header class="category-header" :style="{ '--cat-color': category.color }">
                <Link :href="route('student.lab.index')" class="back-link">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z"
                        clip-rule="evenodd" />
                </svg>
                Orqaga
                </Link>

                <div class="header-content">
                    <div class="category-icon-lg" :style="{ background: category.gradient }">
                        {{ getCategoryIcon(category.icon) }}
                    </div>
                    <div class="header-text">
                        <h1 class="category-title">{{ category.name }}</h1>
                        <p class="category-description">{{ category.description }}</p>
                    </div>
                </div>

                <div class="header-stats">
                    <div class="stat-card">
                        <span class="stat-value">{{ category.total_experiments || experiments.length }}</span>
                        <span class="stat-label">Tajribalar</span>
                    </div>
                    <div v-if="category.avg_rating" class="stat-card">
                        <span class="stat-value">⭐ {{ category.avg_rating.toFixed(1) }}</span>
                        <span class="stat-label">Reyting</span>
                    </div>
                    <div v-if="category.progress" class="stat-card">
                        <span class="stat-value">{{ Math.round(category.progress.completion_percent || 0) }}%</span>
                        <span class="stat-label">Tugallandi</span>
                    </div>
                </div>
            </header>

            <!-- Experiments List -->
            <section class="experiments-section">
                <div class="section-header">
                    <h2 class="section-title">Tajribalar ro'yxati</h2>
                    <div class="filters">
                        <button class="filter-btn active">Barchasi</button>
                        <button class="filter-btn">Bepul</button>
                        <button class="filter-btn">Oson</button>
                    </div>
                </div>

                <div class="experiments-list">
                    <Link v-for="(exp, index) in experiments" :key="exp.id" :href="route('student.lab.show', exp.slug)"
                        class="experiment-row" :class="{ 'completed': exp.user_progress?.status === 'completed' }">
                    <div class="exp-number">{{ index + 1 }}</div>

                    <div class="exp-main">
                        <div class="exp-title-row">
                            <h3 class="exp-title">{{ exp.title }}</h3>
                            <span v-if="exp.is_free" class="free-tag">BEPUL</span>
                            <span v-else class="premium-tag">PREMIUM</span>
                        </div>
                        <p class="exp-description">{{ exp.short_description }}</p>
                    </div>

                    <div class="exp-meta">
                        <div class="meta-row">
                            <span class="meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon-sm" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                        clip-rule="evenodd" />
                                </svg>
                                {{ exp.estimated_duration }} daqiqa
                            </span>
                            <span class="meta-item difficulty" :style="{ color: exp.difficulty_color }">
                                {{ exp.difficulty_label }}
                            </span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-item grade">{{ exp.grade_level }}-sinf</span>
                            <span class="meta-item xp">+{{ exp.xp_reward }} XP</span>
                        </div>
                    </div>

                    <div class="exp-status">
                        <template v-if="exp.user_progress?.status === 'completed'">
                            <div class="status-completed">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                {{ exp.user_progress.best_percentage }}%
                            </div>
                        </template>
                        <template v-else-if="exp.user_progress?.status === 'in_progress'">
                            <div class="status-progress">
                                <div class="mini-progress">
                                    <div class="bar" :style="{ width: exp.user_progress.completion_percent + '%' }">
                                    </div>
                                </div>
                                {{ Math.round(exp.user_progress.completion_percent) }}%
                            </div>
                        </template>
                        <template v-else>
                            <div class="status-new">
                                Boshlash
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon-sm" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        </template>
                    </div>
                    </Link>
                </div>
            </section>
        </div>
    </StudentLayout>
</template>

<style scoped>
.category-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1.5rem 3rem;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--gray-500);
    text-decoration: none;
    font-size: 0.9rem;
    margin-bottom: 1.5rem;
}

.back-link:hover {
    color: var(--gray-700);
}

.dark .back-link:hover {
    color: var(--gray-300);
}

.category-header {
    background: linear-gradient(135deg, var(--cat-color, #3b82f6) 0%, color-mix(in srgb, var(--cat-color, #3b82f6) 80%, black) 100%);
    border-radius: 20px;
    padding: 2rem;
    color: white;
    margin-bottom: 2rem;
}

.header-content {
    display: flex;
    gap: 1.5rem;
    align-items: center;
    margin-bottom: 1.5rem;
}

.category-icon-lg {
    width: 80px;
    height: 80px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    background: rgba(255, 255, 255, 0.2);
}

.category-title {
    font-size: 2rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
}

.category-description {
    font-size: 1rem;
    opacity: 0.9;
    max-width: 600px;
}

.header-stats {
    display: flex;
    gap: 1rem;
}

.stat-card {
    background: rgba(255, 255, 255, 0.15);
    padding: 0.75rem 1.25rem;
    border-radius: 12px;
    text-align: center;
}

.stat-value {
    display: block;
    font-size: 1.25rem;
    font-weight: 700;
}

.stat-label {
    font-size: 0.8rem;
    opacity: 0.8;
}

/* Experiments Section */
.experiments-section {
    background: var(--white);
    border-radius: 16px;
    border: 1px solid var(--gray-200);
    overflow: hidden;
}

.dark .experiments-section {
    background: var(--gray-800);
    border-color: var(--gray-700);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--gray-200);
}

.dark .section-header {
    border-color: var(--gray-700);
}

.section-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--gray-900);
}

.dark .section-title {
    color: var(--gray-100);
}

.filters {
    display: flex;
    gap: 0.5rem;
}

.filter-btn {
    padding: 0.5rem 1rem;
    border: 1px solid var(--gray-300);
    background: transparent;
    border-radius: 20px;
    font-size: 0.85rem;
    color: var(--gray-600);
    cursor: pointer;
    transition: all 0.2s;
}

.filter-btn.active {
    background: var(--primary);
    border-color: var(--primary);
    color: white;
}

.experiments-list {
    display: flex;
    flex-direction: column;
}

.experiment-row {
    display: grid;
    grid-template-columns: 48px 1fr auto auto;
    gap: 1rem;
    align-items: center;
    padding: 1.25rem 1.5rem;
    text-decoration: none;
    border-bottom: 1px solid var(--gray-100);
    transition: all 0.2s;
}

.dark .experiment-row {
    border-color: var(--gray-700);
}

.experiment-row:hover {
    background: var(--gray-50);
}

.dark .experiment-row:hover {
    background: var(--gray-750);
}

.experiment-row:last-child {
    border-bottom: none;
}

.experiment-row.completed {
    background: rgba(16, 185, 129, 0.05);
}

.exp-number {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: var(--gray-100);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: var(--gray-500);
}

.dark .exp-number {
    background: var(--gray-700);
    color: var(--gray-400);
}

.exp-main {
    min-width: 0;
}

.exp-title-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.25rem;
}

.exp-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--gray-900);
}

.dark .exp-title {
    color: var(--gray-100);
}

.free-tag,
.premium-tag {
    padding: 0.125rem 0.5rem;
    border-radius: 12px;
    font-size: 0.65rem;
    font-weight: 700;
}

.free-tag {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
}

.premium-tag {
    background: rgba(245, 158, 11, 0.1);
    color: #f59e0b;
}

.exp-description {
    font-size: 0.85rem;
    color: var(--gray-500);
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.exp-meta {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    min-width: 140px;
}

.meta-row {
    display: flex;
    gap: 0.75rem;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.8rem;
    color: var(--gray-500);
}

.meta-item.xp {
    color: #10b981;
    font-weight: 600;
}

.meta-item.difficulty {
    font-weight: 600;
}

.exp-status {
    min-width: 100px;
    text-align: right;
}

.status-completed {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    color: #10b981;
    font-weight: 600;
    font-size: 0.9rem;
}

.status-progress {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.25rem;
    font-size: 0.85rem;
    color: var(--primary);
}

.mini-progress {
    width: 60px;
    height: 4px;
    background: var(--gray-200);
    border-radius: 2px;
    overflow: hidden;
}

.dark .mini-progress {
    background: var(--gray-700);
}

.mini-progress .bar {
    height: 100%;
    background: var(--primary);
    border-radius: 2px;
}

.status-new {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    color: var(--primary);
    font-weight: 500;
    font-size: 0.9rem;
}

.icon {
    width: 20px;
    height: 20px;
}

.icon-sm {
    width: 16px;
    height: 16px;
}

@media (max-width: 768px) {
    .experiment-row {
        grid-template-columns: 40px 1fr;
        gap: 0.75rem;
    }

    .exp-meta,
    .exp-status {
        grid-column: 2;
    }
}
</style>
