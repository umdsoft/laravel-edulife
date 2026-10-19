<script setup>
import { Head, Link } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { computed } from 'vue';
import {
    PageHeader, Card, StatCard, Badge, Button, EmptyState, ProgressBar, ProgressRing, Table,
} from '@/Components/UI';
import {
    AcademicCapIcon, BeakerIcon, BoltIcon, BuildingLibraryIcon, BuildingOfficeIcon, CalendarIcon, CheckBadgeIcon,
    ClockIcon, Cog6ToothIcon, CubeTransparentIcon, EyeIcon, FireIcon, FlagIcon, LightBulbIcon, MapIcon, SignalIcon,
    SparklesIcon, Squares2X2Icon, StarIcon, TrophyIcon,
} from '@heroicons/vue/24/outline';
import { ChevronRightIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    stats: Object,
    categories: Array,
    recentAttempts: Array,
    earnedBadges: Array,
    skills: Object,
    bestExperiments: Array,
});

// Category / badge icons are stored as heroicon-style names ("cog", "trophy" …); emoji are shown as content.
const ICONS = {
    cog: Cog6ToothIcon, fire: FireIcon, bolt: BoltIcon, eye: EyeIcon, signal: SignalIcon, atom: CubeTransparentIcon,
    beaker: BeakerIcon, 'light-bulb': LightBulbIcon, calendar: CalendarIcon, star: StarIcon, clock: ClockIcon,
    trophy: TrophyIcon, 'academic-cap': AcademicCapIcon, sparkles: SparklesIcon, map: MapIcon, flag: FlagIcon,
    'building-office': BuildingOfficeIcon, school: BuildingLibraryIcon,
};
const isIconName = (v) => typeof v === 'string' && /^[a-z0-9-]+$/i.test(v);
const iconFor = (name, fallback = BeakerIcon) => (isIconName(name) && ICONS[name]) || fallback;
const emojiOf = (v) => (v && !isIconName(v) ? v : null);

const num = (v) => Number(v) || 0;
const pct = (p) => Math.round(Number(p?.percentage ?? p?.completion_percent ?? 0) || 0);
const levelProgress = computed(() => Math.round(num(props.stats?.level_progress)));

const statusVariant = {
    completed: 'success', in_progress: 'info', paused: 'warning', abandoned: 'danger', expired: 'neutral',
};

const columns = [
    { key: 'experiment', label: 'Tajriba' },
    { key: 'grade', label: 'Natija' },
    { key: 'percentage', label: 'Ball', numeric: true },
    { key: 'xp_earned', label: 'XP', numeric: true, hideBelow: 'sm' },
    { key: 'created_at', label: 'Sana', align: 'right', hideBelow: 'md', class: 'whitespace-nowrap tabular-nums text-fg-muted' },
];
</script>

