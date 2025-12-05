<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    result: Object,
    experiment: Object,
});

defineEmits(['close']);

const gradeColor = computed(() => {
    const p = props.result?.percentage || 0;
    if (p >= 90) return '#10b981';
    if (p >= 70) return '#3b82f6';
    if (p >= 50) return '#f59e0b';
    return '#ef4444';
});

const gradeEmoji = computed(() => {
    const p = props.result?.percentage || 0;
    if (p >= 90) return '🏆';
    if (p >= 70) return '⭐';
    if (p >= 50) return '👍';
    return '💪';
});
</script>

<template>
    <div class="result-overlay">
        <div class="result-modal">
            <!-- Header -->
            <div class="result-header" :class="{ passed: result.passed, failed: !result.passed }">
                <span class="result-emoji">{{ gradeEmoji }}</span>
                <h2 v-if="result.passed">Tabriklaymiz!</h2>
                <h2 v-else>Yaxshi harakat!</h2>
                <p>{{ experiment.title }}</p>
            </div>

            <!-- Score Circle -->
            <div class="score-section">
                <div class="score-circle" :style="{ '--score-color': gradeColor }">
                    <svg viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="45" class="bg" />
                        <circle cx="50" cy="50" r="45" class="progress" :style="{
                            strokeDasharray: `${result.percentage * 2.83} 283`,
                        }" />
                    </svg>
                    <div class="score-value">
                        <span class="percentage">{{ result.percentage }}%</span>
                        <span class="grade">{{ result.grade }}</span>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card xp">
                    <span class="stat-icon">⚡</span>
                    <div class="stat-content">
                        <span class="stat-value">+{{ result.xp_earned }}</span>
                        <span class="stat-label">XP</span>
                    </div>
                </div>

                <div class="stat-card coins">
                    <span class="stat-icon">🪙</span>
                    <div class="stat-content">
                        <span class="stat-value">+{{ result.coins_earned }}</span>
                        <span class="stat-label">Coin</span>
                    </div>
                </div>

                <div class="stat-card time">
                    <span class="stat-icon">⏱️</span>
                    <div class="stat-content">
                        <span class="stat-value">{{ result.time_spent }}</span>
                        <span class="stat-label">Vaqt</span>
                    </div>
                </div>
            </div>

            <!-- Badges Earned -->
            <div v-if="result.badges_earned?.length" class="badges-section">
                <h3>🏅 Yangi Badgelar!</h3>
                <div class="badges-grid">
                    <div v-for="badge in result.badges_earned" :key="badge.id" class="badge-card">
                        <span class="badge-icon">{{ badge.icon }}</span>
                        <span class="badge-name">{{ badge.name }}</span>
                        <span class="badge-rarity">{{ badge.rarity }}</span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="result-actions">
                <Link :href="route('student.lab.show', experiment.slug)" class="btn btn-secondary">
                Tajriba sahifasiga
                </Link>
                <Link :href="route('student.lab.index')" class="btn btn-primary">
                Bosh sahifa
                </Link>
            </div>
        </div>
    </div>
</template>

<style scoped>
.result-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 200;
    padding: 1rem;
}

.result-modal {
    background: #1e293b;
    border-radius: 24px;
    width: 100%;
    max-width: 440px;
    overflow: hidden;
    animation: popIn 0.4s ease;
}

@keyframes popIn {
    from {
        transform: scale(0.8);
        opacity: 0;
    }

    to {
        transform: scale(1);
        opacity: 1;
    }
}

.result-header {
    padding: 2rem;
    text-align: center;
}

.result-header.passed {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(16, 185, 129, 0.05));
}

.result-header.failed {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), rgba(245, 158, 11, 0.05));
}

.result-emoji {
    font-size: 3rem;
    display: block;
    margin-bottom: 0.5rem;
}

.result-header h2 {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.result-header p {
    color: #94a3b8;
}

.score-section {
    display: flex;
    justify-content: center;
    padding: 1rem 2rem;
}

.score-circle {
    position: relative;
    width: 160px;
    height: 160px;
}

.score-circle svg {
    width: 100%;
    height: 100%;
    transform: rotate(-90deg);
}

.score-circle circle {
    fill: none;
    stroke-width: 8;
}

.score-circle .bg {
    stroke: #334155;
}

.score-circle .progress {
    stroke: var(--score-color);
    stroke-linecap: round;
    transition: stroke-dasharray 1s ease;
}

.score-value {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.percentage {
    font-size: 2.25rem;
    font-weight: 800;
    color: white;
}

.grade {
    font-size: 1rem;
    color: #94a3b8;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
    padding: 0 1.5rem 1.5rem;
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem;
    background: #0f172a;
    border-radius: 12px;
}

.stat-icon {
    font-size: 1.25rem;
}

.stat-value {
    display: block;
    font-weight: 700;
    color: white;
}

.stat-label {
    font-size: 0.7rem;
    color: #64748b;
}

.badges-section {
    padding: 1rem 1.5rem;
    border-top: 1px solid #334155;
}

.badges-section h3 {
    font-size: 0.95rem;
    margin-bottom: 0.75rem;
}

.badges-grid {
    display: flex;
    gap: 0.75rem;
}

.badge-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0.75rem;
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), rgba(245, 158, 11, 0.05));
    border-radius: 12px;
    flex: 1;
}

.badge-icon {
    font-size: 1.5rem;
}

.badge-name {
    font-size: 0.8rem;
    font-weight: 600;
    margin-top: 0.25rem;
}

.badge-rarity {
    font-size: 0.65rem;
    color: #f59e0b;
    text-transform: uppercase;
}

.result-actions {
    display: flex;
    gap: 0.75rem;
    padding: 1.5rem;
    border-top: 1px solid #334155;
}

.btn {
    flex: 1;
    padding: 0.875rem;
    border-radius: 12px;
    font-weight: 600;
    text-align: center;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-secondary {
    background: #334155;
    color: white;
}

.btn-primary {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
}

.btn:hover {
    transform: translateY(-2px);
}
</style>
