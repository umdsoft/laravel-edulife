<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { computed } from 'vue';
import {
    PageHeader, Card, StatCard, Badge, Button, EmptyState, ProgressBar, Avatar,
} from '@/Components/UI';
import {
    AcademicCapIcon, ArrowTrendingUpIcon, BeakerIcon, BoltIcon, BuildingLibraryIcon, BuildingOfficeIcon,
    CalendarIcon, ChartBarIcon, CheckBadgeIcon, ClockIcon, Cog6ToothIcon, CubeTransparentIcon, EyeIcon,
    FireIcon, FlagIcon, LightBulbIcon, MapIcon, PlayIcon, SignalIcon, SparklesIcon, Squares2X2Icon, StarIcon,
    TrophyIcon,
} from '@heroicons/vue/24/outline';
import {
    ChevronRightIcon, ClockIcon as ClockMini, SparklesIcon as SparklesMini, TrophyIcon as TrophyMini,
} from '@heroicons/vue/20/solid';

const props = defineProps({
    categories: Array,
    featuredExperiments: Array,
    freeExperiments: Array,
    userProgress: Object,
    recentAttempts: Array,
    leaderboard: Array,
});

const page = usePage();
const user = computed(() => page.props.auth?.user);

// Difficulty → Badge variant
const difficultyVariant = { easy: 'success', medium: 'warning', hard: 'danger' };

// Category / badge icons are stored as heroicon-style names ("cog", "bolt" …); emoji are shown as content.
const ICONS = {
    cog: Cog6ToothIcon, fire: FireIcon, bolt: BoltIcon, eye: EyeIcon, signal: SignalIcon, atom: CubeTransparentIcon,
    beaker: BeakerIcon, 'light-bulb': LightBulbIcon, calendar: CalendarIcon, star: StarIcon, clock: ClockIcon,
    trophy: TrophyIcon, 'academic-cap': AcademicCapIcon, sparkles: SparklesIcon, map: MapIcon, flag: FlagIcon,
    'building-office': BuildingOfficeIcon, school: BuildingLibraryIcon,
};
const isIconName = (v) => typeof v === 'string' && /^[a-z0-9-]+$/i.test(v);
const iconFor = (name) => (isIconName(name) && ICONS[name]) || BeakerIcon;
const emojiOf = (v) => (v && !isIconName(v) ? v : null);

const pct = (p) => Math.round(Number(p?.percentage ?? p?.completion_percent ?? 0) || 0);
const fmt = (n) => new Intl.NumberFormat('ru-RU').format(Number(n) || 0);

const experimentSections = computed(() => [
    {
        key: 'featured',
        title: 'Tavsiya etilgan tajribalar',
        description: 'Boshlash uchun eng yaxshi tajribalar',
        items: props.featuredExperiments || [],
    },
    {
        key: 'free',
        title: 'Bepul tajribalar',
        description: "Obunasiz ham sinab ko'ring",
        items: props.freeExperiments || [],
    },
].filter((s) => s.items.length));

const topLeaders = computed(() => (props.leaderboard || []).slice(0, 5));

const medalClass = (rank) => ({
    1: 'bg-warning-50 text-warning ring-1 ring-warning-100',
    2: 'bg-surface-2 text-fg-muted ring-1 ring-border',
    3: 'bg-surface-2 text-warning ring-1 ring-border',
}[rank] || 'text-fg-subtle');

const attemptStatus = {
    completed: { label: 'Yakunlandi', variant: 'success' },
    in_progress: { label: 'Davom etmoqda', variant: 'info' },
    paused: { label: "To'xtatilgan", variant: 'warning' },
    abandoned: { label: 'Bekor qilingan', variant: 'danger' },
    expired: { label: "Muddati o'tgan", variant: 'neutral' },
};

const quickLinks = [
    { key: 'badges', route: 'student.lab.badges', title: 'Badgelar', description: "Yutuqlaringizni ko'ring", icon: CheckBadgeIcon },
    { key: 'leaderboard', route: 'student.lab.leaderboard', title: 'Reyting', description: "O'rningizni bilib oling", icon: ChartBarIcon },
    { key: 'progress', route: 'student.lab.progress', title: 'Statistika', description: 'Rivojlanishingiz', icon: ArrowTrendingUpIcon },
];
</script>

