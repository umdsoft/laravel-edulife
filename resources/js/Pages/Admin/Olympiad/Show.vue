<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    olympiad: Object,
    statistics: Object,
});

const getStatusBadge = (status) => {
    const badges = {
        'draft': { label: 'Qoralama', class: 'bg-gray-100 text-gray-700', icon: '📝' },
        'upcoming': { label: 'Tez kunda', class: 'bg-blue-100 text-blue-700', icon: '📅' },
        'registration_open': { label: "Ro'yxat ochiq", class: 'bg-green-100 text-green-700', icon: '✅' },
        'live': { label: 'Jonli', class: 'bg-red-100 text-red-700', icon: '🔴' },
        'grading': { label: 'Baholanmoqda', class: 'bg-yellow-100 text-yellow-700', icon: '⏳' },
        'completed': { label: 'Yakunlangan', class: 'bg-purple-100 text-purple-700', icon: '🏁' },
    };
    return badges[status] || badges['draft'];
};

const formatPrice = (price) => {
    if (!price || price === 0) return 'Bepul';
    return new Intl.NumberFormat('uz-UZ').format(price) + " so'm";
};

const showDuplicateModal = ref(false);
</script>

<template>
    <AdminLayout>
        <Head :title="`${olympiad.title} - Boshqaruv`" />
        
        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-8">
                    <div>
                        <Link :href="route('admin.olympiads.index')" class="text-purple-600 hover:underline text-sm mb-2 inline-block">
                            ← Olimpiadalar
                        </Link>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                            {{ olympiad.title }}
                            <span :class="['px-3 py-1 rounded-full text-sm font-medium', getStatusBadge(olympiad.status).class]">
                                {{ getStatusBadge(olympiad.status).icon }} {{ getStatusBadge(olympiad.status).label }}
                            </span>
                        </h1>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <Link 
                            :href="route('admin.olympiads.edit', olympiad.id)"
                            class="px-4 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-50 border border-gray-200 dark:border-gray-700">
                            ✏️ Tahrirlash
                        </Link>
                        <button 
                            @click="showDuplicateModal = true"
                            class="px-4 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-50 border border-gray-200 dark:border-gray-700">
                            📋 Nusxalash
                        </button>
                        <Link 
                            v-if="olympiad.status === 'live'"
                            :href="route('admin.olympiads.monitor', olympiad.id)"
                            class="px-4 py-2 bg-red-600 text-white rounded-xl font-medium hover:bg-red-700">
                            📡 Monitoring
                        </Link>
                        <Link 
                            v-if="['grading', 'completed'].includes(olympiad.status)"
                            :href="route('admin.olympiads.grading.index', olympiad.id)"
                            class="px-4 py-2 bg-green-600 text-white rounded-xl font-medium hover:bg-green-700">
                            📝 Baholash
                        </Link>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Ro'yxatdagilar</p>
                                <p class="text-3xl font-bold text-purple-600">{{ statistics.total_registrations || 0 }}</p>
                            </div>
                            <span class="text-4xl">👥</span>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Yakunlaganlar</p>
                                <p class="text-3xl font-bold text-green-600">{{ statistics.total_completed || 0 }}</p>
                            </div>
                            <span class="text-4xl">✅</span>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">O'rtacha ball</p>
                                <p class="text-3xl font-bold text-blue-600">{{ statistics.average_score || 0 }}%</p>
                            </div>
                            <span class="text-4xl">📊</span>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Daromad</p>
                                <p class="text-3xl font-bold text-amber-600">{{ formatPrice(statistics.total_revenue || 0) }}</p>
                            </div>
                            <span class="text-4xl">💰</span>
                        </div>
                    </div>
                </div>

                <div class="grid lg:grid-cols-3 gap-8">
                    <!-- Details -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Main Info -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-4">📋 Asosiy ma'lumotlar</h3>
                            <div class="grid sm:grid-cols-2 gap-4 text-sm">
                                <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                                    <span class="text-gray-500">Turi:</span>
                                    <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ olympiad.type?.display_name }}</span>
                                </div>
                                <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                                    <span class="text-gray-500">Bosqich:</span>
                                    <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ olympiad.stage?.display_name || '—' }}</span>
                                </div>
                                <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                                    <span class="text-gray-500">Narxi:</span>
                                    <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ formatPrice(olympiad.schedule?.registration_fee) }}</span>
                                </div>
                                <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                                    <span class="text-gray-500">Yaratuvchi:</span>
                                    <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ olympiad.created_by }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Schedule -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-4">📅 Jadval</h3>
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                                    <span class="text-gray-500">Ro'yxat boshlanishi:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ olympiad.schedule?.registration_start_at || '—' }}</span>
                                </div>
                                <div class="flex justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                                    <span class="text-gray-500">Ro'yxat tugashi:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ olympiad.schedule?.registration_end_at || '—' }}</span>
                                </div>
                                <div class="flex justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                                    <span class="text-blue-600">Olimpiada boshlanishi:</span>
                                    <span class="font-medium text-blue-700">{{ olympiad.schedule?.olympiad_start_at }}</span>
                                </div>
                                <div class="flex justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                                    <span class="text-blue-600">Olimpiada tugashi:</span>
                                    <span class="font-medium text-blue-700">{{ olympiad.schedule?.olympiad_end_at }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Sections -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-bold text-lg text-gray-900 dark:text-white">🧩 Bo'limlar</h3>
                                <Link 
                                    :href="route('admin.olympiads.edit', olympiad.id) + '#sections'"
                                    class="text-sm text-purple-600 hover:underline">
                                    Tahrirlash →
                                </Link>
                            </div>
                            <div class="space-y-3">
                                <div 
                                    v-for="(section, index) in olympiad.sections" 
                                    :key="section.id"
                                    class="flex items-center gap-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                                    <span class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center font-bold text-purple-600">
                                        {{ index + 1 }}
                                    </span>
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ section.title }}</div>
                                        <div class="text-xs text-gray-500">{{ section.section_type }}</div>
                                    </div>
                                    <div class="text-sm text-gray-500">{{ section.duration_minutes }} daqiqa</div>
                                    <div class="text-sm font-medium text-purple-600">{{ section.max_points }} ball</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar Actions -->
                    <div class="space-y-6">
                        <!-- Quick Actions -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-4">⚡ Tezkor amallar</h3>
                            <div class="space-y-3">
                                <Link 
                                    :href="route('admin.olympiads.registrations', olympiad.id)"
                                    class="flex items-center justify-between w-full p-3 bg-gray-50 dark:bg-gray-700 rounded-xl hover:bg-gray-100 transition-colors">
                                    <span class="text-gray-700 dark:text-gray-300">👥 Ro'yxatdagilar</span>
                                    <span class="text-purple-600 font-medium">{{ statistics.total_registrations || 0 }}</span>
                                </Link>
                                <Link 
                                    :href="route('admin.olympiads.monitor.leaderboard', olympiad.id)"
                                    class="flex items-center justify-between w-full p-3 bg-gray-50 dark:bg-gray-700 rounded-xl hover:bg-gray-100 transition-colors">
                                    <span class="text-gray-700 dark:text-gray-300">🏆 Reyting</span>
                                    <span class="text-gray-500">→</span>
                                </Link>
                                <Link 
                                    :href="route('admin.olympiads.grading.export', olympiad.id)"
                                    class="flex items-center justify-between w-full p-3 bg-gray-50 dark:bg-gray-700 rounded-xl hover:bg-gray-100 transition-colors">
                                    <span class="text-gray-700 dark:text-gray-300">📥 CSV eksport</span>
                                    <span class="text-gray-500">→</span>
                                </Link>
                            </div>
                        </div>

                        <!-- Status Change -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-4">🔄 Statusni o'zgartirish</h3>
                            <div class="space-y-2">
                                <button 
                                    v-if="olympiad.status === 'draft'"
                                    @click="$inertia.patch(route('admin.olympiads.status', olympiad.id), { status: 'upcoming' })"
                                    class="w-full py-2 bg-blue-600 text-white rounded-xl font-medium hover:bg-blue-700 transition-colors">
                                    📅 Tez kundalarga o'tkazish
                                </button>
                                <button 
                                    v-if="olympiad.status === 'upcoming'"
                                    @click="$inertia.patch(route('admin.olympiads.status', olympiad.id), { status: 'registration_open' })"
                                    class="w-full py-2 bg-green-600 text-white rounded-xl font-medium hover:bg-green-700 transition-colors">
                                    ✅ Ro'yxatni ochish
                                </button>
                                <button 
                                    v-if="olympiad.status === 'registration_open'"
                                    @click="$inertia.patch(route('admin.olympiads.status', olympiad.id), { status: 'live' })"
                                    class="w-full py-2 bg-red-600 text-white rounded-xl font-medium hover:bg-red-700 transition-colors">
                                    🔴 Jonli o'tkazish
                                </button>
                                <button 
                                    v-if="olympiad.status === 'live'"
                                    @click="$inertia.patch(route('admin.olympiads.status', olympiad.id), { status: 'grading' })"
                                    class="w-full py-2 bg-yellow-600 text-white rounded-xl font-medium hover:bg-yellow-700 transition-colors">
                                    ⏳ Baholashga o'tkazish
                                </button>
                                <button 
                                    v-if="olympiad.status === 'grading'"
                                    @click="$inertia.patch(route('admin.olympiads.status', olympiad.id), { status: 'completed' })"
                                    class="w-full py-2 bg-purple-600 text-white rounded-xl font-medium hover:bg-purple-700 transition-colors">
                                    🏁 Yakunlash
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
