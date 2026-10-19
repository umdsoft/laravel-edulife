<script setup>
import { Head, Link } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { computed } from 'vue';
import { routeHas, routePath } from '@/Utils/nav';
import { PageHeader, Card, Badge, Button, EmptyState, ProgressBar, Avatar } from '@/Components/UI';
import {
    AcademicCapIcon, BeakerIcon, BoltIcon, BuildingLibraryIcon, BuildingOfficeIcon, CalendarIcon,
    ChartBarIcon, ChatBubbleLeftRightIcon, CircleStackIcon, ClockIcon, Cog6ToothIcon, CubeTransparentIcon, EyeIcon,
    FireIcon, FlagIcon, LightBulbIcon, LockClosedIcon, MapIcon, PlayCircleIcon, PlayIcon, SignalIcon, SparklesIcon,
    StarIcon, TrophyIcon, WrenchScrewdriverIcon,
} from '@heroicons/vue/24/outline';
import { CheckCircleIcon as CheckCircleMini, StarIcon as StarMini } from '@heroicons/vue/20/solid';

const props = defineProps({
    experiment: Object,
    isAccessible: Boolean,
    userProgress: Object,
    prerequisites: Array,
    ratings: Array,
    ratingsSummary: Object,
});

const difficultyVariant = { easy: 'success', medium: 'warning', hard: 'danger' };
const rarity = {
    common: { label: 'Oddiy', variant: 'neutral' },
    uncommon: { label: 'Kam uchraydigan', variant: 'success' },
    rare: { label: 'Noyob', variant: 'info' },
    epic: { label: 'Epik', variant: 'brand' },
    legendary: { label: 'Afsonaviy', variant: 'warning' },
};

// Badge icons are stored as heroicon-style names ("beaker", "trophy" …); emoji are shown as content.
const ICONS = {
    cog: Cog6ToothIcon, fire: FireIcon, bolt: BoltIcon, eye: EyeIcon, signal: SignalIcon, atom: CubeTransparentIcon,
    beaker: BeakerIcon, 'light-bulb': LightBulbIcon, calendar: CalendarIcon, star: StarIcon, clock: ClockIcon,
    trophy: TrophyIcon, 'academic-cap': AcademicCapIcon, sparkles: SparklesIcon, map: MapIcon, flag: FlagIcon,
    'building-office': BuildingOfficeIcon, school: BuildingLibraryIcon,
};
const isIconName = (v) => typeof v === 'string' && /^[a-z0-9-]+$/i.test(v);
const iconFor = (name) => (isIconName(name) && ICONS[name]) || TrophyIcon;
const emojiOf = (v) => (v && !isIconName(v) ? v : null);

const fixed1 = (v) => {
    const n = Number(v);
    return n > 0 ? n.toFixed(1) : null;
};
const avgRating = computed(() => fixed1(props.experiment?.avg_rating));
const summaryAvg = computed(() => fixed1(props.ratingsSummary?.avg) || '0.0');
const summaryStars = computed(() => Math.round(Number(props.ratingsSummary?.avg) || 0));

const facts = computed(() => [
    { key: 'duration', label: 'Davomiyligi', value: props.experiment.duration_text || (props.experiment.estimated_duration ? `${props.experiment.estimated_duration} daqiqa` : '—'), icon: ClockIcon },
    { key: 'grade', label: 'Sinf', value: props.experiment.grade_level ? `${props.experiment.grade_level}-sinf` : '—', icon: AcademicCapIcon },
    { key: 'rating', label: `${props.experiment.total_ratings || 0} ta baho`, value: avgRating.value || '—', icon: StarIcon },
    { key: 'completions', label: 'Yakunlaganlar', value: Number(props.experiment.total_completions || 0).toLocaleString('ru-RU'), icon: ChartBarIcon },
]);

const best = computed(() => Math.round(Number(props.userProgress?.best_percentage ?? props.userProgress?.best_score ?? 0) || 0));
const attempts = computed(() => props.userProgress?.attempts_count ?? props.userProgress?.total_attempts ?? 0);

// Premium purchase link — only when such a route exists (never "#")
const upgradeHref = computed(() => {
    const name = ['student.subscription.index', 'student.subscriptions.index', 'student.premium', 'student.pricing']
        .find((n) => routeHas(n));
    return name ? routePath(name) : null;
});
</script>

