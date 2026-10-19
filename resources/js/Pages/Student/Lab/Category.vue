<script setup>
import { Head } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { computed, ref } from 'vue';
import { PageHeader, Card, StatCard, Badge, EmptyState, Tabs } from '@/Components/UI';
import {
    AcademicCapIcon, BeakerIcon, BoltIcon, BuildingLibraryIcon, BuildingOfficeIcon, CalendarIcon, CheckCircleIcon,
    ClockIcon, Cog6ToothIcon, CubeTransparentIcon, EyeIcon, FireIcon, FlagIcon, LightBulbIcon, MapIcon,
    SignalIcon, SparklesIcon, StarIcon, TrophyIcon,
} from '@heroicons/vue/24/outline';
import {
    CheckCircleIcon as CheckCircleMini, ChevronRightIcon, ClockIcon as ClockMini, SparklesIcon as SparklesMini,
} from '@heroicons/vue/20/solid';

const props = defineProps({
    category: Object,
    experiments: Array,
});

// Category icons are stored as heroicon-style names ("cog", "bolt" …); emoji are shown as content.
const ICONS = {
    cog: Cog6ToothIcon, fire: FireIcon, bolt: BoltIcon, eye: EyeIcon, signal: SignalIcon, atom: CubeTransparentIcon,
    beaker: BeakerIcon, 'light-bulb': LightBulbIcon, calendar: CalendarIcon, star: StarIcon, clock: ClockIcon,
    trophy: TrophyIcon, 'academic-cap': AcademicCapIcon, sparkles: SparklesIcon, map: MapIcon, flag: FlagIcon,
    'building-office': BuildingOfficeIcon, school: BuildingLibraryIcon,
};
const isIconName = (v) => typeof v === 'string' && /^[a-z0-9-]+$/i.test(v);
const categoryIcon = computed(() => (isIconName(props.category?.icon) && ICONS[props.category.icon]) || BeakerIcon);
const categoryEmoji = computed(() => (props.category?.icon && !isIconName(props.category.icon) ? props.category.icon : null));

const difficultyVariant = { easy: 'success', medium: 'warning', hard: 'danger' };

const progressPct = computed(() => {
    const p = props.category?.progress;
    if (!p) return null;
    return Math.round(Number(p.percentage ?? p.completion_percent ?? 0) || 0);
});
const avgRating = computed(() => {
    const r = Number(props.category?.avg_rating);
    return r > 0 ? r.toFixed(1) : null;
});

// Client-side filter (the buttons existed before but did nothing)
const filter = ref('all');
const list = computed(() => props.experiments || []);
const filters = {
    all: () => true,
    free: (e) => !!e.is_free,
    easy: (e) => e.difficulty === 'easy',
};
const filterTabs = computed(() => [
    { key: 'all', label: 'Barchasi', count: list.value.length },
    { key: 'free', label: 'Bepul', count: list.value.filter(filters.free).length },
    { key: 'easy', label: 'Oson', count: list.value.filter(filters.easy).length },
]);
// Keep the original sequence number of each experiment even when filtered
const numbered = computed(() => list.value.map((exp, index) => ({ exp, number: index + 1 })));
const visible = computed(() => numbered.value.filter(({ exp }) => filters[filter.value](exp)));

const bestScore = (p) => Math.round(Number(p?.best_score ?? p?.best_percentage ?? 0) || 0);
</script>

