<template>
    <StudentLayout title="Reyting">
        <Head title="Reyting" />

        <PageHeader title="Reyting" description="Eng faol o'quvchilar: XP, janglar va haftalik natijalar bo'yicha.">
            <template #tabs>
                <Tabs :tabs="tabs" label="Reyting turi" />
            </template>
        </PageHeader>

        <div class="space-y-6">
            <!-- User position -->
            <Card v-if="user_position" padding="sm">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <Avatar :src="me?.avatar_url || me?.avatar" :name="myName" size="md" />
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-fg">Sizning o'rningiz</p>
                            <p class="text-13 text-fg-subtle">{{ user_position > 100 ? 'Top 100 dan tashqarida' : 'Top 100 ichida' }}</p>
                        </div>
                    </div>
                    <p class="text-2xl font-semibold tabular-nums text-fg">#{{ user_position }}</p>
                </div>
            </Card>

            <!-- Leaderboard -->
            <EmptyState v-if="rows.length === 0" :icon="TrophyIcon" bordered title="Reyting hali shakllanmagan"
                description="Faol bo'ling va birinchilardan bo'ling." />
            <Table v-else :columns="columns" :rows="rows" row-key="user_id" caption="Reyting jadvali">
                <tr v-for="player in rows" :key="player.user_id"
                    :class="isMe(player) ? 'bg-brand-50' : 'hover:bg-surface-2'"
                    :aria-current="isMe(player) ? 'true' : undefined">
                    <td class="border-b border-border px-4 py-3">
                        <span class="inline-flex h-8 min-w-[2rem] items-center justify-center gap-1 rounded-full px-2 text-13 font-semibold tabular-nums"
                            :class="rankChip(player.rank)">
                            <TrophyIcon v-if="player.rank <= 3" class="h-4 w-4" aria-hidden="true" />
                            {{ player.rank }}
                        </span>
                    </td>
                    <td class="border-b border-border px-4 py-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <Avatar :src="player.avatar" :name="player.name" size="sm" class="shrink-0" />
                            <div class="min-w-0">
                                <p class="truncate font-medium text-fg">
                                    {{ player.name }}
                                    <span v-if="isMe(player)" class="font-normal text-fg-subtle">(siz)</span>
                                </p>
                                <p v-if="player.streak_days" class="flex items-center gap-1 text-xs text-fg-subtle">
                                    <FireIcon class="h-3.5 w-3.5 text-warning" aria-hidden="true" />
                                    <span class="tabular-nums">{{ player.streak_days }} kunlik seriya</span>
                                </p>
                            </div>
                        </div>
                    </td>
                    <template v-if="type !== 'battles'">
                        <td class="hidden border-b border-border px-4 py-3 text-right tabular-nums text-fg-muted sm:table-cell">
                            {{ player.level }}
                        </td>
                        <td class="border-b border-border px-4 py-3 text-right font-semibold tabular-nums text-fg whitespace-nowrap">
                            {{ fmt(player.xp) }} <span class="font-normal text-fg-subtle">XP</span>
                        </td>
                    </template>
                    <template v-else>
                        <td class="border-b border-border px-4 py-3 text-right font-semibold tabular-nums text-fg">
                            {{ fmt(player.elo_rating) }}
                        </td>
                        <td class="hidden border-b border-border px-4 py-3 text-right tabular-nums text-fg-muted sm:table-cell">
                            {{ player.battles_won }} / {{ player.battles_played }}
                        </td>
                    </template>
                    <td class="hidden border-b border-border px-4 py-3 md:table-cell">
                        <Badge v-if="player.rank_badge" size="sm" :variant="rankBadge(player.rank_badge)">{{ rankLabel(player.rank_badge) }}</Badge>
                    </td>
                </tr>
            </Table>
        </div>
    </StudentLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { BoltIcon, FireIcon, SparklesIcon } from '@heroicons/vue/24/outline';
import { TrophyIcon } from '@heroicons/vue/20/solid';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Avatar, Badge, Card, EmptyState, PageHeader, Table, Tabs } from '@/Components/UI';

const props = defineProps({
    leaderboard: Array,
    user_position: Number,
    type: String,
});

const page = usePage();
const me = computed(() => page.props.auth?.user);
const myName = computed(() => me.value?.full_name || [me.value?.first_name, me.value?.last_name].filter(Boolean).join(' '));
const isMe = (p) => p.user_id === me.value?.id;

const rows = computed(() => props.leaderboard || []);

const tabs = computed(() => [
    { key: 'xp', label: 'XP', icon: SparklesIcon, href: route('student.leaderboard.index', { type: 'xp' }), active: (props.type || 'xp') === 'xp' },
    { key: 'battles', label: 'Janglar', icon: BoltIcon, href: route('student.leaderboard.index', { type: 'battles' }), active: props.type === 'battles' },
    { key: 'weekly', label: 'Haftalik', icon: FireIcon, href: route('student.leaderboard.index', { type: 'weekly' }), active: props.type === 'weekly' },
]);

const columns = computed(() => {
    const base = [
        { key: 'rank', label: 'O\'rin', width: '84px' },
        { key: 'name', label: 'O\'quvchi' },
    ];
    const metric = props.type === 'battles'
        ? [
            { key: 'elo_rating', label: 'ELO', numeric: true },
            { key: 'battles_won', label: 'G\'alaba', numeric: true, hideBelow: 'sm' },
        ]
        : [
            { key: 'level', label: 'Daraja', numeric: true, hideBelow: 'sm' },
            { key: 'xp', label: 'XP', numeric: true },
        ];
    return [...base, ...metric, { key: 'rank_badge', label: 'Unvon', hideBelow: 'md' }];
});

const fmt = (n) => new Intl.NumberFormat('ru-RU').format(Number(n) || 0).replace(/ /g, ' ');

const rankChip = (rank) => {
    if (rank === 1) return 'bg-warning-50 text-warning ring-1 ring-inset ring-warning-100';
    if (rank === 2) return 'bg-surface-2 text-fg-muted ring-1 ring-inset ring-border-strong';
    if (rank === 3) return 'bg-surface-2 text-warning ring-1 ring-inset ring-border';
    return 'text-fg-muted';
};

const RANKS = {
    bronze: { label: 'Bronza', variant: 'neutral' },
    silver: { label: 'Kumush', variant: 'neutral' },
    gold: { label: 'Oltin', variant: 'warning' },
    platinum: { label: 'Platina', variant: 'info' },
    diamond: { label: 'Olmos', variant: 'info' },
    master: { label: 'Usta', variant: 'brand' },
};
const rankBadge = (r) => RANKS[r]?.variant || 'neutral';
const rankLabel = (r) => RANKS[r]?.label || r;
</script>
