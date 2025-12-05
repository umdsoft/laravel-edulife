<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    olympiad: Object,
    liveStats: Object,
    topPerformers: Array,
    activeViolations: Array,
});

const stats = ref(props.liveStats);
const refreshInterval = ref(null);

const refreshStats = async () => {
    try {
        const response = await fetch(route('admin.olympiads.monitor.stats', props.olympiad.id));
        stats.value = await response.json();
    } catch (error) {
        console.error('Error refreshing stats:', error);
    }
};

onMounted(() => {
    refreshInterval.value = setInterval(refreshStats, 5000);
});

onUnmounted(() => {
    if (refreshInterval.value) clearInterval(refreshInterval.value);
});
</script>

<template>
    <AdminLayout>
        <Head :title="`Monitoring - ${olympiad.title}`" />
        
        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <div class="flex items-center gap-3">
                            <span class="flex h-4 w-4 relative">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-4 w-4 bg-red-500"></span>
                            </span>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Live Monitoring</h1>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">{{ olympiad.title }}</p>
                    </div>
                    <Link 
                        :href="route('admin.olympiads.show', olympiad.id)"
                        class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 transition-colors">
                        ← Orqaga
                    </Link>
                </div>
                
                <!-- Live Stats -->
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Faol ishtirokchilar</p>
                                <p class="text-3xl font-bold text-green-600">{{ stats.active_now }}</p>
                            </div>
                            <span class="text-4xl">🟢</span>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Yakunlagan</p>
                                <p class="text-3xl font-bold text-blue-600">{{ stats.completed }}</p>
                            </div>
                            <span class="text-4xl">✅</span>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Diskvalifikatsiya</p>
                                <p class="text-3xl font-bold text-red-600">{{ stats.disqualified }}</p>
                            </div>
                            <span class="text-4xl">⚠️</span>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">O'rtacha ball</p>
                                <p class="text-3xl font-bold text-purple-600">{{ stats.average_score }}%</p>
                            </div>
                            <span class="text-4xl">📊</span>
                        </div>
                    </div>
                </div>
                
                <!-- Progress Bar -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-8">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-gray-900 dark:text-white">Umumiy progress</h3>
                        <span class="text-sm text-gray-500">{{ stats.completion_rate }}% yakunlangan</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                        <div 
                            class="bg-gradient-to-r from-purple-500 to-indigo-500 h-4 rounded-full transition-all duration-500"
                            :style="{ width: `${stats.completion_rate}%` }">
                        </div>
                    </div>
                    <div class="flex justify-between mt-2 text-xs text-gray-500">
                        <span>{{ stats.total_registered }} ro'yxatdan o'tgan</span>
                        <span>{{ stats.not_started }} boshlamagan</span>
                    </div>
                </div>
                
                <div class="grid lg:grid-cols-2 gap-8">
                    <!-- Top Performers -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white">🏆 Top Ishtirokchilar</h3>
                            <Link 
                                :href="route('admin.olympiads.monitor.leaderboard', olympiad.id)"
                                class="text-sm text-purple-600 hover:underline">
                                Barchasini ko'rish →
                            </Link>
                        </div>
                        <div class="space-y-3">
                            <div 
                                v-for="(performer, index) in topPerformers" 
                                :key="index"
                                class="flex items-center gap-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                                <div class="w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-900 flex items-center justify-center font-bold text-purple-600 dark:text-purple-300">
                                    {{ performer.rank }}
                                </div>
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ performer.user_name }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="font-bold text-purple-600">{{ performer.score }}</div>
                                    <div class="text-xs text-gray-500">{{ performer.score_percent }}%</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Active Violations -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white">⚠️ Faol qoidabuzarliklar</h3>
                            <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm font-medium">
                                {{ stats.active_violations }} ta
                            </span>
                        </div>
                        <div v-if="activeViolations.length > 0" class="space-y-3">
                            <div 
                                v-for="violation in activeViolations" 
                                :key="violation.id"
                                class="flex items-center gap-4 p-3 rounded-xl"
                                :class="violation.severity === 'critical' ? 'bg-red-50 dark:bg-red-900/20' : 'bg-yellow-50 dark:bg-yellow-900/20'">
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">{{ violation.user_name }}</div>
                                    <div class="text-sm text-gray-500">{{ violation.type }} ({{ violation.count }}x)</div>
                                </div>
                                <div class="flex-1"></div>
                                <span 
                                    :class="[
                                        'px-2 py-1 rounded text-xs font-medium',
                                        violation.severity === 'critical' ? 'bg-red-500 text-white' : 'bg-yellow-500 text-white'
                                    ]">
                                    {{ violation.severity }}
                                </span>
                                <span class="text-xs text-gray-500">{{ violation.created_at }}</span>
                            </div>
                        </div>
                        <div v-else class="text-center py-8 text-gray-500">
                            <span class="text-4xl mb-2 block">✅</span>
                            Qoidabuzarlik yo'q
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
