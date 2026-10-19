<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { ref, computed } from 'vue';
import { Avatar, Badge, Card, EmptyState, PageHeader, StatCard } from '@/Components/UI';
import { ChartBarIcon, StarIcon, TrophyIcon, UsersIcon } from '@heroicons/vue/24/outline';
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    olympiad: Object,
    leaderboard: Object,
    userRank: Object,
    statistics: Object,
});

const currentPage = ref(1);

// Presentation-only normalisation: the list may arrive as a paginator ({ data, last_page })
// or as a plain array; the user's position may arrive as `userRank` or `userPosition`.
const page = usePage();
const rows = computed(() => (Array.isArray(props.leaderboard) ? props.leaderboard : (props.leaderboard?.data || [])));
const lastPage = computed(() => (Array.isArray(props.leaderboard) ? 1 : (props.leaderboard?.last_page || 1)));
const me = computed(() => props.userRank || page.props.userPosition || null);
const stats = computed(() => props.statistics || {});
const podium = computed(() => (currentPage.value === 1 ? rows.value.slice(0, 3) : []));

const rankChipClass = (rank) => {
    if (rank === 1) return 'bg-warning-50 text-warning ring-warning-100';
    if (rank <= 3) return 'bg-surface-2 text-fg ring-border-strong';
    return 'bg-surface-2 text-fg-muted ring-border';
};

const numberOrDash = (v) => (v === null || v === undefined || v === '' ? null : v);

const goToPage = (p) => {
    currentPage.value = p;
    router.visit(route('student.olympiads.results.leaderboard', props.olympiad.slug), { data: { page: p } });
};
</script>