<template>

    <Head :title="`${category.name} - Virtual laboratoriya`" />

    <StudentLayout>
        <PageHeader :title="category.name" :description="category.description"
            :breadcrumbs="[{ label: 'Laboratoriya', href: route('student.lab.index') }, { label: category.name }]">
            <template #title>
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-physics-50 text-physics">
                        <span v-if="categoryEmoji" class="text-lg leading-none" aria-hidden="true">{{ categoryEmoji }}</span>
                        <component :is="categoryIcon" v-else class="h-5 w-5" aria-hidden="true" />
                    </span>
                    <h1 class="text-2xl font-semibold tracking-tight text-fg">{{ category.name }}</h1>
                </div>
            </template>
        </PageHeader>

        <div class="space-y-6">
            <!-- Stats -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard label="Tajribalar" :value="Number(category.total_experiments || experiments?.length || 0)"
                    :icon="BeakerIcon" tone="physics" />
                <StatCard v-if="category.progress" label="Yakunlangan"
                    :value="`${category.progress.completed ?? 0} / ${category.progress.total ?? (experiments?.length || 0)}`"
                    :icon="CheckCircleIcon" tone="success" />
                <StatCard v-if="progressPct !== null" label="Tugallandi" :value="progressPct" unit="%"
                    :icon="TrophyIcon" tone="brand" />
                <StatCard v-if="avgRating" label="Reyting" :value="avgRating" :icon="StarIcon" tone="warning" />
            </div>

            <!-- Experiments -->
            <section aria-labelledby="category-experiments-title" class="space-y-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <h2 id="category-experiments-title" class="text-lg font-semibold text-fg">Tajribalar ro'yxati</h2>
                    <Tabs v-model="filter" :tabs="filterTabs" variant="pills" label="Tajribalar filtri" />
                </div>

                <div v-if="visible.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <Card v-for="{ exp, number } in visible" :key="exp.id" :href="route('student.lab.show', exp.slug)"
                        padding="none" interactive>
                        <div class="flex h-full flex-col p-5">
                            <div class="flex items-start justify-between gap-3">
                                <span v-if="exp.user_progress?.status === 'completed'"
                                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-success-50 text-success">
                                    <CheckCircleMini class="h-5 w-5" aria-hidden="true" />
                                    <span class="sr-only">Yakunlangan</span>
                                </span>
                                <span v-else
                                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-surface-2 text-sm font-semibold text-fg-muted tabular-nums">
                                    {{ number }}
                                </span>
                                <Badge v-if="exp.is_free" variant="success" size="sm">Bepul</Badge>
                                <Badge v-else variant="warning" size="sm">Premium</Badge>
                            </div>

                            <h3 class="mt-4 text-base font-semibold text-fg">{{ exp.title }}</h3>
                            <p v-if="exp.short_description" class="mt-1 line-clamp-2 text-sm text-fg-muted">
                                {{ exp.short_description }}
                            </p>

                            <div class="mt-auto flex flex-wrap items-center gap-x-3 gap-y-2 pt-4 text-13 text-fg-subtle">
                                <span v-if="exp.estimated_duration" class="inline-flex items-center gap-1 tabular-nums">
                                    <ClockMini class="h-4 w-4" aria-hidden="true" />
                                    {{ exp.estimated_duration }} daqiqa
                                </span>
                                <Badge v-if="exp.difficulty_label" :variant="difficultyVariant[exp.difficulty] || 'neutral'" size="sm">
                                    {{ exp.difficulty_label }}
                                </Badge>
                                <span v-if="exp.grade_level" class="tabular-nums">{{ exp.grade_level }}-sinf</span>
                                <span v-if="exp.xp_reward" class="inline-flex items-center gap-1 font-medium text-fg-muted tabular-nums">
                                    <SparklesMini class="h-4 w-4 text-brand" aria-hidden="true" />+{{ exp.xp_reward }} XP
                                </span>
                            </div>

                            <div class="mt-4 flex items-center justify-between gap-3 border-t border-border pt-3 text-sm">
                                <template v-if="exp.user_progress?.status === 'completed'">
                                    <span class="text-fg-subtle">Eng yaxshi natija</span>
                                    <span class="font-semibold text-success tabular-nums">{{ bestScore(exp.user_progress) }}%</span>
                                </template>
                                <template v-else-if="exp.user_progress?.status === 'in_progress' || exp.user_progress?.status === 'paused'">
                                    <Badge variant="info" size="sm" dot>Davom etmoqda</Badge>
                                    <span class="inline-flex items-center gap-0.5 font-medium text-brand">
                                        Davom etish
                                        <ChevronRightIcon class="h-4 w-4" aria-hidden="true" />
                                    </span>
                                </template>
                                <template v-else>
                                    <span class="text-fg-subtle">Hali boshlanmagan</span>
                                    <span class="inline-flex items-center gap-0.5 font-medium text-brand">
                                        Boshlash
                                        <ChevronRightIcon class="h-4 w-4" aria-hidden="true" />
                                    </span>
                                </template>
                            </div>
                        </div>
                    </Card>
                </div>

                <Card v-else>
                    <EmptyState :icon="BeakerIcon"
                        :title="list.length ? 'Mos tajriba topilmadi' : 'Bu bo\'limda hali tajriba yo\'q'"
                        :description="list.length ? 'Boshqa filtrni tanlab ko\'ring.' : 'Tajribalar tez orada qo\'shiladi.'" />
                </Card>
            </section>
        </div>
    </StudentLayout>
</template>
