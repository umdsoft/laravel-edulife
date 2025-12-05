<script setup>
import { Head, Link } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
    olympiad: Object,
    attempt: Object,
    sectionDetails: Array,
    ranking: Object,
    nearbyCompetitors: Object,
    statistics: Object,
});

const activeTab = ref('overview');
const showShareModal = ref(false);
const showCertificateModal = ref(false);
const chartCanvas = ref(null);

// Chart data for radar/bar visualization
const chartData = computed(() => {
    return props.sectionDetails.map(s => ({
        label: s.section_title,
        value: s.score_percent,
        color: getBarColor(s.score_percent),
    }));
});

const getScoreColor = (percent) => {
    if (percent >= 80) return 'text-green-600';
    if (percent >= 60) return 'text-blue-600';
    if (percent >= 40) return 'text-yellow-600';
    return 'text-red-600';
};

const getBarColor = (percent) => {
    if (percent >= 80) return 'bg-green-500';
    if (percent >= 60) return 'bg-blue-500';
    if (percent >= 40) return 'bg-yellow-500';
    return 'bg-red-500';
};

const getRankEmoji = (rank) => {
    if (rank === 1) return '🥇';
    if (rank === 2) return '🥈';
    if (rank === 3) return '🥉';
    return '🏅';
};

// Share functions
const shareToTelegram = () => {
    const text = `${props.olympiad.title} olimpiadasida ${props.attempt.score_percent}% ball to'pladim! 🎓`;
    const url = window.location.href;
    window.open(`https://t.me/share/url?url=${encodeURIComponent(url)}&text=${encodeURIComponent(text)}`, '_blank');
};

