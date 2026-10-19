<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { ArrowPathIcon, ClockIcon, TrophyIcon, XMarkIcon, BoltIcon } from '@heroicons/vue/24/outline';
import { CheckCircleIcon, XCircleIcon, CircleStackIcon } from '@heroicons/vue/20/solid';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Avatar, Badge, Button, Card, PageHeader, ProgressBar, Spinner } from '@/Components/UI';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    battle: Object,
    currentRound: { type: Object, default: null },
    isPlayer1: Boolean,
    serverTime: String,
});

const toast = useToast();

// --- clock synced with the server -----------------------------------------
const offset = ref(0);
const now = ref(Date.now());
const syncClock = () => {
    const server = Date.parse(props.serverTime);
    if (!Number.isNaN(server)) offset.value = server - Date.now();
};
syncClock();
watch(() => props.serverTime, syncClock);

const serverNow = computed(() => now.value + offset.value);
const secondsUntil = (iso) => (iso ? Math.max(0, Math.ceil((Date.parse(iso) - serverNow.value) / 1000)) : 0);

// --- players ----------------------------------------------------------------
const me = computed(() => (props.isPlayer1 ? props.battle.player1 : props.battle.player2));
const opponent = computed(() => (props.isPlayer1 ? props.battle.player2 : props.battle.player1));
const myScore = computed(() => (props.isPlayer1 ? props.battle.player1_score : props.battle.player2_score));
const opponentScore = computed(() => (props.isPlayer1 ? props.battle.player2_score : props.battle.player1_score));

const status = computed(() => props.battle.status);
const finished = computed(() => !['waiting', 'in_progress'].includes(status.value));

// --- round ------------------------------------------------------------------
const countdown = computed(() => secondsUntil(props.currentRound?.starts_at));
const remaining = computed(() => secondsUntil(props.currentRound?.ends_at));
const timePct = computed(() => (props.battle.time_per_question ? (remaining.value / props.battle.time_per_question) * 100 : 0));

const picked = ref(null);
const feedback = ref(null); // true | false | null
const sending = ref(false);
watch(() => props.currentRound?.id, () => {
    picked.value = null;
    feedback.value = null;
});
const answered = computed(() => props.currentRound?.answered || feedback.value !== null);
const myResult = computed(() => (feedback.value !== null ? feedback.value : props.currentRound?.my_correct));

const answer = async (option) => {
    if (answered.value || sending.value || countdown.value > 0) return;
    picked.value = option.id;
    sending.value = true;
    try {
        const { data } = await axios.post(route('student.battles.answer', props.battle.id), {
            answer: option.id,
            time_taken: Math.max(0, props.battle.time_per_question - remaining.value),
        });
        feedback.value = !!data.is_correct;
        refresh();
    } catch (e) {
        toast.error(e.response?.data?.error || "Javobni yuborib bo'lmadi");
        picked.value = null;
    } finally {
        sending.value = false;
    }
};

// --- polling (works without a websocket server) -----------------------------
let tick = null;
let poll = null;
const refresh = () => router.reload({ only: ['battle', 'currentRound', 'serverTime'], preserveScroll: true });
onMounted(() => {
    tick = setInterval(() => (now.value = Date.now()), 250);
    poll = setInterval(() => {
        if (!finished.value) refresh();
    }, 1500);
});
onBeforeUnmount(() => {
    clearInterval(tick);
    clearInterval(poll);
});

const cancelling = ref(false);
const cancel = async () => {
    cancelling.value = true;
    try {
        await axios.post(route('student.battles.cancel'));
    } finally {
        router.visit(route('student.battles.index'));
    }
};

