<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    olympiads: Array,
    pagination: Object,
    filters: Object,
    currentFilters: Object,
});

const getStatusBadge = (status) => {
    const badges = {
        'draft': { label: 'Qoralama', class: 'bg-gray-100 text-gray-700' },
        'upcoming': { label: 'Tez kunda', class: 'bg-blue-100 text-blue-700' },
        'registration_open': { label: "Ro'yxat ochiq", class: 'bg-green-100 text-green-700' },
        'live': { label: 'Jonli', class: 'bg-red-100 text-red-700' },
        'grading': { label: 'Baholanmoqda', class: 'bg-yellow-100 text-yellow-700' },
        'completed': { label: 'Yakunlangan', class: 'bg-purple-100 text-purple-700' },
    };
    return badges[status] || badges['draft'];
};

const applyFilters = () => {
    router.get(route('admin.olympiads.index'), props.currentFilters, { preserveState: true });
};
</script>

<template>
    <AdminLayout>
        <Head title="Olimpiadalar boshqaruvi" />
        
        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">🏆 Olimpiadalar</h1>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">Barcha olimpiadalarni boshqaring</p>
                    </div>
                    <Link 
                        :href="route('admin.olympiads.create')"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-purple-600 text-white rounded-xl font-medium hover:bg-purple-700 transition-colors">
                        ➕ Yangi olimpiada
                    </Link>
                </div>
                
                <!-- Filters -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-8">
                    <div class="grid sm:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Turi</label>
                            <select 
                                v-model="currentFilters.type_id"
                                @change="applyFilters"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                <option value="">Barchasi</option>
                                <option v-for="type in filters.types" :key="type.id" :value="type.id">
                                    {{ type.display_name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Bosqich</label>
                            <select 
                                v-model="currentFilters.stage_id"
                                @change="applyFilters"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                <option value="">Barchasi</option>
                                <option v-for="stage in filters.stages" :key="stage.id" :value="stage.id">
                                    {{ stage.display_name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                            <select 
                                v-model="currentFilters.status"
                                @change="applyFilters"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                <option value="">Barchasi</option>
                                <option v-for="status in filters.statuses" :key="status" :value="status">
                                    {{ getStatusBadge(status).label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Qidirish</label>
                            <input 
                                v-model="currentFilters.search"
                                @keyup.enter="applyFilters"
                                type="text"
                                placeholder="Nomi bo'yicha..."
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                        </div>
                    </div>
                </div>
                
                <!-- Table -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Olimpiada</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Turi</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Sana</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Ishtirokchilar</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Amallar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="olympiad in olympiads" :key="olympiad.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-6 py-4">
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">{{ olympiad.title }}</div>
                                        <div class="text-sm text-gray-500">{{ olympiad.stage }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span 
                                        class="inline-flex px-3 py-1 rounded-full text-xs font-medium"
                                        :style="{ backgroundColor: (olympiad.type_color || '#9333ea') + '20', color: olympiad.type_color || '#9333ea' }">
                                        {{ olympiad.type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="['inline-flex px-3 py-1 rounded-full text-xs font-medium', getStatusBadge(olympiad.status).class]">
                                        {{ getStatusBadge(olympiad.status).label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ olympiad.olympiad_start_at || '—' }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2 text-sm">
                                        <span class="font-medium text-gray-900 dark:text-white">{{ olympiad.registrations_count }}</span>
                                        <span class="text-gray-500">ro'yxat</span>
                                        <span class="text-gray-300">|</span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ olympiad.attempts_count }}</span>
                                        <span class="text-gray-500">urinish</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link 
                                            :href="route('admin.olympiads.show', olympiad.id)"
                                            class="p-2 text-gray-500 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors"
                                            title="Ko'rish">
                                            👁️
                                        </Link>
                                        <Link 
                                            :href="route('admin.olympiads.edit', olympiad.id)"
                                            class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                            title="Tahrirlash">
                                            ✏️
                                        </Link>
                                        <Link 
                                            v-if="olympiad.status === 'live'"
                                            :href="route('admin.olympiads.monitor', olympiad.id)"
                                            class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Monitoring">
                                            📡
                                        </Link>
                                        <Link 
                                            v-if="['grading', 'completed'].includes(olympiad.status)"
                                            :href="route('admin.olympiads.grading.index', olympiad.id)"
                                            class="p-2 text-gray-500 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors"
                                            title="Baholash">
                                            📝
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <!-- Pagination -->
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">
                                Jami: {{ pagination.total }} ta olimpiada
                            </span>
                            <div class="flex gap-2">
                                <button 
                                    v-for="page in pagination.last_page" 
                                    :key="page"
                                    @click="router.get(route('admin.olympiads.index'), { ...currentFilters, page })"
                                    :class="[
                                        'w-10 h-10 rounded-lg font-medium transition-colors',
                                        page === pagination.current_page 
                                            ? 'bg-purple-600 text-white' 
                                            : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100'
                                    ]">
                                    {{ page }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