<template>

    <Head :title="`${experiment.title} - Virtual laboratoriya`" />

    <StudentLayout>
        <PageHeader :title="experiment.title" :description="experiment.short_description" :breadcrumbs="[
            { label: 'Laboratoriya', href: route('student.lab.index') },
            { label: experiment.category.name, href: route('student.lab.category', experiment.category.slug) },
            { label: experiment.title },
        ]">
            <template #meta>
                <Badge variant="physics">{{ experiment.category.name }}</Badge>
                <Badge v-if="experiment.is_free" variant="success">Bepul</Badge>
                <Badge v-else variant="warning" :icon="LockClosedIcon">Premium</Badge>
                <Badge v-if="experiment.difficulty_label" :variant="difficultyVariant[experiment.difficulty] || 'neutral'" dot>
                    {{ experiment.difficulty_label }}
                </Badge>
            </template>
        </PageHeader>

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Main -->
            <div class="min-w-0 space-y-6 lg:col-span-2">
                <Card title="Tajriba haqida">
                    <p v-if="experiment.description" class="whitespace-pre-line text-sm leading-relaxed text-fg-muted">
                        {{ experiment.description }}
                    </p>
                    <dl class="grid grid-cols-2 gap-3 sm:grid-cols-4" :class="experiment.description ? 'mt-5' : ''">
                        <div v-for="f in facts" :key="f.key" class="rounded-md bg-surface-2 px-3 py-2.5">
                            <dt class="flex items-center gap-1.5 text-xs text-fg-subtle">
                                <component :is="f.icon" class="h-4 w-4" aria-hidden="true" />
                                {{ f.label }}
                            </dt>
                            <dd class="mt-1 font-semibold text-fg tabular-nums">{{ f.value }}</dd>
                        </div>
                    </dl>
                </Card>

                <Card v-if="experiment.objectives?.length" title="Maqsadlar">
                    <ul class="space-y-2.5">
                        <li v-for="(obj, i) in experiment.objectives" :key="i" class="flex items-start gap-2.5 text-sm text-fg">
                            <CheckCircleMini class="mt-0.5 h-4 w-4 shrink-0 text-success" aria-hidden="true" />
                            <span>{{ obj }}</span>
                        </li>
                    </ul>
                </Card>

                <Card v-if="experiment.theory" title="Nazariy qism">
                    <div class="text-sm leading-relaxed text-fg-muted" v-html="experiment.theory?.replace(/\n/g, '<br>')"></div>
                </Card>

                <Card v-if="experiment.formulas?.length" title="Formulalar">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div v-for="(formula, i) in experiment.formulas" :key="formula.id ?? i"
                            class="rounded-md border border-border bg-surface-2 p-4">
                            <div class="overflow-x-auto font-mono text-base text-fg">{{ formula.latex }}</div>
                            <p v-if="formula.name" class="mt-2 text-sm font-medium text-fg">{{ formula.name }}</p>
                            <p v-if="formula.description" class="mt-0.5 text-13 text-fg-muted">{{ formula.description }}</p>
                        </div>
                    </div>
                </Card>

                <Card v-if="experiment.required_equipment?.length" title="Kerakli jihozlar">
                    <ul class="flex flex-wrap gap-2">
                        <li v-for="(eq, i) in experiment.required_equipment" :key="eq.id ?? i"
                            class="inline-flex items-center gap-2 rounded-md border border-border bg-surface px-3 py-2 text-sm text-fg">
                            <WrenchScrewdriverIcon class="h-4 w-4 text-fg-subtle" aria-hidden="true" />
                            {{ eq.name }}
                            <span v-if="eq.count > 1" class="text-fg-subtle tabular-nums">×{{ eq.count }}</span>
                        </li>
                    </ul>
                </Card>

                <Card title="Baholar">
                    <template v-if="ratings?.length">
                        <div class="flex items-center gap-4">
                            <span class="text-3xl font-semibold text-fg tabular-nums">{{ summaryAvg }}</span>
                            <div>
                                <div class="flex items-center gap-0.5" :aria-label="`5 dan ${summaryAvg}`" role="img">
                                    <StarMini v-for="n in 5" :key="n" class="h-4 w-4"
                                        :class="n <= summaryStars ? 'text-warning' : 'text-border-strong'" aria-hidden="true" />
                                </div>
                                <p class="mt-0.5 text-13 text-fg-subtle tabular-nums">{{ ratingsSummary?.total || 0 }} ta baho</p>
                            </div>
                        </div>

                        <ul class="mt-5 divide-y divide-border border-t border-border">
                            <li v-for="rating in ratings.slice(0, 3)" :key="rating.id" class="flex gap-3 py-4">
                                <Avatar :src="rating.user?.avatar" :name="rating.user?.name" size="sm" />
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                        <span class="font-medium text-fg">{{ rating.user?.name }}</span>
                                        <span class="flex items-center gap-0.5" :aria-label="`${rating.rating} yulduz`" role="img">
                                            <StarMini v-for="n in 5" :key="n" class="h-3.5 w-3.5"
                                                :class="n <= (Number(rating.rating) || 0) ? 'text-warning' : 'text-border-strong'"
                                                aria-hidden="true" />
                                        </span>
                                        <span class="text-xs text-fg-subtle">{{ rating.created_at }}</span>
                                    </div>
                                    <p v-if="rating.review" class="mt-1 text-sm text-fg-muted">{{ rating.review }}</p>
                                </div>
                            </li>
                        </ul>
                    </template>
                    <EmptyState v-else :icon="ChatBubbleLeftRightIcon" size="sm" title="Hali baholar yo'q"
                        description="Tajribani yakunlab, birinchi bo'lib baho qoldiring." />
                </Card>
            </div>

            <!-- Sidebar -->
            <aside class="space-y-6">
                <Card>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-md bg-brand-50 px-3 py-3">
                            <SparklesIcon class="h-5 w-5 text-brand" aria-hidden="true" />
                            <p class="mt-1.5 text-lg font-semibold text-fg tabular-nums">+{{ experiment.xp_reward || 0 }}</p>
                            <p class="text-xs text-fg-muted">XP</p>
                        </div>
                        <div class="rounded-md bg-warning-50 px-3 py-3">
                            <CircleStackIcon class="h-5 w-5 text-warning" aria-hidden="true" />
                            <p class="mt-1.5 text-lg font-semibold text-fg tabular-nums">+{{ experiment.coin_reward || 0 }}</p>
                            <p class="text-xs text-fg-muted">Coin</p>
                        </div>
                    </div>

                    <dl class="mt-4 divide-y divide-border text-sm">
                        <div class="flex justify-between py-2.5">
                            <dt class="text-fg-muted">Umumiy ball</dt>
                            <dd class="font-medium text-fg tabular-nums">{{ experiment.total_points ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between py-2.5">
                            <dt class="text-fg-muted">Vazifalar</dt>
                            <dd class="font-medium text-fg tabular-nums">{{ experiment.tasks_count ?? '—' }}</dd>
                        </div>
                    </dl>

                    <div class="mt-4">
                        <Button v-if="isAccessible" size="lg" block :icon-left="PlayIcon"
                            :href="route('student.lab.simulate', experiment.slug)">
                            Tajribani boshlash
                        </Button>
                        <div v-else class="space-y-3">
                            <div class="flex items-start gap-3 rounded-md bg-warning-50 p-3 ring-1 ring-inset ring-warning-100">
                                <LockClosedIcon class="h-5 w-5 shrink-0 text-warning" aria-hidden="true" />
                                <p class="text-sm text-fg">Bu tajriba uchun Premium obuna kerak.</p>
                            </div>
                            <Button v-if="upgradeHref" block :href="upgradeHref">Premium sotib olish</Button>
                        </div>
                    </div>

                    <div v-if="userProgress" class="mt-5 border-t border-border pt-4">
                        <div class="mb-2 flex items-center justify-between text-sm">
                            <span class="text-fg-muted">Sizning natijangiz</span>
                            <span class="font-semibold text-fg tabular-nums">{{ best }}%</span>
                        </div>
                        <ProgressBar :value="best" :tone="best >= 80 ? 'success' : 'brand'" size="sm" />
                        <div class="mt-2 flex flex-wrap justify-between gap-2 text-xs text-fg-subtle">
                            <span class="tabular-nums">{{ attempts }} urinish</span>
                            <span v-if="userProgress.last_attempt_at">Oxirgi: {{ userProgress.last_attempt_at }}</span>
                        </div>
                    </div>
                </Card>

                <Card v-if="experiment.badge_on_complete" title="Mukofot badge">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-md bg-surface-2 text-fg-muted">
                            <span v-if="emojiOf(experiment.badge_on_complete.icon)" class="text-xl leading-none" aria-hidden="true">
                                {{ emojiOf(experiment.badge_on_complete.icon) }}
                            </span>
                            <component :is="iconFor(experiment.badge_on_complete.icon)" v-else class="h-6 w-6" aria-hidden="true" />
                        </span>
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-fg">{{ experiment.badge_on_complete.name }}</p>
                            <div class="mt-1 flex flex-wrap items-center gap-2">
                                <Badge :variant="rarity[experiment.badge_on_complete.rarity]?.variant || 'neutral'" size="sm">
                                    {{ rarity[experiment.badge_on_complete.rarity]?.label || experiment.badge_on_complete.rarity }}
                                </Badge>
                                <span class="text-xs text-fg-subtle">Yakunlash uchun</span>
                            </div>
                        </div>
                    </div>
                </Card>

                <Card v-if="prerequisites?.length" title="Oldingi talablar" padding="none">
                    <ul class="divide-y divide-border">
                        <li v-for="prereq in prerequisites" :key="prereq.id">
                            <Link :href="route('student.lab.show', prereq.slug)"
                                class="focus-ring flex min-h-[44px] items-center gap-3 px-5 py-3 text-sm hover:bg-surface-2/60 sm:px-6">
                                <CheckCircleMini v-if="prereq.completed" class="h-5 w-5 shrink-0 text-success" aria-hidden="true" />
                                <span v-else class="h-5 w-5 shrink-0 rounded-full border-2 border-border-strong" aria-hidden="true" />
                                <span class="min-w-0 flex-1 truncate" :class="prereq.completed ? 'text-fg-muted' : 'font-medium text-fg'">
                                    {{ prereq.title }}
                                </span>
                                <span class="sr-only">{{ prereq.completed ? 'Bajarilgan' : 'Bajarilmagan' }}</span>
                            </Link>
                        </li>
                    </ul>
                </Card>

                <Card v-if="experiment.video_tutorial_url" title="Video darslik">
                    <Button variant="secondary" block external :href="experiment.video_tutorial_url" :icon-left="PlayCircleIcon">
                        Videoni ko'rish
                    </Button>
                </Card>
            </aside>
        </div>
    </StudentLayout>
</template>
