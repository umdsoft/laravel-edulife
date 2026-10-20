<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { ArrowPathIcon, ClockIcon, TrophyIcon, XMarkIcon, BoltIcon } from '@heroicons/vue/24/outline';
import { CheckCircleIcon, XCircleIcon, CircleStackIcon } from '@heroicons/vue/20/solid';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Avatar, Badge, Button, Card, PageHeader, ProgressBar, Spinner } from '@/Components/UI';
import { useToast } from '@/Composables/useToast';
import { levelLabel, levelVariant, sharedPercent, solverLabel, valueFormula } from './scoring';
// media: formulas, pictures and graphs in the question and options (agent M)
import MathText from '@/Components/MathText.vue';
import { optionsHaveMedia } from '@/Components/Media/optionMedia';
import { mediaPlaceholderText } from '@/Utils/richText';
// Telegram WebView: haptics, closing confirmation, light polling paused while minimised (no-ops in a browser)
import { haptic, onAppVisibility, shareLink, useBackButton, useClosingGuard, useMainButton, useTelegram } from '@/Composables/useTelegram';
import { ShareIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    battle: Object,
    currentRound: { type: Object, default: null },
    isPlayer1: Boolean,
    serverTime: String,
    scoring: { type: Object, default: () => ({}) },
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
        haptic.notify(data.is_correct ? 'success' : 'error');
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
// the header's XP / coin badge comes from the shared auth props: refresh it once
// the battle ends, so the reward shows without reloading the page (QA2-S-16)
watch(finished, (done, wasDone) => {
    if (done && !wasDone) router.reload({ only: ['auth'], preserveScroll: true });
});
// --- Telegram WebView: a light state poll (GET battles.state, ~100 bytes) instead of reloading every
// prop every 1.5 s; the full reload runs only when something changed or the round's time is up.
// While Telegram is minimised nothing is polled; the server clock decides the rounds either way,
// so a player in Telegram and one on the website play by the same timing.
const tg = useTelegram();
let paused = false;
const localSig = () => {
    const r = props.currentRound;
    const mine = r && (r.answered || feedback.value !== null) ? 1 : 0;
    const theirs = r?.opponent_answered ? 1 : 0;
    return [status.value, props.battle.current_round, r?.id ?? '', props.isPlayer1 ? mine : theirs, props.isPlayer1 ? theirs : mine,
        props.battle.player1_score, props.battle.player2_score, props.battle.player2 ? 1 : 0].join('|');
};
const lightPoll = async () => {
    if (finished.value || paused) return;
    if (props.currentRound && countdown.value === 0 && remaining.value === 0) {
        refresh(); // time is up: the server closes the round on the full reload
        return;
    }
    try {
        const { data } = await axios.get(route('student.battles.state', props.battle.id));
        if (data?.v && data.v !== localSig()) refresh();
    } catch {
        /* next tick */
    }
};
onAppVisibility((visible) => {
    paused = !visible;
    if (visible && !finished.value) refresh();
});
useClosingGuard(() => !finished.value);
// no Telegram Back while a round is being played (leaving would only cost the rounds)
useBackButton(() => (status.value === 'in_progress' ? null : undefined));
// the scoreboard fits a 320-430 px Mini App: no avatars below sm, one-line score
const compact = computed(() => tg.active.value);
useMainButton(() => (finished.value ? { text: 'Yana jang', onClick: () => router.visit(route('student.battles.index')) } : null));
const invite = computed(() => (status.value === 'waiting' ? props.battle.invite || null : null));
const sendInvite = async () => {
    const link = invite.value?.telegram || invite.value?.web;
    if (!link) return;
    if (shareLink(link, invite.value.text || '')) return;
    try {
        if (navigator.share) {
            await navigator.share({ url: link, text: invite.value.text || '' });
            return;
        }
        await navigator.clipboard.writeText(link);
        toast.success('Havola nusxalandi');
    } catch {
        /* cancelled */
    }
};
// --- end Telegram WebView ---

onMounted(() => {
    tick = setInterval(() => (now.value = Date.now()), 250);
    poll = setInterval(() => {
        if (tg.active.value) lightPoll();
        else if (!finished.value) refresh();
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
const roundMyPoints = (r) => (props.isPlayer1 ? r.player1_points : r.player2_points) ?? 0;
const roundTheirPoints = (r) => (props.isPlayer1 ? r.player2_points : r.player1_points) ?? 0;
const subtitle = computed(() => [props.battle.direction?.name || 'Aralash mavzular', props.battle.difficulty_label].filter(Boolean).join(' · '));
const showRules = ref(false);
</script>

<template>
    <StudentLayout title="Jang">
        <Head title="Jang" />

        <PageHeader title="Bilimlar jangi" :description="subtitle"
            :breadcrumbs="[{ label: 'Janglar', href: route('student.battles.index') }, { label: 'Jang' }]" />

        <!-- Waiting for an opponent -->
        <Card v-if="status === 'waiting'" class="mx-auto max-w-md">
            <div class="flex flex-col items-center py-8 text-center" role="status">
                <Spinner size="h-10 w-10" class="text-brand" />
                <template v-if="invite">
                    <h2 class="mt-4 text-lg font-semibold text-fg">Do'stingiz kutilmoqda…</h2>
                    <p class="mt-1 text-sm text-fg-muted">Havolani do'stingizga yuboring. Chaqiruv {{ Math.ceil(secondsUntil(battle.expires_at) / 60) }} daqiqa amal qiladi.</p>
                    <Button class="mt-6" :icon-left="ShareIcon" @click="sendInvite">Do'stga yuborish</Button>
                    <p class="mt-3 break-all text-13 text-fg-subtle">{{ invite.telegram || invite.web }}</p>
                </template>
                <template v-else>
                    <h2 class="mt-4 text-lg font-semibold text-fg">Raqib qidirilmoqda…</h2>
                    <p class="mt-1 text-sm text-fg-muted">Qidiruv {{ secondsUntil(battle.expires_at) }} soniyadan keyin to'xtaydi.</p>
                </template>
                <Button class="mt-6" variant="secondary" :icon-left="XMarkIcon" :loading="cancelling" @click="cancel">Bekor qilish</Button>
            </div>
        </Card>

        <div v-else class="space-y-6">
            <!-- Scoreboard -->
            <Card padding="sm">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <Avatar :src="me?.avatar" :name="me?.full_name" size="lg" :class="compact ? 'hidden sm:inline-flex' : ''" />
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-fg">{{ me?.full_name }} <span class="text-13 font-normal text-fg-subtle">(siz)</span></p>
                            <p class="text-13 tabular-nums text-fg-subtle">ELO {{ me?.elo_rating ?? '—' }}</p>
                        </div>
                    </div>
                    <div class="text-center">
                        <p :class="['font-semibold tabular-nums text-fg', compact ? 'whitespace-nowrap text-2xl sm:text-3xl' : 'text-3xl']">{{ myScore }} : {{ opponentScore }}</p>
                        <p v-if="!finished" class="text-13 tabular-nums text-fg-subtle">Savol {{ battle.current_round }} / {{ battle.total_rounds }}</p>
                    </div>
                    <div class="flex min-w-0 items-center justify-end gap-3 text-right">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-fg">{{ opponent?.full_name || 'Raqib' }}</p>
                            <p class="text-13 tabular-nums text-fg-subtle">ELO {{ opponent?.elo_rating ?? '—' }}</p>
                        </div>
                        <Avatar :src="opponent?.avatar" :name="opponent?.full_name || 'Raqib'" size="lg" :class="compact ? 'hidden sm:inline-flex' : ''" />
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

                        <div class="flex flex-wrap items-center gap-2">
                            <Badge v-if="battle.difficulty" size="sm" :variant="levelVariant(battle.difficulty)">{{ levelLabel(battle.difficulty) }}</Badge>
                            <Badge v-if="currentRound.question_value" size="sm" variant="brand">Qiymati: {{ currentRound.question_value }} ochko</Badge>
                            <span v-if="currentRound.solve_rate !== null && currentRound.solve_rate !== undefined" class="text-13 text-fg-subtle">
                                Taxminan {{ currentRound.solve_rate }}% o'quvchi yechadi
                            </span>
                        </div>

                        <MathText :text="currentRound.question.question_text" tag="div" class="block text-lg font-medium text-fg" />

                        <div :class="['grid gap-3', optionsHaveMedia(currentRound.question.options) ? 'grid-cols-1 min-[360px]:grid-cols-2' : 'sm:grid-cols-2']">
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
                                <MathText :text="o.content" zoom="badge" class="min-w-0 flex-1" />
                                <CheckCircleIcon v-if="picked === o.id && myResult === true" class="h-5 w-5 text-success" aria-hidden="true" />
                                <XCircleIcon v-if="picked === o.id && myResult === false" class="h-5 w-5 text-danger" aria-hidden="true" />
                            </button>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-2 text-13">
                            <span v-if="answered" :class="myResult ? 'text-success' : 'text-danger'">
                                {{ myResult ? "To'g'ri!" : "Noto'g'ri" }} — raqib kutilmoqda…
                            </span>
                            <span v-else class="text-fg-subtle">Faqat siz yechsangiz — to'liq qiymat, ikkalangiz yechsangiz — {{ sharedPercent(scoring) }}%. Tezlik kichik bonus beradi.</span>
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
                        <li v-for="r in battle.rounds" :key="r.round_number" class="flex flex-wrap items-center gap-x-4 gap-y-1 px-5 py-3 sm:flex-nowrap sm:px-6">
                            <span class="w-6 text-13 tabular-nums text-fg-subtle">{{ r.round_number }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm text-fg">{{ mediaPlaceholderText(r.question_text) }}</p>
                                <p class="text-13 text-fg-subtle">
                                    <span v-if="r.question_value" class="tabular-nums">Qiymati {{ r.question_value }}</span>
                                    <span v-if="r.question_value" class="mx-1">·</span>
                                    <span>{{ solverLabel(r.solver_count, roundMine(r)) }}</span>
                                    <template v-if="r.solve_rate !== null && r.solve_rate !== undefined">
                                        <span class="mx-1">·</span><span class="tabular-nums">umumiy {{ r.solve_rate }}%</span>
                                    </template>
                                </p>
                            </div>
                            <span class="flex items-center gap-2" aria-label="Siz / raqib">
                                <CheckCircleIcon v-if="roundMine(r)" class="h-5 w-5 text-success" aria-hidden="true" />
                                <XCircleIcon v-else class="h-5 w-5 text-danger" aria-hidden="true" />
                                <span class="text-fg-subtle">/</span>
                                <CheckCircleIcon v-if="roundTheirs(r)" class="h-5 w-5 text-success" aria-hidden="true" />
                                <XCircleIcon v-else class="h-5 w-5 text-danger" aria-hidden="true" />
                            </span>
                            <span class="w-24 text-right text-sm font-semibold tabular-nums text-fg"
                                :aria-label="'Siz ' + roundMyPoints(r) + ', raqib ' + roundTheirPoints(r) + ' ochko'">
                                {{ roundMyPoints(r) }} : {{ roundTheirPoints(r) }}
                            </span>
                        </li>
                    </ol>
                </Card>

                <Card padding="sm">
                    <button type="button" class="focus-ring flex w-full items-center justify-between rounded text-left text-sm font-medium text-fg"
                        :aria-expanded="showRules" aria-controls="battle-rules" @click="showRules = !showRules">
                        Ochkolar qanday hisoblanadi?
                        <span class="text-13 text-brand">{{ showRules ? 'Yopish' : "Ko'rsatish" }}</span>
                    </button>
                    <div v-if="showRules" id="battle-rules" class="mt-3 space-y-2 text-13 text-fg-muted">
                        <p>Jangdagi barcha savollar bir xil qiyinlikda va ikkalangizga bir xil tartibda tushadi.</p>
                        <p><span class="font-medium text-fg">Savol qiymati</span> = {{ valueFormula(scoring) }}. Bu yerda p — savolni barcha janglar va kurs testlarida to'g'ri yechganlar ulushi (yangi savolda — o'qituvchi belgilagan qiyinlikdan). Kam yechiladigan savol qimmatroq.</p>
                        <p><span class="font-medium text-fg">Raund ochkosi</span>: faqat bittangiz yechsa — to'liq qiymat; ikkalangiz yechsangiz — har biringizga {{ sharedPercent(scoring) }}%. Tez javob uchun +{{ scoring?.speed_bonus_pct ?? 0 }}% gacha bonus.</p>
                        <p>G'olib — jami ochkosi ko'p bo'lgan o'yinchi; ELO reyting shu natija bo'yicha o'zgaradi.</p>
                    </div>
                </Card>
            </template>
        </div>
    </StudentLayout>
</template>
