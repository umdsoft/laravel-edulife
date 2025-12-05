<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { computed, ref } from 'vue';

const props = defineProps({
    categories: Array,
    featuredExperiments: Array,
    freeExperiments: Array,
    userProgress: Object,
    recentAttempts: Array,
    leaderboard: Array,
});

const page = usePage();
const user = computed(() => page.props.auth?.user);

// Difficulty colors
const difficultyColors = {
    easy: '#10B981',
    medium: '#F59E0B',
    hard: '#EF4444',
};
</script>

<template>

    <Head title="Virtual Fizika Laboratoriyasi" />

    <StudentLayout>
        <div class="lab-container">
            <!-- Header Hero Section -->
            <section class="lab-hero">
                <div class="hero-content">
                    <div class="hero-text">
                        <h1 class="hero-title">
                            Virtual Fizika
                            <span class="gradient-text">Laboratoriyasi</span>
                        </h1>
                        <p class="hero-description">
                            Interaktiv simulyatsiyalar orqali fizika qonunlarini
                            amaliy o'rganing. Tajribalar o'tkazing, o'lchov oling,
                            formulalarni sinab ko'ring!
                        </p>
                        <div class="hero-stats">
                            <div class="stat-item">
                                <span class="stat-value">{{ categories?.length || 0 }}</span>
                                <span class="stat-label">Bo'lim</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value">{{ freeExperiments?.length || 0 }}+</span>
                                <span class="stat-label">Tajriba</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value">100%</span>
                                <span class="stat-label">Bepul</span>
                            </div>
                        </div>
                        <div class="hero-actions">
                            <Link :href="route('student.lab.category', 'mechanics')" class="btn-primary-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"
                                    clip-rule="evenodd" />
                            </svg>
                            Tajribalarni boshlash
                            </Link>
                            <a href="#categories" class="btn-secondary-lg">
                                Bo'limlarni ko'rish
                            </a>
                        </div>
                    </div>

                    <!-- User Progress Card (if logged in) -->
                    <div v-if="userProgress" class="user-progress-card">
                        <div class="progress-header">
                            <div class="level-badge">
                                <span class="level-number">{{ userProgress.level }}</span>
                                <span class="level-title">{{ userProgress.level_title }}</span>
                            </div>
                            <div class="xp-display">
                                <span class="xp-value">{{ userProgress.total_xp }}</span>
                                <span class="xp-label">XP</span>
                            </div>
                        </div>

                        <div class="level-progress-bar">
                            <div class="progress-fill" :style="{ width: userProgress.level_progress + '%' }"></div>
                            <span class="progress-text">{{ Math.round(userProgress.level_progress) }}%</span>
                        </div>

                        <div class="progress-stats">
                            <div class="progress-stat">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                                    <path fill-rule="evenodd"
                                        d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm9.707 5.707a1 1 0 00-1.414-1.414L9 12.586l-1.293-1.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>{{ userProgress.completed_experiments }} tajriba</span>
                            </div>
                            <div class="progress-stat">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>{{ userProgress.current_streak }} kun streak</span>
                            </div>
                            <div class="progress-stat">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path
                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                                <span>{{ userProgress.badges_count }} badge</span>
                            </div>
                        </div>

                        <Link :href="route('student.lab.progress')" class="view-progress-btn">
                        Batafsil ko'rish
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon-sm" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                clip-rule="evenodd" />
                        </svg>
                        </Link>
                    </div>

                    <!-- Demo Animation (for non-logged users) -->
                    <div v-else class="demo-animation">
                        <div class="pendulum-demo">
                            <div class="pendulum-pivot"></div>
                            <div class="pendulum-line"></div>
                            <div class="pendulum-bob"></div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Categories Section -->
            <section id="categories" class="categories-section">
                <div class="section-header">
                    <h2 class="section-title">Fizika Bo'limlari</h2>
                    <p class="section-subtitle">O'zingizga mos bo'limni tanlang</p>
                </div>

                <div class="categories-grid">
                    <Link v-for="cat in categories" :key="cat.id" :href="route('student.lab.category', cat.slug)"
                        class="category-card" :style="{ '--category-color': cat.color }">
                    <div class="category-icon" :style="{ background: cat.gradient }">
                        <span class="icon-emoji">{{ getCategoryIcon(cat.icon) }}</span>
                    </div>
                    <h3 class="category-name">{{ cat.name }}</h3>
                    <p class="category-description">{{ cat.description }}</p>
                    <div class="category-meta">
                        <span class="experiment-count">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon-xs" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M10 2a1 1 0 011 1v1.323l3.954 1.582 1.599-.8a1 1 0 01.894 1.79l-1.233.616 1.738 5.42a1 1 0 01-.285 1.05A3.989 3.989 0 0115 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.715-5.349L11 6.477V16h2a1 1 0 110 2H7a1 1 0 110-2h2V6.477L6.237 7.582l1.715 5.349a1 1 0 01-.285 1.05A3.989 3.989 0 015 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.738-5.42-1.233-.617a1 1 0 01.894-1.788l1.599.799L9 4.323V3a1 1 0 011-1z"
                                    clip-rule="evenodd" />
                            </svg>
                            {{ cat.experiments_count }} tajriba
                        </span>
                        <span v-if="cat.progress" class="category-progress">
                            {{ Math.round(cat.progress.completion_percent || 0) }}%
                        </span>
                    </div>
                    <div v-if="cat.progress" class="category-progress-bar">
                        <div class="bar-fill" :style="{ width: (cat.progress.completion_percent || 0) + '%' }"></div>
                    </div>
                    </Link>
                </div>
            </section>

            <!-- Featured Experiments -->
            <section v-if="featuredExperiments?.length" class="experiments-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon-title" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path
                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        Tavsiya etilgan tajribalar
                    </h2>
                </div>

                <div class="experiments-grid">
                    <Link v-for="exp in featuredExperiments" :key="exp.id" :href="route('student.lab.show', exp.slug)"
                        class="experiment-card featured">
                    <div class="exp-thumbnail" :style="{ background: exp.category.color + '20' }">
                        <div class="exp-icon" :style="{ background: exp.category.color }">
                            🔬
                        </div>
                        <span v-if="exp.is_free" class="free-badge">BEPUL</span>
                        <span v-else class="premium-badge">PREMIUM</span>
                    </div>

                    <div class="exp-content">
                        <div class="exp-category" :style="{ color: exp.category.color }">
                            {{ exp.category.name }}
                        </div>
                        <h3 class="exp-title">{{ exp.title }}</h3>
                        <p class="exp-description">{{ exp.short_description }}</p>

                        <div class="exp-meta">
                            <span class="meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon-xs" viewBox="0 0 20 20"
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
                            <span class="meta-item xp">
                                +{{ exp.xp_reward }} XP
                            </span>
                        </div>

                        <!-- User progress -->
                        <div v-if="exp.user_progress" class="exp-progress">
                            <div class="progress-bar-mini">
                                <div class="bar" :style="{ width: exp.user_progress.completion_percent + '%' }"></div>
                            </div>
                            <span class="progress-text-mini">{{ Math.round(exp.user_progress.completion_percent)
                                }}%</span>
                        </div>
                    </div>
                    </Link>
                </div>
            </section>

            <!-- Free Experiments -->
            <section v-if="freeExperiments?.length" class="experiments-section free-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="free-icon">🆓</span>
                        Bepul Tajribalar
                    </h2>
                    <p class="section-subtitle">Ro'yxatdan o'tmasdan ham sinab ko'ring!</p>
                </div>

                <div class="experiments-grid">
                    <Link v-for="exp in freeExperiments" :key="exp.id" :href="route('student.lab.show', exp.slug)"
                        class="experiment-card">
                    <div class="exp-thumbnail" :style="{ background: exp.category.color + '15' }">
                        <div class="exp-icon" :style="{ background: exp.category.color }">
                            ⚗️
                        </div>
                        <span class="free-badge">BEPUL</span>
                    </div>

                    <div class="exp-content">
                        <div class="exp-category" :style="{ color: exp.category.color }">
                            {{ exp.category.name }}
                        </div>
                        <h3 class="exp-title">{{ exp.title }}</h3>

                        <div class="exp-meta">
                            <span class="meta-item">{{ exp.estimated_duration }} daq</span>
                            <span class="meta-item difficulty" :style="{ color: exp.difficulty_color }">
                                {{ exp.difficulty_label }}
                            </span>
                            <span class="meta-item grade">{{ exp.grade_level }}-sinf</span>
                        </div>
                    </div>
                    </Link>
                </div>
            </section>

            <!-- Recent Attempts (for logged in users) -->
            <section v-if="recentAttempts?.length" class="recent-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon-title" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                clip-rule="evenodd" />
                        </svg>
                        So'nggi urinishlar
                    </h2>
                    <Link :href="route('student.lab.progress')" class="view-all-link">
                    Barchasini ko'rish →
                    </Link>
                </div>

                <div class="recent-grid">
                    <div v-for="attempt in recentAttempts" :key="attempt.id" class="recent-card">
                        <div class="recent-info">
                            <h4 class="recent-title">{{ attempt.experiment.title }}</h4>
                            <span class="recent-category">{{ attempt.experiment.category }}</span>
                        </div>
                        <div class="recent-stats">
                            <div class="stat">
                                <span class="value">{{ attempt.percentage }}%</span>
                                <span class="label">Ball</span>
                            </div>
                            <div class="stat">
                                <span class="value">{{ attempt.time_spent }}</span>
                                <span class="label">Vaqt</span>
                            </div>
                        </div>
                        <Link v-if="attempt.can_continue" :href="route('student.lab.simulate', attempt.experiment.slug)"
                            class="continue-btn">
                        Davom etish
                        </Link>
                        <span v-else class="status-badge" :class="attempt.status">
                            {{ attempt.status === 'completed' ? '✓ Yakunlandi' : attempt.status }}
                        </span>
                    </div>
                </div>
            </section>

            <!-- Leaderboard Preview -->
            <section v-if="leaderboard?.length" class="leaderboard-preview">
                <div class="section-header">
                    <h2 class="section-title">
                        🏆 Haftalik Reyting
                    </h2>
                    <Link :href="route('student.lab.leaderboard')" class="view-all-link">
                    To'liq reyting →
                    </Link>
                </div>

                <div class="leaderboard-list">
                    <div v-for="entry in leaderboard.slice(0, 5)" :key="entry.user.id" class="leader-item"
                        :class="{ 'top-3': entry.rank <= 3 }">
                        <span class="leader-rank">
                            {{ entry.rank_badge || entry.rank }}
                        </span>
                        <div class="leader-info">
                            <span class="leader-name">{{ entry.user.name }}</span>
                            <span class="leader-level">Daraja {{ entry.user.level }}</span>
                        </div>
                        <div class="leader-xp">
                            {{ entry.stats.total_xp }} XP
                        </div>
                    </div>
                </div>
            </section>

            <!-- Quick Links -->
            <section class="quick-links">
                <Link :href="route('student.lab.badges')" class="quick-card badges">
                <span class="quick-icon">🏅</span>
                <span class="quick-title">Badgelar</span>
                <span class="quick-desc">Yutuqlarni ko'ring</span>
                </Link>
                <Link :href="route('student.lab.leaderboard')" class="quick-card leaderboard">
                <span class="quick-icon">📊</span>
                <span class="quick-title">Reyting</span>
                <span class="quick-desc">O'rningizni bilib oling</span>
                </Link>
                <Link :href="route('student.lab.progress')" class="quick-card progress">
                <span class="quick-icon">📈</span>
                <span class="quick-title">Statistika</span>
                <span class="quick-desc">Rivojlanishingiz</span>
                </Link>
            </section>
        </div>
    </StudentLayout>
