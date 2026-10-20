<script setup>
import { computed, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { CalendarIcon, CheckCircleIcon, SparklesIcon, UsersIcon } from '@heroicons/vue/24/outline';
import { CircleStackIcon } from '@heroicons/vue/20/solid';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import TournamentBracket from '@/Components/Gamification/TournamentBracket.vue';
import { Badge, Button, Card, Modal, PageHeader } from '@/Components/UI';
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

// withdraw (DELETE …/withdraw) gives the entry fee back while registration is open
const confirmingWithdraw = ref(false);
const withdrawing = ref(false);
const withdraw = async () => {
    if (withdrawing.value) return;
    withdrawing.value = true;
    try {
        const { data } = await axios.delete(route('student.tournaments.withdraw', props.tournament.id));
        confirmingWithdraw.value = false;
        toast.success(data?.message || 'Turnirdan chiqdingiz');
        router.reload();
    } catch (e) {
        toast.error(e.response?.data?.message || "Turnirdan chiqib bo'lmadi");
    } finally {
        withdrawing.value = false;
    }
};

// Presentation
const title = computed(() => props.tournament?.name || props.tournament?.title || 'Turnir');
const joined = computed(() => {
    if (props.isJoined) return true;
    const uid = page.props.auth?.user?.id;
    return !!uid && (props.tournament?.participants || []).some((p) => p.user_id === uid);
});
const registrationPhase = computed(() => ['upcoming', 'registration'].includes(props.tournament?.status));
const canJoin = computed(() => !joined.value && registrationPhase.value);
const canWithdraw = computed(() => joined.value && registrationPhase.value);
const entryFee = computed(() => Number(props.tournament?.entry_fee_coins) || 0);

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

const prizePool = computed(() => Number(props.tournament?.prize_pool_coins) || 0);

// prizes: { "1": { xp, coins }, ... }; places without a prize are not listed
const prizes = computed(() => {
    const p = props.tournament?.prizes;
    if (!p || typeof p !== 'object') return [];
    return Object.entries(p)
        .map(([pos, v]) => ({ pos: Number(pos), xp: Number(v?.xp) || 0, coins: Number(v?.coins) || 0 }))
        .filter((x) => x.pos > 0 && (x.xp > 0 || x.coins > 0))
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
                <template v-else-if="joined">
                    <Badge variant="success" :icon="CheckCircleIcon">Siz ishtirok etyapsiz</Badge>
                    <Button v-if="canWithdraw" variant="secondary" @click="confirmingWithdraw = true">Turnirdan chiqish</Button>
                </template>
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
                    <!-- no per-place prizes: the admin's prize pool is in coins (prize_pool_coins) -->
                    <div v-else-if="prizePool > 0" class="flex items-center gap-2 text-sm text-fg">
                        <CircleStackIcon class="h-4 w-4 text-warning" aria-hidden="true" />
                        <span><span class="font-medium tabular-nums">{{ fmt(prizePool) }} coin</span> <span class="text-fg-muted">sovrin jamg'armasi</span></span>
                    </div>
                    <p v-else class="text-sm text-fg-muted">Sovrin belgilanmagan</p>
                </Card>
            </aside>
        </div>

        <Modal :show="confirmingWithdraw" size="md" title="Turnirdan chiqish" @close="confirmingWithdraw = false">
            <p class="text-sm text-fg-muted">
                «{{ title }}» turnirida o'rningiz bo'shatiladi.
                <template v-if="entryFee > 0">
                    Kirish to'lovi — <span class="font-medium text-fg tabular-nums">{{ fmt(entryFee) }} coin</span> — balansingizga qaytariladi.
                </template>
                Ro'yxat ochiq bo'lsa, keyin yana qo'shilishingiz mumkin.
            </p>
            <template #footer>
                <Button variant="secondary" :disabled="withdrawing" @click="confirmingWithdraw = false">Bekor qilish</Button>
                <Button variant="danger" :loading="withdrawing" @click="withdraw">Chiqish</Button>
            </template>
        </Modal>
    </StudentLayout>
</template>
