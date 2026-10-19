<script setup>
import { Head, router } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { ref, computed, watch } from 'vue';
import { Badge, Button, Card, EmptyState, Input, PageHeader, Skeleton, Spinner } from '@/Components/UI';
import {
    ArrowRightIcon,
    CalendarDaysIcon,
    ChartBarIcon,
    ClockIcon,
    ClipboardDocumentListIcon,
    MagnifyingGlassIcon,
    TrophyIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { CheckCircleIcon } from '@heroicons/vue/20/solid';

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
        'upcoming': { label: 'Tez kunda', variant: 'info' },
        'registration_open': { label: "Ro'yxat ochiq", variant: 'success' },
        'live': { label: 'Jonli', variant: 'danger' },
        'grading': { label: 'Baholanmoqda', variant: 'warning' },
        'completed': { label: 'Yakunlangan', variant: 'neutral' },
    };
    return badges[status] || badges['upcoming'];
};

const groupDigits = (value) => String(Math.round(Number(value) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');

const formatPrice = (price) => {
    if (!price || Number(price) === 0) return 'Bepul';
    return `${groupDigits(price)} so'm`;
};

// Backend sends "dd.mm.yyyy HH:ii"; show the date part (dd.mm.yyyy).
const formatDate = (date) => {
    if (!date) return '';
    const str = String(date);
    if (/^\d{2}\.\d{2}\.\d{4}/.test(str)) return str.slice(0, 10);
    const d = new Date(str);
    if (Number.isNaN(d.getTime())) return str;
    const pad = (n) => String(n).padStart(2, '0');
    return `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()}`;
};

const hasActiveFilters = computed(() => selectedTypeId.value || searchQuery.value);

const historyHref = computed(() => (route().has('student.olympiad-history') ? route('student.olympiad-history') : null));

const chipClass = (active) => [
    'focus-ring inline-flex h-10 shrink-0 items-center whitespace-nowrap rounded-full px-4 text-sm font-medium transition-colors duration-150',
    active
        ? 'bg-brand-50 text-brand ring-1 ring-inset ring-brand-100'
        : 'bg-surface text-fg-muted ring-1 ring-inset ring-border hover:bg-surface-2 hover:text-fg',
];

const actionLabel = (olympiad) => {
    if (!olympiad.is_registered) return "Ko'rish";
    return olympiad.registration_status === 'confirmed' ? 'Ochish' : "To'lash";
};
</script>

<template>
    <StudentLayout title="Olimpiadalar">
        <Head title="Olimpiadalar" />

        <PageHeader title="Olimpiadalar" description="O'z bilimingizni sinab ko'ring va sovrinlar yutib oling">
            <template v-if="historyHref" #actions>
                <Button variant="secondary" :href="historyHref" :icon-left="ClipboardDocumentListIcon">
                    Ishtirok tarixi
                </Button>
            </template>
        </PageHeader>

        <div class="space-y-6">
            <!-- Filters -->
            <div class="space-y-3">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <Input v-model="searchQuery" type="search" placeholder="Olimpiada nomi bo'yicha qidirish"
                        :leading-icon="MagnifyingGlassIcon" aria-label="Olimpiadalarni qidirish" class="sm:w-80">
                        <template v-if="isLoading" #trailing>
                            <Spinner size="h-4 w-4" label="Yuklanmoqda" />
                        </template>
                    </Input>
                    <Button v-if="hasActiveFilters" variant="ghost" size="sm" :icon-left="XMarkIcon"
                        class="self-start sm:self-auto" @click="clearFilters">
                        Filtrlarni tozalash
                    </Button>
                </div>

                <div class="-mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0 scrollbar-thin" role="group"
                    aria-label="Olimpiada turi">
                    <div class="flex gap-2 pb-1 sm:flex-wrap sm:pb-0">
                        <button type="button" :class="chipClass(!selectedTypeId)" :aria-pressed="!selectedTypeId"
                            @click="filterByType('')">
                            Barchasi
                        </button>
                        <button v-for="type in types" :key="type.id" type="button"
                            :class="chipClass(selectedTypeId === type.id)" :aria-pressed="selectedTypeId === type.id"
                            @click="filterByType(type.id)">
                            {{ type.display_name }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Loading Skeleton -->
            <div v-if="isLoading && olympiads.length === 0" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
                aria-busy="true">
                <div v-for="n in 6" :key="n" class="overflow-hidden rounded-lg border border-border bg-surface">
                    <Skeleton class="h-40 w-full rounded-none" />
                    <div class="space-y-3 p-5">
                        <Skeleton class="h-5 w-3/4" />
                        <Skeleton variant="text" :lines="2" />
                        <div class="flex items-center justify-between border-t border-border pt-4">
                            <Skeleton class="h-5 w-20" />
                            <Skeleton class="h-5 w-16" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Olympiad Cards -->
            <div v-else-if="olympiads.length > 0" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <Card v-for="olympiad in olympiads" :key="olympiad.id" padding="none"
                    :href="route('student.olympiads.show', olympiad.slug)" class="group flex flex-col overflow-hidden">
                    <!-- Cover -->
                    <div class="relative h-40 overflow-hidden rounded-t-lg bg-surface-2">
                        <img v-if="olympiad.cover_image" :src="olympiad.cover_image" :alt="olympiad.title" loading="lazy"
                            class="h-full w-full object-cover">
                        <div v-else class="flex h-full items-center justify-center text-fg-subtle">
                            <TrophyIcon class="h-10 w-10" aria-hidden="true" />
                        </div>

                        <div class="absolute inset-x-0 top-0 flex items-start justify-between gap-2 p-3">
                            <Badge v-if="olympiad.type" variant="neutral">{{ olympiad.type }}</Badge>
                            <Badge :variant="getStatusBadge(olympiad.status).variant" dot class="ml-auto">
                                {{ getStatusBadge(olympiad.status).label }}
                            </Badge>
                        </div>

                        <div v-if="olympiad.is_registered" class="absolute bottom-3 left-3">
                            <Badge variant="success" :icon="CheckCircleIcon">Ro'yxatda</Badge>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="flex flex-1 flex-col p-5">
                        <h3 class="line-clamp-2 text-base font-semibold text-fg group-hover:text-brand">
                            {{ olympiad.title }}
                        </h3>

                        <p v-if="olympiad.short_description || olympiad.description"
                            class="mt-1.5 line-clamp-2 text-sm text-fg-muted">
                            {{ olympiad.short_description || olympiad.description }}
                        </p>

                        <ul class="mb-5 mt-4 flex flex-wrap gap-x-4 gap-y-2 text-13 text-fg-subtle">
                            <li v-if="olympiad.stage" class="inline-flex items-center gap-1.5">
                                <ChartBarIcon class="h-4 w-4" aria-hidden="true" />
                                {{ olympiad.stage }}
                            </li>
                            <li v-if="olympiad.total_duration" class="inline-flex items-center gap-1.5 tabular-nums">
                                <ClockIcon class="h-4 w-4" aria-hidden="true" />
                                {{ olympiad.total_duration }} daqiqa
                            </li>
                            <li v-if="olympiad.olympiad_start_at" class="inline-flex items-center gap-1.5 tabular-nums">
                                <CalendarDaysIcon class="h-4 w-4" aria-hidden="true" />
                                {{ formatDate(olympiad.olympiad_start_at) }}
                            </li>
                        </ul>

                        <div class="mt-auto flex items-center justify-between gap-3 border-t border-border pt-4">
                            <span class="text-base font-semibold tabular-nums"
                                :class="olympiad.registration_fee ? 'text-fg' : 'text-success'">
                                {{ formatPrice(olympiad.registration_fee) }}
                            </span>
                            <span class="inline-flex items-center gap-1 text-sm font-medium text-brand">
                                {{ actionLabel(olympiad) }}
                                <ArrowRightIcon class="h-4 w-4 transition-transform duration-150 group-hover:translate-x-0.5"
                                    aria-hidden="true" />
                            </span>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Empty State -->
            <Card v-else>
                <EmptyState :icon="hasActiveFilters ? MagnifyingGlassIcon : TrophyIcon" title="Olimpiadalar topilmadi"
                    :description="searchQuery ? 'Qidiruv bo\'yicha natija topilmadi. Boshqa so\'z bilan urinib ko\'ring.' : 'Hozircha bu toifada olimpiadalar mavjud emas.'">
                    <Button v-if="hasActiveFilters" variant="secondary" :icon-left="XMarkIcon" @click="clearFilters">
                        Filtrlarni tozalash
                    </Button>
                </EmptyState>
            </Card>
        </div>
    </StudentLayout>
</template>
