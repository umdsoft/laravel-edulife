<script setup>
import { Head, Link } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';

const props = defineProps({
    experiment: Object,
    isAccessible: Boolean,
    userProgress: Object,
    prerequisites: Array,
    ratings: Array,
    ratingsSummary: Object,
});
</script>

<template>

    <Head :title="`${experiment.title} - Virtual Lab`" />

    <StudentLayout>
        <div class="experiment-page">
            <!-- Breadcrumb -->
            <nav class="breadcrumb">
                <Link :href="route('student.lab.index')">Lab</Link>
                <span class="sep">/</span>
                <Link :href="route('student.lab.category', experiment.category.slug)">{{ experiment.category.name }}
                </Link>
                <span class="sep">/</span>
                <span class="current">{{ experiment.title }}</span>
            </nav>

            <div class="page-grid">
                <!-- Main Content -->
                <main class="main-content">
                    <!-- Header Card -->
                    <div class="header-card">
                        <div class="header-top">
                            <span class="category-badge" :style="{ background: experiment.category.color }">
                                {{ experiment.category.name }}
                            </span>
                            <span v-if="experiment.is_free" class="free-badge">BEPUL</span>
                            <span v-else class="premium-badge">PREMIUM</span>
                        </div>

                        <h1 class="experiment-title">{{ experiment.title }}</h1>
                        <p class="experiment-description">{{ experiment.description }}</p>

                        <div class="meta-grid">
                            <div class="meta-item">
                                <span class="meta-icon">⏱️</span>
                                <div>
                                    <span class="meta-value">{{ experiment.duration_text }}</span>
                                    <span class="meta-label">Davomiyligi</span>
                                </div>
                            </div>
                            <div class="meta-item">
                                <span class="meta-icon" :style="{ color: experiment.difficulty_color }">📊</span>
                                <div>
                                    <span class="meta-value" :style="{ color: experiment.difficulty_color }">{{
                                        experiment.difficulty_label }}</span>
                                    <span class="meta-label">Qiyinlik</span>
                                </div>
                            </div>
                            <div class="meta-item">
                                <span class="meta-icon">📚</span>
                                <div>
                                    <span class="meta-value">{{ experiment.grade_level }}-sinf</span>
                                    <span class="meta-label">Sinf</span>
                                </div>
                            </div>
                            <div class="meta-item">
                                <span class="meta-icon">⭐</span>
                                <div>
                                    <span class="meta-value">{{ experiment.avg_rating?.toFixed(1) || '-' }}</span>
                                    <span class="meta-label">{{ experiment.total_ratings || 0 }} baho</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Objectives -->
                    <section class="content-section">
                        <h2 class="section-title">📎 Maqsadlar</h2>
                        <ul class="objectives-list">
                            <li v-for="(obj, i) in experiment.objectives" :key="i">
                                {{ obj }}
                            </li>
                        </ul>
                    </section>

                    <!-- Theory -->
                    <section class="content-section">
                        <h2 class="section-title">📖 Nazariy qism</h2>
                        <div class="theory-content" v-html="experiment.theory?.replace(/\n/g, '<br>')"></div>
                    </section>

                    <!-- Formulas -->
                    <section v-if="experiment.formulas?.length" class="content-section">
                        <h2 class="section-title">📐 Formulalar</h2>
                        <div class="formulas-grid">
                            <div v-for="formula in experiment.formulas" :key="formula.id" class="formula-card">
                                <div class="formula-latex">{{ formula.latex }}</div>
                                <div class="formula-name">{{ formula.name }}</div>
                                <p class="formula-desc">{{ formula.description }}</p>
                            </div>
                        </div>
                    </section>

                    <!-- Equipment -->
                    <section v-if="experiment.required_equipment?.length" class="content-section">
                        <h2 class="section-title">🧪 Kerakli jihozlar</h2>
                        <div class="equipment-list">
                            <div v-for="eq in experiment.required_equipment" :key="eq.id" class="equipment-item">
                                <span class="eq-icon">🔧</span>
                                <span class="eq-name">{{ eq.name }}</span>
                                <span v-if="eq.count > 1" class="eq-count">x{{ eq.count }}</span>
                            </div>
                        </div>
                    </section>

                    <!-- Ratings -->
                    <section v-if="ratings?.length" class="content-section">
                        <h2 class="section-title">💬 Baholar</h2>
                        <div class="ratings-summary">
                            <div class="avg-rating">
                                <span class="big-rating">{{ ratingsSummary.avg?.toFixed(1) || '0' }}</span>
                                <div class="stars">★★★★★</div>
                                <span class="total-count">{{ ratingsSummary.total }} ta baho</span>
                            </div>
                        </div>

                        <div class="ratings-list">
                            <div v-for="rating in ratings.slice(0, 3)" :key="rating.id" class="rating-card">
                                <div class="rating-header">
                                    <span class="rating-stars">{{ rating.stars }}</span>
                                    <span class="rating-user">{{ rating.user.name }}</span>
                                    <span class="rating-date">{{ rating.created_at }}</span>
                                </div>
                                <p class="rating-text">{{ rating.review }}</p>
                            </div>
                        </div>
                    </section>
                </main>

                <!-- Sidebar -->
                <aside class="sidebar">
                    <!-- Start Card -->
                    <div class="start-card">
                        <div class="rewards">
                            <div class="reward">
                                <span class="reward-icon">⚡</span>
                                <span class="reward-value">+{{ experiment.xp_reward }}</span>
                                <span class="reward-label">XP</span>
                            </div>
                            <div class="reward">
                                <span class="reward-icon">🪙</span>
                                <span class="reward-value">+{{ experiment.coin_reward }}</span>
                                <span class="reward-label">Coin</span>
                            </div>
                        </div>

                        <div class="points-info">
                            <span>Umumiy ball: <strong>{{ experiment.total_points }}</strong></span>
                            <span>Vazifalar: <strong>{{ experiment.tasks_count }}</strong></span>
                        </div>

                        <template v-if="isAccessible">
                            <Link :href="route('student.lab.simulate', experiment.slug)" class="start-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"
                                    clip-rule="evenodd" />
                            </svg>
                            Tajribani boshlash
                            </Link>
                        </template>
                        <template v-else>
                            <div class="locked-message">
                                <span>🔒</span>
                                <p>Bu tajriba uchun Premium obuna kerak</p>
                            </div>
                            <a href="#" class="upgrade-btn">Premium sotib olish</a>
                        </template>

                        <!-- User Progress -->
                        <div v-if="userProgress" class="user-progress">
                            <div class="progress-header">
                                <span>Sizning natijangiz</span>
                                <span class="best-score">{{ userProgress.best_percentage }}%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="bar" :style="{ width: userProgress.best_percentage + '%' }"></div>
                            </div>
                            <div class="progress-stats">
                                <span>{{ userProgress.attempts_count }} urinish</span>
                                <span v-if="userProgress.last_attempt_at">Oxirgi: {{ userProgress.last_attempt_at
                                    }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Badge Card -->
                    <div v-if="experiment.badge_on_complete" class="badge-card">
                        <div class="badge-icon" :style="{ background: experiment.badge_on_complete.color }">
                            {{ experiment.badge_on_complete.icon }}
                        </div>
                        <div class="badge-info">
                            <span class="badge-rarity">{{ experiment.badge_on_complete.rarity.toUpperCase() }}</span>
                            <span class="badge-name">{{ experiment.badge_on_complete.name }}</span>
                            <span class="badge-hint">Yakunlash uchun</span>
                        </div>
                    </div>

                    <!-- Prerequisites -->
                    <div v-if="prerequisites?.length" class="prereq-card">
                        <h3 class="prereq-title">Oldingi talablar</h3>
                        <div class="prereq-list">
                            <Link v-for="prereq in prerequisites" :key="prereq.id"
                                :href="route('student.lab.show', prereq.slug)" class="prereq-item"
                                :class="{ completed: prereq.completed }">
                            <span class="prereq-check">{{ prereq.completed ? '✓' : '○' }}</span>
                            <span class="prereq-name">{{ prereq.title }}</span>
                            </Link>
                        </div>
                    </div>

                    <!-- Video -->
                    <div v-if="experiment.video_tutorial_url" class="video-card">
                        <h3 class="video-title">📹 Video darslik</h3>
                        <a :href="experiment.video_tutorial_url" target="_blank" class="video-link">
                            Video ko'rish →
                        </a>
                    </div>
                </aside>
            </div>
        </div>
    </StudentLayout>
</template>

<style scoped>
.experiment-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1.5rem 3rem;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
}