<template>
    <StudentLayout title="Reyting">
        <Head :title="`Reyting - ${olympiad.title}`" />

        <PageHeader title="Reyting" :description="olympiad.title" :breadcrumbs="[
            { label: 'Olimpiadalar', href: route('student.olympiads.index') },
            { label: olympiad.title, href: route('student.olympiads.show', olympiad.slug) },
            { label: 'Natijalar', href: route('student.olympiads.results', olympiad.slug) },
            { label: 'Reyting' },
        ]" />

        <div class="space-y-6">
            <!-- Statistics -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-3">
                <StatCard label="Ishtirokchilar" :value="numberOrDash(stats.total_participants)" :icon="UsersIcon"
                    tone="brand" />
                <StatCard label="O'rtacha natija" :value="numberOrDash(stats.average_score)" unit="%" :icon="ChartBarIcon"
                    tone="info" />
                <StatCard label="Eng yuqori ball" :value="numberOrDash(stats.highest_score)" :icon="StarIcon"
                    tone="success" class="col-span-2 lg:col-span-1" />
            </div>

            <!-- Your Position -->
            <div v-if="me && me.rank"
                class="flex items-center justify-between gap-4 rounded-lg border border-brand-200 bg-brand-50 p-4 lg:sticky lg:top-20 lg:z-10">
                <div class="flex items-center gap-3">
                    <span
                        class="inline-flex h-11 min-w-11 items-center justify-center rounded-full bg-surface px-2 text-sm font-semibold text-brand ring-1 ring-inset ring-brand-100 tabular-nums">
                        #{{ me.rank }}
                    </span>
                    <div>
                        <p class="font-semibold text-fg">Sizning o'rningiz</p>
                        <p v-if="me.top_percent" class="text-13 text-fg-muted tabular-nums">
                            Top {{ me.top_percent }}% · {{ me.total_participants }} ishtirokchidan
                        </p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-xl font-semibold text-fg tabular-nums">{{ me.score }}</p>
                    <p class="text-13 text-fg-muted tabular-nums">{{ me.score_percent }}%</p>
                </div>
            </div>

            <!-- Top 3 -->
            <div v-if="podium.length" class="grid gap-4 sm:grid-cols-3">
                <div v-for="entry in podium" :key="`p-${entry.rank}`"
                    :class="['flex items-center gap-3 rounded-lg border bg-surface p-4 shadow-xs',
                        entry.rank === 1 ? 'border-warning-100' : 'border-border',
                        entry.is_current_user ? 'ring-2 ring-brand-500' : '']">
                    <span :class="['inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full ring-1 ring-inset', rankChipClass(entry.rank)]">
                        <TrophyIcon class="h-5 w-5" aria-hidden="true" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-fg-subtle tabular-nums">{{ entry.rank }}-o'rin</p>
                        <p class="truncate font-semibold text-fg">{{ entry.user_name }}</p>
                        <p class="text-13 text-fg-muted tabular-nums">{{ entry.score }} ball</p>
                    </div>
                </div>
            </div>

            <!-- Full list -->
            <Card padding="none" title="Ishtirokchilar">
                <ol v-if="rows.length" class="divide-y divide-border">
                    <li v-for="entry in rows" :key="entry.rank" :class="[
                        'flex items-center gap-3 px-5 py-3 sm:gap-4 sm:px-6',
                        entry.is_current_user ? 'bg-brand-50' : 'hover:bg-surface-2',
                    ]" :aria-current="entry.is_current_user ? 'true' : undefined">
                        <span :class="['inline-flex h-9 min-w-9 shrink-0 items-center justify-center rounded-full px-2 text-sm font-semibold ring-1 ring-inset tabular-nums', rankChipClass(entry.rank)]">
                            {{ entry.rank }}
                        </span>
                        <Avatar :src="entry.user_avatar || null" :name="entry.user_name" size="sm" class="hidden sm:inline-flex" />
                        <div class="min-w-0 flex-1">
                            <p class="flex items-center gap-2 font-medium text-fg">
                                <span class="truncate">{{ entry.user_name }}</span>
                                <Badge v-if="entry.is_current_user" variant="brand" size="sm">Siz</Badge>
                            </p>
                            <p v-if="entry.region || entry.time" class="text-xs text-fg-subtle tabular-nums">
                                {{ entry.region || entry.time }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-fg tabular-nums">{{ entry.score }}</p>
                            <p class="text-xs text-fg-subtle tabular-nums">{{ entry.score_percent }}%</p>
                        </div>
                    </li>
                </ol>
                <EmptyState v-else :icon="TrophyIcon" title="Reyting hali shakllanmagan"
                    description="Natijalar e'lon qilingach, bu yerda ishtirokchilar ro'yxati ko'rinadi." />

                <!-- Pagination -->
                <template v-if="lastPage > 1" #footer>
                    <nav class="flex items-center justify-between gap-2" aria-label="Sahifalar">
                        <span class="text-13 text-fg-subtle tabular-nums">{{ leaderboard.current_page }} / {{ lastPage }} sahifa</span>
                        <div class="flex flex-wrap items-center gap-1">
                            <button type="button"
                                class="focus-ring inline-flex h-10 w-10 items-center justify-center rounded-md text-fg-muted hover:bg-surface-2 disabled:opacity-50"
                                :disabled="leaderboard.current_page <= 1" aria-label="Oldingi sahifa"
                                @click="goToPage(leaderboard.current_page - 1)">
                                <ChevronLeftIcon class="h-5 w-5" aria-hidden="true" />
                            </button>
                            <button v-for="p in lastPage" :key="p" type="button" :class="[
                                'focus-ring hidden h-10 min-w-10 items-center justify-center rounded-md px-3 text-sm font-medium tabular-nums sm:inline-flex',
                                p === leaderboard.current_page ? 'bg-brand-600 text-white' : 'text-fg-muted hover:bg-surface-2 hover:text-fg',
                            ]" :aria-current="p === leaderboard.current_page ? 'page' : undefined"
                                @click="goToPage(p)">
                                {{ p }}
                            </button>
                            <button type="button"
                                class="focus-ring inline-flex h-10 w-10 items-center justify-center rounded-md text-fg-muted hover:bg-surface-2 disabled:opacity-50"
                                :disabled="leaderboard.current_page >= lastPage" aria-label="Keyingi sahifa"
                                @click="goToPage(leaderboard.current_page + 1)">
                                <ChevronRightIcon class="h-5 w-5" aria-hidden="true" />
                            </button>
                        </div>
                    </nav>
                </template>
            </Card>
        </div>
    </StudentLayout>
</template>
