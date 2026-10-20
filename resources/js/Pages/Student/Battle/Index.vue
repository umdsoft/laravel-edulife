<template>
    <StudentLayout title="Battle">
        <Head title="Battle" />

        <PageHeader title="Battle" description="Tasodifiy raqib bilan 1 ga 1 bellashuv: tez va aniq javob bering.">
            <template #actions>
                <Button v-if="route().has('student.battles.history')" variant="secondary" :icon-left="ClockIcon"
                    :href="route('student.battles.history')">Tarix</Button>
                <Button v-if="route().has('student.tournaments.index')" variant="secondary" :icon-left="TrophyIcon"
                    :href="route('student.tournaments.index')">Turnirlar</Button>
            </template>
        </PageHeader>

        <div class="space-y-6">
            <!-- Stats -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard label="ELO reyting" :value="stats?.elo_rating ?? 0" :icon="ChartBarIcon" tone="brand" />
                <StatCard label="Unvon" :value="rankLabel(stats?.rank)" :icon="StarIcon" tone="warning" />
                <StatCard label="G'alabalar" :value="stats?.battles_won ?? 0" :icon="TrophyIcon" tone="success"
                    :hint="`Mag'lubiyat: ${stats?.battles_lost ?? 0} · Durang: ${stats?.battles_draw ?? 0}`" />
                <StatCard label="G'alaba foizi" :value="stats?.win_rate ?? 0" unit="%" :icon="BoltIcon" tone="info" />
            </div>

            <!-- Find match -->
            <Card>
                <div v-if="!searching && !activeBattle" class="mx-auto max-w-md space-y-4 py-2 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-brand-50 text-brand">
                        <BoltIcon class="h-6 w-6" aria-hidden="true" />
                    </span>
                    <div>
                        <h2 class="text-lg font-semibold text-fg">Raqib topish</h2>
                        <p class="mt-1 text-sm text-fg-muted">Mavzu va qiyinlikni tanlang. Ikkalangizga bir xil savollar tushadi.</p>
                    </div>
                    <Select v-model="selectedDirection" :options="directionOptions" placeholder="Istalgan mavzu" label="Mavzu" class="text-left" />
                    <Select v-model="selectedDifficulty" :options="levelOptions" :placeholder="autoLabel(autoLevel)" label="Qiyinlik" class="text-left"
                        :hint="`Bir jangdagi barcha savollar bir xil qiyinlikda. ${levelSummary(currentCounts)}`" />
                    <p v-if="!canPlay" class="text-left text-13 text-warning" role="status">
                        Bu mavzuda bir xil qiyinlikdagi kamida {{ minRounds }} ta savol hali yo'q.
                    </p>
                    <Button size="lg" block :icon-left="BoltIcon" :disabled="!canPlay" @click="startSearch">Raqib topish</Button>
                    <p class="text-left text-13 text-fg-subtle">
                        Ochkolar olimpiada tizimida: kam o'quvchi yechadigan savol qimmatroq, faqat siz yechgan savol to'liq qiymatini beradi.
                        <button type="button" class="focus-ring rounded font-medium text-brand hover:underline" :aria-expanded="showRules"
                            aria-controls="battle-scoring" @click="showRules = !showRules">Batafsil</button>
                    </p>
                    <div v-if="showRules" id="battle-scoring" class="space-y-1 rounded-md bg-surface-2 p-3 text-left text-13 text-fg-muted">
                        <p><span class="font-medium text-fg">Savol qiymati</span> = {{ valueFormula(scoring) }}, bu yerda p — savolni barcha janglarda to'g'ri yechganlar ulushi.</p>
                        <p>Ikkalangiz yechsangiz, har biringiz qiymatning {{ sharedPercent(scoring) }}% ini olasiz; faqat bittangiz yechsa — to'liq qiymat.</p>
                        <p>Tez javob uchun +{{ scoring?.speed_bonus_pct ?? 0 }}% gacha bonus. G'olib va ELO jami ochko bo'yicha.</p>
                    </div>
                </div>

                <div v-else-if="searching" class="mx-auto max-w-md space-y-4 py-4 text-center" role="status" aria-live="polite">
                    <span class="relative mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-brand-50 text-brand">
                        <span class="absolute inset-0 animate-ping rounded-full bg-brand-100 opacity-60 motion-reduce:animate-none" aria-hidden="true" />
                        <MagnifyingGlassIcon class="relative h-6 w-6" aria-hidden="true" />
                    </span>
                    <div>
                        <p class="text-lg font-semibold text-fg">Raqib qidirilmoqda…</p>
                        <p class="mt-1 text-sm tabular-nums text-fg-muted">{{ formatSeconds(searchTime) }}</p>
                    </div>
                    <Button variant="secondary" :icon-left="XMarkIcon" @click="cancelSearch">Bekor qilish</Button>
                </div>

                <div v-else class="flex flex-col items-center gap-3 py-4 text-center" role="status">
                    <Spinner size="h-6 w-6" class="text-brand" label="Jangga o'tilmoqda" />
                    <p class="text-sm text-fg-muted">Faol jangingizga o'tilmoqda…</p>
                </div>
            </Card>

            <!-- Recent battles -->
            <Card title="So'nggi janglar" padding="none">
                <EmptyState v-if="!recentBattles || recentBattles.length === 0" :icon="BoltIcon" size="sm"
                    title="Hali jang yo'q" description="Birinchi jangingizni boshlang." />

                <ul v-else class="divide-y divide-border">
                    <li v-for="battle in recentBattles" :key="battle.id"
                        class="flex items-center gap-3 px-5 py-3 sm:px-6">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
                            :class="outcome(battle).chip" aria-hidden="true">
                            <component :is="outcome(battle).icon" class="h-5 w-5" />
                        </span>
                        <Avatar :src="opponentOf(battle)?.avatar_url || opponentOf(battle)?.avatar"
                            :name="opponentName(battle)" size="sm" class="hidden shrink-0 sm:inline-flex" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-fg">{{ opponentName(battle) }}</p>
                            <p class="text-xs text-fg-subtle">
                                <span class="tabular-nums">{{ myScore(battle) }} : {{ theirScore(battle) }}</span>
                                <span class="mx-1">·</span>
                                <span class="tabular-nums">{{ formatDate(battle.ended_at) }}</span>
                            </p>
                        </div>
                        <div class="flex shrink-0 flex-col items-end gap-1">
                            <Badge size="sm" :variant="outcome(battle).variant">{{ outcome(battle).label }}</Badge>
                            <span v-if="eloChange(battle) !== null" class="text-xs font-medium tabular-nums"
                                :class="eloChange(battle) > 0 ? 'text-success' : eloChange(battle) < 0 ? 'text-danger' : 'text-fg-subtle'">
                                {{ eloChange(battle) > 0 ? '+' : '' }}{{ eloChange(battle) }} ELO
                            </span>
                        </div>
                    </li>
                </ul>
            </Card>
        </div>
    </StudentLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import {
    BoltIcon, ChartBarIcon, ClockIcon, MagnifyingGlassIcon, MinusCircleIcon, StarIcon, TrophyIcon, XCircleIcon, XMarkIcon,
} from '@heroicons/vue/24/outline';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Avatar, Badge, Button, Card, EmptyState, PageHeader, Select, Spinner, StatCard } from '@/Components/UI';
import { useToast } from '@/Composables/useToast';
import { autoLabel, canPlayAnyLevel, difficultyOptions, levelSummary, sharedPercent, valueFormula } from './scoring';