</template>

<script>
export default {
    methods: {
        getCategoryIcon(iconName) {
            const icons = {
                'cog': '⚙️',
                'fire': '🔥',
                'bolt': '⚡',
                'eye': '👁️',
                'signal': '📡',
                'magnet': '🧲',
                'atom': '⚛️',
            };
            return icons[iconName] || '🔬';
        }
    }
};
</script>

<style scoped>
.lab-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 1.5rem 3rem;
}

/* Hero Section */
.lab-hero {
    padding: 2rem 0 3rem;
}

.hero-content {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 3rem;
    align-items: center;
}

@media (max-width: 1024px) {
    .hero-content {
        grid-template-columns: 1fr;
    }
}

.hero-title {
    font-size: 2.75rem;
    font-weight: 800;
    line-height: 1.2;
    color: var(--gray-900);
    margin-bottom: 1rem;
}

.dark .hero-title {
    color: var(--gray-100);
}

.gradient-text {
    background: linear-gradient(135deg, #3b82f6, #8b5cf6, #ec4899);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.hero-description {
    font-size: 1.125rem;
    color: var(--gray-600);
    line-height: 1.7;
    margin-bottom: 1.5rem;
}

.dark .hero-description {
    color: var(--gray-400);
}

.hero-stats {
    display: flex;
    gap: 2rem;
    margin-bottom: 2rem;
}

.stat-item {
    display: flex;
    flex-direction: column;
}

.stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--primary);
}