// --- result -----------------------------------------------------------------
const result = computed(() => {
    if (props.battle.is_draw) return { label: 'Durang', variant: 'neutral' };
    if (props.battle.winner_id && props.battle.winner_id === me.value?.id) return { label: "G'alaba!", variant: 'success' };
    if (props.battle.winner_id) return { label: "Mag'lubiyat", variant: 'danger' };
    return { label: status.value === 'expired' ? 'Raqib topilmadi' : 'Bekor qilindi', variant: 'neutral' };
});
const myElo = computed(() => (props.isPlayer1 ? props.battle.player1_elo_change : props.battle.player2_elo_change));
const iWon = computed(() => props.battle.winner_id && props.battle.winner_id === me.value?.id);
const roundMine = (r) => (props.isPlayer1 ? r.player1_correct : r.player2_correct);
const roundTheirs = (r) => (props.isPlayer1 ? r.player2_correct : r.player1_correct);
</script>

<template>
    <StudentLayout title="Jang">
        <Head title="Jang" />

        <PageHeader title="Bilimlar jangi" :description="battle.direction?.name || 'Aralash mavzular'"
            :breadcrumbs="[{ label: 'Janglar', href: route('student.battles.index') }, { label: 'Jang' }]" />

        <!-- Waiting for an opponent -->
        <Card v-if="status === 'waiting'" class="mx-auto max-w-md">
            <div class="flex flex-col items-center py-8 text-center" role="status">
                <Spinner size="h-10 w-10" class="text-brand" />
                <h2 class="mt-4 text-lg font-semibold text-fg">Raqib qidirilmoqda…</h2>
                <p class="mt-1 text-sm text-fg-muted">Qidiruv {{ secondsUntil(battle.expires_at) }} soniyadan keyin to'xtaydi.</p>
                <Button class="mt-6" variant="secondary" :icon-left="XMarkIcon" :loading="cancelling" @click="cancel">Bekor qilish</Button>
            </div>
        </Card>

        <div v-else class="space-y-6">
            <!-- Scoreboard -->
            <Card padding="sm">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <Avatar :src="me?.avatar" :name="me?.full_name" size="lg" />
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-fg">{{ me?.full_name }} <span class="text-13 font-normal text-fg-subtle">(siz)</span></p>
                            <p class="text-13 tabular-nums text-fg-subtle">ELO {{ me?.elo_rating ?? '—' }}</p>
                        </div>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl font-semibold tabular-nums text-fg">{{ myScore }} : {{ opponentScore }}</p>
                        <p v-if="!finished" class="text-13 tabular-nums text-fg-subtle">Savol {{ battle.current_round }} / {{ battle.total_rounds }}</p>
                    </div>
                    <div class="flex min-w-0 items-center justify-end gap-3 text-right">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-fg">{{ opponent?.full_name || 'Raqib' }}</p>
                            <p class="text-13 tabular-nums text-fg-subtle">ELO {{ opponent?.elo_rating ?? '—' }}</p>
                        </div>
                        <Avatar :src="opponent?.avatar" :name="opponent?.full_name || 'Raqib'" size="lg" />
                    </div>
                </div>
            </Card>

            <!-- Playing -->
            <template v-if="status === 'in_progress' && currentRound">
                <Card v-if="countdown > 0" class="mx-auto max-w-md">
                    <div class="flex flex-col items-center py-8 text-center" role="status">
                        <p class="text-sm text-fg-muted">Birinchi savol boshlanishiga</p>
                        <p class="mt-2 text-5xl font-semibold tabular-nums text-brand">{{ countdown }}</p>
                    </div>
                </Card>

                <Card v-else>
                    <div class="space-y-5">
                        <div class="flex items-center gap-3">
                            <ClockIcon class="h-5 w-5 shrink-0 text-fg-subtle" aria-hidden="true" />
                            <ProgressBar :value="timePct" size="sm" :tone="remaining <= 5 ? 'danger' : 'brand'" class="flex-1" :label="null" />
                            <span class="w-10 text-right text-sm font-semibold tabular-nums" :class="remaining <= 5 ? 'text-danger' : 'text-fg'">{{ remaining }}s</span>
                        </div>

                        <p class="whitespace-pre-line text-lg font-medium text-fg">{{ currentRound.question.question_text }}</p>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <button v-for="o in currentRound.question.options" :key="o.id" type="button"
                                :disabled="answered || remaining === 0 || sending"
                                :class="[
                                    'focus-ring flex min-h-[48px] items-center gap-3 rounded-md border px-4 py-3 text-left text-sm font-medium transition-colors duration-150 disabled:cursor-not-allowed',
                                    picked === o.id && myResult === true ? 'border-success-600 bg-success-50 text-fg' : '',
                                    picked === o.id && myResult === false ? 'border-danger-600 bg-danger-50 text-fg' : '',
                                    picked === o.id && myResult === null ? 'border-brand-500 bg-brand-50 text-fg' : '',
                                    picked !== o.id ? 'border-border-strong bg-surface text-fg hover:bg-surface-2 disabled:opacity-60' : '',
                                ]"
                                @click="answer(o)">
                                <span class="flex-1">{{ o.content }}</span>
                                <CheckCircleIcon v-if="picked === o.id && myResult === true" class="h-5 w-5 text-success" aria-hidden="true" />
                                <XCircleIcon v-if="picked === o.id && myResult === false" class="h-5 w-5 text-danger" aria-hidden="true" />
                            </button>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-2 text-13">
                            <span v-if="answered" :class="myResult ? 'text-success' : 'text-danger'">
                                {{ myResult ? "To'g'ri!" : "Noto'g'ri" }} — raqib kutilmoqda…
                            </span>
                            <span v-else class="text-fg-subtle">Tezroq to'g'ri javob bergan raund ochkosini oladi.</span>
                            <Badge v-if="currentRound.opponent_answered" variant="info" size="sm" :icon="BoltIcon">Raqib javob berdi</Badge>
                        </div>
                    </div>
                </Card>
            </template>

            <!-- Result -->
            <template v-if="finished">
                <Card>
                    <div class="flex flex-col items-center py-4 text-center">
                        <TrophyIcon :class="['h-12 w-12', result.variant === 'success' ? 'text-warning' : 'text-fg-subtle']" aria-hidden="true" />
                        <h2 class="mt-3 text-2xl font-semibold text-fg">{{ result.label }}</h2>
                        <div class="mt-3 flex flex-wrap justify-center gap-2">
                            <Badge v-if="battle.status === 'completed'" :variant="myElo >= 0 ? 'success' : 'danger'">ELO {{ myElo >= 0 ? '+' : '' }}{{ myElo }}</Badge>
                            <Badge v-if="iWon && battle.coin_reward" variant="warning" :icon="CircleStackIcon">+{{ battle.coin_reward }} coin</Badge>
                        </div>
                        <div class="mt-6 flex flex-wrap justify-center gap-2">
                            <Button :icon-left="ArrowPathIcon" :href="route('student.battles.index')">Yana jang</Button>
                            <Button variant="secondary" :href="route('student.battles.history')">Janglar tarixi</Button>
                        </div>
                    </div>
                </Card>

                <Card v-if="battle.rounds.length" title="Raundlar" padding="none">
                    <ol class="divide-y divide-border">
                        <li v-for="r in battle.rounds" :key="r.round_number" class="flex items-center gap-4 px-5 py-3 sm:px-6">
                            <span class="w-6 text-13 tabular-nums text-fg-subtle">{{ r.round_number }}</span>
                            <p class="min-w-0 flex-1 truncate text-sm text-fg">{{ r.question_text }}</p>
                            <span class="flex items-center gap-2" aria-label="Siz / raqib">
                                <CheckCircleIcon v-if="roundMine(r)" class="h-5 w-5 text-success" aria-hidden="true" />
                                <XCircleIcon v-else class="h-5 w-5 text-danger" aria-hidden="true" />
                                <span class="text-fg-subtle">/</span>
                                <CheckCircleIcon v-if="roundTheirs(r)" class="h-5 w-5 text-success" aria-hidden="true" />
                                <XCircleIcon v-else class="h-5 w-5 text-danger" aria-hidden="true" />
                            </span>
                        </li>
                    </ol>
                </Card>
            </template>
        </div>
    </StudentLayout>
</template>