const props = defineProps({
    activeBattle: Object,
    recentBattles: Array,
    stats: Object,
    directions: Array,
    questionLevels: { type: Object, default: () => ({}) },
    minRounds: { type: Number, default: 3 },
    autoLevel: { type: String, default: null },
    scoring: { type: Object, default: () => ({}) },
});

const page = usePage();
const toast = useToast();
const authId = computed(() => page.props.auth?.user?.id);

const searching = ref(false);
const selectedDirection = ref(null);
const selectedDifficulty = ref(null); // null = automatic (by ELO)
const showRules = ref(false);
const currentCounts = computed(() => props.questionLevels?.[selectedDirection.value || 'all'] || {});
const levelOptions = computed(() => difficultyOptions(currentCounts.value, props.minRounds));
const canPlay = computed(() => (selectedDifficulty.value
    ? Number(currentCounts.value[selectedDifficulty.value] ?? 0) >= props.minRounds
    : canPlayAnyLevel(currentCounts.value, props.minRounds)));
// another topic may not have enough questions of the chosen level: back to automatic
watch(currentCounts, (counts) => {
    if (selectedDifficulty.value && Number(counts[selectedDifficulty.value] ?? 0) < props.minRounds) selectedDifficulty.value = null;
});
const searchTime = ref(0);
let searchInterval = null;
let pollInterval = null;