const shareToTwitter = () => {
    const text = `I scored ${props.attempt.score_percent}% in ${props.olympiad.title}! 🏆`;
    const url = window.location.href;
    window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(url)}`, '_blank');
};

const copyShareLink = async () => {
    try {
        await navigator.clipboard.writeText(window.location.href);
        alert('Havola nusxalandi!');
    } catch (e) { }
};

const downloadCertificate = (format) => {
    window.location.href = route('student.olympiads.certificate.download', { slug: props.olympiad.slug, format });
};
</script>

<template>
    <StudentLayout>

        <Head :title="`Natijalar - ${olympiad.title}`" />

        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            <!-- Hero Section -->
            <div class="bg-gradient-to-br from-purple-600 via-indigo-600 to-blue-700 py-12">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center text-white">
                        <h1 class="text-3xl font-bold mb-2">{{ olympiad.title }}</h1>
                        <p class="text-purple-200">{{ olympiad.type }} • {{ olympiad.stage }}</p>

                        <!-- Score Display -->
                        <div class="mt-8 inline-flex items-center gap-8 bg-white/10 backdrop-blur rounded-2xl p-6">
                            <div class="text-center">
                                <div class="text-5xl font-bold mb-1">{{ attempt.score_percent }}%</div>
                                <div class="text-sm text-purple-200">Umumiy ball</div>
                            </div>
                            <div class="w-px h-16 bg-white/20"></div>
                            <div class="text-center">
                                <div class="text-5xl font-bold mb-1">{{ attempt.total_score }}/{{ attempt.max_score }}
                                </div>
                                <div class="text-sm text-purple-200">Ball</div>
                            </div>
                            <div class="w-px h-16 bg-white/20"></div>
                            <div class="text-center" v-if="ranking.rank">
                                <div class="text-5xl font-bold mb-1">{{ getRankEmoji(ranking.rank) }} #{{ ranking.rank
                                    }}</div>
                                <div class="text-sm text-purple-200">{{ ranking.total_participants }} ishtirokchidan
                                </div>
                            </div>
                        </div>

                        <!-- Status -->
                        <div v-if="attempt.is_grading"
                            class="mt-4 inline-flex items-center gap-2 bg-yellow-500/20 text-yellow-200 px-4 py-2 rounded-full">
                            <span class="animate-pulse">⏳</span>
                            Ba'zi bo'limlar hali baholanmoqda
                        </div>
                        <div v-if="attempt.is_disqualified"
                            class="mt-4 inline-flex items-center gap-2 bg-red-500/20 text-red-200 px-4 py-2 rounded-full">
                            <span>⚠️</span>
                            Diskvalifikatsiya: {{ attempt.disqualified_reason }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Tabs -->
                <div class="flex gap-2 mb-8 overflow-x-auto">
                    <button v-for="tab in ['overview', 'sections', 'leaderboard']" :key="tab" @click="activeTab = tab"
                        :class="[
                            'px-6 py-3 rounded-xl font-medium transition-all whitespace-nowrap',
                            activeTab === tab
                                ? 'bg-purple-600 text-white shadow-lg'
                                : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-50'
                        ]">
                        {{ tab === 'overview' ? '📊 Umumiy' : tab === 'sections' ? '🧩 Bo\'limlar' : '🏆 Reyting' }}
                    </button>
                </div>

                <!-- Overview Tab -->
                <div v-if="activeTab === 'overview'" class="grid lg:grid-cols-3 gap-8">
                    <!-- Stats Cards -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Attempt Info -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-4">📋 Imtihon ma'lumotlari
                            </h3>
                            <div class="grid sm:grid-cols-2 gap-4 text-sm">
                                <div class="flex justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                                    <span class="text-gray-500">Boshlangan:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ attempt.started_at
                                        }}</span>
                                </div>
                                <div class="flex justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                                    <span class="text-gray-500">Yakunlangan:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ attempt.completed_at
                                        }}</span>
                                </div>
                                <div class="flex justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                                    <span class="text-gray-500">Sarflangan vaqt:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ attempt.duration
                                        }}</span>
                                </div>
                                <div class="flex justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                                    <span class="text-gray-500">Percentile:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">Top {{ 100 -
                                        (ranking.percentile || 0) }}%</span>
                                </div>
                            </div>
                        </div>

                        <!-- Section Summary -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-4">🧩 Bo'limlar natijalari
                            </h3>
                            <div class="space-y-4">
                                <div v-for="section in sectionDetails" :key="section.section_type"
                                    class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-xl">
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ section.section_title
                                            }}</div>
                                        <div class="text-sm text-gray-500 mt-1">
                                            {{ section.questions_correct }}/{{ section.questions_answered }} to'g'ri
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div :class="['text-2xl font-bold', getScoreColor(section.score_percent)]">
                                            {{ section.score_percent }}%
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ section.raw_score }}/{{ section.max_score }}
                                        </div>
                                    </div>
                                    <div v-if="section.requires_grading" class="text-amber-500 animate-pulse">⏳</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Actions -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-4">📥 Harakatlar</h3>
                            <div class="space-y-3">
                                <button @click="showCertificateModal = true"
                                    class="flex items-center justify-center gap-2 w-full py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl font-medium hover:from-purple-700 hover:to-indigo-700 transition-all">
                                    🎓 Sertifikat olish
                                </button>
                                <Link :href="route('student.olympiads.results.review', olympiad.slug)"
                                    class="flex items-center justify-center gap-2 w-full py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-200 transition-colors">
                                📝 Javoblarni ko'rish
                                </Link>
                                <button @click="showShareModal = true"
                                    class="flex items-center justify-center gap-2 w-full py-3 bg-blue-500 text-white rounded-xl font-medium hover:bg-blue-600 transition-colors">
                                    📤 Ulashish
                                </button>
                            </div>
                        </div>

                        <!-- Visual Chart -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-4">📊 Bo'limlar grafigi</h3>
                            <div class="space-y-3">
                                <div v-for="item in chartData" :key="item.label" class="space-y-1">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-400 truncate">{{ item.label }}</span>
                                        <span class="font-medium">{{ item.value }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                                        <div :class="[item.color, 'h-3 rounded-full transition-all duration-500']"
                                            :style="{ width: item.value + '%' }"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Nearby Competitors -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-4">👥 Yaqin raqiblar</h3>
                            <div class="space-y-2">
                                <div v-for="comp in nearbyCompetitors.above" :key="comp.rank"
                                    class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">#{{ comp.rank }} {{ comp.name
                                        }}</span>
                                    <span class="font-medium">{{ comp.score }}</span>
                                </div>
                                <div v-if="ranking.rank"
                                    class="flex items-center justify-between p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg text-sm border-2 border-purple-500">
                                    <span class="text-purple-700 dark:text-purple-300 font-medium">#{{ ranking.rank }}
                                        Siz</span>
                                    <span class="font-bold text-purple-700 dark:text-purple-300">{{ attempt.total_score
                                        }}</span>
                                </div>
                                <div v-for="comp in nearbyCompetitors.below" :key="comp.rank"
                                    class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">#{{ comp.rank }} {{ comp.name
                                        }}</span>
                                    <span class="font-medium">{{ comp.score }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sections Tab -->
                <div v-if="activeTab === 'sections'" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                    <div class="space-y-6">
                        <div v-for="section in sectionDetails" :key="section.section_type"
                            class="border-b border-gray-200 dark:border-gray-700 pb-6 last:border-0">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="font-bold text-lg text-gray-900 dark:text-white">{{ section.section_title }}
                                </h4>
                                <span :class="['text-2xl font-bold', getScoreColor(section.score_percent)]">
                                    {{ section.score_percent }}%
                                </span>
                            </div>
                            <div class="grid sm:grid-cols-4 gap-4 text-center">
                                <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-xl">
                                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ section.raw_score
                                        }}</div>
                                    <div class="text-sm text-gray-500">Ball</div>
                                </div>
                                <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-xl">
                                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ section.max_score
                                        }}</div>
                                    <div class="text-sm text-gray-500">Maksimum</div>
                                </div>
                                <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-xl">
                                    <div class="text-2xl font-bold text-green-600">{{ section.questions_correct }}</div>
                                    <div class="text-sm text-gray-500">To'g'ri</div>
                                </div>
                                <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-xl">
                                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{
                                        section.questions_answered }}</div>
                                    <div class="text-sm text-gray-500">Javob berilgan</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Leaderboard Tab -->
                <div v-if="activeTab === 'leaderboard'" class="text-center">
                    <Link :href="route('student.olympiads.results.leaderboard', olympiad.slug)"
                        class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl font-bold hover:from-purple-700 hover:to-indigo-700 transition-all shadow-lg">
                    🏆 To'liq reytingni ko'rish
                    </Link>
                </div>
            </div>
        </div>

        <!-- Share Modal -->
        <div v-if="showShareModal"
            class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4"
            @click.self="showShareModal = false">
            <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-sm w-full p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">📤 Natijalarni ulashish</h3>
                <div class="space-y-3">
                    <button @click="shareToTelegram"
                        class="w-full flex items-center gap-3 p-4 bg-blue-500 text-white rounded-xl hover:bg-blue-600 transition-colors">
                        <span class="text-xl">✈️</span>
                        <span class="font-medium">Telegram</span>
                    </button>
                    <button @click="shareToTwitter"
                        class="w-full flex items-center gap-3 p-4 bg-sky-500 text-white rounded-xl hover:bg-sky-600 transition-colors">
                        <span class="text-xl">🐦</span>
                        <span class="font-medium">Twitter</span>
                    </button>
                    <button @click="copyShareLink"
                        class="w-full flex items-center gap-3 p-4 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                        <span class="text-xl">🔗</span>
                        <span class="font-medium">Havolani nusxalash</span>
                    </button>
                </div>
                <button @click="showShareModal = false"
                    class="w-full mt-4 py-2 text-gray-500 hover:text-gray-700">Yopish</button>
            </div>
        </div>

        <!-- Certificate Modal -->
        <div v-if="showCertificateModal"
            class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4"
            @click.self="showCertificateModal = false">
            <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-lg w-full p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">🎓 Sertifikat</h3>

                <!-- Certificate Preview -->
                <div
                    class="bg-gradient-to-br from-purple-100 to-indigo-100 dark:from-purple-900/30 dark:to-indigo-900/30 rounded-xl p-6 mb-6 border-4 border-purple-500/30">
                    <div class="text-center">
                        <div class="text-4xl mb-2">🏆</div>
                        <div class="text-xs text-purple-600 dark:text-purple-400 uppercase tracking-wider mb-2">
                            Sertifikat</div>
                        <div class="text-lg font-bold text-gray-900 dark:text-white mb-1">{{ olympiad.title }}</div>
                        <div class="text-3xl font-bold text-purple-600 my-3">{{ attempt.score_percent }}%</div>
                        <div v-if="ranking.rank" class="text-sm text-gray-600 dark:text-gray-400">
                            {{ getRankEmoji(ranking.rank) }} #{{ ranking.rank }} o'rin
                        </div>
                    </div>
                </div>

                <!-- Download Options -->
                <div class="grid grid-cols-2 gap-3">
                    <button @click="downloadCertificate('pdf')"
                        class="flex items-center justify-center gap-2 py-3 bg-red-500 text-white rounded-xl font-medium hover:bg-red-600">
                        📄 PDF
                    </button>
                    <button @click="downloadCertificate('png')"
                        class="flex items-center justify-center gap-2 py-3 bg-green-500 text-white rounded-xl font-medium hover:bg-green-600">
                        🖼️ PNG
                    </button>
                </div>
                <button @click="showCertificateModal = false"
                    class="w-full mt-4 py-2 text-gray-500 hover:text-gray-700">Yopish</button>
            </div>
        </div>
    </StudentLayout>
</template>
