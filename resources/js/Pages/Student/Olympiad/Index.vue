<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    olympiads: Array,
    types: Array,
    selectedType: String,
    search: String,
});

const selectedTypeId = ref(props.selectedType || '');
const searchQuery = ref(props.search || '');
const isLoading = ref(false);
const showFilters = ref(false);

// Search debounce
let searchTimeout = null;
watch(searchQuery, (newVal) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 400);
});

const applyFilters = () => {
    isLoading.value = true;
    router.get(route('student.olympiads.index'), {
        type: selectedTypeId.value || undefined,
        search: searchQuery.value || undefined,
    }, {
        preserveState: true,
        onFinish: () => isLoading.value = false,
    });
};

const filterByType = (typeId) => {
    selectedTypeId.value = typeId;
    applyFilters();
};

const clearFilters = () => {
    selectedTypeId.value = '';
    searchQuery.value = '';
    applyFilters();
};

const getStatusBadge = (status) => {
    const badges = {
        'upcoming': { label: 'Tez kunda', class: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300', icon: '📅' },
        'registration_open': { label: "Ro'yxat ochiq", class: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300', icon: '✅' },
        'live': { label: 'Jonli', class: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300 animate-pulse', icon: '🔴' },
        'grading': { label: 'Baholanmoqda', class: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300', icon: '⏳' },
        'completed': { label: 'Yakunlangan', class: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300', icon: '🏁' },
    };
    return badges[status] || badges['upcoming'];
};

const formatPrice = (price) => {
    if (!price || price === 0) return 'Bepul';
    return new Intl.NumberFormat('uz-UZ').format(price) + " so'm";
};

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('uz-UZ', { day: 'numeric', month: 'short' });
};

const hasActiveFilters = computed(() => selectedTypeId.value || searchQuery.value);
</script>

<template>
    <StudentLayout>

        <Head title="Olimpiadalar" />

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
            <!-- Header with Search -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1
                        class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-2 sm:gap-3">
                        <span class="text-3xl sm:text-4xl">🏆</span>
                        Olimpiadalar
                    </h1>
                    <p class="mt-1 text-sm sm:text-base text-gray-600 dark:text-gray-400">
                        O'z bilimingizni sinab ko'ring va sovrinlar yutib oling
                    </p>
                </div>

                <!-- Search -->
                <div class="relative w-full sm:w-72">
                    <input v-model="searchQuery" type="search" placeholder="Qidirish..."
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-800 focus:ring-purple-500 focus:border-purple-500 text-sm"
                        aria-label="Olimpiadalarni qidirish">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <div v-if="isLoading" class="absolute right-3 top-1/2 -translate-y-1/2">
                        <div class="w-4 h-4 border-2 border-purple-500 border-t-transparent rounded-full animate-spin">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Type Filters - Horizontal scroll on mobile -->
            <div class="mb-6 -mx-4 px-4 sm:mx-0 sm:px-0">
                <div class="flex gap-2 overflow-x-auto pb-2 sm:pb-0 sm:flex-wrap scrollbar-hide">
                    <button @click="filterByType('')" :class="[
                        'px-4 py-2 rounded-full text-sm font-medium transition-all whitespace-nowrap flex-shrink-0',
                        !selectedTypeId
                            ? 'bg-purple-600 text-white shadow-lg shadow-purple-500/25'
                            : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                    ]" :aria-pressed="!selectedTypeId">
                        Barchasi
                    </button>
                    <button v-for="type in types" :key="type.id" @click="filterByType(type.id)" :class="[
                        'px-4 py-2 rounded-full text-sm font-medium transition-all inline-flex items-center gap-2 whitespace-nowrap flex-shrink-0',
                        selectedTypeId === type.id
                            ? 'bg-purple-600 text-white shadow-lg shadow-purple-500/25'
                            : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                    ]" :aria-pressed="selectedTypeId === type.id">
                        <span v-if="type.icon">{{ type.icon }}</span>
                        {{ type.display_name }}
                    </button>
                </div>

                <!-- Clear Filters -->
                <button v-if="hasActiveFilters" @click="clearFilters"
                    class="mt-2 text-sm text-purple-600 hover:text-purple-700 font-medium inline-flex items-center gap-1">
                    ✕ Filtrlarni tozalash
                </button>
            </div>

            <!-- Loading Skeleton -->
            <div v-if="isLoading && olympiads.length === 0"
                class="grid gap-4 sm:gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="n in 6" :key="n"
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden animate-pulse">
                    <div class="h-40 sm:h-48 bg-gray-200 dark:bg-gray-700"></div>
                    <div class="p-4 sm:p-5 space-y-3">
                        <div class="h-5 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-full"></div>
                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-2/3"></div>
                        <div class="flex justify-between pt-3 border-t border-gray-100 dark:border-gray-700">
                            <div class="h-6 bg-gray-200 dark:bg-gray-700 rounded w-20"></div>
                            <div class="h-10 bg-gray-200 dark:bg-gray-700 rounded w-24"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Olympiad Cards -->
            <div v-else-if="olympiads.length > 0" class="grid gap-4 sm:gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
                <Link v-for="olympiad in olympiads" :key="olympiad.id"
                    :href="route('student.olympiads.show', olympiad.slug)"
                    class="group bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-1 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                <!-- Card Image -->
                <div class="relative h-40 sm:h-48 bg-gradient-to-br from-purple-500 to-indigo-600 overflow-hidden">
                    <img v-if="olympiad.cover_image" :src="olympiad.cover_image" :alt="olympiad.title" loading="lazy"
                        class="w-full h-full object-cover opacity-90 group-hover:scale-105 transition-transform duration-300">
                    <div v-else class="absolute inset-0 flex items-center justify-center">
                        <span class="text-6xl sm:text-7xl opacity-30">🏆</span>
                    </div>

                    <!-- Badges -->
                    <div class="absolute inset-x-0 top-0 p-3 sm:p-4 flex justify-between items-start">
                        <span
                            class="px-2.5 sm:px-3 py-1 bg-white/90 dark:bg-gray-800/90 rounded-full text-xs font-semibold text-gray-700 dark:text-gray-200 backdrop-blur-sm">
                            {{ olympiad.type }}
                        </span>
                        <span
                            :class="['px-2.5 sm:px-3 py-1 rounded-full text-xs font-semibold backdrop-blur-sm', getStatusBadge(olympiad.status).class]">
                            {{ getStatusBadge(olympiad.status).icon }} {{ getStatusBadge(olympiad.status).label }}
                        </span>
                    </div>

                    <!-- Registered Badge -->
                    <div v-if="olympiad.is_registered" class="absolute bottom-3 left-3">
                        <span
                            class="px-2.5 py-1 bg-green-500 text-white rounded-full text-xs font-semibold inline-flex items-center gap-1">
                            ✓ Ro'yxatda
                        </span>
                    </div>
                </div>

                <!-- Card Content -->
                <div class="p-4 sm:p-5">
                    <h3
                        class="font-bold text-base sm:text-lg text-gray-900 dark:text-white mb-1.5 sm:mb-2 line-clamp-2 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">
                        {{ olympiad.title }}
                    </h3>

                    <p v-if="olympiad.short_description || olympiad.description"
                        class="text-sm text-gray-600 dark:text-gray-400 mb-3 sm:mb-4 line-clamp-2">
                        {{ olympiad.short_description || olympiad.description }}
                    </p>

                    <!-- Meta Info -->
                    <div class="flex flex-wrap gap-2 text-xs text-gray-500 dark:text-gray-400 mb-3 sm:mb-4">
                        <span v-if="olympiad.stage"
                            class="inline-flex items-center gap-1 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded-md">
                            📊 {{ olympiad.stage }}
                        </span>
                        <span v-if="olympiad.total_duration"
                            class="inline-flex items-center gap-1 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded-md">
                            ⏱️ {{ olympiad.total_duration }} daq
                        </span>
                        <span v-if="olympiad.olympiad_start_at"
                            class="inline-flex items-center gap-1 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded-md">
                            📅 {{ formatDate(olympiad.olympiad_start_at) }}
                        </span>
                    </div>

                    <!-- Price & Action -->
                    <div
                        class="flex items-center justify-between pt-3 sm:pt-4 border-t border-gray-100 dark:border-gray-700">
                        <div>
                            <span class="text-base sm:text-lg font-bold"
                                :class="olympiad.registration_fee ? 'text-purple-600' : 'text-green-600'">
                                {{ formatPrice(olympiad.registration_fee) }}
                            </span>
                        </div>
                        <span
                            class="px-4 sm:px-5 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium group-hover:bg-purple-700 transition-colors inline-flex items-center gap-1.5">
                            <span v-if="olympiad.is_registered">
                                {{ olympiad.registration_status === 'confirmed' ? 'Ochish' : 'To\'lash' }}
                            </span>
                            <span v-else>Ko'rish</span>
                            <svg class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </span>
                    </div>
                </div>
                </Link>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-12 sm:py-16 bg-white dark:bg-gray-800 rounded-2xl">
                <div
                    class="w-20 h-20 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                    <span class="text-4xl">🔍</span>
                </div>
                <h3 class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-white mb-2">
                    Olimpiadalar topilmadi
                </h3>
                <p class="text-sm sm:text-base text-gray-600 dark:text-gray-400 mb-6 max-w-md mx-auto px-4">
                    {{ searchQuery ? 'Qidiruv natijalari topilmadi' : 'Hozircha bu toifada olimpiadalar mavjud emas' }}
                </p>
                <button v-if="hasActiveFilters" @click="clearFilters"
                    class="px-6 py-2.5 bg-purple-600 text-white rounded-xl font-medium hover:bg-purple-700 transition-colors">
                    Filtrlarni tozalash
                </button>
            </div>
        </div>
    </StudentLayout>
</template>