const directionOptions = computed(() => [

    ...(props.directions || []).map((d) => ({ value: d.id, label: d.name })),
]);

const stopTimers = () => {
    clearInterval(searchInterval);
    clearInterval(pollInterval);
    searchInterval = null;
    pollInterval = null;
};

const startSearch = () => {
    searching.value = true;
    searchTime.value = 0;

    searchInterval = setInterval(() => {
        searchTime.value++;
    }, 1000);

    axios.post(route('student.battles.find'), {
        direction_id: selectedDirection.value,
        difficulty: selectedDifficulty.value,
    }).then(response => {
        if (response.data.match_found) {
            router.visit(response.data.redirect);
        } else {
            // Poll for match
            pollForMatch(response.data.battle_id);
        }
    }).catch((e) => {
        searching.value = false;
        stopTimers();
        toast.error(e.response?.data?.message || 'Raqib qidirishni boshlab bo\'lmadi');
    });
};

const pollForMatch = (battleId) => {
    clearInterval(pollInterval);
    // battles.show renders an Inertia page (not JSON), so poll this page's activeBattle prop instead
    pollInterval = setInterval(() => {
        router.reload({
            only: ['activeBattle'],
            onSuccess: (page) => {
                if (page.props.activeBattle?.status === 'in_progress') {
                    stopTimers();
                    router.visit(route('student.battles.show', battleId));
                }
            },
        });
    }, 2000);
};

const cancelSearch = () => {
    searching.value = false;
    stopTimers();
    axios.post(route('student.battles.cancel'));
};

// Presentation helpers
const pad = (n) => String(n).padStart(2, '0');
const formatSeconds = (s) => `${pad(Math.floor(s / 60))}:${pad(s % 60)}`;
const formatDate = (date) => {
    if (!date) return '—';
    const d = new Date(date);
    if (Number.isNaN(d.getTime())) return '—';
    return `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()}`;
};

const RANKS = { bronze: 'Bronza', silver: 'Kumush', gold: 'Oltin', platinum: 'Platina', diamond: 'Olmos', master: 'Usta' };
const rankLabel = (r) => (r ? RANKS[r] || r : '—');

const isP1 = (b) => b.player1_id === authId.value;
const opponentOf = (b) => (isP1(b) ? b.player2 : b.player1);
const opponentName = (b) => {
    const o = opponentOf(b);
    if (!o) return 'Raqib';
    return o.full_name || [o.first_name, o.last_name].filter(Boolean).join(' ') || 'Raqib';
};
const myScore = (b) => (isP1(b) ? b.player1_score : b.player2_score) ?? 0;
const theirScore = (b) => (isP1(b) ? b.player2_score : b.player1_score) ?? 0;
const eloChange = (b) => {
    const v = isP1(b) ? b.player1_elo_change : b.player2_elo_change;
    return v === null || v === undefined ? null : Number(v);
};
const outcome = (b) => {
    if (!b.winner_id) return { label: 'Durang', variant: 'neutral', icon: MinusCircleIcon, chip: 'bg-surface-2 text-fg-muted' };
    if (b.winner_id === authId.value) return { label: 'G\'alaba', variant: 'success', icon: TrophyIcon, chip: 'bg-success-50 text-success' };
    return { label: 'Mag\'lubiyat', variant: 'danger', icon: XCircleIcon, chip: 'bg-danger-50 text-danger' };
};

onMounted(() => {
    if (props.activeBattle) {
        router.visit(route('student.battles.show', props.activeBattle.id));
    }
});

onUnmounted(() => {
    stopTimers();
});
</script>