.stat-label {
    font-size: 0.875rem;
    color: var(--gray-500);
}

.hero-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.btn-primary-lg {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1.75rem;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
    font-weight: 600;
    border-radius: 12px;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
}

.btn-primary-lg:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
}

.btn-secondary-lg {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1.75rem;
    background: var(--gray-100);
    color: var(--gray-700);
    font-weight: 600;
    border-radius: 12px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.dark .btn-secondary-lg {
    background: var(--gray-800);
    color: var(--gray-300);
}

.btn-secondary-lg:hover {
    background: var(--gray-200);
}

.dark .btn-secondary-lg:hover {
    background: var(--gray-700);
}

/* User Progress Card */
.user-progress-card {
    background: linear-gradient(135deg, #1e293b, #334155);
    border-radius: 20px;
    padding: 1.5rem;
    color: white;
}

.progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.level-badge {
    display: flex;
    flex-direction: column;
}

.level-number {
    font-size: 2.5rem;
    font-weight: 800;
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.level-title {
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.7);
}

.xp-display {
    text-align: right;
}

.xp-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #10b981;
}

.xp-label {
    display: block;
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.6);
}

.level-progress-bar {
    position: relative;
    height: 8px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 4px;
    margin-bottom: 1rem;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6, #8b5cf6);
    border-radius: 4px;
    transition: width 0.5s ease;
}

