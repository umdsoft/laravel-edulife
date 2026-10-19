<script setup>
import { Head } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { computed } from 'vue';
import { PageHeader, Card, StatCard, Badge, EmptyState, ProgressBar } from '@/Components/UI';
import {
    AcademicCapIcon, BeakerIcon, BoltIcon, BuildingLibraryIcon, BuildingOfficeIcon, CalendarIcon, ChartPieIcon,
    CheckBadgeIcon, ClockIcon, Cog6ToothIcon, CubeTransparentIcon, EyeIcon, FireIcon, FlagIcon, LightBulbIcon,
    MapIcon, SignalIcon, SparklesIcon, StarIcon, TrophyIcon,
} from '@heroicons/vue/24/outline';
import {
    CheckCircleIcon as CheckCircleMini, CircleStackIcon as CoinMini, LockClosedIcon as LockMini,
    SparklesIcon as SparklesMini,
} from '@heroicons/vue/20/solid';

const props = defineProps({
    badges: Array,
    badgesByRarity: Object,
});

const rarityLabels = {
    common: { name: 'Oddiy', variant: 'neutral', tone: 'bg-surface-2 text-fg-muted' },
    uncommon: { name: 'Kam uchraydigan', variant: 'success', tone: 'bg-success-50 text-success' },
    rare: { name: 'Noyob', variant: 'info', tone: 'bg-info-50 text-info' },
    epic: { name: 'Epik', variant: 'brand', tone: 'bg-brand-50 text-brand' },
    legendary: { name: 'Afsonaviy', variant: 'warning', tone: 'bg-warning-50 text-warning' },
};

const rarityOrder = ['legendary', 'epic', 'rare', 'uncommon', 'common'];

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

const all = computed(() => props.badges || []);
const earnedCount = computed(() => all.value.filter((b) => b.earned).length);
const percent = computed(() => (all.value.length ? Math.round((earnedCount.value / all.value.length) * 100) : 0));

const sections = computed(() => {
    const groups = props.badgesByRarity || {};
    const keys = [...rarityOrder, ...Object.keys(groups).filter((k) => !rarityOrder.includes(k))];
    return keys
        .filter((key) => groups[key]?.length)
        .map((key) => ({ key, meta: rarityLabels[key] || rarityLabels.common, items: groups[key] }));
});
</script>

<template>

    <Head title="Badgelar - Virtual laboratoriya" />

    <StudentLayout>
        <PageHeader title="Lab badgelari"
            description="Tajribalar o'tkazing va maxsus badgelarni qo'lga kiriting."
            :breadcrumbs="[{ label: 'Laboratoriya', href: route('student.lab.index') }, { label: 'Badgelar' }]" />

        <div class="space-y-6">
            <!-- Stats -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-3">
                <StatCard label="Qo'lga kiritilgan" :value="earnedCount" :icon="CheckBadgeIcon" tone="success" />
                <StatCard label="Jami badgelar" :value="all.length" :icon="TrophyIcon" tone="neutral" />
                <StatCard class="col-span-2 lg:col-span-1" label="Progress" :value="percent" unit="%" :icon="ChartPieIcon" tone="brand">
                    <ProgressBar class="mt-1 w-full" :value="percent" size="xs" />
                </StatCard>
            </div>

            <!-- Badges by rarity -->
            <section v-for="section in sections" :key="section.key" :aria-labelledby="`rarity-${section.key}`" class="space-y-4">
                <div class="flex items-center gap-2">
                    <h2 :id="`rarity-${section.key}`" class="text-lg font-semibold text-fg">{{ section.meta.name }}</h2>
                    <Badge :variant="section.meta.variant" size="sm">
                        <span class="tabular-nums">{{ section.items.length }}</span>
                    </Badge>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <Card v-for="badge in section.items" :key="badge.id" padding="none"
                        :class="badge.earned ? '' : 'bg-surface-2/40'">
                        <div class="flex h-full flex-col p-5">
                            <div class="flex items-start gap-3">
                                <span :class="[
                                    'inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-md',
                                    badge.earned ? section.meta.tone : 'bg-surface-2 text-fg-subtle',
                                ]">
                                    <span v-if="emojiOf(badge.icon)" class="text-xl leading-none"
                                        :class="badge.earned ? '' : 'opacity-40 grayscale'" aria-hidden="true">
                                        {{ emojiOf(badge.icon) }}
                                    </span>
                                    <component :is="iconFor(badge.icon)" v-else class="h-6 w-6" aria-hidden="true" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <h3 class="font-semibold" :class="badge.earned ? 'text-fg' : 'text-fg-muted'">{{ badge.name }}</h3>
                                    <p v-if="badge.description" class="mt-0.5 line-clamp-2 text-13 text-fg-subtle">{{ badge.description }}</p>
                                </div>
                            </div>

                            <p v-if="badge.condition_text" class="mt-4 rounded-md bg-surface-2 px-3 py-2 text-13 text-fg-muted">
                                {{ badge.condition_text }}
                            </p>

                            <div class="mt-auto flex flex-wrap items-center justify-between gap-2 pt-4">
                                <div class="flex items-center gap-3 text-13 font-medium text-fg-muted tabular-nums">
                                    <span v-if="badge.xp_reward" class="inline-flex items-center gap-1">
                                        <SparklesMini class="h-4 w-4 text-brand" aria-hidden="true" />+{{ badge.xp_reward }} XP
                                    </span>
                                    <span v-if="badge.coin_reward" class="inline-flex items-center gap-1">
                                        <CoinMini class="h-4 w-4 text-warning" aria-hidden="true" />+{{ badge.coin_reward }}
                                        <span class="sr-only">coin</span>
                                    </span>
                                </div>
                                <Badge v-if="badge.earned" variant="success" size="sm" :icon="CheckCircleMini">Qo'lga kiritildi</Badge>
                                <Badge v-else variant="neutral" size="sm" :icon="LockMini">Qulflangan</Badge>
                            </div>
                        </div>
                    </Card>
                </div>
            </section>

            <Card v-if="!sections.length">
                <EmptyState :icon="TrophyIcon" title="Badgelar hali qo'shilmagan"
                    description="Tez orada bu yerda qo'lga kiritish mumkin bo'lgan badgelar paydo bo'ladi." />
            </Card>
        </div>
    </StudentLayout>
</template>