<template>

    <Head title="Virtual fizika laboratoriyasi" />

    <StudentLayout>
        <PageHeader title="Virtual fizika laboratoriyasi"
            description="Interaktiv simulyatsiyalar orqali fizika qonunlarini amaliy o'rganing: tajriba o'tkazing, o'lchov oling, formulalarni sinab ko'ring.">
            <template #actions>
                <Button variant="secondary" :icon-left="ChartBarIcon" :href="route('student.lab.leaderboard')">Reyting</Button>
                <Button :icon-left="PlayIcon" :href="route('student.lab.category', 'mechanics')">Tajribalarni boshlash</Button>
            </template>
        </PageHeader>

        <div class="space-y-6">
            <!-- Stats -->
            <div v-if="userProgress" class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard label="Daraja" :value="userProgress.level" :icon="AcademicCapIcon" tone="info"
                    :hint="userProgress.level_title" :href="route('student.lab.progress')">
                    <ProgressBar class="mt-1 w-full" :value="Number(userProgress.level_progress) || 0" size="xs" />
                </StatCard>
                <StatCard label="Jami XP" :value="Number(userProgress.total_xp) || 0" unit="XP" :icon="SparklesIcon" tone="brand" />
                <StatCard label="Yakunlangan tajribalar" :value="Number(userProgress.completed_experiments) || 0"
                    :icon="BeakerIcon" tone="physics" :hint="`${userProgress.badges_count || 0} ta badge`" />
                <StatCard label="Streak" :value="Number(userProgress.current_streak) || 0" unit="kun" :icon="FireIcon"
                    tone="warning" />
            </div>
            <div v-else class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard label="Bo'limlar" :value="categories?.length || 0" :icon="Squares2X2Icon" tone="physics" />
                <StatCard label="Bepul tajribalar" :value="freeExperiments?.length || 0" :icon="BeakerIcon" tone="success" />
            </div>

            <!-- Categories -->
            <section id="categories" aria-labelledby="lab-categories-title" class="space-y-4">
                <div>
                    <h2 id="lab-categories-title" class="text-lg font-semibold text-fg">Fizika bo'limlari</h2>
                    <p class="mt-0.5 text-sm text-fg-muted">O'zingizga mos bo'limni tanlang</p>
                </div>

                <div v-if="categories?.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <Card v-for="cat in categories" :key="cat.id" :href="route('student.lab.category', cat.slug)"
                        padding="none" interactive>
                        <div class="flex h-full flex-col p-5">
                            <div class="flex items-start gap-3">
                                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-physics-50 text-physics">
                                    <span v-if="emojiOf(cat.icon)" class="text-lg leading-none" aria-hidden="true">{{ emojiOf(cat.icon) }}</span>
                                    <component :is="iconFor(cat.icon)" v-else class="h-5 w-5" aria-hidden="true" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-base font-semibold text-fg">{{ cat.name }}</h3>
                                    <p class="mt-0.5 text-13 text-fg-subtle tabular-nums">{{ cat.experiments_count || 0 }} tajriba</p>
                                </div>
                                <ChevronRightIcon class="mt-0.5 h-5 w-5 shrink-0 text-fg-subtle" aria-hidden="true" />
                            </div>
                            <p v-if="cat.description" class="mt-3 line-clamp-2 text-sm text-fg-muted">{{ cat.description }}</p>
                            <div v-if="cat.progress" class="mt-auto pt-4">
                                <div class="mb-1.5 flex items-center justify-between text-xs">
                                    <span class="text-fg-subtle">Bajarildi</span>
                                    <span class="font-medium text-fg tabular-nums">{{ pct(cat.progress) }}%</span>
                                </div>
                                <ProgressBar :value="pct(cat.progress)" tone="physics" size="xs" />
                            </div>
                        </div>
                    </Card>
                </div>
                <Card v-else>
                    <EmptyState :icon="Squares2X2Icon" title="Bo'limlar hali qo'shilmagan"
                        description="Laboratoriya bo'limlari tez orada paydo bo'ladi." />
                </Card>
            </section>

            <!-- Featured / free experiments -->
            <section v-for="section in experimentSections" :key="section.key"
                :aria-labelledby="`lab-${section.key}-title`" class="space-y-4">
                <div>
                    <h2 :id="`lab-${section.key}-title`" class="text-lg font-semibold text-fg">{{ section.title }}</h2>
                    <p class="mt-0.5 text-sm text-fg-muted">{{ section.description }}</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <Card v-for="exp in section.items" :key="`${section.key}-${exp.id}`"
                        :href="route('student.lab.show', exp.slug)" padding="none" interactive>
                        <div class="flex h-full flex-col p-5">
                            <div class="flex items-start justify-between gap-3">
                                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-physics-50 text-physics">
                                    <BeakerIcon class="h-5 w-5" aria-hidden="true" />
                                </span>
                                <Badge v-if="exp.is_free" variant="success" size="sm">Bepul</Badge>
                                <Badge v-else variant="warning" size="sm">Premium</Badge>
                            </div>

                            <p v-if="exp.category?.name" class="mt-4 text-xs font-medium uppercase tracking-wide text-physics">
                                {{ exp.category.name }}
                            </p>
                            <h3 class="mt-1 text-base font-semibold text-fg">{{ exp.title }}</h3>
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

                            <div v-if="exp.user_progress && exp.user_progress.status !== 'not_started'"
                                class="mt-3 border-t border-border pt-3">
                                <Badge v-if="exp.user_progress.status === 'completed'" variant="success" size="sm" dot>
                                    Yakunlangan · {{ Math.round(Number(exp.user_progress.best_score ?? exp.user_progress.completion_percent) || 0) }}%
                                </Badge>
                                <Badge v-else variant="info" size="sm" dot>Davom etmoqda</Badge>
                            </div>
                        </div>
                    </Card>
                </div>
            </section>

            <!-- Recent attempts + weekly leaderboard -->
            <div class="grid gap-6 lg:grid-cols-2">
                <Card title="So'nggi urinishlar" padding="none">
                    <template #actions>
                        <Button size="sm" variant="ghost" :icon-right="ChevronRightIcon" :href="route('student.lab.progress')">
                            Barchasi
                        </Button>
                    </template>

                    <ul v-if="recentAttempts?.length" class="divide-y divide-border">
                        <li v-for="attempt in recentAttempts" :key="attempt.id"
                            class="flex flex-wrap items-center gap-x-4 gap-y-2 px-5 py-3.5 sm:px-6">
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium text-fg">{{ attempt.experiment.title }}</p>
                                <p class="truncate text-13 text-fg-subtle">{{ attempt.experiment.category }}</p>
                            </div>
                            <div class="flex items-center gap-4 text-right">
                                <div>
                                    <p class="font-semibold text-fg tabular-nums">{{ attempt.percentage ?? 0 }}%</p>
                                    <p class="text-xs text-fg-subtle">Ball</p>
                                </div>
                                <div class="hidden sm:block">
                                    <p class="font-medium text-fg-muted tabular-nums">{{ attempt.time_spent || '—' }}</p>
                                    <p class="text-xs text-fg-subtle">Vaqt</p>
                                </div>
                                <Button v-if="attempt.can_continue" size="sm" variant="soft"
                                    :href="route('student.lab.simulate', attempt.experiment.slug)">
                                    Davom etish
                                </Button>
                                <Badge v-else :variant="attemptStatus[attempt.status]?.variant || 'neutral'" size="sm">
                                    {{ attemptStatus[attempt.status]?.label || attempt.status }}
                                </Badge>
                            </div>
                        </li>
                    </ul>
                    <EmptyState v-else :icon="ClockIcon" size="sm" title="Hali urinishlar yo'q"
                        description="Birinchi tajribangizni boshlang — natijalar shu yerda chiqadi." />
                </Card>

                <Card title="Haftalik reyting" padding="none">
                    <template #actions>
                        <Button size="sm" variant="ghost" :icon-right="ChevronRightIcon" :href="route('student.lab.leaderboard')">
                            To'liq reyting
                        </Button>
                    </template>

                    <ol v-if="topLeaders.length" class="divide-y divide-border">
                        <li v-for="entry in topLeaders" :key="entry.user.id" class="flex items-center gap-3 px-5 py-3 sm:px-6"
                            :class="entry.user.id === user?.id ? 'bg-brand-50' : ''">
                            <span :class="['inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold tabular-nums', medalClass(entry.rank)]">
                                <TrophyMini v-if="entry.rank <= 3" class="h-4 w-4" aria-hidden="true" />
                                <template v-else>{{ entry.rank }}</template>
                                <span v-if="entry.rank <= 3" class="sr-only">{{ entry.rank }}-o'rin</span>
                            </span>
                            <Avatar :src="entry.user.avatar" :name="entry.user.name" size="sm" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium text-fg">{{ entry.user.name }}</p>
                                <p class="text-13 text-fg-subtle tabular-nums">{{ entry.user.level }}-daraja</p>
                            </div>
                            <span class="shrink-0 font-semibold text-fg tabular-nums">{{ fmt(entry.stats.total_xp) }} XP</span>
                        </li>
                    </ol>
                    <EmptyState v-else :icon="TrophyIcon" size="sm" title="Reyting hali shakllanmagan"
                        description="Bu hafta tajriba o'tkazgan birinchi o'quvchi bo'ling." />
                </Card>
            </div>

            <!-- Quick links -->
            <nav aria-label="Laboratoriya bo'limlari" class="grid gap-4 sm:grid-cols-3">
                <Card v-for="item in quickLinks" :key="item.key" :href="route(item.route)" padding="none" interactive>
                    <div class="flex items-center gap-3 p-5">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-surface-2 text-fg-muted">
                            <component :is="item.icon" class="h-5 w-5" aria-hidden="true" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-fg">{{ item.title }}</p>
                            <p class="text-13 text-fg-subtle">{{ item.description }}</p>
                        </div>
                        <ChevronRightIcon class="h-5 w-5 shrink-0 text-fg-subtle" aria-hidden="true" />
                    </div>
                </Card>
            </nav>
        </div>
    </StudentLayout>
</template>
