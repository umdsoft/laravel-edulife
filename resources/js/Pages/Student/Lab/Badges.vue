<script setup>
import { Head, Link } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';

const props = defineProps({
    badges: Array,
    badgesByRarity: Object,
});

const rarityLabels = {
    common: { name: 'Oddiy', color: '#94a3b8' },
    uncommon: { name: 'Kam uchraydigan', color: '#22c55e' },
    rare: { name: 'Kam', color: '#3b82f6' },
    epic: { name: 'Epik', color: '#a855f7' },
    legendary: { name: 'Afsonaviy', color: '#f59e0b' },
};

const rarityOrder = ['legendary', 'epic', 'rare', 'uncommon', 'common'];
</script>

<template>

    <Head title="Badgelar - Virtual Lab" />

    <StudentLayout>
        <div class="badges-page">
            <header class="page-header">
                <Link :href="route('student.lab.index')" class="back-link">
                ← Orqaga
                </Link>
                <h1>🏅 Lab Badgelari</h1>
                <p class="page-subtitle">
                    Tajribalar o'tkazing va maxsus badgelarni qo'lga kiriting!
                </p>
            </header>

            <!-- Badge Stats -->
            <div class="badges-stats">
                <div class="stat-card">
                    <span class="stat-value">{{badges.filter(b => b.earned).length}}</span>
                    <span class="stat-label">Qo'lga kiritilgan</span>
                </div>
                <div class="stat-card">
                    <span class="stat-value">{{ badges.length }}</span>
                    <span class="stat-label">Jami badgelar</span>
                </div>
                <div class="stat-card">
                    <span class="stat-value">{{Math.round(badges.filter(b => b.earned).length / badges.length * 100)
                        }}%</span>
                    <span class="stat-label">Progress</span>
                </div>
            </div>

            <!-- Badges by Rarity -->
            <div v-for="rarity in rarityOrder" :key="rarity" class="rarity-section">
                <template v-if="badgesByRarity[rarity]?.length">
                    <h2 class="rarity-title" :style="{ color: rarityLabels[rarity].color }">
                        {{ rarityLabels[rarity].name }} ({{ badgesByRarity[rarity].length }})
                    </h2>

                    <div class="badges-grid">
                        <div v-for="badge in badgesByRarity[rarity]" :key="badge.id" class="badge-card"
                            :class="{ earned: badge.earned, locked: !badge.earned }"
                            :style="{ '--badge-color': badge.color }">
                            <div class="badge-icon-wrapper" :style="{ background: badge.background_gradient }">
                                <span class="badge-icon">{{ badge.icon }}</span>
                            </div>

                            <div class="badge-content">
                                <h3 class="badge-name">{{ badge.name }}</h3>
                                <p class="badge-description">{{ badge.description }}</p>

                                <div class="badge-rewards">
                                    <span class="reward xp">+{{ badge.xp_reward }} XP</span>
                                    <span class="reward coin">+{{ badge.coin_reward }} 🪙</span>
                                </div>

                                <div class="badge-condition">
                                    {{ badge.condition_text }}
                                </div>
                            </div>

                            <div v-if="badge.earned" class="earned-badge">✓</div>
                            <div v-else class="locked-overlay">🔒</div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </StudentLayout>
</template>

<style scoped>
.badges-page {
    max-width: 1100px;
    margin: 0 auto;
    padding: 0 1.5rem 3rem;
}

.page-header {
    padding: 1.5rem 0 2rem;
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
    margin-bottom: 0.5rem;
}

.page-subtitle {
    color: var(--gray-500);
}

.badges-stats {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    flex: 1;
    padding: 1.25rem;
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: 14px;
    text-align: center;
}

.dark .stat-card {
    background: var(--gray-800);
    border-color: var(--gray-700);
}

.stat-value {
    display: block;
    font-size: 1.75rem;
    font-weight: 800;
    color: var(--primary);
}

.stat-label {
    font-size: 0.85rem;
    color: var(--gray-500);
}

.rarity-section {
    margin-bottom: 2.5rem;
}

.rarity-title {
    font-size: 1.125rem;
    font-weight: 700;
    margin-bottom: 1rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.badges-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
}

.badge-card {
    position: relative;
    display: flex;
    gap: 1rem;
    padding: 1.25rem;
    background: var(--white);
    border: 2px solid var(--gray-200);
    border-radius: 16px;
    transition: all 0.3s;
}

.dark .badge-card {
    background: var(--gray-800);
    border-color: var(--gray-700);
}

.badge-card.earned {
    border-color: var(--badge-color);
    background: linear-gradient(135deg, color-mix(in srgb, var(--badge-color) 5%, transparent), transparent);
}

.badge-card.locked {
    opacity: 0.6;
}

.badge-card:not(.locked):hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.badge-icon-wrapper {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.badge-icon {
    font-size: 1.75rem;
}

.badge-content {
    flex: 1;
    min-width: 0;
}

.badge-name {
    font-size: 1rem;
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 0.25rem;
}

.dark .badge-name {
    color: var(--gray-100);
}

.badge-description {
    font-size: 0.8rem;
    color: var(--gray-500);
    margin-bottom: 0.5rem;
}

.badge-rewards {
    display: flex;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}

.reward {
    font-size: 0.75rem;
    font-weight: 600;
}

.reward.xp {
    color: #10b981;
}

.reward.coin {
    color: #f59e0b;
}

.badge-condition {
    font-size: 0.75rem;
    color: var(--gray-400);
    font-style: italic;
}

.earned-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    width: 28px;
    height: 28px;
    background: #10b981;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.9rem;
}

.locked-overlay {
    position: absolute;
    top: 50%;
    right: 1rem;
    transform: translateY(-50%);
    font-size: 1.25rem;
}
</style>