.breadcrumb a {
    color: var(--gray-500);
    text-decoration: none;
}

.breadcrumb a:hover {
    color: var(--primary);
}

.breadcrumb .sep {
    color: var(--gray-400);
}

.breadcrumb .current {
    color: var(--gray-700);
    font-weight: 500;
}

.dark .breadcrumb .current {
    color: var(--gray-300);
}

.page-grid {
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 2rem;
}

@media (max-width: 1024px) {
    .page-grid {
        grid-template-columns: 1fr;
    }
}

/* Main Content */
.main-content {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.header-card {
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: 16px;
    padding: 1.5rem;
}

.dark .header-card {
    background: var(--gray-800);
    border-color: var(--gray-700);
}

.header-top {
    display: flex;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.category-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    color: white;
}

.free-badge,
.premium-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 700;
}

.free-badge {
    background: #10b981;
    color: white;
}

.premium-badge {
    background: #f59e0b;
    color: white;
}

.experiment-title {
    font-size: 1.75rem;
    font-weight: 800;
    color: var(--gray-900);
    margin-bottom: 0.75rem;
}

.dark .experiment-title {
    color: var(--gray-100);
}

.experiment-description {
    color: var(--gray-600);
    line-height: 1.7;
    margin-bottom: 1.5rem;
}