.progress-text {
    position: absolute;
    right: 0;
    top: -18px;
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.6);
}

.progress-stats {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    margin-bottom: 1rem;
}

.progress-stat {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.8);
    background: rgba(255, 255, 255, 0.1);
    padding: 0.375rem 0.75rem;
    border-radius: 20px;
}

.progress-stat .icon {
    width: 14px;
    height: 14px;
}

.view-progress-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
    width: 100%;
    padding: 0.625rem;
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border-radius: 10px;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: background 0.2s;
}

.view-progress-btn:hover {
    background: rgba(255, 255, 255, 0.15);
}

/* Pendulum Demo Animation */
.demo-animation {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 300px;
}

.pendulum-demo {
    position: relative;
    width: 200px;
    height: 250px;
}

.pendulum-pivot {
    position: absolute;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 16px;
    height: 16px;
    background: #64748b;
    border-radius: 50%;
    z-index: 2;
}

.pendulum-line {
    position: absolute;
    top: 8px;
    left: 50%;
    width: 3px;
    height: 160px;
    background: linear-gradient(to bottom, #64748b, #94a3b8);
    transform-origin: top center;
    animation: swing 2s ease-in-out infinite;
}

.pendulum-bob {
    position: absolute;
    top: 160px;
    left: 50%;
    transform: translateX(-50%);
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    border-radius: 50%;
    box-shadow: 0 4px 20px rgba(239, 68, 68, 0.4);
    transform-origin: top center;
    animation: swing 2s ease-in-out infinite;
}

@keyframes swing {

    0%,
    100% {
        transform: translateX(-50%) rotate(-25deg);
    }

    50% {
        transform: translateX(-50%) rotate(25deg);
    }
}

/* Section Styles */
.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--gray-900);
}

.dark .section-title {
    color: var(--gray-100);
}

.section-subtitle {
    color: var(--gray-500);
    font-size: 0.95rem;
}

.icon-title {
    width: 24px;
    height: 24px;
    color: #f59e0b;
}

.view-all-link {
    color: var(--primary);
    font-weight: 500;
    text-decoration: none;
    font-size: 0.9rem;
}

.view-all-link:hover {
    text-decoration: underline;
}

/* Categories Grid */
.categories-section {
    padding: 2rem 0;
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.25rem;
}

.category-card {
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: 16px;
    padding: 1.5rem;
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.dark .category-card {
    background: var(--gray-800);
    border-color: var(--gray-700);
}

.category-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--category-color);
}

.category-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1);
    border-color: var(--category-color);
}

.dark .category-card:hover {
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.3);
}

.category-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
}

.icon-emoji {
    font-size: 1.75rem;
}

.category-name {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 0.5rem;
}

.dark .category-name {
    color: var(--gray-100);
}

.category-description {
    font-size: 0.875rem;
    color: var(--gray-600);
    line-height: 1.5;
    margin-bottom: 1rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.dark .category-description {
    color: var(--gray-400);
}

.category-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.8rem;
}

.experiment-count {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    color: var(--gray-500);
}

.category-progress {
    font-weight: 600;
    color: #10b981;
}

.category-progress-bar {
    height: 4px;
    background: var(--gray-200);
    border-radius: 2px;
    margin-top: 0.75rem;
    overflow: hidden;
}