<template>

    <Head title="Mening progressim - Virtual laboratoriya" />

    <StudentLayout>
        <PageHeader title="Mening progressim" description="Laboratoriyadagi yutuqlaringiz va natijalaringiz"
            :breadcrumbs="[{ label: 'Laboratoriya', href: route('student.lab.index') }, { label: 'Progress' }]" />

        <div class="space-y-6">
            <!-- Level -->
            <Card>
                <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                    <ProgressRing :value="levelProgress" :size="88" :stroke="7" label="Daraja progressi">
                        <div class="text-center leading-tight">
                            <p class="text-2xl font-semibold text-fg tabular-nums">{{ stats.level }}</p>
                            <p class="text-[11px] text-fg-subtle">daraja</p>
                        </div>
                    </ProgressRing>
                    <div class="min-w-0 flex-1">
                        <p class="text-13 text-fg-muted">Joriy daraja</p>
                        <p class="text-lg font-semibold text-fg">{{ stats.level_title || `${stats.level}-daraja` }}</p>
                        <ProgressBar class="mt-3" :value="levelProgress" size="sm" />
                        <p class="mt-1.5 text-13 text-fg-subtle tabular-nums">
                            <template v-if="stats.xp_to_next_level !== undefined && stats.xp_to_next_level !== null">
                                {{ stats.xp_to_next_level }} XP keyingi darajagacha
                            </template>
                            <template v-else>{{ levelProgress }}% — keyingi darajagacha</template>
                        </p>
                    </div>
                </div>
            </Card>

            <!-- Stats -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard label="Jami XP" :value="num(stats.total_xp)" unit="XP" :icon="SparklesIcon" tone="brand"
                    :hint="stats.weekly_xp ? `Bu hafta: +${stats.weekly_xp}` : null" />
                <StatCard label="Yakunlangan tajribalar" :value="num(stats.completed_experiments)" :icon="BeakerIcon"
                    tone="physics" :hint="stats.perfect_scores ? `${stats.perfect_scores} ta a'lo natija` : null" />
                <StatCard label="Kunlik streak" :value="num(stats.current_streak)" unit="kun" :icon="FireIcon" tone="warning"
                    :hint="stats.longest_streak ? `Eng uzun: ${stats.longest_streak} kun` : null" />
                <StatCard label="Badgelar" :value="num(stats.badges_count)" :icon="CheckBadgeIcon" tone="success"
                    :href="route('student.lab.badges')" />
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Categories -->
                <Card title="Bo'limlar bo'yicha" padding="none" class="lg:col-span-2">
                    <ul v-if="categories?.length" class="divide-y divide-border">
                        <li v-for="cat in categories" :key="cat.id">
                            <Link :href="route('student.lab.category', cat.slug)"
                                class="focus-ring flex items-center gap-3 px-5 py-3.5 hover:bg-surface-2/60 sm:px-6">
                                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-physics-50 text-physics">
                                    <span v-if="emojiOf(cat.icon)" class="text-base leading-none" aria-hidden="true">{{ emojiOf(cat.icon) }}</span>
                                    <component :is="iconFor(cat.icon)" v-else class="h-5 w-5" aria-hidden="true" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="mb-1.5 flex items-center justify-between gap-3 text-sm">
                                        <span class="truncate font-medium text-fg">{{ cat.name }}</span>
                                        <span class="shrink-0 text-fg-muted tabular-nums">
                                            <span v-if="cat.progress?.total" class="mr-2 text-fg-subtle">{{ cat.progress.completed || 0 }}/{{ cat.progress.total }}</span>
                                            <span class="font-semibold text-fg">{{ pct(cat.progress) }}%</span>
                                        </span>
                                    </div>
                                    <ProgressBar :value="pct(cat.progress)" tone="physics" size="xs" />
                                </div>
                                <ChevronRightIcon class="h-5 w-5 shrink-0 text-fg-subtle" aria-hidden="true" />
                            </Link>
                        </li>
                    </ul>
                    <EmptyState v-else :icon="Squares2X2Icon" size="sm" title="Bo'limlar yo'q"
                        description="Laboratoriya bo'limlari hali qo'shilmagan." />
                </Card>

                <!-- Earned badges -->
                <Card title="Qo'lga kiritilgan badgelar">
                    <template #actions>
                        <Button size="sm" variant="ghost" :href="route('student.lab.badges')">Barchasi</Button>
                    </template>
                    <ul v-if="earnedBadges?.length" class="grid grid-cols-2 gap-2">
                        <li v-for="badge in earnedBadges" :key="badge.id"
                            class="flex min-w-0 items-center gap-2 rounded-md border border-border px-2.5 py-2">
                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-success-50 text-success">
                                <span v-if="emojiOf(badge.icon)" class="text-sm leading-none" aria-hidden="true">{{ emojiOf(badge.icon) }}</span>
                                <component :is="iconFor(badge.icon, TrophyIcon)" v-else class="h-4 w-4" aria-hidden="true" />
                            </span>
                            <span class="truncate text-13 font-medium text-fg">{{ badge.name }}</span>
                        </li>
                    </ul>
                    <EmptyState v-else :icon="TrophyIcon" size="sm" title="Hali badge yo'q"
                        description="Tajribalarni yakunlab, birinchi badgeni qo'lga kiriting." />
                </Card>
            </div>

            <!-- Recent attempts -->
            <Card title="So'nggi urinishlar" padding="none">
                <Table :columns="columns" :rows="recentAttempts || []" :bordered="false" caption="So'nggi urinishlar"
                    empty-title="Hali urinishlar yo'q" empty-description="Birinchi tajribangizni boshlang.">
                    <template #cell-experiment="{ row }">
                        <Link :href="route('student.lab.show', row.experiment.slug)"
                            class="focus-ring block min-w-0 rounded-sm hover:text-brand">
                            <span class="block max-w-[14rem] truncate font-medium text-fg sm:max-w-xs">{{ row.experiment.title }}</span>
                            <span class="block truncate text-13 text-fg-subtle">{{ row.experiment.category }}</span>
                        </Link>
                    </template>
                    <template #cell-grade="{ row }">
                        <Badge :variant="statusVariant[row.status] || 'neutral'" size="sm">
                            {{ row.grade || row.status_label || row.status }}
                        </Badge>
                    </template>
                    <template #cell-percentage="{ row }">
                        <span class="font-semibold text-fg">{{ row.percentage ?? 0 }}%</span>
                    </template>
                    <template #cell-xp_earned="{ row }">
                        <span class="text-fg-muted">+{{ row.xp_earned || 0 }}</span>
                    </template>
                </Table>
            </Card>
        </div>
    </StudentLayout>
</template>