.dark .experiment-description {
    color: var(--gray-400);
}

.meta-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
}

@media (max-width: 640px) {
    .meta-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.meta-icon {
    font-size: 1.5rem;
}

.meta-value {
    display: block;
    font-weight: 700;
    color: var(--gray-900);
}

.dark .meta-value {
    color: var(--gray-100);
}

.meta-label {
    font-size: 0.75rem;
    color: var(--gray-500);
}

/* Content Sections */
.content-section {
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: 16px;
    padding: 1.5rem;
}

.dark .content-section {
    background: var(--gray-800);
    border-color: var(--gray-700);
}

.section-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 1rem;
}

.dark .section-title {
    color: var(--gray-100);
}

.objectives-list {
    list-style: none;
    padding: 0;
}

.objectives-list li {
    position: relative;
    padding-left: 1.5rem;
    margin-bottom: 0.75rem;
    color: var(--gray-700);
}

.dark .objectives-list li {
    color: var(--gray-300);
}

.objectives-list li::before {
    content: '✓';
    position: absolute;
    left: 0;
    color: #10b981;
    font-weight: bold;
}

.theory-content {
    color: var(--gray-700);
    line-height: 1.8;
}

.dark .theory-content {
    color: var(--gray-300);
}

.formulas-grid {
    display: grid;
    gap: 1rem;
}

.formula-card {
    background: var(--gray-50);
    border-radius: 12px;
    padding: 1.25rem;
}

.dark .formula-card {
    background: var(--gray-750);
}

.formula-latex {
    font-family: 'Times New Roman', serif;
    font-size: 1.25rem;
    color: var(--gray-900);
    margin-bottom: 0.5rem;
}

.dark .formula-latex {
    color: var(--gray-100);
}

