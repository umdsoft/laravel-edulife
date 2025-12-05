<script setup>
import { Head, Link } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';

const props = defineProps({
    history: Array,
    pagination: Object,
    statistics: Object,
});

const getRankEmoji = (rank) => {
    if (rank === 1) return '🥇';
    if (rank === 2) return '🥈';
    if (rank === 3) return '🥉';
    if (rank <= 10) return '🏅';
    return '';
};

const getStatusBadge = (status) => {
    const badges = {
        'completed': { label: 'Yakunlangan', class: 'bg-green-100 text-green-700' },
        'disqualified': { label: 'Diskvalifikatsiya', class: 'bg-red-100 text-red-700' },
        'in_progress': { label: 'Jarayonda', class: 'bg-blue-100 text-blue-700' },
    };
    return badges[status] || { label: status, class: 'bg-gray-100 text-gray-700' };
};

const formatPrice = (price) => {
    if (!price || price === 0) return '—';
    return new Intl.NumberFormat('uz-UZ').format(price) + " so'm";
};
</script>

<template>
    <StudentLayout>
        <Head title="Olimpiada tarixi" />
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <span class="text-4xl">📜</span>
                    Olimpiada tarixi
                </h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    Barcha ishtirok etgan olimpiadalaringiz
                </p>
            </div>

            <!-- Statistics Cards -->
            <div class="grid sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 text-center">
                    <div class="text-2xl font-bold text-purple-600">{{ statistics.total_participated }}</div>
                    <div class="text-sm text-gray-500">Ishtirok etilgan</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 text-center">
                    <div class="text-2xl font-bold text-green-600">{{ statistics.total_completed }}</div>
                    <div class="text-sm text-gray-500">Yakunlangan</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 text-center">
                    <div class="text-2xl font-bold text-amber-600">{{ getRankEmoji(statistics.best_rank) }} {{ statistics.best_rank || '—' }}</div>
                    <div class="text-sm text-gray-500">Eng yaxshi o'rin</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 text-center">
                    <div class="text-2xl font-bold text-yellow-500">🪙 {{ statistics.total_coins }}</div>
                    <div class="text-sm text-gray-500">Jami coins</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 text-center">
                    <div class="text-2xl font-bold text-green-500">{{ formatPrice(statistics.total_cash) }}</div>
                    <div class="text-sm text-gray-500">Naqd sovrin</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 text-center">
                    <div class="text-2xl font-bold text-indigo-600">🎓 {{ statistics.certificates }}</div>
                    <div class="text-sm text-gray-500">Sertifikatlar</div>
                </div>
            </div>

            <!-- History List -->
            <div v-if="history.length > 0" class="space-y-4">
                <div 
                    v-for="item in history" 
                    :key="item.id"
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow">
                    <div class="flex flex-col md:flex-row md:items-center p-6 gap-6">
                        <!-- Rank Badge -->
                        <div class="flex-shrink-0 w-16 h-16 rounded-2xl flex items-center justify-center text-3xl"
                            :class="item.rank <= 3 ? 'bg-gradient-to-br from-yellow-400 to-amber-500' : 'bg-gray-100 dark:bg-gray-700'">
                            <span v-if="item.rank">{{ getRankEmoji(item.rank) || `#${item.rank}` }}</span>
                            <span v-else class="text-gray-400 text-lg">—</span>
                        </div>

                        <!-- Info -->
                        <div class="flex-1">
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white">
                                {{ item.olympiad_title }}
                            </h3>
                            <div class="flex flex-wrap gap-3 mt-2 text-sm text-gray-500">
                                <span>{{ item.olympiad_type }}</span>
                                <span v-if="item.stage">• {{ item.stage }}</span>
                                <span>• {{ item.created_at }}</span>
                            </div>
                        </div>

                        <!-- Stats -->
                        <div class="flex flex-wrap gap-4 md:gap-8">
                            <div class="text-center">
                                <div class="text-xl font-bold" :class="item.score_percent >= 60 ? 'text-green-600' : 'text-red-600'">
                                    {{ item.score_percent }}%
                                </div>
                                <div class="text-xs text-gray-500">Ball</div>
                            </div>
                            <div class="text-center">
                                <div class="text-xl font-bold text-gray-900 dark:text-white">
                                    {{ item.rank ? `#${item.rank}` : '—' }}
                                </div>
                                <div class="text-xs text-gray-500">{{ item.total_participants }} dan</div>
                            </div>
                            <div v-if="item.coins_earned" class="text-center">
                                <div class="text-xl font-bold text-yellow-500">+{{ item.coins_earned }}</div>
                                <div class="text-xs text-gray-500">Coins</div>
                            </div>
                        </div>

                        <!-- Status & Actions -->
                        <div class="flex items-center gap-4">
                            <span :class="['px-3 py-1 rounded-full text-xs font-medium', getStatusBadge(item.status).class]">
                                {{ getStatusBadge(item.status).label }}
                            </span>
                            <div class="flex gap-2">
                                <Link 
                                    v-if="item.certificate_issued"
                                    :href="route('student.olympiads.certificate.download', item.olympiad_id)"
                                    class="p-2 bg-purple-100 text-purple-600 rounded-lg hover:bg-purple-200 transition-colors"
                                    title="Sertifikat">
                                    🎓
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-16 bg-white dark:bg-gray-800 rounded-2xl">
                <span class="text-6xl mb-4 block">🏆</span>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                    Hali olimpiadalarda ishtirok etmagansiz
                </h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    Birinchi olimpiadangizni tanlang va o'z bilimingizni sinab ko'ring!
                </p>
                <Link 
                    :href="route('student.olympiads.index')"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-purple-600 text-white rounded-xl font-medium hover:bg-purple-700 transition-colors">
                    Olimpiadalarni ko'rish →
                </Link>
            </div>

            <!-- Pagination -->
            <div v-if="pagination.last_page > 1" class="mt-8 flex justify-center gap-2">
                <button 
                    v-for="page in pagination.last_page" 
                    :key="page"
                    @click="$inertia.visit(route('student.olympiad-history', { page }))"
                    :class="[
                        'w-10 h-10 rounded-lg font-medium transition-colors',
                        page === pagination.current_page 
                            ? 'bg-purple-600 text-white' 
                            : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100'
                    ]">
                    {{ page }}
                </button>
            </div>
        </div>
    </StudentLayout>
</template>
