<script setup>
import { computed } from 'vue';
import { TrophyIcon } from '@heroicons/vue/20/solid';
import Avatar from '@/Components/UI/Avatar.vue';
import Badge from '@/Components/UI/Badge.vue';

const props = defineProps({
    tournament: Object,
});

// Participant lookup (tournament.participants[] are TournamentParticipant rows with .user)
const participantMap = computed(() => {
    const map = {};
    for (const p of props.tournament?.participants || []) map[p.id] = p;
    return map;
});

const nameOf = (participantId) => {
    if (!participantId) return null;
    const u = participantMap.value[participantId]?.user;
    if (!u) return 'Ishtirokchi';
    return u.full_name || [u.first_name, u.last_name].filter(Boolean).join(' ') || 'Ishtirokchi';
};
const avatarOf = (participantId) => {
    const u = participantMap.value[participantId]?.user;
    return u?.avatar_url || u?.avatar || null;
};

// Rounds built from real matches when present; otherwise a calm 8-player placeholder bracket
const rounds = computed(() => {
    const matches = props.tournament?.matches || [];
    if (!matches.length) {
        return [4, 2, 1].map((count, r) => ({
            number: r + 1,
            placeholder: true,
            matches: Array.from({ length: count }, (_, i) => ({ id: `p-${r}-${i}`, match_number: i + 1 })),
        }));
    }
    const byRound = {};
    for (const m of matches) {
        const n = Number(m.round_number) || 1;
        (byRound[n] ||= []).push(m);
    }
    return Object.keys(byRound)
        .map(Number)
        .sort((a, b) => a - b)
        .map((n) => ({
            number: n,
            placeholder: false,
            matches: byRound[n].slice().sort((a, b) => (a.match_number || 0) - (b.match_number || 0)),
        }));
});

const roundLabel = (index, total) => {
    if (index === total - 1) return 'Final';
    if (index === total - 2) return 'Yarim final';
    return `${index + 1}-bosqich`;
};

const STATUS = {
    pending: { label: 'Kutilmoqda', variant: 'neutral' },
    scheduled: { label: 'Rejalashtirilgan', variant: 'neutral' },
    in_progress: { label: 'Davom etmoqda', variant: 'brand' },
    completed: { label: 'Yakunlangan', variant: 'success' },
};
const statusOf = (m) => STATUS[m.status] || STATUS.pending;

const slots = (m) => [
    { id: m.participant1_id, score: m.participant1_score },
    { id: m.participant2_id, score: m.participant2_score },
];
</script>

<template>
    <div class="-mx-5 overflow-x-auto px-5 pb-2 sm:-mx-6 sm:px-6">
        <div class="flex min-w-max gap-8">
            <section v-for="(round, rIndex) in rounds" :key="round.number" class="flex w-60 flex-col">
                <h4 class="mb-3 flex items-center gap-1.5 text-xs font-medium uppercase tracking-wide text-fg-subtle">
                    <TrophyIcon v-if="rIndex === rounds.length - 1" class="h-4 w-4 text-warning" aria-hidden="true" />
                    {{ roundLabel(rIndex, rounds.length) }}
                </h4>

                <div class="flex flex-1 flex-col justify-around gap-4">
                    <div v-for="m in round.matches" :key="m.id"
                        class="overflow-hidden rounded-md border bg-surface shadow-xs"
                        :class="rIndex === rounds.length - 1 ? 'border-warning-100' : 'border-border'">
                        <div class="flex items-center justify-between gap-2 border-b border-border bg-surface-2 px-3 py-1.5">
                            <span class="text-xs font-medium tabular-nums text-fg-muted">O'yin {{ m.match_number || '' }}</span>
                            <Badge v-if="!round.placeholder" size="sm" :variant="statusOf(m).variant">{{ statusOf(m).label }}</Badge>
                        </div>

                        <ul class="divide-y divide-border">
                            <template v-if="round.placeholder">
                                <li v-for="n in 2" :key="n" class="flex h-10 items-center gap-2 px-3">
                                    <span class="h-6 w-6 shrink-0 rounded-full bg-surface-2" aria-hidden="true" />
                                    <span class="text-sm text-fg-subtle">Aniqlanmagan</span>
                                </li>
                            </template>
                            <template v-else>
                                <li v-for="(s, sIndex) in slots(m)" :key="sIndex"
                                    class="flex h-10 items-center gap-2 px-3"
                                    :class="m.winner_id && s.id && m.winner_id === s.id ? 'bg-success-50' : ''">
                                    <template v-if="s.id">
                                        <Avatar :src="avatarOf(s.id)" :name="nameOf(s.id)" size="xs" class="shrink-0" />
                                        <span class="min-w-0 flex-1 truncate text-sm"
                                            :class="m.winner_id === s.id ? 'font-semibold text-fg' : 'text-fg-muted'">
                                            {{ nameOf(s.id) }}
                                        </span>
                                        <span class="text-sm tabular-nums"
                                            :class="m.winner_id === s.id ? 'font-semibold text-success' : 'text-fg-subtle'">
                                            {{ s.score ?? '–' }}
                                        </span>
                                    </template>
                                    <template v-else>
                                        <span class="h-6 w-6 shrink-0 rounded-full bg-surface-2" aria-hidden="true" />
                                        <span class="text-sm text-fg-subtle">{{ m.is_bye ? 'Avtomatik o\'tish' : 'Aniqlanmagan' }}</span>
                                    </template>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>
