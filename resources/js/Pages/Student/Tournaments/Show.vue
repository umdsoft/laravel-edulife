<script setup>
import { computed, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { CalendarIcon, CheckCircleIcon, SparklesIcon, UsersIcon } from '@heroicons/vue/24/outline';
import { CircleStackIcon } from '@heroicons/vue/20/solid';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import TournamentBracket from '@/Components/Gamification/TournamentBracket.vue';
import { Badge, Button, Card, PageHeader } from '@/Components/UI';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    tournament: Object,
    isJoined: Boolean,
});

const toast = useToast();
const page = usePage();
const joining = ref(false);

// register returns JSON ({ success, message }), not an Inertia response
const joinTournament = async () => {
    if (joining.value) return;
    joining.value = true;
    try {
        await axios.post(route('student.tournaments.register', props.tournament.id));
        toast.success('Turnirga yozildingiz');
        router.reload();
    } catch (e) {
        toast.error(e.response?.data?.message || 'Turnirga yozilib bo\'lmadi');
    } finally {
        joining.value = false;
    }
};

// Presentation
const title = computed(() => props.tournament?.name || props.tournament?.title || 'Turnir');
const joined = computed(() => {
    if (props.isJoined) return true;
    const uid = page.props.auth?.user?.id;
    return !!uid && (props.tournament?.participants || []).some((p) => p.user_id === uid);
});
const canJoin = computed(() => !joined.value && ['upcoming', 'registration'].includes(props.tournament?.status));

const pad = (n) => String(n).padStart(2, '0');
const formatDate = (date) => {
    if (!date) return '—';
    const d = new Date(date);
    if (Number.isNaN(d.getTime())) return '—';
    return `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()}`;
};
const fmt = (n) => new Intl.NumberFormat('ru-RU').format(Number(n) || 0).replace(/ /g, ' ');

const STATUS = {
    upcoming: { label: 'Tez orada', variant: 'info' },
    registration: { label: 'Ro\'yxatdan o\'tish', variant: 'success' },
    in_progress: { label: 'Davom etmoqda', variant: 'brand' },
    completed: { label: 'Yakunlangan', variant: 'neutral' },
    cancelled: { label: 'Bekor qilingan', variant: 'danger' },
};
const status = computed(() => STATUS[props.tournament?.status] || { label: props.tournament?.status || '—', variant: 'neutral' });

const participantsCount = computed(() =>
    props.tournament?.participants_count ?? props.tournament?.participants?.length ?? 0,
);

// prizes: { "1": { xp, coins }, ... } (legacy pages used prize_pool)
const prizes = computed(() => {
    const p = props.tournament?.prizes;
    if (!p || typeof p !== 'object') return [];
    return Object.entries(p)
        .map(([pos, v]) => ({ pos: Number(pos), xp: v?.xp || 0, coins: v?.coins || 0 }))
        .filter((x) => x.pos > 0)
        .sort((a, b) => a.pos - b.pos);
});
</script>

<template>

    <Head :title="title" />

    <StudentLayout :title="title">
        <PageHeader :title="title" :description="tournament.description"
            :breadcrumbs="[{ label: 'Turnirlar', href: route('student.tournaments.index') }, { label: title }]">
            <template #meta>
                <Badge :variant="status.variant" dot>{{ status.label }}</Badge>
                <Badge v-if="tournament.direction?.name" variant="neutral">{{ tournament.direction.name }}</Badge>
            </template>
            <template #actions>
                <Button v-if="canJoin" :loading="joining" @click="joinTournament">Qo'shilish</Button>
                <Badge v-else-if="joined" variant="success" :icon="CheckCircleIcon">Siz ishtirok etyapsiz</Badge>
            </template>
        </PageHeader>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="min-w-0 space-y-6 lg:col-span-2">
                <Card title="Turnir jadvali" description="Bosqichlar va o'yinlar natijalari">
                    <TournamentBracket :tournament="tournament" />
                </Card>
            </div>

            <aside class="space-y-6">
                <Card title="Ma'lumot">
                    <dl class="-my-2.5 divide-y divide-border text-sm">
                        <div class="flex items-center justify-between gap-3 py-2.5">
                            <dt class="flex items-center gap-1.5 text-fg-muted"><CalendarIcon class="h-4 w-4" aria-hidden="true" /> Boshlanish</dt>
                            <dd class="font-medium tabular-nums">{{ formatDate(tournament.starts_at || tournament.start_date) }}</dd>
                        </div>
                        <div v-if="tournament.registration_ends_at" class="flex items-center justify-between gap-3 py-2.5">
                            <dt class="text-fg-muted">Ro'yxat tugaydi</dt>
                            <dd class="font-medium tabular-nums">{{ formatDate(tournament.registration_ends_at) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 py-2.5">
                            <dt class="flex items-center gap-1.5 text-fg-muted"><UsersIcon class="h-4 w-4" aria-hidden="true" /> Ishtirokchilar</dt>
                            <dd class="font-medium tabular-nums">{{ participantsCount }} / {{ tournament.max_participants || '—' }}</dd>
                        </div>
                        <div v-if="tournament.min_elo || tournament.max_elo" class="flex items-center justify-between gap-3 py-2.5">
                            <dt class="text-fg-muted">ELO oralig'i</dt>
                            <dd class="font-medium tabular-nums">{{ tournament.min_elo || 0 }} – {{ tournament.max_elo || '∞' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 py-2.5">
                            <dt class="text-fg-muted">Kirish to'lovi</dt>
                            <dd class="flex items-center gap-1 font-medium tabular-nums">
                                <template v-if="tournament.entry_fee_coins > 0">
                                    <CircleStackIcon class="h-4 w-4 text-warning" aria-hidden="true" />
                                    {{ fmt(tournament.entry_fee_coins) }} coin
                                </template>
                                <template v-else>Bepul</template>
                            </dd>
                        </div>
                    </dl>
                </Card>

                <Card title="Sovrinlar">
                    <ul v-if="prizes.length" class="-my-2.5 divide-y divide-border text-sm">
                        <li v-for="p in prizes" :key="p.pos" class="flex items-center justify-between gap-3 py-2.5">
                            <span class="font-medium tabular-nums text-fg">{{ p.pos }}-o'rin</span>
                            <span class="flex items-center gap-3 text-fg-muted">
                                <span v-if="p.xp" class="flex items-center gap-1 tabular-nums">
                                    <SparklesIcon class="h-4 w-4 text-brand" aria-hidden="true" />{{ fmt(p.xp) }} XP
                                </span>
                                <span v-if="p.coins" class="flex items-center gap-1 tabular-nums">
                                    <CircleStackIcon class="h-4 w-4 text-warning" aria-hidden="true" />{{ fmt(p.coins) }} coin
                                </span>
                            </span>
                        </li>
                    </ul>
                    <div v-else class="flex items-center gap-2 text-sm text-fg-muted">
                        <SparklesIcon class="h-4 w-4 text-brand" aria-hidden="true" />
                        <span class="tabular-nums">{{ fmt(tournament.prize_pool || 0) }} XP</span>
                    </div>
                </Card>
            </aside>
        </div>
    </StudentLayout>
</template>