.formula-name {
    font-weight: 600;
    color: var(--primary);
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.formula-desc {
    font-size: 0.85rem;
    color: var(--gray-500);
}

.equipment-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.equipment-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: var(--gray-100);
    border-radius: 10px;
}

.dark .equipment-item {
    background: var(--gray-700);
}

.eq-name {
    color: var(--gray-700);
}

.dark .eq-name {
    color: var(--gray-300);
}

.eq-count {
    font-size: 0.8rem;
    color: var(--gray-500);
}

/* Sidebar */
.sidebar {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.start-card {
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: 16px;
    padding: 1.5rem;
    position: sticky;
    top: 1rem;
}

.dark .start-card {
    background: var(--gray-800);
    border-color: var(--gray-700);
}

.rewards {
    display: flex;
    justify-content: center;
    gap: 2rem;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--gray-200);
}

.dark .rewards {
    border-color: var(--gray-700);
}

.reward {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.reward-icon {
    font-size: 1.5rem;
}

.reward-value {
    font-weight: 800;
    font-size: 1.25rem;
    color: var(--gray-900);
}

.dark .reward-value {
    color: var(--gray-100);
}

.reward-label {
    font-size: 0.75rem;
    color: var(--gray-500);
}

.points-info {
    display: flex;
    justify-content: space-between;
    margin-bottom: 1rem;
    font-size: 0.9rem;
    color: var(--gray-600);
}

.dark .points-info {
    color: var(--gray-400);
}

.start-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    width: 100%;
    padding: 1rem;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
    font-weight: 700;
    font-size: 1rem;
    border-radius: 12px;
    text-decoration: none;
    transition: all 0.3s;
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
}

.start-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
}

.locked-message {
    text-align: center;
    padding: 1rem;
    color: var(--gray-500);
}

.upgrade-btn {
    display: block;
    text-align: center;
    padding: 1rem;
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
    font-weight: 600;
    border-radius: 12px;
    text-decoration: none;
}

.user-progress {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--gray-200);
}

.dark .user-progress {
    border-color: var(--gray-700);
}

.progress-header {
    display: flex;
    justify-content: space-between;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.best-score {
    font-weight: 700;
    color: #10b981;
}

.progress-bar {
    height: 8px;
    background: var(--gray-200);
    border-radius: 4px;
    overflow: hidden;
}

.dark .progress-bar {
    background: var(--gray-700);
}

.progress-bar .bar {
    height: 100%;
    background: linear-gradient(90deg, #10b981, #34d399);
    border-radius: 4px;
}

.progress-stats {
    display: flex;
    justify-content: space-between;
    margin-top: 0.5rem;
    font-size: 0.8rem;
    color: var(--gray-500);
}

.badge-card,
.prereq-card,
.video-card {
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: 16px;
    padding: 1.25rem;
}

.dark .badge-card,
.dark .prereq-card,
.dark .video-card {
    background: var(--gray-800);
    border-color: var(--gray-700);
}

.badge-card {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.badge-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.badge-rarity {
    font-size: 0.65rem;
    font-weight: 700;
    color: #f59e0b;
}

.badge-name {
    display: block;
    font-weight: 600;
    color: var(--gray-900);
}

.dark .badge-name {
    color: var(--gray-100);
}

.badge-hint {
    font-size: 0.8rem;
    color: var(--gray-500);
}

.prereq-title,
.video-title {
    font-size: 0.95rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
}

.prereq-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.prereq-item {
    display: flex;
    gap: 0.5rem;
    padding: 0.5rem;
    border-radius: 8px;
    text-decoration: none;
    color: var(--gray-600);
}

.prereq-item:hover {
    background: var(--gray-50);
}

.dark .prereq-item:hover {
    background: var(--gray-750);
}

.prereq-item.completed {
    color: #10b981;
}

.video-link {
    color: var(--primary);
    text-decoration: none;
    font-weight: 500;
}

.icon {
    width: 20px;
    height: 20px;
}
</style>