.dark .category-progress-bar {
    background: var(--gray-700);
}

.bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #10b981, #34d399);
    border-radius: 2px;
    transition: width 0.5s ease;
}

/* Experiments Grid */
.experiments-section {
    padding: 2rem 0;
}

.experiments-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.25rem;
}

.experiment-card {
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: 16px;
    overflow: hidden;
    text-decoration: none;
    transition: all 0.3s ease;
}

.dark .experiment-card {
    background: var(--gray-800);
    border-color: var(--gray-700);
}

.experiment-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1);
}

.dark .experiment-card:hover {
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.3);
}

.experiment-card.featured {
    border-color: rgba(245, 158, 11, 0.3);
}

.exp-thumbnail {
    position: relative;
    height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.exp-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
}

.free-badge,
.premium-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 0.25rem 0.625rem;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.5px;
}

.free-badge {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.premium-badge {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
}

.exp-content {
    padding: 1.25rem;
}

.exp-category {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.375rem;
}

.exp-title {
    font-size: 1.0625rem;
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 0.5rem;
}

.dark .exp-title {
    color: var(--gray-100);
}

.exp-description {
    font-size: 0.875rem;
    color: var(--gray-600);
    line-height: 1.5;
    margin-bottom: 0.75rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.dark .exp-description {
    color: var(--gray-400);
}

.exp-meta {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
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

.icon-xs {
    width: 14px;
    height: 14px;
}

/* Recent Section */
.recent-section {
    padding: 2rem 0;
}

.recent-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
}

.recent-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: 12px;
    padding: 1rem;
}

.dark .recent-card {
    background: var(--gray-800);
    border-color: var(--gray-700);
}

.recent-info {
    flex: 1;
}

.recent-title {
    font-weight: 600;
    color: var(--gray-900);
    font-size: 0.95rem;
}

.dark .recent-title {
    color: var(--gray-100);
}

.recent-category {
    font-size: 0.8rem;
    color: var(--gray-500);
}

.recent-stats {
    display: flex;
    gap: 1rem;
    text-align: center;
}

.recent-stats .stat .value {
    display: block;
    font-weight: 700;
    color: var(--primary);
}

.recent-stats .stat .label {
    font-size: 0.7rem;
    color: var(--gray-500);
}

.continue-btn {
    padding: 0.5rem 1rem;
    background: var(--primary);
    color: white;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 600;
    text-decoration: none;
    white-space: nowrap;
}

.status-badge {
    padding: 0.375rem 0.75rem;
    background: var(--gray-100);
    color: var(--gray-600);
    border-radius: 20px;
    font-size: 0.75rem;
    white-space: nowrap;
}

.status-badge.completed {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
}

/* Leaderboard Preview */
.leaderboard-preview {
    padding: 2rem 0;
}

.leaderboard-list {
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: 16px;
    overflow: hidden;
}

.dark .leaderboard-list {
    background: var(--gray-800);
    border-color: var(--gray-700);
}

.leader-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--gray-100);
}

.dark .leader-item {
    border-color: var(--gray-700);
}

.leader-item:last-child {
    border-bottom: none;
}

.leader-item.top-3 {
    background: linear-gradient(to right, rgba(245, 158, 11, 0.05), transparent);
}

.leader-rank {
    width: 32px;
    text-align: center;
    font-weight: 700;
    font-size: 1.125rem;
    color: var(--gray-400);
}

.leader-item.top-3 .leader-rank {
    font-size: 1.25rem;
}

.leader-info {
    flex: 1;
}

.leader-name {
    display: block;
    font-weight: 600;
    color: var(--gray-900);
}

.dark .leader-name {
    color: var(--gray-100);
}

.leader-level {
    font-size: 0.8rem;
    color: var(--gray-500);
}

.leader-xp {
    font-weight: 700;
    color: #10b981;
}

/* Quick Links */
.quick-links {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    padding: 2rem 0;
}

@media (max-width: 768px) {
    .quick-links {
        grid-template-columns: 1fr;
    }
}

.quick-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 1.5rem;
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: 16px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.dark .quick-card {
    background: var(--gray-800);
    border-color: var(--gray-700);
}

.quick-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.quick-icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.quick-title {
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 0.25rem;
}

.dark .quick-title {
    color: var(--gray-100);
}

.quick-desc {
    font-size: 0.8rem;
    color: var(--gray-500);
}

/* Utility Classes */
.icon {
    width: 20px;
    height: 20px;
}

.icon-sm {
    width: 16px;
    height: 16px;
}
</style>
